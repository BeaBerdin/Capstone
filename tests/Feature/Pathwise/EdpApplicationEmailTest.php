<?php

use App\Models\StudentApplication;
use App\Models\User;
use App\Notifications\Channels\SafeMailChannel;
use App\Notifications\StudentApplicationSubmitted;
use App\Support\EdpNotificationRecipients;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

/*
| "New / resubmitted application" emails to the EDP office.
|
| - PATHWISE_EDP_NOTIFICATION_EMAIL set: exactly one email, to that mailbox;
|   Super Admin accounts get the in-app notification only.
| - Not set: active Super Admins whose address can receive mail are emailed;
|   reserved test addresses (superadmin@pathwise.test, *.example ...) never are.
| - The in-app notification is always created.
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    fakeFileDisks();
    Notification::fake();

    // Behave as if real email were configured (tests normally use "array"),
    // and start without a mailbox whatever the local .env says.
    config([
        'mail.default' => 'smtp',
        'services.pathwise.edp_notification_email' => null,
    ]);
});

function edpRegisterStudent(string $email = 'new.student@school.edu.ph'): User
{
    test()->post(route('register.store'), [
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'student',
        'study_load' => UploadedFile::fake()->create('load.pdf', 200, 'application/pdf'),
    ])->assertSessionHasNoErrors();

    auth()->guard('web')->logout();

    return User::where('email', $email)->sole();
}

function edpRegisterTeacher(string $email = 'new.teacher@school.edu.ph'): User
{
    test()->post(route('register.store'), [
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'teacher',
        'teacher_id' => teacherIdUpload(),
    ])->assertSessionHasNoErrors();

    auth()->guard('web')->logout();

    return User::where('email', $email)->sole();
}

test('the config key the recipients class reads exists and is read from the environment', function () {
    expect(config()->has('services.pathwise.edp_notification_email'))->toBeTrue();

    config(['services.pathwise.edp_notification_email' => ' edp@uclm.edu.ph , not-an-email, EDP@uclm.edu.ph ']);

    expect(EdpNotificationRecipients::configured())->toBe(['edp@uclm.edu.ph']);
});

test('reserved test addresses are never deliverable', function (string $email, bool $deliverable) {
    expect(EdpNotificationRecipients::isDeliverable($email))->toBe($deliverable);
})->with([
    ['superadmin@pathwise.test', false],
    ['edp@school.example', false],
    ['edp@example.com', false],
    ['edp@mail.example.org', false],
    ['edp@localhost', false],
    ['edp@uclm.edu.ph', true],
]);

test('with a configured mailbox, it gets exactly one email per application and Super Admins get in-app only', function (string $role) {
    config(['services.pathwise.edp_notification_email' => 'edp-office@uclm.edu.ph']);
    $testAdmin = pathwiseUser('super_admin', ['email' => 'superadmin@pathwise.test']);
    $realAdmin = pathwiseUser('super_admin', ['email' => 'edp.head@uclm.edu.ph']);

    $applicant = $role === 'teacher' ? edpRegisterTeacher() : edpRegisterStudent();

    Notification::assertSentOnDemandTimes(StudentApplicationSubmitted::class, 1);
    Notification::assertSentOnDemand(StudentApplicationSubmitted::class, function ($notification, array $channels, AnonymousNotifiable $notifiable) use ($applicant) {
        return $notifiable->routes['mail'] === ['edp-office@uclm.edu.ph']
            && $channels === [SafeMailChannel::class]
            && $notification->application->user_id === $applicant->id;
    });

    // In-app for every Super Admin; no email to their account addresses.
    foreach ([$testAdmin, $realAdmin] as $superAdmin) {
        Notification::assertSentTo($superAdmin, StudentApplicationSubmitted::class, fn ($n, array $channels) => $channels === ['database']);
    }
})->with(['student', 'teacher']);

test('without a mailbox, a .test Super Admin address gets no email but still the in-app notification', function (string $role) {
    $testAdmin = pathwiseUser('super_admin', ['email' => 'superadmin@pathwise.test']);
    $realAdmin = pathwiseUser('super_admin', ['email' => 'edp.head@uclm.edu.ph']);

    $role === 'teacher' ? edpRegisterTeacher() : edpRegisterStudent();

    Notification::assertSentTo($testAdmin, StudentApplicationSubmitted::class, fn ($n, array $channels) => $channels === ['database']);
    Notification::assertSentTo($realAdmin, StudentApplicationSubmitted::class, fn ($n, array $channels) => $channels === ['database', SafeMailChannel::class]);
    Notification::assertSentOnDemandTimes(StudentApplicationSubmitted::class, 0);
})->with(['student', 'teacher']);

test('the in-app notification is stored for the Super Admin', function () {
    Notification::swap(new Illuminate\Notifications\ChannelManager(app()));
    config(['mail.default' => 'array']);
    $superAdmin = pathwiseUser('super_admin', ['email' => 'superadmin@pathwise.test']);

    edpRegisterTeacher();

    $stored = $superAdmin->notifications()->sole();

    expect($stored->type)->toBe(StudentApplicationSubmitted::class)
        ->and($stored->data['type'])->toBe(StudentApplicationSubmitted::TYPE);
});

test('with mail not configured nobody is emailed, in-app still works', function () {
    config(['mail.default' => 'log', 'services.pathwise.edp_notification_email' => 'edp-office@uclm.edu.ph']);
    $superAdmin = pathwiseUser('super_admin', ['email' => 'edp.head@uclm.edu.ph']);

    edpRegisterStudent();

    Notification::assertSentTo($superAdmin, StudentApplicationSubmitted::class, fn ($n, array $channels) => $channels === ['database']);

    // The mailbox notification has no channel, so nothing is sent to it.
    Notification::assertSentOnDemandTimes(StudentApplicationSubmitted::class, 0);
});

test('a resubmitted student application also goes only to the configured mailbox', function () {
    config(['services.pathwise.edp_notification_email' => 'edp-office@uclm.edu.ph']);
    pathwiseUser('super_admin', ['email' => 'superadmin@pathwise.test']);
    $student = edpRegisterStudent();
    StudentApplication::where('user_id', $student->id)->update(['status' => StudentApplication::REJECTED, 'rejection_reason' => 'Blurry Study Load photo.']);

    $this->actingAs($student)
        ->post(route('student-application.resubmit'), ['study_load' => UploadedFile::fake()->create('load2.pdf', 200, 'application/pdf')])
        ->assertSessionHas('success');

    Notification::assertSentOnDemandTimes(StudentApplicationSubmitted::class, 2);
    Notification::assertSentOnDemand(StudentApplicationSubmitted::class, fn ($n) => $n->resubmission === true);
});

/*
|--------------------------------------------------------------------------
| Wording: teacher registrations vs student applications
|--------------------------------------------------------------------------
*/

