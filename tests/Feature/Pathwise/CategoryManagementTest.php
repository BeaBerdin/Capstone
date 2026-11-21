<?php

use App\Models\CourseCategory;

/*
| Manage Course Categories (List of Modules): the Administrator adds and edits
| categories. Deleting is covered by CategoryDeletionTest.
*/

test('the administrator can add a category', function () {
    $this->actingAs(pathwiseUser('admin'))
        ->post(route('course-categories.store'), ['name' => 'College of Computer Studies', 'description' => 'Computing courses'])
        ->assertRedirect(route('course-categories.index'))
        ->assertSessionHas('success', 'Category created successfully.');

    expect(CourseCategory::where('name', 'College of Computer Studies')->value('description'))->toBe('Computing courses');
});

test('a category needs a name', function () {
    $this->actingAs(pathwiseUser('admin'))
        ->post(route('course-categories.store'), ['name' => ''])
        ->assertSessionHasErrors('name');

    expect(CourseCategory::count())->toBe(0);
});

test('a new category cannot reuse an existing name', function () {
    CourseCategory::create(['name' => 'College of Computer Studies', 'is_active' => true]);

    $this->actingAs(pathwiseUser('admin'))
        ->post(route('course-categories.store'), ['name' => 'College of Computer Studies'])
        ->assertSessionHasErrors('name');

    expect(CourseCategory::where('name', 'College of Computer Studies')->count())->toBe(1);
});

test('the administrator can edit a category', function () {
    $category = CourseCategory::create(['name' => 'Computer Studies', 'is_active' => true]);

    $this->actingAs(pathwiseUser('admin'))
        ->put(route('course-categories.update', $category), ['name' => 'College of Computer Studies', 'description' => 'Updated'])
        ->assertRedirect(route('course-categories.index'))
        ->assertSessionHas('success', 'Category updated successfully.');

    expect($category->fresh()->name)->toBe('College of Computer Studies')
        ->and($category->fresh()->description)->toBe('Updated');
});

test('an edited category needs a name', function () {
    $category = CourseCategory::create(['name' => 'Computer Studies', 'is_active' => true]);

    $this->actingAs(pathwiseUser('admin'))
        ->put(route('course-categories.update', $category), ['name' => ''])
        ->assertSessionHasErrors('name');

    expect($category->fresh()->name)->toBe('Computer Studies');
});

test('an edited category cannot take the name of another category', function () {
    CourseCategory::create(['name' => 'College of Nursing', 'is_active' => true]);
    $category = CourseCategory::create(['name' => 'Computer Studies', 'is_active' => true]);

    $this->actingAs(pathwiseUser('admin'))
        ->put(route('course-categories.update', $category), ['name' => 'College of Nursing'])
        ->assertSessionHasErrors('name');

    expect(CourseCategory::where('name', 'College of Nursing')->count())->toBe(1);
});

test('only the administrator can add or edit categories', function (string $role) {
    $category = CourseCategory::create(['name' => 'Computer Studies', 'is_active' => true]);
    $user = pathwiseRoleBoundaryUser($role);

    $this->actingAs($user)->post(route('course-categories.store'), ['name' => 'New Category'])->assertForbidden();
    $this->actingAs($user)->put(route('course-categories.update', $category), ['name' => 'Renamed'])->assertForbidden();

    expect(CourseCategory::where('name', 'New Category')->exists())->toBeFalse()
        ->and($category->fresh()->name)->toBe('Computer Studies');
})->with(['teacher', 'student', 'super_admin']);
