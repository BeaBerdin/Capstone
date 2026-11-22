<?php

use App\Models\Enrollment;
use App\Models\LessonProgress;
use Illuminate\Support\Facades\Storage;

function editorReadingFixture(): array
{
    fakeFileDisks();
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher);
    $lesson = $course->lessons()->create([
        'title' => 'Teacher editor', 'lesson_type' => 'text', 'lesson_order' => 1,
        'is_published' => true, 'content' => 'Original mirror',
    ]);
    $plain = $lesson->sections()->create(['type' => 'text', 'body' => "  <h2>Literal markup</h2>\n\nKeep & preserve.  ", 'position' => 0]);
    $rich = $lesson->sections()->create(['type' => 'text', 'body_format' => 'html', 'body' => '<h2>Topic</h2><p><strong>Bold</strong> and <u>underlined</u></p>', 'position' => 1]);
    $image = $lesson->sections()->create(['type' => 'image', 'caption' => 'Saved diagram', 'file_path' => 'lesson-files/editor/diagram.png', 'original_name' => 'diagram.png', 'mime_type' => 'image/png', 'position' => 2]);
    $file = $lesson->sections()->create(['type' => 'file', 'caption' => 'Private guide', 'file_path' => 'lesson-files/editor/guide.pdf', 'original_name' => 'guide.pdf', 'mime_type' => 'application/pdf', 'position' => 3]);
    Storage::disk('local')->put($image->file_path, 'Synthetic image');
    Storage::disk('local')->put($file->file_path, "%PDF-1.4\nSynthetic guide");

    return [$teacher, $course, $lesson, $plain, $rich, $image, $file];
}

function editorReadingFields(array $sections, array $overrides = []): array
{
    return array_replace(['title' => 'Teacher editor', 'lesson_type' => 'text', 'lesson_order' => 1, 'is_published' => 1, 'reading_builder' => 1, 'sections' => $sections], $overrides);
}

function editorSectionRows($lesson): array
{
    return $lesson->sections()->get()->map(fn ($section) => $section->only(['id', 'type', 'body', 'body_format', 'caption']))->all();
}

test('teacher create and edit expose rich editor hooks while escaping literal legacy text', function () {
    [$teacher, $course, $lesson, $plain] = editorReadingFixture();
    $this->actingAs($teacher)->get(route('teacher.lessons.create', $course))->assertOk()->assertSee('data-reading-editor', false);
    $this->get(route('teacher.lessons.edit', $lesson))->assertOk()
        ->assertSee('&lt;h2&gt;Literal markup&lt;/h2&gt;', false)
        ->assertSee('data-editor-html="&lt;h2&gt;Topic', false)
        ->assertSee('data-editor-status', false)
        ->assertDontSee('Formatted content is read-only');
    expect($plain->fresh()->body_format)->toBe('plain')->and($plain->fresh()->body)->toBe($plain->body);
});

test('unchanged editor payload can reorder legacy and rich sections without rewriting text or losing private media', function () {
    [$teacher, $course, $lesson, $plain, $rich, $image, $file] = editorReadingFixture();
    $rows = array_reverse(editorSectionRows($lesson));
    $this->actingAs($teacher)->put(route('teacher.lessons.update', $lesson), editorReadingFields($rows))->assertSessionHasNoErrors();
    expect($lesson->sections()->pluck('id')->all())->toBe([$file->id, $image->id, $rich->id, $plain->id])
        ->and($plain->fresh()->body)->toBe($plain->body)->and($plain->fresh()->body_format)->toBe('plain')
        ->and($rich->fresh()->body)->toBe($rich->body)->and($rich->fresh()->body_format)->toBe('html');
    Storage::disk('local')->assertExists([$image->file_path, $file->file_path]);
    Storage::disk('public')->assertMissing([$image->file_path, $file->file_path]);
});

