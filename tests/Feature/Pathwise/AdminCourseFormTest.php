<?php

use App\Models\Course;

/*
| The administrator's course form (Courses > Add / Edit).
*/

function adminCourseInput(Course $course, array $overrides = []): array
{
    return array_merge([
        'category_id' => $course->category_id,
        'title' => $course->title,
        'description' => 'A course description.',
        'difficulty_level' => 'beginner',
        'price' => 0,
        'status' => 'draft',
    ], $overrides);
}

test('the admin course form does not offer the unused Approved status', function () {
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'pending']);
    $admin = pathwiseUser('admin');

    foreach ([route('courses.create'), route('courses.edit', $course)] as $page) {
        $this->actingAs($admin)->get($page)
            ->assertOk()
            ->assertDontSee('value="approved"', false)
            ->assertSee('value="draft"', false)
            ->assertSee('value="pending"', false)
            ->assertSee('value="published"', false);
    }
});

test('the Approved status is refused when an administrator saves a course', function () {
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'pending']);
    $admin = pathwiseUser('admin');

    $this->actingAs($admin)
        ->put(route('courses.update', $course), adminCourseInput($course, ['status' => 'approved']))
        ->assertSessionHasErrors('status');

    expect($course->fresh()->status)->toBe('pending');

    $this->actingAs($admin)
        ->post(route('courses.store'), adminCourseInput($course, ['title' => 'Brand New Course', 'status' => 'approved']))
        ->assertSessionHasErrors('status');

    expect(Course::where('title', 'Brand New Course')->exists())->toBeFalse();
});

test('the other statuses are still accepted on the admin course form', function (string $status) {
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'pending']);

    $this->actingAs(pathwiseUser('admin'))
        ->put(route('courses.update', $course), adminCourseInput($course, ['status' => $status]))
        ->assertSessionHasNoErrors();

    expect($course->fresh()->status)->toBe($status);
})->with(['draft', 'pending', 'rejected', 'published']);