function edpMailText(StudentApplicationSubmitted $notification): array
{
    $mail = $notification->toMail(new AnonymousNotifiable);
    $html = view($mail->view[0], $mail->viewData)->render();

    return [$mail->subject, $html, $notification->toArray(new AnonymousNotifiable)];
}

test('a new teacher registration email uses Teacher ID wording', function () {
    $teacher = edpRegisterTeacher();
    $review = $teacher->teacherApplications()->sole();

    [$subject, $html, $inApp] = edpMailText(new StudentApplicationSubmitted($review));

    expect($subject)->toBe('PathWise - New teacher registration: Maria Santos')
        ->and($html)->toContain('Maria Santos registered as a teacher and uploaded a Teacher ID for verification.')
        ->and($html)->toContain('Please review the Teacher ID and approve or reject the registration.')
        ->and($html)->toContain('Review registration')
        ->and($html)->not->toContain('Study Load')
        // Only the review link's route path mentions "student-applications".
        ->and($html)->not->toContain('registered as a student')
        ->and($html)->not->toContain('student application')
        ->and($html)->not->toContain('The student')
        ->and($inApp['title'])->toBe('New teacher registration')
        ->and($inApp['message'])->toBe('Maria Santos is waiting for Teacher ID verification.');
});

test('a resubmitted teacher registration email uses Teacher ID wording', function () {
    $teacher = edpRegisterTeacher();
    $review = $teacher->teacherApplications()->sole();
    $review->forceFill(['status' => StudentApplication::REJECTED, 'rejection_reason' => 'The ID photo is blurry.', 'reviewed_at' => now()])->save();

    $this->actingAs($teacher)
        ->post(route('student-application.resubmit'), ['teacher_id' => teacherIdUpload('clear-id.png')])
        ->assertSessionHas('success');

    $new = $teacher->fresh()->currentTeacherApplication();

    [$subject, $html, $inApp] = edpMailText(new StudentApplicationSubmitted($new, resubmission: true));

    expect($subject)->toBe('PathWise - Teacher registration resubmitted: Maria Santos')
        ->and($html)->toContain('Maria Santos uploaded a new Teacher ID (submission #'.$new->submission_no.') after the previous one was rejected.')
        ->and($html)->not->toContain('Study Load')
        ->and($inApp['title'])->toBe('Teacher registration resubmitted')
        ->and($inApp['message'])->toBe('Maria Santos uploaded a new Teacher ID for review.');
});

test('a teacher review started by the EDP office is worded without a Teacher ID', function () {
    $teacher = pathwiseUser('teacher', ['name' => 'Ana Reyes', 'email_verified_at' => null]);
    $review = StudentApplication::submitForTeacher($teacher, 'edp_created');

    [$subject, $html, $inApp] = edpMailText(new StudentApplicationSubmitted($review));

    expect($subject)->toBe('PathWise - New teacher registration: Ana Reyes')
        ->and($html)->toContain('Please confirm the faculty member with the department')
        ->and($html)->not->toContain('Teacher ID')
        ->and($html)->not->toContain('Study Load')
        ->and($inApp['message'])->toBe('Ana Reyes is waiting for teacher registration review.');
});

test('student application emails keep the Study Load wording', function () {
    $student = edpRegisterStudent();
    $application = StudentApplication::where('user_id', $student->id)->sole();

    [$subject, $html, $inApp] = edpMailText(new StudentApplicationSubmitted($application));
    [$reSubject, $reHtml, $reInApp] = edpMailText(new StudentApplicationSubmitted($application, resubmission: true));

    expect($subject)->toBe('PathWise - New student application: Juan Dela Cruz')
        ->and($html)->toContain('Juan Dela Cruz registered as a student and uploaded a Study Load for verification.')
        ->and($html)->toContain('Please review the Study Load and approve or reject the application.')
        ->and($inApp['title'])->toBe('New student application')
        ->and($inApp['message'])->toBe('Juan Dela Cruz is waiting for Study Load verification.')
        ->and($reSubject)->toBe('PathWise - Student application resubmitted: Juan Dela Cruz')
        ->and($reHtml)->toContain('uploaded a corrected Study Load (submission #1) after the previous one was rejected.')
        ->and($reInApp['message'])->toBe('Juan Dela Cruz uploaded a corrected Study Load for review.');
});
