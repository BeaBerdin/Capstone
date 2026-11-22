<?php

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;

/*
| Free-preview lessons: a student who is not enrolled can open a lesson that
| the teacher marked "Free preview", read only. Quizzes, unpublished lessons,
| unpublished courses and non-preview lessons stay locked, and nothing is saved.
*/

function previewCourseFixture(array $courseAttributes = []): array
{
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, array_merge(['status' => 'published', 'price' => 0], $courseAttributes));

    $make = fn (array $attributes) => Lesson::create(array_merge([
        'course_id' => $course->id,
        'content' => 'Lesson body text',
        'lesson_type' => 'text',
        'duration_minutes' => 10,
        'is_preview' => false,
        'is_published' => true,
    ], $attributes));

    return [
        'course' => $course,
        'preview' => $make(['title' => 'Preview lesson', 'lesson_order' => 1, 'is_preview' => true]),
        'locked' => $make(['title' => 'Locked lesson', 'lesson_order' => 2]),
        'quizPreview' => $make(['title' => 'Preview quiz', 'lesson_order' => 3, 'lesson_type' => 'quiz', 'is_preview' => true]),
        'hiddenPreview' => $make(['title' => 'Hidden preview', 'lesson_order' => 4, 'is_preview' => true, 'is_published' => false]),
    ];
}

test('the course page links a free-preview lesson for a student who is not enrolled', function () {
    $f = previewCourseFixture();

    $this->actingAs(pathwiseUser('student'))
        ->get(route('student.course.show', $f['course']))
        ->assertOk()
        ->assertSee('Preview Lesson')
        ->assertSee(route('student.lesson.view', $f['preview']), false)
        ->assertDontSee(route('student.lesson.view', $f['locked']), false)
        ->assertDontSee(route('student.lesson.view', $f['quizPreview']), false);
});

test('a student who is not enrolled can open a free-preview lesson read only', function () {
    $f = previewCourseFixture();
    $student = pathwiseUser('student');

    $this->actingAs($student)
        ->get(route('student.lesson.view', $f['preview']))
        ->assertOk()
        ->assertSee('Lesson body text')
        ->assertSee('You are previewing this lesson')
        ->assertDontSee(route('student.lesson.complete', $f['preview']), false)
        ->assertDontSee(route('student.lesson.view', $f['locked']), false);

    expect(LessonProgress::where('student_id', $student->id)->count())->toBe(0);
});

test('other lessons stay locked for a student who is not enrolled', function (string $which) {
    $f = previewCourseFixture();

    $status = $this->actingAs(pathwiseUser('student'))
        ->get(route('student.lesson.view', $f[$which]))
        ->status();

    expect($status)->toBeIn([403, 404]);
})->with(['locked', 'quizPreview', 'hiddenPreview']);

test('a preview lesson of an unpublished course cannot be opened', function () {
    $f = previewCourseFixture(['status' => 'pending']);

    expect($this->actingAs(pathwiseUser('student'))->get(route('student.lesson.view', $f['preview']))->status())
        ->toBeIn([403, 404]);
});

test('a student who is not enrolled cannot mark a preview lesson complete', function () {
    $f = previewCourseFixture();
    $student = pathwiseUser('student');

    $this->actingAs($student)
        ->post(route('student.lesson.complete', $f['preview']))
        ->assertForbidden();

    expect(LessonProgress::where('student_id', $student->id)->count())->toBe(0);
});

test('an enrolled student still gets the normal lesson page with progress', function () {
    $f = previewCourseFixture();
    $student = pathwiseUser('student');
    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $f['course']->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    $this->actingAs($student)
        ->get(route('student.lesson.view', $f['preview']))
        ->assertOk()
        ->assertDontSee('You are previewing this lesson')
        ->assertSee(route('student.lesson.complete', $f['preview']), false);

    expect(LessonProgress::where('student_id', $student->id)->where('lesson_id', $f['preview']->id)->exists())->toBeTrue();
});
