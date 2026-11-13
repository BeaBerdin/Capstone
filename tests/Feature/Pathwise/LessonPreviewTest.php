<?php

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use Illuminate\Support\Facades\Storage;

function freePreviewFixture(array $courseAttributes = [], array $lessonAttributes = []): array
{
    $course = pathwiseCourse(pathwiseUser('teacher'), array_merge([
        'status' => 'published',
        'price' => 499,
    ], $courseAttributes));
    $lesson = Lesson::create(array_merge([
        'course_id' => $course->id,
        'title' => 'Preview reading lesson',
        'content' => 'This is the published preview reading.',
        'lesson_type' => 'text',
        'lesson_order' => 1,
        'duration_minutes' => 20,
        'is_preview' => true,
        'is_published' => true,
    ], $lessonAttributes));

    return [pathwiseUser('student'), $course, $lesson];
}

test('an unenrolled student can read a free preview without creating learning or payment records', function (int $price) {
    [$student, $course, $lesson] = freePreviewFixture(['price' => $price]);

    $response = $this->actingAs($student)->get(route('student.lesson.preview', $lesson))
        ->assertOk()
        ->assertViewIs('Student.lesson-preview')
        ->assertSee($lesson->content)
        ->assertSee(route('student.course.show', $course), false)
        ->assertDontSee(route('student.lesson.complete', $lesson), false);
    expect($response->headers->get('Cache-Control'))->toContain('no-store');

    foreach (['enrollments', 'lesson_progress', 'transactions', 'quiz_results', 'certificates'] as $table) {
        $this->assertDatabaseCount($table, 0);
    }
})->with(['free course' => 0, 'paid course' => 499]);

test('the course page links to valid previews and hides unpublished lessons', function () {
    [$student, $course, $preview] = freePreviewFixture();
    $locked = $preview->replicate()->fill(['title' => 'Locked reading lesson', 'is_preview' => false, 'lesson_order' => 2]);
    $locked->save();
    $draft = $preview->replicate()->fill(['title' => 'Hidden draft lesson', 'is_published' => false, 'lesson_order' => 3]);
    $draft->save();
    $quiz = $preview->replicate()->fill(['title' => 'Locked quiz lesson', 'lesson_type' => 'quiz', 'lesson_order' => 4]);
    $quiz->save();

    $this->actingAs($student)->get(route('student.course.show', $course))
        ->assertOk()
        ->assertSee(route('student.lesson.preview', $preview), false)
        ->assertSee('Preview Lesson')
        ->assertSee($locked->title)
        ->assertSee($quiz->title)
        ->assertDontSee($draft->title)
        ->assertDontSee(route('student.lesson.preview', $locked), false)
        ->assertDontSee(route('student.lesson.preview', $quiz), false);
});

test('unavailable lessons cannot be opened through the preview URL', function (array $courseAttributes, array $lessonAttributes) {
    [$student, , $lesson] = freePreviewFixture($courseAttributes, $lessonAttributes);

    $this->actingAs($student)->get(route('student.lesson.preview', $lesson))->assertNotFound();
    $this->assertDatabaseCount('lesson_progress', 0);
    $this->assertDatabaseCount('enrollments', 0);
})->with([
    'not a preview' => [[], ['is_preview' => false]],
    'unpublished lesson' => [[], ['is_published' => false]],
    'quiz marked preview' => [[], ['lesson_type' => 'quiz']],
    'draft course' => [['status' => 'draft'], []],
    'pending course' => [['status' => 'pending'], []],
    'rejected course' => [['status' => 'rejected'], []],
]);

test('previewing never changes existing enrollment or progress', function (string $status) {
    [$student, $course, $lesson] = freePreviewFixture();
    $enrollment = Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => $status,
        'enrolled_at' => now()->subDay(),
        'progress_percentage' => 40,
    ]);
    $progress = LessonProgress::create([
        'student_id' => $student->id,
        'lesson_id' => $lesson->id,
        'status' => 'in_progress',
        'started_at' => now()->subHour(),
    ]);
    $beforeEnrollment = $enrollment->fresh()->getAttributes();
    $beforeProgress = $progress->fresh()->getAttributes();

    $this->actingAs($student)->get(route('student.lesson.preview', $lesson))->assertOk();

    expect($enrollment->fresh()->getAttributes())->toBe($beforeEnrollment)
        ->and($progress->fresh()->getAttributes())->toBe($beforeProgress);
})->with(['active', 'completed', 'cancelled']);

