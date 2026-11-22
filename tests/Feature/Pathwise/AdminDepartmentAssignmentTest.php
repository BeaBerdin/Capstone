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
        ->assertDontSee('No department assigned');
});

test('an admin without a department is told so (they manage no courses), never "campus-wide"', function () {
    $department = Department::create(['name' => 'College of Computer Studies']);
    pathwiseCourse(pathwiseUser('teacher', ['department_id' => $department->id]), ['title' => 'Scoped Course', 'status' => 'pending']);
    $admin = pathwiseUser('admin', ['department_id' => null]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Department Assignment: No department assigned — contact the Super Admin')
        ->assertDontSee('Campus-wide')
        // Secure scoping is unchanged: no courses for an admin without a department.
        ->assertDontSee('Scoped Course');

    expect(App\Models\Course::manageableBy($admin)->count())->toBe(0);
});

test('the Super Admin user list flags teachers and admins without a department', function () {
    $department = Department::create(['name' => 'College of Computer Studies']);
    pathwiseUser('teacher', ['name' => 'Teacher NoDept', 'department_id' => null]);
    pathwiseUser('admin', ['name' => 'Admin NoDept', 'department_id' => null]);
    pathwiseUser('teacher', ['name' => 'Teacher WithDept', 'department_id' => $department->id]);
    pathwiseUser('student', ['name' => 'Student NoDept', 'department_id' => null]);

    $html = $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('users.index'))
        ->assertOk()
        ->getContent();

    // Two badges: the teacher and the admin without a department (students
    // and Super Admins never need one).
    expect(substr_count($html, 'data-test="no-department-badge"'))->toBe(2);
});

test('a teacher without a department is warned after submitting a course', function () {
    $teacher = pathwiseApprovedTeacher(['department_id' => null]);
    $course = pathwiseCourse($teacher);
    App\Models\Lesson::create([
        'course_id' => $course->id, 'title' => 'Lesson 1', 'content' => 'Content', 'lesson_type' => 'text',
        'lesson_order' => 1, 'duration_minutes' => 10, 'is_preview' => false, 'is_published' => true,
    ]);

    $this->actingAs($teacher)
        ->post(route('teacher.courses.submit', $course))
        ->assertSessionHas('success')
        ->assertSessionHas('warning', 'Your account has no department, so no department head can review this course yet. Please contact the EDP office.');

    // The warning is shown on My Courses.
    $this->actingAs($teacher)
        ->withSession(['warning' => 'Your account has no department, so no department head can review this course yet. Please contact the EDP office.'])
        ->get(route('teacher.my-courses'))
        ->assertOk()
        ->assertSee('data-test="flash-warning"', false);
});

test('a teacher with a department gets no warning', function () {
    $department = Department::create(['name' => 'College of Computer Studies']);
    $teacher = pathwiseApprovedTeacher(['department_id' => $department->id]);
    $course = pathwiseCourse($teacher);
    App\Models\Lesson::create([
        'course_id' => $course->id, 'title' => 'Lesson 1', 'content' => 'Content', 'lesson_type' => 'text',
        'lesson_order' => 1, 'duration_minutes' => 10, 'is_preview' => false, 'is_published' => true,
    ]);

    $this->actingAs($teacher)
        ->post(route('teacher.courses.submit', $course))
        ->assertSessionHas('success')
        ->assertSessionMissing('warning');
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
