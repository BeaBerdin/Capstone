<?php

use App\Models\CourseInvitation;
use App\Models\Enrollment;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;

/*
| Manuscript: Figure 23 "Enroll in Course" and Functional Requirement (m)
| "Payment Processing". Paid courses must never be enrolled for free.
*/

test('a student cannot enroll in a paid course through the free enroll route', function () {
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 499]);

    $this->actingAs($student)
        ->post(route('student.enroll', $course))
        ->assertRedirect(route('student.course.show', $course))
        ->assertSessionHas('error');

    expect(Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->exists())
        ->toBeFalse();
});

test('a student can enroll in a free published course', function () {
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 0]);

    $this->actingAs($student)
        ->post(route('student.enroll', $course))
        ->assertRedirect(route('student.learn.course', $course));

    expect(Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->exists())
        ->toBeTrue();
});

test('a student cannot enroll in a course that is not published', function () {
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'pending', 'price' => 0]);

    $this->actingAs($student)
        ->post(route('student.enroll', $course))
        ->assertNotFound();
});

test('a teacher cannot use the student enroll route', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 0]);

    $this->actingAs($teacher)
        ->post(route('student.enroll', $course))
        ->assertForbidden();
});

test('the PayMongo webhook is rejected when no signing secret is configured', function () {
    config(['services.paymongo.webhook_secret' => null]);

    $this->postJson(route('paymongo.webhook'), ['data' => []])
        ->assertStatus(503);
});

test('the PayMongo webhook is rejected without a signature', function () {
    config(['services.paymongo.webhook_secret' => 'whsk_test_secret']);

    $this->postJson(route('paymongo.webhook'), ['data' => []])
        ->assertStatus(401);
});

test('the PayMongo webhook is excluded from CSRF protection', function () {
    // PayMongo cannot send a CSRF token, so the URL must be in the
    // except list configured in bootstrap/app.php.
    $middleware = app(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

    expect($middleware->getExcludedPaths())->toContain('paymongo/webhook');
});

function paidCheckoutSetup(): array
{
    config([
        'services.paymongo.secret_key' => 'sk_test_fake',
        'services.paymongo.public_key' => 'pk_test_fake',
    ]);

    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 499]);

    return [$student, $course];
}

test('a failed PayMongo checkout shows an error and is stored as rejected with a remark', function () {
    [$student, $course] = paidCheckoutSetup();

    Http::fake([
        'api.paymongo.com/*' => Http::response(
            ['errors' => [['detail' => 'The secret key is invalid.']]],
            401
        ),
    ]);

    $this->actingAs($student)
        ->from(route('student.marketplace'))
        ->post(route('student.transactions.store', $course))
        ->assertRedirect(route('student.marketplace'))
        ->assertSessionHas('error', 'Unable to create the payment session. Please try again.');

    $transaction = Transaction::where('student_id', $student->id)->sole();

    expect($transaction->status)->toBe('rejected')
        ->and($transaction->remarks)->toContain('The secret key is invalid.')
        ->and(Enrollment::where('student_id', $student->id)->exists())->toBeFalse();
});

test('a PayMongo response without a checkout URL is stored as rejected with a remark', function () {
    [$student, $course] = paidCheckoutSetup();

    Http::fake([
        'api.paymongo.com/*' => Http::response(['data' => []], 200),
    ]);

    $this->actingAs($student)
        ->from(route('student.marketplace'))
        ->post(route('student.transactions.store', $course))
        ->assertRedirect(route('student.marketplace'))
        ->assertSessionHas('error', 'PayMongo did not return a valid checkout session.');

    $transaction = Transaction::where('student_id', $student->id)->sole();

    expect($transaction->status)->toBe('rejected')
        ->and($transaction->remarks)->toContain('no checkout URL');
});

test('an unreachable PayMongo shows a friendly error and is stored as rejected with a remark', function () {
    [$student, $course] = paidCheckoutSetup();

    Http::fake([
        'api.paymongo.com/*' => Http::failedConnection('Connection timed out'),
    ]);

    $this->actingAs($student)
        ->from(route('student.marketplace'))
        ->post(route('student.transactions.store', $course))
        ->assertRedirect(route('student.marketplace'))
        ->assertSessionHas('error', 'The payment service is not responding right now. Please try again in a few minutes.');

    $transaction = Transaction::where('student_id', $student->id)->sole();

    expect($transaction->status)->toBe('rejected')
        ->and($transaction->remarks)->toContain('PayMongo could not be reached')
        ->and(Enrollment::where('student_id', $student->id)->exists())->toBeFalse();
});

