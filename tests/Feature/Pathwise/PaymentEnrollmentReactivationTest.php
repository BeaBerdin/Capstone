<?php

use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseInvitation;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\StudentRemovalRequest;
use App\Models\Submission;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\PathwiseNotification;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Client\Request as PaymentRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    fakeFileDisks();
    Notification::fake();
    config([
        'services.paymongo.secret_key' => 'sk_test_h1_fake',
        'services.paymongo.public_key' => 'pk_test_h1_fake',
        'services.paymongo.webhook_secret' => 'h1_fake_webhook_secret',
        'services.paymongo.webhook_tolerance' => 300,
    ]);
});

function h1Transaction(User $student, Course $course, string $status = 'pending'): Transaction
{
    return Transaction::create([
        'student_id' => $student->id, 'course_id' => $course->id,
        'transaction_no' => 'H1-'.Str::uuid(), 'amount' => 499,
        'payment_method' => 'PayMongo', 'payment_reference' => 'cs_h1_'.Str::random(12),
        'status' => $status, 'approved_at' => $status === 'approved' ? now()->subDays(2) : null,
        'remarks' => 'Original transaction history',
    ]);
}

function h1PaymentSetup(?string $enrollmentStatus = 'cancelled', string $transactionStatus = 'pending'): array
{
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 499]);
    Lesson::create([
        'course_id' => $course->id, 'title' => 'Protected lesson', 'lesson_type' => 'text',
        'content' => 'Student learning material', 'lesson_order' => 1, 'is_published' => true,
    ]);
    $enrollment = $enrollmentStatus === null ? null : Enrollment::create([
        'student_id' => $student->id, 'course_id' => $course->id, 'status' => $enrollmentStatus,
        'enrolled_at' => now()->subMonth(), 'completed_at' => $enrollmentStatus === 'active' ? null : now()->subWeek(),
        'progress_percentage' => $enrollmentStatus === 'completed' ? 100 : 60,
    ]);
    $invitation = CourseInvitation::create([
        'course_id' => $course->id, 'created_by' => $teacher->id, 'student_id' => $student->id,
        'code' => 'H1-'.Str::random(12), 'expires_at' => now()->addDay(),
    ]);
    $transaction = h1Transaction($student, $course, $transactionStatus);
    $transaction->update(['course_invitation_id' => $invitation->id]);

    return compact('teacher', 'student', 'course', 'enrollment', 'invitation', 'transaction');
}

function h1FakeCheckout(Transaction $transaction, array $overrides = [], ?Closure $duringRequest = null): void
{
    $payload = [
        'data' => [
            'id' => $transaction->payment_reference,
            'attributes' => [
                'reference_number' => $transaction->transaction_no,
                'payments' => [[
                    'id' => 'pay_h1_fake',
                    'attributes' => ['status' => 'paid', 'currency' => 'PHP', 'amount' => 49900, 'source' => ['type' => 'gcash']],
                ]],
            ],
        ],
    ];

    foreach ($overrides as $path => $value) {
        data_set($payload, $path, $value);
    }

    Http::fake(function (PaymentRequest $request) use ($transaction, $payload, $duringRequest) {
        if ($request->url() !== 'https://api.paymongo.com/v1/checkout_sessions/'.$transaction->payment_reference) {
            return null;
        }

        expect($request->method())->toBe('GET')
            ->and($request->url())->toBe('https://api.paymongo.com/v1/checkout_sessions/'.$transaction->payment_reference);

        if ($duringRequest !== null) {
            $duringRequest();
        }

        return Http::response($payload);
    });
}

function h1Webhook($test, Transaction $transaction, string $signatureMode = 'valid', array $overrides = [])
{
    $payload = [
        'data' => [
            'id' => 'evt_h1_fake',
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => ['id' => $transaction->payment_reference, 'attributes' => ['reference_number' => $transaction->transaction_no]],
            ],
        ],
    ];

    foreach ($overrides as $path => $value) {
        data_set($payload, $path, $value);
    }

    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $timestamp = now()->timestamp - ($signatureMode === 'expired' ? 600 : 0);
    $hash = hash_hmac('sha256', $timestamp.'.'.$body, 'h1_fake_webhook_secret');
    $signature = $signatureMode === 'invalid' ? str_repeat('0', 64) : $hash;

    return $test->call('POST', route('paymongo.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_PAYMONGO_SIGNATURE' => "t={$timestamp},te={$signature},li=",
    ], $body);
}

