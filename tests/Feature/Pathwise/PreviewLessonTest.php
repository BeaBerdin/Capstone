<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/*
| Preview lessons: a student who is not enrolled may open a published
| Reading/Video lesson marked as a preview, read-only, in a published
| course. Activities, quizzes, documents and non-preview lessons stay locked.
*/

beforeEach(function () {
    $this->withoutVite();
});

/** @return array{0: User, 1: Course, 2: Lesson} */
function previewFixture(array $courseAttributes = [], array $lessonAttributes = []): array
{
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher, array_merge(['status' => 'published', 'price' => 0], $courseAttributes));

    $lesson = Lesson::create(array_merge([
        'course_id' => $course->id, 'title' => 'Welcome Reading', 'content' => 'Preview body text.',
        'lesson_type' => 'text', 'lesson_order' => 1, 'is_preview' => true, 'is_published' => true,
    ], $lessonAttributes));

    return [pathwiseUser('student'), $course, $lesson];
}

function previewUrl(Course $course, Lesson $lesson): string
{
    return route('student.lesson.preview', [$course, $lesson]);
}

test('a student who is not enrolled can open a preview reading lesson, read-only', function () {
    [$student, $course, $lesson] = previewFixture();
    $lesson->sections()->create(['type' => 'text', 'body_format' => 'plain', 'body' => 'Section body for preview.', 'position' => 0]);

    $this->actingAs($student)->get(previewUrl($course, $lesson))
        ->assertOk()
        ->assertSee('You are previewing this lesson. Enroll to access the full course.')
        ->assertSee('Section body for preview.')
        ->assertSee('action="'.route('student.enroll', $course).'"', false)
        ->assertDontSee('Mark as Complete')
        ->assertDontSee('action="'.route('student.lesson.complete', $lesson).'"', false)
        ->assertDontSee(route('student.learn.course', $course));
});

test('a paid course preview shows the purchase button instead of enroll', function () {
    [$student, $course, $lesson] = previewFixture(['price' => 499]);

    $this->actingAs($student)->get(previewUrl($course, $lesson))
        ->assertOk()
        ->assertSee('action="'.route('student.transactions.store', $course).'"', false)
        ->assertDontSee('action="'.route('student.enroll', $course).'"', false);
});

test('a preview video lesson opens before enrolling', function () {
    [$student, $course, $lesson] = previewFixture([], [
        'lesson_type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'content' => null,
    ]);

    $this->actingAs($student)->get(previewUrl($course, $lesson))
        ->assertOk()
        ->assertSee('youtube');
});

test('previewing records no lesson progress', function () {
    [$student, $course, $lesson] = previewFixture();

    $this->actingAs($student)->get(previewUrl($course, $lesson))->assertOk();

    expect(LessonProgress::count())->toBe(0)
        ->and(Enrollment::count())->toBe(0);
});

test('a non-preview or unpublished lesson cannot be previewed', function (array $attributes) {
    [$student, $course, $lesson] = previewFixture([], $attributes);

    $this->actingAs($student)->get(previewUrl($course, $lesson))->assertNotFound();
})->with([
    'not a preview' => [['is_preview' => false]],
    'unpublished' => [['is_published' => false]],
]);

test('a preview lesson of an unpublished or under review course cannot be opened', function (string $status) {
    [$student, $course, $lesson] = previewFixture(['status' => $status]);

    $this->actingAs($student)->get(previewUrl($course, $lesson))->assertNotFound();
})->with(['draft', 'pending', 'rejected']);

test('activities, quizzes and documents are never previewable', function (string $type) {
    [$student, $course, $lesson] = previewFixture([], ['lesson_type' => $type]);

    if ($type === 'quiz') {
        Quiz::create(['course_id' => $course->id, 'lesson_id' => $lesson->id, 'title' => 'Q', 'passing_score' => 75, 'is_published' => true]);
    }

    if ($type === 'activity') {
        $lesson->assignments()->create([
            'course_id' => $course->id, 'title' => 'A', 'description' => 'Do it', 'submission_type' => 'essay',
            'answer_format' => 'either', 'max_score' => 100, 'is_activity' => true, 'is_published' => true,
        ]);
    }

    $this->actingAs($student)->get(previewUrl($course, $lesson))->assertNotFound();
})->with(['activity', 'quiz', 'document']);

