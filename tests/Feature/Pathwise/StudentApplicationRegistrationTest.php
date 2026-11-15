<?php

use App\Models\StudentApplication;
use App\Models\User;
use App\Notifications\StudentApplicationApproved;
use App\Notifications\StudentApplicationSubmitted;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/*
| Student registration with a Study Load, and what a student may do while the
| application waits for the Super Admin (EDP). Teachers are not affected.
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    fakeFileDisks();
});

function registrationPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Juan',
        'middle_initial' => 'D',
        'last_name' => 'Dela Cruz',
        'email' => 'juan.student@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'student',
        'study_load' => UploadedFile::fake()->create('my study load.pdf', 300, 'application/pdf'),
    ], $overrides);
}

/** A student with one application in the given state (file on the fake private disk). */
function applicant(string $status = StudentApplication::PENDING, array $userAttributes = []): User
{
    $user = pathwiseUser('student', array_merge(['email_verified_at' => null], $userAttributes));
    $path = 'study-loads/'.$user->id.'/first.pdf';
    Storage::disk('local')->put($path, '%PDF-1.4 study load');

    $application = StudentApplication::create([
        'user_id' => $user->id,
        'submission_no' => 1,
        'study_load_path' => $path,
        'study_load_original_name' => 'first.pdf',
        'study_load_mime' => 'application/pdf',
        'study_load_size' => 19,
        'status' => $status,
        'submitted_at' => now(),
    ]);

    if ($status !== StudentApplication::PENDING) {
        $application->forceFill([
            'reviewed_by' => pathwiseUser('super_admin')->id,
            'reviewed_at' => now(),
            'rejection_reason' => $status === StudentApplication::REJECTED ? 'The Study Load is blurry and unreadable.' : null,
        ])->save();
    }

    return $user->fresh();
}

/*
|--------------------------------------------------------------------------
| Registration
|--------------------------------------------------------------------------
*/

test('a student with a valid Study Load is registered with one pending application, logged in and sent to the status page', function () {
    $this->post(route('register.store'), registrationPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('student-application.status'));

    $user = User::where('email', 'juan.student@example.com')->sole();
    $application = $user->studentApplications()->sole();

    $this->assertAuthenticatedAs($user);

    expect($user->hasRole('student'))->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeFalse()
        ->and($application->status)->toBe('pending')
        ->and($application->submission_no)->toBe(1)
        ->and($application->study_load_original_name)->toBe('my study load.pdf')
        ->and($application->study_load_mime)->toBe('application/pdf')
        ->and($application->study_load_path)->toStartWith('study-loads/'.$user->id.'/')
        ->and($application->study_load_path)->not->toContain('my study load');

    Storage::disk('local')->assertExists($application->study_load_path);
    expect(Storage::disk('public')->allFiles())->toBe([]);

    $this->get(route('student-application.status'))->assertOk()->assertSee('Pending review');
});

test('JPG and PNG Study Loads are accepted', function (string $name) {
    $this->post(route('register.store'), registrationPayload([
        'study_load' => UploadedFile::fake()->image($name, 800, 1000),
    ]))->assertSessionHasNoErrors();

    expect(StudentApplication::count())->toBe(1);
})->with(['load.jpg', 'load.png']);

test('a student cannot register without a Study Load', function () {
    $this->post(route('register.store'), registrationPayload(['study_load' => null]))
        ->assertSessionHasErrors(['study_load' => 'Please upload your Study Load (PDF, JPG or PNG).']);

    $this->assertGuest();
    expect(User::count())->toBe(0)->and(StudentApplication::count())->toBe(0);
});