test('approved payment replays preserve every enrollment state and have no side effects', function (string $status, string $entryPoint) {
    $s = h1PaymentSetup($status, 'approved');
    $enrollmentBefore = $s['enrollment']->fresh()->getRawOriginal();
    $transactionBefore = $s['transaction']->fresh()->getRawOriginal();
    $invitationBefore = $s['invitation']->fresh()->getRawOriginal();
    Http::fake();

    for ($i = 0; $i < 2; $i++) {
        if ($entryPoint === 'success') {
            $response = $this->actingAs($s['student'])->get(route('student.transactions.success', $s['transaction']))->assertOk();

            if ($status === 'cancelled') {
                $response->assertSeeText('Your enrollment remains cancelled')
                    ->assertDontSeeText('Go to My Courses');
            }
        } elseif ($entryPoint === 'manual approval') {
            $this->actingAs(pathwiseUser('super_admin'))
                ->post(route('super_admin.transactions.approve', $s['transaction']))->assertRedirect();
        } else {
            h1Webhook($this, $s['transaction'])->assertOk()->assertJson(['message' => 'Transaction already processed.']);
        }
    }

    expect($s['enrollment']->fresh()->getRawOriginal())->toBe($enrollmentBefore)
        ->and($s['transaction']->fresh()->getRawOriginal())->toBe($transactionBefore)
        ->and($s['invitation']->fresh()->getRawOriginal())->toBe($invitationBefore)
        ->and(Enrollment::count())->toBe(1);
    Http::assertNothingSent();
    Notification::assertNothingSent();
})->with(['cancelled', 'active', 'completed'])->with(['success', 'webhook', 'manual approval']);

test('genuine payment approval never overwrites existing access or student progress', function (string $status, string $entryPoint) {
    $s = h1PaymentSetup($status);
    $before = $s['enrollment']->fresh()->getRawOriginal();
    $invitationBefore = $s['invitation']->fresh()->getRawOriginal();

    if ($entryPoint === 'manual') {
        $s['transaction']->update(['payment_method' => 'GCash', 'payment_proof' => 'payment-proofs/h1.png']);
        Storage::disk('local')->put('payment-proofs/h1.png', 'original proof');
        Http::fake();
    } else {
        h1FakeCheckout($s['transaction']);
    }

    if ($entryPoint === 'success') {
        $response = $this->actingAs($s['student'])->get(route('student.transactions.success', $s['transaction']))->assertOk();
        if ($status === 'cancelled') {
            $response->assertSeeText('Payment Confirmed!')->assertSeeText('Your enrollment remains cancelled');
        }
    } elseif ($entryPoint === 'webhook') {
        h1Webhook($this, $s['transaction'])->assertOk();
    } else {
        $response = $this->actingAs(pathwiseUser('super_admin'))
            ->post(route('super_admin.transactions.approve', $s['transaction']))->assertRedirect()->assertSessionHas('success');
        if ($status === 'cancelled') {
            $response->assertSessionHas('success', fn ($message) => str_contains($message, 'enrollment remains cancelled'));
        }
    }

    expect($s['transaction']->fresh()->status)->toBe('approved')
        ->and($s['enrollment']->fresh()->getRawOriginal())->toBe($before)
        ->and(Enrollment::count())->toBe(1);

    if ($status === 'cancelled') {
        expect($s['invitation']->fresh()->getRawOriginal())->toBe($invitationBefore);
        Notification::assertSentTo($s['student'], PathwiseNotification::class, function ($notice) use ($s) {
            return $notice->type === 'payment_received_access_unavailable'
                && str_contains($notice->message, 'enrollment remains cancelled')
                && $notice->via($s['student']) === ['database'];
        });
        $this->actingAs($s['student'])->get(route('student.learn.course', $s['course']))->assertForbidden();
    } else {
        expect($s['invitation']->fresh()->accepted_at)->not->toBeNull();
        Notification::assertSentTo($s['student'], PathwiseNotification::class, fn ($notice) => $notice->type === 'payment_approved');
    }

    Notification::assertSentToTimes($s['student'], PathwiseNotification::class, 1);
    if ($entryPoint === 'manual') {
        Http::assertNothingSent();
        expect(Storage::disk('local')->get('payment-proofs/h1.png'))->toBe('original proof');
    }
})->with(['cancelled', 'active', 'completed'])->with(['success', 'webhook', 'paymongo admin', 'manual']);

