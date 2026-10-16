<?php

use App\Models\Assignment;
use App\Models\Enrollment;
use App\Models\Submission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/*
| Code assignment requirements: no code execution, exact formatting,
| 25 MB uploads, a teacher-chosen answer format, and AI evaluation that is
| never marked done unless Gemini returned a usable result.
*/

function requirementsSetup(array $assignmentAttributes = []): array
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

    $assignment = Assignment::create(array_merge([
        'course_id' => $course->id,
        'title' => 'Print a greeting',
        'submission_type' => 'code',
        'programming_language' => 'java',
        'ai_evaluation_enabled' => true,
        'max_score' => 100,
        'is_published' => true,
    ], $assignmentAttributes));

    return [$teacher, $student, $assignment];
}

function formattedCode(): string
{
    return "\n    public class Main {\n\tpublic static void main(String[] args) {\n        // <script>alert('x')</script> & \"quotes\"\n        System.out.println(\"Hi\");\n\n    }\n}\n\n";
}

function submittedCodeSubmission(): array
{
    [$teacher, $student, $assignment] = requirementsSetup();

    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
        'answer_text' => 'class Main { }',
        'status' => 'submitted',
        'submitted_at' => now(),
        'ai_evaluation_status' => 'not_requested',
    ]);

    return [$teacher, $submission];
}

function geminiOutput(array $result): array
{
    return ['output_text' => json_encode($result)];
}

/*
|--------------------------------------------------------------------------
| No code execution
|--------------------------------------------------------------------------
*/

test('the unused code execution service has been removed', function () {
    expect(class_exists('App\Services\CodeExecutionService'))->toBeFalse()
        ->and(file_exists(app_path('Services/CodeExecutionService.php')))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Formatting
|--------------------------------------------------------------------------
*/

test('submitted code is saved exactly, including indentation and line breaks', function () {
    Notification::fake();
    [, $student, $assignment] = requirementsSetup(['answer_format' => 'text']);

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), ['answer_text' => formattedCode()])
        ->assertSessionHasNoErrors();

    expect(Submission::sole()->answer_text)->toBe(formattedCode());
});

test('the student page shows the code exactly, escaped, in monospace', function () {
    [, $student, $assignment] = requirementsSetup();

    Submission::create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
        'answer_text' => formattedCode(),
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $content = $this->actingAs($student)
        ->get(route('student.assignments.show', $assignment))
        ->assertOk()
        ->getContent();

    expect($content)->toContain('font-mono text-sm leading-6 text-slate-100">'.e(formattedCode()).'</pre>')
        // The textarea keeps the leading line break when the code is edited again.
        ->toContain(">\n".e(formattedCode()).'</textarea>')
        ->not->toContain("<script>alert('x')</script>");
});

test('the teacher grading page shows the code exactly, escaped, in monospace', function () {
    [$teacher, $student, $assignment] = requirementsSetup();

    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
        'answer_text' => formattedCode(),
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $content = $this->actingAs($teacher)
        ->get(route('teacher.submissions.show', $submission))
        ->assertOk()
        ->getContent();

    expect($content)->toContain('whitespace-pre font-mono text-sm leading-6 text-slate-800">'.e(formattedCode()).'</pre>')
        ->not->toContain("<script>alert('x')</script>");
});

test('AI feedback keeps its indentation and is escaped', function () {
    [$teacher, $submission] = submittedCodeSubmission();

    $feedback = "Good start.\n    for (int i = 0; i < 3; i++) {\n        <b>print</b>\n    }";

    $submission->update([
        'ai_evaluation_status' => 'completed',
        'ai_score' => 80,
        'ai_feedback' => $feedback,
        'ai_strengths' => "- Clear\n    - Indented",
        'ai_improvements' => '- Add comments',
        'ai_evaluated_at' => now(),
    ]);

    $content = $this->actingAs($teacher)
        ->get(route('teacher.submissions.show', $submission))
        ->assertOk()
        ->getContent();

    expect($content)->toContain('whitespace-pre-wrap wrap-break-word text-sm leading-6 text-slate-700">'.e($feedback).'</p>')
        ->toContain(e("- Clear\n    - Indented"))
        ->not->toContain('<b>print</b>');
});

