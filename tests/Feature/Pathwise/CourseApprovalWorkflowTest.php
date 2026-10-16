<?php

use App\Models\Lesson;

/*
| Manuscript: Figure 25 (Manage Courses and Lessons), Figure 28 (Submit
| Course for Approval), Figure 29 (Manage Course Categories and Approvals).
*/

function addLesson($course, bool $published = true): Lesson
{
    return Lesson::create([
        'course_id' => $course->id,
        'title' => 'Lesson '.uniqid(),
        'content' => 'Lesson content',
        'lesson_type' => 'text',
        'lesson_order' => 1,
        'duration_minutes' => 30,
        'is_preview' => false,
        'is_published' => $published,
    ]);
}

function validCourseInput(array $overrides = []): array
{
    $category = \App\Models\CourseCategory::firstOrCreate(['name' => 'Information Technology']);

    return array_merge([
        'title' => 'Updated Course Title',
        'description' => 'Updated description',
        'category_id' => $category->id,
        'difficulty_level' => 'intermediate',
        'price' => 0,
        'estimated_hours' => 8,
        'certificate_available' => 1,
    ], $overrides);
}

test('a teacher can submit a complete draft course for approval', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher);
    addLesson($course);

    $this->actingAs($teacher)
        ->post(route('teacher.courses.submit', $course))
        ->assertSessionHas('success');

    expect($course->fresh()->status)->toBe('pending');
});

test('a course with unpublished lessons cannot be submitted', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher);
    addLesson($course, published: false);

    $this->actingAs($teacher)
        ->post(route('teacher.courses.submit', $course))
        ->assertSessionHas('error');

    expect($course->fresh()->status)->toBe('draft');
});

test('a teacher can open the edit page of a draft course', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['title' => 'My Draft Course']);

    $this->actingAs($teacher)
        ->get(route('teacher.courses.edit', $course))
        ->assertOk()
        ->assertSee('My Draft Course');
});

test('a teacher can update a draft course', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher);

    $this->actingAs($teacher)
        ->put(route('teacher.courses.update', $course), validCourseInput())
        ->assertRedirect(route('teacher.my-courses'))
        ->assertSessionHasNoErrors();

    expect($course->fresh()->title)->toBe('Updated Course Title');
});

test('course details are locked while the course is pending approval', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'pending', 'title' => 'Locked Title']);

    $this->actingAs($teacher)
        ->get(route('teacher.courses.edit', $course))
        ->assertRedirect(route('teacher.my-courses'));

    $this->actingAs($teacher)
        ->put(route('teacher.courses.update', $course), validCourseInput())
        ->assertRedirect(route('teacher.my-courses'))
        ->assertSessionHas('error');

    expect($course->fresh()->title)->toBe('Locked Title');
});

test('a teacher cannot edit another teacher\'s course', function () {
    $owner = pathwiseUser('teacher');
    $otherTeacher = pathwiseUser('teacher');
    $course = pathwiseCourse($owner);

    $this->actingAs($otherTeacher)
        ->get(route('teacher.courses.edit', $course))
        ->assertForbidden();
});

test('returning a course for revision requires feedback', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'pending']);

    $this->actingAs(pathwiseUser('admin'))
        ->post(route('courses.reject', $course), ['remarks' => ''])
        ->assertSessionHasErrors('remarks');

    expect($course->fresh()->status)->toBe('pending');
});

test('returning a course saves the feedback and notifies the teacher', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'pending']);

    $this->actingAs(pathwiseUser('admin'))
        ->post(route('courses.reject', $course), ['remarks' => 'Please add learning outcomes.'])
        ->assertRedirect(route('courses.index'));

    $course->refresh();

    expect($course->status)->toBe('rejected')
        ->and($course->review_remarks)->toBe('Please add learning outcomes.');

    $notification = $teacher->fresh()->notifications()->first();

    expect($notification)->not->toBeNull()
        ->and($notification->data['remarks'])->toBe('Please add learning outcomes.')
        ->and($notification->data['message'])->toContain('Please add learning outcomes.');
});

test('approving a course publishes it and clears old feedback', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'pending', 'review_remarks' => 'Old feedback']);

    $this->actingAs(pathwiseUser('admin'))
        ->post(route('courses.approve', $course))
        ->assertRedirect(route('courses.index'));

    $course->refresh();

    expect($course->status)->toBe('published')
        ->and($course->review_remarks)->toBeNull();
});

test('a teacher cannot approve courses', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'pending']);

    $this->actingAs($teacher)
        ->post(route('courses.approve', $course))
        ->assertForbidden();
});
