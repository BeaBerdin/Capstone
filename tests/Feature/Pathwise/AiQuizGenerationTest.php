<?php

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\Client\Request;
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

    $teacher = pathwiseApprovedTeacher();
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
    Http::fake(['*' => Http::response(generatedQuestionsBody($texts))]);
}

/**
 * Gemini's Interactions API answer body with these question texts.
 */
function generatedQuestionsBody(array $texts): array
{
    $questions = array_map(fn (string $text, int $index) => [
        'question' => $text,
        'option_a' => 'Answer A'.$index,
        'option_b' => 'Answer B'.$index,
        'option_c' => 'Answer C'.$index,
        'option_d' => 'Answer D'.$index,
        'correct_answer' => 'B',
    ], $texts, array_keys($texts));

    return [
        'steps' => [[
            'type' => 'model_output',
            'content' => [['type' => 'text', 'text' => json_encode(['questions' => $questions])]],
        ]],
    ];
}

function generateWithQuizRevision($test, Quiz $quiz, int $count)
{
    $revision = $test->get(route('teacher.quiz.builder', $quiz->lesson_id))->assertOk()->viewData('quizRevision');

    return $test->post(route('teacher.quiz.questions.generate', $quiz), [
        'question_count' => $count,
        'difficulty' => 'beginner',
        'revision' => $revision,
    ]);
}

test('generated questions are saved into the quiz for the teacher to review', function () {
    [$teacher, $quiz] = generationQuiz();

    // Two requested; the service asks Gemini for two extra candidates.
    fakeGeneratedQuestions(['What does HTML stand for?', 'Which tag makes a link?', 'Spare one?', 'Spare two?']);

    $this->actingAs($teacher);
    generateWithQuizRevision($this, $quiz, 2)
        ->assertRedirect(route('teacher.quiz.builder', $quiz->lesson_id))
        ->assertSessionHas('success', '2 AI-generated questions were added successfully. The questions are saved. Review and edit them before publishing the quiz.');

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

    $this->actingAs($teacher);
    generateWithQuizRevision($this, $quiz, 1);

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

    $this->actingAs($teacher);
    generateWithQuizRevision($this, $quiz, 1)
        ->assertSessionHas('error', 'Gemini generated questions that already exist in this quiz. No duplicate questions were added.');

    expect(QuizQuestion::where('quiz_id', $quiz->id)->count())->toBe(3);
});

test('a course returned for revision can still generate questions', function () {
    [$teacher, $quiz] = generationQuiz('rejected');

    fakeGeneratedQuestions(['Which tag makes a link?', 'Spare one?', 'Spare two?']);

    $this->actingAs($teacher);
    generateWithQuizRevision($this, $quiz, 1)
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

    $this->actingAs(pathwiseApprovedTeacher())
        ->post(route('teacher.quiz.questions.generate', $quiz), ['question_count' => 1, 'difficulty' => 'beginner'])
        ->assertForbidden();

    Http::assertNothingSent();
});

/**
 * Fake Gemini answering with this raw model text.
 */
function fakeGeminiQuizText(string $text): void
{
    Http::fake(['*' => Http::response([
        'steps' => [[
            'type' => 'model_output',
            'content' => [['type' => 'text', 'text' => $text]],
        ]],
    ])]);
}

test('Gemini is asked for a thinking level gemini-2.5-flash accepts', function () {
    [$teacher, $quiz] = generationQuiz();

    fakeGeneratedQuestions(['What does HTML stand for?', 'Spare one?', 'Spare two?']);

    $this->actingAs($teacher);
    generateWithQuizRevision($this, $quiz, 1)
        ->assertSessionHas('success');

    // gemini-2.5-flash rejects "medium" with a 400; it accepts only low or high.
    Http::assertSent(fn (Request $request) => $request['generation_config']['thinking_level'] === 'low');
});