test('first payments create one enrollment and callback ordering is idempotent', function (string $first) {
    $s = h1PaymentSetup(null);
    h1FakeCheckout($s['transaction']);

    if ($first === 'success') {
        $this->actingAs($s['student'])->get(route('student.transactions.success', $s['transaction']))->assertOk();
        h1Webhook($this, $s['transaction'])->assertOk();
    } else {
        h1Webhook($this, $s['transaction'])->assertOk();
        $this->actingAs($s['student'])->get(route('student.transactions.success', $s['transaction']))->assertOk();
    }

    h1Webhook($this, $s['transaction'])->assertOk();
    expect($s['transaction']->fresh()->status)->toBe('approved')
        ->and(Enrollment::sole()->status)->toBe('active')
        ->and($s['invitation']->fresh()->accepted_at)->not->toBeNull();
    Http::assertSentCount(1);
    Notification::assertSentToTimes($s['student'], PathwiseNotification::class, 1);
})->with(['success', 'webhook']);

test('cancellation committed during provider verification wins over late activation', function () {
    $s = h1PaymentSetup('active');
    h1FakeCheckout($s['transaction'], duringRequest: function () use ($s) {
        $s['enrollment']->update(['status' => 'cancelled']);
    });

    $this->actingAs($s['student'])->get(route('student.transactions.success', $s['transaction']))
        ->assertOk()->assertSeeText('Your enrollment remains cancelled');

    expect($s['transaction']->fresh()->status)->toBe('approved')
        ->and($s['enrollment']->fresh()->status)->toBe('cancelled');
});

test('a previously approved removal survives later success and webhook replays', function () {
    $s = h1PaymentSetup('active');
    h1FakeCheckout($s['transaction']);
    $this->actingAs($s['student'])->get(route('student.transactions.success', $s['transaction']))->assertOk();
    $department = Department::create(['name' => 'H1 department']);
    $s['teacher']->update(['department_id' => $department->id]);
    $head = pathwiseUser('admin', ['department_id' => $department->id]);
    $removal = StudentRemovalRequest::create([
        'course_id' => $s['course']->id, 'student_id' => $s['student']->id,
        'instructor_id' => $s['teacher']->id, 'reason' => 'Approved administrative removal',
    ]);
    $this->actingAs($head)->post(route('admin.student-removal-requests.review', $removal), ['decision' => 'approved'])
        ->assertSessionHas('success');
    $before = $s['enrollment']->fresh()->getRawOriginal();
    Http::fake();

    $this->actingAs($s['student'])->get(route('student.transactions.success', $s['transaction']))->assertOk();
    h1Webhook($this, $s['transaction'])->assertOk();

    expect($s['enrollment']->fresh()->getRawOriginal())->toBe($before)
        ->and($removal->fresh()->status)->toBe('approved');
    Http::assertNothingSent();
    Notification::assertSentToTimes($s['student'], PathwiseNotification::class, 1);
});

test('failed or mismatched provider data cannot grant or revoke access', function (?string $status, string $problem) {
    $s = h1PaymentSetup($status);
    $before = $s['enrollment']?->fresh()?->getRawOriginal();
    $overrides = match ($problem) {
        'failed payment' => ['data.attributes.payments.0.attributes.status' => 'failed'],
        'unpaid' => ['data.attributes.payments' => []],
        'wrong session' => ['data.id' => 'cs_wrong'],
        'wrong reference' => ['data.attributes.reference_number' => 'wrong-reference'],
        'wrong currency' => ['data.attributes.payments.0.attributes.currency' => 'USD'],
        'wrong amount' => ['data.attributes.payments.0.attributes.amount' => 1],
    };
    h1FakeCheckout($s['transaction'], $overrides);

    h1Webhook($this, $s['transaction'])->assertStatus(202);

    expect($s['transaction']->fresh()->status)->toBe('pending')
        ->and($s['enrollment']?->fresh()?->getRawOriginal())->toBe($before)
        ->and($s['invitation']->fresh()->accepted_at)->toBeNull()
        ->and(Enrollment::count())->toBe($status === null ? 0 : 1);
    Notification::assertNothingSent();
})->with([
    'cancelled' => ['cancelled'],
    'active' => ['active'],
    'completed' => ['completed'],
    'no enrollment' => [null],
])->with(['failed payment', 'unpaid', 'wrong session', 'wrong reference', 'wrong currency', 'wrong amount']);