test('an in-flight checkout reserves the course against invitation redemption and additional checkout attempts', function () {
    [$student, $course] = paidCheckoutSetup();
    $invitation = CourseInvitation::create([
        'course_id' => $course->id,
        'created_by' => $course->teacher_id,
        'code' => 'CHECKOUT1234',
    ]);
    $this->actingAs($student);

    Http::fake(['api.paymongo.com/v1/checkout_sessions' => function () use ($course, $invitation) {
        $transaction = Transaction::sole();
        expect($transaction->status)->toBe('pending')
            ->and($transaction->payment_reference)->toBeNull();

        $this->get(route('student.transactions.cancel', $transaction))->assertOk();
        expect($transaction->fresh()->status)->toBe('pending');
        $this->post(route('course-invitations.accept', $invitation->code))->assertSessionHasErrors('invitation');
        $this->post(route('student.transactions.store', $course))
            ->assertRedirect(route('student.transactions.show', $transaction));

        return Http::response([
            'data' => [
                'id' => 'cs_test_invite_overlap',
                'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_test_invite_overlap'],
            ],
        ]);
    }]);

    $this->post(route('student.transactions.store', $course))
        ->assertRedirect('https://checkout.paymongo.com/cs_test_invite_overlap');

    expect(Transaction::count())->toBe(1)
        ->and(Enrollment::count())->toBe(0)
        ->and($invitation->redeemedBy()->count())->toBe(0);
    Http::assertSentCount(1);
});

test('a saved legacy checkout blocks invitation redemption even after local cancellation', function () {
    [$student, $course] = paidCheckoutSetup();
    Http::fake();
    $invitation = CourseInvitation::create([
        'course_id' => $course->id,
        'created_by' => $course->teacher_id,
        'code' => 'LEGACYPAY001',
    ]);
    $transaction = Transaction::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'course_invitation_id' => $invitation->id,
        'transaction_no' => 'INV-LEGACY',
        'amount' => $course->price,
        'payment_method' => 'PayMongo',
        'payment_reference' => 'cs_test_legacy_hold',
        'checkout_url' => 'https://checkout.paymongo.com/cs_test_legacy_hold',
        'status' => 'pending',
    ]);

    $this->actingAs($student)->get(route('course-invitations.show', $invitation->code))
        ->assertOk()->assertSee(Transaction::ENROLLMENT_PAYMENT_CONFLICT)->assertDontSee('data-accept-button');
    $this->get(route('student.marketplace'))->assertOk()->assertDontSee($transaction->checkout_url, false);
    $this->get(route('student.transactions.cancel', $transaction))->assertOk();
    expect($transaction->fresh()->status)->toBe('rejected');

    $this->post(route('course-invitations.accept', $invitation->code))->assertSessionHasErrors('invitation');
    $this->post(route('student.transactions.store', $course))
        ->assertRedirect(route('student.transactions.show', $transaction));
    expect(Transaction::count())->toBe(1)
        ->and(Enrollment::count())->toBe(0);
    Http::assertNothingSent();
});

test('a paid course invitation and submissions from old invitation forms never start checkout', function () {
    [$student, $course] = paidCheckoutSetup();
    Http::fake();
    $invitation = CourseInvitation::create([
        'course_id' => $course->id,
        'created_by' => $course->teacher_id,
        'code' => 'FREEINVITE01',
    ]);

    $this->actingAs($student)->post(route('student.transactions.store', $course), [
        'course_invitation_code' => $invitation->code,
    ])->assertRedirect(route('course-invitations.show', $invitation->code));
    expect(Enrollment::count())->toBe(0);
    $this->post(route('course-invitations.accept', $invitation->code))
        ->assertRedirect(route('student.learn.course', $course));
    $this->post(route('student.transactions.store', $course))->assertRedirect(route('student.my-courses'));

    expect(Transaction::count())->toBe(0)
        ->and(Enrollment::sole()->status)->toBe('active');
    Http::assertNothingSent();
});
