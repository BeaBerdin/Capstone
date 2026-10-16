<?php

use App\Models\Assignment;
use App\Models\Enrollment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
| The "How Students Answer" labels follow the submission type: essays say
| "Typed answer", code assignments say "Pasted code". The stored values
| (either, text, file) never change.
*/

function wordingAssignment(string $type, string $format): array
{
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
        'title' => 'Wording task',
        'submission_type' => $type,
        'programming_language' => $type === 'code' ? 'java' : null,
        'answer_format' => $format,
        'max_score' => 100,
        'is_published' => true,
    ]);

    return [$teacher, $student, $course, $assignment];
}

test('the labels are worded for essays and for code, with the same stored values', function () {
    expect(Assignment::answerFormatLabels('essay'))->toBe([
        'either' => 'Typed answer or file upload',
        'text' => 'Typed answer only',
        'file' => 'File upload only',
    ])->and(Assignment::answerFormatLabels('code'))->toBe([
        'either' => 'Pasted code or file upload',
        'text' => 'Pasted code only',
        'file' => 'File upload only',
    ])->and(array_keys(Assignment::answerFormatLabels('essay')))->toBe(Assignment::ANSWER_FORMATS)
        ->and(array_keys(Assignment::answerFormatLabels('code')))->toBe(Assignment::ANSWER_FORMATS);
});

test('the create form starts with the essay wording and carries both sets for the type switch', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'draft']);

    $content = $this->actingAs($teacher)
        ->get(route('teacher.assignments.create', $course))
        ->assertOk()
        ->getContent();

    expect($content)->toMatch('/value="text"[^>]*>\s*Typed answer only\s*</')
        ->not->toContain('Typed answer / pasted code only')
        ->toContain('data-labels=')
        ->toContain('Pasted code only');
});

test('the edit form uses the wording of the assignment\'s type', function (string $type, string $label) {
    [$teacher, , , $assignment] = wordingAssignment($type, 'text');

    $content = $this->actingAs($teacher)
        ->get(route('teacher.assignments.edit', $assignment))
        ->assertOk()
        ->getContent();

    expect($content)->toMatch('/value="text"\s+selected\s*>\s*'.preg_quote($label, '/').'\s*</');
})->with([
    'essay' => ['essay', 'Typed answer only'],
    'code' => ['code', 'Pasted code only'],
]);

test('the student page shows the same wording', function (string $type, string $format, string $label) {
    [, $student, , $assignment] = wordingAssignment($type, $format);

    $this->actingAs($student)
        ->get(route('student.assignments.show', $assignment))
        ->assertOk()
        ->assertSeeText('How to answer:')
        ->assertSeeText($label);
})->with([
    'essay either' => ['essay', 'either', 'Typed answer or file upload'],
    'essay text' => ['essay', 'text', 'Typed answer only'],
    'code either' => ['code', 'either', 'Pasted code or file upload'],
    'code text' => ['code', 'text', 'Pasted code only'],
    'code file' => ['code', 'file', 'File upload only'],
]);

test('the server messages use the same wording', function (string $type, string $format, array $input, string $field, string $message) {
    Storage::fake('public');
    [, $student, , $assignment] = wordingAssignment($type, $format);

    $payload = array_map(
        fn ($value) => $value === 'FILE'
            ? UploadedFile::fake()->createWithContent($type === 'code' ? 'Main.java' : 'answer.txt', 'content')
            : $value,
        $input
    );

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), $payload)
        ->assertSessionHasErrors([$field => $message]);
})->with([
    'code text-only, file sent' => ['code', 'text', ['submission_file' => 'FILE'], 'submission_file', 'This assignment accepts: Pasted code only. Please paste your code instead.'],
    'essay text-only, file sent' => ['essay', 'text', ['submission_file' => 'FILE'], 'submission_file', 'This assignment accepts: Typed answer only. Please type your answer instead.'],
    'file-only, text sent' => ['code', 'file', ['answer_text' => 'class A {}'], 'answer_text', 'This assignment accepts: File upload only. Please upload a file instead.'],
    'code text-only, empty' => ['code', 'text', [], 'answer_text', 'Please paste your code before submitting.'],
    'essay text-only, empty' => ['essay', 'text', [], 'answer_text', 'Please type your answer before submitting.'],
    'code either, empty' => ['code', 'either', [], 'answer_text', 'Please paste your code or upload a file before submitting.'],
    'essay either, empty' => ['essay', 'either', [], 'submission', 'Please type your answer or upload a file before submitting.'],
    'file-only, empty' => ['essay', 'file', [], 'submission_file', 'Please upload a file before submitting.'],
]);
