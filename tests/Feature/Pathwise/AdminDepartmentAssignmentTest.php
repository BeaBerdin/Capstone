<?php

use App\Models\Department;

/*
| The admin dashboard shows the administrator's department assignment under
| the greeting. Departments have a full name only (no short code).
*/

beforeEach(function () {
    $this->withoutVite();
});

test('an admin with a department sees it under the greeting', function () {
    $department = Department::create(['name' => 'College of Computer Studies']);
    $admin = pathwiseUser('admin', ['department_id' => $department->id]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Department Head!', 'Department Assignment: College of Computer Studies'])
        ->assertSee('data-test="department-assignment"', false)
        ->assertDontSee('Campus-wide (all departments)');
});

test('an admin without a department is shown as campus-wide', function () {
    $admin = pathwiseUser('admin', ['department_id' => null]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Department Assignment: Campus-wide (all departments)');
});

test('other dashboards do not show the department assignment', function () {
    $department = Department::create(['name' => 'College of Computer Studies']);

    $this->actingAs(pathwiseUser('super_admin', ['department_id' => $department->id]))
        ->get(route('super_admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Department Assignment:');

    $this->actingAs(pathwiseApprovedTeacher(['department_id' => $department->id]))
        ->get(route('teacher.dashboard'))
        ->assertOk()
        ->assertDontSee('Department Assignment:');
});
