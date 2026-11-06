<?php

namespace App\Services;

use App\Exceptions\AiEvaluationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiEvaluationService
{
    /**
     * @return array<string, mixed>
     */
    public function evaluate(
        string $submissionType,
        string $assignmentTitle,
        string $assignmentDescription,
        string $studentAnswer,
        ?string $evaluationCriteria,
        int $maxScore,
        ?string $codeLanguage = null
    ): array {
        $apiKey = config('services.gemini.api_key');

        // gemini-2.5-flash (Manuscript) when GEMINI_MODEL is missing or empty.
        $model = config('services.gemini.model') ?: 'gemini-2.5-flash';

        $endpoint = config(
            'services.gemini.endpoint',
            'https://generativelanguage.googleapis.com/v1beta/interactions'
        );

        if (empty($apiKey)) {
            throw new AiEvaluationException(
                'Gemini API key is not configured.'
            );
        }

        if (trim($studentAnswer) === '') {
            throw new AiEvaluationException(
                'The student submission is empty.'
            );
        }

        $prompt = $this->buildPrompt(
            $submissionType,
            $assignmentTitle,
            $assignmentDescription,
            $studentAnswer,
            $evaluationCriteria,
            $maxScore,
            $codeLanguage
        );

        $schema = [
            'type' => 'object',

            'properties' => [

                'score' => [
                    'type' => 'integer',
                    'description' =>
                        'Suggested score from 0 to the maximum score.'
                ],

                'feedback' => [
                    'type' => 'string',
                    'description' =>
                        'Constructive feedback for the student.'
                ],

                'strengths' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string'
                    ]
                ],

                'improvements' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string'
                    ]
                ],

                'criteria_analysis' => [
                    'type' => 'string',
                    'description' =>
                        'Brief explanation of how the submission meets the evaluation criteria.'
                ],

                'code_quality' => [
                    'type' => 'string',
                    'description' =>
                        'Assessment of code quality, readability, structure, and maintainability. Empty for non-code submissions.'
                ],

                'efficiency' => [
                    'type' => 'string',
                    'description' =>
                        'Assessment of algorithmic and runtime efficiency. Empty for non-code submissions.'
                ],
            ],

            'required' => [
                'score',
                'feedback',
                'strengths',
                'improvements',
                'criteria_analysis',
                'code_quality',
                'efficiency',
            ],
        ];

        $payload = [
            'model' => $model,

            'input' => $prompt,

            'response_format' => [
                'type' => 'text',
                'mime_type' => 'application/json',
                'schema' => $schema,
            ],

            'generation_config' => [
                'temperature' => 0.2,
                // Thinking tokens count toward this limit; 2000 cut long evaluations off.
                'thinking_level' => 'low',
                'max_output_tokens' => 8000,
            ],
        ];

        try {
            $response = Http::timeout(90)
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, $payload);

            if ($response->failed()) {
                Log::error(
                    'Gemini evaluation API request failed.',
                    [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]
                );

                throw new AiEvaluationException(
                    'Gemini API request failed: HTTP ' .
                    $response->status()
                );
            }

            $data = $response->json();

            Log::info(
                'Gemini evaluation response received.',
                [
                    'status' => $data['status'] ?? null,

                    'model' => $data['model'] ?? null,

                    'has_output_text' =>
                        !empty($data['output_text']),

                    'steps_count' =>
                        isset($data['steps'])
                            ? count($data['steps'])
                            : 0,
                ]
            );

            $outputText = $this->extractOutputText(
                $data
            );

            if (trim($outputText) === '') {
                Log::error(
                    'Gemini returned no text output for evaluation.',
                    [
                        'response' => $data,
                    ]
                );

                throw new AiEvaluationException(
                    'Gemini returned an empty evaluation response.'
                );
            }

            $result = $this->parseJsonResponse(
                $outputText
            );

            return $this->normalizeResult(
                $result,
                $maxScore
            );

        } catch (\Throwable $e) {

            Log::error(
                'Gemini evaluation failed.',
                [
                    'message' =>
                        $e->getMessage(),

                    'assignment_title' =>
                        $assignmentTitle,
                ]
            );

            throw $e;
        }
    }

    private function buildPrompt(
        string $submissionType,
        string $assignmentTitle,
        string $assignmentDescription,
        string $studentAnswer,
        ?string $evaluationCriteria,
        int $maxScore,
        ?string $codeLanguage
    ): string {

        $criteria =
            $evaluationCriteria
            ?: 'No specific rubric was provided.';

        $prompt = <<<PROMPT
You are an AI-assisted evaluator for an e-learning platform called PATHWISE.

Your role is to provide a SUGGESTED evaluation that will be reviewed by a teacher.

IMPORTANT:
The AI does NOT make the final grading decision.

The teacher remains responsible for the final grade.

ASSIGNMENT

Title:
{$assignmentTitle}

Description:
{$assignmentDescription}

Submission Type:
{$submissionType}

Maximum Score:
{$maxScore}

Evaluation Criteria:
{$criteria}

STUDENT SUBMISSION

{$studentAnswer}

GENERAL EVALUATION INSTRUCTIONS

Evaluate the student's submission using:

1. Accuracy
2. Relevance to the assignment
3. Completeness
4. Understanding of the topic
5. The teacher-defined evaluation criteria

Provide:

- A suggested numeric score from 0 to {$maxScore}
- Constructive feedback
- Specific strengths
- Specific improvements
- A brief criteria analysis

PROMPT;

if ($submissionType === 'code') {

            $codeLanguage =
                $codeLanguage
                ?: 'Unknown';

            $prompt .= <<<PROMPT


CODE EVALUATION

Programming Language:
{$codeLanguage}

CODE-SPECIFIC EVALUATION

Evaluate all of the following:

1. Functional correctness
2. Whether the program satisfies the assignment
3. Correctness of the programming language syntax
4. Code readability
5. Code organization and structure
6. Appropriate use of variables, expressions, functions, or other language features
7. Basic efficiency
8. Potential improvements

IMPORTANT CODE RULES

- Do not require advanced optimization for a simple beginner
  programming assignment.

- Judge efficiency relative to the assignment difficulty.

- Do not claim that the code is perfectly optimal.

- Do not give a high score solely because the code is syntactically
  valid.

- The assignment requirements and evaluation criteria should have
  greater importance than stylistic preferences.

CODE QUALITY

Explain whether the code is:

- Clear
- Readable
- Properly structured
- Easy to understand
- Appropriate for the student's apparent level

EFFICIENCY

Explain whether the solution uses reasonable:

- Time complexity
- Space usage
- Operations
- Algorithmic approach

For simple programs, keep the efficiency assessment proportional
to the simplicity of the task.

PROMPT;
        }

        $prompt .= <<<PROMPT


IMPORTANT OUTPUT RULES

Return ONLY valid JSON matching the requested schema, with exactly these keys:

{"score": 0, "feedback": "...", "strengths": ["..."], "improvements": ["..."], "criteria_analysis": "...", "code_quality": "...", "efficiency": "..."}

Do not include Markdown.

Do not include code fences.

Do not include explanations outside the JSON.

The "score" must be an integer between 0 and {$maxScore}.

Remember:

The score is an AI SUGGESTION only.

The teacher will review the AI evaluation and determine the final grade.

PROMPT;

        return $prompt;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function extractOutputText(
        array $data
    ): string {

        if (
            isset($data['output_text'])
            && is_string($data['output_text'])
        ) {
            return trim(
                $data['output_text']
            );
        }

        if (
            isset($data['steps'])
            && is_array($data['steps'])
        ) {

            $parts = [];

            foreach ($data['steps'] as $step) {

                if (
                    !isset($step['content'])
                    || !is_array($step['content'])
                ) {
                    continue;
                }

                foreach ($step['content'] as $content) {

                    if (
                        isset($content['type'])
                        && $content['type'] === 'text'
                        && isset($content['text'])
                        && is_string($content['text'])
                    ) {
                        $parts[] =
                            $content['text'];
                    }
                }
            }

            if (!empty($parts)) {
                return trim(
                    implode("\n", $parts)
                );
            }
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonResponse(
        string $outputText
    ): array {

        $clean = trim(
            $outputText
        );

        /*
         * Remove accidental Markdown
         * code fences.
         */
        $clean = preg_replace(
            '/^```(?:json)?\s*/i',
            '',
            $clean
        );

        $clean = preg_replace(
            '/\s*```$/',
            '',
            $clean
        );

        $clean = trim(
            $clean
        );

        $decoded = json_decode(
            $clean,
            true
        );

        if (!is_array($decoded)) {

            Log::error(
                'Gemini evaluation returned invalid JSON.',
                [
                    'output' =>
                        $outputText,
                ]
            );

            throw new AiEvaluationException(
                'Gemini returned an invalid evaluation format.'
            );
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function normalizeResult(
        array $result,
        int $maxScore
    ): array {
        // A result without a numeric score or without feedback is not an
        // evaluation; never store it as one.
        if (
            ! isset($result['score'])
            || ! is_numeric($result['score'])
            || trim((string) ($result['feedback'] ?? '')) === ''
        ) {
            Log::error(
                'Gemini evaluation is missing a score or feedback.',
                [
                    'keys' => array_keys($result),
                ]
            );

            throw new AiEvaluationException(
                'Gemini returned an incomplete evaluation (no score or feedback).'
            );
        }

        $score = (int) $result['score'];

        $score = max(
            0,
            min(
                $maxScore,
                $score
            )
        );

        $feedback = trim(
            (string) (
                $result['feedback']
                ?? ''
            )
        );

        $strengths =
            $this->normalizeList(
                $result['strengths']
                ?? []
            );

        $improvements =
            $this->normalizeList(
                $result['improvements']
                ?? []
            );

        return [
            'score' => $score,

            'feedback' =>
                $feedback,

            'strengths' =>
                $strengths,

            'improvements' =>
                $improvements,

            'criteria_analysis' =>
                trim(
                    (string) (
                        $result['criteria_analysis']
                        ?? ''
                    )
                ),

            'code_quality' =>
                trim(
                    (string) (
                        $result['code_quality']
                        ?? ''
                    )
                ),

            'efficiency' =>
                trim(
                    (string) (
                        $result['efficiency']
                        ?? ''
                    )
                ),
        ];
    }

    /**
     * @return list<string>
     */
    private function normalizeList(
        mixed $value
    ): array {

        if (is_array($value)) {

            return array_values(
                array_filter(
                    array_map(
                        fn ($item) =>
                            trim((string) $item),
                        $value
                    ),
                    fn ($item) =>
                        $item !== ''
                )
            );
        }

        if (
            is_string($value)
            && trim($value) !== ''
        ) {
            return [
                trim($value)
            ];
        }

        return [];
    }
}