test('a stale verified callback observes an approval committed during retrieval', function () {
    $s = h1PaymentSetup();
    $approved = null;
    h1FakeCheckout($s['transaction'], duringRequest: function () use ($s, &$approved) {
        $s['transaction']->update(['status' => 'approved', 'remarks' => 'Other confirmation already committed']);
        $approved = $s['transaction']->fresh()->getRawOriginal();
    });

    $this->actingAs($s['student'])->get(route('student.transactions.success', $s['transaction']))->assertOk();

    expect($s['transaction']->fresh()->getRawOriginal())->toBe($approved)
        ->and($s['enrollment']->fresh()->status)->toBe('cancelled')
        ->and($s['invitation']->fresh()->accepted_at)->toBeNull();
    Notification::assertNothingSent();
});

test('manual approval rechecks a stale pending transaction before applying effects', function () {
    $s = h1PaymentSetup();
    $s['transaction']->update(['payment_method' => 'GCash', 'payment_proof' => 'payment-proofs/h1.png']);
    $changed = false;
    DB::listen(function (QueryExecuted $query) use ($s, &$changed) {
        if (! $changed && str_starts_with($query->sql, 'select') && str_contains($query->sql, '"transactions"')) {
            $changed = true;
            $s['transaction']->update(['status' => 'approved', 'remarks' => 'Other approval committed']);
        }
    });
    Http::fake();

    $this->actingAs(pathwiseUser('super_admin'))->post(route('super_admin.transactions.approve', $s['transaction']))
        ->assertSessionHas('success', 'This transaction has already been approved.');

    expect($s['enrollment']->fresh()->status)->toBe('cancelled')
        ->and($s['invitation']->fresh()->accepted_at)->toBeNull()
        ->and($s['transaction']->fresh()->remarks)->toBe('Other approval committed');
    Notification::assertNothingSent();
    Http::assertNothingSent();
});

test('manual payment proof remains mandatory and rejected transactions stay unapproved', function (string $status) {
    $s = h1PaymentSetup('cancelled', $status);
    $s['transaction']->update(['payment_method' => 'GCash', 'payment_proof' => null]);
    Http::fake();

    $this->actingAs(pathwiseUser('super_admin'))->post(route('super_admin.transactions.approve', $s['transaction']))
        ->assertSessionHas('error');

    expect($s['transaction']->fresh()->status)->toBe($status)
        ->and($s['enrollment']->fresh()->status)->toBe('cancelled');
    Notification::assertNothingSent();
    Http::assertNothingSent();
})->with(['pending', 'rejected']);

test('provider outages grant no access or approval', function () {
    $s = h1PaymentSetup();
    Http::fake(['api.paymongo.com/*' => Http::response([], 503)]);

    h1Webhook($this, $s['transaction'])->assertStatus(202);

    expect($s['transaction']->fresh()->status)->toBe('pending')
        ->and($s['enrollment']->fresh()->status)->toBe('cancelled');
    Notification::assertNothingSent();
});

test('unpaid success pages remain pending after retries without access', function () {
    $s = h1PaymentSetup();
    h1FakeCheckout($s['transaction'], ['data.attributes.payments' => []]);

    $this->actingAs($s['student'])->get(route('student.transactions.success', $s['transaction']))
        ->assertOk()->assertSeeText('Payment Verification Pending');

    expect($s['enrollment']->fresh()->status)->toBe('cancelled')
        ->and($s['transaction']->fresh()->status)->toBe('pending');
    Http::assertSentCount(3);
    Notification::assertNothingSent();
});

test('invalid signed delivery cannot even replay an approved payment', function (string $mode) {
    $s = h1PaymentSetup('cancelled', 'approved');
    Http::fake();

    h1Webhook($this, $s['transaction'], $mode)->assertStatus(401);

    expect($s['enrollment']->fresh()->status)->toBe('cancelled');
    Http::assertNothingSent();
    Notification::assertNothingSent();
})->with(['invalid', 'expired']);

