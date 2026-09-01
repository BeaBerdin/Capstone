<x-layouts::app :title="'My Courses'">

@php
    $courseEnrollments = collect($enrollments ?? []);

    $totalCourses = $courseEnrollments->count();

    $activeCourses = $courseEnrollments
        ->filter(fn ($item) => strtolower($item->status ?? '') === 'active')
        ->count();

    $completedCourses = $courseEnrollments
        ->filter(fn ($item) => strtolower($item->status ?? '') === 'completed')
        ->count();

    $averageProgress = $totalCourses > 0
        ? (float) $courseEnrollments->avg('progress_percentage')
        : 0;

    $averageProgress = min(
        max($averageProgress, 0),
        100
    );

    $categories = $courseEnrollments
        ->map(fn ($item) => $item->course?->category)
        ->filter()
        ->unique('id')
        ->sortBy('name')
        ->values();
@endphp


<style>
    .pw-card {
        background: #ffffff;
        border: 1px solid #e7e9ef;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.035);
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
        box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.08) !important;
    }

    .pw-course-card {
        transition:
            transform 160ms ease,
            border-color 160ms ease,
            box-shadow 160ms ease;
    }

    .pw-course-card:hover {
        transform: translateY(-3px);
        border-color: #ddd6fe;
        box-shadow: 0 14px 35px rgba(76, 29, 149, 0.07);
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
                        My Learning
                    </p>

                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                        My Courses
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                        Continue your enrolled courses, monitor your progress,
                        and access completed learning activities.
                    </p>

                </div>

            </div>



            {{-- =====================================================
                SUCCESS
            ====================================================== --}}

            @if(session('success'))

                <div
                    class="mt-6 flex items-center gap-3
                           rounded-2xl border border-emerald-200
                           bg-emerald-50 px-5 py-4
                           text-sm font-medium text-emerald-700"
                >

                    <div
                        class="flex h-7 w-7 items-center justify-center
                               rounded-full bg-emerald-100"
                    >
                        ✓
                    </div>

                    {{ session('success') }}

                </div>

            @endif



            {{-- =====================================================
                SUMMARY CARDS
            ====================================================== --}}

            <section class="mt-7 grid grid-cols-2 gap-4 xl:grid-cols-4">


                {{-- TOTAL --}}
                <div class="pw-card p-5">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <p class="text-xs font-semibold text-slate-500">
                                Total Courses
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                                {{ $totalCourses }}
                            </p>

                        </div>


                        <div
                            class="flex h-10 w-10 items-center justify-center
                                   rounded-xl bg-violet-50 text-violet-600"
                        >

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
                        Courses you have joined
                    </p>

                </div>



                {{-- ACTIVE --}}
                <div class="pw-card p-5">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <p class="text-xs font-semibold text-slate-500">
                                Active Courses
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-blue-600">
                                {{ $activeCourses }}
                            </p>

                        </div>


                        <div
                            class="flex h-10 w-10 items-center justify-center
                                   rounded-xl bg-blue-50 text-blue-600"
                        >

                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M12 7v5l3 2"></path>
                            </svg>

                        </div>

                    </div>

                    <p class="mt-2 text-xs text-slate-400">
                        Currently in progress
                    </p>

                </div>



                {{-- COMPLETED --}}
                <div class="pw-card p-5">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <p class="text-xs font-semibold text-slate-500">
                                Completed
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-600">
                                {{ $completedCourses }}
                            </p>

                        </div>


                        <div
                            class="flex h-10 w-10 items-center justify-center
                                   rounded-xl bg-emerald-50 font-bold text-emerald-600"
                        >
                            ✓
                        </div>

                    </div>

                    <p class="mt-2 text-xs text-slate-400">
                        Finished courses
                    </p>

                </div>



                {{-- AVERAGE --}}
                <div class="pw-card p-5">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <p class="text-xs font-semibold text-slate-500">
                                Average Progress
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-orange-500">
                                {{ number_format($averageProgress, 1) }}%
                            </p>

                        </div>


                        <div
                            class="flex h-10 w-10 items-center justify-center
                                   rounded-xl bg-orange-50 text-orange-500"
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

                    <p class="mt-2 text-xs text-slate-400">
                        Across all enrollments
                    </p>

                </div>

            </section>



            {{-- =====================================================
                FILTERS
            ====================================================== --}}

            <section class="pw-card mt-6 p-5 sm:p-6">

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                    <div>

                        <h2 class="text-lg font-bold text-slate-900">
                            Learning Courses
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Search and filter your enrolled courses.
                        </p>

                    </div>


                    <p
                        id="courseCounter"
                        class="text-xs font-semibold text-slate-400"
                    >
                        {{ $totalCourses }}
                        {{ \Illuminate\Support\Str::plural('course', $totalCourses) }}
                    </p>

                </div>



                @if($courseEnrollments->isNotEmpty())

                    <div
                        class="mt-5 grid grid-cols-1 gap-3
                               md:grid-cols-2
                               xl:grid-cols-[minmax(280px,1fr)_220px_190px_190px_auto]"
                    >


                        {{-- SEARCH --}}
                        <div class="relative">

                            <svg
                                class="pointer-events-none absolute left-3.5 top-1/2
                                       h-4 w-4 -translate-y-1/2 text-slate-400"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>


                            <input
                                type="search"
                                id="courseSearch"
                                placeholder="Search your courses..."
                                class="pw-control h-11 pl-10 pr-4"
                            >

                        </div>



                        {{-- CATEGORY --}}
                        <select
                            id="categoryFilter"
                            class="pw-control h-11 px-3"
                        >

                            <option value="">
                                All Categories
                            </option>

                            @foreach($categories as $category)

                                <option value="{{ $category->id }}">
                                    {{ $category->name }}
                                </option>

                            @endforeach

                        </select>



                        {{-- STATUS --}}
                        <select
                            id="statusFilter"
                            class="pw-control h-11 px-3"
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
                            class="pw-control h-11 px-3"
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

                    </div>

                @endif

            </section>



            {{-- =====================================================
                COURSES
            ====================================================== --}}

            @if($courseEnrollments->isNotEmpty())

                <section
                    id="courseGrid"
                    class="mt-6 grid grid-cols-1 gap-5
                           md:grid-cols-2 xl:grid-cols-3"
                >

                    @foreach($courseEnrollments as $enrollment)

                        @php
                            $course = $enrollment->course;

                            if (!$course) {
                                continue;
                            }

                            $progress = (float) (
                                $enrollment->progress_percentage ?? 0
                            );

                            $progress = min(
                                max($progress, 0),
                                100
                            );

                            $status = strtolower(
                                $enrollment->status ?? 'active'
                            );

                            $isCompleted = $status === 'completed';

                            if ($progress <= 0) {
                                $progressGroup = 'not-started';
                            } elseif ($progress < 50) {
                                $progressGroup = 'early';
                            } elseif ($progress < 100) {
                                $progressGroup = 'advanced';
                            } else {
                                $progressGroup = 'complete';
                            }


                            $thumbnail = $course->thumbnail ?? null;
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
                                        'storage/' .
                                        ltrim($thumbnail, '/')
                                    );
                                }
                            }


                            $categoryId =
                                $course->category?->id ?? '';

                            $searchText = strtolower(
                                ($course->title ?? '') . ' ' .
                                ($course->description ?? '') . ' ' .
                                ($course->category?->name ?? '')
                            );
                        @endphp



                        <article
                            class="course-card pw-card pw-course-card overflow-hidden"
                            data-search="{{ $searchText }}"
                            data-category="{{ $categoryId }}"
                            data-status="{{ $status }}"
                            data-progress="{{ $progressGroup }}"
                        >


                            {{-- =========================================
                                IMAGE
                            ========================================== --}}

                            <div class="relative h-48 overflow-hidden bg-slate-100">

                                @if($thumbnailUrl)

                                    <img
                                        src="{{ $thumbnailUrl }}"
                                        alt="{{ $course->title }}"
                                        class="h-full w-full object-cover
                                               transition duration-300"
                                    >

                                @else

                                    <div
                                        class="flex h-full w-full
                                               items-center justify-center
                                               bg-gradient-to-br
                                               from-violet-500
                                               via-indigo-500
                                               to-blue-500"
                                    >

                                        <svg
                                            class="h-12 w-12 text-white/75"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                        >
                                            <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>

                                            <path
                                                d="M6.5 2H20v20H6.5A2.5 2.5
                                                   0 014 19.5v-15A2.5 2.5
                                                   0 016.5 2z"
                                            ></path>
                                        </svg>

                                    </div>

                                @endif



                                {{-- STATUS --}}
                                <div class="absolute left-3 top-3">

                                    @if($isCompleted)

                                        <span
                                            class="inline-flex items-center gap-1.5
                                                   rounded-full bg-emerald-500
                                                   px-3 py-1.5
                                                   text-[10px] font-bold text-white
                                                   shadow-sm"
                                        >

                                            <span
                                                class="h-1.5 w-1.5
                                                       rounded-full bg-white"
                                            ></span>

                                            Completed

                                        </span>

                                    @else

                                        <span
                                            class="inline-flex items-center gap-1.5
                                                   rounded-full bg-white/95
                                                   px-3 py-1.5
                                                   text-[10px] font-bold
                                                   text-blue-700 shadow-sm
                                                   backdrop-blur"
                                        >

                                            <span
                                                class="h-1.5 w-1.5
                                                       rounded-full bg-blue-500"
                                            ></span>

                                            Active

                                        </span>

                                    @endif

                                </div>



                                {{-- PROGRESS --}}
                                <div
                                    class="absolute bottom-3 right-3
                                           rounded-full bg-slate-950/75
                                           px-3 py-1.5
                                           text-[11px] font-bold text-white
                                           backdrop-blur"
                                >
                                    {{ number_format($progress, 0) }}%
                                </div>

                            </div>



                            {{-- =========================================
                                CONTENT
                            ========================================== --}}

                            <div class="p-5">


                                {{-- CATEGORY --}}
                                <p
                                    class="text-[10px] font-bold uppercase
                                           tracking-[.08em] text-violet-500"
                                >
                                    {{ $course->category?->name ?? 'Course' }}
                                </p>



                                {{-- TITLE --}}
                                <h2
                                    class="mt-2 line-clamp-2 min-h-[48px]
                                           text-base font-bold leading-6
                                           text-slate-900"
                                >
                                    {{ $course->title }}
                                </h2>



                                {{-- DESCRIPTION --}}
                                <p
                                    class="mt-2 line-clamp-2 min-h-[40px]
                                           text-xs leading-5 text-slate-500"
                                >
                                    {{
                                        \Illuminate\Support\Str::limit(
                                            $course->description
                                                ?? 'Continue your learning journey in this course.',
                                            110
                                        )
                                    }}
                                </p>



                                {{-- INFO --}}
                                <div class="mt-4 flex flex-wrap gap-2">

                                    @if($course->difficulty_level)

                                        <span
                                            class="rounded-full bg-slate-100
                                                   px-2.5 py-1
                                                   text-[10px] font-semibold
                                                   text-slate-600"
                                        >
                                            {{ ucfirst($course->difficulty_level) }}
                                        </span>

                                    @endif


                                    @if($course->estimated_hours)

                                        <span
                                            class="rounded-full bg-blue-50
                                                   px-2.5 py-1
                                                   text-[10px] font-semibold
                                                   text-blue-600"
                                        >
                                            {{ $course->estimated_hours }} hrs
                                        </span>

                                    @endif


                                    @if($course->certificate_available)

                                        <span
                                            class="rounded-full bg-orange-50
                                                   px-2.5 py-1
                                                   text-[10px] font-semibold
                                                   text-orange-600"
                                        >
                                            Certificate
                                        </span>

                                    @endif

                                </div>



                                {{-- PROGRESS --}}
                                <div class="mt-5">

                                    <div class="flex items-center justify-between">

                                        <span class="text-xs font-semibold text-slate-500">
                                            Course Progress
                                        </span>


                                        <span
                                            class="text-xs font-bold
                                                   {{ $isCompleted
                                                       ? 'text-emerald-600'
                                                       : 'text-violet-600'
                                                   }}"
                                        >
                                            {{ number_format($progress, 0) }}%
                                        </span>

                                    </div>


                                    <div
                                        class="mt-2 h-2 overflow-hidden
                                               rounded-full bg-slate-100"
                                    >

                                        <div
                                            class="h-full rounded-full
                                                   {{ $isCompleted
                                                       ? 'bg-emerald-500'
                                                       : 'bg-violet-600'
                                                   }}"
                                            style="width: {{ $progress }}%;"
                                        ></div>

                                    </div>

                                </div>



                                {{-- ENROLLMENT --}}
                                <div
                                    class="mt-4 flex items-center justify-between
                                           border-t border-slate-100 pt-4"
                                >

                                    <div>

                                        <p class="text-[9px] font-semibold uppercase tracking-wide text-slate-400">
                                            Enrolled
                                        </p>

                                        <p class="mt-1 text-[11px] font-semibold text-slate-600">

                                            @if($enrollment->enrolled_at)

                                                {{
                                                    \Carbon\Carbon::parse(
                                                        $enrollment->enrolled_at
                                                    )->format('M d, Y')
                                                }}

                                            @else

                                                —

                                            @endif

                                        </p>

                                    </div>


                                    <div class="text-right">

                                        <p class="text-[9px] font-semibold uppercase tracking-wide text-slate-400">
                                            Status
                                        </p>

                                        <p
                                            class="mt-1 text-[11px] font-bold
                                                   {{ $isCompleted
                                                       ? 'text-emerald-600'
                                                       : 'text-blue-600'
                                                   }}"
                                        >
                                            {{ ucfirst($status) }}
                                        </p>

                                    </div>

                                </div>



                                {{-- ACTION --}}
                                @if($isCompleted)

                                    <div class="mt-5 grid grid-cols-2 gap-2">

                                        <a
                                            href="{{ route('student.learn.course', $course) }}"
                                            class="inline-flex h-10 items-center justify-center
                                                   rounded-xl border border-slate-200 bg-white
                                                   px-3 text-xs font-semibold text-slate-600
                                                   transition hover:border-violet-200
                                                   hover:text-violet-700"
                                        >
                                            Review Course
                                        </a>


                                        @if($course->certificate_available)

                                            <a
                                                href="{{ route('student.certificates') }}"
                                                class="inline-flex h-10 items-center justify-center
                                                       rounded-xl bg-emerald-600
                                                       px-3 text-xs font-semibold text-white
                                                       transition hover:bg-emerald-700"
                                            >
                                                Certificate
                                            </a>

                                        @else

                                            <a
                                                href="{{ route('student.course.show', $course) }}"
                                                class="inline-flex h-10 items-center justify-center
                                                       rounded-xl bg-violet-600
                                                       px-3 text-xs font-semibold text-white
                                                       transition hover:bg-violet-700"
                                            >
                                                Course Details
                                            </a>

                                        @endif

                                    </div>


                                @else

                                    <a
                                        href="{{ route('student.learn.course', $course) }}"
                                        class="mt-5 inline-flex h-10 w-full
                                               items-center justify-center gap-2
                                               rounded-xl bg-violet-600
                                               text-xs font-semibold text-white
                                               transition hover:bg-violet-700"
                                    >

                                        <svg
                                            class="h-3.5 w-3.5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <circle cx="12" cy="12" r="9"></circle>
                                            <path d="m10 8 6 4-6 4z"></path>
                                        </svg>

                                        Continue Learning
                                    </a>

                                @endif

                            </div>

                        </article>

                    @endforeach

                </section>



                {{-- =================================================
                    FILTER EMPTY
                ================================================== --}}

                <section
                    id="noCourseResults"
                    class="pw-card mt-6 hidden px-6 py-16 text-center"
                >

                    <div
                        class="mx-auto flex h-14 w-14
                               items-center justify-center
                               rounded-2xl bg-slate-100
                               text-slate-400"
                    >

                        <svg
                            class="h-6 w-6"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>

                    </div>


                    <h3 class="mt-4 text-sm font-bold text-slate-800">
                        No matching courses
                    </h3>


                    <p class="mt-1 text-xs text-slate-400">
                        Try changing your search or course filters.
                    </p>


                    <button
                        type="button"
                        id="emptyResetButton"
                        class="mt-4 inline-flex h-10
                               items-center justify-center
                               rounded-xl border
                               border-violet-200 bg-white
                               px-4 text-xs font-semibold
                               text-violet-700 transition
                               hover:bg-violet-50"
                    >
                        Clear Filters
                    </button>

                </section>


            @else


                {{-- =================================================
                    NO COURSES
                ================================================== --}}

                <section class="pw-card mt-6 px-6 py-20 text-center">

                    <div
                        class="mx-auto flex h-16 w-16
                               items-center justify-center
                               rounded-2xl bg-violet-50
                               text-violet-600"
                    >

                        <svg
                            class="h-7 w-7"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                        </svg>

                    </div>


                    <h3 class="mt-4 text-base font-bold text-slate-900">
                        You haven't enrolled in a course yet
                    </h3>


                    <p
                        class="mx-auto mt-2 max-w-md
                               text-sm leading-6 text-slate-500"
                    >
                        Explore the PathWise marketplace and choose
                        a course that matches your learning goals.
                    </p>


                    <a
                        href="{{ route('student.marketplace') }}"
                        class="mt-5 inline-flex h-11
                               items-center justify-center
                               gap-2 rounded-xl
                               bg-violet-600 px-5
                               text-sm font-semibold text-white
                               transition hover:bg-violet-700"
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

                </section>

            @endif

        </div>

    </main>

