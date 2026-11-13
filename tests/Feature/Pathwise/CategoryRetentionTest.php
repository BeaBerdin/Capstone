<?php

use App\Exceptions\DeletionBlockedException;
use App\Models\Assignment;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    fakeFileDisks();
    Notification::fake();
});

test('admin category deletion cannot cascade into courses even without student records', function () {
    $course = pathwiseCourse(pathwiseUser('teacher'), ['thumbnail' => 'courses/category-child.jpg']);
    Storage::disk('public')->put($course->thumbnail, 'retained thumbnail');

    $this->actingAs(pathwiseUser('admin'))->delete(route('course-categories.destroy', $course->category_id))
        ->assertRedirect(route('course-categories.index'))->assertSessionHas('error');

    $this->assertDatabaseHas('course_categories', ['id' => $course->category_id]);
    $this->assertDatabaseHas('courses', ['id' => $course->id]);
    Storage::disk('public')->assertExists('courses/category-child.jpg');
    $this->get(route('course-categories.index'))->assertOk()->assertSee('contains courses');
});

test('direct category model deletion refuses courses despite a stale empty relationship', function () {
    $category = CourseCategory::create(['name' => 'Protected category']);
    $category->load('courses');
    $course = pathwiseCourse(pathwiseUser('teacher'), ['category_id' => $category->id]);
    $enrollment = Enrollment::create([
        'course_id' => $course->id, 'student_id' => pathwiseUser('student')->id,
        'status' => 'cancelled', 'enrolled_at' => now(), 'progress_percentage' => 40,
    ]);

    expect(fn () => $category->delete())->toThrow(DeletionBlockedException::class)
        ->and($category->fresh())->not->toBeNull()
        ->and($course->fresh())->not->toBeNull()
        ->and($enrollment->fresh()->status)->toBe('cancelled');
});

test('an admin can still delete an empty category', function () {
    $category = CourseCategory::create(['name' => 'Unused category']);

    $this->actingAs(pathwiseUser('admin'))->delete(route('course-categories.destroy', $category))
        ->assertRedirect(route('course-categories.index'))->assertSessionHas('success')->assertSessionMissing('error');

    $this->assertDatabaseMissing('course_categories', ['id' => $category->id]);
});

test('direct Eloquent deletion still allows an empty category', function () {
    $category = CourseCategory::create(['name' => 'Unused category']);

    expect($category->delete())->toBeTrue();
    $this->assertDatabaseMissing('course_categories', ['id' => $category->id]);
});

test('non-admin roles cannot delete categories', function (string $role) {
    $category = CourseCategory::create(['name' => 'Retained category']);

    $this->actingAs(pathwiseUser($role))->delete(route('course-categories.destroy', $category))->assertForbidden();

    $this->assertDatabaseHas('course_categories', ['id' => $category->id]);
})->with(['student', 'teacher', 'super_admin']);

test('guests cannot reach any C2 deletion route', function (string $routeName, string $recordType) {
    $course = pathwiseCourse(pathwiseUser('teacher'));
    $assignment = Assignment::create([
        'course_id' => $course->id, 'title' => 'Unused assignment',
        'submission_type' => 'essay', 'max_score' => 100,
    ]);
    $id = match ($recordType) {
        'assignment' => $assignment->id,
        'category' => $course->category_id,
        default => $course->id,
    };

    $this->delete(route($routeName, $id))->assertRedirect(route('login'));

    $this->assertDatabaseHas('courses', ['id' => $course->id]);
    $this->assertDatabaseHas('assignments', ['id' => $assignment->id]);
    $this->assertDatabaseHas('course_categories', ['id' => $course->category_id]);
})->with([
    ['assignments.destroy', 'assignment'],
    ['teacher.assignments.destroy', 'assignment'],
    ['courses.destroy', 'course'],
    ['teacher.courses.destroy', 'course'],
    ['course-categories.destroy', 'category'],
]);
