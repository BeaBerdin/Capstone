<?php

use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\User;
use Illuminate\Support\Facades\Blade;

/*
| B8: Chart.js charts on the Super Admin reports and teacher analytics pages.
| Each chart is drawn from data the page already has, and the page keeps a
| server-rendered fallback (table, list or CSS bars) for when JavaScript or
| Chart.js does not load.
| The clock is frozen at 2026-06-15 10:00 UTC (a Monday) in TestCase.
*/

function chartEnrollment(User $student, App\Models\Course $course, string $status, $createdAt = null): Enrollment
{
    $enrollment = Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => $status,
        'enrolled_at' => $createdAt ?? now(),
        'progress_percentage' => $status === 'completed' ? 100 : 0,
    ]);

    if ($createdAt) {
        $enrollment->forceFill(['created_at' => $createdAt])->save();
    }

    return $enrollment;
}

function chartQuizResults(App\Models\Course $course, array $percentages): void
{
    $quiz = Quiz::create([
        'course_id' => $course->id,
        'title' => 'Checkpoint',
        'passing_score' => 75,
        'is_published' => true,
    ]);

    foreach ($percentages as $percentage) {
        QuizResult::create([
            'student_id' => pathwiseUser('student')->id,
            'quiz_id' => $quiz->id,
            'score' => $percentage,
            'total_items' => 100,
            'percentage' => $percentage,
            'remarks' => $percentage >= 75 ? 'passed' : 'failed',
            'attempt_number' => 1,
            'completed_at' => now(),
        ]);
    }
}

/**
 * The chart data printed in the page's data-chart attribute, by chart name.
 */
function renderedChart(string $html, string $name): ?array
{
    $pattern = '/data-chart-name="'.preg_quote($name, '/').'"\s+data-chart="([^"]*)"/';

    if (! preg_match($pattern, $html, $match)) {
        return null;
    }

    return json_decode(html_entity_decode($match[1], ENT_QUOTES), true);
}

/*
|--------------------------------------------------------------------------
| Super Admin reports
|--------------------------------------------------------------------------
*/

test('the reports page charts enrollments by status', function () {
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published']);

    chartEnrollment(pathwiseUser('student'), $course, 'active');
    chartEnrollment(pathwiseUser('student'), $course, 'active');
    chartEnrollment(pathwiseUser('student'), $course, 'completed');
    chartEnrollment(pathwiseUser('student'), $course, 'cancelled');

    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('reports.index'))
        ->assertOk()
        ->assertViewHas('charts', fn ($charts) => $charts['enrollmentStatus'] === [
            'label' => 'Enrollments',
            'labels' => ['Active', 'Completed', 'Cancelled'],
            'values' => [2, 1, 1],
        ]);
});

test('the enrollment status chart follows the selected period', function () {
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published']);

    chartEnrollment(pathwiseUser('student'), $course, 'active');
    chartEnrollment(pathwiseUser('student'), $course, 'completed', now()->subDays(100));

    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('reports.index', ['range' => '30']))
        ->assertOk()
        ->assertViewHas('charts', fn ($charts) => $charts['enrollmentStatus']['values'] === [1, 0, 0]);
});

test('the reports page charts lessons completed per week from the engagement report', function () {
    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('reports.index'))
        ->assertOk()
        ->assertViewHas('charts', function ($charts) {
            $chart = $charts['weeklyCompletions'];

            return $chart['label'] === 'Lessons completed'
                && count($chart['labels']) === 8
                && end($chart['labels']) === 'Jun 15'
                && $chart['values'] === array_fill(0, 8, 0);
        });
});

test('the reports page prints the chart data and keeps the fallback visible', function () {
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published']);

    chartEnrollment(pathwiseUser('student'), $course, 'active');

    $html = $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('reports.index'))
        ->assertOk()
        // The canvas is hidden until JavaScript draws the chart ...
        ->assertSee('data-chart-canvas hidden', false)
        // ... so without JavaScript the status list and weekly table still show.
        ->assertSee('Enrollments by Status')
        ->assertSee('Week of')
        ->assertSee('Lessons completed')
        ->getContent();

    expect(renderedChart($html, 'enrollment-status'))->toBe([
        'type' => 'hbar',
        'label' => 'Enrollments',
        'labels' => ['Active', 'Completed', 'Cancelled'],
        'values' => [1, 0, 0],
    ])
        ->and(renderedChart($html, 'weekly-completions')['type'])->toBe('bar');
});

test('the reports page shows a message instead of an empty status chart', function () {
    $html = $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('reports.index'))
        ->assertOk()
        ->assertSee('No enrollments in this period.')
        ->getContent();

    expect(renderedChart($html, 'enrollment-status'))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Teacher analytics
|--------------------------------------------------------------------------
*/

test('teacher analytics charts the enrollment trend for the last six months', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published']);

    chartEnrollment(pathwiseUser('student'), $course, 'active', now()->setDate(2026, 3, 10));
    chartEnrollment(pathwiseUser('student'), $course, 'active', now()->setDate(2026, 6, 1));
    chartEnrollment(pathwiseUser('student'), $course, 'completed', now()->setDate(2026, 6, 12));

    // Another teacher's enrollment is not counted.
    chartEnrollment(pathwiseUser('student'), pathwiseCourse(pathwiseUser('teacher')), 'active');

    $this->actingAs($teacher)
        ->get(route('teacher.analytics'))
        ->assertOk()
        ->assertViewHas('charts', fn ($charts) => $charts['enrollmentTrend'] === [
            'label' => 'Enrollments',
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'values' => [0, 0, 1, 0, 0, 2],
        ]);
});

