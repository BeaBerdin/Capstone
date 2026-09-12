<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiQuizGenerationService
{
    /**
     * Generate validated multiple-choice question drafts from a course's text lessons.
     * The caller remains responsible for teacher authorization and saving the drafts.
     */
    public function generate(Course $course, int $count, string $difficulty): array
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-3.6-flash');

        if (!$apiKey) {
            throw new RuntimeException('AI quiz generation is not configured yet. Add GEMINI_API_KEY to the environment settings.');
        }

        $source = $this->buildSource($course);

        if ($source === '') {
            throw new RuntimeException('Add text content to at least one non-quiz lesson before generating questions.');
        }

        $prompt = <<<PROMPT
You create assessment drafts for a university e-learning platform. Based ONLY on the course material below, generate exactly {$count} {$difficulty}-level multiple-choice questions.

Return only a valid JSON array. Do not wrap it in markdown. Each item must have this exact shape:
{"question":"...","options":["...","...","...","..."],"correct_answer":"A","points":1}

Rules:
- Each question must be answerable from the supplied material.
- Use exactly four distinct, plausible options.
- correct_answer must be A, B, C, or D and must identify the correct option's position.
- Do not use "all of the above" or "none of the above".
- Keep questions clear and suitable for students.

Course material:
{$source}
PROMPT;

        try {
            $response = null;

            // Gemini can briefly return 503 while a model is under load. Retry only
            // temporary server failures; client-side errors should be surfaced at once.
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $response = Http::timeout(30)
                    ->withHeaders(['x-goog-api-key' => $apiKey])
                    ->post(
                        "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                        ['contents' => [['parts' => [['text' => $prompt]]]]]
                    );

                if ($response->successful() || $response->status() < 500 || $attempt === 3) {
                    break;
                }

                usleep($attempt * 500000);
            }

            if (!$response->successful()) {
                Log::warning('Gemini quiz generation failed', ['status' => $response->status()]);

                if ($response->status() === 429 || $response->status() >= 500) {
                    throw new RuntimeException('The AI service is temporarily busy. Please try again in a moment.');
                }

                throw new RuntimeException('The AI service could not generate questions. Please try again.');
            }

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
            $questions = $this->decodeQuestions($text);

            if (count($questions) !== $count) {
                throw new RuntimeException('The AI returned an incomplete quiz. Please generate it again.');
            }

            return $questions;
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::warning('Gemini quiz generation exception', ['message' => $exception->getMessage()]);
            throw new RuntimeException('The AI service could not generate questions. Please try again.');
        }
    }

    private function buildSource(Course $course): string
    {
        $lessons = $course->lessons()
            ->where('lesson_type', '!=', 'quiz')
            ->where('is_published', true)
            ->orderBy('lesson_order')
            ->get(['title', 'content']);

        $source = $lessons
            ->filter(fn ($lesson) => filled($lesson->content))
            ->map(fn ($lesson) => "Lesson: {$lesson->title}\n{$lesson->content}")
            ->implode("\n\n");

        return mb_substr(strip_tags($source), 0, 12000);
    }

    private function decodeQuestions(mixed $text): array
    {
        if (!is_string($text)) {
            throw new RuntimeException('The AI returned no quiz content. Please try again.');
        }

        $json = preg_replace('/^```(?:json)?\s*|\s*```$/', '', trim($text));
        $items = json_decode($json, true);

        if (!is_array($items)) {
            throw new RuntimeException('The AI returned an invalid quiz format. Please try again.');
        }

        $questions = [];

        foreach ($items as $item) {
            $options = $item['options'] ?? null;
            $answer = strtoupper((string) ($item['correct_answer'] ?? ''));

            if (
                !is_array($options)
                || count($options) !== 4
                || !in_array($answer, ['A', 'B', 'C', 'D'], true)
                || !is_string($item['question'] ?? null)
                || blank($item['question'])
                || collect($options)->contains(fn ($option) => !is_string($option) || blank($option))
                || collect($options)->map(fn ($option) => mb_strtolower(trim($option)))->unique()->count() !== 4
            ) {
                throw new RuntimeException('The AI returned an invalid question. Please generate the quiz again.');
            }

            $questions[] = [
                'question' => trim($item['question']),
                'option_a' => trim($options[0]),
                'option_b' => trim($options[1]),
                'option_c' => trim($options[2]),
                'option_d' => trim($options[3]),
                'correct_answer' => $answer,
                'points' => 1,
            ];
        }

        return $questions;
    }
}
