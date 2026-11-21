<?php

use App\Models\Assignment;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Submission;

/*
| Pages that previously crashed because their Blade file was missing or
| named differently from the controller.
*/

test('a teacher can open the student removal requests page', function () {
    $this->actingAs(pathwiseApprovedTeacher())
        ->get(route('teacher.student-removal-requests.index'))
        ->assertOk();
});

test('an administrator can open an assignment and a submission', function () {
    $department = Department::create(['name' => 'Computing']);
    $teacher = pathwiseApprovedTeacher( ['department_id' => $department->id]);
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published']);

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    $assignment = Assignment::create([
        'course_id' => $course->id,
        'title' => 'Build a landing page',
        'description' => 'Use HTML and CSS.',
        'max_score' => 100,
        'is_published' => true,
    ]);

    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
        'answer_text' => 'My answer',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $admin = pathwiseUser('admin', ['department_id' => $department->id]);

    $this->actingAs($admin)
        ->get(route('assignments.show', $assignment))
        ->assertOk()
        ->assertSee('Build a landing page');

    $this->actingAs($admin)
        ->get(route('submissions.show', $submission))
        ->assertOk()
        ->assertSee('My answer');
});

test('a student can open the marketplace and their dashboard', function () {
    $student = pathwiseUser('student');

    $this->actingAs($student)->get(route('student.marketplace'))->assertOk();
    $this->actingAs($student)->get(route('student.dashboard'))->assertOk();
});

test('a teacher can open their dashboard and courses', function () {
    $teacher = pathwiseApprovedTeacher();
    pathwiseCourse($teacher);

    $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertOk();
    $this->actingAs($teacher)->get(route('teacher.my-courses'))->assertOk();
});
