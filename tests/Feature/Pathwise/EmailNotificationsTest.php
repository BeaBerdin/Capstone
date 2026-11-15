<?php

use App\Models\CourseInvitation;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Transaction;
use App\Notifications\Channels\SafeMailChannel;
use App\Notifications\PathwiseNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

/*
| B2 / Manuscript Fig. 41 (SMTP email for enrollment updates and certificate
| issuance): enrollment-confirmed and certificate-issued emails, sent once,
| only when real mail is configured, and never breaking the action.
| No real email is sent: "smtp" here uses an in-memory transport.
*/

/**
 * Pretend real mail is configured, but keep messages in memory.
 */
function mailIsConfigured(): void
{
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp' => ['transport' => 'array'],
    ]);
}

/**
 * Pretend real mail is configured, but every send fails like a broken SMTP server.
 */
function mailIsBroken(): void
{
    Mail::extend('failing', fn () => new class extends AbstractTransport
    {
        protected function doSend(SentMessage $message): void
        {
            throw new TransportException('Connection to SMTP server refused.');
        }

        public function __toString(): string
        {
            return 'failing://';
        }
    });

    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp' => ['transport' => 'failing'],
    ]);
}

/**
 * @return array<int, \Symfony\Component\Mime\Email>
 */
function sentEmails(): array
{
    return array_map(
        fn ($sent) => $sent->getOriginalMessage(),
        iterator_to_array(Mail::mailer('smtp')->getSymfonyTransport()->messages())
    );
}

function emailCourse(array $attributes = []): App\Models\Course
{
    return pathwiseCourse(pathwiseUser('teacher'), array_merge([
        'status' => 'published',
        'price' => 0,
        'title' => 'Intro to PHP Email Course',
    ], $attributes));
}

function inAppNotificationCount(App\Models\User $user, string $type): int
{
    return $user->notifications()->where('data->type', $type)->count();
}

/*
|--------------------------------------------------------------------------
| Enrollment confirmed
|--------------------------------------------------------------------------
*/

test('a free enrollment sends one confirmation email with the course title and a link', function () {
    mailIsConfigured();
    $student = pathwiseUser('student', ['email' => 'learner@example.com']);
    $course = emailCourse();

    $this->actingAs($student)
        ->post(route('student.enroll', $course))
        ->assertRedirect(route('student.learn.course', $course));

    $emails = sentEmails();

    expect($emails)->toHaveCount(1);

    $email = $emails[0];

    expect($email->getTo()[0]->getAddress())->toBe('learner@example.com')
        ->and($email->getSubject())->toContain('Enrollment confirmed: Intro to PHP Email Course')
        ->and($email->getSubject())->toContain(config('app.name'))
        ->and($email->getHtmlBody())->toContain('Intro to PHP Email Course')
        ->and($email->getHtmlBody())->toContain(route('student.learn.course', $course))
        ->and($email->getHtmlBody())->toContain('Go to course')
        // Plain-text fallback.
        ->and($email->getTextBody())->toContain('Intro to PHP Email Course')
        ->and($email->getTextBody())->toContain(route('student.learn.course', $course))
        ->and(inAppNotificationCount($student, 'enrollment_activated'))->toBe(1);
});

test('joining through an invitation sends the confirmation email', function () {
    mailIsConfigured();
    $student = pathwiseUser('student');
    $course = emailCourse();

    $invitation = CourseInvitation::create([
        'course_id' => $course->id,
        'created_by' => $course->teacher_id,
        'code' => strtoupper(Str::random(12)),
    ]);

    $this->actingAs($student)->post(route('course-invitations.accept', $invitation->code));

    expect(Enrollment::where('student_id', $student->id)->exists())->toBeTrue()
        ->and(sentEmails())->toHaveCount(1)
        ->and(sentEmails()[0]->getSubject())->toContain('Enrollment confirmed');
});

/**
 * A pending PayMongo transaction and a faked "paid" checkout session.
 */
