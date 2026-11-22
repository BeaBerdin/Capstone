<?php

use App\Models\Role;
use App\Models\StudentApplication;
use App\Models\User;
use App\Notifications\StudentApplicationApproved;
use App\Notifications\VerifyTeacherEmail;
use App\Support\VerificationLogin;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
    // Teacher IDs go to a fake private disk, never to real storage.
    fakeFileDisks();
});

function teacherVerificationUser(string $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->roles()->attach(Role::firstOrCreate(['name' => $role])->id);

    return $user;
}

function teacherVerificationLink(User $teacher, ?StudentApplication $review = null): string
{
    $parameters = ['id' => $teacher->id, 'hash' => sha1($teacher->email)];

    if ($review) {
        $parameters['teacher_application'] = $review->id;
    }

    return URL::temporarySignedRoute('verification.verify', now()->addMinutes(15), $parameters);
}

function teacherVerificationPendingReview(User $teacher): StudentApplication
{
    return $teacher->teacherApplications()->sole();
}

test('teacher registration creates a pending review and blocks direct teacher tools and verification', function () {
    Role::firstOrCreate(['name' => 'teacher']);

    $this->post(route('register.store'), [
        'first_name' => 'Tina',
        'last_name' => 'Santos',
        'email' => 'tina@example.test',
        'password' => 'SafePass123!',
        'password_confirmation' => 'SafePass123!',
        'role' => 'teacher',
        'teacher_id' => teacherIdUpload(),
    ])->assertRedirect(route('student-application.status'));

    $teacher = User::where('email', 'tina@example.test')->sole();
    $review = teacherVerificationPendingReview($teacher);
    expect($review->status)->toBe(StudentApplication::PENDING)
        ->and($review->review_email)->toBe($teacher->email)
        ->and($review->review_origin)->toBe('registration')
        ->and($review->hasStudyLoad())->toBeTrue();

    Notification::assertNotSentTo($teacher, VerifyEmail::class);
    $this->assertAuthenticatedAs($teacher);
    $this->get(route('student-application.status'))->assertOk()->assertSee('Pending review');
    $this->get(route('teacher.dashboard'))->assertRedirect(route('student-application.status'));
    $this->postJson(route('teacher.courses.store'), [])->assertForbidden();
    $this->post(route('verification.send'))->assertRedirect(route('student-application.status'));
    $this->get(teacherVerificationLink($teacher, $review))->assertForbidden();
    expect($teacher->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('EDP approval requires faculty confirmation and only the current review link verifies a teacher', function () {
    $teacher = teacherVerificationUser('teacher', ['email_verified_at' => null]);
    $review = StudentApplication::submitForTeacher($teacher, 'registration');
    $edp = teacherVerificationUser('super_admin');
    $dean = teacherVerificationUser('admin');
    $approve = route('super-admin.student-applications.approve', $review);

    $this->actingAs($dean)->post($approve, ['faculty_confirmed' => '1'])->assertForbidden();
    $this->actingAs($edp)->post($approve)->assertSessionHasErrors('faculty_confirmed');
    expect($review->fresh()->status)->toBe(StudentApplication::PENDING);

    $this->post($approve, ['faculty_confirmed' => '1'])->assertSessionHas('success');
    expect($review->fresh()->reviewed_by)->toBe($edp->id)
        ->and($teacher->fresh()->hasVerifiedEmail())->toBeFalse();

    $mail = null;
    Notification::assertSentTo($teacher, StudentApplicationApproved::class, function ($notification) use ($teacher, &$mail) {
        $mail = $notification->toMail($teacher)->viewData['buttonUrl'];

        return true;
    });
    $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertRedirect(route('verification.notice'));
    $this->get(route('student-application.status'))->assertSee('Verify Email');
    $this->get(teacherVerificationLink($teacher))->assertForbidden();
    $this->get($mail)->assertRedirect();
    expect($teacher->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->actingAs($teacher->fresh());
    $this->get(route('dashboard'))->assertRedirect(route('teacher.dashboard'));
    $this->postJson(route('teacher.courses.store'), [])->assertSessionHasErrors('title');
});

test('an approved teacher can resend a review-bound link with the standard verification expiry', function () {
    $teacher = teacherVerificationUser('teacher', ['email_verified_at' => null]);
    $review = StudentApplication::submitForTeacher($teacher, 'registration');
    $edp = teacherVerificationUser('super_admin');
    $this->actingAs($edp)->post(route('super-admin.student-applications.approve', $review), ['faculty_confirmed' => '1']);

    $this->actingAs($teacher)->post(route('verification.send'))->assertSessionHas('status', 'verification-link-sent');
    $link = null;
    Notification::assertSentTo($teacher, VerifyTeacherEmail::class, function ($notification) use ($teacher, &$link) {
        $link = $notification->toMail($teacher)->viewData['url'];

        return true;
    });

    parse_str((string) parse_url($link, PHP_URL_QUERY), $parameters);
    expect($parameters['teacher_application'])->toBe((string) $review->id)
        ->and((int) $parameters['expires'])->toBe(now()->addMinutes(60)->timestamp);
    $this->travel(61)->minutes();
    $this->get($link)->assertForbidden();
    expect($teacher->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('cross-browser student verification requires an explicit one-time continuation', function () {
    $student = teacherVerificationUser('student', ['email_verified_at' => null]);
    $link = teacherVerificationLink($student);

    $this->assertGuest();
    $this->get($link)->assertOk()->assertSee('Continue as');
    $this->assertGuest();
    expect($student->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and(session()->has(VerificationLogin::SESSION_KEY))->toBeTrue();

    $this->post(route('verification.continue'))->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($student);
    expect(DB::table('verification_link_uses')->count())->toBe(1);

    // The same signed link cannot create a second sign-in grant after use.
    parse_str((string) parse_url($link, PHP_URL_QUERY), $parameters);
    expect(VerificationLogin::consume(
        $student->fresh(),
        VerificationLogin::linkHash($parameters['signature'] ?? null),
    ))->toBeFalse();
    expect(DB::table('verification_link_uses')->count())->toBe(1);
});

test('cross-browser verification never silently switches a signed-in account', function () {
    $student = teacherVerificationUser('student', ['email_verified_at' => null]);
    $otherAccount = teacherVerificationUser('admin');
    $link = teacherVerificationLink($student);

    $this->actingAs($otherAccount)->get($link)->assertOk()->assertSee('verification-switch');
    $this->post(route('verification.continue'))->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($otherAccount);
    expect(DB::table('verification_link_uses')->count())->toBe(0);

    // Switching only occurs after an explicit confirmation from the page.
    $this->get($link)->assertOk();
    $this->post(route('verification.continue'), ['switch' => '1'])->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($student);
    expect(DB::table('verification_link_uses')->count())->toBe(1);
});

test('teacher email verification on another browser does not offer student sign-in continuation', function () {
    $teacher = teacherVerificationUser('teacher', ['email_verified_at' => null]);
    $edp = teacherVerificationUser('super_admin');
    $review = StudentApplication::submitForTeacher($teacher, 'registration');
    $review->forceFill([
        'status' => StudentApplication::APPROVED,
        'reviewed_by' => $edp->id,
        'reviewed_at' => now(),
    ])->save();

    $this->get(teacherVerificationLink($teacher, $review))
        ->assertOk()
        ->assertDontSee('verification-continue', false);

    $this->assertGuest();
    expect($teacher->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and(session()->has(VerificationLogin::SESSION_KEY))->toBeFalse();
});

test('teacher tools require current EDP approval, verified email, and an active account', function () {
    $teacher = teacherVerificationUser('teacher', ['email_verified_at' => now()]);
    $review = StudentApplication::submitForTeacher($teacher, 'registration');

    $this->actingAs($teacher)->get(route('teacher.dashboard'))
        ->assertRedirect(route('student-application.status'));
    $this->postJson(route('teacher.courses.store'), [])->assertForbidden();

    $edp = teacherVerificationUser('super_admin');
    $review->forceFill([
        'status' => StudentApplication::APPROVED,
        'reviewed_by' => $edp->id,
        'reviewed_at' => now(),
    ])->save();
    $teacher->forceFill(['email_verified_at' => null])->save();

    $this->actingAs($teacher)->get(route('teacher.dashboard'))
        ->assertRedirect(route('verification.notice'));

    $teacher->forceFill(['email_verified_at' => now()])->save();
    $this->actingAs($teacher)->postJson(route('teacher.courses.store'), [])
        ->assertSessionHasErrors('title');

    $teacher->forceFill(['is_active' => false])->save();
    $this->actingAs($teacher)->get(route('teacher.dashboard'))
        ->assertRedirect(route('login'));
    $this->assertGuest();
});

test('a verified legacy teacher with no review can be queued by EDP and keeps the same account', function () {
    $teacher = teacherVerificationUser('teacher');
    $edp = teacherVerificationUser('super_admin');

    $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertRedirect(route('student-application.status'));
    $this->get(route('student-application.status'))->assertSee('Request EDP review');
    $this->postJson(route('users.teacher-review', $teacher))->assertForbidden();
    $this->actingAs($edp)->post(route('users.teacher-review', $teacher))->assertRedirect();
    $review = teacherVerificationPendingReview($teacher);
    expect($review->status)->toBe(StudentApplication::PENDING)
        ->and($review->review_origin)->toBe('edp_legacy_review');

    $this->post(route('users.teacher-review', $teacher))
        ->assertRedirect(route('super-admin.student-applications.show', $review));
    $this->post(route('super-admin.student-applications.approve', $review), ['faculty_confirmed' => '1']);
    expect($teacher->fresh()->hasVerifiedEmail())->toBeFalse()
        ->and($teacher->fresh()->approvedTeacherApplication()->id)->toBe($review->id);
});

test('a historical approval with no recorded email does not grant access and the teacher can request review', function () {
    $teacher = teacherVerificationUser('teacher');
    $edp = teacherVerificationUser('super_admin');
    $student = teacherVerificationUser('student');
    $historyId = DB::table('student_applications')->insertGetId([
        'user_id' => $teacher->id,
        'submission_no' => 1,
        'applicant_role' => StudentApplication::TEACHER,
        'review_email' => null,
        'review_origin' => 'legacy_history',
        'study_load_path' => '',
        'study_load_original_name' => '',
        'study_load_mime' => '',
        'study_load_size' => 0,
        'status' => StudentApplication::APPROVED,
        'reviewed_by' => $edp->id,
        'reviewed_at' => now(),
        'submitted_at' => now(),
    ]);
    DB::table('student_applications')->insert([
        'user_id' => $student->id,
        'submission_no' => 1,
        'study_load_path' => 'study-loads/existing.pdf',
        'study_load_original_name' => 'existing.pdf',
        'study_load_mime' => 'application/pdf',
        'study_load_size' => 42,
        'status' => StudentApplication::PENDING,
        'submitted_at' => now(),
    ]);
    $history = StudentApplication::findOrFail($historyId);
    expect($history->isTeacherApplication())->toBeTrue()
        ->and($history->review_email)->toBeNull()
        ->and($student->fresh()->awaitingStudentApproval())->toBeTrue();

    $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertRedirect(route('student-application.status'));
    $this->post(route('student-application.resubmit'))->assertSessionHas('success');
    expect($teacher->currentTeacherApplication()->review_origin)->toBe('legacy_request')
        ->and($teacher->currentTeacherApplication()->status)->toBe(StudentApplication::PENDING)
        ->and($history->fresh()->status)->toBe(StudentApplication::APPROVED);
});

test('a rejected teacher cannot use a signed link and can resubmit with a new Teacher ID', function () {
    $teacher = teacherVerificationUser('teacher', ['email_verified_at' => null]);
    $review = StudentApplication::submitForTeacher($teacher, 'registration');
    $edp = teacherVerificationUser('super_admin');

    $this->actingAs($edp)->post(route('super-admin.student-applications.reject', $review), [
        'rejection_reason' => 'Please ask the department to confirm your faculty address.',
    ])->assertSessionHas('success');

    $this->actingAs($teacher)->get(teacherVerificationLink($teacher, $review))->assertForbidden();
    $this->get(route('student-application.status'))->assertSee('Rejected')->assertSee('faculty address');
    $this->postJson(route('teacher.courses.store'), [])->assertForbidden();
    $this->post(route('student-application.resubmit'), ['teacher_id' => teacherIdUpload()])->assertSessionHas('success');
    $this->post(route('student-application.resubmit'), ['teacher_id' => teacherIdUpload()])->assertSessionHas('error');
    expect($review->fresh()->status)->toBe(StudentApplication::REJECTED)
        ->and($teacher->teacherApplications()->count())->toBe(2)
        ->and($teacher->currentTeacherApplication()->status)->toBe(StudentApplication::PENDING);
});

test('promotion cannot reuse an approved student application or its verified email', function () {
    $student = teacherVerificationUser('student');
    $edp = teacherVerificationUser('super_admin');
    Role::firstOrCreate(['name' => 'teacher']);
    $studyLoad = $student->studentApplications()->create([
        'applicant_role' => StudentApplication::STUDENT,
        'submission_no' => 1,
        'study_load_path' => 'study-loads/sample.pdf',
        'study_load_original_name' => 'sample.pdf',
        'study_load_mime' => 'application/pdf',
        'study_load_size' => 30,
        'status' => StudentApplication::APPROVED,
        'submitted_at' => now(),
    ]);
    $oldLink = teacherVerificationLink($student);

    $this->actingAs($edp)->put(route('users.update-role', $student), ['role' => 'teacher', 'reason' => 'Hired as faculty'])
        ->assertSessionHas('success');

    $teacher = $student->fresh();
    expect($teacher->hasVerifiedEmail())->toBeFalse()
        ->and($teacher->approvedTeacherApplication())->toBeNull()
        ->and($teacher->currentTeacherApplication()->submission_no)->toBe(2)
        ->and($studyLoad->fresh()->isTeacherApplication())->toBeFalse();
    $this->actingAs($teacher)->get($oldLink)->assertForbidden();
    $this->get(route('teacher.dashboard'))->assertRedirect(route('student-application.status'));
});

test('admin email edits require new approval and invalidate a prior link even if the address changes back', function () {
    $teacher = teacherVerificationUser('teacher', ['email_verified_at' => null]);
    $edp = teacherVerificationUser('super_admin');
    $review = StudentApplication::submitForTeacher($teacher, 'registration');
    $firstEmail = $teacher->email;
    $oldLink = teacherVerificationLink($teacher, $review);

    $this->actingAs($edp)->post(route('super-admin.student-applications.approve', $review), ['faculty_confirmed' => '1']);
    $this->actingAs($teacher)->get($oldLink)->assertRedirect();
    expect($teacher->fresh()->hasVerifiedEmail())->toBeTrue();

    $edit = route('users.update', $teacher);
    $this->actingAs($edp)->put($edit, ['name' => 'Tina Santos', 'email' => 'new-address@example.test']);
    $stale = $teacher->fresh()->currentTeacherApplication();
    $this->put($edit, ['name' => 'Tina Santos', 'email' => $firstEmail]);
    $this->post(route('super-admin.student-applications.approve', $stale), ['faculty_confirmed' => '1'])
        ->assertSessionHas('error');
    $teacher = $teacher->fresh();
    $latest = $teacher->currentTeacherApplication();
    expect($teacher->hasVerifiedEmail())->toBeFalse()
        ->and($latest->review_origin)->toBe('email_change')
        ->and($latest->id)->not->toBe($review->id);
    $this->post(route('super-admin.student-applications.approve', $latest), ['faculty_confirmed' => '1']);
    $this->actingAs($teacher)->get($oldLink)->assertForbidden();
    expect($teacher->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('a student still uploads a study load and verifies through the student approval link', function () {
    Role::firstOrCreate(['name' => 'student']);
    $edp = teacherVerificationUser('super_admin');
    Storage::fake('local');

    $this->post(route('register.store'), [
        'first_name' => 'Juan',
        'last_name' => 'Cruz',
        'email' => 'juan@example.test',
        'password' => 'SafePass123!',
        'password_confirmation' => 'SafePass123!',
        'role' => 'student',
        'study_load' => UploadedFile::fake()->createWithContent('study-load.pdf', "%PDF-1.4 test"),
    ])->assertRedirect(route('student-application.status'));

    $student = User::where('email', 'juan@example.test')->sole();
    $review = $student->studentApplications()->sole();
    Storage::disk('local')->assertExists($review->study_load_path);
    expect($student->teacherApplications()->exists())->toBeFalse()
        ->and($student->awaitingStudentApproval())->toBeTrue();

    $this->actingAs($edp)->post(route('super-admin.student-applications.approve', $review))
        ->assertSessionHas('success');
    $this->actingAs($student)->get(teacherVerificationLink($student))->assertRedirect();
    $this->actingAs($student->fresh());
    $this->get(route('dashboard'))->assertRedirect(route('student.dashboard'));
});

test('a teacher changing email in profile gets a fresh review and loses prior verification', function () {
    $teacher = teacherVerificationUser('teacher');
    $edp = teacherVerificationUser('super_admin');
    $review = StudentApplication::submitForTeacher($teacher, 'registration');
    $review->forceFill(['status' => StudentApplication::APPROVED, 'reviewed_by' => $edp->id, 'reviewed_at' => now()])->save();

    $this->actingAs($teacher);
    Livewire::test('pages::settings.profile')
        ->set('email', 'profile-change@example.test')
        ->call('updateProfileInformation')
        ->assertRedirect(route('student-application.status'));

    $teacher = $teacher->fresh();
    expect($teacher->email)->toBe('profile-change@example.test')
        ->and($teacher->hasVerifiedEmail())->toBeFalse()
        ->and($teacher->currentTeacherApplication()->status)->toBe(StudentApplication::PENDING)
        ->and($teacher->currentTeacherApplication()->review_email)->toBe($teacher->email);
});

test('EDP-created teachers are pending and manual or bulk verification cannot verify them', function () {
    $edp = teacherVerificationUser('super_admin');
    $student = teacherVerificationUser('student', ['email_verified_at' => null]);
    Role::firstOrCreate(['name' => 'teacher']);

    $this->actingAs($edp)->post(route('users.store'), [
        'name' => 'Tina Santos',
        'email' => 'edp-created@example.test',
        'password' => 'SafePass123!',
        'password_confirmation' => 'SafePass123!',
        'role' => 'teacher',
    ])->assertRedirect(route('users.index'));

    $teacher = User::where('email', 'edp-created@example.test')->sole();
    $review = teacherVerificationPendingReview($teacher);
    expect($review->review_origin)->toBe('edp_created');
    $this->patch(route('users.verify-email', $teacher))->assertSessionHas('error');
    $this->post(route('super-admin.student-applications.approve', $review), ['faculty_confirmed' => '1']);
    $this->patch(route('users.verify-email', $teacher))->assertSessionHas('error');
    $teacher->forceFill(['created_at' => now()->subDay()])->save();
    $this->artisan('users:verify-existing')->assertSuccessful();
    expect($teacher->fresh()->hasVerifiedEmail())->toBeFalse();
    $this->patch(route('users.verify-email', $student))->assertSessionHas('success');
    expect($student->fresh()->hasVerifiedEmail())->toBeTrue();
});
