<?php

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;

/*
| Create Quiz (List of Modules): the teacher saves the quiz settings and adds
| questions by hand in the Quiz Builder, without Gemini.
*/

function quizLessonFor($teacher, string $status = 'draft'): Lesson
{
    $course = pathwiseCourse($teacher, ['status' => $status]);

    return Lesson::create([
        'course_id' => $course->id,
        'title' => 'Web Basics Quiz',
        'lesson_type' => 'quiz',
        'lesson_order' => 1,
        'duration_minutes' => 15,
        'is_preview' => false,
        'is_published' => true,
    ]);
}

function savedQuizFor(Lesson $lesson): Quiz
{
    return Quiz::create([
        'course_id' => $lesson->course_id,
        'lesson_id' => $lesson->id,
        'title' => 'Web Basics Quiz',
        'passing_score' => 70,
        'time_limit_minutes' => 15,
        'is_published' => true,
    ]);
}

function validQuestion(array $overrides = []): array
{
    return array_merge([
        'question' => 'Which HTML element holds the page title shown in the browser tab?',
        'option_a' => 'body',
        'option_b' => 'title',
        'option_c' => 'h1',
        'option_d' => 'footer',
        'correct_answer' => 'B',
        'points' => 1,
    ], $overrides);
}

test('a teacher can save the quiz settings of a quiz lesson', function () {
    $teacher = pathwiseUser('teacher');
    $lesson = quizLessonFor($teacher);

    $this->actingAs($teacher)
        ->post(route('teacher.quiz.save', $lesson), [
            'title' => 'Web Basics Quiz',
            'description' => 'Five questions about HTML and CSS.',
            'passing_score' => 70,
            'time_limit_minutes' => 15,
            'is_published' => 1,
        ])
        ->assertRedirect(route('teacher.quiz.builder', $lesson))
        ->assertSessionHas('success', 'Quiz settings saved successfully.');

    $quiz = Quiz::where('lesson_id', $lesson->id)->firstOrFail();

    expect($quiz->title)->toBe('Web Basics Quiz')
        ->and($quiz->passing_score)->toBe(70)
        ->and($quiz->time_limit_minutes)->toBe(15)
        ->and((bool) $quiz->is_published)->toBeTrue();
});

test('the quiz settings refuse an invalid passing score', function ($score) {
    $teacher = pathwiseUser('teacher');
    $lesson = quizLessonFor($teacher);

    $this->actingAs($teacher)
        ->post(route('teacher.quiz.save', $lesson), [
            'title' => 'Web Basics Quiz',
            'passing_score' => $score,
            'time_limit_minutes' => 15,
        ])
        ->assertSessionHasErrors('passing_score');

    expect(Quiz::where('lesson_id', $lesson->id)->exists())->toBeFalse();
})->with([0, 101, 'seventy', null]);

test('the quiz settings refuse an invalid time limit', function ($minutes) {
    $teacher = pathwiseUser('teacher');
    $lesson = quizLessonFor($teacher);

    $this->actingAs($teacher)
        ->post(route('teacher.quiz.save', $lesson), [
            'title' => 'Web Basics Quiz',
            'passing_score' => 70,
            'time_limit_minutes' => $minutes,
        ])
        ->assertSessionHasErrors('time_limit_minutes');

    expect(Quiz::where('lesson_id', $lesson->id)->exists())->toBeFalse();
})->with([0, -5, 'fifteen']);

test('the quiz settings require a title', function () {
    $teacher = pathwiseUser('teacher');
    $lesson = quizLessonFor($teacher);

    $this->actingAs($teacher)
        ->post(route('teacher.quiz.save', $lesson), ['title' => '', 'passing_score' => 70])
        ->assertSessionHasErrors('title');
});

test('another teacher cannot save the quiz settings', function () {
    $lesson = quizLessonFor(pathwiseUser('teacher'));

    $this->actingAs(pathwiseUser('teacher'))
        ->post(route('teacher.quiz.save', $lesson), ['title' => 'Web Basics Quiz', 'passing_score' => 70])
        ->assertForbidden();

    expect(Quiz::where('lesson_id', $lesson->id)->exists())->toBeFalse();
});

test('a teacher can add a question by hand', function () {
    $teacher = pathwiseUser('teacher');
    $quiz = savedQuizFor(quizLessonFor($teacher));

    $this->actingAs($teacher)
        ->post(route('teacher.quiz.question.store', $quiz), validQuestion())
        ->assertRedirect(route('teacher.quiz.builder', $quiz->lesson_id))
        ->assertSessionHas('success', 'Question added successfully.');

    $question = QuizQuestion::where('quiz_id', $quiz->id)->firstOrFail();

    expect($question->option_b)->toBe('title')
        ->and($question->correct_answer)->toBe('B')
        ->and($question->points)->toBe(1);
});

test('a question needs options A and B', function (string $missing) {
    $teacher = pathwiseUser('teacher');
    $quiz = savedQuizFor(quizLessonFor($teacher));

    $this->actingAs($teacher)
        ->post(route('teacher.quiz.question.store', $quiz), validQuestion([$missing => '']))
        ->assertSessionHasErrors($missing);

    expect(QuizQuestion::where('quiz_id', $quiz->id)->count())->toBe(0);
})->with(['option_a', 'option_b']);

test('a question needs a correct answer of A, B, C or D', function ($answer) {
    $teacher = pathwiseUser('teacher');
    $quiz = savedQuizFor(quizLessonFor($teacher));

    $this->actingAs($teacher)
        ->post(route('teacher.quiz.question.store', $quiz), validQuestion(['correct_answer' => $answer]))
        ->assertSessionHasErrors('correct_answer');

    expect(QuizQuestion::where('quiz_id', $quiz->id)->count())->toBe(0);
})->with(['', 'E']);

test('the correct answer must point to an option that has text', function () {
    $teacher = pathwiseUser('teacher');
    $quiz = savedQuizFor(quizLessonFor($teacher));

    $this->actingAs($teacher)
        ->post(route('teacher.quiz.question.store', $quiz), validQuestion(['option_d' => '', 'correct_answer' => 'D']))
        ->assertStatus(422);

    expect(QuizQuestion::where('quiz_id', $quiz->id)->count())->toBe(0);
});

test('questions cannot be added while the course is pending approval', function () {
    $teacher = pathwiseUser('teacher');
    $quiz = savedQuizFor(quizLessonFor($teacher, 'pending'));

    $this->actingAs($teacher)
        ->post(route('teacher.quiz.question.store', $quiz), validQuestion())
        ->assertForbidden();

    expect(QuizQuestion::where('quiz_id', $quiz->id)->count())->toBe(0);
});

test('another teacher cannot add a question', function () {
    $quiz = savedQuizFor(quizLessonFor(pathwiseUser('teacher')));

    $this->actingAs(pathwiseUser('teacher'))
        ->post(route('teacher.quiz.question.store', $quiz), validQuestion())
        ->assertForbidden();

    expect(QuizQuestion::where('quiz_id', $quiz->id)->count())->toBe(0);
});
