<x-layouts::app :title="'Dashboard'">

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

    $recentCourses = $studentEnrollments->take(4);
    $recentResults = $studentQuizResults->take(4);

    $recommended = null;

    if (isset($recommendedCourse) && $recommendedCourse) {
        $recommended = $recommendedCourse->course ?? $recommendedCourse;
    }
@endphp


<div class="min-h-screen bg-slate-50/70">
    <main class="px-4 py-6 sm:px-5 lg:px-6 lg:py-7">
        <div class="w-full max-w-none space-y-6">

            {{-- =========================================================
                HEADER
            ========================================================== --}}
            <section class="overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">
                <div class="relative px-6 py-7 sm:px-8 sm:py-8">
                    <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-violet-100/70 blur-3xl"></div>
                    <div class="pointer-events-none absolute right-28 top-16 h-24 w-24 rounded-full bg-indigo-100/60 blur-2xl"></div>

                    <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="max-w-2xl">
                            <div class="inline-flex items-center gap-2 rounded-full bg-violet-50 px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-violet-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-violet-500"></span>
                                Student Dashboard
                            </div>

                            <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                                Welcome back, {{ $firstName }}
                            </h1>

                            <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">
                                Continue learning, track your progress, review your quiz performance,
                                and discover your next course in PathWise.
                            </p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <a
                                href="{{ route('student.my-courses') }}"
                                class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                </svg>
                                My Courses
                            </a>

                            <a
                                href="{{ route('student.marketplace') }}"
                                class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.35-4.35"></path>
                                </svg>
                                Browse Courses
                            </a>
                        </div>
                    </div>
                </div>
            </section>


            {{-- =========================================================
                SUMMARY CARDS
            ========================================================== --}}
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold text-slate-500">
                                My Courses
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                                {{ $totalCourses }}
                            </p>

                            <p class="mt-2 text-xs text-slate-400">
                                {{ $activeCount }} currently active
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>


                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold text-slate-500">
                                Average Progress
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-violet-700">
                                {{ number_format($overallProgress, 1) }}%
                            </p>

                            <p class="mt-2 text-xs text-slate-400">
                                Across all enrolled courses
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 3v18h18"></path>
                                <path d="m7 16 4-5 4 3 5-7"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full bg-violet-600"
                            style="width: {{ $overallProgress }}%;"
                        ></div>
                    </div>
                </div>


                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold text-slate-500">
                                Average Quiz Score
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-600">
                                {{ number_format($averageScoreValue, 1) }}%
                            </p>

                            <p class="mt-2 text-xs text-slate-400">
                                {{ $quizCount }}
                                {{ \Illuminate\Support\Str::plural('attempt', $quizCount) }}
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m5 12 4 4L19 6"></path>
                            </svg>
                        </div>
                    </div>
                </div>


                <a
                    href="{{ route('student.certificates') }}"
                    class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-violet-200 hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold text-slate-500">
                                Certificates
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                                {{ $certificateCount }}
                            </p>

                            <p class="mt-2 text-xs text-slate-400">
                                View earned achievements
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 transition group-hover:bg-violet-50 group-hover:text-violet-600">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="8" r="5"></circle>
                                <path d="M8.5 12.5 7 21l5-2 5 2-1.5-8.5"></path>
                            </svg>
                        </div>
                    </div>
                </a>

            </section>


            {{-- =========================================================
                CONTINUE LEARNING + OVERVIEW
            ========================================================== --}}
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-5 sm:px-6">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">
                                Continue Learning
                            </h2>

                            <p class="mt-1 text-xs text-slate-500">
                                Pick up where you left off.
                            </p>
                        </div>

                        <a
                            href="{{ route('student.my-courses') }}"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-violet-600 transition hover:text-violet-800"
                        >
                            View all
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </a>
                    </div>


                    @if($recentCourses->isNotEmpty())

                        <div class="divide-y divide-slate-100">

                            @foreach($recentCourses as $enrollment)

                                @php
                                    $course = $enrollment->course;

                                    $courseProgress = (float) (
                                        $enrollment->progress_percentage ?? 0
                                    );

                                    $courseProgress = min(
                                        max($courseProgress, 0),
                                        100
                                    );

                                    $status = strtolower(
                                        $enrollment->status ?? 'active'
                                    );

                                    $isCompleted = $status === 'completed';

                                    $thumbnail = $course?->thumbnail ?? null;
                                    $thumbnailUrl = null;

                                    if ($thumbnail) {
                                        if (
                                            \Illuminate\Support\Str::startsWith(
                                                $thumbnail,
                                                ['http://', 'https://']
                                            )
                                        ) {
                                            $thumbnailUrl = $thumbnail;
                                        } else {
                                            $thumbnailUrl = asset(
                                                'storage/' . ltrim($thumbnail, '/')
                                            );
                                        }
                                    }
                                @endphp

                                <article class="p-5 transition hover:bg-slate-50/70 sm:p-6">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">

                                        @if($thumbnailUrl)
                                            <img
                                                src="{{ $thumbnailUrl }}"
                                                alt="{{ $course?->title ?? 'Course' }}"
                                                class="h-32 w-full shrink-0 rounded-2xl object-cover sm:h-24 sm:w-36"
                                            >
                                        @else
                                            <div class="flex h-32 w-full shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-100 via-indigo-50 to-sky-100 text-violet-600 sm:h-24 sm:w-36">
                                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                                </svg>
                                            </div>
                                        @endif


                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-bold uppercase tracking-[0.1em] text-violet-500">
                                                        {{ $course?->category?->name ?? 'Course' }}
                                                    </p>

                                                    <h3 class="mt-1 truncate text-sm font-bold text-slate-900 sm:text-base">
                                                        {{ $course?->title ?? 'Course unavailable' }}
                                                    </h3>
                                                </div>

                                                <span
                                                    class="inline-flex w-fit items-center rounded-full px-2.5 py-1 text-[10px] font-bold
                                                        {{ $isCompleted
                                                            ? 'bg-emerald-50 text-emerald-700'
                                                            : 'bg-violet-50 text-violet-700'
                                                        }}"
                                                >
                                                    {{ $isCompleted ? 'Completed' : number_format($courseProgress, 0) . '%' }}
                                                </span>
                                            </div>


                                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                                                <div
                                                    class="h-full rounded-full
                                                        {{ $isCompleted
                                                            ? 'bg-emerald-500'
                                                            : 'bg-violet-600'
                                                        }}"
                                                    style="width: {{ $courseProgress }}%;"
                                                ></div>
                                            </div>


                                            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                                <span class="text-[11px] font-medium text-slate-400">
                                                    {{ $isCompleted ? 'Course completed' : 'In progress' }}
                                                </span>

                                                @if($course && !$isCompleted)
                                                    <a
                                                        href="{{ route('student.learn.course', $course) }}"
                                                        class="inline-flex h-8 items-center justify-center gap-1 rounded-lg bg-violet-600 px-3 text-[11px] font-semibold text-white transition hover:bg-violet-700"
                                                    >
                                                        Continue
                                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="m9 18 6-6-6-6"></path>
                                                        </svg>
                                                    </a>
                                                @elseif($isCompleted)
                                                    <a
                                                        href="{{ route('student.certificates') }}"
                                                        class="inline-flex h-8 items-center justify-center gap-1 rounded-lg bg-emerald-50 px-3 text-[11px] font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                                    >
                                                        Certificate
                                                    </a>
                                                @endif
                                            </div>
                                        </div>

                                    </div>
                                </article>

                            @endforeach

                        </div>

                    @else

                        <div class="px-6 py-16 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                </svg>
                            </div>

                            <h3 class="mt-4 text-sm font-bold text-slate-800">
                                No courses yet
                            </h3>

                            <p class="mx-auto mt-1 max-w-sm text-xs leading-5 text-slate-400">
                                Browse available courses and start your learning journey.
                            </p>

                            <a
                                href="{{ route('student.marketplace') }}"
                                class="mt-5 inline-flex h-10 items-center justify-center rounded-xl bg-violet-600 px-4 text-xs font-semibold text-white transition hover:bg-violet-700"
                            >
                                Browse Courses
                            </a>
                        </div>

                    @endif
                </section>


                <aside class="space-y-5">

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-violet-500">
                                    Progress
                                </p>

                                <h2 class="mt-1 text-base font-bold text-slate-900">
                                    Learning Overview
                                </h2>

                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    A quick look at your current learning journey.
                                </p>
                            </div>

                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 3v18h18"></path>
                                    <path d="m7 16 4-5 4 3 5-7"></path>
                                </svg>
                            </div>
                        </div>


                        <div class="mt-6 rounded-2xl border border-violet-100 bg-violet-50/70 p-5">
                            <div class="flex items-end justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.1em] text-violet-500">
                                        Completion Rate
                                    </p>

                                    <p class="mt-2 text-3xl font-bold tracking-tight text-violet-900">
                                        {{ number_format($completionRate, 0) }}%
                                    </p>
                                </div>

                                <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-violet-700 shadow-sm">
                                    {{ $completedCount }}/{{ $totalCourses }}
                                </span>
                            </div>

                            <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-violet-100">
                                <div
                                    class="h-full rounded-full bg-violet-600"
                                    style="width: {{ $completionRate }}%;"
                                ></div>
                            </div>
                        </div>


                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                    Active
                                </p>

                                <p class="mt-1 text-2xl font-bold text-violet-700">
                                    {{ $activeCount }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                    Completed
                                </p>

                                <p class="mt-1 text-2xl font-bold text-emerald-600">
                                    {{ $completedCount }}
                                </p>
                            </div>
                        </div>
                    </section>


                    <a
                        href="{{ route('student.learning-paths') }}"
                        class="group block rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-600 to-indigo-600 p-5 text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-violet-200">
                                    Personalized
                                </p>

                                <h3 class="mt-1 text-base font-bold">
                                    Learning Paths
                                </h3>

                                <p class="mt-2 text-xs leading-5 text-violet-100">
                                    Explore a course sequence based on your learning performance.
                                </p>
                            </div>

                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 12h14"></path>
                                    <path d="m13 6 6 6-6 6"></path>
                                </svg>
                            </div>
                        </div>
                    </a>

                </aside>
            </div>


            {{-- =========================================================
                QUIZ RESULTS + RECOMMENDATION
            ========================================================== --}}
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-5 sm:px-6">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">
                                Recent Quiz Results
                            </h2>

                            <p class="mt-1 text-xs text-slate-500">
                                Your latest assessment performance.
                            </p>
                        </div>

                        <span class="rounded-full bg-slate-100 px-3 py-1.5 text-[11px] font-semibold text-slate-500">
                            {{ $quizCount }} total
                        </span>
                    </div>


                    @if($recentResults->isNotEmpty())

                        {{-- DESKTOP TABLE --}}
                        <div class="hidden overflow-x-auto md:block">
                            <table class="w-full min-w-[680px] text-left">
                                <thead class="border-b border-slate-100 bg-slate-50/80">
                                    <tr class="text-[10px] font-bold uppercase tracking-[0.08em] text-slate-400">
                                        <th class="px-6 py-4">Assessment</th>
                                        <th class="px-6 py-4">Score</th>
                                        <th class="px-6 py-4">Attempt</th>
                                        <th class="px-6 py-4">Result</th>
                                        <th class="px-6 py-4">Date</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-100">

                                    @foreach($recentResults as $result)

                                        @php
                                            $resultPercentage = (float) (
                                                $result->percentage ?? 0
                                            );

                                            $resultPercentage = min(
                                                max($resultPercentage, 0),
                                                100
                                            );

                                            $isPassed = strtolower(
                                                $result->remarks ?? ''
                                            ) === 'passed';
                                        @endphp

                                        <tr class="transition hover:bg-slate-50/70">
                                            <td class="px-6 py-5">
                                                <p class="max-w-[220px] truncate text-sm font-semibold text-slate-800">
                                                    {{ $result->quiz?->title ?? 'Quiz' }}
                                                </p>

                                                <p class="mt-1 max-w-[220px] truncate text-[11px] text-slate-400">
                                                    {{ $result->quiz?->course?->title ?? 'Course' }}
                                                </p>
                                            </td>

                                            <td class="px-6 py-5">
                                                <p
                                                    class="text-sm font-bold
                                                        {{ $isPassed
                                                            ? 'text-emerald-600'
                                                            : 'text-rose-500'
                                                        }}"
                                                >
                                                    {{ number_format($resultPercentage, 1) }}%
                                                </p>

                                                <p class="mt-1 text-[10px] text-slate-400">
                                                    {{ $result->score ?? 0 }}
                                                    /
                                                    {{ $result->total_items ?? 0 }}
                                                </p>
                                            </td>

                                            <td class="px-6 py-5">
                                                <span class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-[11px] font-semibold text-slate-600">
                                                    Attempt {{ $result->attempt_number ?? 1 }}
                                                </span>
                                            </td>

                                            <td class="px-6 py-5">
                                                <span
                                                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-[11px] font-bold
                                                        {{ $isPassed
                                                            ? 'bg-emerald-50 text-emerald-700'
                                                            : 'bg-rose-50 text-rose-600'
                                                        }}"
                                                >
                                                    <span
                                                        class="h-1.5 w-1.5 rounded-full
                                                            {{ $isPassed
                                                                ? 'bg-emerald-500'
                                                                : 'bg-rose-500'
                                                            }}"
                                                    ></span>

                                                    {{ $isPassed ? 'Passed' : 'Failed' }}
                                                </span>
                                            </td>

                                            <td class="px-6 py-5">
                                                @if($result->completed_at)
                                                    <p class="text-xs font-semibold text-slate-600">
                                                        {{
                                                            \Carbon\Carbon::parse(
                                                                $result->completed_at
                                                            )->format('M d, Y')
                                                        }}
                                                    </p>
                                                @else
                                                    <span class="text-xs text-slate-400">
                                                        —
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>

                                    @endforeach

                                </tbody>
                            </table>
                        </div>


                        {{-- MOBILE CARDS --}}
                        <div class="divide-y divide-slate-100 md:hidden">

                            @foreach($recentResults as $result)

                                @php
                                    $resultPercentage = (float) (
                                        $result->percentage ?? 0
                                    );

                                    $resultPercentage = min(
                                        max($resultPercentage, 0),
                                        100
                                    );

                                    $isPassed = strtolower(
                                        $result->remarks ?? ''
                                    ) === 'passed';
                                @endphp

                                <div class="p-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-900">
                                                {{ $result->quiz?->title ?? 'Quiz' }}
                                            </p>

                                            <p class="mt-1 truncate text-xs text-slate-400">
                                                {{ $result->quiz?->course?->title ?? 'Course' }}
                                            </p>
                                        </div>

                                        <span
                                            class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold
                                                {{ $isPassed
                                                    ? 'bg-emerald-50 text-emerald-700'
                                                    : 'bg-rose-50 text-rose-600'
                                                }}"
                                        >
                                            {{ $isPassed ? 'Passed' : 'Failed' }}
                                        </span>
                                    </div>

                                    <div class="mt-4 grid grid-cols-3 gap-3">
                                        <div>
                                            <p class="text-[10px] font-semibold uppercase text-slate-400">
                                                Score
                                            </p>

                                            <p
                                                class="mt-1 text-sm font-bold
                                                    {{ $isPassed
                                                        ? 'text-emerald-600'
                                                        : 'text-rose-500'
                                                    }}"
                                            >
                                                {{ number_format($resultPercentage, 1) }}%
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-[10px] font-semibold uppercase text-slate-400">
                                                Attempt
                                            </p>

                                            <p class="mt-1 text-sm font-bold text-slate-700">
                                                {{ $result->attempt_number ?? 1 }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-[10px] font-semibold uppercase text-slate-400">
                                                Date
                                            </p>

                                            <p class="mt-1 text-xs font-semibold text-slate-600">
                                                @if($result->completed_at)
                                                    {{
                                                        \Carbon\Carbon::parse(
                                                            $result->completed_at
                                                        )->format('M d')
                                                    }}
                                                @else
                                                    —
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>

                            @endforeach

                        </div>

                    @else

                        <div class="px-6 py-16 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M12 8v5"></path>
                                    <path d="M12 17h.01"></path>
                                </svg>
                            </div>

                            <h3 class="mt-4 text-sm font-bold text-slate-800">
                                No quiz attempts yet
                            </h3>

                            <p class="mt-1 text-xs text-slate-400">
                                Your assessment results will appear here.
                            </p>
                        </div>

                    @endif
                </section>


                <aside>
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 p-5">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-violet-500">
                                    Suggested Next
                                </p>

                                <h2 class="mt-1 text-base font-bold text-slate-900">
                                    Recommended for You
                                </h2>
                            </div>

                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m12 3 1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3Z"></path>
                                    <path d="m19 16 .8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8L19 16Z"></path>
                                </svg>
                            </div>
                        </div>


                        @if($recommended)

                            @php
                                $recThumbnail = $recommended->thumbnail ?? null;
                                $recThumbnailUrl = null;

                                if ($recThumbnail) {
                                    if (
                                        \Illuminate\Support\Str::startsWith(
                                            $recThumbnail,
                                            ['http://', 'https://']
                                        )
                                    ) {
                                        $recThumbnailUrl = $recThumbnail;
                                    } else {
                                        $recThumbnailUrl = asset(
                                            'storage/' . ltrim($recThumbnail, '/')
                                        );
                                    }
                                }
                            @endphp

                            <div class="p-5">
                                <div class="h-40 overflow-hidden rounded-2xl">
                                    @if($recThumbnailUrl)
                                        <img
                                            src="{{ $recThumbnailUrl }}"
                                            alt="{{ $recommended->title }}"
                                            class="h-full w-full object-cover"
                                        >
                                    @else
                                        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-violet-500 via-indigo-500 to-blue-500">
                                            <svg class="h-9 w-9 text-white/85" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                <p class="mt-4 text-[10px] font-bold uppercase tracking-[0.1em] text-violet-500">
                                    {{ $recommended->category?->name ?? 'Recommended Course' }}
                                </p>

                                <h3 class="mt-2 line-clamp-2 text-base font-bold leading-6 text-slate-900">
                                    {{ $recommended->title }}
                                </h3>

                                @if($recommended->description)
                                    <p class="mt-2 line-clamp-3 text-xs leading-5 text-slate-500">
                                        {{ $recommended->description }}
                                    </p>
                                @endif

                                <div class="mt-4 flex flex-wrap gap-2">
                                    @if($recommended->difficulty_level)
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-600">
                                            {{ ucfirst($recommended->difficulty_level) }}
                                        </span>
                                    @endif

                                    @if($recommended->estimated_hours)
                                        <span class="rounded-full bg-violet-50 px-2.5 py-1 text-[10px] font-semibold text-violet-700">
                                            {{ $recommended->estimated_hours }} hrs
                                        </span>
                                    @endif
                                </div>

                                <a
                                    href="{{ route('student.course.show', $recommended) }}"
                                    class="mt-5 inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-violet-600 text-xs font-semibold text-white transition hover:bg-violet-700"
                                >
                                    View Course
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="m9 18 6-6-6-6"></path>
                                    </svg>
                                </a>
                            </div>

                        @else

                            <div class="px-6 py-12 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="m12 3 1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3Z"></path>
                                    </svg>
                                </div>

                                <h3 class="mt-4 text-sm font-bold text-slate-800">
                                    No recommendation yet
                                </h3>

                                <p class="mt-1 text-xs leading-5 text-slate-400">
                                    Continue learning and taking quizzes to receive personalized suggestions.
                                </p>

                                <a
                                    href="{{ route('student.marketplace') }}"
                                    class="mt-4 inline-flex h-10 items-center justify-center rounded-xl border border-violet-200 bg-white px-4 text-xs font-semibold text-violet-700 transition hover:bg-violet-50"
                                >
                                    Browse Courses
                                </a>
                            </div>

                        @endif
                    </section>
                </aside>

            </div>

        </div>
    </main>
</div>

</x-layouts::app>
