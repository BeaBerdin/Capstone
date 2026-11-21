<?php

use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Quiz;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

function readingLessonFields(array $overrides = []): array
{
    return array_replace([
        'title' => 'Reading builder lesson',
        'lesson_type' => 'text',
        'lesson_order' => 1,
        'is_published' => 1,
        'reading_builder' => 1,
    ], $overrides);
}

function readingFileUpload(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('reader guide.pdf', "%PDF-1.4\nReading material\n%%EOF");
}

test('teachers create reorder edit and delete reading sections and related files', function () {
    fakeFileDisks();
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher);
    $this->actingAs($teacher)->get(route('teacher.lessons.create', $course))
        ->assertOk()->assertSee('data-section-builder', false);

    $this->post(route('teacher.lessons.store', $course), readingLessonFields([
        'content' => 'Client preview text',
        'sections' => [
            ['type' => 'text', 'caption' => 'First heading', 'body' => 'Original explanation'],
            ['type' => 'image', 'caption' => 'Original illustration', 'file' => UploadedFile::fake()->image('diagram.png')],
            ['type' => 'file', 'caption' => 'Reading guide', 'file' => readingFileUpload()],
        ],
    ]))->assertSessionHasNoErrors()->assertRedirect(route('teacher.lessons', $course));

    $lesson = $course->lessons()->sole();
    [$text, $image, $file] = $lesson->sections()->get()->all();
    expect($lesson->content)->toBe("First heading\nOriginal explanation");
    Storage::disk('local')->assertExists([$image->file_path, $file->file_path]);
    Storage::disk('public')->assertMissing([$image->file_path, $file->file_path]);
    $this->get(route('teacher.lessons.edit', $lesson))->assertOk()
        ->assertSeeInOrder(['First heading', 'Original illustration', 'Reading guide'])
        ->assertSee(route('files.lesson-section', $image));

    $this->put(route('teacher.lessons.update', $lesson), readingLessonFields([
        'sections' => [
            ['type' => 'file', 'id' => $file->id, 'caption' => 'Moved guide'],
            ['type' => 'text', 'id' => $text->id, 'caption' => 'Revised heading', 'body' => 'Revised explanation'],
            ['type' => 'image', 'id' => $image->id, 'file' => UploadedFile::fake()->image('replacement.png')],
            ['type' => 'text', 'body' => 'New conclusion'],
        ],
    ]))->assertSessionHasNoErrors()->assertRedirect(route('teacher.lessons', $course));

    $updated = $lesson->sections()->get();
    expect($updated->pluck('id')->take(3)->all())->toBe([$file->id, $text->id, $image->id])
        ->and($updated->pluck('position')->all())->toBe([0, 1, 2, 3])
        ->and($file->fresh()->file_path)->toBe($file->file_path)
        ->and($lesson->fresh()->content)->toBe("Revised heading\nRevised explanation\n\nNew conclusion");
    $replacement = $image->fresh()->file_path;
    Storage::disk('local')->assertMissing($image->file_path);
    Storage::disk('local')->assertExists($replacement);

    $this->put(route('teacher.lessons.update', $lesson), readingLessonFields([
        'sections' => [
            ['type' => 'image', 'id' => $image->id],
            ['type' => 'text', 'id' => $text->id, 'body' => 'Remaining explanation'],
        ],
    ]))->assertSessionHasNoErrors();
    $this->assertModelMissing($file);
    $this->assertModelMissing($updated->last());
    $this->get(route('files.lesson-section', $file))->assertNotFound();
    Storage::disk('local')->assertMissing($file->file_path);

    $this->delete(route('teacher.lessons.delete', $lesson))->assertRedirect(route('teacher.lessons', $course));
    $this->assertModelMissing($lesson);
    $this->assertModelMissing($image);
    Storage::disk('local')->assertMissing($replacement);
});

