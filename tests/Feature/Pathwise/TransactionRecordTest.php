<?php

use App\Models\Enrollment;
use App\Models\Transaction;
use Illuminate\Support\Facades\Notification;

/*
| Manuscript: Functional Requirement (m) "Payment Processing". The
| administrator's decision on a transaction (remarks, who decided, and
| when) must be saved on the transaction record.
*/

function manualTransactionSetup(array $attributes = []): array
{
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $superAdmin = pathwiseUser('super_admin');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 499]);

    $transaction = Transaction::create(array_merge([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'transaction_no' => 'TXN-TEST-'.uniqid(),
        'amount' => 499,
        'payment_method' => 'GCash',
        'status' => 'pending',
        'payment_proof' => 'payment-proofs/proof.png',
    ], $attributes));

    return [$student, $superAdmin, $transaction];
}

test('rejecting a transaction saves the remarks, the approver and the time', function () {
    Notification::fake();

    [, $superAdmin, $transaction] = manualTransactionSetup();

    $this->actingAs($superAdmin)
        ->post(route('super_admin.transactions.reject', $transaction), [
            'remarks' => 'The reference number does not match the receipt.',
        ])
        ->assertSessionHas('success');

    $transaction->refresh();

    expect($transaction->status)->toBe('rejected')
        ->and($transaction->remarks)->toBe('The reference number does not match the receipt.')
        ->and((int) $transaction->approved_by)->toBe($superAdmin->id)
        ->and($transaction->approved_at)->not->toBeNull();
});

test('approving a manual transaction saves the approver and the time', function () {
    Notification::fake();

    [$student, $superAdmin, $transaction] = manualTransactionSetup();

    $this->actingAs($superAdmin)
        ->post(route('super_admin.transactions.approve', $transaction))
        ->assertSessionHas('success');

    $transaction->refresh();

    expect($transaction->status)->toBe('approved')
        ->and((int) $transaction->approved_by)->toBe($superAdmin->id)
        ->and($transaction->approved_at)->not->toBeNull()
        ->and($transaction->approver->is($superAdmin))->toBeTrue()
        ->and(Enrollment::where('student_id', $student->id)->value('status'))->toBe('active');
});

test('a student cancelling a PayMongo checkout saves the cancellation remark', function () {
    [$student, , $transaction] = manualTransactionSetup([
        'payment_method' => 'PayMongo',
        'payment_reference' => 'cs_test_cancel',
        'payment_proof' => null,
    ]);

    $this->actingAs($student)
        ->get(route('student.transactions.cancel', $transaction))
        ->assertOk();

    $transaction->refresh();

    expect($transaction->status)->toBe('rejected')
        ->and($transaction->remarks)->toBe('PayMongo checkout was cancelled by the student.');
});
