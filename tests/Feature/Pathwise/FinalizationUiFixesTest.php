<?php

use App\Models\Certificate;

/*
| Finalization: page defects found in the responsive check.
*/

test('the certificate page prints this site\'s verification address, not the local one', function () {
    config(['app.url' => 'https://pathwise.example']);
    $student = pathwiseUser('student', ['name' => 'Cert Student']);
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'title' => 'Cert Course']);

    App\Models\Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'completed',
        'enrolled_at' => now()->subWeek(),
        'completed_at' => now(),
        'progress_percentage' => 100,
    ]);

    $certificate = Certificate::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'certificate_number' => 'PATH-2026-09999',
        'issued_date' => now()->toDateString(),
        'status' => 'issued',
    ]);

    $this->actingAs($student)
        ->get(route('student.certificate.view', $certificate))
        ->assertOk()
        ->assertSeeText('Cert Student')
        ->assertSeeText('PATH-2026-09999')
        ->assertSee(route('certificates.verify'), false)
        ->assertDontSee('at: pathwise.test', false);
});

test('the departments page does not print stray markdown', function () {
    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('departments.index'))
        ->assertOk()
        ->assertDontSee('```', false);
});
