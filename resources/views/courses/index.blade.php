<x-layouts::app :title="__('Courses')">
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
                    Courses
                </h1>

                <p class="mt-2 max-w-2xl text-sm text-slate-500">
                    Manage and organize the courses available on PathWise.
                </p>
            </div>

            <a href="{{ route('courses.create') }}"
               class="sad-btn-primary inline-flex items-center justify-center h-11 rounded-xl bg-violet-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">

                <svg class="mr-2 h-4 w-4"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M12 4v16m8-8H4" />

                </svg>

                Add Course

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

        @if(session('error') || $errors->any())

            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') ?? $errors->first() }}
            </div>

        @endif


        {{-- =========================================================
             SEARCH AND FILTERS
        ========================================================== --}}

        <div class="sad-card rounded-xl border border-slate-200 bg-white p-4 shadow-sm">

            <div class="flex flex-col gap-3 lg:flex-row">

                {{-- SEARCH --}}
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
                        id="courseSearch"
                        type="text"
                        autocomplete="off"
                        placeholder="Search courses..."
                        class="w-full rounded-lg border-slate-300 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:border-violet-500 focus:ring-violet-500"
                    >

                </div>


                {{-- CATEGORY --}}
                <select
                    id="categoryFilter"
                    class="rounded-lg border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-violet-500 focus:ring-violet-500"
                >

                    <option value="all">
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
                    class="rounded-lg border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-violet-500 focus:ring-violet-500"
                >

                    <option value="all">
                        All Status
                    </option>

                    <option value="published">
                        Published
                    </option>

                    <option value="pending">
                        Pending
                    </option>

                    <option value="draft">
                        Draft
                    </option>

                    <option value="rejected">
                        Rejected
                    </option>

                </select>

            </div>

        </div>


        {{-- =========================================================
             COURSE COUNT
        ========================================================== --}}

        <div class="flex items-center justify-between">

            <div>

                <h2 class="text-xl font-semibold text-slate-900">
                    All Courses
                </h2>

                <p class="mt-1 text-sm text-slate-500">

                    <span id="courseCount">
                        {{ $courses->count() }}
                    </span>

                    <span id="courseCountText">
                        {{ Str::plural('course', $courses->count()) }}
                    </span>

                    available

                </p>

            </div>

        </div>


        {{-- =========================================================
             COURSE GRID
        ========================================================== --}}

        <div
            id="courseGrid"
            class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3"
        >

            @forelse($courses as $course)

                @php

                    $status = strtolower($course->status);

                    $statusClass = match ($status) {

                        'published' =>
                            'bg-green-100 text-green-700',

                        'pending' =>
                            'bg-amber-100 text-amber-700',

                        'rejected' =>
                            'bg-red-100 text-red-700',

                        'draft' =>
                            'bg-slate-100 text-slate-600',

                        default =>
                            'bg-blue-100 text-blue-700',

                    };

                @endphp


                {{-- =====================================================
                     COURSE CARD
                ====================================================== --}}

                <div
                    class="sad-card course-card group overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg"
                    data-title="{{ strtolower($course->title) }}"
                    data-category="{{ $course->category_id }}"
                    data-status="{{ strtolower($course->status) }}"
                >


                    {{-- THUMBNAIL --}}

                    <x-pw.course-cover :course="$course">

                        {{-- PREVIEW: the whole cover and the button open this course's preview --}}
                        <button
                            type="button"
                            class="absolute inset-0 z-[1] cursor-pointer focus:outline-none focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-violet-300"
                            onclick="document.getElementById('course-preview-{{ $course->id }}').showModal()"
                            aria-label="Preview {{ $course->title }}"
                        ></button>

                        <button
                            type="button"
                            class="absolute bottom-3 left-3 z-[2] inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1.5 text-xs font-semibold text-violet-700 shadow-sm ring-1 ring-violet-100 transition hover:bg-white hover:text-violet-800"
                            onclick="document.getElementById('course-preview-{{ $course->id }}').showModal()"
                            data-test="course-preview-button"
                        >
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            Preview
                        </button>

                        {{-- STATUS BADGE --}}

                        <div class="absolute right-3 top-3 z-[2]">

                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                {{ ucfirst($course->status) }}
                            </span>

                        </div>

                    </x-pw.course-cover>


                    {{-- COURSE DETAILS --}}

                    <div class="p-5">

                        {{-- CATEGORY --}}

                        <p class="text-xs font-semibold uppercase tracking-wide text-violet-600">

                            {{ $course->category->name ?? 'No Category' }}

                        </p>


                        {{-- TITLE --}}

                        <h3 class="mt-2 line-clamp-2 text-lg font-bold leading-snug text-slate-900">

                            {{ $course->title }}

                        </h3>


                        {{-- TEACHER --}}

                        <p class="mt-2 text-sm text-slate-500">

                            By {{ $course->teacher->name ?? 'No Teacher' }}

                        </p>


                        {{-- PRICE --}}

                        <div class="mt-4">

                            @if($course->price > 0)

                                <span class="text-lg font-bold text-slate-900">

                                    ₱{{ number_format($course->price, 2) }}

                                </span>

                            @else

                                <span class="text-lg font-bold text-green-600">

                                    Free

                                </span>

                            @endif

                        </div>


                        <div class="my-4 border-t border-slate-100"></div>


                        {{-- ACTIONS --}}

                        <div class="flex flex-wrap gap-2">

                            @if($course->status === 'pending')

                                {{-- APPROVE --}}

                                <form
                                    action="{{ route('courses.approve', $course) }}"
                                    method="POST"
                                    class="flex-1"
                                >

                                    @csrf

                                    <button
                                        type="submit"
                                        class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700"
                                    >
                                        Approve
                                    </button>

                                </form>


                                {{-- REJECT --}}

                                {{-- The administrator must write feedback before the
                                     course is returned (Manuscript Figure 29). --}}
                                <form
                                    action="{{ route('courses.reject', $course) }}"
                                    method="POST"
                                    class="flex-1"
                                    onsubmit="
                                        const feedback = prompt('Feedback for the teacher (what needs to be revised?):');
                                        if (feedback === null) { return false; }
                                        if (feedback.trim().length < 5) { alert('Please write at least 5 characters of feedback.'); return false; }
                                        this.querySelector('input[name=remarks]').value = feedback.trim();
                                        return true;
                                    "
                                >

                                    @csrf

                                    <input type="hidden" name="remarks" value="">

                                    <button
                                        type="submit"
                                        class="w-full rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-red-700"
                                    >
                                        Return for Revision
                                    </button>

                                </form>

                            @else

                                {{-- EDIT --}}

                                <a
                                    href="{{ route('courses.edit', $course) }}"
                                    class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-center text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    Edit
                                </a>

                            @endif


                            {{-- DELETE --}}

                            <form
                                action="{{ route('courses.destroy', $course) }}"
                                method="POST"
                                class="flex-1"
                            >

                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    onclick="return confirm('Delete this course?')"
                                    class="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50"
                                >
                                    Delete
                                </button>

                            </form>

                        </div>

                    </div>

                </div>


                {{-- =====================================================
                     COURSE PREVIEW (one native dialog per course: the
                     close buttons, Esc or a click outside close it and
                     leave the admin on Course Management)
                ====================================================== --}}

                <dialog
                    id="course-preview-{{ $course->id }}"
                    class="sad-dialog"
                    aria-labelledby="course-preview-{{ $course->id }}-title"
                    onclick="if (event.target === this) this.close()"
                    data-test="course-preview"
                >

                    <div class="flex max-h-[88vh] flex-col">

                        {{-- Header --}}
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-violet-600">Course Preview</p>
                                <h2 id="course-preview-{{ $course->id }}-title" class="mt-0.5 truncate text-base font-bold text-slate-900">
                                    {{ $course->title }}
                                </h2>
                            </div>

                            <button
                                type="button"
                                onclick="this.closest('dialog').close()"
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"></path></svg>
                                <span class="sr-only">Close preview</span>
                            </button>
                        </div>

                        {{-- Body --}}
                        <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-6">

                            <x-pw.course-cover :course="$course" framed />

                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ ucfirst($course->status) }}</span>
                                <span class="rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700">{{ $course->category->name ?? 'No Category' }}</span>
                                @if($course->difficulty_level)
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ ucfirst($course->difficulty_level) }}</span>
                                @endif
                            </div>

                            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                                <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                    <dt class="text-xs text-slate-500">Teacher</dt>
                                    <dd class="mt-0.5 truncate font-semibold text-slate-800">{{ $course->teacher->name ?? 'No Teacher' }}</dd>
                                </div>
                                <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                    <dt class="text-xs text-slate-500">Price</dt>
                                    <dd class="mt-0.5 font-semibold {{ $course->price > 0 ? 'text-slate-800' : 'text-green-600' }}">
                                        {{ $course->price > 0 ? '₱'.number_format($course->price, 2) : 'Free' }}
                                    </dd>
                                </div>
                                <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                    <dt class="text-xs text-slate-500">Duration</dt>
                                    <dd class="mt-0.5 font-semibold text-slate-800">{{ $course->estimated_hours ? $course->estimated_hours.' hrs' : '—' }}</dd>
                                </div>
                                <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                    <dt class="text-xs text-slate-500">Certificate</dt>
                                    <dd class="mt-0.5 font-semibold text-slate-800">{{ $course->certificate_available ? 'Available' : 'None' }}</dd>
                                </div>
                            </dl>

                            <div class="mt-5">
                                <h3 class="text-sm font-bold text-slate-900">Description</h3>
                                <p class="mt-1.5 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $course->description ?: 'No description provided.' }}</p>
                            </div>

                            @if($course->review_remarks && $status === 'rejected')
                                <div class="mt-5 rounded-xl border border-red-100 bg-red-50 px-4 py-3">
                                    <h3 class="text-xs font-bold uppercase tracking-wide text-red-700">Revision feedback</h3>
                                    <p class="mt-1 text-sm text-red-700">{{ $course->review_remarks }}</p>
                                </div>
                            @endif

                            <div class="mt-5">
                                <h3 class="text-sm font-bold text-slate-900">
                                    Lessons
                                    <span class="font-medium text-slate-500">({{ $course->lessons->count() }})</span>
                                </h3>

                                @if($course->lessons->isNotEmpty())
                                    <ol class="mt-2 divide-y divide-slate-100 rounded-xl border border-slate-100">
                                        @foreach($course->lessons as $lesson)
                                            <li class="flex items-center gap-3 px-3 py-2.5 text-sm">
                                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-violet-50 text-xs font-bold text-violet-700">{{ $loop->iteration }}</span>
                                                <span class="min-w-0 flex-1 truncate text-slate-700">{{ $lesson->title }}</span>
                                                <span class="shrink-0 text-xs text-slate-400">{{ ucfirst((string) $lesson->lesson_type) }}</span>
                                            </li>
                                        @endforeach
                                    </ol>
                                @else
                                    <p class="mt-1.5 text-sm text-slate-500">No lessons yet.</p>
                                @endif
                            </div>

                        </div>

                        {{-- Footer --}}
                        <div class="flex justify-end border-t border-slate-100 px-5 py-3 sm:px-6">
                            <button
                                type="button"
                                onclick="this.closest('dialog').close()"
                                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                data-test="course-preview-close"
                            >
                                Close
                            </button>
                        </div>

                    </div>

                </dialog>

            @empty

                {{-- DATABASE EMPTY --}}

                <div
                    class="sad-card col-span-full rounded-xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center"
                >

                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-violet-100 text-violet-600">

                        <svg
                            class="h-7 w-7"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253"
                            />

                        </svg>

                    </div>

                    <h3 class="mt-4 text-lg font-semibold text-slate-900">
                        No courses found
                    </h3>

                    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                        Create your first course to get started.
                    </p>

                    <a
                        href="{{ route('courses.create') }}"
                        class="sad-btn-primary mt-5 inline-flex h-11 items-center justify-center rounded-xl bg-violet-600 px-5 text-sm font-semibold text-white hover:bg-violet-700"
                    >
                        Add Course
                    </a>

                </div>

            @endforelse


            {{-- =====================================================
                 NO SEARCH RESULTS
            ====================================================== --}}

            <div
                id="noCourseResults"
                class="sad-card col-span-full hidden rounded-xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center"
            >

                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">

                    <svg
                        class="h-7 w-7"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"
                        />

                    </svg>

                </div>

                <h3 class="mt-4 text-lg font-semibold text-slate-900">
                    No courses found
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Try searching for another course or changing your filters.
                </p>

            </div>

        </div>

    </div>


    {{-- =============================================================
         COURSE SEARCH / FILTER SCRIPT
    ============================================================= --}}

    <script>

        (function () {

            function initializeCourseFilters() {

                const searchInput =
                    document.getElementById('courseSearch');

                const categoryFilter =
                    document.getElementById('categoryFilter');

                const statusFilter =
                    document.getElementById('statusFilter');

                const courseCount =
                    document.getElementById('courseCount');

                const courseCountText =
                    document.getElementById('courseCountText');

                const noResults =
                    document.getElementById('noCourseResults');


                /*
                 * Stop if this is not the Courses page.
                 */
                if (
                    !searchInput ||
                    !categoryFilter ||
                    !statusFilter
                ) {
                    return;
                }


                /*
                 * Prevent duplicate event listeners
                 * when wire:navigate loads the page again.
                 */
                if (searchInput.dataset.filterInitialized === 'true') {
                    return;
                }

                searchInput.dataset.filterInitialized = 'true';


                function filterCourses() {

                    const searchValue =
                        searchInput.value
                            .toLowerCase()
                            .trim();

                    const categoryValue =
                        categoryFilter.value;

                    const statusValue =
                        statusFilter.value;


                    const cards =
                        document.querySelectorAll('.course-card');


                    let visibleCount = 0;


                    cards.forEach(function (card) {

                        const title =
                            (card.dataset.title || '')
                                .toLowerCase();

                        const category =
                            card.dataset.category || '';

                        const status =
                            (card.dataset.status || '')
                                .toLowerCase();


                        const matchesSearch =
                            title.includes(searchValue);


                        const matchesCategory =
                            categoryValue === 'all' ||
                            category === categoryValue;


                        const matchesStatus =
                            statusValue === 'all' ||
                            status === statusValue;


                        if (
                            matchesSearch &&
                            matchesCategory &&
                            matchesStatus
                        ) {

                            card.classList.remove('hidden');

                            visibleCount++;

                        } else {

                            card.classList.add('hidden');

                        }

                    });


                    /*
                     * Update count
                     */
                    if (courseCount) {

                        courseCount.textContent =
                            visibleCount;

                    }


                    if (courseCountText) {

                        courseCountText.textContent =
                            visibleCount === 1
                                ? 'course'
                                : 'courses';

                    }


                    /*
                     * No results message
                     */
                    if (noResults) {

                        if (visibleCount === 0) {

                            noResults.classList.remove('hidden');

                        } else {

                            noResults.classList.add('hidden');

                        }

                    }

                }


                /*
                 * SEARCH WHILE TYPING
                 */
                searchInput.addEventListener(
                    'input',
                    filterCourses
                );


                /*
                 * CATEGORY AUTOMATIC FILTER
                 */
                categoryFilter.addEventListener(
                    'change',
                    filterCourses
                );


                /*
                 * STATUS AUTOMATIC FILTER
                 */
                statusFilter.addEventListener(
                    'change',
                    filterCourses
                );


                /*
                 * Run immediately
                 */
                filterCourses();

            }


            /*
             * Initial page load
             */
            if (document.readyState === 'loading') {

                document.addEventListener(
                    'DOMContentLoaded',
                    initializeCourseFilters
                );

            } else {

                initializeCourseFilters();

            }


            /*
             * IMPORTANT FOR FLUX / LIVEWIRE NAVIGATION
             *
             * This runs when navigating:
             *
             * Courses
             *     ↓
             * Categories
             *     ↓
             * Courses
             *
             * without refreshing the browser.
             */
            document.addEventListener(
                'livewire:navigated',
                initializeCourseFilters
            );

        })();

    </script>

</x-layouts::app>