test('other file types, or a non-PDF renamed to .pdf, are rejected', function (UploadedFile $file) {
    $this->post(route('register.store'), registrationPayload(['study_load' => $file]))
        ->assertSessionHasErrors('study_load');

    expect(User::count())->toBe(0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'word document' => fn () => UploadedFile::fake()->create('load.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
    'text renamed to pdf' => fn () => UploadedFile::fake()->create('load.pdf', 10, 'text/plain'),
    'executable' => fn () => UploadedFile::fake()->create('load.exe', 10, 'application/x-msdownload'),
    'svg image' => fn () => UploadedFile::fake()->create('load.svg', 10, 'image/svg+xml'),
]);

test('a Study Load over 10 MB is rejected with the 10 MB message', function () {
    $this->post(route('register.store'), registrationPayload([
        'study_load' => UploadedFile::fake()->create('big.pdf', 10241, 'application/pdf'),
    ]))->assertSessionHasErrors(['study_load' => StudentApplication::TOO_LARGE_MESSAGE]);

    expect(User::count())->toBe(0);
});

test('a Study Load of exactly 10 MB is accepted', function () {
    $this->post(route('register.store'), registrationPayload([
        'study_load' => UploadedFile::fake()->create('max.pdf', 10240, 'application/pdf'),
    ]))->assertSessionHasNoErrors();

    expect(StudentApplication::count())->toBe(1);
});

test('a teacher registers without a Study Load, gets no application and keeps the usual verification email', function () {
    Notification::fake();

    $this->post(route('register.store'), registrationPayload([
        'email' => 'teacher@example.com',
        'role' => 'teacher',
        'study_load' => null,
    ]))->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $teacher = User::where('email', 'teacher@example.com')->sole();

    expect($teacher->hasRole('teacher'))->toBeTrue()
        ->and($teacher->studentApplications()->count())->toBe(0)
        ->and($teacher->awaitingStudentApproval())->toBeFalse();

    Notification::assertSentTo($teacher, VerifyEmail::class);

    // Existing teacher flow: verify first, then the teacher dashboard.
    $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
});

test('a Study Load sent with a teacher registration is ignored and not stored', function () {
    $this->post(route('register.store'), registrationPayload([
        'email' => 'teacher@example.com',
        'role' => 'teacher',
        'study_load' => UploadedFile::fake()->create('sneaky.pdf', 100, 'application/pdf'),
    ]))->assertSessionHasNoErrors();

    expect(StudentApplication::count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([])
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

test('tampered role values are rejected and nothing is created', function (string $role) {
    $this->post(route('register.store'), registrationPayload(['role' => $role]))
        ->assertSessionHasErrors('role');

    $this->assertGuest();
    expect(User::count())->toBe(0)->and(Storage::disk('local')->allFiles())->toBe([]);
})->with(['admin', 'super_admin', 'superadmin', 'Student ', 'dean', '']);

test('if saving the application fails, the account and the file are rolled back too', function () {
    StudentApplication::creating(function () {
        throw new RuntimeException('Simulated database failure');
    });

    $this->withoutExceptionHandling();

    expect(fn () => $this->post(route('register.store'), registrationPayload()))
        ->toThrow(RuntimeException::class, 'Simulated database failure');

    expect(User::where('email', 'juan.student@example.com')->exists())->toBeFalse()
        ->and(StudentApplication::count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);

    $this->assertGuest();
});

test('a pending student gets no verification email, and every active Super Admin is notified', function () {
    Notification::fake();

    $edp = pathwiseUser('super_admin');
    $inactiveEdp = pathwiseUser('super_admin');
    $inactiveEdp->forceFill(['is_active' => false])->save();
    $dean = pathwiseUser('admin');

    $this->post(route('register.store'), registrationPayload())->assertSessionHasNoErrors();

    $student = User::where('email', 'juan.student@example.com')->sole();

    Notification::assertNotSentTo($student, VerifyEmail::class);
    Notification::assertNotSentTo($student, StudentApplicationApproved::class);

    Notification::assertSentTo($edp, StudentApplicationSubmitted::class, fn ($n) => ! $n->resubmission
        && $n->application->user_id === $student->id);
    Notification::assertNotSentTo($inactiveEdp, StudentApplicationSubmitted::class);
    Notification::assertNotSentTo($dean, StudentApplicationSubmitted::class);
});

test('the EDP notification links to the application and opens it', function () {
    $edp = pathwiseUser('super_admin');

    $this->post(route('register.store'), registrationPayload());
    auth()->guard('web')->logout();

    $application = StudentApplication::sole();
    $notification = $edp->notifications()->sole();

    expect($notification->data['type'])->toBe(StudentApplicationSubmitted::TYPE)
        ->and($notification->data['application_id'])->toBe($application->id);

    $this->actingAs($edp)
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect(route('super-admin.student-applications.show', $application));
});

/*
|--------------------------------------------------------------------------
| Registration form (server-rendered checks; the switching itself is also
| checked in a real browser, see the report)
|--------------------------------------------------------------------------
*/

test('the registration form uploads files and shows the required Study Load for students', function () {
    $html = $this->get(route('register'))->assertOk()->getContent();

    expect($html)
        ->toContain('enctype="multipart/form-data"')
        ->toContain('name="study_load"')
        ->toContain('data-study-load-section')
        ->toContain('accept=".pdf,.jpg,.jpeg,.png')
        ->toContain('Data privacy: your Study Load contains personal information.')
        ->toContain('authorized EDP (Super Admin) personnel');

    // Student is the default: the field is visible and required.
    $section = substr($html, strpos($html, '<div data-study-load-section'), 2500);
    expect($section)->not->toMatch('/data-study-load-section\s+hidden/')
        ->and($section)->toMatch('/data-study-load-input/')
        ->and(preg_match('/<input[^>]*name="study_load"[^>]*>/s', $section, $input))->toBe(1)
        ->and($input[0])->toContain('required')
        ->and($input[0])->not->toContain('disabled');
});

test('after a failed teacher registration the Study Load stays hidden, disabled and optional', function () {
    $html = $this->withSession(['_old_input' => ['role' => 'teacher']])
        ->get(route('register'))
        ->getContent();

    preg_match('/<input[^>]*name="study_load"[^>]*>/s', $html, $input);

    expect($html)->toMatch('/data-study-load-section\s+hidden/')
        ->and($input[0])->toContain('disabled')
        ->and($input[0])->not->toMatch('/\srequired[\s>]/');
});

test('the switching script clears the file for Teacher and requires it again for Student', function () {
    $html = $this->get(route('register'))->getContent();

    expect($html)
        ->toContain("section.hidden = !isStudent;")
        ->toContain('input.required = isStudent;')
        ->toContain('input.disabled = !isStudent;')
        ->toContain("input.value = '';")
        ->toContain("radio.addEventListener('change', sync);")
        ->toContain('var maxBytes = 10240 * 1024;');
});

/*
|--------------------------------------------------------------------------
| Rate limits
|--------------------------------------------------------------------------
*/

test('registration allows 5 attempts per email and IP in 10 minutes', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('register.store'), registrationPayload(['first_name' => '', 'email' => ' Same@Example.com ']))
            ->assertStatus(302);
    }

    // Same email in another letter case is the same key.
    $this->post(route('register.store'), registrationPayload(['first_name' => '', 'email' => 'same@example.com']))
        ->assertStatus(429);

    // Another student on the same school network is not blocked.
    $this->post(route('register.store'), registrationPayload(['first_name' => '', 'email' => 'classmate@example.com']))
        ->assertStatus(302);

    $this->travel(11)->minutes();

    $this->post(route('register.store'), registrationPayload(['first_name' => '', 'email' => 'same@example.com']))
        ->assertStatus(302);
});

test('registration allows 30 attempts per IP in 10 minutes, whatever the email', function () {
    foreach (range(1, 30) as $attempt) {
        $this->post(route('register.store'), registrationPayload(['first_name' => '', 'email' => "student{$attempt}@example.com"]))
            ->assertStatus(302);
    }

    $this->post(route('register.store'), registrationPayload(['first_name' => '', 'email' => 'brand-new@example.com']))
        ->assertStatus(429);
});

/*
|--------------------------------------------------------------------------
| Access while the application is pending or rejected
|--------------------------------------------------------------------------
*/

test('pending and rejected students cannot open student pages and are sent to the status page', function (string $status) {
    $student = applicant($status);
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'price' => 0]);

    $this->actingAs($student);

    foreach ([
        route('dashboard'),
        route('student.dashboard'),
        route('student.marketplace'),
        route('student.my-courses'),
        route('student.course.show', $course),
        route('student.learn.course', $course),
        route('student.learning-paths'),
        route('student.recommendations'),
        route('verification.notice'),
    ] as $url) {
        $this->get($url)->assertRedirect(route('student-application.status'));
    }

    // Actions are refused too, and nothing is created.
    $this->post(route('student.enroll', $course))->assertRedirect(route('student-application.status'));
    $this->postJson(route('student.enroll', $course))->assertForbidden();
    $this->withHeader('X-Livewire', 'true')->post(route('student.enroll', $course))->assertForbidden();

    expect(App\Models\Enrollment::count())->toBe(0);
})->with(['pending', 'rejected']);