test('a lesson from another course cannot be previewed through this course', function () {
    [$student, $course, $lesson] = previewFixture();
    $other = pathwiseCourse(pathwiseApprovedTeacher(), ['status' => 'published']);

    $this->actingAs($student)->get(previewUrl($other, $lesson))->assertNotFound();
});

test('a guest is redirected to log in', function () {
    [, $course, $lesson] = previewFixture();

    $this->get(previewUrl($course, $lesson))->assertRedirect(route('login'));
});

test('a teacher cannot use the student preview route', function () {
    [, $course, $lesson] = previewFixture();

    $this->actingAs(pathwiseApprovedTeacher())->get(previewUrl($course, $lesson))->assertForbidden();
});

test('an enrolled student is sent to the normal lesson view', function () {
    [$student, $course, $lesson] = previewFixture();
    Enrollment::create([
        'student_id' => $student->id, 'course_id' => $course->id, 'status' => 'active',
        'enrolled_at' => now(), 'progress_percentage' => 0,
    ]);

    $this->actingAs($student)->get(previewUrl($course, $lesson))
        ->assertRedirect(route('student.lesson.view', $lesson));

    $this->actingAs($student)->get(route('student.lesson.view', $lesson))
        ->assertOk()
        ->assertDontSee('You are previewing this lesson.')
        ->assertSee('Mark as Complete');
});

test('the course page links preview lessons for students who are not enrolled', function () {
    [$student, $course, $lesson] = previewFixture();
    $locked = Lesson::create([
        'course_id' => $course->id, 'title' => 'Locked Reading', 'content' => 'x', 'lesson_type' => 'text',
        'lesson_order' => 2, 'is_preview' => false, 'is_published' => true,
    ]);

    $this->actingAs($student)->get(route('student.course.show', $course))
        ->assertOk()
        ->assertSee('Preview Lesson')
        ->assertSee(previewUrl($course, $lesson), false)
        ->assertDontSee(previewUrl($course, $locked), false)
        ->assertDontSee('Preview available');

    Enrollment::create([
        'student_id' => $student->id, 'course_id' => $course->id, 'status' => 'active',
        'enrolled_at' => now(), 'progress_percentage' => 0,
    ]);

    $this->actingAs($student)->get(route('student.course.show', $course))
        ->assertOk()
        ->assertSee('Open Lesson')
        ->assertSee(route('student.lesson.view', $lesson), false)
        ->assertDontSee(previewUrl($course, $lesson), false);
});

test('files inside a preview lesson open before enrolling, but not files of other lessons', function () {
    Storage::fake('local');
    [$student, $course, $lesson] = previewFixture();

    Storage::disk('local')->put('lesson-sections/preview.png', 'synthetic image');
    $image = $lesson->sections()->create(['type' => 'image', 'file_path' => 'lesson-sections/preview.png', 'original_name' => 'preview.png', 'position' => 0]);

    $locked = Lesson::create([
        'course_id' => $course->id, 'title' => 'Locked Reading', 'content' => 'x', 'lesson_type' => 'text',
        'lesson_order' => 2, 'is_preview' => false, 'is_published' => true,
    ]);
    Storage::disk('local')->put('lesson-sections/locked.png', 'synthetic image');
    $lockedImage = $locked->sections()->create(['type' => 'image', 'file_path' => 'lesson-sections/locked.png', 'original_name' => 'locked.png', 'position' => 0]);

    Storage::disk('local')->put('lesson-files/doc.pdf', 'synthetic document');
    $document = Lesson::create([
        'course_id' => $course->id, 'title' => 'Preview Document', 'lesson_type' => 'document', 'file_path' => 'lesson-files/doc.pdf',
        'lesson_order' => 3, 'is_preview' => true, 'is_published' => true,
    ]);

    $this->actingAs($student)->get(route('files.lesson-section', $image))->assertOk();
    $this->actingAs($student)->get(route('files.lesson-section', $lockedImage))->assertForbidden();
    $this->actingAs($student)->get(route('files.lesson', $document))->assertForbidden();

    // Once the course goes back under review, the preview files close too.
    $course->update(['status' => 'pending']);
    $this->actingAs($student)->get(route('files.lesson-section', $image))->assertForbidden();
});
