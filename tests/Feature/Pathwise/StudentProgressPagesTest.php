<?php

use App\Models\Enrollment;

/*
| Finalization: student progress pages found broken in the responsive check.
| - Manuscript Fig. 36 / Table 24: the Administrator views student progress
|   reports for every course, not only courses the administrator teaches.
| - Fig. 37: the teacher opens one student's progress from Course Students.
*/

function progressEnrollment(): array
{
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student', ['name' => 'Progress Student']);
    $course = pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Progress Course']);

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 40,
    ]);

    return [$teacher, $student, $course];
}

test('the administrator progress report lists enrollments in every teacher\'s courses', function () {
    [, $student, $course] = progressEnrollment();

    $this->actingAs(pathwiseUser('admin'))
        ->get(route('student-progress.index'))
        ->assertOk()
        ->assertSeeText('Progress Student')
        ->assertSeeText('Progress Course');
});

test('a teacher progress list shows only the teacher\'s own courses', function () {
    [$teacher] = progressEnrollment();
    [, , $otherCourse] = progressEnrollment();
    $otherCourse->update(['title' => 'Someone Else Course']);

    $this->actingAs($teacher)
        ->get(route('teacher.student-progress.index'))
        ->assertOk()
        ->assertSeeText('Progress Course')
        ->assertDontSeeText('Someone Else Course');
});

test('a teacher can open one enrolled student\'s progress page', function () {
    [$teacher, $student, $course] = progressEnrollment();

    $this->actingAs($teacher)
        ->get(route('teacher.student.progress', [$course, $student]))
        ->assertOk()
        ->assertSeeText('Progress Student')
        ->assertSeeText('Progress Course');
});

test('the progress page of a student who is not enrolled is not found', function () {
    [$teacher, , $course] = progressEnrollment();

    $this->actingAs($teacher)
        ->get(route('teacher.student.progress', [$course, pathwiseUser('student')]))
        ->assertNotFound();
});
