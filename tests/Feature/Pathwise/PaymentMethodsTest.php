<?php

use App\Models\Transaction;
use App\Support\PaymentMethods;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

/*
| B3: PayMongo payment methods come from PAYMONGO_PAYMENT_METHODS
| (gcash, paymaya, card). One list drives the checkout request, the labels
| students see and the server validation.
*/

function paymentMethodsCheckout(string $methods): array
{
    config([
        'services.paymongo.secret_key' => 'sk_test_fake',
        'services.paymongo.public_key' => 'pk_test_fake',
        'services.paymongo.payment_methods' => $methods,
    ]);

    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 499]);

    return [$student, $course];
}

function fakePayMongoCheckoutSuccess(): void
{
    Http::fake([
        'api.paymongo.com/*' => Http::response([
            'data' => [
                'id' => 'cs_test_123',
                'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_test_123'],
            ],
        ]),
    ]);
}

function sentPaymentMethodTypes(): array
{
    $types = null;

    Http::assertSent(function (Request $request) use (&$types) {
        $types = data_get($request->data(), 'data.attributes.payment_method_types');

        return str_contains($request->url(), 'checkout_sessions');
    });

    return $types;
}

test('Maya is sent to PayMongo when it is configured', function () {
    [$student, $course] = paymentMethodsCheckout('gcash,card,paymaya');
    fakePayMongoCheckoutSuccess();

    $this->actingAs($student)
        ->post(route('student.transactions.store', $course))
        ->assertRedirect('https://checkout.paymongo.com/cs_test_123');

    expect(sentPaymentMethodTypes())->toBe(['gcash', 'card', 'paymaya']);
});

test('Maya is left out when it is removed from the configuration', function () {
    [$student, $course] = paymentMethodsCheckout('gcash,card');
    fakePayMongoCheckoutSuccess();

    $this->actingAs($student)->post(route('student.transactions.store', $course));

    expect(sentPaymentMethodTypes())->toBe(['gcash', 'card']);
});

test('unknown, duplicate and badly spaced values are cleaned up', function () {
    [$student, $course] = paymentMethodsCheckout(' GCash , bitcoin, PayMaya,card , gcash ');
    fakePayMongoCheckoutSuccess();

    $this->actingAs($student)->post(route('student.transactions.store', $course));

    expect(sentPaymentMethodTypes())->toBe(['gcash', 'paymaya', 'card']);
});

test('a configuration with no supported value falls back to the default list', function () {
    config(['services.paymongo.payment_methods' => 'bitcoin,cash']);

    expect(PaymentMethods::enabled())->toBe(['gcash', 'card', 'paymaya']);
});

test('a PayMongo refusal of a method is rejected with a remark and a friendly message', function () {
    [$student, $course] = paymentMethodsCheckout('gcash,card,paymaya');

    Http::fake([
        'api.paymongo.com/*' => Http::response([
            'errors' => [[
                'code' => 'parameter_invalid',
                'detail' => 'paymaya is not enabled for your account.',
            ]],
        ], 400),
    ]);

    $this->actingAs($student)
        ->from(route('student.course.show', $course))
        ->post(route('student.transactions.store', $course))
        ->assertRedirect(route('student.course.show', $course))
        ->assertSessionHas('error', 'Unable to create the payment session. Please try again.');

    $transaction = Transaction::sole();

    expect($transaction->status)->toBe('rejected')
        ->and($transaction->remarks)->toContain('paymaya is not enabled');
});

test('the student purchase page lists the enabled methods with their labels', function (string $methods, string $text) {
    [$student, $course] = paymentMethodsCheckout($methods);

    $this->actingAs($student)
        ->get(route('student.course.show', $course))
        ->assertOk()
        ->assertSeeText('Pay securely with '.$text.' via PayMongo.');
})->with([
    'with Maya' => ['gcash,card,paymaya', 'GCash, Card, Maya'],
    'without Maya' => ['gcash,card', 'GCash, Card'],
]);

test('the manual payment-proof form and validation use the same list', function () {
    fakeFileDisks();
    config(['services.paymongo.payment_methods' => 'gcash,card']);

    $student = pathwiseUser('student');
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'price' => 499]);

    $transaction = Transaction::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'transaction_no' => 'TXN-MANUAL-1',
        'amount' => 499,
        'payment_method' => 'Manual',
        'status' => 'pending',
    ]);

    $proof = fn () => UploadedFile::fake()->image('receipt.png');

    // Maya is not enabled, so it is not accepted.
    $this->actingAs($student)
        ->post(route('student.transactions.upload-proof', $transaction), [
            'payment_method' => 'Maya',
            'payment_proof' => $proof(),
        ])
        ->assertSessionHasErrors('payment_method');

    $this->actingAs($student)
        ->post(route('student.transactions.upload-proof', $transaction), [
            'payment_method' => 'Bitcoin',
            'payment_proof' => $proof(),
        ])
        ->assertSessionHasErrors('payment_method');

    foreach (['GCash', 'Card', 'Bank Transfer'] as $method) {
        $this->actingAs($student)
            ->post(route('student.transactions.upload-proof', $transaction), [
                'payment_method' => $method,
                'payment_proof' => $proof(),
            ])
            ->assertSessionHasNoErrors();

        expect($transaction->fresh()->payment_method)->toBe($method);
    }

    expect(PaymentMethods::proofOptions())->toBe(['GCash', 'Card', 'Bank Transfer']);
});

test('existing transactions keep displaying their payment method', function () {
    $student = pathwiseUser('student');
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'price' => 499]);

    foreach (['PayMongo', 'GCash', 'Maya', 'Bank Transfer', 'paymaya'] as $index => $method) {
        Transaction::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'transaction_no' => 'TXN-OLD-'.$index,
            'amount' => 499,
            'payment_method' => $method,
            'status' => 'approved',
        ]);
    }

    $response = $this->actingAs($student)
        ->get(route('student.transactions'))
        ->assertOk()
        ->assertSeeText('PayMongo')
        ->assertSeeText('GCash')
        ->assertSeeText('Bank Transfer');

    // The stored key "paymaya" is shown as "Maya".
    expect(PaymentMethods::label('paymaya'))->toBe('Maya')
        ->and(PaymentMethods::label('PayMongo'))->toBe('PayMongo')
        ->and(PaymentMethods::label('GCash'))->toBe('GCash')
        ->and(PaymentMethods::label(null))->toBeNull();

    $response->assertDontSeeText('paymaya');
});
