<?php

use App\Models\Assignment;
use App\Models\Enrollment;
use App\Models\Submission;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/*
| Manuscript FR (s) "AI Output Evaluation": Gemini evaluates the student's
| actual work. The text of an uploaded source or .txt file is sent; when
| there is no readable text, AI evaluation is not started.
*/

function aiEvaluationSetup(array $submissionAttributes, string $type = 'code', ?string $language = 'java'): array
{
    config(['services.gemini.api_key' => 'test-key', 'services.gemini.model' => 'gemini-2.5-flash']);

    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published']);

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    $assignment = Assignment::create([
        'course_id' => $course->id,
        'title' => 'Hello World',
        'submission_type' => $type,
        'programming_language' => $type === 'code' ? $language : null,
        'ai_evaluation_enabled' => true,
        'max_score' => 100,
        'is_published' => true,
    ]);

    $submission = Submission::create(array_merge([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
        'status' => 'submitted',
        'submitted_at' => now(),
    ], $submissionAttributes));

    return [$teacher, $submission];
}

function fakeGeminiEvaluation(): void
{
    Http::fake([
        '*' => Http::response([
            'output_text' => json_encode([
                'score' => 85,
                'feedback' => 'Prints the expected greeting.',
                'strengths' => ['Correct output'],
                'improvements' => ['Add comments'],
                'criteria_analysis' => 'Meets the task.',
                'code_quality' => 'Clean.',
                'efficiency' => 'Fine.',
            ]),
        ]),
    ]);
}

test('the text of an uploaded source file is sent to Gemini', function () {
    fakeFileDisks();
    Storage::disk('local')->put('assignment-submissions/1/Main.java', 'class Main { /* UNIQUE-SOURCE-MARKER */ }');
    fakeGeminiEvaluation();

    [$teacher, $submission] = aiEvaluationSetup(['file_path' => 'assignment-submissions/1/Main.java']);

    $this->actingAs($teacher)
        ->post(route('teacher.submissions.ai-evaluate', $submission))
        ->assertSessionHas('success');

    expect($submission->fresh()->ai_evaluation_status)->toBe('completed')
        ->and($submission->fresh()->ai_score)->toBe(85);

    Http::assertSent(fn (Request $request) => str_contains($request->body(), 'UNIQUE-SOURCE-MARKER')
        && ! str_contains($request->body(), 'File content extraction is not yet configured'));
});

test('a .txt attachment on an essay is sent to Gemini', function () {
    fakeFileDisks();
    Storage::disk('local')->put('assignment-submissions/1/essay.txt', 'My essay about UNIQUE-ESSAY-MARKER.');
    fakeGeminiEvaluation();

    [$teacher, $submission] = aiEvaluationSetup(['file_path' => 'assignment-submissions/1/essay.txt'], 'essay');

    $this->actingAs($teacher)
        ->post(route('teacher.submissions.ai-evaluate', $submission))
        ->assertSessionHas('success');

    Http::assertSent(fn (Request $request) => str_contains($request->body(), 'UNIQUE-ESSAY-MARKER'));
});

test('a file-only submission without readable text is not sent to Gemini', function (string $path, string $contents, string $reason) {
    fakeFileDisks();
    Storage::disk('local')->put($path, $contents);
    Http::fake();

    [$teacher, $submission] = aiEvaluationSetup(['file_path' => $path], 'essay');

    $this->actingAs($teacher)
        ->post(route('teacher.submissions.ai-evaluate', $submission))
        ->assertRedirect(route('teacher.submissions.show', $submission))
        ->assertSessionHas('error', fn (string $error) => str_contains($error, $reason)
            && str_contains($error, 'AI evaluation was not started'));

    expect($submission->fresh()->ai_evaluation_status)->not->toBe('processing');
    Http::assertNothingSent();
})->with([
    'PDF document' => ['assignment-submissions/1/report.pdf', '%PDF-1.4 binary', 'cannot be read'],
    'binary content' => ['assignment-submissions/1/data.txt', "abc\0def", 'not readable text'],
    'empty file' => ['assignment-submissions/1/empty.txt', "   \n", 'is empty'],
]);

