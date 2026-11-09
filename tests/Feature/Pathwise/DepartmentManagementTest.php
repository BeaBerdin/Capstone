<?php

use App\Models\Department;

/*
| Departments (Super Admin): create, edit and delete departments.
*/

test('the Super Admin can create a department', function () {
    $this->actingAs(pathwiseUser('super_admin'))
        ->post(route('departments.store'), ['name' => 'College of Computer Studies', 'description' => 'CCS'])
        ->assertRedirect(route('departments.index'))
        ->assertSessionHas('success', 'Department created successfully.');

    expect(Department::where('name', 'College of Computer Studies')->value('description'))->toBe('CCS');
});

test('a department needs a unique name', function ($name) {
    Department::create(['name' => 'College of Nursing']);

    $this->actingAs(pathwiseUser('super_admin'))
        ->post(route('departments.store'), ['name' => $name])
        ->assertSessionHasErrors('name');

    expect(Department::count())->toBe(1);
})->with(['', 'College of Nursing']);

test('the Super Admin can edit a department', function () {
    $department = Department::create(['name' => 'Computer Studies']);

    $this->actingAs(pathwiseUser('super_admin'))
        ->put(route('departments.update', $department), ['name' => 'College of Computer Studies', 'description' => 'Updated'])
        ->assertRedirect(route('departments.index'))
        ->assertSessionHas('success', 'Department updated successfully.');

    expect($department->fresh()->name)->toBe('College of Computer Studies');
});

test('an edited department cannot take an empty name or the name of another department', function ($name) {
    Department::create(['name' => 'College of Nursing']);
    $department = Department::create(['name' => 'Computer Studies']);

    $this->actingAs(pathwiseUser('super_admin'))
        ->put(route('departments.update', $department), ['name' => $name])
        ->assertSessionHasErrors('name');

    expect($department->fresh()->name)->toBe('Computer Studies');
})->with(['', 'College of Nursing']);

test('the Super Admin can delete an empty department', function () {
    $department = Department::create(['name' => 'Senior High']);

    $this->actingAs(pathwiseUser('super_admin'))
        ->delete(route('departments.destroy', $department))
        ->assertRedirect(route('departments.index'))
        ->assertSessionHas('success', 'Department deleted successfully.');

    expect(Department::find($department->id))->toBeNull();
});

test('a department with assigned users cannot be deleted', function () {
    $department = Department::create(['name' => 'College of Computer Studies']);
    pathwiseUser('teacher', ['department_id' => $department->id]);

    $this->actingAs(pathwiseUser('super_admin'))
        ->delete(route('departments.destroy', $department))
        ->assertSessionHas('error', 'Cannot delete this department because users are assigned to it.');

    expect(Department::find($department->id))->not->toBeNull();
});

test('only the Super Admin can create, edit or delete departments', function (string $role) {
    $department = Department::create(['name' => 'Computer Studies']);
    $user = pathwiseUser($role);

    $this->actingAs($user)->post(route('departments.store'), ['name' => 'New Department'])->assertForbidden();
    $this->actingAs($user)->put(route('departments.update', $department), ['name' => 'Renamed'])->assertForbidden();
    $this->actingAs($user)->delete(route('departments.destroy', $department))->assertForbidden();

    expect(Department::count())->toBe(1)
        ->and($department->fresh()->name)->toBe('Computer Studies');
})->with(['admin', 'teacher', 'student']);
