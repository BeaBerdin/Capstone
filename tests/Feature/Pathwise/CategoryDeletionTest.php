<?php

use App\Models\Course;
use App\Models\CourseCategory;

/*
| Course categories: a category that still has courses cannot be deleted
| (courses.category_id cascades on delete, so deleting it would remove the
| courses too). An empty category can still be deleted.
*/

test('deleting a category that still has a course is refused and the course is kept', function (string $status) {
    $admin = pathwiseUser('super_admin');
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => $status]);
    $category = $course->category;

    $this->actingAs($admin)
        ->delete(route('course-categories.destroy', $category))
        ->assertRedirect(route('course-categories.index'))
        ->assertSessionHas('error', fn (string $message) => str_contains($message, 'cannot be deleted because it still has 1 course'));

    expect(CourseCategory::find($category->id))->not->toBeNull()
        ->and(Course::find($course->id))->not->toBeNull();
})->with(['draft', 'pending', 'rejected', 'published']);

test('the refusal message is shown on the Categories page', function () {
    $admin = pathwiseUser('super_admin');
    $course = pathwiseCourse(pathwiseUser('teacher'));

    $this->actingAs($admin)
        ->followingRedirects()
        ->delete(route('course-categories.destroy', $course->category))
        ->assertOk()
        ->assertSee('cannot be deleted because it still has 1 course');
});

test('an empty category can still be deleted', function () {
    $admin = pathwiseUser('super_admin');
    $category = CourseCategory::create(['name' => 'Unused Category', 'is_active' => true]);

    $this->actingAs($admin)
        ->delete(route('course-categories.destroy', $category))
        ->assertRedirect(route('course-categories.index'))
        ->assertSessionHas('success', 'Category deleted successfully.');

    expect(CourseCategory::find($category->id))->toBeNull();
});
