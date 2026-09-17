<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiQuizService
{
    private const DEFAULT_MODEL = 'gemini-3.8-flash';

    private const DEFAULT_ENDPOINT =
        'https://generativelanguage.googleapis.com/v1beta/interactions';

    private const MAX_SOURCE_CHARACTERS = 50000;

    private const MAX_QUESTIONS = 10;

    /**
     * Generate multiple-choice questions using only published
     * Reading/Text lessons from the selected course.
     *
     * @return array<int, array{
     *     question: string,
     *     option_a: string,
     *     option_b: string,
     *     option_c: string,
     *     option_d: string,
     *     correct_answer: string,
     *     points: int
     * }>
     */
    public function generateQuestions(
        Course $course,
        int $count,
        string $difficulty
    ): array {
        $apiKey = trim(
            (string) config('services.gemini.api_key')
        );

        if ($apiKey === '') {
            throw new RuntimeException(
                'Gemini API key is not configured.'
            );
        }

        $count = max(
            1,
            min(
                self::MAX_QUESTIONS,
                $count
            )
        );

        $difficulty =
            $this->normalizeDifficulty(
                $difficulty
            );

        $sourceMaterial =
            $this->buildCourseSourceMaterial(
                $course
            );

        if ($sourceMaterial === '') {
            throw new RuntimeException(
                'Add published Reading lessons with content before generating AI questions.'
            );
        }

        $payload = [
            'model' =>
                (string) config(
                    'services.gemini.model',
                    self::DEFAULT_MODEL
                ),

            'system_instruction' =>
                $this->systemInstruction(),

            'input' =>
                $this->buildPrompt(
                    $course,
                    $count,
                    $difficulty,
                    $sourceMaterial
                ),

            'generation_config' => [
                'thinking_level' => 'medium',
                'max_output_tokens' =>
                    $this->maxOutputTokens(
                        $count
                    ),
            ],

            'response_format' =>
                $this->responseFormat(
                    $count
                ),

            // We do not need Google to retain quiz-generation
            // interactions for this application workflow.
            'store' => false,
        ];

        $response =
            $this->sendRequest(
                $apiKey,
                $payload
            );

        $jsonText =
            $this->extractModelText(
                $response
            );

        $decoded =
            json_decode(
                $jsonText,
                true
            );

        if (!is_array($decoded)) {
            throw new RuntimeException(
                'Gemini returned an invalid quiz response.'
            );
        }

        $questions =
            $decoded['questions']
            ?? null;

        if (!is_array($questions)) {
            throw new RuntimeException(
                'Gemini did not return quiz questions.'
            );
        }

        return $this->validateAndNormalizeQuestions(
            $questions,
            $count
        );
    }


    /**
     * Build clean source material from published Reading lessons only.
     */
    private function buildCourseSourceMaterial(
        Course $course
    ): string {
        $lessons =
            $course
                ->lessons()
                ->where(
                    'lesson_type',
                    'text'
                )
                ->where(
                    'is_published',
                    true
                )
                ->whereNotNull(
                    'content'
                )
                ->orderBy(
                    'lesson_order'
                )
                ->get([
                    'id',
                    'title',
                    'content',
                    'lesson_order',
                ]);

        $chunks = [];

        foreach ($lessons as $lesson) {
            $content =
                $this->cleanText(
                    (string) $lesson->content
                );

            if ($content === '') {
                continue;
            }

            $chunks[] =
                "LESSON {$lesson->lesson_order}: "
                .
                $this->cleanText(
                    (string) $lesson->title
                )
                .
                "\n"
                .
                $content;
        }

        $source =
            implode(
                "\n\n---\n\n",
                $chunks
            );

        if (
            mb_strlen($source)
            >
            self::MAX_SOURCE_CHARACTERS
        ) {
            $source =
                mb_substr(
                    $source,
                    0,
                    self::MAX_SOURCE_CHARACTERS
                );
        }

        return trim($source);
    }


    /**
     * System-level quality and grounding rules.
     */
    private function systemInstruction(): string
    {
        return <<<'PROMPT'
You are PathWise's assessment-generation engine.

Your task is to create high-quality multiple-choice quiz questions for a teacher.

NON-NEGOTIABLE RULES:
1. Use ONLY facts, concepts, definitions, examples, and relationships explicitly supported by the supplied course lesson content.
2. Do not use outside knowledge, web knowledge, assumptions, or invented facts.
3. Every question must have exactly four non-empty answer choices: A, B, C, and D.
4. Exactly one answer must be clearly correct.
5. Wrong choices must be plausible and related to the same topic, but clearly false based on the supplied lesson content.
6. Do not create trick questions, opinion questions, ambiguous questions, or "all of the above"/"none of the above" choices.
7. Do not duplicate or paraphrase the same question more than once.
8. Avoid answer-position patterns. Do not always make A the correct answer.
9. Keep each question understandable without needing to see another question.
10. Do not mention that the question was generated by AI.
11. Output only data that conforms to the required JSON schema.
12. Every question must be fully standalone and natural.
13. Never use phrases such as "According to the lesson material", "Based on the lesson", "From the reading", "According to the course", or similar references to source material.
14. Ask the question directly as if it were written by a teacher for an actual assessment.
15. Do not reveal or mention that the answer comes from provided lesson content.
PROMPT;
    }


    /**
     * Teacher-requested generation prompt.
     */
    private function buildPrompt(
        Course $course,
        int $count,
        string $difficulty,
        string $sourceMaterial
    ): string {
        $difficultyInstruction =
            match ($difficulty) {
                'beginner' =>
                    'Focus on direct understanding, definitions, and clearly stated concepts.',

                'intermediate' =>
                    'Mix understanding with simple application and relationships between concepts explicitly present in the lessons.',

                'advanced' =>
                    'Use deeper analysis and application of relationships explicitly supported by the lessons, while avoiding outside knowledge.',

                default =>
                    'Focus on direct understanding and clearly supported concepts.',
            };

        $courseTitle =
            $this->cleanText(
                (string) $course->title
            );

        return <<<PROMPT
Generate exactly {$count} multiple-choice quiz questions.

COURSE:
{$courseTitle}

DIFFICULTY:
{$difficulty}

DIFFICULTY GUIDANCE:
{$difficultyInstruction}

QUALITY REQUIREMENTS:
- Cover different important concepts when the lesson material allows it.
- Do not create duplicate questions.
- Exactly one answer must be correct for each question.
- All four options must be distinct.
- Keep distractors believable and in the same conceptual category as the correct answer.
- Base every question strictly on the lesson material below.
- Set points to 1 for every generated question.
- Write every question as a direct, standalone assessment question.
- Never begin a question with "According to", "Based on", "From the lesson", "From the reading", or similar source-referencing phrases.
- The student should not need to know that lesson content was supplied to an AI.

COURSE LESSON MATERIAL:
<<<SOURCE
{$sourceMaterial}
SOURCE

Return exactly {$count} questions that satisfy the required JSON schema.
PROMPT;
    }


    /**
     * Gemini Interactions API structured-output schema.
     */
    private function responseFormat(
        int $count
    ): array {
        return [
            'type' => 'text',
            'mime_type' => 'application/json',
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,

                'properties' => [
                    'questions' => [
                        'type' => 'array',
                        'minItems' => $count,
                        'maxItems' => $count,

                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,

                            'properties' => [
                                'question' => [
                                    'type' => 'string',
                                    'description' =>
                                        'A clear standalone multiple-choice question grounded only in the provided lesson material.',
                                ],

                                'option_a' => [
                                    'type' => 'string',
                                ],

                                'option_b' => [
                                    'type' => 'string',
                                ],

                                'option_c' => [
                                    'type' => 'string',
                                ],

                                'option_d' => [
                                    'type' => 'string',
                                ],

                                'correct_answer' => [
                                    'type' => 'string',
                                    'enum' => [
                                        'A',
                                        'B',
                                        'C',
                                        'D',
                                    ],
                                ],

                                'points' => [
                                    'type' => 'integer',
                                    'enum' => [
                                        1,
                                    ],
                                ],
                            ],

                            'required' => [
                                'question',
                                'option_a',
                                'option_b',
                                'option_c',
                                'option_d',
                                'correct_answer',
                                'points',
                            ],
                        ],
                    ],
                ],

                'required' => [
                    'questions',
                ],
            ],
        ];
    }


    /**
     * Send the request with controlled retry handling for
     * throttling and temporary server failures.
     */
    private function sendRequest(
        string $apiKey,
        array $payload
    ): Response {
        $endpoint =
            (string) config(
                'services.gemini.endpoint',
                self::DEFAULT_ENDPOINT
            );

        $lastResponse = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $response =
                Http::withHeaders([
                    'x-goog-api-key' =>
                        $apiKey,

                    'Content-Type' =>
                        'application/json',
                ])
                    ->acceptJson()
                    ->timeout(60)
                    ->connectTimeout(15)
                    ->post(
                        $endpoint,
                        $payload
                    );

            $lastResponse =
                $response;

            if ($response->successful()) {
                return $response;
            }

            if (
                !$this->shouldRetry(
                    $response
                )
                ||
                $attempt === 3
            ) {
                break;
            }

            usleep(
                500000
                *
                $attempt
            );
        }

        $status =
            $lastResponse?->status()
            ?? 0;

        $message =
            $this->extractApiErrorMessage(
                $lastResponse
            );

        throw new RuntimeException(
            $message !== ''
                ? "Gemini API error ({$status}): {$message}"
                : "Gemini API request failed ({$status})."
        );
    }


   private function shouldRetry(
    Response $response
): bool {
    // Never automatically retry rate-limit errors.
    // One teacher action should equal one Gemini request.
    return $response->serverError();
}


    private function extractApiErrorMessage(
        ?Response $response
    ): string {
        if (!$response) {
            return '';
        }

        $message =
            $response->json(
                'error.message'
            );

        if (is_string($message)) {
            return trim($message);
        }

        return '';
    }


    /**
     * REST Interactions responses expose model text inside
     * model_output steps.
     */
    private function extractModelText(
        Response $response
    ): string {
        $steps =
            $response->json(
                'steps',
                []
            );

        if (!is_array($steps)) {
            throw new RuntimeException(
                'Gemini returned an unexpected response format.'
            );
        }

        $textParts = [];

        foreach ($steps as $step) {
            if (
                !is_array($step)
                ||
                ($step['type'] ?? null)
                !==
                'model_output'
            ) {
                continue;
            }

            $content =
                $step['content']
                ?? [];

            if (!is_array($content)) {
                continue;
            }

            foreach ($content as $block) {
                if (
                    !is_array($block)
                    ||
                    ($block['type'] ?? null)
                    !==
                    'text'
                ) {
                    continue;
                }

                $text =
                    $block['text']
                    ?? null;

                if (
                    is_string($text)
                    &&
                    trim($text) !== ''
                ) {
                    $textParts[] =
                        trim($text);
                }
            }
        }

        $output =
            trim(
                implode(
                    "\n",
                    $textParts
                )
            );

        if ($output === '') {
            throw new RuntimeException(
                'Gemini returned no quiz content.'
            );
        }

        return $output;
    }


    /**
     * Application-level validation remains necessary even when
     * structured output is enabled.
     */
    private function validateAndNormalizeQuestions(
        array $questions,
        int $expectedCount
    ): array {
        if (
            count($questions)
            !==
            $expectedCount
        ) {
            throw new RuntimeException(
                'Gemini returned an unexpected number of questions.'
            );
        }

        $normalized = [];
        $seenQuestions = [];

        foreach ($questions as $index => $question) {
            if (!is_array($question)) {
                throw new RuntimeException(
                    'Gemini returned an invalid question.'
                );
            }

            $questionText =
                $this->cleanText(
                    (string) (
                        $question['question']
                        ?? ''
                    )
                );

            $optionA =
                $this->cleanText(
                    (string) (
                        $question['option_a']
                        ?? ''
                    )
                );

            $optionB =
                $this->cleanText(
                    (string) (
                        $question['option_b']
                        ?? ''
                    )
                );

            $optionC =
                $this->cleanText(
                    (string) (
                        $question['option_c']
                        ?? ''
                    )
                );

            $optionD =
                $this->cleanText(
                    (string) (
                        $question['option_d']
                        ?? ''
                    )
                );

            $correctAnswer =
                strtoupper(
                    trim(
                        (string) (
                            $question['correct_answer']
                            ?? ''
                        )
                    )
                );

            if ($questionText === '') {
                throw new RuntimeException(
                    'Gemini returned an empty question.'
                );
            }

            $options = [
                'A' => $optionA,
                'B' => $optionB,
                'C' => $optionC,
                'D' => $optionD,
            ];

            foreach ($options as $letter => $option) {
                if ($option === '') {
                    throw new RuntimeException(
                        "Gemini returned an empty Option {$letter}."
                    );
                }
            }

            $normalizedOptions =
                array_map(
                    fn (string $option) =>
                        mb_strtolower(
                            trim($option)
                        ),
                    array_values(
                        $options
                    )
                );

            if (
                count(
                    array_unique(
                        $normalizedOptions
                    )
                )
                !==
                4
            ) {
                throw new RuntimeException(
                    'Gemini returned duplicate answer choices.'
                );
            }

            if (
                !array_key_exists(
                    $correctAnswer,
                    $options
                )
            ) {
                throw new RuntimeException(
                    'Gemini returned an invalid correct answer.'
                );
            }

            $questionKey =
                mb_strtolower(
                    preg_replace(
                        '/\s+/u',
                        ' ',
                        $questionText
                    )
                    ?? $questionText
                );

            if (
                isset(
                    $seenQuestions[$questionKey]
                )
            ) {
                throw new RuntimeException(
                    'Gemini returned duplicate questions.'
                );
            }

            $seenQuestions[$questionKey] =
                true;

            $normalized[] = [
                'question' =>
                    $questionText,

                'option_a' =>
                    $optionA,

                'option_b' =>
                    $optionB,

                'option_c' =>
                    $optionC,

                'option_d' =>
                    $optionD,

                'correct_answer' =>
                    $correctAnswer,

                'points' =>
                    1,
            ];
        }

        return $normalized;
    }


    private function normalizeDifficulty(
        string $difficulty
    ): string {
        $difficulty =
            strtolower(
                trim($difficulty)
            );

        return in_array(
            $difficulty,
            [
                'beginner',
                'intermediate',
                'advanced',
            ],
            true
        )
            ? $difficulty
            : 'beginner';
    }


    private function maxOutputTokens(
        int $count
    ): int {
        return max(
            2000,
            min(
                8000,
                800
                *
                $count
            )
        );
    }


    private function cleanText(
        string $text
    ): string {
        $text =
            html_entity_decode(
                strip_tags($text),
                ENT_QUOTES
                |
                ENT_HTML5,
                'UTF-8'
            );

        $text =
            preg_replace(
                '/[ \t]+/u',
                ' ',
                $text
            )
            ?? $text;

        $text =
            preg_replace(
                "/\r\n|\r/u",
                "\n",
                $text
            )
            ?? $text;

        $text =
            preg_replace(
                "/\n{3,}/u",
                "\n\n",
                $text
            )
            ?? $text;

        return trim($text);
    }
}
