<?php

use App\Models\StudentApplication;
use App\Models\User;
use App\Notifications\StudentApplicationApproved;
use App\Notifications\StudentApplicationRejected;
use App\Notifications\StudentApplicationSubmitted;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/*
| The Super Admin (EDP) reviews Study Loads; only the Super Admin may decide.
| Approval sends a 72-hour verification link; rejection sends the reason and
| the student resubmits with the same account. History is never lost.
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    fakeFileDisks();
});

/** A student with one application in the given state. */
function reviewApplicant(string $status = StudentApplication::PENDING): array
{
    $student = pathwiseUser('student', ['email_verified_at' => null, 'name' => 'Juan Dela Cruz']);
    $path = 'study-loads/'.$student->id.'/load-1.pdf';
    Storage::disk('local')->put($path, '%PDF-1.4 first study load');

    $application = StudentApplication::create([
        'user_id' => $student->id,
        'submission_no' => 1,
        'study_load_path' => $path,
        'study_load_original_name' => 'study load.pdf',
        'study_load_mime' => 'application/pdf',
        'study_load_size' => 26,
        'status' => StudentApplication::PENDING,
        'submitted_at' => now(),
    ]);

    if ($status !== StudentApplication::PENDING) {
        $application->forceFill([
            'status' => $status,
            'reviewed_by' => pathwiseUser('super_admin')->id,
            'reviewed_at' => now(),
            'rejection_reason' => $status === StudentApplication::REJECTED ? 'The Study Load is blurry and unreadable.' : null,
        ])->save();
    }

    return [$student->fresh(), $application->fresh()];
}

/*
|--------------------------------------------------------------------------
| Authorization: Super Admin (EDP) only
|--------------------------------------------------------------------------
*/

test('only the Super Admin can list, view, open, approve or reject applications', function (string $role) {
    [$student, $application] = reviewApplicant();
    $user = pathwiseUser($role);

    $this->actingAs($user);

    $this->get(route('super-admin.student-applications.index'))->assertForbidden();
    $this->get(route('super-admin.student-applications.show', $application))->assertForbidden();
    $this->get(route('super-admin.student-applications.study-load', $application))->assertForbidden();
    $this->post(route('super-admin.student-applications.approve', $application))->assertForbidden();
    $this->post(route('super-admin.student-applications.reject', $application), ['rejection_reason' => 'Not allowed to decide this.'])->assertForbidden();

    expect($application->fresh()->status)->toBe('pending')
        ->and($application->fresh()->reviewed_by)->toBeNull();
})->with(['admin', 'teacher', 'student']);

test('guests are sent to the login page for every application route', function () {
    [, $application] = reviewApplicant();

    $this->get(route('super-admin.student-applications.index'))->assertRedirect(route('login'));
    $this->get(route('super-admin.student-applications.study-load', $application))->assertRedirect(route('login'));
    $this->post(route('super-admin.student-applications.approve', $application))->assertRedirect(route('login'));
});