test('questions wrapped in a Markdown code fence are still saved', function () {
    [$teacher, $quiz] = generationQuiz();

    $questions = array_map(fn (int $i) => [
        'question' => "Fenced question {$i}?",
        'option_a' => "A{$i}", 'option_b' => "B{$i}", 'option_c' => "C{$i}", 'option_d' => "D{$i}",
        'correct_answer' => 'C',
        'points' => 1,
    ], [1, 2, 3]);

    fakeGeminiQuizText("```json\n".json_encode(['questions' => $questions], JSON_PRETTY_PRINT)."\n```");

    $this->actingAs($teacher);
    generateWithQuizRevision($this, $quiz, 1)
        ->assertSessionHas('success');

    expect(QuizQuestion::where('quiz_id', $quiz->id)->pluck('question')->all())->toBe(['Fenced question 1?']);
});

test('questions with the choices under "options" are still saved', function () {
    [$teacher, $quiz] = generationQuiz();

    $questions = array_map(fn (int $i) => [
        'question' => "Options question {$i}?",
        'options' => ['A' => "A{$i}", 'B' => "B{$i}", 'C' => "C{$i}", 'D' => "D{$i}"],
        'correct_answer' => 'D',
        'points' => 1,
    ], [1, 2, 3]);

    fakeGeminiQuizText(json_encode(['questions' => $questions]));

    $this->actingAs($teacher);
    generateWithQuizRevision($this, $quiz, 1)
        ->assertSessionHas('success');

    $saved = QuizQuestion::where('quiz_id', $quiz->id)->first();

    expect($saved->question)->toBe('Options question 1?')
        ->and($saved->option_a)->toBe('A1')
        ->and($saved->option_d)->toBe('D1')
        ->and($saved->correct_answer)->toBe('D');
});

test('a Gemini timeout shows the friendly message and leaves the quiz unchanged', function () {
    [$teacher, $quiz] = generationQuiz();

    Http::fake(['*' => Http::failedConnection('Operation timed out')]);

    $this->actingAs($teacher);
    generateWithQuizRevision($this, $quiz, 2)
        ->assertRedirect(route('teacher.quiz.builder', $quiz->lesson_id))
        ->assertSessionHas('error', 'AI features are unavailable at the moment. Please try again later.');

    expect(QuizQuestion::where('quiz_id', $quiz->id)->count())->toBe(0);
});

test('a temporary Gemini server error is retried within the time budget', function () {
    [$teacher, $quiz] = generationQuiz();

    $questions = array_map(fn (int $i) => [
        'question' => "Retried question {$i}?",
        'option_a' => "A{$i}", 'option_b' => "B{$i}", 'option_c' => "C{$i}", 'option_d' => "D{$i}",
        'correct_answer' => 'A',
        'points' => 1,
    ], [1, 2, 3]);

    Http::fake(['*' => Http::sequence()
        ->push(['error' => ['message' => 'Unavailable']], 503)
        ->push(['steps' => [[
            'type' => 'model_output',
            'content' => [['type' => 'text', 'text' => json_encode(['questions' => $questions])]],
        ]]])]);

    $this->actingAs($teacher);
    generateWithQuizRevision($this, $quiz, 1)
        ->assertSessionHas('success');

    Http::assertSentCount(2);
    expect(QuizQuestion::where('quiz_id', $quiz->id)->pluck('question')->all())->toBe(['Retried question 1?']);
});

