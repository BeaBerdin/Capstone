<?php

use App\Models\Course;
use App\Models\StudentApplication;
use App\Models\User;
use App\Notifications\StudentApplicationApproved;
use App\Notifications\StudentApplicationRejected;
use App\Notifications\StudentApplicationSubmitted;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

/*
| Self-registered teachers wait for the Super Admin (EDP) in the same queue as
| student applications, without a Study Load. Until approved they cannot open
| any teacher page. Existing teachers and teachers created by the Super Admin
| have no application row and are approved.
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    fakeFileDisks();
});

function registerTeacher(string $email = 'new.teacher@example.org'): User
{
    test()->post(route('register.store'), [
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'teacher',
    ])->assertSessionHasNoErrors();

    auth()->guard('web')->logout();

    return User::where('email', $email)->sole();
}

/** A self-registered teacher with one application in the given state. */
function teacherApplicant(string $status = StudentApplication::PENDING, array $attributes = []): array
{
    $teacher = pathwiseUser('teacher', array_merge(['email_verified_at' => null, 'name' => 'Maria Santos'], $attributes));
    $application = StudentApplication::submitForTeacher($teacher, 1);

    if ($status !== StudentApplication::PENDING) {
        $application->forceFill([
            'status' => $status,
            'reviewed_by' => pathwiseUser('super_admin')->id,
            'reviewed_at' => now(),
            'rejection_reason' => $status === StudentApplication::REJECTED ? 'We could not confirm that you teach here.' : null,
        ])->save();
    }

    return [$teacher->fresh(), $application->fresh()];
}

test('a new teacher registration is pending and the EDP is notified in-app and at the EDP mailbox', function () {
    Notification::fake();
    config(['mail.default' => 'smtp', 'services.pathwise.edp_notification_email' => 'edp-office@school.example.edu.ph']);
    $superAdmin = pathwiseUser('super_admin');

    $teacher = registerTeacher();
    $application = $teacher->studentApplications()->sole();

    expect($application->status)->toBe(StudentApplication::PENDING)
        ->and($application->hasStudyLoad())->toBeFalse()
        ->and($application->isTeacherApplication())->toBeTrue()
        ->and($teacher->awaitingApproval())->toBeTrue();

    Notification::assertSentTo($superAdmin, StudentApplicationSubmitted::class, fn ($n, array $channels) => $channels === ['database']
        && $n->toArray($superAdmin)['title'] === 'New teacher registration');

    Notification::assertSentOnDemand(StudentApplicationSubmitted::class, function ($notification, array $channels, AnonymousNotifiable $notifiable) use ($teacher) {
        $mail = $notification->toMail($notifiable);

        return $notifiable->routes['mail'] === ['edp-office@school.example.edu.ph']
            && $notification->application->user_id === $teacher->id
            && $mail->subject === 'PathWise - New teacher registration: Maria Santos';
    });

    Notification::assertNotSentTo($teacher, VerifyEmail::class);
});

test('a pending teacher is sent to the status page from every teacher page', function (string $route) {
    [$teacher] = teacherApplicant();

    $this->actingAs($teacher)
        ->get(route($route))
        ->assertRedirect(route('student-application.status'));
})->with(['dashboard', 'teacher.dashboard', 'teacher.my-courses', 'teacher.courses.create', 'teacher.assignments.index', 'course-invitations.index', 'teacher.analytics']);

test('a pending teacher stays blocked even with a verified email, and cannot create anything', function () {
    [$teacher] = teacherApplicant(StudentApplication::PENDING, ['email_verified_at' => now()]);

    $this->actingAs($teacher)
        ->get(route('teacher.dashboard'))
        ->assertRedirect(route('student-application.status'));

    $this->actingAs($teacher)
        ->post(route('teacher.courses.store'), ['title' => 'Sneaky course'])
        ->assertRedirect(route('student-application.status'))
        ->assertSessionHas('error');

    $this->actingAs($teacher)
        ->postJson(route('teacher.courses.store'), ['title' => 'Sneaky course'])
        ->assertForbidden();

    expect(Course::count())->toBe(0);
});