</div>



<script>
    function initializeMyCoursesPage() {

        const searchInput =
            document.getElementById('courseSearch');

        const categoryFilter =
            document.getElementById('categoryFilter');

        const statusFilter =
            document.getElementById('statusFilter');

        const progressFilter =
            document.getElementById('progressFilter');

        const clearButton =
            document.getElementById('clearFilters');

        const emptyResetButton =
            document.getElementById('emptyResetButton');

        const counter =
            document.getElementById('courseCounter');

        const emptyState =
            document.getElementById('noCourseResults');

        const grid =
            document.getElementById('courseGrid');

        const cards = Array.from(
            document.querySelectorAll('.course-card')
        );


        if (!searchInput || cards.length === 0) {
            return;
        }


        function filterCourses() {

            const search =
                searchInput.value
                    .trim()
                    .toLowerCase();

            const category =
                categoryFilter.value;

            const status =
                statusFilter.value;

            const progress =
                progressFilter.value;

            let visible = 0;


            cards.forEach(function (card) {

                const matchesSearch =
                    !search ||
                    card.dataset.search.includes(search);

                const matchesCategory =
                    !category ||
                    card.dataset.category === category;

                const matchesStatus =
                    !status ||
                    card.dataset.status === status;

                const matchesProgress =
                    !progress ||
                    card.dataset.progress === progress;


                const show =
                    matchesSearch &&
                    matchesCategory &&
                    matchesStatus &&
                    matchesProgress;


                card.classList.toggle(
                    'hidden',
                    !show
                );


                if (show) {
                    visible++;
                }

            });


            if (counter) {

                counter.textContent =
                    visible +
                    (
                        visible === 1
                            ? ' course'
                            : ' courses'
                    );

            }


            if (emptyState) {

                emptyState.classList.toggle(
                    'hidden',
                    visible !== 0
                );

            }


            if (grid) {

                grid.classList.toggle(
                    'hidden',
                    visible === 0
                );

            }

        }


        function resetFilters() {

            searchInput.value = '';
            categoryFilter.value = '';
            statusFilter.value = '';
            progressFilter.value = '';

            filterCourses();

        }


        searchInput.addEventListener(
            'input',
            filterCourses
        );


        categoryFilter.addEventListener(
            'change',
            filterCourses
        );


        statusFilter.addEventListener(
            'change',
            filterCourses
        );


        progressFilter.addEventListener(
            'change',
            filterCourses
        );


        if (clearButton) {

            clearButton.addEventListener(
                'click',
                resetFilters
            );

        }


        if (emptyResetButton) {

            emptyResetButton.addEventListener(
                'click',
                resetFilters
            );

        }


        filterCourses();

    }


    document.addEventListener(
        'DOMContentLoaded',
        initializeMyCoursesPage
    );


    document.addEventListener(
        'livewire:navigated',
        initializeMyCoursesPage
    );
</script>


</x-layouts::app>