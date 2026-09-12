<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Quiz;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiRecommendationService
{
    public function generateReason(Quiz $quiz, Course $recommendedCourse, float $percentage): ?string
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-3.6-flash');

        if (!$apiKey) {
            return null;
        }

        $currentCourse = $quiz->course;

        if (!$currentCourse) {
            return null;
        }

        $performanceStatus = $percentage >= $quiz->passing_score
            ? 'PASSED'
            : 'FAILED';

        $prompt = "
You are PathWise AI, an intelligent learning recommendation assistant for an AI-powered e-learning platform.

Analyze the student's quiz performance and generate a personalized learning recommendation.

Student Performance:
- Current Course: {$currentCourse->title}
- Current Course Difficulty: {$currentCourse->difficulty_level}
- Quiz Score: {$percentage}%
- Passing Score: {$quiz->passing_score}%
- Result: {$performanceStatus}

Recommended Course:
- Course Title: {$recommendedCourse->title}
- Course Difficulty: {$recommendedCourse->difficulty_level}

Writing Instructions:
If the student FAILED:
- Explain that the learner may need to strengthen foundational understanding first.
- Explain why the recommended course can help improve the learner's knowledge or skills.
- Use encouraging and supportive language.
- Do not say the learner is ready for advanced mastery.

If the student PASSED:
- Congratulate or acknowledge the student's good performance.
- Explain why the recommended course is a suitable next step.
- Mention the skill or knowledge the student can develop next.
- Motivate the learner to continue progressing.

Output Rules:
- Write only 2 to 3 sentences.
- Use a professional but friendly tone.
- Make the recommendation sound personalized.
- Do not mention Gemini, Google, API, or AI.
- Do not use markdown, bullets, numbering, or quotation marks.
- Return plain text only.
";

        try {
            $response = null;

            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $response = Http::timeout(20)
                    ->withHeaders(['x-goog-api-key' => $apiKey])
                    ->post(
                        "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                        [
                            'contents' => [
                                [
                                    'parts' => [
                                        ['text' => $prompt],
                                    ],
                                ],
                            ],
                        ]
                    );

                if ($response->successful() || $response->status() < 500 || $attempt === 3) {
                    break;
                }

                usleep($attempt * 500000);
            }

            if (!$response->successful()) {
                Log::warning('Gemini recommendation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

            return $text ? trim($text) : null;

        } catch (\Throwable $e) {
            Log::warning('Gemini recommendation exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