/*
|--------------------------------------------------------------------------
| 25 MB upload limit
|--------------------------------------------------------------------------
*/

test('files up to 25 MB are accepted and larger files are rejected', function (string $type, string $file, int $kilobytes, bool $accepted) {
    Storage::fake('public');
    Notification::fake();

    [, $student, $assignment] = requirementsSetup([
        'submission_type' => $type,
        'programming_language' => $type === 'code' ? 'java' : null,
    ]);

    $response = $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), [
            'submission_file' => UploadedFile::fake()->create($file, $kilobytes),
        ]);

    if ($accepted) {
        $response->assertSessionHasNoErrors();
        expect(Submission::count())->toBe(1);
    } else {
        $response->assertSessionHasErrors('submission_file');
        expect(Submission::count())->toBe(0);
    }
})->with([
    'code file of 20 MB' => ['code', 'Main.java', 20 * 1024, true],
    'code file of exactly 25 MB' => ['code', 'Main.java', 25 * 1024, true],
    'code file over 25 MB' => ['code', 'Main.java', 25 * 1024 + 1, false],
    'essay PDF of exactly 25 MB' => ['essay', 'report.pdf', 25 * 1024, true],
    'essay PDF over 25 MB' => ['essay', 'report.pdf', 25 * 1024 + 1, false],
]);

test('a file over 256 KB is kept for manual grading and not sent to Gemini', function () {
    Storage::fake('public');
    Http::fake();

    [$teacher, $student, $assignment] = requirementsSetup();

    Storage::disk('public')->put('assignment-submissions/1/Main.java', str_repeat('a', 300 * 1024));

    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
        'file_path' => 'assignment-submissions/1/Main.java',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $this->actingAs($teacher)
        ->post(route('teacher.submissions.ai-evaluate', $submission))
        ->assertSessionHas('error', fn (string $error) => str_contains($error, 'larger than 256 KB')
            && str_contains($error, 'grade this submission manually'));

    Http::assertNothingSent();
    Storage::disk('public')->assertExists('assignment-submissions/1/Main.java');
});

/*
|--------------------------------------------------------------------------
| Answer format chosen by the teacher
|--------------------------------------------------------------------------
*/

test('a typed-only assignment accepts code and refuses a file', function () {
    Storage::fake('public');
    Notification::fake();
    [, $student, $assignment] = requirementsSetup(['answer_format' => 'text']);

    $this->actingAs($student)
        ->get(route('student.assignments.show', $assignment))
        ->assertSee('name="answer_text"', false)
        ->assertDontSee('name="submission_file"', false);

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), [
            'submission_file' => UploadedFile::fake()->createWithContent('Main.java', 'class Main {}'),
        ])
        ->assertSessionHasErrors('submission_file');

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), [])
        ->assertSessionHasErrors(['answer_text' => 'Please paste your code before submitting.']);

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), ['answer_text' => 'class Main {}'])
        ->assertSessionHasNoErrors();

    expect(Submission::sole()->file_path)->toBeNull();
});

test('a file-only assignment accepts a file and refuses typed text', function () {
    Storage::fake('public');
    Notification::fake();
    [, $student, $assignment] = requirementsSetup(['answer_format' => 'file']);

    $this->actingAs($student)
        ->get(route('student.assignments.show', $assignment))
        ->assertSee('name="submission_file"', false)
        ->assertDontSee('name="answer_text"', false);

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), ['answer_text' => 'class Main {}'])
        ->assertSessionHasErrors('answer_text');

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), [])
        ->assertSessionHasErrors(['submission_file' => 'Please upload a file before submitting.']);

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), [
            'submission_file' => UploadedFile::fake()->createWithContent('Main.java', 'class Main {}'),
        ])
        ->assertSessionHasNoErrors();

    expect(Submission::sole()->file_path)->not->toBeNull();
});

test('an either assignment accepts typed text or a file', function (string $type) {
    Storage::fake('public');
    Notification::fake();
    [, $student, $assignment] = requirementsSetup([
        'submission_type' => $type,
        'programming_language' => $type === 'code' ? 'java' : null,
        'answer_format' => 'either',
    ]);

    $this->actingAs($student)
        ->get(route('student.assignments.show', $assignment))
        ->assertSee('name="answer_text"', false)
        ->assertSee('name="submission_file"', false);

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), ['answer_text' => 'My answer'])
        ->assertSessionHasNoErrors();

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), [
            'submission_file' => UploadedFile::fake()->createWithContent($type === 'code' ? 'Main.java' : 'answer.txt', 'content'),
        ])
        ->assertSessionHasNoErrors();

    expect(Submission::sole()->file_path)->not->toBeNull();
})->with(['code', 'essay']);

