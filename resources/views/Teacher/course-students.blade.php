<x-layouts::app :title="'Course Students'">

@php
    $totalStudents = $enrollments->count();

    $activeStudents = $enrollments
        ->filter(
            fn ($item) =>
                strtolower($item->status ?? '') === 'active'
        )
        ->count();

    $completedStudents = $enrollments
        ->filter(
            fn ($item) =>
                strtolower($item->status ?? '') === 'completed'
        )
        ->count();

    $averageProgress =
        $totalStudents > 0
            ? (float) $enrollments->avg('progress_percentage')
            : 0;

    $thumbnail = $course->thumbnail ?? null;
    $thumbnailUrl = null;

    if ($thumbnail) {

        if (
            \Illuminate\Support\Str::startsWith(
                $thumbnail,
                [
                    'http://',
                    'https://',
                ]
            )
        ) {
            $thumbnailUrl = $thumbnail;
        } else {

            $thumbnailUrl = asset(
                'storage/' .
                ltrim(
                    $thumbnail,
                    '/'
                )
            );
        }
    }
@endphp


<style>
    .pw-card {
        background: #ffffff;
        border: 1px solid #e7e9ef;
        border-radius: 20px;
        box-shadow:
            0 1px 3px
            rgba(15, 23, 42, .035);
    }

    .pw-control {
        width: 100%;
        border: 1px solid #e1e4ea;
        border-radius: 12px;
        background: #ffffff;
        color: #334155;
        font-size: 13px;
        transition: all 160ms ease;
    }

    .pw-control:focus {
        outline: none !important;
        border-color: #8b5cf6 !important;
        box-shadow:
            0 0 0 4px
            rgba(139, 92, 246, .08) !important;
    }
</style>