test('pending students may still see their status page, profile settings and an invitation preview, but not accept it', function () {
    $student = applicant();
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 0]);
    $invitation = App\Models\CourseInvitation::create(['course_id' => $course->id, 'created_by' => $teacher->id, 'code' => 'PENDINGCASE1']);

    $this->actingAs($student);

    $this->get(route('student-application.status'))->assertOk()->assertSee('Pending review');
    $this->get(route('profile.edit'))->assertOk();
    $this->get(route('course-invitations.show', $invitation->code))->assertOk();

    $this->post(route('course-invitations.accept', $invitation->code))
        ->assertRedirect(route('student-application.status'));

    expect(App\Models\Enrollment::count())->toBe(0);
});

test('Livewire requests from protected pages re-check the application', function () {
    expect(Livewire\Livewire::getPersistentMiddleware())
        ->toContain(App\Http\Middleware\EnsureStudentApplicationApproved::class);
});

test('an approved but unverified student is asked to verify, not sent to the status page', function () {
    $student = applicant('approved');

    $this->actingAs($student)->get(route('student.dashboard'))->assertRedirect(route('verification.notice'));
    $this->actingAs($student)->get(route('verification.notice'))->assertOk();
});

test('an approved and verified student can use the student dashboard', function () {
    $student = applicant('approved', ['email_verified_at' => now()]);

    $this->actingAs($student)->get(route('student.dashboard'))->assertOk();
    $this->actingAs($student)->get(route('dashboard'))->assertRedirect(route('student.dashboard'));
});