test('students see ordered escaped sections with private inline images and file downloads', function () {
    fakeFileDisks();
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher);
    $this->actingAs($teacher)->post(route('teacher.lessons.store', $course), readingLessonFields([
        'sections' => [
            ['type' => 'text', 'caption' => 'First position', 'body' => '<b>Plain text</b>'],
            ['type' => 'image', 'caption' => 'Middle illustration', 'file' => UploadedFile::fake()->image('diagram.png')],
            ['type' => 'file', 'caption' => 'Final handout', 'file' => readingFileUpload()],
        ],
    ]))->assertSessionHasNoErrors();

    $course->update(['status' => 'published']);
    $lesson = $course->lessons()->sole();
    [$text, $image, $file] = $lesson->sections()->get()->all();
    $student = pathwiseUser('student');
    Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'status' => 'completed', 'enrolled_at' => now()]);

    $this->actingAs($student)->get(route('student.lesson.view', $lesson))
        ->assertOk()->assertSeeInOrder(['First position', '&lt;b&gt;Plain text&lt;/b&gt;', 'Middle illustration', 'Final handout'], false)
        ->assertDontSee('<b>Plain text</b>', false)
        ->assertSee(route('files.lesson-section', $image))
        ->assertSee(route('files.lesson-section', $file))
        ->assertDontSee($file->file_path);

    $this->get(route('files.lesson-section', $text))->assertNotFound();
    $this->get(route('files.lesson-section', $image))->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Disposition', 'inline; filename=diagram.png');
    $this->get(route('files.lesson-section', $file))->assertDownload('reader guide.pdf');
    Storage::disk('local')->delete($file->file_path);
    Storage::disk('public')->put($file->file_path, 'A section must not fall back to public storage');
    $this->get(route('files.lesson-section', $file))->assertNotFound();
});

test('reading media shares lesson ownership publication and enrollment rules', function () {
    fakeFileDisks();
    $department = Department::create(['name' => 'Reading Department']);
    $teacher = pathwiseApprovedTeacher(['department_id' => $department->id]);
    $course = pathwiseCourse($teacher, ['status' => 'published']);
    $lesson = $course->lessons()->create(readingLessonFields(['is_preview' => 1]));
    $path = 'lesson-files/'.$course->id.'/sections/handout.pdf';
    Storage::disk('local')->put($path, '%PDF-1.4');
    $section = $lesson->sections()->create(['type' => 'file', 'file_path' => $path]);
    $url = route('files.lesson-section', $section);

    $this->get($url)->assertRedirect(route('login'));
    $this->actingAs(pathwiseUser('student'))->get($url)->assertForbidden();
    $this->actingAs(pathwiseApprovedTeacher())->get($url)->assertForbidden();
    $this->actingAs(pathwiseUser('admin'))->get($url)->assertForbidden();
    $this->actingAs(pathwiseUser('admin', ['department_id' => $department->id]))->get($url)->assertOk();
    $this->actingAs(pathwiseUser('super_admin'))->get($url)->assertOk();
    $this->actingAs($teacher)->get($url)->assertOk();

    $student = pathwiseUser('student');
    $enrollment = Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'status' => 'cancelled', 'enrolled_at' => now()]);
    $this->actingAs($student)->get($url)->assertForbidden();

    $enrollment->update(['status' => 'active']);
    $this->get($url)->assertOk();
    $lesson->update(['is_published' => false]);
    $this->get($url)->assertForbidden();
    $lesson->update(['is_published' => true]);
    $course->update(['status' => 'draft']);
    $this->get($url)->assertForbidden();
});

test('rejected updates never change stored sections or discard existing private files', function () {
    fakeFileDisks();
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher);
    $lesson = $course->lessons()->create(readingLessonFields(['content' => 'Keep this content']));
    $oldPath = 'lesson-files/'.$course->id.'/sections/keep.pdf';
    Storage::disk('local')->put($oldPath, '%PDF-1.4');
    $existing = $lesson->sections()->create(['type' => 'file', 'file_path' => $oldPath]);
    $other = $course->lessons()->create(readingLessonFields());
    $outside = $other->sections()->create(['type' => 'text', 'body' => 'Other lesson']);
    $url = route('teacher.lessons.update', $lesson);

    $this->actingAs(pathwiseApprovedTeacher())->put($url, readingLessonFields([
        'sections' => [['id' => $existing->id, 'type' => 'file']],
    ]))->assertForbidden();

    $this->actingAs($teacher)->put($url, readingLessonFields([
        'sections' => [['id' => $outside->id, 'type' => 'text', 'body' => 'Outside']],
    ]))->assertSessionHasErrors('sections');

    $this->put($url, readingLessonFields([
        'sections' => [['id' => $existing->id, 'type' => 'file'], ['id' => $existing->id, 'type' => 'file']],
    ]))->assertSessionHasErrors('sections.1.id');

    $this->put($url, readingLessonFields([
        'title' => 'Even the title should roll back',
        'sections' => [
            ['id' => $existing->id, 'type' => 'file', 'file' => readingFileUpload()],
            ['type' => 'image'],
        ],
    ]))->assertSessionHasErrors('sections.1.file');
    expect($lesson->fresh()->title)->toBe($lesson->title)
        ->and($lesson->fresh()->content)->toBe('Keep this content')
        ->and($lesson->sections()->sole()->file_path)->toBe($oldPath)
        ->and(Storage::disk('local')->allFiles())->toBe([$oldPath]);
});