function paidCheckoutReady(App\Models\User $student, App\Models\Course $course): Transaction
{
    config([
        'services.paymongo.secret_key' => 'sk_test_fake',
        'services.paymongo.public_key' => 'pk_test_fake',
        'services.paymongo.webhook_secret' => 'whsk_test_secret',
    ]);

    Http::fake([
        'api.paymongo.com/v1/checkout_sessions/*' => Http::response([
            'data' => [
                'id' => 'cs_test_email',
                'attributes' => [
                    'reference_number' => 'TXN-EMAIL-1',
                    'payments' => [[
                        'id' => 'pay_test_email',
                        'attributes' => [
                            'status' => 'paid',
                            'currency' => 'PHP',
                            'amount' => 49900,
                            'source' => ['type' => 'paymaya'],
                        ],
                    ]],
                ],
            ],
        ]),
    ]);

    return Transaction::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'transaction_no' => 'TXN-EMAIL-1',
        'amount' => 499,
        'payment_method' => 'PayMongo',
        'payment_reference' => 'cs_test_email',
        'status' => 'pending',
    ]);
}

function sendPaidWebhook($test): void
{
    $body = json_encode([
        'data' => [
            'id' => 'evt_test_email',
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => 'cs_test_email',
                    'attributes' => ['reference_number' => 'TXN-EMAIL-1'],
                ],
            ],
        ],
    ]);

    $timestamp = now()->timestamp;
    $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'whsk_test_secret');

    $test->call('POST', route('paymongo.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_PAYMONGO_SIGNATURE' => "t={$timestamp},te={$signature},li=",
    ], $body)->assertOk();
}

test('a paid checkout sends one email even when the success page and the webhook both confirm it', function () {
    mailIsConfigured();
    $student = pathwiseUser('student');
    $course = emailCourse(['price' => 499, 'title' => 'Paid Java Course']);
    $transaction = paidCheckoutReady($student, $course);

    $this->actingAs($student)
        ->get(route('student.transactions.success', $transaction))
        ->assertOk();

    sendPaidWebhook($this);

    expect($transaction->fresh()->status)->toBe('approved')
        ->and(sentEmails())->toHaveCount(1)
        ->and(sentEmails()[0]->getSubject())->toContain('Paid Java Course')
        ->and(inAppNotificationCount($student, 'payment_approved'))->toBe(1);
});

test('the webhook alone also sends the enrollment email', function () {
    mailIsConfigured();
    $student = pathwiseUser('student');
    $course = emailCourse(['price' => 499, 'title' => 'Webhook Course']);
    paidCheckoutReady($student, $course);

    sendPaidWebhook($this);

    expect(sentEmails())->toHaveCount(1)
        ->and(sentEmails()[0]->getHtmlBody())->toContain('Webhook Course');
});

test('a payment approved manually by the Super Admin sends the enrollment email', function () {
    mailIsConfigured();
    $student = pathwiseUser('student');
    $course = emailCourse(['price' => 499, 'title' => 'Manual Payment Course']);

    $transaction = Transaction::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'transaction_no' => 'TXN-MANUAL-EMAIL',
        'amount' => 499,
        'payment_method' => 'GCash',
        'payment_proof' => 'payment-proofs/receipt.png',
        'status' => 'pending',
    ]);

    $this->actingAs(pathwiseUser('super_admin'))
        ->post(route('super_admin.transactions.approve', $transaction))
        ->assertSessionHas('success');

    expect(sentEmails())->toHaveCount(1)
        ->and(sentEmails()[0]->getHtmlBody())->toContain('Manual Payment Course');
});

test('enrolling again in the same course does not send a second email', function () {
    mailIsConfigured();
    $student = pathwiseUser('student');
    $course = emailCourse();

    $this->actingAs($student)->post(route('student.enroll', $course));
    $this->actingAs($student)->post(route('student.enroll', $course));
    // The purchase route for a free course also confirms the enrollment.
    $this->actingAs($student)->post(route('student.transactions.store', $course));

    expect(sentEmails())->toHaveCount(1);
});

/*
|--------------------------------------------------------------------------
| Certificate issued
|--------------------------------------------------------------------------
*/

