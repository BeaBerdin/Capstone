<?php

use App\Models\Assignment;
use App\Models\Enrollment;
use App\Models\Submission;
use Illuminate\Support\Facades\Http;

/*
| Manuscript FR (s) and Fig. 19: an AI evaluation is a pending suggestion
| that the student cannot see. Only the teacher's grade and feedback reach
| the student, after the teacher grades.
*/

const AI_FEEDBACK_MARKER = 'AI-FEEDBACK-SECRET-7f3a';
const AI_STRENGTH_MARKER = 'AI-STRENGTH-SECRET-91bc';
const AI_IMPROVEMENT_MARKER = 'AI-IMPROVE-SECRET-4d2e';

function aiVisibilitySetup(): array
{
    $teacher = pathwiseApprovedTeacher();
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published']);

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    $assignment = Assignment::create([
        'course_id' => $course->id,
        'title' => 'Visibility Essay',
        'submission_type' => 'essay',
        'ai_evaluation_enabled' => true,
        'max_score' => 100,
        'is_published' => true,
    ]);

    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
        'answer_text' => 'My essay.',
        'status' => 'submitted',
        'submitted_at' => now(),
        'ai_evaluation_status' => 'completed',
        'ai_score' => 73,
        'ai_feedback' => AI_FEEDBACK_MARKER,
        'ai_strengths' => AI_STRENGTH_MARKER,
        'ai_improvements' => AI_IMPROVEMENT_MARKER,
        'ai_evaluated_at' => now(),
    ]);

    return [$teacher, $student, $assignment, $submission];
}

/**
 * Every page a student can open that could show their submission.
 */
function studentPages(Assignment $assignment): array
{
    return [
        route('student.assignments.show', $assignment),
        route('student.assignments.index'),
        route('student.dashboard'),
        route('student.my-courses'),
        route('student.learn.course', $assignment->course_id),
    ];
}

function assertNoAiShown($test, App\Models\User $student, Assignment $assignment): void
{
    foreach (studentPages($assignment) as $url) {
        $test->actingAs($student)
            ->get($url)
            ->assertOk()
            ->assertDontSee(AI_FEEDBACK_MARKER)
            ->assertDontSee(AI_STRENGTH_MARKER)
            ->assertDontSee(AI_IMPROVEMENT_MARKER)
            ->assertDontSee('73 / 100')
            ->assertDontSee('AI Suggested Score');
    }
}

test('a student sees no AI score or feedback before the teacher grades', function () {
    [, $student, $assignment] = aiVisibilitySetup();

    assertNoAiShown($this, $student, $assignment);

    // No score at all is shown before grading.
    $this->actingAs($student)
        ->get(route('student.assignments.show', $assignment))
        ->assertDontSee('Final Score');
});

test('a student cannot open the teacher or admin view of their submission', function () {
    [, $student, , $submission] = aiVisibilitySetup();

    $this->actingAs($student)->get(route('teacher.submissions.show', $submission))->assertForbidden();
    $this->actingAs($student)->get(route('submissions.show', $submission))->assertForbidden();
});

test('running the AI evaluation does not notify the student', function () {
    [$teacher, $student, , $submission] = aiVisibilitySetup();
    $submission->update(['ai_evaluation_status' => 'not_requested', 'ai_score' => null, 'ai_feedback' => null]);

    config(['services.gemini.api_key' => 'test-key']);
    Http::fake(['*' => Http::response([
        'output_text' => json_encode([
            'score' => 73,
            'feedback' => AI_FEEDBACK_MARKER,
            'strengths' => [AI_STRENGTH_MARKER],
            'improvements' => [AI_IMPROVEMENT_MARKER],
        ]),
    ])]);

    $this->actingAs($teacher)
        ->post(route('teacher.submissions.ai-evaluate', $submission))
        ->assertSessionHas('success');

    expect($submission->fresh()->ai_score)->toBe(73)
        ->and($student->notifications()->count())->toBe(0);
});

test('after grading the student sees the teacher\'s score and feedback, not the AI\'s', function () {
    [$teacher, $student, $assignment, $submission] = aiVisibilitySetup();

    $this->actingAs($teacher)
        ->put(route('teacher.submissions.grade', $submission), [
            'status' => 'graded',
            'score' => 88,
            'feedback' => 'Teacher feedback: well argued.',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($student)
        ->get(route('student.assignments.show', $assignment))
        ->assertOk()
        ->assertSee('88')
        ->assertSee('Teacher feedback: well argued.');

    assertNoAiShown($this, $student, $assignment);

    // The grading notification carries the teacher's score.
    $notification = $student->notifications()->latest()->first();

    expect($notification->data['message'])->toContain('88')
        ->not->toContain('73')
        ->not->toContain(AI_FEEDBACK_MARKER);
});

test('a submission returned for revision shows no AI data either', function () {
    [$teacher, $student, $assignment, $submission] = aiVisibilitySetup();

    $this->actingAs($teacher)
        ->put(route('teacher.submissions.grade', $submission), [
            'status' => 'returned',
            'feedback' => 'Please revise the conclusion.',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($student)
        ->get(route('student.assignments.show', $assignment))
        ->assertOk()
        ->assertSee('Please revise the conclusion.');

    assertNoAiShown($this, $student, $assignment);
});

test('the course teacher can review the AI evaluation before grading', function () {
    [$teacher, , , $submission] = aiVisibilitySetup();

    $this->actingAs($teacher)
        ->get(route('teacher.submissions.show', $submission))
        ->assertOk()
        ->assertSee('AI Suggested Score')
        ->assertSee('73')
        ->assertSee(AI_FEEDBACK_MARKER)
        ->assertSee(AI_STRENGTH_MARKER)
        ->assertSee(AI_IMPROVEMENT_MARKER);
});

test('another teacher cannot open the AI evaluation', function () {
    [, , , $submission] = aiVisibilitySetup();

    $this->actingAs(pathwiseApprovedTeacher())
        ->get(route('teacher.submissions.show', $submission))
        ->assertForbidden();
});