test('a pending teacher cannot verify the email with a signed link', function () {
    [$teacher] = teacherApplicant();

    $link = Illuminate\Support\Facades\URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $teacher->id,
        'hash' => sha1($teacher->email),
    ]);

    $this->get($link)->assertForbidden();

    expect($teacher->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('the status page explains the teacher registration', function () {
    [$teacher] = teacherApplicant();

    $this->actingAs($teacher)
        ->get(route('student-application.status'))
        ->assertOk()
        ->assertSee('Teacher registration')
        ->assertSee('reviewing your registration')
        ->assertDontSee('Study Load you uploaded');
});

test('the EDP queue labels teachers and can show teachers or students only', function () {
    [, $teacherApplication] = teacherApplicant();
    $student = pathwiseUser('student', ['name' => 'Juan Student']);
    StudentApplication::create([
        'user_id' => $student->id,
        'submission_no' => 1,
        'study_load_path' => 'study-loads/'.$student->id.'/load.pdf',
        'study_load_original_name' => 'load.pdf',
        'study_load_mime' => 'application/pdf',
        'study_load_size' => 10,
        'status' => StudentApplication::PENDING,
        'submitted_at' => now(),
    ]);
    $superAdmin = pathwiseUser('super_admin');

    $this->actingAs($superAdmin)
        ->get(route('super-admin.student-applications.index'))
        ->assertOk()
        ->assertSee('Maria Santos')
        ->assertSee('Juan Student')
        ->assertSee('data-test="applicant-type">Teacher<', false)
        ->assertSee('data-test="applicant-type">Student<', false);

    $this->actingAs($superAdmin)
        ->get(route('super-admin.student-applications.index', ['role' => 'teacher']))
        ->assertOk()
        ->assertSee('Maria Santos')
        ->assertDontSee('Juan Student');

    $this->actingAs($superAdmin)
        ->get(route('super-admin.student-applications.index', ['role' => 'student']))
        ->assertOk()
        ->assertSee('Juan Student')
        ->assertDontSee('Maria Santos');

    $this->actingAs($superAdmin)
        ->get(route('super-admin.student-applications.show', $teacherApplication))
        ->assertOk()
        ->assertSee('Teacher registration')
        ->assertSee('data-test="approve-button"', false)
        ->assertDontSee('Open Study Load');
});

test('approving a teacher sends the acceptance email, then the verified teacher gets access', function () {
    Notification::fake();
    [$teacher, $application] = teacherApplicant();

    $this->actingAs(pathwiseUser('super_admin'))
        ->post(route('super-admin.student-applications.approve', $application))
        ->assertSessionHas('success');

    expect($application->fresh()->status)->toBe(StudentApplication::APPROVED)
        ->and($teacher->fresh()->awaitingApproval())->toBeFalse();

    Notification::assertSentTo($teacher, StudentApplicationApproved::class, function ($notification) use ($teacher) {
        return $notification->toMail($teacher)->subject === 'PathWise - Your teacher registration is approved';
    });

    // The approval email's signed link verifies the email.
    $link = (new StudentApplicationApproved)->toMail($teacher)->viewData['buttonUrl'];
    $this->get($link);
    expect($teacher->fresh()->hasVerifiedEmail())->toBeTrue();

    $this->actingAs($teacher->fresh())
        ->get(route('teacher.dashboard'))
        ->assertOk();
});

test('a rejected teacher stays blocked, sees the reason and can ask for another review', function () {
    Notification::fake();
    [$teacher, $application] = teacherApplicant();

    $this->actingAs(pathwiseUser('super_admin'))
        ->post(route('super-admin.student-applications.reject', $application), [
            'rejection_reason' => 'We could not confirm that you teach here.',
        ])
        ->assertSessionHas('success');

    Notification::assertSentTo($teacher, StudentApplicationRejected::class, function ($notification) use ($teacher) {
        return $notification->toMail($teacher)->subject === 'PathWise - Your teacher registration was not approved';
    });

    $teacher = $teacher->fresh();
    expect($teacher->awaitingTeacherApproval())->toBeTrue();

    $this->actingAs($teacher)
        ->get(route('teacher.dashboard'))
        ->assertRedirect(route('student-application.status'));

    $this->actingAs($teacher)
        ->get(route('student-application.status'))
        ->assertOk()
        ->assertSee('We could not confirm that you teach here.')
        ->assertSee('data-test="teacher-review-again-form"', false)
        ->assertDontSee('Corrected Study Load');

    $this->actingAs($teacher)
        ->post(route('student-application.resubmit'))
        ->assertRedirect(route('student-application.status'))
        ->assertSessionHas('success');

    $latest = $teacher->fresh()->latestStudentApplication;
    expect($latest->submission_no)->toBe(2)
        ->and($latest->status)->toBe(StudentApplication::PENDING)
        ->and($latest->hasStudyLoad())->toBeFalse()
        ->and($application->fresh()->rejection_reason)->toBe('We could not confirm that you teach here.');

    Notification::assertSentTo(User::whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))->first(), StudentApplicationSubmitted::class, fn ($n) => $n->resubmission === true);

    // Still blocked until the EDP approves the new request.
    $this->actingAs($teacher->fresh())
        ->get(route('teacher.dashboard'))
        ->assertRedirect(route('student-application.status'));
});

test('a teacher cannot ask for another review while pending', function () {
    [$teacher] = teacherApplicant();

    $this->actingAs($teacher)
        ->post(route('student-application.resubmit'))
        ->assertSessionHas('error');

    expect($teacher->studentApplications()->count())->toBe(1);
});

test('a teacher created by the Super Admin is approved automatically', function () {
    $this->actingAs(pathwiseUser('super_admin'))
        ->post(route('users.store'), [
            'name' => 'Faculty Member',
            'email' => 'faculty@uclm.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'teacher',
        ])
        ->assertSessionHasNoErrors();

    $teacher = User::where('email', 'faculty@uclm.edu.ph')->sole();

    expect($teacher->hasRole('teacher'))->toBeTrue()
        ->and($teacher->studentApplications()->count())->toBe(0)
        ->and($teacher->awaitingApproval())->toBeFalse();

    $this->actingAs($teacher)
        ->get(route('teacher.dashboard'))
        ->assertOk();
});

test('an existing teacher without an application keeps working', function () {
    $teacher = pathwiseUser('teacher');

    expect($teacher->awaitingApproval())->toBeFalse();

    $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertOk();
    $this->actingAs($teacher)->get(route('teacher.my-courses'))->assertOk();
});
