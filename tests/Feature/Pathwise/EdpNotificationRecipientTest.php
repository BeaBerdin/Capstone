<?php

use App\Models\StudentApplication;
use App\Models\User;
use App\Notifications\StudentApplicationSubmitted;
use App\Support\EdpNotificationRecipients;
use App\Support\StudyLoads;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/*
| The EDP's "new / resubmitted student application" email goes to the
| configured mailbox (PATHWISE_EDP_NOTIFICATION_EMAIL), not to the Super
| Admin's login email, which may be undeliverable (superadmin@pathwise.test).
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    fakeFileDisks();

    // Behave as if real email were configured (tests normally use "array").
    config(['mail.default' => 'smtp']);
});

function registerApplicant(string $email = 'new.student@example.org'): User
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

test('the email goes to the configured EDP mailbox, not to the Super Admin login email', function () {
    Notification::fake();
    config(['services.pathwise.edp_notification_email' => 'edp-office@school.example.edu.ph']);

    $superAdmin = pathwiseUser('super_admin', ['email' => 'superadmin@pathwise.test']);

    $student = registerApplicant();

    // Email: only the configured mailbox.
    Notification::assertSentOnDemand(StudentApplicationSubmitted::class, function ($notification, array $channels, AnonymousNotifiable $notifiable) use ($student) {
        return $notifiable->routes['mail'] === ['edp-office@school.example.edu.ph']
            && $channels === [App\Notifications\Channels\SafeMailChannel::class]
            && $notification->application->user_id === $student->id
            && $notification->resubmission === false;
    });

    // The Super Admin account still gets the in-app notification, but no email.
    Notification::assertSentTo($superAdmin, StudentApplicationSubmitted::class, fn ($n, array $channels) => $channels === ['database']);

    // The Super Admin login email is unchanged.
    expect($superAdmin->fresh()->email)->toBe('superadmin@pathwise.test');
});

test('several configured mailboxes are supported and invalid entries are ignored', function () {
    config(['services.pathwise.edp_notification_email' => ' edp@school.example.edu.ph , not-an-email, registrar@school.example.edu.ph, EDP@school.example.edu.ph ']);

    expect(EdpNotificationRecipients::configured())->toBe([
        'edp@school.example.edu.ph',
        'registrar@school.example.edu.ph',
    ]);
});

test('resubmissions are emailed to the configured mailbox as well', function () {
    Notification::fake();
    config(['services.pathwise.edp_notification_email' => 'edp-office@school.example.edu.ph']);
    pathwiseUser('super_admin', ['email' => 'superadmin@pathwise.test']);

    $student = registerApplicant();
    StudentApplication::query()->update(['status' => 'rejected', 'rejection_reason' => 'Please upload a clearer copy.']);

    $this->actingAs($student)->post(route('student-application.resubmit'), [
        'study_load' => UploadedFile::fake()->create('clearer.pdf', 200, 'application/pdf'),
    ])->assertSessionHas('success');

    Notification::assertSentOnDemand(StudentApplicationSubmitted::class, fn ($n, $channels, $notifiable) => $n->resubmission
        && $notifiable->routes['mail'] === ['edp-office@school.example.edu.ph']);
});

test('without the setting, an undeliverable Super Admin login email is not emailed and a warning is logged', function () {
    Notification::fake();
    Log::spy();
    config(['services.pathwise.edp_notification_email' => null]);

    $superAdmin = pathwiseUser('super_admin', ['email' => 'superadmin@pathwise.test']);

    registerApplicant();

    Notification::assertSentTo($superAdmin, StudentApplicationSubmitted::class, fn ($n, array $channels) => $channels === ['database']);
    Notification::assertSentOnDemandTimes(StudentApplicationSubmitted::class, 0);

    Log::shouldHaveReceived('warning')->withArgs(fn ($message) => str_contains($message, 'PATHWISE_EDP_NOTIFICATION_EMAIL'))->once();
});

test('without the setting, a deliverable Super Admin email keeps receiving the email', function () {
    Notification::fake();
    config(['services.pathwise.edp_notification_email' => '']);

    $superAdmin = pathwiseUser('super_admin', ['email' => 'edp.head@school-domain.edu.ph']);

    registerApplicant();

    Notification::assertSentTo($superAdmin, StudentApplicationSubmitted::class, fn ($n, array $channels) => in_array(App\Notifications\Channels\SafeMailChannel::class, $channels, true)
        && in_array('database', $channels, true));
});

test('reserved test domains are recognised as undeliverable', function (string $email, bool $deliverable) {
    expect(EdpNotificationRecipients::isDeliverable($email))->toBe($deliverable);
})->with([
    ['superadmin@pathwise.test', false],
    ['a@localhost', false],
    ['a@host.local', false],
    ['a@example.com', false],
    ['a@mail.example.org', false],
    ['a@site.invalid', false],
    ['edp@school-domain.edu.ph', true],
    ['someone@gmail.com', true],
    ['not-an-email', false],
]);

test('the EDP email has no attachment and no Study Load, and its content is unchanged', function () {
    $edp = pathwiseUser('super_admin', ['name' => 'EDP Head']);
    $student = registerApplicant();
    $application = StudentApplication::sole();

    $mail = (new StudentApplicationSubmitted($application))->toMail(new AnonymousNotifiable);
    $html = (string) $mail->render();

    expect($mail->subject)->toBe('PathWise - New student application: '.$student->name)
        ->and($mail->attachments)->toBe([])
        ->and($mail->rawAttachments)->toBe([])
        ->and($html)->toContain('New student application')
        ->and($html)->toContain('Review application')
        ->and($html)->toContain(route('super-admin.student-applications.show', $application))
        ->and($html)->not->toContain($application->study_load_path)
        ->and($html)->not->toContain('/study-load');
});

test('a failed email to the EDP mailbox never undoes the registration', function () {
    config([
        'services.pathwise.edp_notification_email' => 'edp-office@school.example.edu.ph',
        'mail.default' => 'no-such-mailer',
    ]);

    // MailSettings treats any other mailer name as configured, so a send is attempted and fails.
    $student = registerApplicant();

    expect($student->studentApplications()->sole()->status)->toBe('pending');
});

test('notifyEdp sends nothing when there is no application', function () {
    Notification::fake();
    config(['services.pathwise.edp_notification_email' => 'edp-office@school.example.edu.ph']);

    StudyLoads::notifyEdp(null, resubmission: false);

    Notification::assertNothingSent();
});