test('the applicant cannot decide or change their own application', function () {
    [$student, $application] = reviewApplicant();

    $this->actingAs($student)->post(route('super-admin.student-applications.approve', $application))
        ->assertRedirect(route('student-application.status'));

    // A forged "status" on resubmission is ignored (and refused while pending).
    $this->actingAs($student)->post(route('student-application.resubmit'), [
        'status' => 'approved',
        'study_load' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
    ]);

    expect($application->fresh()->status)->toBe('pending')
        ->and(StudentApplication::count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Super Admin pages and the private file
|--------------------------------------------------------------------------
*/

test('the Super Admin sees pending applications with a count, tabs and the review page', function () {
    [$student, $application] = reviewApplicant();
    reviewApplicant('approved');
    reviewApplicant('rejected');
    $edp = pathwiseUser('super_admin');

    $this->actingAs($edp)->get(route('super-admin.student-applications.index'))
        ->assertOk()
        ->assertSee('Student Applications')
        ->assertSee($student->name)
        ->assertSee($student->email)
        ->assertSee('data-test="pending-applications-count"', false)
        ->assertSee(route('super-admin.student-applications.show', $application), false);

    $this->actingAs($edp)->get(route('super-admin.student-applications.index', ['status' => 'rejected']))
        ->assertOk()->assertDontSee($student->email);

    $this->actingAs($edp)->get(route('super-admin.student-applications.show', $application))
        ->assertOk()
        ->assertSee('Juan Dela Cruz')
        ->assertSee('study load.pdf')
        ->assertSee(route('super-admin.student-applications.study-load', $application), false)
        ->assertSee('data-test="approve-button"', false)
        ->assertSee('data-test="reject-button"', false);
});

test('the Study Load is served privately to the Super Admin only, with safe headers', function () {
    [, $application] = reviewApplicant();

    $response = $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('super-admin.student-applications.study-load', $application))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($response->headers->get('Content-Disposition'))->toContain('inline')
        ->and($response->headers->get('Content-Disposition'))->toContain('study-load-'.$application->user_id.'-submission-1.pdf');

    // It lives on the private disk only: no public copy and no /storage URL.
    Storage::disk('local')->assertExists($application->study_load_path);
    Storage::disk('public')->assertMissing($application->study_load_path);
    $this->get('/storage/'.$application->study_load_path)->assertNotFound();
});

test('a missing Study Load file gives 404, not an error', function () {
    [, $application] = reviewApplicant();
    Storage::disk('local')->delete($application->study_load_path);

    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('super-admin.student-applications.study-load', $application))
        ->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Approval
|--------------------------------------------------------------------------
*/

test('approval records the reviewer and sends the acceptance email with the verification link', function () {
    Notification::fake();
    [$student, $application] = reviewApplicant();
    $edp = pathwiseUser('super_admin', ['name' => 'EDP Officer']);

    Notification::assertNothingSentTo($student);

    $this->actingAs($edp)
        ->post(route('super-admin.student-applications.approve', $application))
        ->assertRedirect(route('super-admin.student-applications.show', $application))
        ->assertSessionHas('success');

    $application->refresh();

    expect($application->status)->toBe('approved')
        ->and($application->reviewed_by)->toBe($edp->id)
        ->and($application->reviewed_at)->not->toBeNull()
        ->and($application->rejection_reason)->toBeNull()
        ->and($student->fresh()->awaitingStudentApproval())->toBeFalse();

    Notification::assertSentTo($student, StudentApplicationApproved::class, function (StudentApplicationApproved $notification) use ($student) {
        $mail = $notification->toMail($student);
        $html = (string) $mail->render();

        expect($mail->subject)->toBe('PathWise - Your student application is approved')
            ->and($html)->toContain('Your application is approved!')
            ->and($html)->toContain('Verify Email Address')
            ->and($html)->toContain('This link expires in 72 hours.')
            ->and($mail->viewData['buttonUrl'])->toContain('/email/verify/'.$student->id.'/'.sha1($student->email));

        return true;
    });

    // The ordinary verification email is not sent in addition.
    Notification::assertNotSentTo($student, VerifyEmail::class);
});

function approvalLinkFor(User $student): string
{
    return (new StudentApplicationApproved)->toMail($student)->viewData['buttonUrl'];
}

test('the approval link is valid for 72 hours, then refused', function () {
    [$student, $application] = reviewApplicant();

    $this->actingAs(pathwiseUser('super_admin'))->post(route('super-admin.student-applications.approve', $application));
    auth()->guard('web')->logout();

    $link = approvalLinkFor($student->fresh());
    parse_str((string) parse_url($link, PHP_URL_QUERY), $query);

    expect((int) $query['expires'])->toBeGreaterThanOrEqual(now()->addHours(72)->timestamp - 5)
        ->and((int) $query['expires'])->toBeLessThanOrEqual(now()->addHours(72)->timestamp + 5);

    // Expired after 72 hours: refused, still unverified.
    $this->travel(73)->hours();
    $this->get($link)->assertForbidden();
    expect($student->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('the approval link still works after 71 hours and verifies the student', function () {
    [$student, $application] = reviewApplicant();

    $this->actingAs(pathwiseUser('super_admin'))->post(route('super-admin.student-applications.approve', $application));
    auth()->guard('web')->logout();

    $link = approvalLinkFor($student->fresh());

    $this->travel(71)->hours();
    $this->get($link)->assertOk()->assertSee('Your email address has been verified.');

    expect($student->fresh()->hasVerifiedEmail())->toBeTrue();

    $this->actingAs($student->fresh())->get(route('student.dashboard'))->assertOk();
});

test('a tampered approval link is refused', function () {
    [$student, $application] = reviewApplicant();
    $this->actingAs(pathwiseUser('super_admin'))->post(route('super-admin.student-applications.approve', $application));
    auth()->guard('web')->logout();

    $link = approvalLinkFor($student->fresh());

    $this->get(str_replace('expires=', 'expires=9', $link))->assertForbidden();
    expect($student->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('ordinary verification links keep their 60-minute expiry', function () {
    Notification::fake();
    $teacher = pathwiseUser('teacher', ['email_verified_at' => null]);

    $teacher->sendEmailVerificationNotification();

    $link = null;
    Notification::assertSentTo($teacher, VerifyEmail::class, function (VerifyEmail $notification) use ($teacher, &$link) {
        $link = $notification->toMail($teacher)->viewData['url'];

        return true;
    });

    parse_str((string) parse_url($link, PHP_URL_QUERY), $query);
    expect((int) $query['expires'])->toBeLessThanOrEqual(now()->addMinutes(60)->timestamp + 5);

    $this->travel(61)->minutes();
    $this->get($link)->assertForbidden();
    expect($teacher->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('after the approval link expires, the approved student can get a fresh one', function () {
    [$student, $application] = reviewApplicant();
    $this->actingAs(pathwiseUser('super_admin'))->post(route('super-admin.student-applications.approve', $application));

    $this->travel(80)->hours();
    Notification::fake();

    $this->actingAs($student->fresh())
        ->from(route('student-application.status'))
        ->post(route('verification.send'))
        ->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentTo($student, VerifyEmail::class);
});

test('if the approval email fails, the approval is kept and the Super Admin is warned', function () {
    [$student, $application] = reviewApplicant();
    config(['mail.default' => 'no-such-mailer']);

    $this->actingAs(pathwiseUser('super_admin'))
        ->post(route('super-admin.student-applications.approve', $application))
        ->assertRedirect(route('super-admin.student-applications.show', $application))
        ->assertSessionHas('success', 'Application approved.')
        ->assertSessionHas('warning', fn ($warning) => str_contains($warning, 'could not be sent')
            && str_contains($warning, 'Resend verification email'));

    expect($application->fresh()->status)->toBe('approved');
});

/*
|--------------------------------------------------------------------------
| Rejection
|--------------------------------------------------------------------------
*/

test('rejection requires a reason of at least 10 characters', function (?string $reason) {
    [, $application] = reviewApplicant();

    $this->actingAs(pathwiseUser('super_admin'))
        ->from(route('super-admin.student-applications.show', $application))
        ->post(route('super-admin.student-applications.reject', $application), ['rejection_reason' => $reason])
        ->assertSessionHasErrors('rejection_reason');

    expect($application->fresh()->status)->toBe('pending');
})->with([null, '', 'too short', '         ']);

test('rejection saves the reason and reviewer and emails the reason with resubmission steps', function () {
    Notification::fake();
    [$student, $application] = reviewApplicant();
    $edp = pathwiseUser('super_admin');
    $reason = 'Your Study Load is from last semester. Please upload the current one.';

    $this->actingAs($edp)
        ->post(route('super-admin.student-applications.reject', $application), ['rejection_reason' => "  {$reason}  "])
        ->assertSessionHas('success');

    $application->refresh();

    expect($application->status)->toBe('rejected')
        ->and($application->rejection_reason)->toBe($reason)
        ->and($application->reviewed_by)->toBe($edp->id);

    Notification::assertSentTo($student, StudentApplicationRejected::class, function (StudentApplicationRejected $notification) use ($student, $reason) {
        $html = (string) $notification->toMail($student)->render();

        expect($html)->toContain(e($reason))
            ->and($html)->toContain('Log in and resubmit')
            ->and($html)->toContain('You do not need a new account.');

        return true;
    });

    Notification::assertNotSentTo($student, VerifyEmail::class);
    Notification::assertNotSentTo($student, StudentApplicationApproved::class);
});

test('if the rejection email fails, the rejection is kept and the Super Admin is warned', function () {
    [, $application] = reviewApplicant();
    config(['mail.default' => 'no-such-mailer']);

    $this->actingAs(pathwiseUser('super_admin'))
        ->post(route('super-admin.student-applications.reject', $application), ['rejection_reason' => 'Please upload a clearer copy.'])
        ->assertSessionHas('warning');

    expect($application->fresh()->status)->toBe('rejected');
});

test('the submission time is never changed by an approval or a rejection', function (string $decision) {
    Notification::fake();
    [, $application] = reviewApplicant();

    $submittedAt = now()->subDays(3)->startOfSecond();
    StudentApplication::whereKey($application->id)->update(['submitted_at' => $submittedAt]);

    $this->travel(2)->hours();

    $route = $decision === 'approve'
        ? route('super-admin.student-applications.approve', $application)
        : route('super-admin.student-applications.reject', $application);

    $this->actingAs(pathwiseUser('super_admin'))
        ->post($route, ['rejection_reason' => 'Please upload a clearer copy of the Study Load.'])
        ->assertSessionHas('success');

    $application->refresh();

    expect($application->status)->toBe($decision === 'approve' ? 'approved' : 'rejected')
        ->and($application->submitted_at->equalTo($submittedAt))->toBeTrue()
        ->and($application->reviewed_at->greaterThan($submittedAt))->toBeTrue();
})->with(['approve', 'reject']);

test('submitted_at has an explicit default so the database never auto-updates it', function () {
    $migration = file_get_contents(database_path('migrations/2026_11_15_000001_create_student_applications_table.php'));

    expect($migration)->toContain("\$table->timestamp('submitted_at')->useCurrent();");
});

test('a decided application cannot be decided again or overwritten', function () {
    Notification::fake();
    [$student, $application] = reviewApplicant();
    $first = pathwiseUser('super_admin', ['name' => 'First Reviewer']);
    $second = pathwiseUser('super_admin', ['name' => 'Second Reviewer']);

    $this->actingAs($first)->post(route('super-admin.student-applications.approve', $application));

    $this->actingAs($second)
        ->post(route('super-admin.student-applications.reject', $application), ['rejection_reason' => 'Trying to overwrite the decision.'])
        ->assertSessionHas('error', fn ($error) => str_contains($error, 'already approved by First Reviewer'));

    $this->actingAs($second)
        ->post(route('super-admin.student-applications.approve', $application))
        ->assertSessionHas('error');

    $application->refresh();

    expect($application->status)->toBe('approved')
        ->and($application->reviewed_by)->toBe($first->id)
        ->and($application->rejection_reason)->toBeNull();

    Notification::assertSentToTimes($student, StudentApplicationApproved::class, 1);
    Notification::assertNotSentTo($student, StudentApplicationRejected::class);
});

/*
|--------------------------------------------------------------------------
| Resubmission and history
|--------------------------------------------------------------------------
*/

test('a rejected student resubmits with the same account; the new submission is pending and history is kept', function () {
    [$student, $first] = reviewApplicant('rejected');
    $edp = pathwiseUser('super_admin');
    Notification::fake();

    $this->actingAs($student)
        ->get(route('student-application.status'))
        ->assertOk()
        ->assertSee('The Study Load is blurry and unreadable.')
        ->assertSee('data-test="resubmit-form"', false);

    $this->actingAs($student)
        ->post(route('student-application.resubmit'), [
            'study_load' => UploadedFile::fake()->create('clearer copy.pdf', 400, 'application/pdf'),
        ])
        ->assertRedirect(route('student-application.status'))
        ->assertSessionHas('success');

    $second = StudentApplication::where('user_id', $student->id)->orderByDesc('submission_no')->first();

    expect(StudentApplication::where('user_id', $student->id)->count())->toBe(2)
        ->and($second->submission_no)->toBe(2)
        ->and($second->status)->toBe('pending')
        ->and($second->study_load_original_name)->toBe('clearer copy.pdf')
        ->and($second->study_load_path)->not->toBe($first->study_load_path)
        ->and($student->fresh()->applicationStatus())->toBe('pending')
        ->and(User::where('email', $student->email)->count())->toBe(1);

    // The first submission, its file and its decision are untouched.
    $first->refresh();
    expect($first->status)->toBe('rejected')
        ->and($first->rejection_reason)->toBe('The Study Load is blurry and unreadable.')
        ->and($first->reviewed_by)->not->toBeNull();
    Storage::disk('local')->assertExists($first->study_load_path);
    Storage::disk('local')->assertExists($second->study_load_path);

    // EDP notified of the resubmission; still no verification email.
    Notification::assertSentTo($edp, StudentApplicationSubmitted::class, fn ($n) => $n->resubmission && $n->application->is($second));
    Notification::assertNotSentTo($student, VerifyEmail::class);

    // The Super Admin can see both submissions and decisions.
    $this->actingAs($edp)->get(route('super-admin.student-applications.show', $second))
        ->assertOk()
        ->assertSee('Submission #1')
        ->assertSee('Submission #2')
        ->assertSee('The Study Load is blurry and unreadable.');

    $this->actingAs($edp)->get(route('super-admin.student-applications.study-load', $first))->assertOk();
});

test('resubmission is refused while pending or after approval', function (string $status) {
    [$student] = reviewApplicant($status);

    $this->actingAs($student)
        ->post(route('student-application.resubmit'), [
            'study_load' => UploadedFile::fake()->create('again.pdf', 100, 'application/pdf'),
        ]);

    expect(StudentApplication::where('user_id', $student->id)->count())->toBe(1)
        ->and(Storage::disk('local')->allFiles('study-loads/'.$student->id))->toHaveCount(1);
})->with(['pending', 'approved']);

test('a double-submitted resubmission creates only one new application', function () {
    [$student] = reviewApplicant('rejected');

    foreach (range(1, 2) as $attempt) {
        $this->actingAs($student)->post(route('student-application.resubmit'), [
            'study_load' => UploadedFile::fake()->create("copy-{$attempt}.pdf", 100, 'application/pdf'),
        ]);
    }

    expect(StudentApplication::where('user_id', $student->id)->count())->toBe(2)
        ->and(StudentApplication::where('user_id', $student->id)->where('status', 'pending')->count())->toBe(1)
        ->and(Storage::disk('local')->allFiles('study-loads/'.$student->id))->toHaveCount(2);
});

test('resubmission validates the file like registration', function () {
    [$student] = reviewApplicant('rejected');

    $this->actingAs($student)
        ->post(route('student-application.resubmit'), [
            'study_load' => UploadedFile::fake()->create('too-big.pdf', 10241, 'application/pdf'),
        ])
        ->assertSessionHasErrors(['study_load' => StudentApplication::TOO_LARGE_MESSAGE]);

    $this->actingAs($student)
        ->post(route('student-application.resubmit'), [
            'study_load' => UploadedFile::fake()->create('notes.txt', 5, 'text/plain'),
        ])
        ->assertSessionHasErrors('study_load');

    expect(StudentApplication::where('user_id', $student->id)->count())->toBe(1);
});

test('resubmissions are rate limited per account', function () {
    [$student] = reviewApplicant('rejected');

    foreach (range(1, 5) as $attempt) {
        $this->actingAs($student)->post(route('student-application.resubmit'), ['study_load' => null])->assertStatus(302);
    }

    $this->actingAs($student)->post(route('student-application.resubmit'), ['study_load' => null])->assertStatus(429);
});

test('the full workflow: register, reject, resubmit, approve, verify, use the dashboard', function () {
    Notification::fake();
    $edp = pathwiseUser('super_admin');

    // 1-7. Register with a Study Load; pending; EDP notified.
    $this->post(route('register.store'), [
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'student',
        'study_load' => UploadedFile::fake()->create('load.pdf', 200, 'application/pdf'),
    ])->assertRedirect(route('student-application.status'));

    $student = User::where('email', 'maria@example.com')->sole();
    $this->get(route('student.dashboard'))->assertRedirect(route('student-application.status'));
    auth()->guard('web')->logout();

    // 8-9, 11. Rejected with a reason.
    $first = StudentApplication::sole();
    $this->actingAs($edp)->post(route('super-admin.student-applications.reject', $first), ['rejection_reason' => 'Upload the current semester Study Load.']);
    Notification::assertSentTo($student, StudentApplicationRejected::class);

    // Resubmission.
    $this->actingAs($student)->post(route('student-application.resubmit'), [
        'study_load' => UploadedFile::fake()->create('current.pdf', 200, 'application/pdf'),
    ]);
    $second = StudentApplication::where('submission_no', 2)->sole();

    // 10. Approved: acceptance email with the link.
    $this->actingAs($edp)->post(route('super-admin.student-applications.approve', $second));
    Notification::assertSentTo($student, StudentApplicationApproved::class);

    // 12. Approved but unverified: must verify first.
    $this->actingAs($student->fresh())->get(route('student.dashboard'))->assertRedirect(route('verification.notice'));

    // 13. Verified through the emailed link: dashboard access.
    auth()->guard('web')->logout();
    $this->get(approvalLinkFor($student->fresh()))->assertOk();
    $this->actingAs($student->fresh())->get(route('student.dashboard'))->assertOk();

    expect(StudentApplication::where('user_id', $student->id)->pluck('status')->all())->toBe(['rejected', 'approved']);
});