<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-[1500px]">


            {{-- =====================================================
                BREADCRUMB
            ====================================================== --}}

            <div
                class="mb-5 flex flex-wrap
                       items-center gap-2
                       text-xs text-slate-400"
            >

                <a
                    href="{{ route('teacher.my-courses') }}"
                    class="font-medium transition
                           hover:text-violet-600"
                >
                    My Courses
                </a>

                <span>
                    ›
                </span>

                <span>
                    {{ $course->title }}
                </span>

                <span>
                    ›
                </span>

                <span
                    class="font-semibold
                           text-slate-600"
                >
                    Students
                </span>

            </div>



            {{-- =====================================================
                HEADER
            ====================================================== --}}

            <div
                class="flex flex-col gap-5
                       lg:flex-row
                       lg:items-end
                       lg:justify-between"
            >

                <div>

                    <p
                        class="text-xs font-bold
                               uppercase
                               tracking-[.12em]
                               text-violet-600"
                    >
                        Course Management
                    </p>


                    <h1
                        class="mt-2 text-3xl
                               font-bold tracking-tight
                               text-slate-950"
                    >
                        Course Students
                    </h1>


                    <p
                        class="mt-2 max-w-2xl
                               text-sm leading-6
                               text-slate-500"
                    >
                        View enrolled students and monitor
                        their learning progress in this course.
                    </p>

                </div>


                <div
                    class="flex flex-wrap
                           items-center gap-2"
                >

                    <a
                        href="{{ route('teacher.lessons', $course) }}"
                        class="inline-flex h-11
                               items-center justify-center
                               rounded-xl
                               border border-slate-200
                               bg-white px-4
                               text-sm font-semibold
                               text-slate-600
                               transition
                               hover:border-violet-200
                               hover:text-violet-700"
                    >
                        Manage Lessons
                    </a>


                    <a
                        href="{{ route('teacher.my-courses') }}"
                        class="inline-flex h-11
                               items-center justify-center
                               rounded-xl
                               bg-violet-600 px-4
                               text-sm font-semibold
                               text-white transition
                               hover:bg-violet-700"
                    >
                        ← Back to Courses
                    </a>

                </div>

            </div>



            {{-- =====================================================
                COURSE SUMMARY
            ====================================================== --}}

            <section
                class="pw-card mt-7 overflow-hidden"
            >

                <div
                    class="grid grid-cols-1
                           lg:grid-cols-[280px_minmax(0,1fr)]"
                >


                    {{-- THUMBNAIL --}}
                    <div
                        class="relative min-h-[190px]
                               overflow-hidden"
                    >

                        @if($thumbnailUrl)

                            <img
                                src="{{ $thumbnailUrl }}"
                                alt="{{ $course->title }}"
                                class="h-full w-full
                                       object-cover"
                            >

                            <div
                                class="absolute inset-0
                                       bg-gradient-to-t
                                       from-slate-950/50
                                       via-transparent
                                       to-transparent"
                            ></div>

                        @else

                            <div
                                class="flex h-full
                                       min-h-[190px]
                                       items-center
                                       justify-center
                                       bg-gradient-to-br
                                       from-violet-500
                                       via-indigo-500
                                       to-blue-500"
                            >

                                <svg
                                    class="h-12 w-12
                                           text-white/80"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                >
                                    <path
                                        d="M4 19.5A2.5 2.5
                                           0 016.5 17H20"
                                    ></path>

                                    <path
                                        d="M6.5 2H20v20H6.5
                                           A2.5 2.5 0 014
                                           19.5v-15A2.5 2.5
                                           0 016.5 2z"
                                    ></path>
                                </svg>

                            </div>

                        @endif

                    </div>



                    {{-- COURSE INFO --}}
                    <div
                        class="flex flex-col
                               justify-center p-6
                               sm:p-7"
                    >

                        <div
                            class="flex flex-wrap
                                   items-center gap-2"
                        >

                            @if($course->category)

                                <span
                                    class="rounded-full
                                           bg-violet-50
                                           px-2.5 py-1
                                           text-[10px]
                                           font-bold uppercase
                                           tracking-wide
                                           text-violet-700"
                                >
                                    {{ $course->category->name }}
                                </span>

                            @endif


                            @if($course->difficulty_level)

                                <span
                                    class="rounded-full
                                           bg-slate-100
                                           px-2.5 py-1
                                           text-[10px]
                                           font-semibold
                                           text-slate-600"
                                >
                                    {{
                                        ucfirst(
                                            $course->difficulty_level
                                        )
                                    }}
                                </span>

                            @endif


                            <span
                                class="rounded-full
                                       {{
                                           $course->status === 'published'
                                           ? 'bg-emerald-50 text-emerald-700'
                                           : (
                                               $course->status === 'pending'
                                               ? 'bg-orange-50 text-orange-600'
                                               : 'bg-slate-100 text-slate-600'
                                           )
                                       }}
                                       px-2.5 py-1
                                       text-[10px]
                                       font-semibold"
                            >
                                {{
                                    ucfirst(
                                        $course->status
                                        ?? 'draft'
                                    )
                                }}
                            </span>

                        </div>


                        <h2
                            class="mt-3 text-2xl
                                   font-bold
                                   tracking-tight
                                   text-slate-900"
                        >
                            {{ $course->title }}
                        </h2>


                        @if($course->description)

                            <p
                                class="mt-2 line-clamp-2
                                       max-w-3xl
                                       text-sm leading-6
                                       text-slate-500"
                            >
                                {{ $course->description }}
                            </p>

                        @endif


                        <div
                            class="mt-5 flex
                                   flex-wrap gap-x-6
                                   gap-y-2
                                   text-xs text-slate-500"
                        >

                            <span>
                                <strong
                                    class="font-semibold
                                           text-slate-700"
                                >
                                    {{ $totalStudents }}
                                </strong>

                                {{
                                    \Illuminate\Support\Str::plural(
                                        'student',
                                        $totalStudents
                                    )
                                }}
                            </span>


                            @if($course->estimated_hours)

                                <span>
                                    <strong
                                        class="font-semibold
                                               text-slate-700"
                                    >
                                        {{ $course->estimated_hours }}
                                    </strong>

                                    estimated hours
                                </span>

                            @endif


                            <span>
                                <strong
                                    class="font-semibold
                                           text-slate-700"
                                >
                                    {{ number_format($averageProgress, 1) }}%
                                </strong>

                                average progress
                            </span>

                        </div>

                    </div>

                </div>

            </section>



            {{-- =====================================================
                STAT CARDS
            ====================================================== --}}

            <section
                class="mt-5 grid
                       grid-cols-2 gap-4
                       xl:grid-cols-4"
            >


                {{-- TOTAL --}}
                <div class="pw-card p-5">

                    <div
                        class="flex items-start
                               justify-between gap-4"
                    >

                        <div>

                            <p
                                class="text-xs font-semibold
                                       text-slate-500"
                            >
                                Enrolled Students
                            </p>

                            <p
                                class="mt-2 text-3xl
                                       font-bold
                                       tracking-tight
                                       text-slate-950"
                            >
                                {{ $totalStudents }}
                            </p>

                        </div>


                        <div
                            class="flex h-10 w-10
                                   items-center
                                   justify-center
                                   rounded-xl
                                   bg-violet-50
                                   text-violet-600"
                        >

                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="M16 21v-2a4
                                       4 0 00-4-4H6
                                       a4 4 0 00-4 4v2"
                                ></path>

                                <circle
                                    cx="9"
                                    cy="7"
                                    r="4"
                                ></circle>

                                <path
                                    d="M22 21v-2a4
                                       4 0 00-3-3.87"
                                ></path>
                            </svg>

                        </div>

                    </div>


                    <p
                        class="mt-2 text-xs
                               text-slate-400"
                    >
                        Total course enrollments
                    </p>

                </div>



                {{-- ACTIVE --}}
                <div class="pw-card p-5">

                    <div
                        class="flex items-start
                               justify-between gap-4"
                    >

                        <div>

                            <p
                                class="text-xs font-semibold
                                       text-slate-500"
                            >
                                Active Learners
                            </p>

                            <p
                                class="mt-2 text-3xl
                                       font-bold
                                       tracking-tight
                                       text-blue-600"
                            >
                                {{ $activeStudents }}
                            </p>

                        </div>


                        <div
                            class="flex h-10 w-10
                                   items-center
                                   justify-center
                                   rounded-xl
                                   bg-blue-50
                                   text-blue-600"
                        >

                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="9"
                                ></circle>

                                <path
                                    d="M12 7v5l3 2"
                                ></path>
                            </svg>

                        </div>

                    </div>


                    <p
                        class="mt-2 text-xs
                               text-slate-400"
                    >
                        Currently taking the course
                    </p>

                </div>



                {{-- COMPLETED --}}
                <div class="pw-card p-5">

                    <div
                        class="flex items-start
                               justify-between gap-4"
                    >

                        <div>

                            <p
                                class="text-xs font-semibold
                                       text-slate-500"
                            >
                                Completed
                            </p>

                            <p
                                class="mt-2 text-3xl
                                       font-bold
                                       tracking-tight
                                       text-emerald-600"
                            >
                                {{ $completedStudents }}
                            </p>

                        </div>


                        <div
                            class="flex h-10 w-10
                                   items-center
                                   justify-center
                                   rounded-xl
                                   bg-emerald-50
                                   font-bold
                                   text-emerald-600"
                        >
                            ✓
                        </div>

                    </div>


                    <p
                        class="mt-2 text-xs
                               text-slate-400"
                    >
                        Finished the course
                    </p>

                </div>



                {{-- AVERAGE --}}
                <div class="pw-card p-5">

                    <div
                        class="flex items-start
                               justify-between gap-4"
                    >

                        <div>

                            <p
                                class="text-xs font-semibold
                                       text-slate-500"
                            >
                                Average Progress
                            </p>

                            <p
                                class="mt-2 text-3xl
                                       font-bold
                                       tracking-tight
                                       text-orange-500"
                            >
                                {{
                                    number_format(
                                        $averageProgress,
                                        1
                                    )
                                }}%
                            </p>

                        </div>


                        <div
                            class="flex h-10 w-10
                                   items-center
                                   justify-center
                                   rounded-xl
                                   bg-orange-50
                                   text-orange-500"
                        >

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


                    <p
                        class="mt-2 text-xs
                               text-slate-400"
                    >
                        Overall learner progress
                    </p>

                </div>

            </section>



            {{-- =====================================================
                STUDENT LIST
            ====================================================== --}}

            <section
                class="pw-card mt-6
                       overflow-hidden"
            >


                {{-- TOOLBAR --}}
                <div
                    class="border-b
                           border-slate-100
                           p-5 sm:p-6"
                >

                    <div
                        class="flex flex-col gap-4
                               lg:flex-row
                               lg:items-center
                               lg:justify-between"
                    >

                        <div>

                            <h2
                                class="text-lg
                                       font-bold
                                       text-slate-900"
                            >
                                Enrolled Students
                            </h2>

                            <p
                                class="mt-1 text-xs
                                       text-slate-500"
                            >
                                View learner status,
                                enrollment date, and
                                course completion progress.
                            </p>

                        </div>


                        <p
                            id="studentCounter"
                            class="text-xs
                                   font-semibold
                                   text-slate-400"
                        >
                            {{ $totalStudents }}

                            {{
                                \Illuminate\Support\Str::plural(
                                    'student',
                                    $totalStudents
                                )
                            }}
                        </p>

                    </div>



                    @if($enrollments->isNotEmpty())

                        {{-- FILTERS --}}
                        <div
                            class="mt-5 grid
                                   grid-cols-1 gap-3
                                   md:grid-cols-2
                                   xl:grid-cols-[minmax(280px,1fr)_180px_190px_auto]"
                        >


                            {{-- SEARCH --}}
                            <div class="relative">

                                <svg
                                    class="pointer-events-none
                                           absolute left-3.5
                                           top-1/2 h-4 w-4
                                           -translate-y-1/2
                                           text-slate-400"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <circle
                                        cx="11"
                                        cy="11"
                                        r="8"
                                    ></circle>

                                    <path
                                        d="m21 21-4.35-4.35"
                                    ></path>
                                </svg>


                                <input
                                    type="search"
                                    id="studentSearch"
                                    placeholder="Search student name or email..."
                                    class="pw-control
                                           h-11 pl-10 pr-4"
                                >

                            </div>



                            {{-- STATUS --}}
                            <select
                                id="studentStatus"
                                class="pw-control
                                       h-11 px-3"
                            >

                                <option value="">
                                    All Status
                                </option>

                                <option value="active">
                                    Active
                                </option>

                                <option value="completed">
                                    Completed
                                </option>

                            </select>



                            {{-- PROGRESS --}}
                            <select
                                id="progressFilter"
                                class="pw-control
                                       h-11 px-3"
                            >

                                <option value="">
                                    All Progress
                                </option>

                                <option value="not-started">
                                    Not Started
                                </option>

                                <option value="early">
                                    1% - 49%
                                </option>

                                <option value="advanced">
                                    50% - 99%
                                </option>

                                <option value="complete">
                                    100%
                                </option>

                            </select>



                            {{-- RESET --}}
                            <button
                                type="button"
                                id="clearStudentFilters"
                                class="inline-flex
                                       h-11 items-center
                                       justify-center
                                       rounded-xl
                                       border border-slate-200
                                       bg-white px-4
                                       text-xs font-semibold
                                       text-slate-600
                                       transition
                                       hover:bg-slate-50"
                            >
                                Reset
                            </button>

                        </div>

                    @endif

                </div>



                {{-- =================================================
                    TABLE
                ================================================== --}}

                @if($enrollments->isNotEmpty())

                    <div class="overflow-x-auto">

                        <table
                            class="w-full
                                   min-w-[1050px]
                                   text-left"
                        >

                            <thead
                                class="border-b
                                       border-slate-100
                                       bg-slate-50/80"
                            >

                                <tr
                                    class="text-[10px]
                                           font-bold uppercase
                                           tracking-[.08em]
                                           text-slate-400"
                                >

                                    <th class="px-6 py-4">
                                        Student
                                    </th>

                                    <th class="px-6 py-4">
                                        Progress
                                    </th>

                                    <th class="px-6 py-4">
                                        Status
                                    </th>

                                    <th class="px-6 py-4">
                                        Enrolled
                                    </th>

                                    <th class="px-6 py-4">
                                        Last Updated
                                    </th>

                                    <th
                                        class="px-6 py-4
                                               text-right"
                                    >
                                        Action
                                    </th>

                                </tr>

                            </thead>



                            <tbody
                                class="divide-y
                                       divide-slate-100"
                            >

                                @foreach($enrollments as $enrollment)

                                    @php
                                        $student =
                                            $enrollment->student;

                                        $studentName =
                                            $student?->name
                                            ?? 'Unknown Student';

                                        $studentEmail =
                                            $student?->email
                                            ?? '';

                                        $status =
                                            strtolower(
                                                $enrollment->status
                                                ?? 'active'
                                            );

                                        $progress =
                                            (float) (
                                                $enrollment->progress_percentage
                                                ?? 0
                                            );

                                        $progress =
                                            min(
                                                max(
                                                    $progress,
                                                    0
                                                ),
                                                100
                                            );

                                        if ($progress <= 0) {

                                            $progressGroup =
                                                'not-started';

                                        } elseif ($progress < 50) {

                                            $progressGroup =
                                                'early';

                                        } elseif ($progress < 100) {

                                            $progressGroup =
                                                'advanced';

                                        } else {

                                            $progressGroup =
                                                'complete';
                                        }


                                        $parts =
                                            collect(
                                                preg_split(
                                                    '/\s+/',
                                                    trim(
                                                        $studentName
                                                    )
                                                )
                                            )
                                            ->filter()
                                            ->take(2);


                                        $initials =
                                            $parts
                                            ->map(
                                                fn ($part) =>
                                                    strtoupper(
                                                        substr(
                                                            $part,
                                                            0,
                                                            1
                                                        )
                                                    )
                                            )
                                            ->implode('');


                                        if (!$initials) {
                                            $initials = 'S';
                                        }


                                        $searchText =
                                            strtolower(
                                                $studentName
                                                . ' '
                                                . $studentEmail
                                            );
                                    @endphp



                                    <tr
                                        class="student-row
                                               transition
                                               hover:bg-slate-50/70"
                                        data-search="{{ $searchText }}"
                                        data-status="{{ $status }}"
                                        data-progress="{{ $progressGroup }}"
                                    >


                                        {{-- STUDENT --}}
                                        <td class="px-6 py-5">

                                            <div
                                                class="flex
                                                       items-center
                                                       gap-3"
                                            >

                                                <div
                                                    class="flex h-11
                                                           w-11
                                                           shrink-0
                                                           items-center
                                                           justify-center
                                                           rounded-full
                                                           bg-violet-100
                                                           text-xs font-bold
                                                           text-violet-700"
                                                >
                                                    {{ $initials }}
                                                </div>


                                                <div class="min-w-0">

                                                    <p
                                                        class="max-w-[220px]
                                                               truncate
                                                               text-sm
                                                               font-semibold
                                                               text-slate-800"
                                                    >
                                                        {{ $studentName }}
                                                    </p>


                                                    <p
                                                        class="mt-0.5
                                                               max-w-[220px]
                                                               truncate
                                                               text-[11px]
                                                               text-slate-400"
                                                    >
                                                        {{
                                                            $studentEmail
                                                            ?: 'Student'
                                                        }}
                                                    </p>

                                                </div>

                                            </div>

                                        </td>



                                        {{-- PROGRESS --}}
                                        <td class="px-6 py-5">

                                            <div class="w-[220px]">

                                                <div
                                                    class="mb-2 flex
                                                           items-center
                                                           justify-between"
                                                >

                                                    <span
                                                        class="text-xs
                                                               font-bold
                                                               {{
                                                                   $progress >= 100
                                                                   ? 'text-emerald-600'
                                                                   : 'text-violet-600'
                                                               }}"
                                                    >
                                                        {{
                                                            number_format(
                                                                $progress,
                                                                1
                                                            )
                                                        }}%
                                                    </span>


                                                    @if($progress >= 100)

                                                        <span
                                                            class="text-[10px]
                                                                   font-semibold
                                                                   text-emerald-600"
                                                        >
                                                            Complete
                                                        </span>

                                                    @elseif($progress > 0)

                                                        <span
                                                            class="text-[10px]
                                                                   font-medium
                                                                   text-slate-400"
                                                        >
                                                            In progress
                                                        </span>

                                                    @else

                                                        <span
                                                            class="text-[10px]
                                                                   font-medium
                                                                   text-slate-400"
                                                        >
                                                            Not started
                                                        </span>

                                                    @endif

                                                </div>


                                                <div
                                                    class="h-2
                                                           overflow-hidden
                                                           rounded-full
                                                           bg-slate-100"
                                                >

                                                    <div
                                                        class="h-full
                                                               rounded-full
                                                               {{
                                                                   $progress >= 100
                                                                   ? 'bg-emerald-500'
                                                                   : 'bg-violet-500'
                                                               }}"
                                                        style="
                                                            width:
                                                            {{ $progress }}%;
                                                        "
                                                    ></div>

                                                </div>

                                            </div>

                                        </td>



                                        {{-- STATUS --}}
                                        <td class="px-6 py-5">

                                            @if($status === 'completed')

                                                <span
                                                    class="inline-flex
                                                           items-center
                                                           gap-1.5
                                                           rounded-full
                                                           bg-emerald-50
                                                           px-3 py-1.5
                                                           text-[11px]
                                                           font-bold
                                                           text-emerald-700"
                                                >

                                                    <span
                                                        class="h-1.5
                                                               w-1.5
                                                               rounded-full
                                                               bg-emerald-500"
                                                    ></span>

                                                    Completed

                                                </span>


                                            @elseif($status === 'active')

                                                <span
                                                    class="inline-flex
                                                           items-center
                                                           gap-1.5
                                                           rounded-full
                                                           bg-blue-50
                                                           px-3 py-1.5
                                                           text-[11px]
                                                           font-bold
                                                           text-blue-700"
                                                >

                                                    <span
                                                        class="h-1.5
                                                               w-1.5
                                                               rounded-full
                                                               bg-blue-500"
                                                    ></span>

                                                    Active

                                                </span>


                                            @else

                                                <span
                                                    class="inline-flex
                                                           rounded-full
                                                           bg-slate-100
                                                           px-3 py-1.5
                                                           text-[11px]
                                                           font-bold
                                                           text-slate-600"
                                                >
                                                    {{
                                                        ucfirst(
                                                            $status
                                                        )
                                                    }}
                                                </span>

                                            @endif

                                        </td>



                                        {{-- ENROLLMENT DATE --}}
                                        <td class="px-6 py-5">

                                            @if($enrollment->enrolled_at)

                                                <p
                                                    class="text-xs
                                                           font-semibold
                                                           text-slate-600"
                                                >
                                                    {{
                                                        \Carbon\Carbon::parse(
                                                            $enrollment
                                                                ->enrolled_at
                                                        )
                                                        ->format(
                                                            'M d, Y'
                                                        )
                                                    }}
                                                </p>


                                                <p
                                                    class="mt-1
                                                           text-[10px]
                                                           text-slate-400"
                                                >
                                                    {{
                                                        \Carbon\Carbon::parse(
                                                            $enrollment
                                                                ->enrolled_at
                                                        )
                                                        ->format(
                                                            'g:i A'
                                                        )
                                                    }}
                                                </p>

                                            @else

                                                <span
                                                    class="text-xs
                                                           text-slate-400"
                                                >
                                                    —
                                                </span>

                                            @endif

                                        </td>



                                        {{-- LAST UPDATED --}}
                                        <td class="px-6 py-5">

                                            @if($enrollment->updated_at)

                                                <p
                                                    class="text-xs
                                                           font-semibold
                                                           text-slate-600"
                                                >
                                                    {{
                                                        $enrollment
                                                            ->updated_at
                                                            ->format(
                                                                'M d, Y'
                                                            )
                                                    }}
                                                </p>

                                                <p
                                                    class="mt-1
                                                           text-[10px]
                                                           text-slate-400"
                                                >
                                                    {{
                                                        $enrollment
                                                            ->updated_at
                                                            ->diffForHumans()
                                                    }}
                                                </p>

                                            @else

                                                <span
                                                    class="text-xs
                                                           text-slate-400"
                                                >
                                                    —
                                                </span>

                                            @endif

                                        </td>



                                        {{-- ACTION --}}
                                        <td
                                            class="px-6 py-5
                                                   text-right"
                                        >

                                            @if($student)

                                                <a
                                                    href="{{ route(
                                                        'teacher.student.progress',
                                                        [
                                                            $course,
                                                            $student
                                                        ]
                                                    ) }}"
                                                    class="inline-flex
                                                           h-9
                                                           items-center
                                                           justify-center
                                                           gap-1.5
                                                           rounded-lg
                                                           border
                                                           border-violet-200
                                                           bg-white
                                                           px-3
                                                           text-xs
                                                           font-semibold
                                                           text-violet-700
                                                           transition
                                                           hover:bg-violet-50"
                                                >
                                                    View Progress

                                                    <svg
                                                        class="h-3.5
                                                               w-3.5"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                    >
                                                        <path
                                                            d="m9 18 6-6-6-6"
                                                        ></path>
                                                    </svg>

                                                </a>

                                            @endif

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>



                    {{-- FILTER EMPTY STATE --}}
                    <div
                        id="noStudentResults"
                        class="hidden
                               border-t
                               border-slate-100
                               px-6 py-16
                               text-center"
                    >

                        <div
                            class="mx-auto flex
                                   h-12 w-12
                                   items-center
                                   justify-center
                                   rounded-full
                                   bg-slate-100
                                   text-slate-400"
                        >

                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle
                                    cx="11"
                                    cy="11"
                                    r="8"
                                ></circle>

                                <path
                                    d="m21 21-4.35-4.35"
                                ></path>
                            </svg>

                        </div>


                        <h3
                            class="mt-3
                                   text-sm font-bold
                                   text-slate-800"
                        >
                            No matching students
                        </h3>

                        <p
                            class="mt-1 text-xs
                                   text-slate-400"
                        >
                            Try changing the
                            search or filters.
                        </p>

                    </div>


                @else


                    {{-- =================================================
                        EMPTY STATE
                    ================================================== --}}

                    <div
                        class="px-6 py-20
                               text-center"
                    >

                        <div
                            class="mx-auto flex
                                   h-16 w-16
                                   items-center
                                   justify-center
                                   rounded-2xl
                                   bg-violet-50
                                   text-violet-600"
                        >

                            <svg
                                class="h-7 w-7"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="M16 21v-2a4
                                       4 0 00-4-4H6
                                       a4 4 0 00-4 4v2"
                                ></path>

                                <circle
                                    cx="9"
                                    cy="7"
                                    r="4"
                                ></circle>

                                <path
                                    d="M22 21v-2a4
                                       4 0 00-3-3.87"
                                ></path>
                            </svg>

                        </div>


                        <h3
                            class="mt-4
                                   text-base font-bold
                                   text-slate-900"
                        >
                            No students enrolled yet
                        </h3>


                        <p
                            class="mx-auto mt-2
                                   max-w-md
                                   text-sm leading-6
                                   text-slate-500"
                        >
                            Students who enroll in
                            <span
                                class="font-semibold
                                       text-slate-700"
                            >
                                {{ $course->title }}
                            </span>
                            will automatically appear here.
                        </p>


                        <a
                            href="{{ route('teacher.lessons', $course) }}"
                            class="mt-5 inline-flex
                                   h-11 items-center
                                   justify-center
                                   rounded-xl
                                   bg-violet-600
                                   px-5
                                   text-sm font-semibold
                                   text-white
                                   transition
                                   hover:bg-violet-700"
                        >
                            Manage Course Lessons
                        </a>

                    </div>

                @endif

            </section>

        </div>

    </main>

