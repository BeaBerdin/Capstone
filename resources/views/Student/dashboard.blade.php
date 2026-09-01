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


<style>
    .pw-card {
        background: #ffffff;
        border: 1px solid #e7e9ef;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.035);
    }

    .pw-card-hover {
        transition:
            transform 160ms ease,
            border-color 160ms ease,
            box-shadow 160ms ease;
    }

    .pw-card-hover:hover {
        transform: translateY(-2px);
        border-color: #ddd6fe;
        box-shadow: 0 12px 30px rgba(76, 29, 149, 0.06);
    }
</style>


<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-[1500px]">


            {{-- =====================================================
                HEADER
            ====================================================== --}}

            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">

                <div>

                    <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                        Student Dashboard
                    </p>

                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                        Welcome back, {{ $firstName }}
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                        Continue your courses, monitor your progress,
                        and keep building your skills with PathWise.
                    </p>

                </div>


                <div class="flex flex-wrap items-center gap-2">

                    <a
                        href="{{ route('student.my-courses') }}"
                        class="inline-flex h-11 items-center justify-center gap-2
                               rounded-xl border border-slate-200 bg-white px-4
                               text-sm font-semibold text-slate-600 transition
                               hover:border-violet-200 hover:text-violet-700"
                    >

                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                        </svg>

                        My Courses
                    </a>


                    <a
                        href="{{ route('student.marketplace') }}"
                        class="inline-flex h-11 items-center justify-center gap-2
                               rounded-xl bg-violet-600 px-4
                               text-sm font-semibold text-white transition
                               hover:bg-violet-700"
                    >

                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>

                        Browse Courses
                    </a>

                </div>

            </div>



            {{-- =====================================================
                SUMMARY CARDS
            ====================================================== --}}

            <section class="mt-7 grid grid-cols-2 gap-4 xl:grid-cols-4">


                {{-- MY COURSES --}}
                <div class="pw-card p-5">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <p class="text-xs font-semibold text-slate-500">
                                My Courses
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                                {{ $totalCourses }}
                            </p>

                        </div>


                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">

                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                            </svg>

                        </div>

                    </div>


                    <p class="mt-2 text-xs text-slate-400">
                        {{ $activeCount }} currently active
                    </p>

                </div>



                {{-- AVERAGE PROGRESS --}}
                <div class="pw-card p-5">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <p class="text-xs font-semibold text-slate-500">
                                Average Progress
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-blue-600">
                                {{ number_format($overallProgress, 1) }}%
                            </p>

                        </div>


                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">

                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M3 3v18h18"></path>
                                <path d="m7 16 4-5 4 3 5-7"></path>
                            </svg>

                        </div>

                    </div>


                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100">

                        <div
                            class="h-full rounded-full bg-blue-500"
                            style="width: {{ $overallProgress }}%;"
                        ></div>

                    </div>

                </div>



                {{-- QUIZ SCORE --}}
                <div class="pw-card p-5">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <p class="text-xs font-semibold text-slate-500">
                                Average Quiz Score
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-600">
                                {{ number_format($averageScoreValue, 1) }}%
                            </p>

                        </div>


                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-600">
                            ✓
                        </div>

                    </div>


                    <p class="mt-2 text-xs text-slate-400">
                        {{ $quizCount }}
                        {{ \Illuminate\Support\Str::plural('attempt', $quizCount) }}
                    </p>

                </div>



                {{-- CERTIFICATES --}}
                <div class="pw-card p-5">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <p class="text-xs font-semibold text-slate-500">
                                Certificates
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-orange-500">
                                {{ $certificateCount }}
                            </p>

                        </div>


                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-orange-500">

                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="12" cy="8" r="5"></circle>
                                <path d="M8.5 12.5 7 22l5-3 5 3-1.5-9.5"></path>
                            </svg>

                        </div>

                    </div>


                    <p class="mt-2 text-xs text-slate-400">
                        Earned achievements
                    </p>

                </div>

            </section>



            {{-- =====================================================
                MAIN CONTENT
            ====================================================== --}}

            <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.45fr)_360px]">


                {{-- =================================================
                    CONTINUE LEARNING
                ================================================== --}}

                <section class="pw-card overflow-hidden">


                    <div class="flex items-center justify-between border-b border-slate-100 p-5 sm:p-6">

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
                            class="text-xs font-semibold text-violet-600 transition hover:text-violet-700"
                        >
                            View all
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


                                <article class="p-5 transition hover:bg-slate-50/60 sm:p-6">

                                    <div class="flex flex-col gap-4 md:flex-row md:items-center">


                                        {{-- COURSE IMAGE --}}
                                        @if($thumbnailUrl)

                                            <img
                                                src="{{ $thumbnailUrl }}"
                                                alt="{{ $course?->title ?? 'Course' }}"
                                                class="h-28 w-full shrink-0 rounded-xl object-cover
                                                       md:h-20 md:w-28"
                                            >

                                        @else

                                            <div
                                                class="flex h-28 w-full shrink-0 items-center
                                                       justify-center rounded-xl
                                                       bg-gradient-to-br
                                                       from-violet-100 via-indigo-100 to-blue-100
                                                       text-violet-600
                                                       md:h-20 md:w-28"
                                            >

                                                <svg
                                                    class="h-6 w-6"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                                </svg>

                                            </div>

                                        @endif



                                        {{-- COURSE DETAILS --}}
                                        <div class="min-w-0 flex-1">

                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">

                                                <div class="min-w-0">

                                                    <p class="text-[10px] font-bold uppercase tracking-[.08em] text-violet-500">
                                                        {{ $course?->category?->name ?? 'Course' }}
                                                    </p>

                                                    <h3 class="mt-1 truncate text-sm font-bold text-slate-900">
                                                        {{ $course?->title ?? 'Course unavailable' }}
                                                    </h3>

                                                </div>


                                                <span
                                                    class="self-start rounded-full px-2.5 py-1
                                                           text-[10px] font-bold
                                                           {{ $isCompleted
                                                               ? 'bg-emerald-50 text-emerald-700'
                                                               : 'bg-violet-50 text-violet-700'
                                                           }}"
                                                >
                                                    {{ number_format($courseProgress, 0) }}%
                                                </span>

                                            </div>



                                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">

                                                <div
                                                    class="h-full rounded-full
                                                           {{ $isCompleted
                                                               ? 'bg-emerald-500'
                                                               : 'bg-violet-600'
                                                           }}"
                                                    style="width: {{ $courseProgress }}%;"
                                                ></div>

                                            </div>



                                            <div class="mt-3 flex items-center justify-between gap-4">

                                                <span class="text-[11px] font-medium text-slate-400">
                                                    {{ $isCompleted ? 'Completed' : 'In progress' }}
                                                </span>


                                                @if($course && !$isCompleted)

                                                    <a
                                                        href="{{ route('student.learn.course', $course) }}"
                                                        class="inline-flex h-8 items-center justify-center
                                                               rounded-lg bg-violet-600 px-3
                                                               text-[11px] font-semibold text-white
                                                               transition hover:bg-violet-700"
                                                    >
                                                        Continue
                                                    </a>

                                                @elseif($isCompleted)

                                                    <a
                                                        href="{{ route('student.certificates') }}"
                                                        class="inline-flex h-8 items-center justify-center
                                                               rounded-lg bg-emerald-50 px-3
                                                               text-[11px] font-semibold text-emerald-700
                                                               transition hover:bg-emerald-100"
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

                            <div
                                class="mx-auto flex h-14 w-14 items-center
                                       justify-center rounded-2xl
                                       bg-violet-50 text-violet-600"
                            >

                                <svg
                                    class="h-6 w-6"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
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
                                class="mt-4 inline-flex h-10 items-center justify-center
                                       rounded-xl bg-violet-600 px-4
                                       text-xs font-semibold text-white
                                       hover:bg-violet-700"
                            >
                                Browse Courses
                            </a>

                        </div>

                    @endif

                </section>



                {{-- =================================================
                    RIGHT COLUMN
                ================================================== --}}

                <aside class="space-y-5">


                    {{-- LEARNING OVERVIEW --}}
                    <section class="pw-card p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div>

                                <h2 class="text-sm font-bold text-slate-900">
                                    Learning Overview
                                </h2>

                                <p class="mt-1 text-xs text-slate-500">
                                    Your current learning journey.
                                </p>

                            </div>


                            <div
                                class="flex h-9 w-9 items-center
                                       justify-center rounded-xl
                                       bg-violet-50 text-violet-600"
                            >

                                <svg
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M3 3v18h18"></path>
                                    <path d="m7 16 4-5 4 3 5-7"></path>
                                </svg>

                            </div>

                        </div>



                        <div class="mt-6 rounded-2xl bg-violet-50/70 p-5">

                            <div class="flex items-end justify-between">

                                <div>

                                    <p class="text-[10px] font-bold uppercase tracking-[.08em] text-violet-500">
                                        Completion Rate
                                    </p>

                                    <p class="mt-2 text-3xl font-bold text-violet-900">
                                        {{ number_format($completionRate, 0) }}%
                                    </p>

                                </div>


                                <span class="text-xs font-semibold text-violet-600">
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

                            <div class="rounded-xl bg-slate-50 p-3">

                                <p class="text-[10px] font-semibold text-slate-400">
                                    Active
                                </p>

                                <p class="mt-1 text-xl font-bold text-blue-600">
                                    {{ $activeCount }}
                                </p>

                            </div>


                            <div class="rounded-xl bg-slate-50 p-3">

                                <p class="text-[10px] font-semibold text-slate-400">
                                    Completed
                                </p>

                                <p class="mt-1 text-xl font-bold text-emerald-600">
                                    {{ $completedCount }}
                                </p>

                            </div>

                        </div>

                    </section>



                 

            {{-- =====================================================
                QUIZ RESULTS + RECOMMENDATION
            ====================================================== --}}

            <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.4fr)_360px]">


                {{-- RECENT QUIZ RESULTS --}}
                <section class="pw-card overflow-hidden">

                    <div class="flex items-center justify-between border-b border-slate-100 p-5 sm:p-6">

                        <div>

                            <h2 class="text-lg font-bold text-slate-900">
                                Recent Quiz Results
                            </h2>

                            <p class="mt-1 text-xs text-slate-500">
                                Your latest assessment performance.
                            </p>

                        </div>


                        <span class="text-xs font-semibold text-slate-400">
                            {{ $quizCount }} total
                        </span>

                    </div>



                    @if($recentResults->isNotEmpty())

                        <div class="overflow-x-auto">

                            <table class="w-full min-w-[720px] text-left">

                                <thead class="border-b border-slate-100 bg-slate-50/80">

                                    <tr class="text-[10px] font-bold uppercase tracking-[.08em] text-slate-400">

                                        <th class="px-6 py-4">
                                            Assessment
                                        </th>

                                        <th class="px-6 py-4">
                                            Score
                                        </th>

                                        <th class="px-6 py-4">
                                            Attempt
                                        </th>

                                        <th class="px-6 py-4">
                                            Result
                                        </th>

                                        <th class="px-6 py-4">
                                            Date
                                        </th>

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


                                            {{-- QUIZ --}}
                                            <td class="px-6 py-5">

                                                <p class="max-w-[230px] truncate text-sm font-semibold text-slate-800">
                                                    {{ $result->quiz?->title ?? 'Quiz' }}
                                                </p>


                                                <p class="mt-1 max-w-[230px] truncate text-[11px] text-slate-400">
                                                    {{ $result->quiz?->course?->title ?? 'Course' }}
                                                </p>

                                            </td>



                                            {{-- SCORE --}}
                                            <td class="px-6 py-5">

                                                <p
                                                    class="text-sm font-bold
                                                           {{ $isPassed
                                                               ? 'text-emerald-600'
                                                               : 'text-red-500'
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



                                            {{-- ATTEMPT --}}
                                            <td class="px-6 py-5">

                                                <span
                                                    class="rounded-lg bg-slate-100
                                                           px-2.5 py-1.5
                                                           text-[11px] font-semibold
                                                           text-slate-600"
                                                >
                                                    Attempt {{ $result->attempt_number ?? 1 }}
                                                </span>

                                            </td>



                                            {{-- STATUS --}}
                                            <td class="px-6 py-5">

                                                <span
                                                    class="inline-flex items-center gap-1.5
                                                           rounded-full px-3 py-1.5
                                                           text-[11px] font-bold
                                                           {{ $isPassed
                                                               ? 'bg-emerald-50 text-emerald-700'
                                                               : 'bg-red-50 text-red-600'
                                                           }}"
                                                >

                                                    <span
                                                        class="h-1.5 w-1.5 rounded-full
                                                               {{ $isPassed
                                                                   ? 'bg-emerald-500'
                                                                   : 'bg-red-500'
                                                               }}"
                                                    ></span>

                                                    {{ $isPassed ? 'Passed' : 'Failed' }}

                                                </span>

                                            </td>



                                            {{-- DATE --}}
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


                    @else


                        <div class="px-6 py-16 text-center">

                            <div
                                class="mx-auto flex h-12 w-12 items-center
                                       justify-center rounded-2xl
                                       bg-blue-50 text-lg font-bold text-blue-600"
                            >
                                ?
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



                {{-- =================================================
                    RECOMMENDATION
                ================================================== --}}

                <aside>

                    <section class="pw-card overflow-hidden">


                        <div class="border-b border-slate-100 p-5">

                            <div class="flex items-center justify-between gap-3">

                                <div>

                                    <h2 class="text-sm font-bold text-slate-900">
                                        Recommended for You
                                    </h2>

                                    <p class="mt-1 text-xs text-slate-500">
                                        Based on your learning.
                                    </p>

                                </div>


                                <div
                                    class="flex h-9 w-9 items-center
                                           justify-center rounded-xl
                                           bg-violet-50 text-violet-600"
                                >
                                    ✦
                                </div>

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


                                {{-- IMAGE --}}
                                <div class="h-40 overflow-hidden rounded-2xl">

                                    @if($recThumbnailUrl)

                                        <img
                                            src="{{ $recThumbnailUrl }}"
                                            alt="{{ $recommended->title }}"
                                            class="h-full w-full object-cover"
                                        >

                                    @else

                                        <div
                                            class="flex h-full w-full items-center
                                                   justify-center
                                                   bg-gradient-to-br
                                                   from-violet-500
                                                   via-indigo-500
                                                   to-blue-500"
                                        >

                                            <svg
                                                class="h-9 w-9 text-white/80"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.5"
                                            >
                                                <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                            </svg>

                                        </div>

                                    @endif

                                </div>



                                <p class="mt-4 text-[10px] font-bold uppercase tracking-[.08em] text-violet-500">
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

                                        <span
                                            class="rounded-full bg-slate-100
                                                   px-2.5 py-1
                                                   text-[10px] font-semibold
                                                   text-slate-600"
                                        >
                                            {{ ucfirst($recommended->difficulty_level) }}
                                        </span>

                                    @endif


                                    @if($recommended->estimated_hours)

                                        <span
                                            class="rounded-full bg-blue-50
                                                   px-2.5 py-1
                                                   text-[10px] font-semibold
                                                   text-blue-600"
                                        >
                                            {{ $recommended->estimated_hours }} hrs
                                        </span>

                                    @endif

                                </div>



                                <a
                                    href="{{ route('student.course.show', $recommended) }}"
                                    class="mt-5 inline-flex h-10 w-full
                                           items-center justify-center
                                           rounded-xl bg-violet-600
                                           text-xs font-semibold text-white
                                           transition hover:bg-violet-700"
                                >
                                    View Course
                                </a>

                            </div>


                        @else


                            <div class="px-6 py-12 text-center">

                                <div
                                    class="mx-auto flex h-12 w-12
                                           items-center justify-center
                                           rounded-2xl
                                           bg-violet-50 text-violet-600"
                                >
                                    ✦
                                </div>


                                <h3 class="mt-4 text-sm font-bold text-slate-800">
                                    No recommendation yet
                                </h3>


                                <p class="mt-1 text-xs leading-5 text-slate-400">
                                    Continue learning and taking quizzes
                                    to receive personalized suggestions.
                                </p>


                                <a
                                    href="{{ route('student.marketplace') }}"
                                    class="mt-4 inline-flex h-10
                                           items-center justify-center
                                           rounded-xl border
                                           border-violet-200 bg-white
                                           px-4 text-xs font-semibold
                                           text-violet-700 transition
                                           hover:bg-violet-50"
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