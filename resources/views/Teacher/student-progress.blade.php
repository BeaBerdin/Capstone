<x-layouts::app :title="'Student Progress'">

@php
    $totalEnrollments = $enrollments->count();

    $completed = $enrollments
        ->filter(
            fn ($item) =>
                strtolower($item->status ?? '') === 'completed'
        )
        ->count();

    $active = $enrollments
        ->filter(
            fn ($item) =>
                strtolower($item->status ?? '') === 'active'
        )
        ->count();

    $averageProgress =
        $totalEnrollments > 0
            ? (float) $enrollments->avg('progress_percentage')
            : 0;

    $uniqueStudents = $enrollments
        ->pluck('student_id')
        ->unique()
        ->count();

    $courses = $enrollments
        ->map(fn ($item) => $item->course)
        ->filter()
        ->unique('id')
        ->sortBy('title')
        ->values();
@endphp


<style>
    .pw-card {
        background: #ffffff;
        border: 1px solid #e7e9ef;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .035);
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
        box-shadow: 0 0 0 4px rgba(139, 92, 246, .08) !important;
    }
</style>


<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-[1500px]">


            {{-- HEADER --}}
            <div
                class="flex flex-col gap-5
                       lg:flex-row
                       lg:items-end
                       lg:justify-between"
            >

                <div>

                    <p
                        class="text-xs font-bold uppercase
                               tracking-[.12em]
                               text-violet-600"
                    >
                        Learning Analytics
                    </p>

                    <h1
                        class="mt-2 text-3xl font-bold
                               tracking-tight
                               text-slate-950"
                    >
                        Student Progress
                    </h1>

                    <p
                        class="mt-2 max-w-2xl
                               text-sm leading-6
                               text-slate-500"
                    >
                        Monitor student enrollment,
                        course completion, and learning
                        progress across your courses.
                    </p>

                </div>


                <a
                    href="{{ route('teacher.my-courses') }}"
                    class="inline-flex h-11
                           self-start items-center
                           justify-center gap-2
                           rounded-xl
                           border border-slate-200
                           bg-white px-4
                           text-sm font-semibold
                           text-slate-600
                           transition
                           hover:border-violet-200
                           hover:text-violet-700"
                >

                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M4 19h16"></path>
                        <path d="M4 5h16"></path>
                        <path d="M4 12h16"></path>
                    </svg>

                    My Courses
                </a>

            </div>



            {{-- SUMMARY CARDS --}}
            <section
                class="mt-7 grid
                       grid-cols-2 gap-4
                       xl:grid-cols-4"
            >

                {{-- STUDENTS --}}
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
                                Students
                            </p>

                            <p
                                class="mt-2 text-3xl
                                       font-bold
                                       tracking-tight
                                       text-slate-950"
                            >
                                {{ $uniqueStudents }}
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
                                    d="M16 21v-2a4 4 0 00-4-4H6
                                       a4 4 0 00-4 4v2"
                                ></path>

                                <circle
                                    cx="9"
                                    cy="7"
                                    r="4"
                                ></circle>

                                <path
                                    d="M22 21v-2a4 4 0 00-3-3.87"
                                ></path>
                            </svg>

                        </div>

                    </div>

                    <p
                        class="mt-2 text-xs
                               text-slate-400"
                    >
                        Unique enrolled learners
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
                                Active
                            </p>

                            <p
                                class="mt-2 text-3xl
                                       font-bold
                                       tracking-tight
                                       text-blue-600"
                            >
                                {{ $active }}
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
                        Currently learning
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
                                {{ $completed }}
                            </p>

                        </div>


                        <div
                            class="flex h-10 w-10
                                   items-center
                                   justify-center
                                   rounded-xl
                                   bg-emerald-50
                                   text-emerald-600"
                        >
                            ✓
                        </div>

                    </div>

                    <p
                        class="mt-2 text-xs
                               text-slate-400"
                    >
                        Finished enrollments
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
                        Across {{ $totalEnrollments }}
                        {{
                            \Illuminate\Support\Str::plural(
                                'enrollment',
                                $totalEnrollments
                            )
                        }}
                    </p>

                </div>

            </section>



            {{-- PROGRESS TABLE CARD --}}
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
                                class="text-lg font-bold
                                       text-slate-900"
                            >
                                Course Progress
                            </h2>

                            <p
                                class="mt-1 text-xs
                                       text-slate-500"
                            >
                                Track every student's
                                progress in your courses.
                            </p>

                        </div>


                        <p
                            id="progressCounter"
                            class="text-xs
                                   font-semibold
                                   text-slate-400"
                        >
                            {{ $totalEnrollments }}

                            {{
                                \Illuminate\Support\Str::plural(
                                    'enrollment',
                                    $totalEnrollments
                                )
                            }}
                        </p>

                    </div>



                    {{-- FILTERS --}}
                    <div
                        class="mt-5 grid
                               grid-cols-1 gap-3
                               md:grid-cols-2
                               xl:grid-cols-[minmax(260px,1fr)_240px_180px_180px_auto]"
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
                                id="progressSearch"
                                placeholder="Search student or course..."
                                class="pw-control
                                       h-11 pl-10 pr-4"
                            >

                        </div>



                        {{-- COURSE FILTER --}}
                        <select
                            id="courseFilter"
                            class="pw-control
                                   h-11 px-3"
                        >

                            <option value="">
                                All Courses
                            </option>

                            @foreach($courses as $course)

                                <option
                                    value="{{ $course->id }}"
                                >
                                    {{ $course->title }}
                                </option>

                            @endforeach

                        </select>



                        {{-- STATUS FILTER --}}
                        <select
                            id="statusFilter"
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



                        {{-- PROGRESS FILTER --}}
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
                            id="clearFilters"
                            class="inline-flex h-11
                                   items-center
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

                </div>



                @if($enrollments->isNotEmpty())

                    <div class="overflow-x-auto">

                        <table
                            class="w-full
                                   min-w-[1120px]
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
                                        Course
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

                                    <th class="px-6 py-4 text-right">
                                        Action
                                    </th>

                                </tr>

                            </thead>



                            <tbody
                                class="divide-y
                                       divide-slate-100"
                            >

                                @foreach(
                                    $enrollments
                                    as
                                    $enrollment
                                )

                                    @php
                                        $student =
                                            $enrollment->student;

                                        $course =
                                            $enrollment->course;

                                        $studentName =
                                            $student?->name
                                            ?? 'Unknown Student';

                                        $studentEmail =
                                            $student?->email
                                            ?? '';

                                        $progress =
                                            (float) (
                                                $enrollment
                                                    ->progress_percentage
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

                                        $status =
                                            strtolower(
                                                $enrollment->status
                                                ?? 'active'
                                            );

                                        $isCompleted =
                                            $status
                                            ===
                                            'completed';

                                        if ($progress <= 0) {
                                            $progressGroup =
                                                'not-started';
                                        } elseif (
                                            $progress < 50
                                        ) {
                                            $progressGroup =
                                                'early';
                                        } elseif (
                                            $progress < 100
                                        ) {
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


                                        $thumbnail =
                                            $course?->thumbnail;

                                        $thumbnailUrl =
                                            null;

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
                                                $thumbnailUrl =
                                                    $thumbnail;
                                            } else {
                                                $thumbnailUrl =
                                                    asset(
                                                        'storage/'
                                                        .
                                                        ltrim(
                                                            $thumbnail,
                                                            '/'
                                                        )
                                                    );
                                            }
                                        }


                                        $searchText =
                                            strtolower(
                                                $studentName
                                                . ' '
                                                . $studentEmail
                                                . ' '
                                                . ($course?->title ?? '')
                                            );
                                    @endphp



                                    <tr
                                        class="progress-row
                                               transition
                                               hover:bg-slate-50/70"
                                        data-search="{{ $searchText }}"
                                        data-course="{{ $course?->id }}"
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
                                                    class="flex h-10
                                                           w-10
                                                           shrink-0
                                                           items-center
                                                           justify-center
                                                           rounded-full
                                                           bg-violet-100
                                                           text-xs
                                                           font-bold
                                                           text-violet-700"
                                                >
                                                    {{ $initials }}
                                                </div>


                                                <div class="min-w-0">

                                                    <p
                                                        class="max-w-[190px]
                                                               truncate
                                                               text-sm
                                                               font-semibold
                                                               text-slate-800"
                                                    >
                                                        {{ $studentName }}
                                                    </p>

                                                    <p
                                                        class="mt-0.5
                                                               max-w-[190px]
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



                                        {{-- COURSE --}}
                                        <td class="px-6 py-5">

                                            <div
                                                class="flex
                                                       items-center
                                                       gap-3"
                                            >

                                                @if($thumbnailUrl)

                                                    <img
                                                        src="{{ $thumbnailUrl }}"
                                                        alt=""
                                                        class="h-11 w-16
                                                               shrink-0
                                                               rounded-lg
                                                               object-cover"
                                                    >

                                                @else

                                                    <div
                                                        class="flex h-11
                                                               w-16
                                                               shrink-0
                                                               items-center
                                                               justify-center
                                                               rounded-lg
                                                               bg-gradient-to-br
                                                               from-violet-100
                                                               via-indigo-100
                                                               to-blue-100
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


                                                <div class="min-w-0">

                                                    <p
                                                        class="max-w-[220px]
                                                               truncate
                                                               text-sm
                                                               font-semibold
                                                               text-slate-800"
                                                    >
                                                        {{
                                                            $course?->title
                                                            ?? 'Course unavailable'
                                                        }}
                                                    </p>

                                                    <p
                                                        class="mt-0.5
                                                               text-[11px]
                                                               text-slate-400"
                                                    >
                                                        {{
                                                            $course?->category?->name
                                                            ?? 'Course'
                                                        }}
                                                    </p>

                                                </div>

                                            </div>

                                        </td>



                                        {{-- PROGRESS --}}
                                        <td class="px-6 py-5">

                                            <div class="w-[190px]">

                                                <div
                                                    class="mb-2
                                                           flex
                                                           items-center
                                                           justify-between"
                                                >

                                                    <span
                                                        class="text-xs
                                                               font-semibold
                                                               text-slate-600"
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
                                                               transition-all
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

                                            @if($isCompleted)

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



                                        {{-- ENROLLED --}}
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

                                            @if($course && $student)

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
                                                    View Details

                                                    <svg
                                                        class="h-3.5 w-3.5"
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



                    {{-- NO FILTER RESULTS --}}
                    <div
                        id="noProgressResults"
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


                    {{-- EMPTY STATE --}}
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
                                    d="M16 21v-2a4 4
                                       0 00-4-4H6a4 4
                                       0 00-4 4v2"
                                ></path>

                                <circle
                                    cx="9"
                                    cy="7"
                                    r="4"
                                ></circle>

                                <path
                                    d="M22 21v-2a4 4
                                       0 00-3-3.87"
                                ></path>
                            </svg>

                        </div>


                        <h3
                            class="mt-4
                                   text-base font-bold
                                   text-slate-900"
                        >
                            No student progress yet
                        </h3>

                        <p
                            class="mx-auto mt-2
                                   max-w-md
                                   text-sm leading-6
                                   text-slate-500"
                        >
                            Student progress will
                            appear here once learners
                            enroll in your courses.
                        </p>


                        <a
                            href="{{ route('teacher.my-courses') }}"
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
                            View My Courses
                        </a>

                    </div>

                @endif

            </section>

        </div>

    </main>