test('completing a course sends one certificate email with a link to the certificate', function () {
    mailIsConfigured();
    $student = pathwiseUser('student');
    $course = emailCourse(['title' => 'Certificate Email Course']);

    $lesson = Lesson::create([
        'course_id' => $course->id,
        'title' => 'Only lesson',
        'content' => 'Content',
        'lesson_type' => 'text',
        'lesson_order' => 1,
        'is_preview' => false,
        'is_published' => true,
    ]);

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    $this->actingAs($student)->post(route('student.lesson.complete', $lesson))->assertSessionHas('success');
    // Completing again must not issue or email a second certificate.
    $this->actingAs($student)->post(route('student.lesson.complete', $lesson));

    $certificate = App\Models\Certificate::sole();
    $emails = sentEmails();

    expect($emails)->toHaveCount(1)
        ->and($emails[0]->getSubject())->toContain('Your certificate is ready: Certificate Email Course')
        ->and($emails[0]->getHtmlBody())->toContain(route('student.certificate.view', $certificate))
        ->and($emails[0]->getHtmlBody())->toContain('View certificate')
        ->and($emails[0]->getTextBody())->toContain(route('student.certificate.view', $certificate))
        ->and(inAppNotificationCount($student, 'certificate_issued'))->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Only when mail is configured
|--------------------------------------------------------------------------
*/

test('no email is sent with the log or array mailer, but the in-app notification is saved', function (string $mailer) {
    config(['mail.default' => $mailer]);
    Notification::fake();

    $student = pathwiseUser('student');
    $course = emailCourse();

    $this->actingAs($student)->post(route('student.enroll', $course));

    Notification::assertSentTo(
        $student,
        PathwiseNotification::class,
        fn ($notification, array $channels) => $channels === ['database']
    );
})->with(['log', 'array']);

test('with real mail configured the mail channel is added next to the in-app one', function () {
    config(['mail.default' => 'smtp']);
    Notification::fake();

    $student = pathwiseUser('student');

    $this->actingAs($student)->post(route('student.enroll', emailCourse()));

    Notification::assertSentTo(
        $student,
        PathwiseNotification::class,
        fn ($notification, array $channels) => $channels === ['database', SafeMailChannel::class]
    );
});

test('other notification types are never emailed', function () {
    config(['mail.default' => 'smtp']);

    $notification = new PathwiseNotification(title: 'Invite', message: 'Hi', type: 'course_invitation');

    expect($notification->via(pathwiseUser('student')))->toBe(['database']);
});

/*
|--------------------------------------------------------------------------
| A failed email never breaks the flow
|--------------------------------------------------------------------------
*/

test('a broken mail server does not stop a free enrollment', function () {
    mailIsBroken();
    $student = pathwiseUser('student');
    $course = emailCourse();

    $this->actingAs($student)
        ->post(route('student.enroll', $course))
        ->assertRedirect(route('student.learn.course', $course))
        ->assertSessionHas('success');

    expect(Enrollment::where('student_id', $student->id)->value('status'))->toBe('active')
        ->and(inAppNotificationCount($student, 'enrollment_activated'))->toBe(1);
});

test('a broken mail server does not stop a paid enrollment or the success page', function () {
    mailIsBroken();
    $student = pathwiseUser('student');
    $course = emailCourse(['price' => 499]);
    $transaction = paidCheckoutReady($student, $course);

    $this->actingAs($student)
        ->get(route('student.transactions.success', $transaction))
        ->assertOk();

    expect($transaction->fresh()->status)->toBe('approved')
        ->and(Enrollment::where('student_id', $student->id)->value('status'))->toBe('active');
});

test('a broken mail server does not stop a certificate', function () {
    mailIsBroken();
    $student = pathwiseUser('student');
    $course = emailCourse();

    $lesson = Lesson::create([
        'course_id' => $course->id,
        'title' => 'Only lesson',
        'content' => 'Content',
        'lesson_type' => 'text',
        'lesson_order' => 1,
        'is_preview' => false,
        'is_published' => true,
    ]);

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    $this->actingAs($student)
        ->post(route('student.lesson.complete', $lesson))
        ->assertSessionHas('success');

    expect(App\Models\Certificate::where('student_id', $student->id)->value('status'))->toBe('issued')
        ->and(inAppNotificationCount($student, 'certificate_issued'))->toBe(1);
});

test('emails contain no passwords or keys', function () {
    mailIsConfigured();
    config([
        'services.gemini.api_key' => 'gemini-secret-key',
        'services.paymongo.secret_key' => 'sk_test_secret_value',
    ]);

    $student = pathwiseUser('student');
    $this->actingAs($student)->post(route('student.enroll', emailCourse()));

    $html = sentEmails()[0]->getHtmlBody().sentEmails()[0]->getTextBody();

    expect($html)->not->toContain('gemini-secret-key')
        ->not->toContain('sk_test_secret_value')
        ->not->toContain('password');
});