test('duplicate deliveries still reject a mismatched checkout session', function () {
    $s = h1PaymentSetup('cancelled', 'approved');
    Http::fake();

    h1Webhook($this, $s['transaction'], overrides: ['data.attributes.data.id' => 'cs_different'])->assertStatus(400);

    expect($s['enrollment']->fresh()->status)->toBe('cancelled');
    Http::assertNothingSent();
});

test('distinct genuine payments preserve both financial records but only one closed enrollment', function () {
    $s = h1PaymentSetup();
    h1FakeCheckout($s['transaction']);
    h1Webhook($this, $s['transaction'])->assertOk();
    $first = $s['transaction']->fresh()->getRawOriginal();
    $second = h1Transaction($s['student'], $s['course']);
    h1FakeCheckout($second);

    h1Webhook($this, $second)->assertOk();

    expect(Transaction::where('status', 'approved')->count())->toBe(2)
        ->and(Enrollment::count())->toBe(1)
        ->and($s['enrollment']->fresh()->status)->toBe('cancelled')
        ->and($s['transaction']->fresh()->getRawOriginal())->toBe($first);
});

test('a locally rejected checkout can be genuinely paid later without undoing cancellation', function () {
    $s = h1PaymentSetup('cancelled', 'rejected');
    h1FakeCheckout($s['transaction']);

    h1Webhook($this, $s['transaction'])->assertOk();

    expect($s['transaction']->fresh()->status)->toBe('approved')
        ->and($s['transaction']->fresh()->payment_reference)->toStartWith('cs_')
        ->and($s['enrollment']->fresh()->status)->toBe('cancelled');
});

test('cancelled students cannot start or retry checkout even after a course becomes free', function (int $price) {
    $s = h1PaymentSetup();
    $s['course']->update(['price' => $price]);
    Http::fake();
    $before = $s['transaction']->fresh()->getRawOriginal();

    $this->actingAs($s['student'])->post(route('student.transactions.store', $s['course']))
        ->assertRedirect(route('student.course.show', $s['course']))->assertSessionHas('error');

    expect($s['enrollment']->fresh()->status)->toBe('cancelled')
        ->and($s['transaction']->fresh()->getRawOriginal())->toBe($before)
        ->and(Transaction::count())->toBe(1);
    Http::assertNothingSent();
    Notification::assertNothingSent();
})->with([0, 499]);

test('the final free-course activation check sees cancellation after the entry check', function () {
    $s = h1PaymentSetup('active');
    $s['course']->update(['price' => 0]);
    $cancelled = false;
    DB::listen(function (QueryExecuted $query) use ($s, &$cancelled) {
        if (! $cancelled && str_starts_with($query->sql, 'select') && str_contains($query->sql, '"enrollments"')) {
            $cancelled = true;
            $s['enrollment']->update(['status' => 'cancelled']);
        }
    });
    Http::fake();

    $this->actingAs($s['student'])->post(route('student.transactions.store', $s['course']))
        ->assertSessionHas('error');

    expect($s['enrollment']->fresh()->status)->toBe('cancelled');
    Notification::assertNothingSent();
});