test('a preview cannot bypass enrolled learning, completion or quiz access', function () {
    [$student, $course, $lesson] = freePreviewFixture();
    $quizLesson = $lesson->replicate()->fill(['lesson_type' => 'quiz', 'lesson_order' => 2]);
    $quizLesson->save();
    $quiz = Quiz::create([
        'course_id' => $course->id,
        'lesson_id' => $quizLesson->id,
        'title' => 'Enrollment-only quiz',
        'passing_score' => 70,
        'is_published' => true,
    ]);

    $this->actingAs($student)->get(route('student.lesson.preview', $lesson))->assertOk();
    $this->get(route('student.learn.course', $course))->assertForbidden();
    $this->get(route('student.lesson.view', $lesson))->assertForbidden();
    $this->post(route('student.lesson.complete', $lesson))->assertForbidden();
    $this->get(route('student.lesson.preview', $quizLesson))->assertNotFound();
    $this->get(route('student.quiz.take', $quiz))->assertForbidden();
    $this->assertDatabaseCount('lesson_progress', 0);
    $this->assertDatabaseCount('quiz_results', 0);
});

test('preview routes keep the existing authentication and verification requirements', function () {
    [, , $lesson] = freePreviewFixture();
    $url = route('student.lesson.preview', $lesson);
    $fileUrl = route('student.lesson.preview.file', $lesson);

    $this->get($url)->assertRedirect(route('login'));
    $this->get($fileUrl)->assertRedirect(route('login'));
    $this->actingAs(pathwiseUser('student', ['email_verified_at' => null]));
    $this->get($url)->assertRedirect(route('verification.notice'));
    $this->get($fileUrl)->assertRedirect(route('verification.notice'));
});

test('management roles cannot use the student preview routes', function (string $role) {
    [, , $lesson] = freePreviewFixture();

    $this->actingAs(pathwiseUser($role));
    $this->get(route('student.lesson.preview', $lesson))->assertForbidden();
    $this->get(route('student.lesson.preview.file', $lesson))->assertForbidden();
})->with(['teacher', 'admin', 'super_admin']);

test('preview reading content is escaped', function () {
    $content = '<script>alert("preview")</script><h1>HTML example</h1>';
    [$student, , $lesson] = freePreviewFixture([], ['content' => $content]);

    $this->actingAs($student)->get(route('student.lesson.preview', $lesson))
        ->assertOk()
        ->assertSee(e($content), false)
        ->assertDontSee($content, false);
});

test('a video preview displays its video without recording progress', function () {
    [$student, , $lesson] = freePreviewFixture([], [
        'lesson_type' => 'video',
        'video_url' => 'https://www.youtube.com/watch?v=abcdefghijk',
    ]);

    $this->actingAs($student)->get(route('student.lesson.preview', $lesson))
        ->assertOk()
        ->assertSee('src="https://www.youtube.com/embed/abcdefghijk"', false)
        ->assertSee('href="'.e($lesson->video_url).'"', false);
    $this->assertDatabaseCount('lesson_progress', 0);
});

test('a document preview uses its protected file URL and leaves enrolled files protected', function () {
    fakeFileDisks();
    [$student, , $lesson] = freePreviewFixture([], [
        'lesson_type' => 'document',
        'file_path' => 'lesson-files/preview.pdf',
    ]);
    Storage::disk('local')->put($lesson->file_path, '%PDF-1.4 Preview document');
    $fileUrl = route('student.lesson.preview.file', $lesson);

    $this->actingAs($student)->get(route('student.lesson.preview', $lesson))
        ->assertOk()
        ->assertSee('src="'.$fileUrl.'"', false);
    $response = $this->get($fileUrl)->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->streamedContent())->toBe('%PDF-1.4 Preview document')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store');
    $this->get(route('files.lesson', $lesson))->assertForbidden();
    $this->assertDatabaseCount('lesson_progress', 0);
    $this->assertDatabaseCount('enrollments', 0);
});

test('a document file cannot bypass preview availability', function (array $courseAttributes, array $lessonAttributes) {
    fakeFileDisks();
    [$student, , $lesson] = freePreviewFixture($courseAttributes, array_merge([
        'lesson_type' => 'document',
        'file_path' => 'lesson-files/closed-preview.pdf',
    ], $lessonAttributes));
    Storage::disk('local')->put($lesson->file_path, '%PDF-1.4 Closed document');

    $this->actingAs($student)->get(route('student.lesson.preview.file', $lesson))->assertNotFound();
})->with([
    'not preview' => [[], ['is_preview' => false]],
    'unpublished lesson' => [[], ['is_published' => false]],
    'unpublished course' => [['status' => 'draft'], []],
    'reading lesson with a file' => [[], ['lesson_type' => 'text']],
    'quiz lesson with a file' => [[], ['lesson_type' => 'quiz']],
]);

test('turning off a preview closes its page and document immediately', function () {
    fakeFileDisks();
    [$student, , $lesson] = freePreviewFixture([], [
        'lesson_type' => 'document',
        'file_path' => 'lesson-files/revoked-preview.pdf',
    ]);
    Storage::disk('local')->put($lesson->file_path, '%PDF-1.4 Revoked document');

    $this->actingAs($student)->get(route('student.lesson.preview', $lesson))->assertOk();
    $this->get(route('student.lesson.preview.file', $lesson))->assertOk();
    $lesson->update(['is_preview' => false]);
    $this->get(route('student.lesson.preview', $lesson))->assertNotFound();
    $this->get(route('student.lesson.preview.file', $lesson))->assertNotFound();
});