test('generation of a new question returns a published quiz to draft and requires explicit republication', function () {
    [$teacher, $quiz] = generationQuiz();
    $quiz->update(['is_published' => true]);
    $original = $quiz->questions()->create([
        'question' => 'Existing question?', 'option_a' => 'A', 'option_b' => 'B',
        'correct_answer' => 'A', 'points' => 1,
    ]);
    fakeGeneratedQuestions(['A new question?', 'Spare one?', 'Spare two?']);
    $this->actingAs($teacher);
    generateWithQuizRevision($this, $quiz, 1)->assertSessionHas('success',
        fn (string $message) => str_contains($message, 'returned to draft'));

    $student = pathwiseUser('student');
    App\Models\Enrollment::create(['course_id' => $quiz->course_id, 'student_id' => $student->id,
        'status' => 'active', 'enrolled_at' => now(), 'progress_percentage' => 0]);
    $quiz->course->update(['status' => 'published']);
    $this->actingAs($student)->get(route('student.quiz.take', $quiz))->assertForbidden();

    $quiz->course->update(['status' => 'draft']);
    $this->actingAs($teacher);
    $revision = $this->get(route('teacher.quiz.builder', $quiz->lesson_id))->assertOk()->viewData('quizRevision');
    $this->post(route('teacher.quiz.save', $quiz->lesson_id), [
        'revision' => $revision, 'intent' => 'publish', 'title' => $quiz->title, 'passing_score' => 75,
        'questions' => $quiz->questions()->get()->keyBy('id')->map->only([
            'question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'points',
        ])->all(),
    ])->assertSessionHas('success', 'Quiz published.');

    expect((bool) $quiz->fresh()->is_published)->toBeTrue()
        ->and($original->fresh()->question)->toBe('Existing question?');
    $quiz->course->update(['status' => 'published']);
    $this->actingAs($student)->get(route('student.quiz.take', $quiz))->assertOk()->assertSee('A new question?');
});

test('generation discards its response when the quiz changes while Gemini is working', function () {
    [$teacher, $quiz] = generationQuiz();
    $this->actingAs($teacher);
    Http::fake(['*' => function () use ($quiz) {
        $quiz->update(['title' => 'A simultaneous edit']);
        $questions = array_map(fn (int $number) => [
            'question' => "Generated question $number?", 'option_a' => 'A', 'option_b' => 'B',
            'option_c' => 'C', 'option_d' => 'D', 'correct_answer' => 'A',
        ], [1, 2, 3]);
        $raw = json_encode(['questions' => $questions]);

        return Http::response(['steps' => [[
            'type' => 'model_output', 'content' => [['type' => 'text', 'text' => $raw]],
        ]]]);
    }]);

    generateWithQuizRevision($this, $quiz, 1)->assertSessionHasErrors('quiz');
    Http::assertSentCount(1);
    expect($quiz->fresh()->title)->toBe('A simultaneous edit')
        ->and($quiz->questions()->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| AI unavailable: one friendly message, details only in the log
|--------------------------------------------------------------------------
*/

test('a Gemini quota or rate-limit error shows the friendly message and logs the details', function () {
    [$teacher, $quiz] = generationQuiz();
    Illuminate\Support\Facades\Log::spy();

    Http::fake(['*' => Http::response(['error' => ['code' => 429, 'status' => 'RESOURCE_EXHAUSTED', 'message' => 'Quota exceeded for metric generate_content_requests']], 429)]);

    $this->actingAs($teacher);
    generateWithQuizRevision($this, $quiz, 2)
        ->assertRedirect(route('teacher.quiz.builder', $quiz->lesson_id))
        ->assertSessionHas('error', 'AI features are unavailable at the moment. Please try again later.');

    expect(QuizQuestion::where('quiz_id', $quiz->id)->count())->toBe(0);

    Illuminate\Support\Facades\Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context) => str_contains($message, 'quiz generation')
            && str_contains($context['reason'] ?? '', '429'))
        ->once();
});

test('the quota details never reach the quiz builder page', function () {
    [$teacher, $quiz] = generationQuiz();

    Http::fake(['*' => Http::response(['error' => ['code' => 429, 'status' => 'RESOURCE_EXHAUSTED', 'message' => 'Quota exceeded for metric generate_content_requests']], 429)]);

    $this->actingAs($teacher);
    generateWithQuizRevision($this, $quiz, 2);

    $this->get(route('teacher.quiz.builder', $quiz->lesson_id))
        ->assertOk()
        ->assertSee('AI features are unavailable at the moment. Please try again later.')
        ->assertDontSee('RESOURCE_EXHAUSTED')
        ->assertDontSee('Quota exceeded')
        ->assertDontSee('Gemini quota');
});

test('a Gemini server error after the retries shows the friendly message', function () {
    [$teacher, $quiz] = generationQuiz();

    Http::fake(['*' => Http::response(['error' => ['message' => 'Internal error']], 503)]);

    $this->actingAs($teacher);
    generateWithQuizRevision($this, $quiz, 2)
        ->assertSessionHas('error', 'AI features are unavailable at the moment. Please try again later.');
});

test('a missing Gemini API key shows the friendly message', function () {
    [$teacher, $quiz] = generationQuiz();
    config(['services.gemini.api_key' => '']);
    Http::fake();

    $this->actingAs($teacher);
    generateWithQuizRevision($this, $quiz, 2)
        ->assertSessionHas('error', 'AI features are unavailable at the moment. Please try again later.');

    Http::assertNothingSent();
});

test('PathWise\'s own per-minute and hourly AI limits show the friendly message', function (string $key, int $hits, string $message) {
    [$teacher, $quiz] = generationQuiz();
    Illuminate\Support\Facades\Log::spy();
    Http::fake();

    foreach (range(1, $hits) as $attempt) {
        Illuminate\Support\Facades\RateLimiter::hit($key, 3600);
    }

    $this->actingAs($teacher);
    generateWithQuizRevision($this, $quiz, 2)
        ->assertRedirect(route('teacher.quiz.builder', $quiz->lesson_id))
        ->assertSessionHas('error', $message);

    Http::assertNothingSent();
    Illuminate\Support\Facades\Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context) => str_contains($context['limit'] ?? '', 'PathWise'))
        ->once();
})->with([
    'per minute' => ['pathwise:gemini:quiz:minute', 4, 'AI features are unavailable at the moment. Please try again later.'],
    'per hour' => ['pathwise:gemini:quiz:hourly', 20, 'AI features are unavailable at the moment. Please try again in about an hour.'],
]);

