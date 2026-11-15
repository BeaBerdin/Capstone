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
            'label' => 'Total Lessons',
            'value' => $totalLessons,
            'sub' => 'Across your courses',

            'icon' =>
                'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',

            'tone' => 'blue',

            'route' =>
                'teacher.lessons.index',

            'link' =>
                'View lessons',
        ],


        [
            'label' => 'Enrolled Students',
            'value' => $totalStudents,
            'sub' => 'Across all courses',

            'icon' =>
                'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm13 10v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75',

            'tone' => 'emerald',

            'route' =>
                'teacher.student-progress.index',

            'link' =>
                'View all',
        ],


        [
            'label' => 'Avg. Quiz Score',

            'value' =>
                $averageScore . '%',

            'sub' =>
                'Across all quiz attempts',

            'icon' =>
                'M4 19V9m5 10V5m5 14v-7m5 7V3',

            'tone' => 'orange',

            'route' =>
                'teacher.quiz-results.index',

            'link' =>
                'View results',
        ],
    ];
@endphp

{{--
    Teacher dashboard in the soft-3D style (styles in partials/soft3d).
    All figures come from the block above and DashboardController@teacher.
--}}

@php
    // Course status split for the rail (from the counts above).
    $draftOrOther = max(0, $totalCourses - $publishedCourses - $pendingCourses);

    // Progress ring (circle of radius 42).
    $ringCircumference = 2 * M_PI * 42;
    $ringProgress = min(100, max(0, $averageProgress));

    $statTones = ['sad-violet', 'sad-sky', 'sad-emerald', 'sad-amber'];

    $quickActions = [
        ['label' => 'Create Course', 'href' => route('teacher.courses.create'), 'tone' => 'sad-violet', 'icon' => 'M12 5v14M5 12h14'],
        ['label' => 'Lessons', 'href' => route('teacher.lessons.index'), 'tone' => 'sad-sky', 'icon' => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M8 13h8M8 17h6'],
        ['label' => 'Assignments', 'href' => route('teacher.assignments.index'), 'tone' => 'sad-emerald', 'icon' => 'M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11'],
        ['label' => 'Quiz Results', 'href' => route('teacher.quiz-results.index'), 'tone' => 'sad-amber', 'icon' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM8.5 12.5l2.5 2.5 4.5-5'],
        ['label' => 'Student Progress', 'href' => route('teacher.student-progress.index'), 'tone' => 'sad-rose', 'icon' => 'M4 19V9M9 19V5M14 19v-7M19 19V3'],
        ['label' => 'Invitations', 'href' => route('course-invitations.index'), 'tone' => 'sad-violet', 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M19 8v6M22 11h-6'],
    ];
@endphp

@include('partials.soft3d')

<div class="sad-page">

    <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">

        <div class="mx-auto grid max-w-375 grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">

            {{-- =====================================================
                MAIN COLUMN
            ====================================================== --}}

            <div class="min-w-0 space-y-6">

                {{-- WELCOME BANNER --}}
                <section class="sad-banner">

                    <div class="relative px-6 py-7 sm:px-8 md:pr-[38%]">

                        <p class="text-xs font-bold uppercase tracking-[.14em] text-violet-600">
                            Teacher Dashboard
                        </p>

                        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-[26px]">
                            {{ $greeting }}, {{ $firstName }}!&nbsp;<span aria-hidden="true">👋</span>
                        </h1>

                        <p class="mt-2 max-w-xl text-sm leading-6 text-slate-600">
                            Here's what's happening in your classes today.
                        </p>

                        <p class="mt-1 text-xs font-medium text-slate-500">{{ $currentDate }}</p>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="{{ route('teacher.courses.create') }}" class="sad-btn sad-btn-primary">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                                Create Course
                            </a>

                            <a href="{{ route('teacher.lessons.index') }}" class="sad-btn sad-btn-light">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                Plan a Lesson
                            </a>
                        </div>

                    </div>

                    {{-- Soft-3D teaching artwork: board, chart and books, drawn inline. --}}
                    <svg class="sad-banner-art" viewBox="0 0 360 220" aria-hidden="true">
                        <defs>
                            <linearGradient id="tchBoard" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#ffffff" />
                                <stop offset="1" stop-color="#ede9fe" />
                            </linearGradient>
                            <linearGradient id="tchBookA" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#a78bfa" />
                                <stop offset="1" stop-color="#7c3aed" />
                            </linearGradient>
                            <linearGradient id="tchBookB" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#93c5fd" />
                                <stop offset="1" stop-color="#6366f1" />
                            </linearGradient>
                            <linearGradient id="tchApple" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#fda4af" />
                                <stop offset="1" stop-color="#e11d48" />
                            </linearGradient>
                            <filter id="tchSoft" x="-20%" y="-20%" width="140%" height="160%">
                                <feDropShadow dx="0" dy="8" stdDeviation="7" flood-color="#4c1d95" flood-opacity="0.18" />
                            </filter>
                        </defs>

                        <circle cx="220" cy="110" r="96" fill="#ffffff" opacity="0.35" />
                        <ellipse cx="200" cy="206" rx="130" ry="10" fill="#7c3aed" opacity="0.08" />

                        {{-- Board with a rising chart --}}
                        <g filter="url(#tchSoft)">
                            <rect x="110" y="28" width="170" height="112" rx="16" fill="url(#tchBoard)" />
                            <rect x="132" y="96" width="18" height="28" rx="5" fill="#ddd6fe" />
                            <rect x="160" y="80" width="18" height="44" rx="5" fill="#c4b5fd" />
                            <rect x="188" y="64" width="18" height="60" rx="5" fill="#a78bfa" />
                            <rect x="216" y="50" width="18" height="74" rx="5" fill="#7c3aed" />
                            <path d="M138 84 L168 70 L196 56 L248 40" fill="none" stroke="#f59e0b" stroke-width="3" stroke-linecap="round" />
                            <circle cx="248" cy="40" r="5" fill="#f59e0b" />
                            <path d="M195 140 L185 168 M195 140 L205 168" stroke="#c4b5fd" stroke-width="5" stroke-linecap="round" />
                        </g>

                        {{-- Books --}}
                        <g filter="url(#tchSoft)">
                            <rect x="60" y="176" width="110" height="22" rx="6" fill="url(#tchBookB)" />
                            <rect x="68" y="181" width="94" height="5" rx="2.5" fill="#ffffff" opacity="0.55" />
                            <rect x="70" y="154" width="92" height="22" rx="6" fill="url(#tchBookA)" />
                            <rect x="78" y="159" width="76" height="5" rx="2.5" fill="#ffffff" opacity="0.5" />
                        </g>

                        {{-- Apple --}}
                        <g filter="url(#tchSoft)">
                            <circle cx="290" cy="182" r="18" fill="url(#tchApple)" />
                            <path d="M290 164 C290 156 296 152 302 152" fill="none" stroke="#7c2d12" stroke-width="3" stroke-linecap="round" />
                            <path d="M292 160 C300 154 310 158 312 164 C304 168 296 166 292 160 Z" fill="#34d399" />
                        </g>
                    </svg>

                </section>


                {{-- SUMMARY CARDS (Manuscript Fig. 37). Label and value stay in
                     their own elements; each card links to its page. --}}
                <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 2xl:grid-cols-4">

                    @foreach($statCards as $stat)
                        <a href="{{ route($stat['route']) }}" class="sad-card sad-lift block p-5">
                            <span class="sad-chip {{ $statTones[$loop->index % 4] }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['icon'] }}" />
                                </svg>
                            </span>
                            <p class="mt-4 text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                            <p class="mt-1 text-3xl font-bold tracking-tight text-slate-950">{{ $stat['value'] }}</p>
                            <p class="mt-2 text-xs font-medium text-slate-400">{{ $stat['sub'] }}</p>
                        </a>
                    @endforeach

                </section>


                {{-- AI EVALUATIONS AWAITING REVIEW --}}
                <section class="sad-card overflow-hidden" data-dashboard-section="ai-awaiting-review">

                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
                        <div>
                            <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                                <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m12 3 1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3Z"></path>
                                    <path d="m19 16 .8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8L19 16Z"></path>
                                </svg>
                                AI Evaluations Awaiting Review
                            </h2>
                            <p class="mt-1 text-xs text-slate-500">Suggested scores the students cannot see until you grade them</p>
                        </div>

                        <span class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700" data-ai-awaiting-count>
                            {{ number_format($aiAwaitingCount ?? 0) }} awaiting
                        </span>
                    </div>

                    @if(($aiUnfinished ?? collect())->isNotEmpty())
                        <div class="mx-5 mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700 sm:mx-6" data-ai-unfinished>
                            {{ $aiUnfinished->count() }} {{ \Illuminate\Support\Str::plural('AI evaluation', $aiUnfinished->count()) }} did not finish (failed or interrupted). Open
                            <a href="{{ route('teacher.submissions.show', $aiUnfinished->first()->id) }}" class="font-semibold underline">
                                the latest one
                            </a>
                            to retry, or grade manually.
                        </div>
                    @endif

                    @if(($aiAwaitingSubmissions ?? collect())->isEmpty())

                        <div class="border-t border-slate-100 px-5 py-6 text-center text-sm text-slate-500 sm:px-6">
                            No AI evaluations are waiting for your review.
                        </div>

                    @else

                        <ul class="divide-y divide-slate-100 border-t border-slate-100">
                            @foreach($aiAwaitingSubmissions as $awaiting)
                                <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">

                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="sad-chip sad-chip-sm sad-violet">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-slate-800">
                                                {{ $awaiting->student?->name ?? 'Student' }}
                                            </p>
                                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                                {{ $awaiting->assignment?->title ?? 'Assignment' }}
                                                · {{ $awaiting->assignment?->course?->title ?? 'Course' }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-3">
                                        <span class="inline-flex rounded-full bg-violet-50 px-2.5 py-1 text-xs font-bold text-violet-700">
                                            AI: {{ $awaiting->ai_score }} / {{ $awaiting->assignment?->max_score }}
                                        </span>

                                        <a href="{{ route('teacher.submissions.show', $awaiting) }}" class="sad-btn sad-btn-primary h-9! px-4! text-xs!">
                                            Review
                                        </a>
                                    </div>

                                </li>
                            @endforeach
                        </ul>

                        @if(($aiAwaitingCount ?? 0) > $aiAwaitingSubmissions->count())
                            <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:px-6">
                                Showing the latest {{ $aiAwaitingSubmissions->count() }} of {{ number_format($aiAwaitingCount) }}.
                                Open an assignment's submissions to see the rest.
                            </p>
                        @endif

                    @endif

                </section>


                {{-- CLASS OVERVIEW: progress ring + quiz trend --}}
                <section class="sad-card p-5 sm:p-6">

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                                <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 3v18h18"></path>
                                    <path d="m7 16 4-5 4 3 5-7"></path>
                                </svg>
                                Class Overview
                            </h2>
                            <p class="mt-1 text-xs text-slate-500">Overall learner performance</p>
                        </div>
                        <span class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-500">
                            @if($chartAttemptCount > 0)
                                Latest {{ $chartAttemptCount }} {{ \Illuminate\Support\Str::plural('attempt', $chartAttemptCount) }}
                            @else
                                No attempts yet
                            @endif
                        </span>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-6 md:grid-cols-[180px_minmax(0,1fr)]">

                        {{-- Average course progress --}}
                        <div class="flex flex-col items-center justify-center rounded-2xl border border-slate-100 bg-slate-50/60 p-4">
                            <p class="text-xs font-semibold text-slate-500">Overall Progress</p>
                            <div class="relative mt-3 h-28 w-28">
                                <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90" role="img" aria-label="Average course progress">
                                    <circle cx="50" cy="50" r="42" fill="none" stroke="#ede9fe" stroke-width="11" />
                                    @if($ringProgress > 0)
                                        <circle cx="50" cy="50" r="42" fill="none" stroke="#7c3aed" stroke-width="11" stroke-linecap="round"
                                                stroke-dasharray="{{ round($ringCircumference * $ringProgress / 100, 3) }} {{ round($ringCircumference, 3) }}" />
                                    @endif
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-xl font-bold text-slate-950">{{ $averageProgress }}%</span>
                                    <span class="text-[10px] font-medium text-slate-400">Average</span>
                                </div>
                            </div>
                            <p class="mt-3 text-xs font-semibold text-emerald-600">{{ $totalStudents }} active learners</p>
                        </div>

                        {{-- Quiz performance trend --}}
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-700">Quiz Performance Trend</p>

                            @if($chartAttemptCount > 0)
                                <div class="mt-3 grid grid-cols-[36px_minmax(0,1fr)] gap-2">
                                    <div class="flex h-40 flex-col justify-between pb-0.5 text-right text-[11px] text-slate-400">
                                        @foreach([100, 75, 50, 25, 0] as $tick)
                                            <span>{{ $tick }}%</span>
                                        @endforeach
                                    </div>

                                    <div>
                                        <div class="relative h-40">
                                            <div class="absolute inset-0 flex flex-col justify-between">
                                                @foreach([100, 75, 50, 25, 0] as $tick)
                                                    <div class="border-t border-dashed border-slate-200"></div>
                                                @endforeach
                                            </div>

                                            <svg class="absolute inset-0 h-full w-full overflow-visible" viewBox="0 0 600 150" preserveAspectRatio="none" role="img" aria-label="Quiz performance trend">
                                                <defs>
                                                    <linearGradient id="tchTrendFill" x1="0" y1="0" x2="0" y2="1">
                                                        <stop offset="0" stop-color="#7c3aed" stop-opacity="0.22" />
                                                        <stop offset="1" stop-color="#7c3aed" stop-opacity="0" />
                                                    </linearGradient>
                                                </defs>
                                                @if($chartAttemptCount >= 2)
                                                    <polygon points="{{ $chartAreaPoints }}" fill="url(#tchTrendFill)" />
                                                    <polyline points="{{ $chartPoints }}" fill="none" stroke="#7c3aed" stroke-width="2.5"
                                                              stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                                                @endif
                                                {{-- Round dots that stay round although the SVG stretches. --}}
                                                @foreach($chartCoordinates as $point)
                                                    <line x1="{{ $point['x'] }}" y1="{{ $point['y'] }}" x2="{{ $point['x'] }}" y2="{{ $point['y'] }}"
                                                          stroke="#7c3aed" stroke-width="9" stroke-linecap="round" vector-effect="non-scaling-stroke">
                                                        <title>Attempt {{ $loop->iteration }}: {{ round($point['score']) }}%</title>
                                                    </line>
                                                @endforeach
                                            </svg>
                                        </div>

                                        <div class="mt-2 flex {{ $chartAttemptCount === 1 ? 'justify-center' : 'justify-between' }} text-[11px] text-slate-400">
                                            @foreach($chartCoordinates as $point)
                                                <span>{{ $loop->iteration }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="mt-3 flex h-40 items-center justify-center rounded-2xl border border-dashed border-slate-200 px-6 text-center text-sm font-medium text-slate-500">
                                    Quiz attempts will appear here once students submit assessments.
                                </div>
                            @endif
                        </div>

                    </div>

                </section>


                {{-- RECENT ACTIVITIES --}}
                <section class="sad-card overflow-hidden">

                    <div class="flex items-center justify-between gap-3 px-5 py-4 sm:px-6">
                        <div>
                            <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                                <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M12 7v5l3 2"></path>
                                </svg>
                                Recent Activities
                            </h2>
                            <p class="mt-1 text-xs text-slate-500">Latest activity from your courses</p>
                        </div>
                        <a href="{{ route('teacher.quiz-results.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-violet-600 hover:text-violet-700">
                            View All
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                        </a>
                    </div>

                    <ul class="divide-y divide-slate-100 border-t border-slate-100">
                        @forelse($activityItems as $activity)
                            @php
                                $tone = match ($activity['type']) {
                                    'quiz' => 'sad-emerald',
                                    'lesson' => 'sad-sky',
                                    default => 'sad-violet',
                                };
                            @endphp

                            <li class="flex items-center gap-3 px-5 py-3.5 sm:px-6">
                                <span class="sad-chip sad-chip-sm {{ $tone }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        @if($activity['type'] === 'quiz')
                                            <path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
                                        @elseif($activity['type'] === 'lesson')
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path>
                                        @else
                                            <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                        @endif
                                    </svg>
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-800">{{ $activity['title'] }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $activity['description'] }}</p>
                                </div>

                                <div class="flex shrink-0 flex-col items-end gap-1">
                                    @if($activity['score'] !== null)
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $activity['score'] >= 75 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600' }}">
                                            {{ $activity['score'] }}%
                                        </span>
                                    @endif
                                    <span class="text-[11px] text-slate-400">{{ $activity['time']->diffForHumans() }}</span>
                                </div>
                            </li>
                        @empty
                            <li class="px-6 py-10 text-center text-sm text-slate-500">No recent activities yet.</li>
                        @endforelse
                    </ul>

                </section>

            </div>


            {{-- =====================================================
                RIGHT RAIL
            ====================================================== --}}

            <aside class="min-w-0 space-y-6">

                {{-- Upcoming deadlines --}}
                <section class="sad-card overflow-hidden">
                    <div class="flex items-center justify-between gap-3 px-5 pt-5">
                        <div>
                            <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                                <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"></path>
                                </svg>
                                Upcoming
                            </h2>
                            <p class="mt-1 text-xs text-slate-500">Scheduled deadlines</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                            {{ $upcomingAssignments->count() }} {{ \Illuminate\Support\Str::plural('item', $upcomingAssignments->count()) }}
                        </span>
                    </div>

                    <ul class="mt-3 divide-y divide-slate-100 border-t border-slate-100">
                        @forelse($upcomingAssignments as $assignment)
                            @php $due = \Carbon\Carbon::parse($assignment->due_date); @endphp
                            <li class="flex items-center gap-3 px-5 py-3">
                                <span class="w-11 shrink-0 overflow-hidden rounded-xl border border-violet-100 bg-white text-center shadow-[0_6px_14px_-8px_rgba(124,58,237,0.5)]">
                                    <span class="block bg-violet-50 py-0.5 text-[10px] font-bold uppercase text-violet-600">{{ $due->format('M') }}</span>
                                    <span class="block py-1 text-sm font-bold text-slate-900">{{ $due->format('d') }}</span>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-800">{{ $assignment->title }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $assignment->course->title ?? 'Course' }} · {{ $due->format('g:i A') }}</p>
                                </div>
                            </li>
                        @empty
                            <li class="px-5 py-8 text-center">
                                <span class="sad-chip sad-violet mx-auto">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"></path></svg>
                                </span>
                                <p class="mt-3 text-sm font-semibold text-slate-800">Nothing scheduled</p>
                                <p class="mt-1 text-xs text-slate-500">Upcoming assignments will appear here.</p>
                            </li>
                        @endforelse
                    </ul>
                </section>

                {{-- Course status --}}
                <section class="sad-card p-5">
                    <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                        <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                        </svg>
                        Course Status
                    </h2>

                    <ul class="mt-4 space-y-2.5">
                        <li class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/60 p-2.5">
                            <span class="sad-chip sad-chip-sm sad-emerald">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"></path></svg>
                            </span>
                            <span class="flex-1 text-sm font-medium text-slate-600">Published</span>
                            <span class="text-base font-bold text-slate-950">{{ $publishedCourses }}</span>
                        </li>
                        <li class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/60 p-2.5">
                            <span class="sad-chip sad-chip-sm sad-amber">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                            </span>
                            <span class="flex-1 text-sm font-medium text-slate-600">Pending approval</span>
                            <span class="text-base font-bold text-slate-950">{{ $pendingCourses }}</span>
                        </li>
                        <li class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/60 p-2.5">
                            <span class="sad-chip sad-chip-sm sad-sky">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                            </span>
                            <span class="flex-1 text-sm font-medium text-slate-600">Draft or other</span>
                            <span class="text-base font-bold text-slate-950">{{ $draftOrOther }}</span>
                        </li>
                    </ul>

                    {{-- Latest courses, with their covers --}}
                    @if($recentCourses->isNotEmpty())
                        <ul class="mt-4 space-y-2.5 border-t border-slate-100 pt-4">
                            @foreach($recentCourses as $recentCourse)
                                <li>
                                    <a href="{{ route('teacher.my-courses') }}" class="flex items-center gap-3 rounded-2xl p-1.5 transition-colors hover:bg-slate-50">
                                        <x-pw.course-cover :course="$recentCourse" variant="thumb" />
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-semibold text-slate-800">{{ $recentCourse->title }}</span>
                                            <span class="block text-xs text-slate-500">{{ ucfirst((string) $recentCourse->status) }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                {{-- Quick actions (existing teacher pages only) --}}
                <section class="sad-card p-5">
                    <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                        <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"></path></svg>
                        Quick Actions
                    </h2>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        @foreach($quickActions as $action)
                            <a href="{{ $action['href'] }}" class="sad-lift flex flex-col items-center gap-2 rounded-2xl border border-slate-100 bg-slate-50/60 px-2 py-4 text-center">
                                <span class="sad-chip sad-chip-sm {{ $action['tone'] }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $action['icon'] }}"></path></svg>
                                </span>
                                <span class="text-xs font-semibold text-slate-700">{{ $action['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>

            </aside>

        </div>

    </main>

</div>

</x-layouts::app>