test('teacher analytics charts the quiz score distribution', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published']);

    chartQuizResults($course, [95, 90, 80, 74, 60, 59, 10]);

    // Another teacher's quiz results are not counted.
    chartQuizResults(pathwiseCourse(pathwiseUser('teacher')), [100, 100]);

    $this->actingAs($teacher)
        ->get(route('teacher.analytics'))
        ->assertOk()
        ->assertViewHas('charts', fn ($charts) => $charts['scoreDistribution'] === [
            'label' => 'Quiz attempts',
            'labels' => ['90–100', '75–89', '60–74', 'Below 60'],
            'values' => [2, 1, 2, 2],
        ]);
});

test('teacher analytics charts follow the selected course', function () {
    $teacher = pathwiseUser('teacher');
    $web = pathwiseCourse($teacher, ['status' => 'published']);
    $java = pathwiseCourse($teacher, ['status' => 'published']);

    chartEnrollment(pathwiseUser('student'), $web, 'active');
    chartEnrollment(pathwiseUser('student'), $java, 'active');
    chartQuizResults($web, [95]);
    chartQuizResults($java, [50]);

    $this->actingAs($teacher)
        ->get(route('teacher.analytics', ['course' => $web->id]))
        ->assertOk()
        ->assertViewHas('charts', fn ($charts) => $charts['enrollmentTrend']['values'] === [0, 0, 0, 0, 0, 1]
            && $charts['scoreDistribution']['values'] === [1, 0, 0, 0]);
});

test('teacher analytics prints the chart data and keeps the CSS bars as the fallback', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published']);

    chartEnrollment(pathwiseUser('student'), $course, 'active');
    chartQuizResults($course, [80]);

    $html = $this->actingAs($teacher)
        ->get(route('teacher.analytics'))
        ->assertOk()
        ->assertSee('data-chart-canvas hidden', false)
        ->assertSee('data-chart-fallback', false)
        ->assertSee('pw-a-progress-bar', false)
        ->getContent();

    expect(renderedChart($html, 'enrollment-trend'))->toMatchArray([
        'type' => 'bar',
        'values' => [0, 0, 0, 0, 0, 1],
    ])
        ->and(renderedChart($html, 'score-distribution'))->toMatchArray([
            'type' => 'hbar',
            'labels' => ['90–100', '75–89', '60–74', 'Below 60'],
            'values' => [0, 1, 0, 0],
        ]);
});

test('teacher analytics without data shows the empty states and no charts', function () {
    $html = $this->actingAs(pathwiseUser('teacher'))
        ->get(route('teacher.analytics'))
        ->assertOk()
        ->assertSee('No enrollment activity yet')
        ->assertSee('No quiz results yet')
        ->getContent();

    expect($html)->not->toContain('data-chart=');
});

/*
|--------------------------------------------------------------------------
| Chart component
|--------------------------------------------------------------------------
*/

test('every chart is sent with one hidden canvas and a visible fallback', function () {
    // resources/js/charts.js draws each [data-chart] once per page visit and
    // keeps that state in memory. Livewire saves the page HTML for the Back
    // button, so the server must never send a chart already marked as drawn.
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published']);

    chartEnrollment(pathwiseUser('student'), $course, 'active');
    chartQuizResults($course, [80]);

    $pages = [
        [pathwiseUser('super_admin'), route('reports.index', ['range' => '30'])],
        [$teacher, route('teacher.analytics', ['course' => $course->id, 'range' => '30'])],
    ];

    foreach ($pages as [$user, $url]) {
        $html = $this->actingAs($user)->get($url)->assertOk()->getContent();

        $charts = substr_count($html, ' data-chart="');

        expect($charts)->toBe(2)
            ->and(substr_count($html, '<canvas'))->toBe($charts)
            ->and(substr_count($html, 'data-chart-canvas hidden'))->toBe($charts)
            ->and(substr_count($html, '<div data-chart-fallback>'))->toBe($charts)
            ->and($html)->not->toContain('data-chart-ready')
            ->and($html)->not->toMatch('/data-chart-fallback[^>]*sr-only/');
    }
});

test('the chart component escapes chart labels', function () {
    $html = Blade::render('<x-chart :chart="$chart">Fallback</x-chart>', [
        'chart' => ['label' => '<script>x</script>', 'labels' => ['"quoted"'], 'values' => [1]],
    ]);

    expect($html)->not->toContain('<script>x</script>')
        ->toContain('aria-label="&lt;script&gt;x&lt;/script&gt;"')
        ->toContain('Fallback');
});
