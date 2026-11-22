<?php

use App\Models\Assignment;
use App\Models\Submission;

/*
| Manuscript Fig. 19: the Teacher Dashboard shows AI evaluations awaiting
| approval. Only the teacher's own courses; finished AI evaluations on
| submissions that are not graded yet.
*/

function aiReviewAssignment(App\Models\User $teacher, string $courseTitle = 'Java Course', string $title = 'Hello Java'): Assignment
{
    $course = pathwiseCourse($teacher, ['status' => 'published', 'title' => $courseTitle]);

    return Assignment::create([
        'course_id' => $course->id,
        'title' => $title,
        'submission_type' => 'code',
        'programming_language' => 'java',
        'ai_evaluation_enabled' => true,
        'max_score' => 100,
        'is_published' => true,
    ]);
}

function aiReviewSubmission(Assignment $assignment, array $attributes = [], ?string $studentName = null): Submission
{
    $student = pathwiseUser('student', $studentName ? ['name' => $studentName] : []);

    return Submission::create(array_merge([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
        'answer_text' => 'class Main {}',
        'status' => 'submitted',
        'submitted_at' => now(),
        'ai_evaluation_status' => 'completed',
        'ai_score' => 82,
        'ai_feedback' => 'Good.',
        'ai_evaluated_at' => now(),
    ], $attributes));
}

test('the dashboard lists finished AI evaluations awaiting review with a Review button', function () {
    $teacher = pathwiseApprovedTeacher();
    $assignment = aiReviewAssignment($teacher);
    $submission = aiReviewSubmission($assignment, [], 'Ana Reyes');

    $this->actingAs($teacher)
        ->get(route('teacher.dashboard'))
        ->assertOk()
        ->assertSee('AI Evaluations Awaiting Review')
        ->assertSee('1 awaiting')
        ->assertSee('Ana Reyes')
        ->assertSee('Hello Java')
        ->assertSee('Java Course')
        ->assertSee('AI: 82 / 100')
        ->assertSee(route('teacher.submissions.show', $submission), false)
        ->assertSee('Review');
});

test('graded, unevaluated and other teachers\' submissions are not listed', function () {
    $teacher = pathwiseApprovedTeacher();
    $assignment = aiReviewAssignment($teacher);

    aiReviewSubmission($assignment, ['status' => 'graded', 'score' => 90], 'Graded Student');
    aiReviewSubmission($assignment, ['ai_evaluation_status' => 'not_requested', 'ai_score' => null], 'No AI Student');
    aiReviewSubmission($assignment, ['status' => 'returned'], 'Returned Student');

    $otherAssignment = aiReviewAssignment(pathwiseApprovedTeacher(), 'Other Teacher Course');
    aiReviewSubmission($otherAssignment, [], 'Other Course Student');

    $this->actingAs($teacher)
        ->get(route('teacher.dashboard'))
        ->assertOk()
        ->assertSee('0 awaiting')
        ->assertSee('No AI evaluations are waiting for your review.')
        ->assertDontSee('Graded Student')
        ->assertDontSee('No AI Student')
        ->assertDontSee('Returned Student')
        ->assertDontSee('Other Course Student');
});

test('the count covers all, the list shows the latest 5', function () {
    $teacher = pathwiseApprovedTeacher();
    $assignment = aiReviewAssignment($teacher);

    foreach (range(1, 7) as $number) {
        $this->travel(1)->minutes();
        aiReviewSubmission($assignment, [], sprintf('Learner %02d', $number));
    }

    $response = $this->actingAs($teacher)
        ->get(route('teacher.dashboard'))
        ->assertOk()
        ->assertSee('7 awaiting')
        ->assertSee('Showing the latest 5 of 7')
        ->assertSee('Learner 07')
        ->assertSee('Learner 03')
        ->assertDontSee('Learner 02')
        ->assertDontSee('Learner 01');

    expect($response->viewData('aiAwaitingSubmissions')->pluck('student.name')->first())->toBe('Learner 07');
});

test('failed and stuck AI evaluations get a note; a running one does not', function () {
    $teacher = pathwiseApprovedTeacher();
    $assignment = aiReviewAssignment($teacher);

    $failed = aiReviewSubmission($assignment, ['ai_evaluation_status' => 'failed', 'ai_score' => null]);
    $stuck = aiReviewSubmission($assignment, ['ai_evaluation_status' => 'processing', 'ai_score' => null]);
    // Make the "processing" one older than the timeout without touching updated_at again.
    Submission::whereKey($stuck->id)->update(['updated_at' => now()->subMinutes(Submission::AI_PROCESSING_TIMEOUT_MINUTES + 5)]);

    aiReviewSubmission($assignment, ['ai_evaluation_status' => 'processing', 'ai_score' => null]); // still running

    $this->actingAs($teacher)
        ->get(route('teacher.dashboard'))
        ->assertOk()
        ->assertSee('data-ai-unfinished', false)
        ->assertSee('2 AI evaluations')
        ->assertSee('did not finish')
        ->assertSee(route('teacher.submissions.show', $failed), false);
});

test('without failed or stuck evaluations there is no note', function () {
    $teacher = pathwiseApprovedTeacher();
    aiReviewSubmission(aiReviewAssignment($teacher));

    $this->actingAs($teacher)
        ->get(route('teacher.dashboard'))
        ->assertOk()
        ->assertDontSee('data-ai-unfinished', false);
});

test('an empty dashboard shows the empty message', function () {
    $this->actingAs(pathwiseApprovedTeacher())
        ->get(route('teacher.dashboard'))
        ->assertOk()
        ->assertSee('0 awaiting')
        ->assertSee('No AI evaluations are waiting for your review.');
});
