<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Services\GeminiEvaluationService;
use App\Services\GeminiQuizService;
use App\Services\GeminiRecommendationService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/*
| Manuscript technology stack: Google Gemini API (gemini-2.5-flash).
| When GEMINI_MODEL is missing or empty, every service uses gemini-2.5-flash.
*/

test('config falls back to gemini-2.5-flash', function () {
    $config = file_get_contents(config_path('services.php'));

    expect($config)->toContain("'gemini-2.5-flash'")
        ->not->toContain('gemini-3.8-flash');
});

test('AI evaluation uses gemini-2.5-flash when no model is set', function (?string $model) {
    config(['services.gemini.api_key' => 'test-key', 'services.gemini.model' => $model]);

    Http::fake(['*' => Http::response([
        'output_text' => json_encode(['score' => 80, 'feedback' => 'Good.', 'strengths' => [], 'improvements' => []]),
    ])]);

    app(GeminiEvaluationService::class)->evaluate(
        submissionType: 'essay',
        assignmentTitle: 'Essay',
        assignmentDescription: 'Write an essay.',
        studentAnswer: 'My essay.',
        evaluationCriteria: null,
        maxScore: 100
    );

    Http::assertSent(fn (Request $request) => data_get($request->data(), 'model') === 'gemini-2.5-flash');
})->with(['missing' => [null], 'empty' => ['']]);

test('the recommendation explanation uses gemini-2.5-flash when no model is set', function () {
    config(['services.gemini.api_key' => 'test-key', 'services.gemini.model' => '']);

    Http::fake(['*' => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => 'Try CSS next.']]]]],
    ])]);

    $current = (new Course)->forceFill(['title' => 'HTML Basics', 'difficulty_level' => 'beginner']);
    $quiz = (new Quiz)->forceFill(['title' => 'Quiz 1', 'passing_score' => 75]);
    $quiz->setRelation('course', $current);
    $next = (new Course)->forceFill(['title' => 'CSS Basics', 'difficulty_level' => 'beginner']);

    app(GeminiRecommendationService::class)->generateReason($quiz, $next, 90);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/models/gemini-2.5-flash:generateContent'));
});

test('AI quiz generation uses gemini-2.5-flash when no model is set', function () {
    config(['services.gemini.api_key' => 'test-key', 'services.gemini.model' => '']);

    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'draft']);

    Lesson::create([
        'course_id' => $course->id,
        'title' => 'HTML Intro',
        'content' => 'HTML stands for HyperText Markup Language.',
        'lesson_type' => 'text',
        'lesson_order' => 1,
        'is_preview' => false,
        'is_published' => true,
    ]);

    Http::fake(['*' => Http::response(['output_text' => json_encode(['questions' => []])])]);

    try {
        app(GeminiQuizService::class)->generateQuestions($course, 1, 'easy');
    } catch (Throwable) {
        // The fake answer has no questions; only the request matters here.
    }

    Http::assertSent(fn (Request $request) => data_get($request->data(), 'model') === 'gemini-2.5-flash');
});
