<x-layouts::app :title="__('Lessons')">
@include('partials.soft3d')

    <div class="space-y-8 px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        {{-- =========================================================
             PAGE HEADER
        ========================================================== --}}

        {{-- Header card, as on My Certificates. --}}
        <section class="sad-banner overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">
            <div class="relative px-6 py-6 sm:px-7">
                <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full bg-violet-100/70 blur-3xl"></div>

        <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <p class="text-sm font-medium text-violet-600">
                    Course Management
                </p>

                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                    Lessons
                </h1>

                <p class="mt-2 max-w-2xl text-sm text-slate-500">
                    Manage course lessons, content type, and lesson order.
                </p>
            </div>

            <a href="{{ route('lessons.create') }}"
               class="sad-btn-primary inline-flex items-center justify-center rounded-lg bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">

                <svg class="mr-2 h-4 w-4"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M12 4v16m8-8H4" />

                </svg>

                Add Lesson

            </a>

        </div>
            </div>
        </section>


        {{-- =========================================================
             SUCCESS MESSAGE
        ========================================================== --}}

        @if(session('success'))

            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">

                {{ session('success') }}

            </div>

        @endif


        {{-- =========================================================
             STATISTICS
        ========================================================== --}}

        @php
            $totalLessons = $lessons->count();

            $textLessons = $lessons->where('lesson_type', 'text')->count();

            $videoLessons = $lessons->where('lesson_type', 'video')->count();

            $courseCount = $lessons->pluck('course_id')->unique()->count();
        @endphp


        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

            {{-- Total Lessons --}}
            <div class="sad-card rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Total Lessons
                        </p>

                        <h2 class="mt-2 text-3xl font-bold text-slate-900">
                            {{ $totalLessons }}
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            All lessons created
                        </p>
                    </div>

                    <div class="sad-chip sad-violet flex h-11 w-11 items-center justify-center rounded-xl bg-violet-100 text-violet-600">

                        <svg class="h-6 w-6"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor">

                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="1.5"
                                  d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253" />

                        </svg>

                    </div>

                </div>

            </div>


            {{-- Courses Covered --}}
            <div class="sad-card rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Courses Covered
                        </p>

                        <h2 class="mt-2 text-3xl font-bold text-violet-600">
                            {{ $courseCount }}
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            With lesson content
                        </p>
                    </div>

                    <div class="sad-chip sad-violet flex h-11 w-11 items-center justify-center rounded-xl bg-violet-100 text-violet-600">

                        <svg class="h-6 w-6"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor">

                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="1.5"
                                  d="M4 6.5A2.5 2.5 0 0 1 6.5 4H10l2 2h5.5A2.5 2.5 0 0 1 20 8.5v9a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-11Z" />

                        </svg>

                    </div>

                </div>

            </div>


            {{-- Text Lessons --}}
            <div class="sad-card rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Text Lessons
                        </p>

                        <h2 class="mt-2 text-3xl font-bold text-blue-600">
                            {{ $textLessons }}
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Reading materials
                        </p>
                    </div>

                    <div class="sad-chip sad-sky flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-blue-600">

                        <svg class="h-6 w-6"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor">

                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="1.5"
                                  d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15.5A2.5 2.5 0 0 0 17.5 16H4V5.5Z" />

                        </svg>

                    </div>

                </div>

            </div>


            {{-- Video Lessons --}}
            <div class="sad-card rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Video Lessons
                        </p>

                        <h2 class="mt-2 text-3xl font-bold text-green-600">
                            {{ $videoLessons }}
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Video content
                        </p>
                    </div>

                    <div class="sad-chip sad-emerald flex h-11 w-11 items-center justify-center rounded-xl bg-green-100 text-green-600">

                        <svg class="h-6 w-6"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor">

                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="1.5"
                                  d="m15 10 4.5-2.5v9L15 14m-9 5h8a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z" />

                        </svg>

                    </div>

                </div>

            </div>

        </div>


        {{-- =========================================================
             SEARCH AND FILTERS
        ========================================================== --}}

        <div class="sad-card rounded-xl border border-slate-200 bg-white p-4 shadow-sm">

            <div class="flex flex-col gap-3 lg:flex-row">

                {{-- Search --}}
                <div class="relative flex-1">

                    <svg
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"
                        />

                    </svg>

                    <input
                        id="lessonSearch"
                        type="text"
                        placeholder="Search lessons..."
                        class="w-full rounded-lg border-slate-300 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:border-violet-500 focus:ring-violet-500"
                    >

                </div>


                {{-- Course Filter --}}
                <select
                    id="courseFilter"
                    class="rounded-lg border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-violet-500 focus:ring-violet-500"
                >

                    <option value="all">
                        All Courses
                    </option>

                    @foreach($lessons->pluck('course')->filter()->unique('id')->sortBy('title') as $course)

                        <option value="{{ $course->id }}">
                            {{ $course->title }}
                        </option>

                    @endforeach

                </select>


                {{-- Type Filter --}}
                <select
                    id="typeFilter"
                    class="rounded-lg border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-violet-500 focus:ring-violet-500"
                >

                    <option value="all">
                        All Types
                    </option>

                    <option value="text">
                        Text
                    </option>

                    <option value="video">
                        Video
                    </option>

                    <option value="document">
                        Document
                    </option>

                    <option value="quiz">
                        Quiz
                    </option>

                </select>

            </div>

        </div>


        {{-- =========================================================
             LESSON HEADER
        ========================================================== --}}

        <div class="flex items-center justify-between">

            <div>

                <h2 class="text-xl font-semibold text-slate-900">
                    All Lessons
                </h2>

                <p class="mt-1 text-sm text-slate-500">

                    <span id="lessonCount">
                        {{ $totalLessons }}
                    </span>

                    {{ Str::plural('lesson', $totalLessons) }}

                    available

                </p>

            </div>

        </div>


        {{-- =========================================================
             LESSON GRID
        ========================================================== --}}

        <div id="lessonGrid"
             class="space-y-5">

            @forelse($lessons->groupBy(fn ($lesson) => $lesson->course->title ?? 'No Course') as $courseTitle => $courseLessons)

                <div
                    class="sad-card lesson-course-group rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                    data-course-id="{{ $courseLessons->first()->course_id ?? '' }}"
                >

                    {{-- Course Header --}}
                    <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">

                        <div class="flex items-center gap-3">

                            <div class="sad-chip sad-violet flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-sm font-bold text-violet-600">

                                {{ strtoupper(substr($courseTitle, 0, 1)) }}

                            </div>

                            <div>

                                <h2 class="text-lg font-bold text-slate-900">

                                    {{ $courseTitle }}

                                </h2>

                                <p class="text-sm text-slate-500">

                                    <span class="group-count">
                                        {{ $courseLessons->count() }}
                                    </span>

                                    lesson{{ $courseLessons->count() > 1 ? 's' : '' }}

                                </p>

                            </div>

                        </div>

                        <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700">

                            Course Lessons

                        </span>

                    </div>


                    {{-- Lessons --}}
                    <div class="mt-4 space-y-3">

                        @foreach($courseLessons->sortBy('lesson_order') as $lesson)

                            @php

                                $type = strtolower($lesson->lesson_type);

                                $typeClass = match ($type) {

                                    'video' =>
                                        'bg-violet-100 text-violet-700',

                                    'text' =>
                                        'bg-blue-100 text-blue-700',

                                    'document' =>
                                        'bg-amber-100 text-amber-700',

                                    'quiz' =>
                                        'bg-green-100 text-green-700',

                                    default =>
                                        'bg-slate-100 text-slate-600',

                                };

                            @endphp


                            <div
                                class="lesson-card flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-violet-300 hover:shadow-sm"
                                data-title="{{ strtolower($lesson->title) }}"
                                data-course-id="{{ $lesson->course_id }}"
                                data-type="{{ $type }}"
                            >

                                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">

                                    <div class="flex items-start gap-3">

                                        {{-- Order --}}
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-sm font-bold text-slate-600 shadow-sm">

                                            {{ $lesson->lesson_order }}

                                        </div>


                                        <div>

                                            <h3 class="font-semibold text-slate-900">

                                                {{ $lesson->title }}

                                            </h3>


                                            <div class="mt-2 flex flex-wrap gap-2">

                                                {{-- Type --}}
                                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $typeClass }}">

                                                    {{ ucfirst($lesson->lesson_type) }}

                                                </span>


                                                {{-- Duration --}}
                                                @if($lesson->duration_minutes)

                                                    <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-600">

                                                        {{ $lesson->duration_minutes }} mins

                                                    </span>

                                                @endif


                                                {{-- Preview --}}
                                                @if($lesson->is_preview)

                                                    <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700">

                                                        Preview

                                                    </span>

                                                @endif


                                                {{-- Published --}}
                                                @if($lesson->is_published)

                                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">

                                                        Published

                                                    </span>

                                                @else

                                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">

                                                        Draft

                                                    </span>

                                                @endif

                                            </div>

                                        </div>

                                    </div>


                                    {{-- Actions --}}
                                    <div class="flex shrink-0 gap-2">

                                        <a
                                            href="{{ route('lessons.edit', $lesson) }}"
                                            class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                        >

                                            Edit

                                        </a>


                                        <form
                                            action="{{ route('lessons.destroy', $lesson) }}"
                                            method="POST"
                                        >

                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                onclick="return confirm('Delete this lesson?')"
                                                class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50"
                                            >

                                                Delete

                                            </button>

                                        </form>

                                    </div>

                                </div>

                            </div>

                        @endforeach

                    </div>

                </div>

            @empty

                {{-- =====================================================
                     EMPTY STATE
                ====================================================== --}}

                <div class="sad-card rounded-xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">

                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-violet-100 text-violet-600">

                        <svg class="h-7 w-7"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor">

                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="1.5"
                                  d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253" />

                        </svg>

                    </div>

                    <h3 class="mt-4 text-lg font-semibold text-slate-900">

                        No lessons found

                    </h3>

                    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">

                        Lessons will appear here once they are created.

                    </p>

                    <a
                        href="{{ route('lessons.create') }}"
                        class="sad-btn-primary mt-5 inline-flex rounded-lg bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-violet-700"
                    >

                        Add Lesson

                    </a>

                </div>

            @endforelse


            {{-- =========================================================
                 NO SEARCH RESULTS
            ========================================================== --}}

            <div
                id="noResults"
                class="sad-card hidden rounded-xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center"
            >

                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">

                    <svg class="h-7 w-7"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="1.5"
                              d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />

                    </svg>

                </div>

                <h3 class="mt-4 text-lg font-semibold text-slate-900">

                    No lessons found

                </h3>

                <p class="mt-1 text-sm text-slate-500">

                    Try changing your search or filters.

                </p>

            </div>

        </div>

    </div>


    {{-- =========================================================
         AUTOMATIC SEARCH / FILTER SCRIPT
    ========================================================== --}}

    <script>

        function initializeLessonFilters() {

            const searchInput = document.getElementById('lessonSearch');
            const courseFilter = document.getElementById('courseFilter');
            const typeFilter = document.getElementById('typeFilter');

            const groups = document.querySelectorAll('.lesson-course-group');
            const cards = document.querySelectorAll('.lesson-card');

            const noResults = document.getElementById('noResults');
            const lessonCount = document.getElementById('lessonCount');


            if (!searchInput || !courseFilter || !typeFilter) {
                return;
            }


            function filterLessons() {

                const searchValue =
                    searchInput.value.toLowerCase().trim();

                const courseValue =
                    courseFilter.value;

                const typeValue =
                    typeFilter.value;

                let visibleCount = 0;


                cards.forEach(function(card) {

                    const title =
                        card.dataset.title || '';

                    const courseId =
                        card.dataset.courseId || '';

                    const type =
                        card.dataset.type || '';


                    const matchesSearch =
                        title.includes(searchValue);

                    const matchesCourse =
                        courseValue === 'all' ||
                        courseId === courseValue;

                    const matchesType =
                        typeValue === 'all' ||
                        type === typeValue;


                    if (
                        matchesSearch &&
                        matchesCourse &&
                        matchesType
                    ) {

                        card.classList.remove('hidden');

                        visibleCount++;

                    } else {

                        card.classList.add('hidden');

                    }

                });


                // Hide course groups with no visible lessons
                groups.forEach(function(group) {

                    const visibleLessons =
                        group.querySelectorAll('.lesson-card:not(.hidden)').length;

                    if (visibleLessons === 0) {

                        group.classList.add('hidden');

                    } else {

                        group.classList.remove('hidden');

                    }

                });


                lessonCount.textContent =
                    visibleCount;


                if (visibleCount === 0) {

                    noResults.classList.remove('hidden');

                } else {

                    noResults.classList.add('hidden');

                }

            }


            // Prevent duplicate listeners
            if (!searchInput.dataset.initialized) {

                searchInput.addEventListener(
                    'input',
                    filterLessons
                );

                courseFilter.addEventListener(
                    'change',
                    filterLessons
                );

                typeFilter.addEventListener(
                    'change',
                    filterLessons
                );

                searchInput.dataset.initialized = 'true';

            }


            filterLessons();

        }


        // Normal page load
        document.addEventListener(
            'DOMContentLoaded',
            initializeLessonFilters
        );


        // Important for Flux / wire:navigate
        document.addEventListener(
            'livewire:navigated',
            initializeLessonFilters
        );

    </script>

</x-layouts::app>