test('an essay can be typed-only or file-only too', function () {
    Storage::fake('public');
    Notification::fake();
    [, $student, $typedOnly] = requirementsSetup([
        'submission_type' => 'essay',
        'programming_language' => null,
        'answer_format' => 'text',
    ]);

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $typedOnly), [
            'submission_file' => UploadedFile::fake()->create('essay.pdf', 10),
        ])
        ->assertSessionHasErrors(['submission_file' => 'This assignment accepts: Typed answer only. Please type your answer instead.']);
});

test('existing assignments without an answer format keep accepting either', function () {
    [, , $assignment] = requirementsSetup();

    $fresh = Assignment::find($assignment->id);

    expect($fresh->answer_format)->toBe('either')
        ->and($fresh->acceptsTypedAnswer())->toBeTrue()
        ->and($fresh->acceptsFileUpload())->toBeTrue();
});

test('a teacher can choose and change the answer format', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'draft']);

    $this->actingAs($teacher)
        ->post(route('teacher.assignments.store', $course), [
            'title' => 'Upload task',
            'submission_type' => 'code',
            'programming_language' => 'php',
            'answer_format' => 'file',
            'max_score' => 100,
        ])
        ->assertSessionHasNoErrors();

    $assignment = Assignment::sole();
    expect($assignment->answer_format)->toBe('file');

    $this->actingAs($teacher)
        ->get(route('teacher.assignments.edit', $assignment))
        ->assertOk()
        ->assertSee('name="answer_format"', false);

    $this->actingAs($teacher)
        ->put(route('teacher.assignments.update', $assignment), [
            'title' => 'Upload task',
            'submission_type' => 'code',
            'programming_language' => 'php',
            'answer_format' => 'text',
            'max_score' => 100,
        ])
        ->assertSessionHasNoErrors();

    expect($assignment->fresh()->answer_format)->toBe('text');

    $this->actingAs($teacher)
        ->put(route('teacher.assignments.update', $assignment), [
            'title' => 'Upload task',
            'submission_type' => 'code',
            'programming_language' => 'php',
            'answer_format' => 'video',
            'max_score' => 100,
        ])
        ->assertSessionHasErrors('answer_format');
});

test('due dates, resubmission and grading still work', function () {
    Storage::fake('public');
    Notification::fake();
    [$teacher, $student, $assignment] = requirementsSetup(['answer_format' => 'file']);

    $this->actingAs($student)->post(route('student.assignments.submit', $assignment), [
        'submission_file' => UploadedFile::fake()->createWithContent('Main.java', 'class A {}'),
    ]);
    $firstPath = Submission::sole()->file_path;

    // Resubmitting replaces the file.
    $this->actingAs($student)->post(route('student.assignments.submit', $assignment), [
        'submission_file' => UploadedFile::fake()->createWithContent('Main.java', 'class B {}'),
    ])->assertSessionHasNoErrors();

    $submission = Submission::sole();
    expect($submission->file_path)->not->toBe($firstPath);
    Storage::disk('public')->assertMissing($firstPath);

    // The teacher grades it manually.
    $this->actingAs($teacher)
        ->put(route('teacher.submissions.grade', $submission), [
            'status' => 'graded',
            'score' => 90,
            'feedback' => "Well done.\n    Keep the indentation.",
        ])
        ->assertSessionHasNoErrors();

    expect($submission->fresh()->score)->toBe(90)
        ->and($submission->fresh()->status)->toBe('graded');

    // After the due date nothing can be submitted.
    $assignment->update(['due_date' => now()->subDay()]);
    $submission->update(['status' => 'submitted']);

    $this->actingAs($student)->post(route('student.assignments.submit', $assignment), [
        'submission_file' => UploadedFile::fake()->createWithContent('Main.java', 'class C {}'),
    ])->assertSessionHasErrors('submission');
});

