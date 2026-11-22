<x-layouts::app :title="'Dashboard'">

{{--
    Student dashboard in the soft-3D style (styles in partials/soft3d).
    Everything shown comes from DashboardController@student; nothing is
    sample data.
--}}

@php
    $studentName = auth()->user()->name ?? 'Student';

    $firstName = collect(
        preg_split('/\s+/', trim($studentName))
    )->filter()->first() ?? 'Student';

    $studentEnrollments = collect($enrollments ?? []);
    $studentQuizResults = collect($quizResults ?? []);

    $totalCourses = $studentEnrollments->count();

    $activeCount = isset($activeCourses)
        ? (int) $activeCourses
        : $studentEnrollments
            ->filter(fn ($item) => strtolower($item->status ?? '') === 'active')
            ->count();

    $completedCount = isset($completedCourses)
        ? (int) $completedCourses
        : $studentEnrollments
            ->filter(fn ($item) => strtolower($item->status ?? '') === 'completed')
            ->count();

    $quizCount = isset($quizzesTaken)
        ? (int) $quizzesTaken
        : $studentQuizResults->count();

    $averageScoreValue = isset($averageScore)
        ? (float) $averageScore
        : (
            $studentQuizResults->count() > 0
                ? (float) $studentQuizResults->avg('percentage')
                : 0
        );

    $averageScoreValue = min(
        max($averageScoreValue, 0),
        100
    );

    $certificateCount = isset($certificatesEarned)
        ? (int) $certificatesEarned
        : 0;

    $overallProgress = $totalCourses > 0
        ? (float) $studentEnrollments->avg('progress_percentage')
        : 0;

    $overallProgress = min(
        max($overallProgress, 0),
        100
    );

    $completionRate = $totalCourses > 0
        ? ($completedCount / $totalCourses) * 100
        : 0;

    $completionRate = min(
        max($completionRate, 0),
        100
    );

    // Courses still in progress first, then completed ones (newest first
    // within each group).
    $recentCourses = $studentEnrollments
        ->sortBy(fn ($enrollment) => strtolower((string) ($enrollment->status ?? 'active')) === 'completed' ? 1 : 0)
        ->take(4)
        ->values();
    $recentResults = $studentQuizResults->take(4);

    $recommended = null;

    if (isset($recommendedCourse) && $recommendedCourse) {
        $recommended = $recommendedCourse->course ?? $recommendedCourse;
    }

    // Greeting in the same local time the other dashboards use.
    $hour = now('Asia/Manila')->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

    // Quiz performance chart: the latest five attempts, oldest first, 0-100%.
    $chartAttempts = $studentQuizResults->take(5)->reverse()->values();
    $chartCount = $chartAttempts->count();
    $chartPoints = $chartAttempts->map(function ($result, $i) use ($chartCount) {
        $score = min(max((float) ($result->percentage ?? 0), 0), 100);

        return [
            'x' => $chartCount > 1 ? round($i * (600 / ($chartCount - 1)), 1) : 300,
            'y' => round(200 - ($score / 100) * 200, 1),
            'score' => $score,
            'title' => $result->quiz?->title ?? 'Quiz',
            'label' => $result->completed_at
                ? \Illuminate\Support\Carbon::parse($result->completed_at)->format('M d')
                : 'Attempt '.($i + 1),
        ];
    });
    $chartLine = $chartPoints->map(fn ($p) => $p['x'].','.$p['y'])->implode(' ');
    $chartArea = $chartCount > 1 ? '0,200 '.$chartLine.' 600,200' : '';

    // Completion ring (circle of radius 42).
    $ringCircumference = 2 * M_PI * 42;

    $thumbnailUrl = fn ($thumbnail) => \App\Models\Course::thumbnailUrlFor($thumbnail);

    $quickActions = [
        ['label' => 'Browse Courses', 'href' => route('student.marketplace'), 'tone' => 'sad-violet', 'icon' => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.35-4.35'],
        ['label' => 'My Courses', 'href' => route('student.my-courses'), 'tone' => 'sad-sky', 'icon' => 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z'],
        ['label' => 'Assignments', 'href' => route('student.assignments.index'), 'tone' => 'sad-emerald', 'icon' => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M8 13h8M8 17h6'],
        ['label' => 'Learning Paths', 'href' => route('student.learning-paths'), 'tone' => 'sad-violet', 'icon' => 'M6 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM18 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM8 19h7a3 3 0 0 0 0-6H9a3 3 0 0 1 0-6h7'],
        ['label' => 'Recommendations', 'href' => route('student.recommendations'), 'tone' => 'sad-amber', 'icon' => 'm12 3 1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3zM19 16l.8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8L19 16z'],
        ['label' => 'Certificates', 'href' => route('student.certificates'), 'tone' => 'sad-rose', 'icon' => 'M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10zM8.5 12.5 7 21l5-2 5 2-1.5-8.5'],
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
                            Student Dashboard
                        </p>

                        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-[26px]">
                            {{ $greeting }}, {{ $firstName }}!&nbsp;<span aria-hidden="true">👋</span>
                        </h1>

                        <p class="mt-2 max-w-xl text-sm leading-6 text-slate-600">
                            Continue learning, track your progress, review your quiz performance,
                            and discover your next course in PathWise.
                        </p>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="{{ route('student.marketplace') }}" class="sad-btn sad-btn-primary">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.35-4.35"></path>
                                </svg>
                                Browse Courses
                            </a>

                            <a href="{{ route('student.my-courses') }}" class="sad-btn sad-btn-light">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                </svg>
                                My Courses
                            </a>
                        </div>

                    </div>

                    {{-- Soft-3D learning artwork, drawn inline (no image files). --}}
                    <svg class="sad-banner-art" viewBox="0 0 360 220" aria-hidden="true">
                        <defs>
                            <linearGradient id="stuPage" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#ffffff" />
                                <stop offset="1" stop-color="#ede9fe" />
                            </linearGradient>
                            <linearGradient id="stuCover" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#a78bfa" />
                                <stop offset="1" stop-color="#7c3aed" />
                            </linearGradient>
                            <linearGradient id="stuCap" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#4c1d95" />
                                <stop offset="1" stop-color="#312e81" />
                            </linearGradient>
                            <linearGradient id="stuStar" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#fde68a" />
                                <stop offset="1" stop-color="#f59e0b" />
                            </linearGradient>
                            <linearGradient id="stuTile" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#ffffff" />
                                <stop offset="1" stop-color="#ede9fe" />
                            </linearGradient>
                            <filter id="stuSoft" x="-20%" y="-20%" width="140%" height="160%">
                                <feDropShadow dx="0" dy="8" stdDeviation="7" flood-color="#4c1d95" flood-opacity="0.18" />
                            </filter>
                        </defs>

                        <circle cx="230" cy="120" r="96" fill="#ffffff" opacity="0.35" />
                        <ellipse cx="200" cy="206" rx="130" ry="10" fill="#7c3aed" opacity="0.08" />

                        {{-- Progress tile --}}
                        <g filter="url(#stuSoft)">
                            <rect x="262" y="34" width="84" height="84" rx="18" fill="url(#stuTile)" />
                            <circle cx="304" cy="76" r="24" fill="none" stroke="#ede9fe" stroke-width="8" />
                            <circle cx="304" cy="76" r="24" fill="none" stroke="#7c3aed" stroke-width="8" stroke-linecap="round"
                                    stroke-dasharray="110 151" transform="rotate(-90 304 76)" />
                        </g>

                        {{-- Open book --}}
                        <g filter="url(#stuSoft)">
                            <path d="M90 196 L196 182 L302 196 L302 204 L196 190 L90 204 Z" fill="url(#stuCover)" />
                            <path d="M100 116 Q148 104 196 124 L196 186 Q148 166 100 178 Z" fill="url(#stuPage)" />
                            <path d="M292 116 Q244 104 196 124 L196 186 Q244 166 292 178 Z" fill="url(#stuPage)" />
                            <path d="M116 132 Q148 124 180 136 M116 146 Q148 138 180 150 M116 160 Q140 154 168 162" fill="none" stroke="#c4b5fd" stroke-width="3" stroke-linecap="round" />
                            <path d="M212 136 Q244 124 276 132 M212 150 Q244 138 276 146" fill="none" stroke="#c4b5fd" stroke-width="3" stroke-linecap="round" />
                        </g>

                        {{-- Graduation cap above the book --}}
                        <g filter="url(#stuSoft)">
                            <path d="M150 72 L196 54 L242 72 L196 90 Z" fill="url(#stuCap)" />
                            <path d="M170 77 L170 92 Q196 104 222 92 L222 77 L196 87 Z" fill="#4338ca" />
                            <path d="M196 72 L232 78 L232 98" fill="none" stroke="#fbbf24" stroke-width="3" stroke-linecap="round" />
                            <circle cx="232" cy="101" r="5" fill="#f59e0b" />
                        </g>

                        {{-- Stars --}}
                        <path d="M70 70 l5 10 11 2 -8 8 2 11 -10 -5 -10 5 2 -11 -8 -8 11 -2z" fill="url(#stuStar)" opacity="0.9" />
                        <path d="M118 30 l3 6 7 1 -5 5 1 7 -6 -3 -6 3 1 -7 -5 -5 7 -1z" fill="url(#stuStar)" opacity="0.7" />
                    </svg>

                </section>


                {{-- SUMMARY CARDS (Manuscript Fig. 38: Enrolled Courses, Quizzes
                     Taken, Certificates Earned and Average Score, plus Average
                     Progress). Label and value stay in their own elements. --}}
                <section class="grid grid-cols-2 gap-4 md:grid-cols-3 2xl:grid-cols-5" data-student-summary>

                    <a href="{{ route('student.my-courses') }}" class="sad-card sad-lift block p-5">
                        <span class="sad-chip sad-violet">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                            </svg>
                        </span>
                        <p class="mt-4 text-sm font-medium text-slate-500">Enrolled Courses</p>
                        <p class="mt-1 text-3xl font-bold tracking-tight text-slate-950">{{ $totalCourses }}</p>
                        <p class="mt-2 text-xs font-medium text-slate-400">{{ $activeCount }} currently active</p>
                    </a>

                    <div class="sad-card p-5">
                        <span class="sad-chip sad-sky">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 11l3 3L22 4"></path>
                                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
                            </svg>
                        </span>
                        <p class="mt-4 text-sm font-medium text-slate-500">Quizzes Taken</p>
                        <p class="mt-1 text-3xl font-bold tracking-tight text-slate-950">{{ $quizCount }}</p>
                        <p class="mt-2 text-xs font-medium text-slate-400">Quiz {{ \Illuminate\Support\Str::plural('attempt', $quizCount) }} so far</p>
                    </div>

                    <a href="{{ route('student.certificates') }}" class="sad-card sad-lift block p-5">
                        <span class="sad-chip sad-amber">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="8" r="5"></circle>
                                <path d="M8.5 12.5 7 21l5-2 5 2-1.5-8.5"></path>
                            </svg>
                        </span>
                        <p class="mt-4 text-sm font-medium text-slate-500">Certificates Earned</p>
                        <p class="mt-1 text-3xl font-bold tracking-tight text-slate-950">{{ $certificateCount }}</p>
                        <p class="mt-2 text-xs font-medium text-slate-400">View your certificates</p>
                    </a>

                    <div class="sad-card p-5">
                        <span class="sad-chip sad-emerald">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m5 12 4 4L19 6"></path>
                            </svg>
                        </span>
                        <p class="mt-4 text-sm font-medium text-slate-500">Average Score</p>
                        <p class="mt-1 text-3xl font-bold tracking-tight text-slate-950">{{ number_format($averageScoreValue, 1) }}%</p>
                        <p class="mt-2 text-xs font-medium text-slate-400">Across all quiz attempts</p>
                    </div>

                    <div class="sad-card col-span-2 p-5 md:col-span-2 2xl:col-span-1">
                        <span class="sad-chip sad-rose">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 3v18h18"></path>
                                <path d="m7 16 4-5 4 3 5-7"></path>
                            </svg>
                        </span>
                        <p class="mt-4 text-sm font-medium text-slate-500">Average Progress</p>
                        <p class="mt-1 text-3xl font-bold tracking-tight text-slate-950">{{ number_format($overallProgress, 1) }}%</p>
                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-violet-100">
                            <div class="h-full rounded-full bg-linear-to-r from-violet-400 to-violet-600" style="width: {{ $overallProgress }}%;"></div>
                        </div>
                    </div>

                </section>


                {{-- CONTINUE LEARNING --}}
                <section class="sad-card overflow-hidden">

                    <div class="flex items-center justify-between gap-3 px-5 py-4 sm:px-6">
                        <div>
                            <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                                <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="6 4 20 12 6 20 6 4"></polygon>
                                </svg>
                                Continue Learning
                            </h2>
                            <p class="mt-1 text-xs text-slate-500">Pick up where you left off.</p>
                        </div>
                        <a href="{{ route('student.my-courses') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-violet-600 hover:text-violet-700">
                            View All
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                        </a>
                    </div>

                    @if($recentCourses->isNotEmpty())

                        <div class="grid grid-cols-1 gap-4 border-t border-slate-100 p-5 sm:grid-cols-2 sm:p-6">

                            @foreach($recentCourses as $enrollment)

                                @php
                                    $course = $enrollment->course;
                                    $courseProgress = min(max((float) ($enrollment->progress_percentage ?? 0), 0), 100);
                                    $isCompleted = strtolower($enrollment->status ?? 'active') === 'completed';
                                    $isUnderReview = strtolower(trim((string) $course?->status)) === 'pending';
                                    $courseThumb = $thumbnailUrl($course?->thumbnail);
                                @endphp

                                <article class="sad-lift flex flex-col rounded-2xl border border-slate-100 bg-white p-3">

                                    @if($courseThumb)
                                        <img src="{{ $courseThumb }}" alt="{{ $course?->title ?? 'Course' }}" class="h-28 w-full rounded-xl object-cover">
                                    @else
                                        <div class="flex h-28 w-full items-center justify-center rounded-xl bg-linear-to-br from-violet-100 via-indigo-50 to-sky-100">
                                            <span class="sad-chip sad-violet">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                                </svg>
                                            </span>
                                        </div>
                                    @endif

                                    <div class="flex flex-1 flex-col px-1 pt-3">
                                        <p class="truncate text-[11px] font-bold uppercase tracking-[0.1em] text-violet-600">
                                            {{ $course?->category?->name ?? 'Course' }}
                                        </p>
                                        <h3 class="mt-1 truncate text-sm font-bold text-slate-900">
                                            {{ $course?->title ?? 'Course unavailable' }}
                                        </h3>

                                        <div class="mt-3 flex items-center justify-between text-xs">
                                            <span class="font-medium {{ $isCompleted ? 'text-emerald-700' : 'text-slate-500' }}" data-test="course-progress-label">{{ $isCompleted ? 'Completed' : 'In progress' }}</span>
                                            <span class="font-bold {{ $isCompleted ? 'text-emerald-600' : 'text-violet-700' }}">{{ number_format($courseProgress, 0) }}%</span>
                                        </div>
                                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full {{ $isCompleted ? 'bg-emerald-500' : 'bg-linear-to-r from-violet-400 to-violet-600' }}" style="width: {{ $courseProgress }}%;"></div>
                                        </div>

                                        <div class="mt-3 flex justify-end">
                                            @if($isUnderReview)
                                                <p class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-900" data-course-under-review>Course Under Review</p>
                                            @elseif($course && ! $isCompleted)
                                                <a href="{{ route('student.learn.course', $course) }}" class="sad-btn sad-btn-primary h-9! px-3.5! text-xs!">
                                                    Continue
                                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"></path></svg>
                                                </a>
                                            @elseif($isCompleted)
                                                {{-- Same rule as My Courses: issued -> View Certificate; otherwise "Certificate pending". --}}
                                                @include('Student.partials.certificate-status', [
                                                    'status' => \App\Support\StudentCertificateStatus::for($enrollment, $course, (int) auth()->id(), ($issuedCertificates ?? collect())->get($course?->id)),
                                                    'size' => 'sm',
                                                ])
                                            @endif
                                        </div>
                                    </div>

                                </article>

                            @endforeach

                        </div>

                    @else

                        <div class="border-t border-slate-100 px-6 py-12 text-center">
                            <span class="sad-chip sad-violet mx-auto">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                </svg>
                            </span>
                            <h3 class="mt-4 text-sm font-semibold text-slate-800">No courses yet</h3>
                            <p class="mx-auto mt-1 max-w-sm text-xs leading-5 text-slate-500">Browse available courses and start your learning journey.</p>
                            <a href="{{ route('student.marketplace') }}" class="sad-btn sad-btn-primary mt-5">Browse Courses</a>
                        </div>

                    @endif

                </section>


                {{-- QUIZ PERFORMANCE + RECENT QUIZ RESULTS --}}
                <div class="grid grid-cols-1 gap-6 2xl:grid-cols-[minmax(0,1fr)_minmax(0,1.25fr)]">

                    {{-- Quiz performance (latest five attempts) --}}
                    <section class="sad-card p-5 sm:p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                                <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 3v18h18"></path>
                                    <path d="m7 16 4-5 4 3 5-7"></path>
                                </svg>
                                Quiz Performance
                            </h2>
                            <span class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-500">
                                Latest {{ $chartCount }} {{ \Illuminate\Support\Str::plural('attempt', $chartCount) }}
                            </span>
                        </div>

                        @if($chartCount > 0)
                            <div class="mt-5 grid grid-cols-[36px_minmax(0,1fr)] gap-2">
                                <div class="flex h-44 flex-col justify-between pb-0.5 text-right text-[11px] text-slate-400">
                                    @foreach([100, 75, 50, 25, 0] as $tick)
                                        <span>{{ $tick }}%</span>
                                    @endforeach
                                </div>

                                <div>
                                    <div class="relative h-44">
                                        <div class="absolute inset-0 flex flex-col justify-between">
                                            @foreach([100, 75, 50, 25, 0] as $tick)
                                                <div class="border-t border-dashed border-slate-200"></div>
                                            @endforeach
                                        </div>

                                        <svg class="absolute inset-0 h-full w-full overflow-visible" viewBox="0 0 600 200" preserveAspectRatio="none" role="img" aria-label="Your latest quiz scores">
                                            <defs>
                                                <linearGradient id="stuQuizFill" x1="0" y1="0" x2="0" y2="1">
                                                    <stop offset="0" stop-color="#7c3aed" stop-opacity="0.22" />
                                                    <stop offset="1" stop-color="#7c3aed" stop-opacity="0" />
                                                </linearGradient>
                                            </defs>
                                            @if($chartArea)
                                                <polygon points="{{ $chartArea }}" fill="url(#stuQuizFill)" />
                                                <polyline points="{{ $chartLine }}" fill="none" stroke="#7c3aed" stroke-width="2.5"
                                                          stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                                            @endif
                                            {{-- Round dots that stay round although the SVG stretches. --}}
                                            @foreach($chartPoints as $p)
                                                <line x1="{{ $p['x'] }}" y1="{{ $p['y'] }}" x2="{{ $p['x'] }}" y2="{{ $p['y'] }}"
                                                      stroke="#7c3aed" stroke-width="9" stroke-linecap="round" vector-effect="non-scaling-stroke">
                                                    <title>{{ $p['title'] }}: {{ number_format($p['score'], 1) }}%</title>
                                                </line>
                                            @endforeach
                                        </svg>
                                    </div>

                                    <div class="mt-2 flex {{ $chartCount > 1 ? 'justify-between' : 'justify-center' }} text-[11px] text-slate-400">
                                        @foreach($chartPoints as $p)
                                            <span>{{ $p['label'] }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="mt-5 flex h-44 flex-col items-center justify-center text-center">
                                <span class="sad-chip sad-violet">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="m7 16 4-5 4 3 5-7"></path></svg>
                                </span>
                                <p class="mt-3 text-sm font-semibold text-slate-800">No quiz attempts yet</p>
                                <p class="mt-1 text-xs text-slate-500">Your quiz scores will be charted here.</p>
                            </div>
                        @endif
                    </section>

                    {{-- Recent quiz results --}}
                    <section class="sad-card overflow-hidden">

                        <div class="flex items-center justify-between gap-3 px-5 py-4 sm:px-6">
                            <div>
                                <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                                    <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 11l3 3L22 4"></path>
                                        <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
                                    </svg>
                                    Recent Quiz Results
                                </h2>
                                <p class="mt-1 text-xs text-slate-500">Your latest assessment performance.</p>
                            </div>
                            <span class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">{{ $quizCount }} total</span>
                        </div>

                        <ul class="divide-y divide-slate-100 border-t border-slate-100">
                            @forelse($recentResults as $result)
                                @php
                                    $resultPercentage = min(max((float) ($result->percentage ?? 0), 0), 100);
                                    $isPassed = strtolower($result->remarks ?? '') === 'passed';
                                @endphp

                                <li class="flex items-center gap-3 px-5 py-3.5 sm:px-6">
                                    <span class="sad-chip sad-chip-sm {{ $isPassed ? 'sad-emerald' : 'sad-rose' }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                            @if($isPassed)
                                                <path d="m5 12 4 4L19 6"></path>
                                            @else
                                                <path d="M18 6 6 18M6 6l12 12"></path>
                                            @endif
                                        </svg>
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-slate-800">{{ $result->quiz?->title ?? 'Quiz' }}</p>
                                        <p class="truncate text-xs text-slate-500">
                                            {{ $result->quiz?->course?->title ?? 'Course' }}
                                            · Attempt {{ $result->attempt_number ?? 1 }}
                                            @if($result->completed_at)
                                                · {{ \Carbon\Carbon::parse($result->completed_at)->format('M d, Y') }}
                                            @endif
                                        </p>
                                    </div>

                                    <div class="flex shrink-0 flex-col items-end gap-1">
                                        <span class="text-sm font-bold {{ $isPassed ? 'text-emerald-600' : 'text-rose-500' }}">{{ number_format($resultPercentage, 1) }}%</span>
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold {{ $isPassed ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600' }}">
                                            {{ $isPassed ? 'Passed' : 'Failed' }} · {{ $result->score ?? 0 }}/{{ $result->total_items ?? 0 }}
                                        </span>
                                    </div>
                                </li>
                            @empty
                                <li class="px-6 py-12 text-center">
                                    <p class="text-sm font-semibold text-slate-800">No quiz attempts yet</p>
                                    <p class="mt-1 text-xs text-slate-500">Your assessment results will appear here.</p>
                                </li>
                            @endforelse
                        </ul>

                    </section>

                </div>

            </div>


            {{-- =====================================================
                RIGHT RAIL
            ====================================================== --}}

            <aside class="min-w-0 space-y-6">

                {{-- Learning overview --}}
                <section class="sad-card p-5">
                    <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                        <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 3v18h18"></path>
                            <path d="m7 16 4-5 4 3 5-7"></path>
                        </svg>
                        Learning Overview
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">A quick look at your current learning journey.</p>

                    <div class="mt-4 flex items-center gap-4">
                        <div class="relative h-28 w-28 shrink-0">
                            <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90" role="img" aria-label="Completion rate">
                                <circle cx="50" cy="50" r="42" fill="none" stroke="#ede9fe" stroke-width="11" />
                                @if($completionRate > 0)
                                    <circle cx="50" cy="50" r="42" fill="none" stroke="#7c3aed" stroke-width="11" stroke-linecap="round"
                                            stroke-dasharray="{{ round($ringCircumference * $completionRate / 100, 3) }} {{ round($ringCircumference, 3) }}" />
                                @endif
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-xl font-bold text-slate-950">{{ number_format($completionRate, 0) }}%</span>
                                <span class="text-[10px] font-medium text-slate-400">completed</span>
                            </div>
                        </div>

                        <div class="min-w-0 flex-1 space-y-2">
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2">
                                <span class="text-xs font-medium text-slate-500">Active</span>
                                <span class="text-base font-bold text-violet-700">{{ $activeCount }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2">
                                <span class="text-xs font-medium text-slate-500">Completed</span>
                                <span class="text-base font-bold text-emerald-600">{{ $completedCount }}</span>
                            </div>
                            <p class="text-right text-[11px] text-slate-400">{{ $completedCount }}/{{ $totalCourses }} courses</p>
                        </div>
                    </div>

                    @if(! empty($learningLevel) || ! empty($strongestCategory))
                        {{-- Label above value, so long category names wrap instead of being cut off. --}}
                        <dl class="mt-4 space-y-3 border-t border-slate-100 pt-4 text-sm">
                            @if(! empty($learningLevel))
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">Learning level</dt>
                                    <dd class="mt-0.5 font-semibold text-slate-800">{{ $learningLevel }}</dd>
                                </div>
                            @endif
                            @if(! empty($strongestCategory))
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">Strongest area</dt>
                                    <dd class="mt-0.5 break-words font-semibold leading-5 text-slate-800">{{ $strongestCategory->category_name }}</dd>
                                </div>
                            @endif
                        </dl>
                    @endif
                </section>

                {{-- Recommended for you --}}
                <section class="sad-card overflow-hidden">
                    <div class="px-5 pt-5">
                        <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                            <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m12 3 1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3Z"></path>
                                <path d="m19 16 .8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8L19 16Z"></path>
                            </svg>
                            Recommended for You
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">Suggested next, based on your quiz performance.</p>
                    </div>

                    @if($recommended)
                        <div class="p-5">
                            <x-pw.course-cover :course="$recommended" framed />

                            <p class="mt-4 text-[11px] font-bold uppercase tracking-[0.1em] text-violet-600">
                                {{ $recommended->category?->name ?? 'Recommended Course' }}
                            </p>
                            <h3 class="mt-1 line-clamp-2 text-base font-semibold leading-6 text-slate-900">{{ $recommended->title }}</h3>

                            @if($recommended->description)
                                <p class="mt-2 line-clamp-3 text-xs leading-5 text-slate-500">{{ $recommended->description }}</p>
                            @endif

                            <div class="mt-3 flex flex-wrap gap-2">
                                @if($recommended->difficulty_level)
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ ucfirst($recommended->difficulty_level) }}</span>
                                @endif
                                @if($recommended->estimated_hours)
                                    <span class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">{{ $recommended->estimated_hours }} hrs</span>
                                @endif
                            </div>

                            <a href="{{ route('student.course.show', $recommended) }}" class="sad-btn sad-btn-primary mt-4 w-full">
                                View Course
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"></path></svg>
                            </a>
                        </div>
                    @else
                        <div class="px-5 py-8 text-center">
                            <span class="sad-chip sad-violet mx-auto">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m12 3 1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3Z"></path>
                                </svg>
                            </span>
                            <p class="mt-3 text-sm font-semibold text-slate-800">No recommendation yet</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Continue learning and taking quizzes to receive personalized suggestions.</p>
                            <a href="{{ route('student.marketplace') }}" class="sad-btn sad-btn-light mt-4 border border-violet-100">Browse Courses</a>
                        </div>
                    @endif
                </section>

                {{-- Quick actions (existing student pages only) --}}
                <section class="sad-card p-5">
                    <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                        <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"></path></svg>
                        Quick Actions
                    </h2>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        @foreach($quickActions as $action)
                            <a href="{{ $action['href'] }}" class="sad-lift flex flex-col items-center gap-2 rounded-2xl border border-slate-100 bg-slate-50/60 px-2 py-4 text-center">
                                <span class="sad-chip sad-chip-sm {{ $action['tone'] }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="{{ $action['icon'] }}"></path></svg>
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