test('a source file over the size limit is not sent to Gemini', function () {
    fakeFileDisks();
    Storage::disk('local')->put('assignment-submissions/1/Big.java', str_repeat('a', Assignment::AI_TEXT_MAX_KB * 1024 + 1));
    Http::fake();

    [$teacher, $submission] = aiEvaluationSetup(['file_path' => 'assignment-submissions/1/Big.java']);

    $this->actingAs($teacher)
        ->post(route('teacher.submissions.ai-evaluate', $submission))
        ->assertSessionHas('error', fn (string $error) => str_contains($error, 'larger than'));

    Http::assertNothingSent();
});

test('a missing file is reported instead of being sent to Gemini', function () {
    fakeFileDisks();
    Http::fake();

    [$teacher, $submission] = aiEvaluationSetup(['file_path' => 'assignment-submissions/1/Gone.java']);

    $this->actingAs($teacher)
        ->post(route('teacher.submissions.ai-evaluate', $submission))
        ->assertSessionHas('error', fn (string $error) => str_contains($error, 'could not be found'));

    Http::assertNothingSent();
});

test('a typed answer is still evaluated when its attachment cannot be read', function () {
    fakeFileDisks();
    Storage::disk('local')->put('assignment-submissions/1/report.pdf', '%PDF-1.4 binary');
    fakeGeminiEvaluation();

    [$teacher, $submission] = aiEvaluationSetup([
        'answer_text' => 'Typed essay UNIQUE-TYPED-MARKER.',
        'file_path' => 'assignment-submissions/1/report.pdf',
    ], 'essay');

    $this->actingAs($teacher)
        ->post(route('teacher.submissions.ai-evaluate', $submission))
        ->assertSessionHas('success');

    Http::assertSent(fn (Request $request) => str_contains($request->body(), 'UNIQUE-TYPED-MARKER'));
});

test('the text of an uploaded .py file is sent to Gemini as Python', function () {
    fakeFileDisks();
    Storage::disk('local')->put('assignment-submissions/1/main.py', 'print("UNIQUE-PYTHON-MARKER")');
    fakeGeminiEvaluation();

    [$teacher, $submission] = aiEvaluationSetup(['file_path' => 'assignment-submissions/1/main.py'], 'code', 'python');

    $this->actingAs($teacher)
        ->post(route('teacher.submissions.ai-evaluate', $submission))
        ->assertSessionHas('success');

    expect($submission->fresh()->ai_evaluation_status)->toBe('completed');

    Http::assertSent(fn (Request $request) => str_contains($request->body(), 'UNIQUE-PYTHON-MARKER')
        && str_contains($request->body(), 'Python'));
});

test('Gemini is told the language by its display name', function () {
    fakeGeminiEvaluation();

    [$teacher, $submission] = aiEvaluationSetup(['answer_text' => '<h1>Hello</h1>'], 'code', 'javascript');

    $this->actingAs($teacher)
        ->post(route('teacher.submissions.ai-evaluate', $submission))
        ->assertSessionHas('success');

    Http::assertSent(fn (Request $request) => str_contains($request->body(), 'HTML\/CSS\/JavaScript')
        || str_contains($request->body(), 'HTML/CSS/JavaScript'));
});

test('a source file not moved to the private disk yet is still sent to Gemini', function () {
    fakeFileDisks();
    Storage::disk('public')->put('assignment-submissions/1/Old.java', 'class Old { /* UNIQUE-LEGACY-MARKER */ }');
    fakeGeminiEvaluation();

    [$teacher, $submission] = aiEvaluationSetup(['file_path' => 'assignment-submissions/1/Old.java']);

    $this->actingAs($teacher)
        ->post(route('teacher.submissions.ai-evaluate', $submission))
        ->assertSessionHas('success');

    Http::assertSent(fn (Request $request) => str_contains($request->body(), 'UNIQUE-LEGACY-MARKER'));
});
