<x-layouts::app :title="'Teacher Dashboard'">

@php
    $teacherId = auth()->id();

    $publishedCourses = \App\Models\Course::where('teacher_id', $teacherId)
        ->where('status', 'published')
        ->count();

    $pendingCourses = \App\Models\Course::where('teacher_id', $teacherId)
        ->where('status', 'pending')
        ->count();

    $averageScore = round(
        \App\Models\QuizResult::whereHas(
            'quiz.course',
            fn ($q) => $q->where('teacher_id', $teacherId)
        )->avg('percentage') ?? 0,
        1
    );

    $averageProgress = round(
        \App\Models\Enrollment::whereHas(
            'course',
            fn ($q) => $q->where('teacher_id', $teacherId)
        )->avg('progress_percentage') ?? 0
    );

    $totalAssignments = \App\Models\Assignment::whereHas(
        'course',
        fn ($q) => $q->where('teacher_id', $teacherId)
    )->count();

    $pendingGrading = \App\Models\Submission::whereHas(
        'assignment.course',
        fn ($q) => $q->where('teacher_id', $teacherId)
    )
        ->where('status', 'submitted')
        ->count();

    $upcomingAssignments = \App\Models\Assignment::with('course')
        ->whereHas(
            'course',
            fn ($q) => $q->where('teacher_id', $teacherId)
        )
        ->whereNotNull('due_date')
        ->where('due_date', '>=', now())
        ->orderBy('due_date')
        ->take(4)
        ->get();

    $recentQuizResults = \App\Models\QuizResult::with([
        'student',
        'quiz.course'
    ])
        ->whereHas(
            'quiz.course',
            fn ($q) => $q->where('teacher_id', $teacherId)
        )
        ->latest()
        ->take(6)
        ->get();

    $recentLessons = \App\Models\Lesson::with('course')
        ->whereHas(
            'course',
            fn ($q) => $q->where('teacher_id', $teacherId)
        )
        ->latest()
        ->take(3)
        ->get();

    $recentCourses = \App\Models\Course::where(
        'teacher_id',
        $teacherId
    )
        ->latest()
        ->take(3)
        ->get();


    /*
    |--------------------------------------------------------------------------
    | Recent Activities
    |--------------------------------------------------------------------------
    */

    $activityItems = collect();

    foreach ($recentQuizResults as $r) {
        $activityItems->push([
            'type' => 'quiz',

            'title' =>
                ($r->student->name ?? 'Student')
                . ' completed a quiz',

            'description' =>
                ($r->quiz->title ?? 'Quiz')
                . ' • '
                . ($r->quiz->course->title ?? 'Course'),

            'time' => $r->created_at,

            'score' => round(
                $r->percentage ?? 0
            ),
        ]);
    }


    foreach ($recentLessons as $l) {
        $activityItems->push([
            'type' => 'lesson',

            'title' =>
                'Lesson added: "'
                . $l->title
                . '"',

            'description' =>
                $l->course->title ?? 'Course',

            'time' => $l->created_at,

            'score' => null,
        ]);
    }


    foreach ($recentCourses as $c) {
        $activityItems->push([
            'type' => 'course',

            'title' =>
                'Course created: "'
                . $c->title
                . '"',

            'description' =>
                ucfirst(
                    $c->status ?? 'draft'
                ),

            'time' => $c->created_at,

            'score' => null,
        ]);
    }


    $activityItems = $activityItems
        ->sortByDesc('time')
        ->take(4)
        ->values();


    /*
    |--------------------------------------------------------------------------
    | Quiz Chart
    |--------------------------------------------------------------------------
    | Use only real quiz attempts. Do not pad the chart with average-score
    | placeholders because that makes the trend look like historical data
    | that never actually happened.
    */

    $chartResults = \App\Models\QuizResult::whereHas(
        'quiz.course',
        fn ($q) => $q->where('teacher_id', $teacherId)
    )
        ->latest()
        ->take(7)
        ->pluck('percentage')
        ->reverse()
        ->values();


    $chartArray = $chartResults
        ->map(
            fn ($score) =>
                max(
                    0,
                    min(
                        100,
                        (float) $score
                    )
                )
        )
        ->values()
        ->all();


    $chartAttemptCount = count($chartArray);

    $chartWidth = 600;
    $chartHeight = 150;


    $chartCoordinates = collect($chartArray)
        ->map(
            function ($score, $index) use (
                $chartWidth,
                $chartHeight,
                $chartAttemptCount
            ) {
                $x = $chartAttemptCount <= 1
                    ? $chartWidth / 2
                    : $index * (
                        $chartWidth
                        / ($chartAttemptCount - 1)
                    );

                $y = $chartHeight
                    - (($score / 100) * $chartHeight);

                return [
                    'x' => round($x, 1),
                    'y' => round($y, 1),
                    'score' => $score,
                ];
            }
        )
        ->values();


    $chartPoints = $chartCoordinates
        ->map(
            fn ($point) =>
                $point['x']
                . ','
                . $point['y']
        )
        ->implode(' ');


    $chartAreaPoints = '';

    if ($chartAttemptCount >= 2) {
        $firstX = $chartCoordinates->first()['x'];
        $lastX = $chartCoordinates->last()['x'];

        $chartAreaPoints =
            $firstX . ',' . $chartHeight
            . ' '
            . $chartPoints
            . ' '
            . $lastX . ',' . $chartHeight;
    }


    /*
    |--------------------------------------------------------------------------
    | Greeting
    |--------------------------------------------------------------------------
    */

    $hour = now()->hour;

    $greeting =
        $hour < 12
            ? 'Good morning'
            : (
                $hour < 18
                    ? 'Good afternoon'
                    : 'Good evening'
            );


    $teacherName =
        auth()->user()->name ?? 'Teacher';


    $firstName =
        explode(
            ' ',
            trim($teacherName)
        )[0];


    $currentDate =
        now()->format(
            'l, F d, Y'
        );


    /*
    |--------------------------------------------------------------------------
    | Dashboard Statistic Cards
    |--------------------------------------------------------------------------
    */

    $statCards = [

        [
            'label' => 'My Courses',
            'value' => $totalCourses,
            'sub' => 'Active courses',

            'icon' =>
                'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25',

            'tone' => 'violet',

            'route' =>
                'teacher.my-courses',

            'link' =>
                'View all',
        ],


        [
            'label' => 'Total Students',
            'value' => $totalStudents,
            'sub' => 'Across all courses',

            'icon' =>
                'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm13 10v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75',

            'tone' => 'blue',

            'route' =>
                'teacher.student-progress.index',

            'link' =>
                'View all',
        ],


        [
            'label' => 'Assignments',
            'value' => $totalAssignments,

            'sub' =>
                $pendingGrading
                . ' need grading',

            'icon' =>
                'M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11',

            'tone' => 'emerald',

            'route' =>
                'teacher.my-courses',

            'link' =>
                'View courses',
        ],


        [
            'label' => 'Avg. Class Progress',

            'value' =>
                $averageProgress . '%',

            'sub' =>
                'Across all enrollments',

            'icon' =>
                'M4 19V9m5 10V5m5 14v-7m5 7V3',

            'tone' => 'orange',

            'route' =>
                'teacher.student-progress.index',

            'link' =>
                'View report',
        ],
    ];