/*
|--------------------------------------------------------------------------
| AI evaluation reliability
|--------------------------------------------------------------------------
*/

test('a successful Gemini evaluation is saved as completed', function () {
    [$teacher, $submission] = submittedCodeSubmission();

    Http::fake(['*' => Http::response(geminiOutput([
        'score' => 88,
        'feedback' => 'Correct output.',
        'strengths' => ['Works'],
        'improvements' => ['Add comments'],
        'criteria_analysis' => '',
        'code_quality' => '',
        'efficiency' => '',
    ]))]);

    $this->actingAs($teacher)
        ->post(route('teacher.submissions.ai-evaluate', $submission))
        ->assertSessionHas('success');

    $submission->refresh();

    expect($submission->ai_evaluation_status)->toBe('completed')
        ->and($submission->ai_score)->toBe(88)
        ->and($submission->ai_feedback)->toBe('Correct output.')
        ->and($submission->ai_evaluated_at)->not->toBeNull();
});

test('a failed Gemini evaluation is not saved and the teacher can retry', function (Closure $fake, string $reason) {
    [$teacher, $submission] = submittedCodeSubmission();

    Http::fake(['*' => $fake()]);

    $this->actingAs($teacher)
        ->post(route('teacher.submissions.ai-evaluate', $submission))
        ->assertRedirect(route('teacher.submissions.show', $submission))
        ->assertSessionHas('error', fn (string $error) => str_contains($error, $reason)
            && str_contains($error, 'No AI score was saved'));

    $submission->refresh();

    expect($submission->ai_evaluation_status)->toBe('failed')
        ->and($submission->ai_score)->toBeNull()
        ->and($submission->ai_feedback)->toBeNull()
        ->and($submission->ai_evaluated_at)->toBeNull()
        ->and($submission->answer_text)->toBe('class Main { }')
        ->and($submission->status)->toBe('submitted');

    $this->actingAs($teacher)
        ->get(route('teacher.submissions.show', $submission))
        ->assertOk()
        ->assertSee('The last AI evaluation did not finish')
        ->assertSee('Retry AI Evaluation');
})->with([
    'HTTP error' => [fn () => Http::response(['error' => 'server error'], 500), 'HTTP 500'],
    'timeout' => [fn () => Http::failedConnection('Operation timed out'), 'did not respond in time'],
    'empty output' => [fn () => Http::response(['output_text' => '']), 'empty evaluation response'],
    'invalid JSON' => [fn () => Http::response(['output_text' => 'Sure! Here is my evaluation.']), 'invalid evaluation format'],
    'missing score' => [fn () => Http::response(geminiOutput(['feedback' => 'Looks fine.'])), 'incomplete evaluation'],
    'missing feedback' => [fn () => Http::response(geminiOutput(['score' => 70, 'feedback' => '  '])), 'incomplete evaluation'],
]);

test('a retry after a failure can succeed', function () {
    [$teacher, $submission] = submittedCodeSubmission();
    $submission->update(['ai_evaluation_status' => 'failed']);

    Http::fake(['*' => Http::response(geminiOutput([
        'score' => 75,
        'feedback' => 'Fine now.',
        'strengths' => [],
        'improvements' => [],
    ]))]);

    $this->actingAs($teacher)
        ->post(route('teacher.submissions.ai-evaluate', $submission))
        ->assertSessionHas('success');

    expect($submission->fresh()->ai_evaluation_status)->toBe('completed')
        ->and($submission->fresh()->ai_score)->toBe(75);
});

test('an evaluation stuck in processing can be retried, a fresh one cannot be started twice', function () {
    [$teacher, $submission] = submittedCodeSubmission();
    Http::fake();

    $submission->update(['ai_evaluation_status' => 'processing']);

    $this->actingAs($teacher)
        ->post(route('teacher.submissions.ai-evaluate', $submission))
        ->assertSessionHas('error', 'An AI evaluation for this submission is already in progress.');

    Http::assertNothingSent();

    $this->travel(Submission::AI_PROCESSING_TIMEOUT_MINUTES + 1)->minutes();

    $this->actingAs($teacher)
        ->get(route('teacher.submissions.show', $submission))
        ->assertSee('The last AI evaluation did not finish')
        ->assertSee('Retry AI Evaluation');
});
