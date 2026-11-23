<?php

use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use App\Models\Submission;
use App\Support\CertificateEligibility;
use Illuminate\Support\Facades\Notification;

/*
| PathWise is self-paced: activities and assignments have no due date. The
| assignments.due_date column is kept (nullable, unused) so old data is not
| lost, but nothing reads or writes it. Quiz time limits still apply.
*/

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
});

/**
 * A published course with one activity (max 100) whose stored due date is
 * a month in the past, and a student who enrolls today.
 */
function lateEnrolleeFixture(): array
{
    $teacher = pathwiseApprovedTeacher();
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'certificate_available' => true]);

    $lesson = Lesson::create([
        'course_id' => $course->id, 'title' => 'Activity 1', 'content' => 'Answer this.', 'lesson_type' => 'activity',
        'lesson_order' => 1, 'is_preview' => false, 'is_published' => true,
    ]);

    $activity = $lesson->assignments()->create([
        'course_id' => $course->id, 'title' => 'Activity 1', 'description' => 'Answer this.',
        'submission_type' => 'essay', 'answer_format' => 'either', 'max_score' => 100,
        'is_activity' => true, 'is_published' => true,
    ]);

    // Old data from before due dates were removed.
    $activity->forceFill(['due_date' => now()->subMonth()])->save();

    Enrollment::create([
        'course_id' => $course->id, 'student_id' => $student->id, 'status' => 'active',
        'enrolled_at' => now(), 'progress_percentage' => 0,
    ]);

    return [$teacher, $student, $course, $lesson, $activity];
}

test('a student who enrolls after an old due date can still submit and resubmit', function () {
    [$teacher, $student, $course, $lesson, $activity] = lateEnrolleeFixture();

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $activity), ['answer_text' => 'First answer'])
        ->assertSessionHasNoErrors();

    $submission = Submission::sole();

    // Below 75%: resubmission is allowed, whatever the old date says.
    $this->actingAs($teacher)
        ->put(route('teacher.submissions.grade', $submission), ['status' => 'graded', 'score' => 60])
        ->assertSessionHasNoErrors();

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $activity), ['answer_text' => 'Improved answer'])
        ->assertSessionHasNoErrors();

    expect($submission->fresh()->answer_text)->toBe('Improved answer')
        ->and($submission->fresh()->status)->toBe('submitted');

    // 75% or more is final.
    $this->actingAs($teacher)
        ->put(route('teacher.submissions.grade', $submission), ['status' => 'graded', 'score' => 75])
        ->assertSessionHasNoErrors();

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $activity), ['answer_text' => 'Another try'])
        ->assertSessionHasErrors('submission');

    expect($submission->fresh()->score)->toBe(75);
});

test('a standalone assignment with an old due date accepts a submission', function () {
    [$teacher, $student, $course] = lateEnrolleeFixture();

    $assignment = Assignment::create([
        'course_id' => $course->id, 'title' => 'Essay', 'submission_type' => 'essay',
        'answer_format' => 'either', 'max_score' => 100, 'is_published' => true,
    ]);
    $assignment->forceFill(['due_date' => now()->subYear()])->save();

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), ['answer_text' => 'My essay'])
        ->assertSessionHasNoErrors();

    expect(Submission::where('assignment_id', $assignment->id)->count())->toBe(1);
});

test('certificate eligibility does not depend on an old due date', function () {
    [$teacher, $student, $course, $lesson, $activity] = lateEnrolleeFixture();

    LessonProgress::create([
        'student_id' => $student->id, 'lesson_id' => $lesson->id,
        'status' => 'completed', 'completed_at' => now(),
    ]);

    Submission::create([
        'assignment_id' => $activity->id, 'student_id' => $student->id, 'answer_text' => 'Answer',
        'status' => 'graded', 'score' => 80, 'ai_evaluation_status' => 'not_requested', 'submitted_at' => now(),
    ]);

    expect(CertificateEligibility::isEligible($course, $student->id))->toBeTrue();

    CertificateEligibility::issueIfEligible($course, $student->id);

    expect(Certificate::where('student_id', $student->id)->where('course_id', $course->id)->exists())->toBeTrue();
});

test('students are not shown due dates or deadline labels', function () {
    [$teacher, $student, $course, $lesson, $activity] = lateEnrolleeFixture();

    foreach ([route('student.assignments.index'), route('student.assignments.show', $activity)] as $page) {
        $this->actingAs($student)->get($page)
            ->assertOk()
            ->assertDontSee('Due Date')
            ->assertDontSee('Deadline')
            ->assertDontSee('No deadline')
            ->assertDontSee('Overdue');
    }
});

test('teacher activity and assignment forms have no due date input, and a posted due date is ignored', function () {
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher, ['status' => 'draft']);

    $this->actingAs($teacher)->get(route('teacher.lessons.create', $course))
        ->assertOk()
        ->assertDontSee('name="activity_due_date"', false);

    $this->actingAs($teacher)->get(route('teacher.assignments.create', $course))
        ->assertOk()
        ->assertDontSee('name="due_date"', false);

    $this->actingAs($teacher)->post(route('teacher.lessons.store', $course), [
        'title' => 'New Activity', 'lesson_type' => 'activity', 'content' => 'Do this.',
        'lesson_order' => 1, 'activity_max_score' => 100, 'activity_answer_format' => 'either',
        'activity_due_date' => '2020-01-01T10:00', 'is_published' => 1,
    ])->assertSessionHasNoErrors();

    $activity = Assignment::where('course_id', $course->id)->where('is_activity', true)->sole();
    expect($activity->due_date)->toBeNull();
});

test('a quiz time limit still applies', function () {
    $student = pathwiseUser('student');
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published']);

    Enrollment::create([
        'student_id' => $student->id, 'course_id' => $course->id, 'status' => 'active',
        'enrolled_at' => now(), 'progress_percentage' => 0,
    ]);

    $quiz = Quiz::create([
        'course_id' => $course->id, 'title' => 'Timed Quiz', 'passing_score' => 50,
        'time_limit_minutes' => 5, 'is_published' => true,
    ]);

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id, 'question' => '2 + 2?', 'option_a' => '4', 'option_b' => '5',
        'correct_answer' => 'A', 'points' => 1,
    ]);

    $this->actingAs($student)->get(route('student.quiz.take', $quiz))
        ->assertOk()
        ->assertSee('Time remaining')
        ->assertSee('05:00');

    $this->travel(6)->minutes();

    $this->actingAs($student)
        ->post(route('student.quiz.submit', $quiz), ['question_'.$question->id => 'A'])
        ->assertRedirect(route('student.quiz.take', $quiz))
        ->assertSessionHas('error');

    expect(QuizResult::count())->toBe(0);
});
