<x-layouts::app :title="'My Courses'">

@php
    $courseEnrollments = collect($enrollments ?? []);

    $totalCourses = $courseEnrollments->count();

    $activeCourses = $sections['active']->count();

    $completedCourses = $sections['completed']->count();

    $courseSections = [
        'active' => [
            'title' => 'In Progress',
            'caption' => 'Courses you are taking now',
            'dot' => 'bg-blue-500',
            'enrollments' => $sections['active'],
            'empty_title' => 'No courses in progress',
            'empty_copy' => 'Start something new from the marketplace. Courses you enroll in will show up here.',
        ],
        'completed' => [
            'title' => 'Completed',
            'caption' => 'Finished courses and certificates',
            'dot' => 'bg-emerald-500',
            'enrollments' => $sections['completed'],
            'empty_title' => 'No completed courses yet',
            'empty_copy' => 'Finish every lesson in a course and it will move here, together with your certificate.',
        ],
        'cancelled' => [
            'title' => 'Cancelled',
            'caption' => 'Enrollments that no longer give access',
            'dot' => 'bg-slate-400',
            'enrollments' => $sections['cancelled'],
        ],
    ];

    // In Progress and Completed always show; Cancelled only when it has courses.
    if ($sections['cancelled']->isEmpty()) {
        unset($courseSections['cancelled']);
    }

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

    /* A cancelled course cannot be opened, so it does not lift on hover. */
    .pw-course-card-cancelled,
    .pw-course-card-cancelled:hover {
        transform: none;
        border-color: #e7e9ef;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.035);
        background: #fbfbfd;
    }
</style>


<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-375">


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
                                In Progress
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
                               xl:grid-cols-[minmax(280px,1fr)_220px_190px]"
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
                COURSES: In Progress, Completed, Cancelled
            ====================================================== --}}

            @if($courseEnrollments->isNotEmpty())

                @foreach($courseSections as $key => $section)

                    <section
                        class="mt-8"
                        data-course-section="{{ $key }}"
                        aria-labelledby="section-{{ $key }}-title"
                    >

                        <div class="flex items-center justify-between gap-3">

                            <div class="flex items-center gap-2.5">

                                <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $section['dot'] }}"></span>

                                <h2
                                    id="section-{{ $key }}-title"
                                    class="text-base font-bold text-slate-900 sm:text-lg"
                                >
                                    {{ $section['title'] }}
                                </h2>

                                <span
                                    class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-600"
                                    data-section-count
                                >
                                    {{ $section['enrollments']->count() }}
                                </span>

                            </div>

                            <p class="hidden text-xs text-slate-400 sm:block">
                                {{ $section['caption'] }}
                            </p>

                        </div>


                        @if($section['enrollments']->isNotEmpty())

                            <div
                                class="mt-4 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3"
                                data-section-grid
                            >
                                @foreach($section['enrollments'] as $enrollment)
                                    @include('Student.partials.my-course-card', [
                                        'enrollment' => $enrollment,
                                        'certificate' => $certificates->get($enrollment->course_id),
                                    ])
                                @endforeach
                            </div>

                            <p
                                class="pw-card mt-4 hidden px-5 py-6 text-center text-xs text-slate-400"
                                data-section-no-match
                            >
                                No {{ strtolower($section['title']) }} courses match your filters.
                            </p>

                        @else

                            <div
                                class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-white px-5 py-8 text-center sm:px-6"
                                data-section-empty
                            >

                                <p class="text-sm font-semibold text-slate-700">
                                    {{ $section['empty_title'] }}
                                </p>

                                <p class="mx-auto mt-1 max-w-md text-xs leading-5 text-slate-500">
                                    {{ $section['empty_copy'] }}
                                </p>

                                @if($key === 'active')
                                    <a
                                        href="{{ route('student.marketplace') }}"
                                        class="mt-4 inline-flex h-10 items-center justify-center rounded-xl border border-violet-200 bg-white px-4 text-xs font-semibold text-violet-700 transition hover:bg-violet-50"
                                    >
                                        Browse Courses
                                    </a>
                                @endif

                            </div>

                        @endif

                    </section>

                @endforeach



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
        const searchInput = document.getElementById('courseSearch');
        const categoryFilter = document.getElementById('categoryFilter');
        const progressFilter = document.getElementById('progressFilter');
        const emptyResetButton = document.getElementById('emptyResetButton');
        const counter = document.getElementById('courseCounter');
        const emptyState = document.getElementById('noCourseResults');
        const sections = Array.from(document.querySelectorAll('[data-course-section]'));
        const cards = Array.from(document.querySelectorAll('.course-card'));

        if (!searchInput || cards.length === 0) {
            return;
        }

        function filterCourses() {
            const search = searchInput.value.trim().toLowerCase();
            const category = categoryFilter.value;
            const progress = progressFilter.value;
            const filtering = Boolean(search || category || progress);

            let visible = 0;

            cards.forEach(function (card) {
                const show =
                    (!search || card.dataset.search.includes(search)) &&
                    (!category || card.dataset.category === category) &&
                    (!progress || card.dataset.progress === progress);

                card.classList.toggle('hidden', !show);

                if (show) {
                    visible++;
                }
            });

            // Per section: hide the grid and say so when its courses are all
            // filtered out. A section with no courses keeps its own message,
            // and while filtering it is hidden to keep the results short.
            sections.forEach(function (section) {
                const grid = section.querySelector('[data-section-grid]');
                const noMatch = section.querySelector('[data-section-no-match]');
                const shown = section.querySelectorAll('.course-card:not(.hidden)').length;

                if (grid) {
                    grid.classList.toggle('hidden', shown === 0);
                    noMatch.classList.toggle('hidden', shown !== 0);
                }

                section.classList.toggle('hidden', visible === 0 || (filtering && !grid));
            });

            if (counter) {
                counter.textContent = visible + (visible === 1 ? ' course' : ' courses');
            }

            if (emptyState) {
                emptyState.classList.toggle('hidden', visible !== 0);
            }
        }

        function resetFilters() {
            searchInput.value = '';
            categoryFilter.value = '';
            progressFilter.value = '';
            filterCourses();
        }

        searchInput.addEventListener('input', filterCourses);
        categoryFilter.addEventListener('change', filterCourses);
        progressFilter.addEventListener('change', filterCourses);

        if (emptyResetButton) {
            emptyResetButton.addEventListener('click', resetFilters);
        }

        filterCourses();
    }

    document.addEventListener('DOMContentLoaded', initializeMyCoursesPage);
    document.addEventListener('livewire:navigated', initializeMyCoursesPage);
</script>


</x-layouts::app>