test('reading sections reject disallowed uploads oversized files and an empty builder', function () {
    fakeFileDisks();
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher);
    $url = route('teacher.lessons.store', $course);
    $this->actingAs($teacher);

    $this->post($url, readingLessonFields(['content' => 'Stale mirror']))->assertSessionHasErrors('sections');
    $this->post($url, readingLessonFields(['sections' => [
        ['type' => 'image', 'file' => UploadedFile::fake()->create('diagram.svg', 1, 'image/svg+xml')],
    ]]))->assertSessionHasErrors('sections.0.file');
    $this->post($url, readingLessonFields(['sections' => [
        ['type' => 'image', 'file' => UploadedFile::fake()->image('too-big.png')->size(10241)],
    ]]))->assertSessionHasErrors('sections.0.file');
    $this->post($url, readingLessonFields(['sections' => [
        ['type' => 'file', 'file' => readingFileUpload()->size(20481)],
    ]]))->assertSessionHasErrors('sections.0.file');
    $this->post($url, readingLessonFields(['sections' => [
        ['type' => 'file', 'file' => UploadedFile::fake()->create('page.html', 2, 'text/html')],
    ]]))->assertSessionHasErrors('sections.0.file');
    $this->post($url, readingLessonFields(['sections' => array_fill(0, 51, ['type' => 'text', 'body' => 'Reading'])]))
        ->assertSessionHasErrors('sections');
    $this->post($url, readingLessonFields(['sections' => [['type' => 'text', 'body' => str_repeat('x', 30001)]]]))
        ->assertSessionHasErrors('sections.0.body');
    expect($course->lessons()->exists())->toBeFalse()
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('legacy reading still renders and can be edited with the builder', function () {
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher, ['status' => 'published']);
    $content = "Legacy introduction.\n\nLegacy conclusion.";
    $lesson = $course->lessons()->create(readingLessonFields(['content' => $content]));
    $student = pathwiseUser('student');
    Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'status' => 'active', 'enrolled_at' => now()]);

    $this->actingAs($student)->get(route('student.lesson.view', $lesson))->assertOk()
        ->assertSeeInOrder(['Legacy introduction.', 'Legacy conclusion.']);
    expect($lesson->sections()->exists())->toBeFalse();

    $course->update(['status' => 'draft']);
    $this->actingAs($teacher)->get(route('teacher.lessons.edit', $lesson))
        ->assertOk()->assertSee($content);
    $this->put(route('teacher.lessons.update', $lesson), readingLessonFields([
        'sections' => [['type' => 'text', 'body' => $content]],
    ]))->assertSessionHasNoErrors();
    expect($lesson->fresh()->content)->toBe($content)
        ->and($lesson->sections()->sole()->body)->toBe($content);

    $this->post(route('teacher.lessons.store', $course), readingLessonFields([
        'reading_builder' => 0, 'lesson_order' => 2, 'content' => 'Legacy client content',
    ]))->assertSessionHasNoErrors();
    expect($course->lessons()->where('lesson_order', 2)->sole()->content)->toBe('Legacy client content');
});

