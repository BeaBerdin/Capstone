<?php

use App\Models\Enrollment;
use App\Models\Transaction;
use App\Support\PaymentMethods;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

/*
| After a PayMongo checkout, the method the student actually used (GCash,
| Maya or Card) is saved and shown as e.g. "Maya via PayMongo". Older
| transactions keep showing "PayMongo".
*/

function pendingPayMongoTransaction(): array
{
    config([
        'services.paymongo.secret_key' => 'sk_test_fake',
        'services.paymongo.public_key' => 'pk_test_fake',
        'services.paymongo.webhook_secret' => 'whsk_test_secret',
    ]);

    $student = pathwiseUser('student');
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'price' => 499]);

    $transaction = Transaction::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'transaction_no' => 'TXN-METHOD-1',
        'amount' => 499,
        'payment_method' => 'PayMongo',
        'payment_reference' => 'cs_test_method',
        'status' => 'pending',
    ]);

    return [$student, $transaction];
}

/**
 * Fake PayMongo's "retrieve checkout session" answer with one paid payment.
 */
function fakePaidCheckoutSession(?string $sourceType, ?string $methodUsed = null): void
{
    $payment = [
        'id' => 'pay_test_1',
        'attributes' => [
            'status' => 'paid',
            'currency' => 'PHP',
            'amount' => 49900,
        ],
    ];

    if ($sourceType !== null) {
        $payment['attributes']['source'] = ['id' => 'src_test_1', 'type' => $sourceType];
    }

    $attributes = [
        'reference_number' => 'TXN-METHOD-1',
        'payments' => [$payment],
    ];

    if ($methodUsed !== null) {
        $attributes['payment_method_used'] = $methodUsed;
    }

    Http::fake([
        'api.paymongo.com/v1/checkout_sessions/*' => Http::response([
            'data' => ['id' => 'cs_test_method', 'attributes' => $attributes],
        ]),
    ]);
}

test('returning from a checkout saves the method used and shows it on the success page', function (string $type, string $shown) {
    Notification::fake();
    [$student, $transaction] = pendingPayMongoTransaction();
    fakePaidCheckoutSession($type);

    $this->actingAs($student)
        ->get(route('student.transactions.success', $transaction))
        ->assertOk()
        ->assertSeeText($shown);

    $transaction->refresh();

    expect($transaction->status)->toBe('approved')
        ->and($transaction->paymongo_method)->toBe($type)
        // The flow still relies on this value.
        ->and($transaction->payment_method)->toBe('PayMongo')
        ->and(Enrollment::where('student_id', $student->id)->value('status'))->toBe('active');

    $this->actingAs($student)
        ->get(route('student.transactions'))
        ->assertOk()
        ->assertSeeText($shown);
})->with([
    'Maya' => ['paymaya', 'Maya via PayMongo'],
    'GCash' => ['gcash', 'GCash via PayMongo'],
    'Card' => ['card', 'Card via PayMongo'],
]);

test('the signed webhook also saves the method used', function () {
    Notification::fake();
    [, $transaction] = pendingPayMongoTransaction();
    fakePaidCheckoutSession('paymaya');

    $body = json_encode([
        'data' => [
            'id' => 'evt_test_1',
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => 'cs_test_method',
                    'attributes' => ['reference_number' => 'TXN-METHOD-1'],
                ],
            ],
        ],
    ]);

    $timestamp = now()->timestamp;
    $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'whsk_test_secret');

    $this->call(
        'POST',
        route('paymongo.webhook'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PAYMONGO_SIGNATURE' => "t={$timestamp},te={$signature},li=",
        ],
        $body
    )->assertOk();

    expect($transaction->fresh()->status)->toBe('approved')
        ->and($transaction->fresh()->paymongo_method)->toBe('paymaya');
});

test('the checkout session method is used when the payment has no source type', function () {
    Notification::fake();
    [$student, $transaction] = pendingPayMongoTransaction();
    fakePaidCheckoutSession(null, 'gcash');

    $this->actingAs($student)->get(route('student.transactions.success', $transaction));

    expect($transaction->fresh()->paymongo_method)->toBe('gcash');
});

test('an unknown or missing method is not saved and the payment still goes through', function (?string $type) {
    Notification::fake();
    [$student, $transaction] = pendingPayMongoTransaction();
    fakePaidCheckoutSession($type);

    $this->actingAs($student)
        ->get(route('student.transactions.success', $transaction))
        ->assertOk()
        ->assertSeeText('PayMongo')
        ->assertDontSeeText('via PayMongo');

    expect($transaction->fresh()->status)->toBe('approved')
        ->and($transaction->fresh()->paymongo_method)->toBeNull();
})->with([
    'unknown type' => ['qrph'],
    'no type' => [null],
]);

test('older PayMongo transactions keep showing PayMongo', function () {
    [$student] = pendingPayMongoTransaction();

    $old = Transaction::create([
        'student_id' => $student->id,
        'course_id' => Transaction::sole()->course_id,
        'transaction_no' => 'TXN-OLD-PAYMONGO',
        'amount' => 499,
        'payment_method' => 'PayMongo',
        'status' => 'approved',
    ]);

    expect(PaymentMethods::forTransaction($old))->toBe('PayMongo')
        ->and(PaymentMethods::forTransaction($old->forceFill(['paymongo_method' => 'PAYMAYA '])))->toBe('Maya via PayMongo')
        ->and(PaymentMethods::forTransaction((new Transaction)->forceFill(['payment_method' => 'GCash'])))->toBe('GCash');
});
