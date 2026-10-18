<?php

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\Http;

/*
| Functional Requirement r and Figure 19 (MR-14): a teacher asks Gemini for
| quiz questions while the course is a Draft (or returned for revision).
| The questions are saved straight into the quiz for the teacher to review;
| questions the quiz already has are skipped; nothing can be generated once
| the course is pending approval or published.
*/

function generationQuiz(string $courseStatus = 'draft'): array
{
    config(['services.gemini.api_key' => 'test-key', 'services.gemini.model' => 'gemini-2.5-flash']);

    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => $courseStatus]);

    // Source material for Gemini: a published Reading lesson with content.
    Lesson::create([
        'course_id' => $course->id,
        'title' => 'HTML Intro',
        'content' => 'HTML stands for HyperText Markup Language.',
        'lesson_type' => 'text',
        'lesson_order' => 1,
        'is_preview' => false,
        'is_published' => true,
    ]);

    $quizLesson = Lesson::create([
        'course_id' => $course->id,
        'title' => 'HTML Checkpoint',
        'lesson_type' => 'quiz',
        'lesson_order' => 2,
        'is_preview' => false,
        'is_published' => true,
    ]);

    $quiz = Quiz::create([
        'course_id' => $course->id,
        'lesson_id' => $quizLesson->id,
        'title' => 'HTML Checkpoint',
        'passing_score' => 75,
        'is_published' => false,
    ]);

    return [$teacher, $quiz];
}

/**
 * Fake Gemini's Interactions API answer with these question texts.
 */
function fakeGeneratedQuestions(array $texts): void
{
    $questions = array_map(fn (string $text, int $index) => [
        'question' => $text,
        'option_a' => 'Answer A'.$index,
        'option_b' => 'Answer B'.$index,
        'option_c' => 'Answer C'.$index,
        'option_d' => 'Answer D'.$index,
        'correct_answer' => 'B',
    ], $texts, array_keys($texts));

    Http::fake(['*' => Http::response([
        'steps' => [[
            'type' => 'model_output',
            'content' => [['type' => 'text', 'text' => json_encode(['questions' => $questions])]],
        ]],
    ])]);
}

test('generated questions are saved into the quiz for the teacher to review', function () {
    [$teacher, $quiz] = generationQuiz();

    // Two requested; the service asks Gemini for two extra candidates.
    fakeGeneratedQuestions(['What does HTML stand for?', 'Which tag makes a link?', 'Spare one?', 'Spare two?']);

    $this->actingAs($teacher)
        ->post(route('teacher.quiz.questions.generate', $quiz), ['question_count' => 2, 'difficulty' => 'beginner'])
        ->assertRedirect(route('teacher.quiz.builder', $quiz->lesson_id))
        ->assertSessionHas('success', '2 AI-generated questions were added successfully. Review and edit them before publishing the quiz.');

    $saved = QuizQuestion::where('quiz_id', $quiz->id)->orderBy('id')->get();

    expect($saved->pluck('question')->all())->toBe(['What does HTML stand for?', 'Which tag makes a link?'])
        ->and($saved->first()->correct_answer)->toBe('B')
        ->and($saved->first()->points)->toBe(1)
        ->and((bool) $quiz->fresh()->is_published)->toBeFalse();   // still unpublished, for the teacher to review
});

test('questions the quiz already has are skipped', function () {
    [$teacher, $quiz] = generationQuiz();

    QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'question' => 'What does HTML stand for?',
        'option_a' => 'HyperText Markup Language',
        'option_b' => 'Other',
        'correct_answer' => 'A',
        'points' => 1,
    ]);

    // Same question with different spacing and case, then new ones.
    fakeGeneratedQuestions(['  what does   HTML stand for? ', 'Which tag makes a link?', 'Which tag adds an image?']);

    $this->actingAs($teacher)
        ->post(route('teacher.quiz.questions.generate', $quiz), ['question_count' => 1, 'difficulty' => 'beginner']);

    expect(QuizQuestion::where('quiz_id', $quiz->id)->orderBy('id')->pluck('question')->all())
        ->toBe(['What does HTML stand for?', 'Which tag makes a link?']);
});

test('nothing is added when every generated question is a duplicate', function () {
    [$teacher, $quiz] = generationQuiz();

    foreach (['Question one?', 'Question two?', 'Question three?'] as $text) {
        QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question' => $text,
            'option_a' => 'A',
            'option_b' => 'B',
            'correct_answer' => 'A',
            'points' => 1,
        ]);
    }

    fakeGeneratedQuestions(['Question one?', 'Question two?', 'Question three?']);

    $this->actingAs($teacher)
        ->post(route('teacher.quiz.questions.generate', $quiz), ['question_count' => 1, 'difficulty' => 'beginner'])
        ->assertSessionHas('error', 'Gemini generated questions that already exist in this quiz. No duplicate questions were added.');

    expect(QuizQuestion::where('quiz_id', $quiz->id)->count())->toBe(3);
});

test('a course returned for revision can still generate questions', function () {
    [$teacher, $quiz] = generationQuiz('rejected');

    fakeGeneratedQuestions(['Which tag makes a link?', 'Spare one?', 'Spare two?']);

    $this->actingAs($teacher)
        ->post(route('teacher.quiz.questions.generate', $quiz), ['question_count' => 1, 'difficulty' => 'beginner'])
        ->assertSessionHas('success');

    expect(QuizQuestion::where('quiz_id', $quiz->id)->count())->toBe(1);
});

test('generation is locked once the course is pending approval or published', function (string $status) {
    [$teacher, $quiz] = generationQuiz($status);

    fakeGeneratedQuestions(['Which tag makes a link?', 'Spare one?', 'Spare two?']);

    $this->actingAs($teacher)
        ->post(route('teacher.quiz.questions.generate', $quiz), ['question_count' => 1, 'difficulty' => 'beginner'])
        ->assertForbidden();

    expect(QuizQuestion::where('quiz_id', $quiz->id)->count())->toBe(0);

    Http::assertNothingSent();
})->with(['pending', 'published']);

test('another teacher cannot generate questions for the quiz', function () {
    [, $quiz] = generationQuiz();

    fakeGeneratedQuestions(['Which tag makes a link?', 'Spare one?', 'Spare two?']);

    $this->actingAs(pathwiseUser('teacher'))
        ->post(route('teacher.quiz.questions.generate', $quiz), ['question_count' => 1, 'difficulty' => 'beginner'])
        ->assertForbidden();

    Http::assertNothingSent();
});
