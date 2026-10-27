<?php

use App\Models\Enrollment;

/*
| The administrator's Enrollments page lists enrollments newest first by
| enrollment date, shows that date, and can be narrowed with quick date
| ranges (Today, Last 7/30/90 days, This year).
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
        ->assertSee('3 enrollments · newest first');
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

test('the quick ranges keep only enrollments from that period', function () {
    $this->travelTo('2026-10-27 12:00:00');

    datedEnrollment('Tara Today', '2026-10-27 08:00:00');
    datedEnrollment('Wes Week', '2026-10-21 08:00:00');      // 6 days ago
    datedEnrollment('Mona Month', '2026-09-28 08:00:00');    // 29 days ago
    datedEnrollment('Quinn Quarter', '2026-08-01 08:00:00'); // 87 days ago
    datedEnrollment('Yara Year', '2026-01-02 08:00:00');
    datedEnrollment('Lars LastYear', '2025-12-31 08:00:00');

    $admin = pathwiseUser('admin');

    $expected = [
        'today' => ['Tara Today'],
        '7d' => ['Tara Today', 'Wes Week'],
        '30d' => ['Tara Today', 'Wes Week', 'Mona Month'],
        '90d' => ['Tara Today', 'Wes Week', 'Mona Month', 'Quinn Quarter'],
        'year' => ['Tara Today', 'Wes Week', 'Mona Month', 'Quinn Quarter', 'Yara Year'],
    ];

    $everyone = ['Tara Today', 'Wes Week', 'Mona Month', 'Quinn Quarter', 'Yara Year', 'Lars LastYear'];

    foreach ($expected as $range => $names) {
        $response = $this->actingAs($admin)
            ->get(route('enrollments.index', ['range' => $range]))
            ->assertOk();

        foreach ($everyone as $name) {
            in_array($name, $names, true)
                ? $response->assertSee($name)
                : $response->assertDontSee($name);
        }
    }

    $this->actingAs($admin)
        ->get(route('enrollments.index'))
        ->assertSee('Lars LastYear')
        ->assertSee('6 enrollments · newest first');
});

test('the selected range is highlighted and named in the summary', function () {
    $this->travelTo('2026-10-27 12:00:00');
    datedEnrollment('Mona Month', '2026-10-10 08:00:00');

    $this->actingAs(pathwiseUser('admin'))
        ->get(route('enrollments.index', ['range' => '30d']))
        ->assertOk()
        ->assertSee('aria-current="page"', false)
        ->assertSee('1 enrollment in the last 30 days · newest first');
});

test('a range with no enrollments says so', function () {
    $this->travelTo('2026-10-27 12:00:00');
    datedEnrollment('Olive Oldest', '2026-08-01 08:00:00');

    $this->actingAs(pathwiseUser('admin'))
        ->get(route('enrollments.index', ['range' => 'today']))
        ->assertOk()
        ->assertSee('No enrollments today.');
});

test('an unknown range shows all enrollments', function () {
    datedEnrollment('Olive Oldest', '2020-08-01 08:00:00');

    $this->actingAs(pathwiseUser('admin'))
        ->get(route('enrollments.index', ['range' => 'forever']))
        ->assertOk()
        ->assertSee('Olive Oldest')
        ->assertSee('1 enrollment · newest first');
});
