<?php

use App\Models\CourseInvitation;
use App\Models\Enrollment;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

/*
| Finalization: the PayMongo webhook only approves a payment when the
| signature is valid for the exact body, the timestamp is recent, the event
| is a paid checkout, and PayMongo itself confirms the session is paid for
| the full amount. Repeated events do not create duplicate enrollments.
*/

const WEBHOOK_SECRET = 'whsk_finalization_secret';

function webhookTransaction(string $secretKey = 'sk_test_fake'): Transaction
{
    config([
        'services.paymongo.secret_key' => $secretKey,
        'services.paymongo.public_key' => 'pk_test_fake',
        'services.paymongo.webhook_secret' => WEBHOOK_SECRET,
    ]);

    $student = pathwiseUser('student');
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'price' => 499]);

    return Transaction::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'transaction_no' => 'TXN-WEBHOOK-1',
        'amount' => 499,
        'payment_method' => 'PayMongo',
        'payment_reference' => 'cs_webhook_1',
        'status' => 'pending',
    ]);
}

function fakeCheckoutSession(string $status = 'paid', int $amount = 49900): void
{
    Http::fake([
        'api.paymongo.com/v1/checkout_sessions/*' => Http::response([
            'data' => [
                'id' => 'cs_webhook_1',
                'attributes' => [
                    'reference_number' => 'TXN-WEBHOOK-1',
                    'payments' => [[
                        'id' => 'pay_webhook_1',
                        'attributes' => [
                            'status' => $status,
                            'currency' => 'PHP',
                            'amount' => $amount,
                            'source' => ['id' => 'src_1', 'type' => 'gcash'],
                        ],
                    ]],
                ],
            ],
        ]),
    ]);
}

function webhookBody(string $type = 'checkout_session.payment.paid', string $reference = 'TXN-WEBHOOK-1'): string
{
    return json_encode([
        'data' => [
            'id' => 'evt_'.uniqid(),
            'attributes' => [
                'type' => $type,
                'data' => [
                    'id' => 'cs_webhook_1',
                    'attributes' => ['reference_number' => $reference],
                ],
            ],
        ],
    ]);
}

function sendWebhook($test, string $body, ?string $header)
{
    $server = ['CONTENT_TYPE' => 'application/json'];

    if ($header !== null) {
        $server['HTTP_PAYMONGO_SIGNATURE'] = $header;
    }

    return $test->call('POST', route('paymongo.webhook'), [], [], [], $server, $body);
}

function signedHeader(string $body, ?int $timestamp = null, string $mode = 'te', string $secret = WEBHOOK_SECRET): string
{
    $timestamp ??= now()->timestamp;
    $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);

    return $mode === 'li'
        ? "t={$timestamp},te=,li={$signature}"
        : "t={$timestamp},te={$signature},li=";
}

test('a correctly signed paid event approves the payment and enrolls the student once', function () {
    Notification::fake();
    $transaction = webhookTransaction();
    fakeCheckoutSession();

    $body = webhookBody();
    sendWebhook($this, $body, signedHeader($body))->assertOk();

    // PayMongo retries events: a second delivery must not duplicate anything.
    $again = webhookBody();
    sendWebhook($this, $again, signedHeader($again))->assertOk();

    expect($transaction->fresh()->status)->toBe('approved')
        ->and(Enrollment::where('student_id', $transaction->student_id)
            ->where('course_id', $transaction->course_id)->count())->toBe(1)
        ->and(Enrollment::where('student_id', $transaction->student_id)->value('status'))->toBe('active');
});

test('a signature made for a different body is rejected', function () {
    $transaction = webhookTransaction();
    fakeCheckoutSession();

    $header = signedHeader(webhookBody(reference: 'TXN-SOMETHING-ELSE'));

    sendWebhook($this, webhookBody(), $header)->assertStatus(401);

    expect($transaction->fresh()->status)->toBe('pending');
    Http::assertNothingSent();
});

test('a signature made with the wrong secret is rejected', function () {
    $transaction = webhookTransaction();
    fakeCheckoutSession();

    $body = webhookBody();

    sendWebhook($this, $body, signedHeader($body, secret: 'whsk_attacker'))->assertStatus(401);

    expect($transaction->fresh()->status)->toBe('pending');
});

test('an old timestamp outside the tolerance is rejected', function () {
    $transaction = webhookTransaction();
    fakeCheckoutSession();

    $body = webhookBody();

    sendWebhook($this, $body, signedHeader($body, now()->timestamp - 3600))->assertStatus(401);

    expect($transaction->fresh()->status)->toBe('pending');
});

test('a test-mode signature is not accepted when live keys are configured', function () {
    $transaction = webhookTransaction('sk_live_fake');
    fakeCheckoutSession();

    $body = webhookBody();

    sendWebhook($this, $body, signedHeader($body, mode: 'te'))->assertStatus(401);
    expect($transaction->fresh()->status)->toBe('pending');

    Notification::fake();
    sendWebhook($this, $body, signedHeader($body, mode: 'li'))->assertOk();
    expect($transaction->fresh()->status)->toBe('approved');
});