</div>



<script>
    function initializeStudentProgressPage() {

        const searchInput =
            document.getElementById(
                'progressSearch'
            );

        const courseFilter =
            document.getElementById(
                'courseFilter'
            );

        const statusFilter =
            document.getElementById(
                'statusFilter'
            );

        const progressFilter =
            document.getElementById(
                'progressFilter'
            );

        const clearButton =
            document.getElementById(
                'clearFilters'
            );

        const counter =
            document.getElementById(
                'progressCounter'
            );

        const empty =
            document.getElementById(
                'noProgressResults'
            );

        const rows =
            Array.from(
                document.querySelectorAll(
                    '.progress-row'
                )
            );


        if (
            !searchInput
            ||
            rows.length === 0
        ) {
            return;
        }



        function filterRows() {

            const search =
                searchInput.value
                    .trim()
                    .toLowerCase();

            const course =
                courseFilter.value;

            const status =
                statusFilter.value;

            const progress =
                progressFilter.value;


            let visible = 0;


            rows.forEach(
                function (row) {

                    const matchSearch =
                        !search
                        ||
                        row.dataset.search
                            .includes(search);


                    const matchCourse =
                        !course
                        ||
                        row.dataset.course
                        ===
                        course;


                    const matchStatus =
                        !status
                        ||
                        row.dataset.status
                        ===
                        status;


                    const matchProgress =
                        !progress
                        ||
                        row.dataset.progress
                        ===
                        progress;


                    const show =
                        matchSearch
                        &&
                        matchCourse
                        &&
                        matchStatus
                        &&
                        matchProgress;


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
                            ? ' enrollment'
                            : ' enrollments'
                    );

            }


            if (empty) {

                empty.classList.toggle(
                    'hidden',
                    visible !== 0
                );

            }

        }



        searchInput.addEventListener(
            'input',
            filterRows
        );


        courseFilter.addEventListener(
            'change',
            filterRows
        );


        statusFilter.addEventListener(
            'change',
            filterRows
        );


        progressFilter.addEventListener(
            'change',
            filterRows
        );


        if (clearButton) {

            clearButton.addEventListener(
                'click',
                function () {

                    searchInput.value = '';

                    courseFilter.value = '';

                    statusFilter.value = '';

                    progressFilter.value = '';


                    filterRows();

                }
            );

        }


        filterRows();

    }


    document.addEventListener(
        'DOMContentLoaded',
        initializeStudentProgressPage
    );


    document.addEventListener(
        'livewire:navigated',
        initializeStudentProgressPage
    );
</script>


</x-layouts::app>