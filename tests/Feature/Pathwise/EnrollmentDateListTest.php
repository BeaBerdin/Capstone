<?php

use App\Models\Enrollment;

/*
| The administrator's Enrollments page lists enrollments newest first by
| enrollment date, shows that date, and can be filtered by a date range.
*/

function datedEnrollment(string $studentName, ?string $enrolledAt, string $status = 'active'): Enrollment
{
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published']);

    return Enrollment::create([
        'student_id' => pathwiseUser('student', ['name' => $studentName])->id,
        'course_id' => $course->id,
        'status' => $status,
        'enrolled_at' => $enrolledAt,
        'completed_at' => $status === 'completed' ? '2026-10-20 09:00:00' : null,
        'progress_percentage' => $status === 'completed' ? 100 : 0,
    ]);
}

test('enrollments are listed newest first with their enrollment date', function () {
    datedEnrollment('Olive Oldest', '2026-08-01 08:00:00');
    datedEnrollment('Nina Newest', '2026-10-15 08:00:00', 'completed');
    datedEnrollment('Mia Middle', '2026-09-10 08:00:00');

    $this->actingAs(pathwiseUser('admin'))
        ->get(route('enrollments.index'))
        ->assertOk()
        ->assertSeeInOrder(['Nina Newest', 'Mia Middle', 'Olive Oldest'])
        ->assertSee('Oct 15, 2026')
        ->assertSee('Completed Oct 20, 2026')
        ->assertSee('3 enrollments');
});

test('an enrollment without an enrolled_at date falls back to its creation date', function () {
    $this->travelTo(now()->setDate(2026, 7, 4));
    datedEnrollment('Uma Undated', null);
    $this->travelBack();

    $this->actingAs(pathwiseUser('admin'))
        ->get(route('enrollments.index'))
        ->assertOk()
        ->assertSee('Jul 04, 2026');
});

test('enrollments can be filtered by a date range', function () {
    datedEnrollment('Olive Oldest', '2026-08-01 08:00:00');
    datedEnrollment('Mia Middle', '2026-09-10 08:00:00');
    datedEnrollment('Nina Newest', '2026-10-15 08:00:00');

    $this->actingAs(pathwiseUser('admin'))
        ->get(route('enrollments.index', ['from' => '2026-09-01', 'to' => '2026-09-30']))
        ->assertOk()
        ->assertSee('Mia Middle')
        ->assertDontSee('Olive Oldest')
        ->assertDontSee('Nina Newest')
        ->assertSee('1 enrollment enrolled from Sep 01, 2026 to Sep 30, 2026');
});

test('a date range with no enrollments says so', function () {
    datedEnrollment('Olive Oldest', '2026-08-01 08:00:00');

    $this->actingAs(pathwiseUser('admin'))
        ->get(route('enrollments.index', ['from' => '2025-01-01', 'to' => '2025-01-31']))
        ->assertOk()
        ->assertSee('No enrollments in this date range.');
});

test('the end date cannot be before the start date', function () {
    $this->actingAs(pathwiseUser('admin'))
        ->from(route('enrollments.index'))
        ->get(route('enrollments.index', ['from' => '2026-10-01', 'to' => '2026-09-01']))
        ->assertRedirect(route('enrollments.index'))
        ->assertSessionHasErrors('to');
});