</div>



<script>
    function initializeCourseStudentsPage() {

        const search =
            document.getElementById(
                'studentSearch'
            );

        const status =
            document.getElementById(
                'studentStatus'
            );

        const progress =
            document.getElementById(
                'progressFilter'
            );

        const reset =
            document.getElementById(
                'clearStudentFilters'
            );

        const counter =
            document.getElementById(
                'studentCounter'
            );

        const empty =
            document.getElementById(
                'noStudentResults'
            );

        const rows =
            Array.from(
                document.querySelectorAll(
                    '.student-row'
                )
            );


        if (
            !search
            ||
            rows.length === 0
        ) {
            return;
        }



        function filterStudents() {

            const keyword =
                search.value
                    .trim()
                    .toLowerCase();

            const selectedStatus =
                status.value;

            const selectedProgress =
                progress.value;


            let visible = 0;


            rows.forEach(
                function (row) {

                    const matchesSearch =
                        !keyword
                        ||
                        row.dataset.search
                            .includes(keyword);


                    const matchesStatus =
                        !selectedStatus
                        ||
                        row.dataset.status
                        ===
                        selectedStatus;


                    const matchesProgress =
                        !selectedProgress
                        ||
                        row.dataset.progress
                        ===
                        selectedProgress;


                    const show =
                        matchesSearch
                        &&
                        matchesStatus
                        &&
                        matchesProgress;


                    row.classList.toggle(
                        'hidden',
                        !show
                    );


                    if (show) {
                        visible++;
                    }

                }
            );


            if (counter) {

                counter.textContent =
                    visible
                    +
                    (
                        visible === 1
                            ? ' student'
                            : ' students'
                    );

            }


            if (empty) {

                empty.classList.toggle(
                    'hidden',
                    visible !== 0
                );

            }

        }



        search.addEventListener(
            'input',
            filterStudents
        );


        status.addEventListener(
            'change',
            filterStudents
        );


        progress.addEventListener(
            'change',
            filterStudents
        );


        if (reset) {

            reset.addEventListener(
                'click',
                function () {

                    search.value = '';

                    status.value = '';

                    progress.value = '';

                    filterStudents();

                }
            );

        }


        filterStudents();

    }


    document.addEventListener(
        'DOMContentLoaded',
        initializeCourseStudentsPage
    );


    document.addEventListener(
        'livewire:navigated',
        initializeCourseStudentsPage
    );
</script>


</x-layouts::app>