test('successful late payment preserves academic history and every uploaded file', function () {
    $s = h1PaymentSetup();
    $lesson = $s['course']->lessons()->first();
    $progress = LessonProgress::create(['student_id' => $s['student']->id, 'lesson_id' => $lesson->id, 'status' => 'completed', 'completed_at' => now()]);
    $quiz = Quiz::create(['course_id' => $s['course']->id, 'title' => 'Historical quiz', 'passing_score' => 70]);
    $result = QuizResult::create(['student_id' => $s['student']->id, 'quiz_id' => $quiz->id, 'score' => 4, 'total_items' => 5, 'percentage' => 80, 'remarks' => 'passed']);
    $assignment = Assignment::create(['course_id' => $s['course']->id, 'title' => 'Historical assignment', 'submission_type' => 'essay', 'max_score' => 100]);
    $submission = Submission::create([
        'assignment_id' => $assignment->id, 'student_id' => $s['student']->id,
        'answer_text' => 'Original answer', 'status' => 'graded', 'score' => 90, 'feedback' => 'Final grade',
        'ai_score' => 95, 'ai_feedback' => 'AI feedback', 'file_path' => 'assignment-submissions/h1.txt',
    ]);
    $certificate = Certificate::create(['student_id' => $s['student']->id, 'course_id' => $s['course']->id, 'certificate_number' => 'H1-CERT', 'issued_date' => now()->toDateString(), 'status' => 'issued']);
    $records = [$s['enrollment'], $progress, $result, $submission, $certificate];
    $before = array_map(fn ($record) => $record->fresh()->getRawOriginal(), $records);
    Storage::disk('local')->put('assignment-submissions/h1.txt', 'original attachment');
    Storage::disk('public')->put('courses/h1.jpg', 'original thumbnail');
    h1FakeCheckout($s['transaction']);

    h1Webhook($this, $s['transaction'])->assertOk();

    expect(array_map(fn ($record) => $record->fresh()->getRawOriginal(), $records))->toBe($before)
        ->and(Storage::disk('local')->get('assignment-submissions/h1.txt'))->toBe('original attachment')
        ->and(Storage::disk('public')->get('courses/h1.jpg'))->toBe('original thumbnail');
});

test('only the existing authorized administrator action restores access', function () {
    $s = h1PaymentSetup('cancelled', 'approved');

    $this->actingAs(pathwiseUser('admin'))->put(route('enrollments.update', $s['enrollment']), [
        'student_id' => $s['student']->id, 'course_id' => $s['course']->id,
        'status' => 'active', 'progress_percentage' => 60,
    ])->assertRedirect(route('enrollments.index'));
    Http::fake();

    $this->actingAs($s['student'])->get(route('student.transactions.success', $s['transaction']))
        ->assertOk()->assertSeeText('Your enrollment is active');

    expect($s['enrollment']->fresh()->status)->toBe('active');
    Http::assertNothingSent();
});

test('payment ownership and approval role restrictions remain unchanged', function (string $actor, string $entryPoint) {
    $s = h1PaymentSetup('cancelled', 'approved');
    Http::fake();
    $user = pathwiseUser($actor);

    if ($entryPoint === 'success') {
        $this->actingAs($user)->get(route('student.transactions.success', $s['transaction']))->assertForbidden();
    } else {
        $this->actingAs($user)->post(route('super_admin.transactions.approve', $s['transaction']))->assertForbidden();
    }

    expect($s['enrollment']->fresh()->status)->toBe('cancelled');
    Http::assertNothingSent();
})->with([['student', 'success'], ['teacher', 'success'], ['student', 'approval'], ['teacher', 'approval'], ['admin', 'approval']]);

test('guest access to payment success still requires login', function () {
    $s = h1PaymentSetup('cancelled', 'approved');
    Http::fake();

    $this->get(route('student.transactions.success', $s['transaction']))->assertRedirect(route('login'));

    expect($s['enrollment']->fresh()->status)->toBe('cancelled');
    Http::assertNothingSent();
});

test('approved receipt replay cannot recreate an enrollment after its marker was deleted', function () {
    $s = h1PaymentSetup('cancelled', 'approved');
    $this->actingAs(pathwiseUser('admin'))->delete(route('enrollments.destroy', $s['enrollment']))->assertRedirect();
    Http::fake();

    $this->actingAs($s['student'])->get(route('student.transactions.success', $s['transaction']))
        ->assertOk()->assertSeeText('No enrollment access is available');
    h1Webhook($this, $s['transaction'])->assertOk();

    $this->assertDatabaseCount('enrollments', 0);
    expect($s['transaction']->fresh()->status)->toBe('approved');
    Http::assertNothingSent();
});

test('documents remaining risk when admin deletes cancellation marker before first genuine confirmation', function () {
    // This is an explicit boundary test, not desired restoration behavior.
    // EnrollmentController::destroy is outside the approved H1 file scope.
    $s = h1PaymentSetup();
    $this->actingAs(pathwiseUser('admin'))->delete(route('enrollments.destroy', $s['enrollment']))->assertRedirect();
    h1FakeCheckout($s['transaction']);

    h1Webhook($this, $s['transaction'])->assertOk();

    expect($s['enrollment']->fresh())->toBeNull()
        ->and(Enrollment::sole()->status)->toBe('active')
        ->and($s['transaction']->fresh()->status)->toBe('approved');
});
