<?php

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/*
| System finalization L-1 (approved November 6, 2026): transaction numbers
| (TRX-YYYYMMDD-00001) stay unique when two purchases happen at the same
| moment. The old code used "today's count + 1", so a number taken by a
| concurrent purchase made the second purchase fail on the unique index.
*/

function transactionNumberCheckout(): array
{
    config([
        'services.paymongo.secret_key' => 'sk_test_fake',
        'services.paymongo.public_key' => 'pk_test_fake',
    ]);

    Http::fake([
        'api.paymongo.com/*' => Http::response([
            'data' => [
                'id' => 'cs_test_number',
                'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_test_number'],
            ],
        ]),
    ]);

    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 499]);

    return [$course, pathwiseUser('student')];
}

function existingTransaction(int $courseId, string $number): Transaction
{
    return Transaction::create([
        'student_id' => pathwiseUser('student')->id,
        'course_id' => $courseId,
        'transaction_no' => $number,
        'amount' => 499,
        'payment_method' => 'PayMongo',
        'status' => 'approved',
    ]);
}

test('the first purchase of the day keeps the TRX-YYYYMMDD-00001 format', function () {
    [$course, $student] = transactionNumberCheckout();

    $this->actingAs($student)
        ->post(route('student.transactions.store', $course))
        ->assertRedirect('https://checkout.paymongo.com/cs_test_number');

    expect(Transaction::where('student_id', $student->id)->value('transaction_no'))
        ->toBe('TRX-'.now()->format('Ymd').'-00001');
});

test('a number taken by a simultaneous purchase is not reused and the purchase still succeeds', function () {
    [$course, $student] = transactionNumberCheckout();
    $prefix = 'TRX-'.now()->format('Ymd').'-';
    existingTransaction($course->id, $prefix.'00001');

    // Reproduce the race: just before PathWise inserts its new transaction,
    // another purchase stores the same number (as a concurrent request would).
    $rival = pathwiseUser('student');
    $raced = false;

    Transaction::creating(function (Transaction $transaction) use (&$raced, $course, $rival) {
        if ($raced || $transaction->student_id === $rival->id) {
            return;
        }

        $raced = true;

        DB::table('transactions')->insert([
            'student_id' => $rival->id,
            'course_id' => $course->id,
            'transaction_no' => $transaction->transaction_no,
            'amount' => 499,
            'payment_method' => 'PayMongo',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    $this->actingAs($student)
        ->post(route('student.transactions.store', $course))
        ->assertRedirect('https://checkout.paymongo.com/cs_test_number');

    expect($raced)->toBeTrue()
        ->and(Transaction::where('transaction_no', $prefix.'00002')->value('student_id'))->toBe($rival->id)
        ->and(Transaction::where('student_id', $student->id)->value('transaction_no'))->toBe($prefix.'00003')
        ->and(Transaction::where('transaction_no', 'like', $prefix.'%')->count())->toBe(3)
        ->and(Transaction::distinct()->count('transaction_no'))->toBe(Transaction::count());
});

test('numbering continues after the highest number even when the count is lower', function () {
    [$course, $student] = transactionNumberCheckout();
    $prefix = 'TRX-'.now()->format('Ymd').'-';

    // Two existing rows numbered 00001 and 00003: "count + 1" would give
    // 00003 again and fail. The next number must be 00004.
    $first = existingTransaction($course->id, $prefix.'00001');
    $third = existingTransaction($course->id, $prefix.'00003');

    $this->actingAs($student)
        ->post(route('student.transactions.store', $course))
        ->assertRedirect('https://checkout.paymongo.com/cs_test_number');

    expect(Transaction::where('student_id', $student->id)->value('transaction_no'))->toBe($prefix.'00004')
        ->and($first->fresh()->transaction_no)->toBe($prefix.'00001')
        ->and($third->fresh()->transaction_no)->toBe($prefix.'00003');
});

test('numbers from earlier days do not affect today\'s sequence', function () {
    [$course, $student] = transactionNumberCheckout();
    existingTransaction($course->id, 'TRX-'.now()->subDay()->format('Ymd').'-00007');

    $this->actingAs($student)->post(route('student.transactions.store', $course));

    expect(Transaction::where('student_id', $student->id)->value('transaction_no'))
        ->toBe('TRX-'.now()->format('Ymd').'-00001');
});