test('existing accounts without an application keep their access unchanged', function () {
    $existingStudent = pathwiseUser('student');
    $teacher = pathwiseUser('teacher');
    $dean = pathwiseUser('admin');
    $edp = pathwiseUser('super_admin');

    expect($existingStudent->applicationStatus())->toBe('approved')
        ->and($existingStudent->awaitingStudentApproval())->toBeFalse()
        ->and(StudentApplication::count())->toBe(0);

    $this->actingAs($existingStudent)->get(route('student.dashboard'))->assertOk();
    $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertOk();
    $this->actingAs($dean)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($edp)->get('/super-admin-dashboard')->assertOk();

    // An existing unverified student keeps the existing "verify your email" flow.
    $unverified = pathwiseUser('student', ['email_verified_at' => null]);
    $this->actingAs($unverified)->get(route('student.dashboard'))->assertRedirect(route('verification.notice'));

    // Nothing was written for them.
    expect(StudentApplication::count())->toBe(0);
});

test('a teacher, admin or super admin with no application is never sent to the status page', function (string $role) {
    $user = pathwiseUser($role);

    $this->actingAs($user)->get(route('student-application.status'))->assertRedirect(route('dashboard'));
})->with(['teacher', 'admin', 'super_admin', 'student']);

/*
|--------------------------------------------------------------------------
| Verification is blocked until approval
|--------------------------------------------------------------------------
*/

test('pending and rejected students cannot request a verification email', function (string $status) {
    Notification::fake();
    $student = applicant($status);

    $this->actingAs($student)
        ->post(route('verification.send'))
        ->assertRedirect(route('student-application.status'));

    // Even a direct call cannot send it.
    $student->sendEmailVerificationNotification();

    Notification::assertNothingSentTo($student);
})->with(['pending', 'rejected']);

test('pending and rejected students cannot verify, even with a valid signed link', function (string $status) {
    $student = applicant($status);

    $link = Illuminate\Support\Facades\URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $student->id,
        'hash' => sha1($student->email),
    ]);

    $this->get($link)->assertForbidden();
    $this->actingAs($student)->get($link)->assertForbidden();

    expect($student->fresh()->hasVerifiedEmail())->toBeFalse();
})->with(['pending', 'rejected']);

test('an approved but unverified student can request a new verification email', function () {
    Notification::fake();
    $student = applicant('approved');

    $this->actingAs($student)
        ->from(route('verification.notice'))
        ->post(route('verification.send'))
        ->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentTo($student, VerifyEmail::class);
});

test('the Super Admin cannot mark a pending or rejected applicant as verified', function (string $status) {
    $student = applicant($status);

    $this->actingAs(pathwiseUser('super_admin'))
        ->patch(route('users.verify-email', $student))
        ->assertSessionHas('error');

    expect($student->fresh()->hasVerifiedEmail())->toBeFalse();
})->with(['pending', 'rejected']);

test('the Super Admin can still mark an approved or existing account as verified', function () {
    $approved = applicant('approved');
    $existing = pathwiseUser('teacher', ['email_verified_at' => null]);
    $edp = pathwiseUser('super_admin');

    $this->actingAs($edp)->patch(route('users.verify-email', $approved))->assertSessionHas('success');
    $this->actingAs($edp)->patch(route('users.verify-email', $existing))->assertSessionHas('success');

    expect($approved->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and($existing->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('users:verify-existing never verifies a pending or rejected applicant', function () {
    $pending = applicant('pending');
    $rejected = applicant('rejected');
    $existing = pathwiseUser('student', ['email_verified_at' => null]);

    $this->travel(1)->minutes();
    $this->artisan('users:verify-existing')->assertSuccessful();

    expect($pending->fresh()->hasVerifiedEmail())->toBeFalse()
        ->and($rejected->fresh()->hasVerifiedEmail())->toBeFalse()
        ->and($existing->fresh()->hasVerifiedEmail())->toBeTrue();
});
