<?php

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;

/*
| Submit for Approval refuses a course whose quiz lesson has no quiz, an
| unpublished quiz or a quiz without questions. Quizzes are locked once the
| course is pending or published, so students would otherwise meet a quiz
| that can never be opened.
*/

function quizReadinessCourse(?bool $quizPublished, int $questions = 1): array
{
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher);

    Lesson::create([
        'course_id' => $course->id,
        'title' => 'Reading',
        'content' => 'Lesson content',
        'lesson_type' => 'text',
        'lesson_order' => 1,
        'duration_minutes' => 10,
        'is_preview' => false,
        'is_published' => true,
    ]);

    $quizLesson = Lesson::create([
        'course_id' => $course->id,
        'title' => 'Checkpoint Quiz',
        'content' => null,
        'lesson_type' => 'quiz',
        'lesson_order' => 2,
        'duration_minutes' => 10,
        'is_preview' => false,
        'is_published' => true,
    ]);

    if ($quizPublished !== null) {
        $quiz = Quiz::create([
            'course_id' => $course->id,
            'lesson_id' => $quizLesson->id,
            'title' => 'Checkpoint Quiz',
            'passing_score' => 75,
            'is_published' => $quizPublished,
        ]);

        for ($i = 1; $i <= $questions; $i++) {
            QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => 'Question '.$i,
                'option_a' => 'Right',
                'option_b' => 'Wrong',
                'correct_answer' => 'A',
                'points' => 1,
            ]);
        }
    }

    return [$teacher, $course];
}

test('a course with a published quiz that has questions can be submitted', function () {
    [$teacher, $course] = quizReadinessCourse(quizPublished: true);

    $this->actingAs($teacher)
        ->post(route('teacher.courses.submit', $course))
        ->assertSessionHas('success');

    expect($course->fresh()->status)->toBe('pending');
});

test('a course whose quiz is not ready cannot be submitted', function (?bool $published, int $questions) {
    [$teacher, $course] = quizReadinessCourse($published, $questions);

    $this->actingAs($teacher)
        ->post(route('teacher.courses.submit', $course))
        ->assertSessionHas('error', fn (string $message) => str_contains($message, 'Checkpoint Quiz')
            && str_contains($message, 'Publish Quiz'));

    expect($course->fresh()->status)->toBe('draft');
})->with([
    'unpublished quiz' => [false, 1],
    'published quiz without questions' => [true, 0],
    'quiz lesson without a quiz' => [null, 0],
]);

test('students cannot open an unpublished quiz', function () {
    [, $course] = quizReadinessCourse(quizPublished: false);
    $course->update(['status' => 'published']);
    $student = pathwiseUser('student');
    App\Models\Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    $quiz = Quiz::sole();

    $this->actingAs($student)
        ->get(route('student.quiz.take', $quiz))
        ->assertForbidden();
});