@endphp

<style>
    /* =========================================================
       PATHWISE TEACHER DASHBOARD
       Clean reference-matched light UI
    ========================================================= */

    .pw-dashboard-page {
        min-height: 100vh;
        background: #f7f8fc;
        color: #0f172a;
    }

    .pw-dashboard-shell {
    width: 100%;
    max-width: 1600px;
    margin: 0;
    padding: 26px 28px 40px;
}

    .pw-dashboard-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 22px;
    }

    .pw-dashboard-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    .pw-dashboard-card {
        background: #ffffff;
        border: 1px solid #e7eaf0;
        border-radius: 14px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.025);
    }

    .pw-section-heading {
        font-size: 15px;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: #111827;
    }

    .pw-section-subheading {
        margin-top: 4px;
        font-size: 13px;
        font-weight: 500;
        color: #94a3b8;
    }

    .pw-primary-action,
    .pw-secondary-action {
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 0 15px;
        border-radius: 9px;
        font-size: 12px;
        font-weight: 700;
        transition: 160ms ease;
    }

    .pw-primary-action {
        color: #fff;
        background: linear-gradient(90deg, #7c3aed, #5b4ff1);
        box-shadow: 0 5px 14px rgba(124, 58, 237, .18);
    }

    .pw-primary-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(124, 58, 237, .22);
    }

    .pw-secondary-action {
        color: #475569;
        background: #fff;
        border: 1px solid #dde3ec;
    }

    .pw-secondary-action:hover {
        border-color: #c4b5fd;
        background: #faf8ff;
        color: #6d28d9;
    }

    .pw-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 16px;
    }

    .pw-stat-card {
        min-height: 128px;
        padding: 17px 18px 15px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: 160ms ease;
    }

    .pw-stat-card:hover {
        border-color: #ddd6fe;
        box-shadow: 0 7px 22px rgba(76, 29, 149, .055);
    }

    .pw-stat-top {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .pw-stat-icon {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .pw-stat-icon-violet { background: #f1eaff; color: #7c3aed; }
    .pw-stat-icon-blue { background: #e4f0ff; color: #2563eb; }
    .pw-stat-icon-emerald { background: #ddfaeb; color: #059669; }
    .pw-stat-icon-orange { background: #fff0dc; color: #ea580c; }

    .pw-stat-label {
        font-size: 12px;
        font-weight: 600;
        color: #475569;
    }

    .pw-stat-value {
        margin-top: 2px;
        font-size: 26px;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: #0f172a;
    }

    .pw-stat-sub {
        margin-top: 7px;
        font-size: 10px;
        color: #94a3b8;
    }

    .pw-stat-link {
        margin-top: 12px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        width: fit-content;
        font-size: 10.5px;
        font-weight: 700;
        color: #6d28d9;
    }

    .pw-overview-grid,
    .pw-bottom-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.55fr) minmax(310px, .95fr);
        gap: 16px;
    }

    .pw-bottom-grid {
        margin-top: 16px;
    }

    .pw-overview-card {
        padding: 18px 20px 18px;
        min-height: 320px;
    }

    .pw-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .pw-mini-chip {
        padding: 7px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        font-size: 9px;
        font-weight: 600;
        color: #64748b;
        white-space: nowrap;
    }

    .pw-overview-body {
        margin-top: 20px;
        display: grid;
        grid-template-columns: 180px minmax(0, 1fr);
        gap: 22px;
        align-items: center;
    }

    .pw-progress-panel {
        min-height: 225px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-right: 1px solid #eef1f5;
        padding-right: 18px;
    }

    .pw-progress-label {
        margin-bottom: 14px;
        font-size: 11px;
        font-weight: 600;
        color: #475569;
    }

    .pw-ring {
        --pw-progress: 0%;
        width: 132px;
        height: 132px;
        position: relative;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background:
            conic-gradient(
                #7657f6 0 var(--pw-progress),
                #ece8ff var(--pw-progress) 100%
            );
    }

    .pw-ring::after {
        content: "";
        position: absolute;
        width: 98px;
        height: 98px;
        border-radius: 50%;
        background: #fff;
    }

    .pw-ring-copy {
        position: relative;
        z-index: 2;
        text-align: center;
    }

    .pw-ring-value {
        font-size: 25px;
        line-height: 1;
        font-weight: 800;
        color: #111827;
    }

    .pw-ring-caption {
        margin-top: 4px;
        font-size: 9px;
        color: #94a3b8;
    }

    .pw-active-learners {
        margin-top: 13px;
        font-size: 9.5px;
        font-weight: 700;
        color: #059669;
    }

    .pw-chart-panel {
        min-width: 0;
    }

    .pw-chart-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 10px;
    }

    .pw-chart-title {
        font-size: 11px;
        font-weight: 600;
        color: #475569;
    }

    .pw-chart-note {
        font-size: 9px;
        color: #94a3b8;
    }

    .pw-chart-box {
        position: relative;
        height: 182px;
    }

    .pw-y-axis {
        position: absolute;
        inset: 0 auto 22px 0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        font-size: 8px;
        color: #94a3b8;
    }

    .pw-chart-canvas {
        position: absolute;
        inset: 0 0 0 36px;
        padding-bottom: 22px;
    }

    .pw-chart-grid {
        position: absolute;
        inset: 0 0 22px 0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .pw-chart-grid > div {
        border-top: 1px solid #eef1f5;
    }

    .pw-chart-svg {
        position: absolute;
        inset: 0 0 22px 0;
        width: 100%;
        height: calc(100% - 22px);
        overflow: visible;
    }

    .pw-x-axis {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        justify-content: space-between;
        font-size: 8px;
        color: #94a3b8;
    }

    .pw-upcoming-card,
    .pw-activity-card,
    .pw-quick-card {
        overflow: hidden;
    }

    .pw-panel-header {
        min-height: 68px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 15px 18px;
        border-bottom: 1px solid #eef1f5;
    }

    .pw-panel-count,
    .pw-panel-link {
        flex-shrink: 0;
        font-size: 9.5px;
        font-weight: 700;
        color: #6d28d9;
    }

    .pw-upcoming-body {
        min-height: 250px;
    }

    .pw-upcoming-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 18px;
        border-bottom: 1px solid #f1f3f7;
    }

    .pw-upcoming-row:last-child {
        border-bottom: 0;
    }

    .pw-date-card {
        width: 46px;
        flex: 0 0 46px;
        overflow: hidden;
        border: 1px solid #e8eaf0;
        border-radius: 8px;
        background: #fff;
        text-align: center;
    }

    .pw-date-month {
        padding: 3px 0;
        background: #7c3aed;
        color: #fff;
        font-size: 7px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .pw-date-day {
        padding: 5px 0;
        font-size: 14px;
        font-weight: 800;
        line-height: 1;
        color: #111827;
    }

    .pw-empty-state {
        min-height: 250px;
        display: grid;
        place-items: center;
        padding: 24px;
        text-align: center;
    }

    .pw-empty-icon {
        width: 42px;
        height: 42px;
        margin: 0 auto;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: #f5f3ff;
        color: #7c3aed;
    }

    .pw-empty-title {
        margin-top: 12px;
        font-size: 11px;
        font-weight: 800;
        color: #1f2937;
    }

    .pw-empty-copy {
        margin-top: 4px;
        font-size: 9px;
        color: #94a3b8;
    }

    .pw-activity-row {
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 64px;
        padding: 10px 18px;
        border-bottom: 1px solid #f1f3f7;
    }

    .pw-activity-row:last-child {
        border-bottom: 0;
    }

    .pw-activity-icon {
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        display: grid;
        place-items: center;
        border-radius: 50%;
    }

    .pw-activity-quiz { background: #ecfdf5; color: #059669; }
    .pw-activity-lesson { background: #eff6ff; color: #2563eb; }
    .pw-activity-course { background: #f5f3ff; color: #7c3aed; }

    .pw-activity-title {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 10.5px;
        font-weight: 700;
        color: #1f2937;
    }

    .pw-activity-sub {
        margin-top: 3px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 9px;
        color: #94a3b8;
    }

    .pw-activity-time {
        margin-left: auto;
        flex-shrink: 0;
        font-size: 9px;
        color: #94a3b8;
        text-align: right;
    }

    .pw-score-good,
    .pw-score-bad {
        flex-shrink: 0;
        padding: 4px 7px;
        border-radius: 999px;
        font-size: 9px;
        font-weight: 800;
    }

    .pw-score-good { background: #ecfdf5; color: #059669; }
    .pw-score-bad { background: #fef2f2; color: #dc2626; }

    .pw-quick-card {
        padding: 17px;
    }

    .pw-quick-grid {
        margin-top: 14px;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .pw-quick-item {
        min-height: 96px;
        padding: 13px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border: 1px solid #eceef3;
        border-radius: 11px;
        background: #fff;
        transition: 160ms ease;
    }

    .pw-quick-item:hover {
        transform: translateY(-1px);
        border-color: #ddd6fe;
        background: #faf9ff;
    }

    .pw-quick-icon {
        width: 32px;
        height: 32px;
        display: grid;
        place-items: center;
        border-radius: 9px;
    }

    .pw-quick-label {
        margin-top: 10px;
        font-size: 10px;
        font-weight: 700;
        color: #334155;
    }

    .pw-quick-sub {
        margin-top: 3px;
        font-size: 8.5px;
        color: #94a3b8;
    }

    @media (max-width: 1180px) {
        .pw-stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .pw-overview-grid,
        .pw-bottom-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 760px) {
        .pw-dashboard-shell {
    width: 100%;
    max-width: 1600px;
    margin: 0;
    padding: 26px 28px 40px;
}

        .pw-dashboard-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .pw-stats-grid {
            grid-template-columns: 1fr;
        }

        .pw-overview-body {
            grid-template-columns: 1fr;
        }

        .pw-progress-panel {
            min-height: auto;
            border-right: 0;
            border-bottom: 1px solid #eef1f5;
            padding: 0 0 20px;
        }

        .pw-dashboard-actions {
            width: 100%;
            flex-wrap: wrap;
        }
    }
</style>


<div class="pw-dashboard-page">

    <main class="pw-dashboard-shell">


        {{-- =====================================================
             HEADER
        ====================================================== --}}

        <section class="pw-dashboard-header">

            <div>

                <h1
                    style="
                        margin:0;
                        font-size:26px;
                        line-height:1.2;
                        font-weight:800;
                        letter-spacing:-.03em;
                        color:#0f172a;
                    "
                >
                    {{ $greeting }}, {{ $firstName }}! 👋
                </h1>

                <p
                    style="
                        margin:6px 0 0;
                        font-size:12px;
                        color:#64748b;
                    "
                >
                    Here's what's happening in your classes today.
                </p>

                <p
                    style="
                        margin:4px 0 0;
                        font-size:10px;
                        font-weight:500;
                        color:#94a3b8;
                    "
                >
                    {{ $currentDate }}
                </p>

            </div>


            <div class="pw-dashboard-actions">

                <a
                    href="{{ route('teacher.lessons.index') }}"
                    class="pw-secondary-action"
                >
                    <svg
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"
                        />
                    </svg>

                    Plan a Lesson
                </a>


                <a
                    href="{{ route('teacher.courses.create') }}"
                    class="pw-primary-action"
                >
                    <svg
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            d="M12 5v14M5 12h14"
                        />
                    </svg>

                    Create Course
                </a>

            </div>

        </section>



        {{-- =====================================================
             STATS
        ====================================================== --}}

        <section class="pw-stats-grid">

            @foreach($statCards as $stat)

                <div class="pw-dashboard-card pw-stat-card">

                    <div class="pw-stat-top">

                        <div
                            class="pw-stat-icon
                                   pw-stat-icon-{{ $stat['tone'] }}"
                        >
                            <svg
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.9"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="{{ $stat['icon'] }}"
                                />
                            </svg>
                        </div>


                        <div style="min-width:0;">

                            <div class="pw-stat-label">
                                {{ $stat['label'] }}
                            </div>

                            <div class="pw-stat-value">
                                {{ $stat['value'] }}
                            </div>

                            <div class="pw-stat-sub">
                                {{ $stat['sub'] }}
                            </div>

                        </div>

                    </div>


                    <a
                        href="{{ route($stat['route']) }}"
                        class="pw-stat-link"
                    >
                        {{ $stat['link'] }} →
                    </a>

                </div>

            @endforeach

        </section>



        {{-- =====================================================
             OVERVIEW + UPCOMING
        ====================================================== --}}

        <section class="pw-overview-grid">


            {{-- CLASS OVERVIEW --}}
            <div class="pw-dashboard-card pw-overview-card">

                <div class="pw-card-header">

                    <div>

                        <div class="pw-section-heading">
                            Class Overview
                        </div>

                        <div class="pw-section-subheading">
                            Overall learner performance
                        </div>

                    </div>


                    <div class="pw-mini-chip">
                        Recent performance
                    </div>

                </div>


                <div class="pw-overview-body">


                    {{-- Progress --}}
                    <div class="pw-progress-panel">

                        <div class="pw-progress-label">
                            Overall Progress
                        </div>


                        <div
                            class="pw-ring"
                            style="--pw-progress: {{ min(100, max(0, $averageProgress)) }}%;"
                        >
                            <div class="pw-ring-copy">

                                <div class="pw-ring-value">
                                    {{ $averageProgress }}%
                                </div>

                                <div class="pw-ring-caption">
                                    Average
                                </div>

                            </div>
                        </div>


                        <div class="pw-active-learners">
                            {{ $totalStudents }} active learners
                        </div>

                    </div>



                    {{-- Chart --}}
                    <div class="pw-chart-panel">

                        <div class="pw-chart-meta">

                            <div class="pw-chart-title">
                                Quiz Performance Trend
                            </div>

                            <div class="pw-chart-note">
                                @if($chartAttemptCount > 0)
                                    Latest {{ $chartAttemptCount }}
                                    {{ \Illuminate\Support\Str::plural('attempt', $chartAttemptCount) }}
                                @else
                                    No attempts yet
                                @endif
                            </div>

                        </div>


                        <div class="pw-chart-box">

                            @if($chartAttemptCount > 0)

                                <div class="pw-y-axis">
                                    <span>100%</span>
                                    <span>75%</span>
                                    <span>50%</span>
                                    <span>25%</span>
                                    <span>0%</span>
                                </div>


                                <div class="pw-chart-canvas">

                                    <div class="pw-chart-grid">
                                        @for($i = 0; $i < 5; $i++)
                                            <div></div>
                                        @endfor
                                    </div>


                                    <svg
                                        class="pw-chart-svg"
                                        preserveAspectRatio="none"
                                        viewBox="0 0 600 150"
                                    >

                                        <defs>
                                            <linearGradient
                                                id="pwDashboardGradient"
                                                x1="0"
                                                y1="0"
                                                x2="0"
                                                y2="1"
                                            >
                                                <stop
                                                    offset="0%"
                                                    stop-color="#7657f6"
                                                    stop-opacity=".18"
                                                />
                                                <stop
                                                    offset="100%"
                                                    stop-color="#7657f6"
                                                    stop-opacity="0"
                                                />
                                            </linearGradient>
                                        </defs>


                                        @if($chartAttemptCount >= 2)

                                            <polygon
                                                points="{{ $chartAreaPoints }}"
                                                fill="url(#pwDashboardGradient)"
                                            />


                                            <polyline
                                                points="{{ $chartPoints }}"
                                                fill="none"
                                                stroke="#7657f6"
                                                stroke-width="3"
                                                vector-effect="non-scaling-stroke"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />

                                        @endif


                                        @foreach($chartCoordinates as $point)

                                            <circle
                                                cx="{{ $point['x'] }}"
                                                cy="{{ $point['y'] }}"
                                                r="4.5"
                                                fill="#7657f6"
                                            />

                                        @endforeach

                                    </svg>


                                    <div
                                        class="pw-x-axis"
                                        @if($chartAttemptCount === 1)
                                            style="justify-content:center;"
                                        @endif
                                    >
                                        @foreach($chartCoordinates as $point)
                                            <span>{{ $loop->iteration }}</span>
                                        @endforeach
                                    </div>

                                </div>

                            @else

                                <div
                                    style="
                                        height:100%;
                                        display:grid;
                                        place-items:center;
                                        padding:24px;
                                        text-align:center;
                                        color:#94a3b8;
                                        font-size:10px;
                                        font-weight:600;
                                    "
                                >
                                    Quiz attempts will appear here once students submit assessments.
                                </div>

                            @endif

                        </div>

                    </div>

                </div>

            </div>



            {{-- UPCOMING --}}
            <div class="pw-dashboard-card pw-upcoming-card">

                <div class="pw-panel-header">

                    <div>

                        <div class="pw-section-heading">
                            Upcoming
                        </div>

                        <div class="pw-section-subheading">
                            Scheduled deadlines
                        </div>

                    </div>


                    <div class="pw-panel-count">
                        {{ $upcomingAssignments->count() }}
                        {{ \Illuminate\Support\Str::plural('item', $upcomingAssignments->count()) }}
                    </div>

                </div>


                <div class="pw-upcoming-body">

                    @forelse($upcomingAssignments as $assignment)

                        @php
                            $due = \Carbon\Carbon::parse(
                                $assignment->due_date
                            );
                        @endphp


                        <div class="pw-upcoming-row">

                            <div class="pw-date-card">

                                <div class="pw-date-month">
                                    {{ $due->format('M') }}
                                </div>

                                <div class="pw-date-day">
                                    {{ $due->format('d') }}
                                </div>

                            </div>


                            <div style="min-width:0;flex:1;">

                                <div
                                    style="
                                        overflow:hidden;
                                        text-overflow:ellipsis;
                                        white-space:nowrap;
                                        font-size:10.5px;
                                        font-weight:700;
                                        color:#1f2937;
                                    "
                                >
                                    {{ $assignment->title }}
                                </div>

                                <div
                                    style="
                                        margin-top:3px;
                                        overflow:hidden;
                                        text-overflow:ellipsis;
                                        white-space:nowrap;
                                        font-size:9px;
                                        color:#94a3b8;
                                    "
                                >
                                    {{ $assignment->course->title ?? 'Course' }}
                                </div>

                            </div>


                            <div
                                style="
                                    flex-shrink:0;
                                    font-size:9px;
                                    font-weight:500;
                                    color:#94a3b8;
                                    text-align:right;
                                "
                            >
                                {{ $due->format('g:i A') }}
                            </div>

                        </div>


                    @empty

                        <div class="pw-empty-state">

                            <div>

                                <div class="pw-empty-icon">
                                    <svg
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"
                                        />
                                    </svg>
                                </div>

                                <div class="pw-empty-title">
                                    Nothing scheduled
                                </div>

                                <div class="pw-empty-copy">
                                    Upcoming assignments will appear here.
                                </div>

                            </div>

                        </div>

                    @endforelse

                </div>

            </div>

        </section>



        {{-- =====================================================
             RECENT ACTIVITIES + QUICK ACTIONS
        ====================================================== --}}

        <section class="pw-bottom-grid">


            {{-- RECENT ACTIVITIES --}}
            <div class="pw-dashboard-card pw-activity-card">

                <div class="pw-panel-header">

                    <div>

                        <div class="pw-section-heading">
                            Recent Activities
                        </div>

                        <div class="pw-section-subheading">
                            Latest activity from your courses
                        </div>

                    </div>


                    <a
                        href="{{ route('teacher.quiz-results.index') }}"
                        class="pw-panel-link"
                    >
                        View all →
                    </a>

                </div>


                <div>

                    @forelse($activityItems as $activity)

                        <div class="pw-activity-row">

                            @if($activity['type'] === 'quiz')

                                <div class="pw-activity-icon pw-activity-quiz">
                                    <svg
                                        width="16"
                                        height="16"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.9"
                                    >
                                        <path d="M9 11l3 3L22 4" />
                                        <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" />
                                    </svg>
                                </div>

                            @elseif($activity['type'] === 'lesson')

                                <div class="pw-activity-icon pw-activity-lesson">
                                    <svg
                                        width="16"
                                        height="16"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path d="M4 19.5A2.5 2.5 0 016.5 17H20" />
                                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z" />
                                    </svg>
                                </div>

                            @else

                                <div class="pw-activity-icon pw-activity-course">
                                    <svg
                                        width="16"
                                        height="16"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292" />
                                        <path d="M12 6.042A8.966 8.966 0 0118 3.75c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292" />
                                    </svg>
                                </div>

                            @endif


                            <div style="min-width:0;flex:1;">

                                <div class="pw-activity-title">
                                    {{ $activity['title'] }}
                                </div>

                                <div class="pw-activity-sub">
                                    {{ $activity['description'] }}
                                </div>

                            </div>


                            @if($activity['score'] !== null)

                                <div
                                    class="{{
                                        $activity['score'] >= 75
                                            ? 'pw-score-good'
                                            : 'pw-score-bad'
                                    }}"
                                >
                                    {{ $activity['score'] }}%
                                </div>

                            @endif


                            <div class="pw-activity-time">
                                {{ $activity['time']->diffForHumans() }}
                            </div>

                        </div>


                    @empty

                        <div
                            style="
                                padding:45px 20px;
                                text-align:center;
                                font-size:10px;
                                color:#94a3b8;
                            "
                        >
                            No recent activities yet.
                        </div>

                    @endforelse

                </div>

            </div>



            {{-- QUICK ACTIONS --}}
            <div class="pw-dashboard-card pw-quick-card">

                <div>

                    <div class="pw-section-heading">
                        Quick Actions
                    </div>

                    <div class="pw-section-subheading">
                        Jump directly to common teaching tasks.
                    </div>

                </div>


                <div class="pw-quick-grid">

                    <a
                        href="{{ route('teacher.courses.create') }}"
                        class="pw-quick-item"
                    >
                        <div
                            class="pw-quick-icon"
                            style="background:#f5f3ff;color:#7c3aed;"
                        >
                            <svg
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    d="M12 5v14M5 12h14"
                                />
                            </svg>
                        </div>

                        <div>
                            <div class="pw-quick-label">Create Course</div>
                            <div class="pw-quick-sub">Build new content</div>
                        </div>
                    </a>


                    <a
                        href="{{ route('teacher.lessons.index') }}"
                        class="pw-quick-item"
                    >
                        <div
                            class="pw-quick-icon"
                            style="background:#eff6ff;color:#2563eb;"
                        >
                            <svg
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8Z" />
                                <path d="M14 2v6h6M8 13h8M8 17h6" />
                            </svg>
                        </div>

                        <div>
                            <div class="pw-quick-label">Lessons</div>
                            <div class="pw-quick-sub">Manage materials</div>
                        </div>
                    </a>


                    <a
                        href="{{ route('teacher.quiz-results.index') }}"
                        class="pw-quick-item"
                    >
                        <div
                            class="pw-quick-icon"
                            style="background:#fff7ed;color:#ea580c;"
                        >
                            <svg
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <circle cx="12" cy="12" r="9" />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m8.5 12.5 2.5 2.5 4.5-5"
                                />
                            </svg>
                        </div>

                        <div>
                            <div class="pw-quick-label">Quiz Results</div>
                            <div class="pw-quick-sub">Review performance</div>
                        </div>
                    </a>


                    <a
                        href="{{ route('teacher.student-progress.index') }}"
                        class="pw-quick-item"
                    >
                        <div
                            class="pw-quick-icon"
                            style="background:#ecfdf5;color:#059669;"
                        >
                            <svg
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M4 19V9M9 19V5M14 19v-7M19 19V3"
                                />
                            </svg>
                        </div>

                        <div>
                            <div class="pw-quick-label">Student Progress</div>
                            <div class="pw-quick-sub">Track learners</div>
                        </div>
                    </a>

                </div>

            </div>

        </section>


    </main>

</div>

</x-layouts::app>
