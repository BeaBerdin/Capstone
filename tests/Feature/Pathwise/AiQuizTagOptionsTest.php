<?php

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\Http;

/*
| A quiz about HTML often has HTML tags as its answer options, for example
| "<h1>" or "<p>". Gemini's questions and options keep such tags as plain
| text; stripping them emptied the options and rejected the whole batch
| ("Gemini returned an empty Option A"). They are always shown escaped.
*/

function tagQuiz(): array
{
    config(['services.gemini.api_key' => 'test-key', 'services.gemini.model' => 'gemini-2.5-flash']);

    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'draft']);

    Lesson::create([
        'course_id' => $course->id, 'title' => 'HTML Headings', 'lesson_type' => 'text', 'lesson_order' => 1,
        'content' => 'Headings use the tags h1 to h6; h1 is the largest.', 'is_preview' => false, 'is_published' => true,
    ]);
    $quizLesson = Lesson::create([
        'course_id' => $course->id, 'title' => 'HTML Quiz', 'lesson_type' => 'quiz', 'lesson_order' => 2,
        'is_preview' => false, 'is_published' => true,
    ]);
    $quiz = Quiz::create(['course_id' => $course->id, 'lesson_id' => $quizLesson->id, 'title' => 'HTML Quiz', 'passing_score' => 75, 'is_published' => false]);

    return [$teacher, $quiz];
}

function fakeTagQuestions(): void
{
    $question = fn (string $text, string $suffix) => [
        'question' => $text,
        'option_a' => '<h1>'.$suffix,
        'option_b' => '<p>'.$suffix,
        'option_c' => '<head>'.$suffix,
        'option_d' => '<header>'.$suffix,
        'correct_answer' => 'A',
    ];

    Http::fake(['*' => Http::response([
        'steps' => [[
            'type' => 'model_output',
            'content' => [['type' => 'text', 'text' => json_encode(['questions' => [
                $question('Which tag creates the largest heading?', ''),
                $question('Which tag starts the main heading of a page?', ' tag'),
                $question('Spare question one?', ' element'),
                $question('Spare question two?', ' block'),
            ]])]],
        ]],
    ])]);
}

test('generated options that are HTML tags are kept as text', function () {
    [$teacher, $quiz] = tagQuiz();
    fakeTagQuestions();

    $this->actingAs($teacher);
    $revision = $this->get(route('teacher.quiz.builder', $quiz->lesson_id))->assertOk()->viewData('quizRevision');
    $this->post(route('teacher.quiz.questions.generate', $quiz), [
        'question_count' => 2, 'difficulty' => 'beginner', 'revision' => $revision,
    ])
        ->assertRedirect(route('teacher.quiz.builder', $quiz->lesson_id))
        ->assertSessionHas('success', '2 AI-generated questions were added successfully. The questions are saved. Review and edit them before publishing the quiz.');

    $first = QuizQuestion::where('quiz_id', $quiz->id)->orderBy('id')->first();

    expect($first->option_a)->toBe('<h1>')
        ->and($first->option_b)->toBe('<p>')
        ->and($first->option_c)->toBe('<head>')
        ->and($first->option_d)->toBe('<header>')
        ->and($first->correct_answer)->toBe('A');
});

test('tag options are shown as text in the Quiz Builder, never as HTML', function () {
    [$teacher, $quiz] = tagQuiz();
    fakeTagQuestions();

    $this->actingAs($teacher);
    $revision = $this->get(route('teacher.quiz.builder', $quiz->lesson_id))->assertOk()->viewData('quizRevision');
    $this->post(route('teacher.quiz.questions.generate', $quiz), [
        'question_count' => 2, 'difficulty' => 'beginner', 'revision' => $revision,
    ]);

    $this->actingAs($teacher)
        ->get(route('teacher.quiz.builder', $quiz->lesson_id))
        ->assertOk()
        ->assertSee('&lt;h1&gt;', false)
        ->assertDontSee('<header> tag', false);
});
