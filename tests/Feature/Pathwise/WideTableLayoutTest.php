<?php

use App\Models\Enrollment;

/*
| The Course Students and User Management tables have six columns. With 24px
| of padding on each side of every cell they were about 50px wider than the
| content area at a 1440px window, so the last column (Action / Registered)
| was cut off. Their cells use 16px side padding (px-4) instead; this test
| keeps that from regressing.
*/

test('the Course Students table uses compact cell padding so the Action column fits', function () {
    $teacher = pathwiseApprovedTeacher();
    $student = pathwiseUser('student', ['name' => 'Bea Berdin']);
    $course = pathwiseCourse($teacher, ['status' => 'published']);
    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 50,
    ]);

    $html = $this->actingAs($teacher)
        ->get(route('teacher.course.students', $course))
        ->assertOk()
        ->assertSee('Bea Berdin')
        ->getContent();

    $table = substr($html, strpos($html, '<table'), strpos($html, '</table>') - strpos($html, '<table'));

    expect($table)->toContain('Action')
        ->and($table)->toContain('class="px-4 py-5"')
        ->and($table)->not->toContain('px-6 py-5')
        ->and($table)->not->toContain('px-6 py-4');
});

test('the User Management table uses compact cell padding so the Registered column fits', function () {
    $superAdmin = pathwiseUser('super_admin');
    pathwiseApprovedTeacher( ['name' => 'Paolo Reyes']);

    $html = $this->actingAs($superAdmin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee('Paolo Reyes')
        ->getContent();

    $table = substr($html, strpos($html, '<table'), strpos($html, '</table>') - strpos($html, '<table'));

    expect($table)->toContain('Registered')
        ->and($table)->toContain('class="px-4 py-5"')
        ->and($table)->not->toContain('class="px-6 py-5"')
        ->and($table)->not->toContain('class="px-6 py-4"');
});