test('the hourly limit counts successful generations and resets after about an hour', function () {
    [$teacher, $quiz] = generationQuiz();
    config(['services.pathwise.ai_generations_per_hour' => 2]);
    $this->actingAs($teacher);

    Http::fake(['*' => Http::sequence()
        ->push(generatedQuestionsBody(['Hourly A1?', 'Hourly A2?', 'Spare A1?', 'Spare A2?']))
        ->push(generatedQuestionsBody(['Hourly B1?', 'Hourly B2?', 'Spare B1?', 'Spare B2?']))
        ->push(generatedQuestionsBody(['Hourly C1?', 'Hourly C2?', 'Spare C1?', 'Spare C2?']))]);

    // Two successful generations use up this hour (each also counts for the
    // per-minute limit, so move past the minute between them).
    foreach (range(1, 2) as $generation) {
        generateWithQuizRevision($this, $quiz, 2)->assertSessionHas('success');
        $this->travel(2)->minutes();
    }

    generateWithQuizRevision($this, $quiz, 2)
        ->assertSessionHas('error', 'AI features are unavailable at the moment. Please try again in about an hour.');
    Http::assertSentCount(2);

    // About an hour after the first counted generation, it works again.
    $this->travel(60)->minutes();
    generateWithQuizRevision($this, $quiz, 2)->assertSessionHas('success');
    Http::assertSentCount(3);
});

test('a failed generation does not use up the hourly limit', function () {
    [$teacher, $quiz] = generationQuiz();
    config(['services.pathwise.ai_generations_per_hour' => 1]);
    $this->actingAs($teacher);

    Http::fake(['*' => Http::sequence()
        ->push(['error' => ['code' => 429, 'status' => 'RESOURCE_EXHAUSTED']], 429)
        ->push(generatedQuestionsBody(['After failure 1?', 'After failure 2?', 'Spare 1?', 'Spare 2?']))]);

    generateWithQuizRevision($this, $quiz, 2)
        ->assertSessionHas('error', 'AI features are unavailable at the moment. Please try again later.');

    // The failed request did not count: the single hourly generation is still free.
    $this->travel(2)->minutes();
    generateWithQuizRevision($this, $quiz, 2)->assertSessionHas('success');
});