test('intentional rich edits add formatting and sanitize pasted attacks while retaining media and progress', function () {
    [$teacher, $course, $lesson, $plain, $rich, $image, $file] = editorReadingFixture();
    $student = pathwiseUser('student');
    Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);
    $progress = LessonProgress::create(['student_id' => $student->id, 'lesson_id' => $lesson->id, 'status' => 'completed', 'completed_at' => now()]);
    $rows = editorSectionRows($lesson);
    $rows[0]['body_format'] = 'html';
    $rows[0]['body'] = '<h3>Edited topic</h3><p onclick="evil()"><strong>Bold</strong> <em>Italic</em> <u>Underline</u> <code>x &lt; y</code></p><ul><li>Bullet</li></ul><ol><li>Number</li></ol><pre><code>&lt;script&gt;literal&lt;/script&gt;</code></pre><a href="https://example.test/topic">Read</a><img src="https://example.test/tracker"><script>evil()</script><a href="javascript:evil()">Bad</a>';
    $rows[] = ['type' => 'text', 'body_format' => 'html', 'body' => '<p>New text</p>'];
    $this->actingAs($teacher)->put(route('teacher.lessons.update', $lesson), editorReadingFields($rows))->assertSessionHasNoErrors();
    expect($plain->fresh()->body_format)->toBe('html')->and($plain->fresh()->body)
        ->toContain('<h3>', '<strong>', '<em>', '<u>', '<code>', '<ul>', '<ol>', '<pre>', 'href="https://example.test/topic"')
        ->not->toContain('onclick', '<img', '<script>', 'javascript:')
        ->and($lesson->fresh()->content)->toContain('Edited topic', '- Bullet', '1. Number', 'x < y', '<script>literal</script>')
        ->and($progress->fresh()->status)->toBe('completed')->and($lesson->sections()->count())->toBe(5);
    Storage::disk('local')->assertExists([$image->file_path, $file->file_path]);
});

test('validation retry restores attempted rich bodies order and captions and retains saved attachments', function () {
    [$teacher, $course, $lesson, $plain, $rich, $image, $file] = editorReadingFixture();
    $rows = array_reverse(editorSectionRows($lesson));
    $rows[2]['body'] = '<p>Unsaved <strong>revision</strong></p><img src=x onerror="evil()">';
    $rows[2]['caption'] = 'Unsaved heading';
    $this->actingAs($teacher)->from(route('teacher.lessons.edit', $lesson))->put(route('teacher.lessons.update', $lesson), editorReadingFields($rows, ['title' => '']))->assertSessionHasErrors('title');
    $response = $this->get(route('teacher.lessons.edit', $lesson))->assertOk()
        ->assertSee('data-validation-retry="true"', false)->assertSee('Unsaved heading')
        ->assertSee('&lt;strong&gt;revision&lt;/strong&gt;', false)->assertSee('reselect any new uploads')
        ->assertDontSee('<img src=x', false)->assertSee(route('files.lesson-section', $file));
    $document = \Dom\HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);
    $ids = [];
    foreach ($document->querySelectorAll('[data-section-list] [data-section-id]') as $input) $ids[] = (int) $input->getAttribute('value');
    expect($ids)->toBe([$file->id, $image->id, $rich->id, $plain->id])
        ->and($rich->fresh()->body)->toBe($rich->body)->and($lesson->sections()->count())->toBe(4);
    Storage::disk('local')->assertExists([$image->file_path, $file->file_path]);
});

test('oversized rejected rich input remains escaped and recoverable instead of breaking the edit page', function () {
    [$teacher, $course, $lesson, $plain, $rich] = editorReadingFixture();
    $rows = editorSectionRows($lesson);
    $rows[1]['body'] = '<p>'.str_repeat('x', 120001).'</p>';
    $this->actingAs($teacher)->from(route('teacher.lessons.edit', $lesson))->put(route('teacher.lessons.update', $lesson), editorReadingFields($rows))->assertSessionHasErrors('sections.1.body');
    $this->get(route('teacher.lessons.edit', $lesson))->assertOk()->assertSee('data-editor-error="true"', false)
        ->assertSee('Your original input is preserved above')->assertSee('&lt;p&gt;'.str_repeat('x', 120001).'&lt;/p&gt;', false);
    expect($rich->fresh()->body)->toBe($rich->body);
});

test('editor saves do not grant private file access or complete a lesson by viewing it', function () {
    [$teacher, $course, $lesson, $plain, $rich, $image, $file] = editorReadingFixture();
    $this->actingAs($teacher)->put(route('teacher.lessons.update', $lesson), editorReadingFields(editorSectionRows($lesson)))->assertSessionHasNoErrors();
    $course->update(['status' => 'published']);
    $student = pathwiseUser('student');
    $this->actingAs($student)->get(route('files.lesson-section', $file))->assertForbidden();
    Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);
    $this->get(route('files.lesson-section', $file))->assertOk();
    $this->get(route('files.lesson-section', $image))->assertOk();
    $this->get(route('student.lesson.view', $lesson))->assertOk();
    expect(LessonProgress::where('lesson_id', $lesson->id)->where('student_id', $student->id)->sole()->status)->toBe('in_progress');
});
