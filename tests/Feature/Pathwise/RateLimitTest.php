<?php

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Submission;
use App\Models\Transaction;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Http;

/*
| Actions that call an outside service or create records are limited per
| signed-in user, per minute (AppServiceProvider::RATE_LIMITS). Past the
| limit the request is answered with 429 and does nothing.
*/

function rateLimitSubmission(): Submission
{
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published']);

    $assignment = Assignment::create([
        'course_id' => $course->id,
        'title' => 'Limited Essay',
        'submission_type' => 'essay',
        'max_score' => 100,
        'is_published' => true,
        'ai_evaluation_enabled' => false,   // refused before any Gemini call
    ]);

    return Submission::create([
        'assignment_id' => $assignment->id,
        'student_id' => pathwiseUser('student')->id,
        'answer_text' => 'An answer.',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);
}

test('AI evaluation is limited per teacher and then shows the friendly message', function () {
    Http::fake();
    $submission = rateLimitSubmission();
    $teacher = $submission->assignment->course->teacher;
    $limit = AppServiceProvider::RATE_LIMITS['ai-evaluation'];

    for ($i = 1; $i <= $limit; $i++) {
        $this->actingAs($teacher)
            ->post(route('teacher.submissions.ai-evaluate', $submission))
            ->assertRedirect();
    }

    $this->actingAs($teacher)
        ->post(route('teacher.submissions.ai-evaluate', $submission))
        ->assertRedirect(route('teacher.submissions.show', $submission))
        ->assertSessionHas('error', 'AI features are unavailable at the moment. Please try again later.');

    // Another teacher has their own counter.
    $otherSubmission = rateLimitSubmission();
    $this->actingAs($otherSubmission->assignment->course->teacher)
        ->post(route('teacher.submissions.ai-evaluate', $otherSubmission))
        ->assertRedirect();

    // The limit clears after a minute.
    $this->travel(61)->seconds();
    $this->actingAs($teacher)
        ->post(route('teacher.submissions.ai-evaluate', $submission))
        ->assertRedirect();

    Http::assertNothingSent();
});

test('learning-path generation is limited per student and then answers 429', function () {
    $student = pathwiseUser('student');
    $limit = AppServiceProvider::RATE_LIMITS['learning-path'];

    for ($i = 1; $i <= $limit; $i++) {
        $this->actingAs($student)
            ->post(route('student.learning-paths.generate'))
            ->assertRedirect();
    }

    $this->actingAs($student)
        ->post(route('student.learning-paths.generate'))
        ->assertStatus(429);

    $this->actingAs(pathwiseUser('student'))
        ->post(route('student.learning-paths.generate'))
        ->assertRedirect();
});

test('enrollment is limited per student and then answers 429 without enrolling', function () {
    $student = pathwiseUser('student');
    $teacher = pathwiseUser('teacher');
    $limit = AppServiceProvider::RATE_LIMITS['enrollment'];

    $courses = collect(range(1, $limit + 1))
        ->map(fn ($n) => pathwiseCourse($teacher, ['status' => 'published', 'price' => 0, 'title' => 'Free Course '.$n]));

    foreach ($courses->take($limit) as $course) {
        $this->actingAs($student)->post(route('student.enroll', $course))->assertRedirect();
    }

    expect(Enrollment::where('student_id', $student->id)->count())->toBe($limit);

    $this->actingAs($student)
        ->post(route('student.enroll', $courses->last()))
        ->assertStatus(429);

    expect(Enrollment::where('student_id', $student->id)->count())->toBe($limit)
        ->and(Enrollment::where('course_id', $courses->last()->id)->exists())->toBeFalse();
});

test('purchase is limited per student and then answers 429 without creating a transaction', function () {
    // No PayMongo key in tests: each allowed attempt stops with a message
    // before any checkout is created, but it still counts toward the limit.
    Http::fake();
    $student = pathwiseUser('student');
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'price' => 499]);
    $limit = AppServiceProvider::RATE_LIMITS['purchase'];

    for ($i = 1; $i <= $limit; $i++) {
        $this->actingAs($student)->post(route('student.transactions.store', $course))->assertRedirect();
    }

    $transactionsBefore = Transaction::count();

    $this->actingAs($student)
        ->post(route('student.transactions.store', $course))
        ->assertStatus(429);

    expect(Transaction::count())->toBe($transactionsBefore);
});

test('each limited action has its own counter', function () {
    $student = pathwiseUser('student');

    for ($i = 1; $i <= AppServiceProvider::RATE_LIMITS['learning-path']; $i++) {
        $this->actingAs($student)->post(route('student.learning-paths.generate'));
    }
    $this->actingAs($student)->post(route('student.learning-paths.generate'))->assertStatus(429);

    // Reaching the learning-path limit does not block enrollment.
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'price' => 0]);
    $this->actingAs($student)->post(route('student.enroll', $course))->assertRedirect();
    expect(Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->exists())->toBeTrue();
});
