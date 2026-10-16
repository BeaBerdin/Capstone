<?php

use App\Models\Course;
use App\Models\Quiz;
use App\Services\GeminiRecommendationService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/*
| The Gemini API key must be sent in a header, never in the URL, so it
| cannot leak into log files or error messages.
*/

function fakeQuizWithCourse(): Quiz
{
    $current = (new Course)->forceFill(['title' => 'HTML Basics', 'difficulty_level' => 'beginner']);

    $quiz = (new Quiz)->forceFill(['title' => 'Quiz 1', 'passing_score' => 75]);
    $quiz->setRelation('course', $current);

    return $quiz;
}

test('the recommendation service sends the API key in a header, not the URL', function () {
    config(['services.gemini.api_key' => 'secret-test-key', 'services.gemini.model' => 'gemini-2.5-flash']);

    Http::fake([
        '*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'Great job, continue to CSS.']]]]],
        ]),
    ]);

    $next = (new Course)->forceFill(['title' => 'CSS Basics', 'difficulty_level' => 'beginner']);

    $reason = app(GeminiRecommendationService::class)->generateReason(fakeQuizWithCourse(), $next, 90);

    expect($reason)->toBe('Great job, continue to CSS.');

    Http::assertSent(function (Request $request) {
        return $request->hasHeader('x-goog-api-key', 'secret-test-key')
            && ! str_contains($request->url(), 'secret-test-key');
    });
});

test('the recommendation service returns null when Gemini fails, so a fallback reason is used', function () {
    config(['services.gemini.api_key' => 'secret-test-key']);

    Http::fake(['*' => Http::response(['error' => ['message' => 'quota']], 429)]);

    $next = (new Course)->forceFill(['title' => 'CSS Basics', 'difficulty_level' => 'beginner']);

    expect(app(GeminiRecommendationService::class)->generateReason(fakeQuizWithCourse(), $next, 50))
        ->toBeNull();
});

test('no request is made when the API key is missing', function () {
    config(['services.gemini.api_key' => null]);

    Http::fake();

    $next = (new Course)->forceFill(['title' => 'CSS Basics', 'difficulty_level' => 'beginner']);

    expect(app(GeminiRecommendationService::class)->generateReason(fakeQuizWithCourse(), $next, 50))
        ->toBeNull();

    Http::assertNothingSent();
});
