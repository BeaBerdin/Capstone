<?php

use Illuminate\Support\Facades\Storage;

function plainPreservationFixture(string $body, bool $sectionExists = true): array
{
    fakeFileDisks();
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher);
    $lesson = $course->lessons()->create([
        'title' => 'Plain preservation', 'lesson_type' => 'text', 'content' => $body,
        'lesson_order' => 1, 'is_published' => true,
    ]);
    $section = $sectionExists ? $lesson->sections()->create(['type' => 'text', 'body' => $body, 'position' => 0]) : null;

    return [$teacher, $course, $lesson, $section];
}

function plainPreservationFields(array $sections): array
{
    return ['title' => 'Plain preservation', 'lesson_type' => 'text', 'reading_builder' => 1,
        'lesson_order' => 1, 'is_published' => 1, 'sections' => $sections];
}

// Values PHP receives after a native browser serializes a textarea in multipart/form-data.
// The complementary real Chromium regression exercises actual HTTP encoding and PHP parsing.
function browserMultipartText(string $value): string
{
    return preg_replace('/\r\n|\r|\n/', "\r\n", $value);
}

test('unchanged parsed multipart Reading bodies retain exact original bytes', function (string $original) {
    [$teacher, $course, $lesson, $section] = plainPreservationFixture($original);
    $this->actingAs($teacher)->post(route('teacher.lessons.update', $lesson), ['_method' => 'PUT'] + plainPreservationFields([
        ['id' => $section->id, 'type' => 'text', 'body_format' => 'plain', 'body' => browserMultipartText($original)],
    ]))->assertSessionHasNoErrors();
    expect($section->fresh()->body)->toBe($original)
        ->and($section->fresh()->body_format)->toBe('plain')
        ->and($section->fresh()->id)->toBe($section->id)
        ->and($lesson->fresh()->content)->toBe($original);
})->with([
    'LF, Unicode and outer spaces' => ["  Café λ 日本語 <b>literal</b>\n\n  indented\nLast line.  "],
    'CRLF with outer blank lines' => ["\r\n\r\n  Café λ 日本語\r\n\r\nLast line.  \r\n"],
    'leading LF blank lines' => ["\n\n\t  Indented Unicode résumé\n\nEnd\n\n"],
    'mixed stored line endings' => ["  First\r\nSecond\nThird\rFourth  "],
    'single line with tabs and spaces' => ["\t  Café & <script>literal</script>  \t"],
]);

test('intentional plain text and whitespace edits are retained rather than mistaken for transport changes', function (string $edited) {
    $original = "  Café λ\n\n  indented\nLast line.  ";
    [$teacher, $course, $lesson, $section] = plainPreservationFixture($original);
    $submitted = browserMultipartText($edited);
    $this->actingAs($teacher)->post(route('teacher.lessons.update', $lesson), ['_method' => 'PUT'] + plainPreservationFields([
        ['id' => $section->id, 'type' => 'text', 'body_format' => 'plain', 'body' => $submitted],
    ]))->assertSessionHasNoErrors();
    expect($section->fresh()->body)->toBe($submitted)->not->toBe($original)
        ->and($section->fresh()->body_format)->toBe('plain')
        ->and($lesson->fresh()->content)->toBe($submitted);
})->with([
    'remove one leading space' => [" Café λ\n\n  indented\nLast line.  "],
    'add leading spaces' => ["    Café λ\n\n  indented\nLast line.  "],
    'remove trailing spaces' => ["  Café λ\n\n  indented\nLast line."],
    'add trailing spaces' => ["  Café λ\n\n  indented\nLast line.    "],
    'add a blank line' => ["  Café λ\n\n\n  indented\nLast line.  "],
    'remove a blank line' => ["  Café λ\n  indented\nLast line.  "],
    'edit indentation' => ["  Café λ\n\n\tindented\nLast line.  "],
    'edit text' => ["  Café λ changed\n\n  indented\nLast line.  "],
    'append a trailing newline' => ["  Café λ\n\n  indented\nLast line.  \n"],
]);

test('first builder conversion restores original legacy content after multipart normalization', function () {
    $original = "\n  Café λ\n\nKeep whitespace.  \n";
    [$teacher, $course, $lesson] = plainPreservationFixture($original, false);
    $this->actingAs($teacher)->post(route('teacher.lessons.update', $lesson), ['_method' => 'PUT'] + plainPreservationFields([
        ['type' => 'text', 'body_format' => 'plain', 'body' => browserMultipartText($original)],
    ]))->assertSessionHasNoErrors();
    expect($lesson->sections()->sole()->body)->toBe($original)
        ->and($lesson->sections()->sole()->body_format)->toBe('plain')
        ->and($lesson->fresh()->content)->toBe($original);
});

test('transport preservation keeps reordered media and rich sections intact with sanitizer enforcement', function () {
    $original = "\n  Café λ\n\nKeep whitespace.  \n";
    [$teacher, $course, $lesson, $plain] = plainPreservationFixture($original);
    $rich = $lesson->sections()->create(['type' => 'text', 'body_format' => 'html', 'body' => '<h2>Topic</h2><p><strong>Important</strong></p>', 'position' => 1]);
    $file = $lesson->sections()->create(['type' => 'file', 'file_path' => 'lesson-files/preserve.pdf', 'original_name' => 'preserve.pdf', 'position' => 2]);
    Storage::disk('local')->put($file->file_path, '%PDF-1.4 fake');
    $this->actingAs($teacher)->post(route('teacher.lessons.update', $lesson), ['_method' => 'PUT'] + plainPreservationFields([
        ['id' => $file->id, 'type' => 'file'],
        ['id' => $rich->id, 'type' => 'text', 'body_format' => 'html', 'body' => $rich->body.'<script>attack()</script>'],
        ['id' => $plain->id, 'type' => 'text', 'body_format' => 'plain', 'body' => browserMultipartText($original)],
    ]))->assertSessionHasNoErrors();
    expect($lesson->sections()->pluck('id')->all())->toBe([$file->id, $rich->id, $plain->id])
        ->and($plain->fresh()->body)->toBe($original)
        ->and($rich->fresh()->body)->toBe($rich->body)
        ->and($rich->fresh()->body_format)->toBe('html')
        ->and($file->fresh()->file_path)->toBe($file->file_path)
        ->and($lesson->fresh()->content)->toBe("Topic\n\nImportant\n\n".$original);
    Storage::disk('local')->assertExists($file->file_path);
});
