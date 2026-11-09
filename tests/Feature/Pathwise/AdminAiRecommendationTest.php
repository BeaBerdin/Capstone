<?php

use App\Models\AIRecommendation;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizResult;

/*
| Manage AI Recommendations (List of Modules): the Administrator generates a
| recommendation for a student from the student's quiz results, and deletes it.
*/

function completePublishedCourse(string $difficulty, string $title)
{
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'difficulty_level' => $difficulty, 'title' => $title]);
    $lesson = Lesson::create([
        'course_id' => $course->id, 'title' => 'Lesson 1', 'content' => 'Content', 'lesson_type' => 'text',
        'lesson_order' => 1, 'duration_minutes' => 20, 'is_preview' => false, 'is_published' => true,
    ]);
    $quiz = Quiz::create([
        'course_id' => $course->id, 'lesson_id' => $lesson->id, 'title' => 'Quiz', 'passing_score' => 70, 'is_published' => true,
    ]);

    return [$course, $quiz];
}

function studentWithQuizScore(float $percentage)
{
    $student = pathwiseUser('student');
    [, $quiz] = completePublishedCourse('beginner', 'Introduction to Web Development');
    QuizResult::create([
        'student_id' => $student->id, 'quiz_id' => $quiz->id, 'score' => 4, 'total_items' => 5,
        'percentage' => $percentage, 'remarks' => $percentage >= 70 ? 'passed' : 'failed', 'attempt_number' => 1, 'completed_at' => now(),
    ]);

    return $student;
}

test('the administrator can generate a recommendation for a student', function () {
    $student = studentWithQuizScore(80);
    [$intermediate] = completePublishedCourse('intermediate', 'Python Programming Basics');

    $this->actingAs(pathwiseUser('admin'))
        ->post(route('ai-recommendations.store'), ['student_id' => $student->id])
        ->assertSessionHas('success');

    $recommendation = AIRecommendation::where('student_id', $student->id)->firstOrFail();

    expect($recommendation->course_id)->toBe($intermediate->id)
        ->and((float) $recommendation->recommendation_score)->toBe(80.0);
});

test('a recommendation is only generated for a student account', function () {
    $teacher = pathwiseUser('teacher');

    $this->actingAs(pathwiseUser('admin'))
        ->post(route('ai-recommendations.store'), ['student_id' => $teacher->id])
        ->assertSessionHas('error', 'AI recommendations can only be generated for student accounts.');

    expect(AIRecommendation::count())->toBe(0);
});

test('a recommendation needs an existing student', function ($studentId) {
    $this->actingAs(pathwiseUser('admin'))
        ->post(route('ai-recommendations.store'), ['student_id' => $studentId])
        ->assertSessionHasErrors('student_id');

    expect(AIRecommendation::count())->toBe(0);
})->with([null, 999999]);

test('a student without quiz results gets no recommendation', function () {
    $student = pathwiseUser('student');

    $this->actingAs(pathwiseUser('admin'))
        ->post(route('ai-recommendations.store'), ['student_id' => $student->id])
        ->assertSessionHas('error', 'No eligible published quiz results were found for this student.');

    expect(AIRecommendation::count())->toBe(0);
});

test('the administrator can delete a recommendation', function () {
    $student = studentWithQuizScore(80);
    [$course] = completePublishedCourse('intermediate', 'Python Programming Basics');
    $recommendation = AIRecommendation::create([
        'student_id' => $student->id, 'course_id' => $course->id, 'recommendation_score' => 80, 'reason' => 'Next step', 'is_viewed' => false,
    ]);

    $this->actingAs(pathwiseUser('admin'))
        ->from(route('ai-recommendations.index'))
        ->delete(route('ai-recommendations.destroy', $recommendation))
        ->assertSessionHas('success', 'Recommendation deleted successfully.');

    expect(AIRecommendation::find($recommendation->id))->toBeNull();
});

test('only the administrator can generate or delete recommendations', function (string $role) {
    $student = studentWithQuizScore(80);
    [$course] = completePublishedCourse('intermediate', 'Python Programming Basics');
    $recommendation = AIRecommendation::create([
        'student_id' => $student->id, 'course_id' => $course->id, 'recommendation_score' => 80, 'reason' => 'Next step', 'is_viewed' => false,
    ]);
    $user = pathwiseUser($role);

    $this->actingAs($user)->post(route('ai-recommendations.store'), ['student_id' => $student->id])->assertForbidden();
    $this->actingAs($user)->delete(route('ai-recommendations.destroy', $recommendation))->assertForbidden();

    expect(AIRecommendation::count())->toBe(1);
})->with(['teacher', 'student', 'super_admin']);