test('other event types are ignored', function () {
    $transaction = webhookTransaction();
    fakeCheckoutSession();

    $body = webhookBody('payment.failed');

    sendWebhook($this, $body, signedHeader($body))->assertSuccessful();

    expect($transaction->fresh()->status)->toBe('pending')
        ->and(Enrollment::count())->toBe(0);
});

test('a signed event is not trusted when PayMongo says the session is unpaid', function () {
    $transaction = webhookTransaction();
    fakeCheckoutSession('pending');

    $body = webhookBody();

    sendWebhook($this, $body, signedHeader($body))->assertStatus(202);

    expect($transaction->fresh()->status)->toBe('pending')
        ->and(Enrollment::count())->toBe(0);
});

test('a payment for less than the course price is not approved', function () {
    $transaction = webhookTransaction();
    fakeCheckoutSession('paid', 100);

    $body = webhookBody();

    sendWebhook($this, $body, signedHeader($body));

    expect($transaction->fresh()->status)->not->toBe('approved')
        ->and(Enrollment::count())->toBe(0);
});

test('a late verified payment for an invitation enrollment is recorded for review only', function () {
    Notification::fake();
    $transaction = webhookTransaction();
    $enrollment = Enrollment::create([
        'student_id' => $transaction->student_id,
        'course_id' => $transaction->course_id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 40,
    ]);
    $invitation = CourseInvitation::create([
        'course_id' => $transaction->course_id,
        'created_by' => $transaction->course->teacher_id,
        'code' => 'WEBHOOKINV01',
    ]);
    $invitation->redeemedBy()->attach($transaction->student_id, ['enrollment_id' => $enrollment->id]);
    fakeCheckoutSession();

    foreach (range(1, 2) as $attempt) {
        $body = webhookBody();
        sendWebhook($this, $body, signedHeader($body))->assertOk()
            ->assertJsonPath('message', 'Verified payment requires enrollment review.');
    }

    $transaction->refresh();
    expect($transaction->status)->toBe('pending')
        ->and($transaction->payment_review_required_at)->not->toBeNull()
        ->and($transaction->remarks)->toContain('pay_webhook_1')
        ->and(Enrollment::count())->toBe(1)
        ->and((int) $enrollment->fresh()->progress_percentage)->toBe(40)
        ->and($invitation->redeemedBy()->count())->toBe(1);
    Notification::assertNothingSent();
    Http::assertSentCount(1);

    $this->actingAs($transaction->student)->get(route('student.transactions.success', $transaction))
        ->assertOk()->assertSee('Payment Requires Review')->assertDontSee('Start Learning');
});

test('a verified legacy payment with an unavailable or foreign private invitation requires review', function (string $reason) {
    Notification::fake();
    $transaction = webhookTransaction();
    $invitation = CourseInvitation::create([
        'course_id' => $transaction->course_id,
        'created_by' => $transaction->course->teacher_id,
        'code' => 'WEBHOOKINV02',
    ]);
    $invitation->update(match ($reason) {
        'expired' => ['expires_at' => now()],
        'deactivated' => ['deactivated_at' => now()],
        'foreign recipient' => ['is_private' => true, 'student_id' => pathwiseUser('student')->id],
    });
    $transaction->update(['course_invitation_id' => $invitation->id]);
    fakeCheckoutSession();

    $body = webhookBody();
    sendWebhook($this, $body, signedHeader($body))->assertOk()
        ->assertJsonPath('message', 'Verified payment requires enrollment review.');
    expect($transaction->fresh()->payment_review_required_at)->not->toBeNull()
        ->and($transaction->fresh()->approved_at)->toBeNull()
        ->and(Enrollment::count())->toBe(0);
    Notification::assertNothingSent();
})->with(['expired', 'deactivated', 'foreign recipient']);

test('replaying an approved payment or opening its success page cannot restore a cancelled enrollment', function () {
    Notification::fake();
    Http::fake();
    $transaction = webhookTransaction();
    $transaction->update(['status' => 'approved']);
    $enrollment = Enrollment::create([
        'student_id' => $transaction->student_id,
        'course_id' => $transaction->course_id,
        'status' => 'cancelled',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    $body = webhookBody();
    sendWebhook($this, $body, signedHeader($body))->assertOk();
    $this->actingAs($transaction->student)->get(route('student.transactions.success', $transaction))
        ->assertOk()->assertDontSee('Start Learning');

    expect(Enrollment::count())->toBe(1)
        ->and($enrollment->fresh()->status)->toBe('cancelled');
    Http::assertNothingSent();
    Notification::assertNothingSent();
});

test('a second verified payment for the same student and course is recorded for review', function () {
    Notification::fake();
    $transaction = webhookTransaction();
    $original = $transaction->replicate();
    $original->fill([
        'transaction_no' => 'TXN-ALREADY-APPROVED',
        'payment_reference' => 'cs_original',
        'status' => 'approved',
        'approved_at' => now(),
    ])->save();
    fakeCheckoutSession();

    $body = webhookBody();
    sendWebhook($this, $body, signedHeader($body))->assertOk()
        ->assertJsonPath('message', 'Verified payment requires enrollment review.');
    expect($transaction->fresh()->payment_review_required_at)->not->toBeNull()
        ->and(Transaction::where('status', 'approved')->count())->toBe(1)
        ->and(Enrollment::count())->toBe(0);
    Notification::assertNothingSent();
});