test('the admin legacy textarea preserves sections on metadata changes and retains media on text edits', function () {
    fakeFileDisks();
    $course = pathwiseCourse(pathwiseApprovedTeacher());
    $content = "First heading\nFirst body\n\nSecond body";
    $lesson = $course->lessons()->create(readingLessonFields(['content' => $content]));
    $first = $lesson->sections()->create(['type' => 'text', 'caption' => 'First heading', 'body' => 'First body']);
    $path = 'lesson-files/'.$course->id.'/sections/guide.pdf';
    Storage::disk('local')->put($path, '%PDF-1.4');
    $file = $lesson->sections()->create(['type' => 'file', 'position' => 1, 'file_path' => $path]);
    $second = $lesson->sections()->create(['type' => 'text', 'position' => 2, 'body' => 'Second body']);
    $url = route('lessons.update', $lesson);
    $fields = readingLessonFields(['course_id' => $course->id, 'content' => $content, 'title' => 'Renamed']);

    $this->actingAs(pathwiseUser('super_admin'))->put($url, $fields)->assertRedirect(route('lessons.index'));
    expect($lesson->sections()->pluck('id')->all())->toBe([$first->id, $file->id, $second->id]);
    expect($first->fresh()->caption)->toBe('First heading');

    $this->put($url, array_replace($fields, ['content' => 'Replacement text']))->assertRedirect(route('lessons.index'));
    expect($lesson->sections()->pluck('id')->all())->toBe([$first->id, $file->id])
        ->and($first->fresh()->body)->toBe('Replacement text');
    Storage::disk('local')->assertExists($path);

    $this->delete(route('lessons.destroy', $lesson))->assertRedirect(route('lessons.index'));
    $this->assertModelMissing($file);
    Storage::disk('local')->assertMissing($path);
});

test('AI quiz generation reads the text and heading saved by the builder', function () {
    config(['services.gemini.api_key' => 'test-key', 'services.gemini.model' => 'gemini-2.5-flash']);
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher);
    $this->actingAs($teacher)->post(route('teacher.lessons.store', $course), readingLessonFields([
        'sections' => [['type' => 'text', 'caption' => 'Glacial valleys', 'body' => 'Moving glaciers carve U-shaped valleys.']],
    ]))->assertSessionHasNoErrors();

    $quizLesson = $course->lessons()->create(['title' => 'Glaciers quiz', 'lesson_type' => 'quiz', 'lesson_order' => 2]);
    $quiz = Quiz::create(['course_id' => $course->id, 'lesson_id' => $quizLesson->id, 'title' => 'Glaciers quiz', 'passing_score' => 75]);
    $questions = array_map(fn ($question) => [
        'question' => $question, 'option_a' => 'First', 'option_b' => 'Second',
        'option_c' => 'Third', 'option_d' => 'Fourth', 'correct_answer' => 'A',
    ], ['What shape are glacial valleys?', 'How do glaciers move?', 'What forms a valley?']);
    Http::fake(['*' => Http::response(['steps' => [[
        'type' => 'model_output',
        'content' => [['type' => 'text', 'text' => json_encode(['questions' => $questions])]],
    ]]])]);
    $revision = $this->get(route('teacher.quiz.builder', $quizLesson))->assertOk()->viewData('quizRevision');
    $this->post(route('teacher.quiz.questions.generate', $quiz), [
        'question_count' => 1, 'difficulty' => 'beginner', 'revision' => $revision,
    ])->assertSessionHasNoErrors()->assertSessionHas('success')->assertRedirect(route('teacher.quiz.builder', $quizLesson));
    Http::assertSent(fn ($request) => str_contains($request->body(), 'Glacial valleys')
        && str_contains($request->body(), 'Moving glaciers carve U-shaped valleys.'));
    expect($quiz->questions()->count())->toBe(1);
});


test('a standalone heading is ordered and included in the reading text mirror', function () {
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher);
    $this->actingAs($teacher)->post(route('teacher.lessons.store', $course), readingLessonFields([
        'sections' => [
            ['type' => 'text', 'caption' => 'Standalone heading'],
            ['type' => 'text', 'body' => 'Separate explanation'],
        ],
    ]))->assertSessionHasNoErrors();

    $lesson = $course->lessons()->sole();
    expect($lesson->sections()->first()->caption)->toBe('Standalone heading')
        ->and($lesson->sections()->first()->body)->toBeNull()
        ->and($lesson->content)->toBe("Standalone heading\n\nSeparate explanation");
});