test('the per-minute limit is still 4 by default and both limits are configurable', function () {
    [$teacher, $quiz] = generationQuiz();
    Http::fake();
    $this->actingAs($teacher);

    expect(config('services.pathwise.ai_generations_per_minute'))->toBe(4)
        ->and(config('services.pathwise.ai_generations_per_hour'))->toBe(20);

    foreach (range(1, 3) as $attempt) {
        Illuminate\Support\Facades\RateLimiter::hit('pathwise:gemini:quiz:minute', 60);
    }

    // 3 of 4 used: not blocked yet by the minute limit (Gemini is reached).
    generateWithQuizRevision($this, $quiz, 2);
    Http::assertSentCount(1);

    // The 4th request of the minute is now used: blocked.
    generateWithQuizRevision($this, $quiz, 2)
        ->assertSessionHas('error', 'AI features are unavailable at the moment. Please try again later.');
    Http::assertSentCount(1);

    // A lower configured minute limit applies at once.
    Illuminate\Support\Facades\RateLimiter::clear('pathwise:gemini:quiz:minute');
    config(['services.pathwise.ai_generations_per_minute' => 1]);
    Illuminate\Support\Facades\RateLimiter::hit('pathwise:gemini:quiz:minute', 60);
    generateWithQuizRevision($this, $quiz, 2)
        ->assertSessionHas('error', 'AI features are unavailable at the moment. Please try again later.');
});

/*
|--------------------------------------------------------------------------
| AI generation before the quiz was ever saved
|--------------------------------------------------------------------------
*/

/** A quiz lesson whose quiz has not been saved yet. */
function unsavedQuizLesson(): array
{
    [$teacher, $quiz] = generationQuiz();
    $lesson = $quiz->lesson;
    $quiz->delete();

    return [$teacher, $lesson->fresh()];
}

function firstSavePayload(Illuminate\Testing\TestResponse $builder, array $overrides = []): array
{
    return array_merge([
        'revision' => $builder->viewData('quizRevision'),
        'title' => 'HTML Checkpoint',
        'description' => 'Check what you learned.',
        'passing_score' => 70,
        'time_limit_minutes' => '',
        'question_count' => 2,
        'difficulty' => 'beginner',
    ], $overrides);
}

test('the AI panel is shown before the quiz is first saved', function () {
    [$teacher, $lesson] = unsavedQuizLesson();

    $this->actingAs($teacher)
        ->get(route('teacher.quiz.builder', $lesson))
        ->assertOk()
        ->assertSee('Generate quiz questions')
        ->assertSee('data-test="ai-generate-panel"', false)
        ->assertSee('data-test="ai-first-save-note"', false)
        ->assertSee('form="quiz-edit-form"', false)
        ->assertSee('formaction="'.route('teacher.quiz.save-and-generate', $lesson).'"', false);
});

test('generating before the first save saves the quiz as a draft, then adds the questions', function () {
    [$teacher, $lesson] = unsavedQuizLesson();
    // Two requested; the service asks Gemini for two extra candidates.
    fakeGeneratedQuestions(['First generated question?', 'Second generated question?', 'Spare one?', 'Spare two?']);

    $this->actingAs($teacher);
    $builder = $this->get(route('teacher.quiz.builder', $lesson))->assertOk();

    $this->post(route('teacher.quiz.save-and-generate', $lesson), firstSavePayload($builder))
        ->assertRedirect(route('teacher.quiz.builder', $lesson))
        ->assertSessionHas('success');

    $quiz = Quiz::where('lesson_id', $lesson->id)->sole();

    expect($quiz->title)->toBe('HTML Checkpoint')
        ->and($quiz->description)->toBe('Check what you learned.')
        ->and($quiz->passing_score)->toBe(70)
        ->and((bool) $quiz->is_published)->toBeFalse()
        ->and(QuizQuestion::where('quiz_id', $quiz->id)->pluck('question')->all())
        ->toBe(['First generated question?', 'Second generated question?']);

    // Once saved, the panel uses the normal generate form.
    $this->get(route('teacher.quiz.builder', $lesson))
        ->assertOk()
        ->assertSee('action="'.route('teacher.quiz.questions.generate', $quiz).'"', false)
        ->assertDontSee('data-test="ai-first-save-note"', false);
});

