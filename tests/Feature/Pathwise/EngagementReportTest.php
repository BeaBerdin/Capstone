<?php

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\EngagementReport;

/*
| B7 / Manuscript FR (o): the system reports include user engagement,
| based on lesson activity in lesson_progress.
| The clock is frozen at 2026-06-15 10:00 UTC (a Monday) in TestCase.
*/

function engagementLesson(App\Models\Course $course, int $order): Lesson
{
    return Lesson::create([
        'course_id' => $course->id,
        'title' => 'Lesson '.$order,
        'content' => 'Content',
        'lesson_type' => 'text',
        'lesson_order' => $order,
        'is_preview' => false,
        'is_published' => true,
    ]);
}

function lessonActivity(App\Models\User $student, Lesson $lesson, string $status, $startedAt, $completedAt = null): void
{
    LessonProgress::create([
        'student_id' => $student->id,
        'lesson_id' => $lesson->id,
        'status' => $status,
        'started_at' => $startedAt,
        'completed_at' => $completedAt,
    ]);
}

function activeEnrollment(App\Models\User $student, App\Models\Course $course): void
{
    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now()->subMonths(6),
        'progress_percentage' => 0,
    ]);
}

/**
 * Ana completed 2 lessons of "Web Basics" this week, Ben opened one lesson
 * of "Java Basics" 10 days ago, Cara is enrolled but never opened a lesson,
 * and Dan completed a lesson 100 days ago.
 */
function engagementScenario(): array
{
    $teacher = pathwiseUser('teacher');
    $web = pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Web Basics']);
    $java = pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Java Basics']);

    $web1 = engagementLesson($web, 1);
    $web2 = engagementLesson($web, 2);
    $java1 = engagementLesson($java, 1);

    [$ana, $ben, $cara, $dan] = [
        pathwiseUser('student'), pathwiseUser('student'), pathwiseUser('student'), pathwiseUser('student'),
    ];

    foreach ([$ana, $ben, $cara] as $student) {
        activeEnrollment($student, $web);
    }

    lessonActivity($ana, $web1, 'completed', now()->subDays(3), now()->subDays(2));
    lessonActivity($ana, $web2, 'completed', now()->subDays(2), now()->subDay());
    lessonActivity($ben, $java1, 'in_progress', now()->subDays(10));
    lessonActivity($dan, $java1, 'completed', now()->subDays(101), now()->subDays(100));

    return compact('web', 'java');
}

test('engagement for all time counts every lesson activity', function () {
    engagementScenario();

    $report = app(EngagementReport::class)->build();

    expect($report['lessonsStarted'])->toBe(4)
        ->and($report['lessonsCompleted'])->toBe(3)
        ->and($report['activeLearners'])->toBe(3)       // Ana, Ben, Dan
        ->and($report['inactiveLearners'])->toBe(1)     // Cara
        ->and($report['averageCompletedPerLearner'])->toBe(1.0)
        ->and($report['completionRatio'])->toBe(75.0)
        ->and($report['topCourses']->pluck('title')->all())->toBe(['Web Basics', 'Java Basics'])
        ->and((int) $report['topCourses']->first()->lessons_completed)->toBe(2)
        ->and((int) $report['topCourses']->first()->learners)->toBe(1);
});

test('engagement for the last 30 days leaves out older activity', function () {
    engagementScenario();

    $report = app(EngagementReport::class)->build(now()->subDays(30)->startOfDay());

    expect($report['lessonsStarted'])->toBe(3)
        ->and($report['lessonsCompleted'])->toBe(2)
        ->and($report['activeLearners'])->toBe(2)       // Ana, Ben
        ->and($report['inactiveLearners'])->toBe(1)     // Cara
        ->and($report['topCourses']->pluck('title')->all())->toBe(['Web Basics']);
});

test('weekly completions cover the last 8 weeks and place each completion in its week', function () {
    engagementScenario();

    $weeks = app(EngagementReport::class)->weeklyCompletions();

    expect($weeks)->toHaveCount(8)
        ->and(array_sum(array_column($weeks, 'completed')))->toBe(2)   // Dan's is older than 8 weeks
        ->and($weeks[7]['week'])->toBe('Jun 15')                      // current week (frozen Monday)
        ->and($weeks[6]['completed'])->toBe(2);                       // Ana's two, last week
});

test('an empty platform reports zeros without errors', function () {
    $report = app(EngagementReport::class)->build();

    expect($report['lessonsStarted'])->toBe(0)
        ->and($report['activeLearners'])->toBe(0)
        ->and($report['averageCompletedPerLearner'])->toBe(0)
        ->and($report['completionRatio'])->toBe(0)
        ->and($report['topCourses'])->toHaveCount(0);
});

test('the Super Admin reports page shows the engagement section', function () {
    engagementScenario();

    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('reports.index'))
        ->assertOk()
        ->assertSee('data-report-section="engagement"', false)
        ->assertSee('User Engagement')
        ->assertSee('Active Learners')
        ->assertSee('Inactive Learners')
        ->assertSee('Most Active Courses')
        ->assertSee('Web Basics')
        ->assertSee('Lessons Completed per Week');
});

test('the reports page engagement follows the selected period', function () {
    engagementScenario();

    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('reports.index', ['range' => '30']))
        ->assertOk()
        ->assertViewHas('engagement', fn ($engagement) => $engagement['activeLearners'] === 2
            && $engagement['lessonsCompleted'] === 2);
});

test('the PDF export includes the engagement data', function () {
    engagementScenario();

    $response = $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('reports.export.pdf'))
        ->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('the engagement section renders in the PDF view', function () {
    engagementScenario();

    $html = view('reports.pdf', [
        'engagement' => app(EngagementReport::class)->build(),
    ] + pdfReportDefaults())->render();

    expect($html)->toContain('User Engagement')
        ->toContain('Active learners (opened or completed a lesson)')
        ->toContain('Web Basics');
});

test('only the Super Admin can open the system reports', function (string $role) {
    $this->actingAs(pathwiseUser($role))
        ->get(route('reports.index'))
        ->assertForbidden();
})->with(['admin', 'teacher', 'student']);

/**
 * The other variables the PDF view expects, with neutral values.
 */
function pdfReportDefaults(): array
{
    return [
        'reportPeriod' => 'All Time',
        'users' => ['students' => 0, 'teachers' => 0, 'admins' => 0],
        'courses' => 0,
        'totalEnrollments' => 0,
        'completedEnrollments' => 0,
        'activeEnrollments' => 0,
        'completionRate' => 0,
        'certificates' => 0,
        'quizAttempts' => 0,
        'averageQuizScore' => 0,
        'passedQuizzes' => 0,
        'failedQuizzes' => 0,
        'assignments' => 0,
        'submissions' => 0,
        'gradedSubmissions' => 0,
        'pendingSubmissions' => 0,
        'transactions' => 0,
        'approvedTransactions' => 0,
        'pendingTransactions' => 0,
        'rejectedTransactions' => 0,
        'totalRevenue' => 0,
        'recentQuizResults' => collect(),
        'recentCertificates' => collect(),
        'popularCourses' => collect(),
    ];
}