test('the first save uses the same validation as Save Changes and saves nothing when invalid', function (array $overrides, string $field) {
    [$teacher, $lesson] = unsavedQuizLesson();
    Http::fake();

    $this->actingAs($teacher);
    $builder = $this->get(route('teacher.quiz.builder', $lesson))->assertOk();

    $this->post(route('teacher.quiz.save-and-generate', $lesson), firstSavePayload($builder, $overrides))
        ->assertSessionHasErrors($field);

    expect(Quiz::where('lesson_id', $lesson->id)->exists())->toBeFalse();
    Http::assertNothingSent();
})->with([
    'missing title' => [['title' => ''], 'title'],
    'passing score too high' => [['passing_score' => 101], 'passing_score'],
    'bad question count' => [['question_count' => 50], 'question_count'],
    'bad difficulty' => [['difficulty' => 'expert'], 'difficulty'],
]);

test('when AI is unavailable the quiz is still saved as a draft and the friendly message is shown', function () {
    [$teacher, $lesson] = unsavedQuizLesson();
    Http::fake(['*' => Http::response(['error' => ['code' => 429, 'status' => 'RESOURCE_EXHAUSTED']], 429)]);

    $this->actingAs($teacher);
    $builder = $this->get(route('teacher.quiz.builder', $lesson))->assertOk();

    $this->post(route('teacher.quiz.save-and-generate', $lesson), firstSavePayload($builder))
        ->assertRedirect(route('teacher.quiz.builder', $lesson))
        ->assertSessionHas('error', 'AI features are unavailable at the moment. Please try again later.');

    $quiz = Quiz::where('lesson_id', $lesson->id)->sole();

    expect((bool) $quiz->is_published)->toBeFalse()
        ->and($quiz->questions()->count())->toBe(0);
});

test('PathWise\'s own AI limits still apply to the first-save generation', function () {
    [$teacher, $lesson] = unsavedQuizLesson();
    Http::fake();

    foreach (range(1, 4) as $attempt) {
        Illuminate\Support\Facades\RateLimiter::hit('pathwise:gemini:quiz:minute', 60);
    }

    $this->actingAs($teacher);
    $builder = $this->get(route('teacher.quiz.builder', $lesson))->assertOk();

    $this->post(route('teacher.quiz.save-and-generate', $lesson), firstSavePayload($builder))
        ->assertSessionHas('error', 'AI features are unavailable at the moment. Please try again later.');

    Http::assertNothingSent();
});

test('only the course teacher can use the first-save generation', function () {
    [, $lesson] = unsavedQuizLesson();

    $this->actingAs(pathwiseApprovedTeacher())
        ->post(route('teacher.quiz.save-and-generate', $lesson), [
            'revision' => str_repeat('a', 64), 'title' => 'X', 'passing_score' => 70,
            'question_count' => 2, 'difficulty' => 'beginner',
        ])
        ->assertForbidden();

    expect(Quiz::where('lesson_id', $lesson->id)->exists())->toBeFalse();
});

test('the AI panel shows no quota notice or API request badge', function () {
    [$teacher, $quiz] = generationQuiz();

    $this->actingAs($teacher)
        ->get(route('teacher.quiz.builder', $quiz->lesson_id))
        ->assertOk()
        ->assertSee('Generate quiz questions')
        ->assertDontSee('Quota protection')
        ->assertDontSee('AI generation requests per minute')
        ->assertDontSee('1 generation = 1 API request');
});
