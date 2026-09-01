<x-layouts::app :title="'Course Marketplace'">

@php
    $courseCollection = collect($courses ?? []);

    $totalCourses = $courseCollection->count();

    $freeCourses = $courseCollection
        ->filter(fn ($course) => (float) ($course->price ?? 0) <= 0)
        ->count();

    $paidCourses = $totalCourses - $freeCourses;

    $categories = $courseCollection
        ->map(fn ($course) => $course->category)
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
                        Discover Learning
                    </p>

                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                        Course Marketplace
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                        Explore available PathWise courses and find the
                        right learning experience for your goals.
                    </p>

                </div>


                <a
                    href="{{ route('student.my-courses') }}"
                    class="inline-flex h-11 self-start items-center justify-center
                           gap-2 rounded-xl border border-slate-200
                           bg-white px-4 text-sm font-semibold text-slate-600
                           transition hover:border-violet-200
                           hover:text-violet-700"
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

            </div>



            {{-- =====================================================
                SUMMARY CARDS
            ====================================================== --}}

            <section class="mt-7 grid grid-cols-2 gap-4 lg:grid-cols-4">


                {{-- AVAILABLE --}}
                <div class="pw-card p-5">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <p class="text-xs font-semibold text-slate-500">
                                Available Courses
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
                        Published learning courses
                    </p>

                </div>



                {{-- CATEGORIES --}}
                <div class="pw-card p-5">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <p class="text-xs font-semibold text-slate-500">
                                Categories
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-blue-600">
                                {{ $categories->count() }}
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
                                <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                                <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                                <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                                <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                            </svg>

                        </div>

                    </div>

                    <p class="mt-2 text-xs text-slate-400">
                        Areas you can explore
                    </p>

                </div>



                {{-- FREE --}}
                <div class="pw-card p-5">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <p class="text-xs font-semibold text-slate-500">
                                Free Courses
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-600">
                                {{ $freeCourses }}
                            </p>

                        </div>


                        <div
                            class="flex h-10 w-10 items-center justify-center
                                   rounded-xl bg-emerald-50 font-bold text-emerald-600"
                        >
                            ₱0
                        </div>

                    </div>

                    <p class="mt-2 text-xs text-slate-400">
                        Start learning at no cost
                    </p>

                </div>



                {{-- PREMIUM --}}
                <div class="pw-card p-5">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <p class="text-xs font-semibold text-slate-500">
                                Premium Courses
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-orange-500">
                                {{ $paidCourses }}
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
                                <path d="M12 2 15 8l6 .9-4.5 4.4 1.1 6.2L12 16.6 6.4 19.5l1.1-6.2L3 8.9 9 8z"></path>
                            </svg>

                        </div>

                    </div>

                    <p class="mt-2 text-xs text-slate-400">
                        Premium learning options
                    </p>

                </div>

            </section>



            {{-- =====================================================
                SEARCH AND FILTERS
            ====================================================== --}}

            <section class="pw-card mt-6 p-5 sm:p-6">

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                    <div>

                        <h2 class="text-lg font-bold text-slate-900">
                            Explore Courses
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Search or filter courses based on your interests.
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



                <div
                    class="mt-5 grid grid-cols-1 gap-3
                           md:grid-cols-2
                           xl:grid-cols-[minmax(280px,1fr)_220px_190px_170px_auto]"
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
                            placeholder="Search courses..."
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



                    {{-- DIFFICULTY --}}
                    <select
                        id="difficultyFilter"
                        class="pw-control h-11 px-3"
                    >

                        <option value="">
                            All Levels
                        </option>

                        <option value="beginner">
                            Beginner
                        </option>

                        <option value="intermediate">
                            Intermediate
                        </option>

                        <option value="advanced">
                            Advanced
                        </option>

                    </select>



                    {{-- PRICE --}}
                    <select
                        id="priceFilter"
                        class="pw-control h-11 px-3"
                    >

                        <option value="">
                            All Prices
                        </option>

                        <option value="free">
                            Free
                        </option>

                        <option value="paid">
                            Premium
                        </option>

                    </select>



            
                </div>

            </section>



            {{-- =====================================================
                COURSE GRID
            ====================================================== --}}

            @if($courseCollection->isNotEmpty())

                <section
                    id="courseGrid"
                    class="mt-6 grid grid-cols-1 gap-5
                           sm:grid-cols-2
                           xl:grid-cols-3
                           2xl:grid-cols-4"
                >

                    @forelse($courseCollection as $course)

                        @php
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
                                        'storage/' . ltrim($thumbnail, '/')
                                    );
                                }
                            }

                            $price = (float) ($course->price ?? 0);
                            $isFree = $price <= 0;

                            $difficulty = strtolower(
                                $course->difficulty_level ?? 'beginner'
                            );

                            $categoryId = $course->category?->id ?? '';

                            $searchText = strtolower(
                                ($course->title ?? '') . ' ' .
                                ($course->description ?? '') . ' ' .
                                ($course->category?->name ?? '') . ' ' .
                                ($course->teacher?->name ?? '')
                            );
                        @endphp


                        <article
                            class="course-card pw-card pw-course-card overflow-hidden"
                            data-search="{{ $searchText }}"
                            data-category="{{ $categoryId }}"
                            data-difficulty="{{ $difficulty }}"
                            data-price="{{ $isFree ? 'free' : 'paid' }}"
                        >


                            {{-- =========================================
                                THUMBNAIL
                            ========================================== --}}

                            <div class="relative h-48 overflow-hidden bg-slate-100">

                                @if($thumbnailUrl)

                                    <img
                                        src="{{ $thumbnailUrl }}"
                                        alt="{{ $course->title }}"
                                        class="h-full w-full object-cover
                                               transition duration-300
                                               hover:scale-[1.02]"
                                    >

                                @else

                                    <div
                                        class="flex h-full w-full items-center justify-center
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
                                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                        </svg>

                                    </div>

                                @endif



                                {{-- PRICE --}}
                                <div class="absolute right-3 top-3">

                                    @if($isFree)

                                        <span
                                            class="inline-flex rounded-full
                                                   bg-emerald-500 px-3 py-1.5
                                                   text-[10px] font-bold
                                                   text-white shadow-sm"
                                        >
                                            FREE
                                        </span>

                                    @else

                                        <span
                                            class="inline-flex rounded-full
                                                   bg-white/95 px-3 py-1.5
                                                   text-[11px] font-bold
                                                   text-slate-900
                                                   shadow-sm backdrop-blur"
                                        >
                                            ₱{{ number_format($price, 2) }}
                                        </span>

                                    @endif

                                </div>



                                {{-- DIFFICULTY --}}
                                <div class="absolute bottom-3 left-3">

                                    <span
                                        class="inline-flex rounded-full
                                               bg-slate-950/70 px-2.5 py-1
                                               text-[10px] font-semibold
                                               text-white backdrop-blur"
                                    >
                                        {{ ucfirst($difficulty) }}
                                    </span>

                                </div>

                            </div>



                            {{-- =========================================
                                CONTENT
                            ========================================== --}}

                            <div class="p-5">

                                <p
                                    class="text-[10px] font-bold uppercase
                                           tracking-[.08em] text-violet-500"
                                >
                                    {{ $course->category?->name ?? 'General' }}
                                </p>


                                <h2
                                    class="mt-2 line-clamp-2 min-h-[48px]
                                           text-base font-bold leading-6
                                           text-slate-900"
                                >
                                    {{ $course->title }}
                                </h2>


                                <p
                                    class="mt-2 line-clamp-2 min-h-[40px]
                                           text-xs leading-5 text-slate-500"
                                >
                                    {{
                                        \Illuminate\Support\Str::limit(
                                            $course->description ?? 'No course description available.',
                                            110
                                        )
                                    }}
                                </p>



                                {{-- TEACHER --}}
                                <div class="mt-4 flex items-center gap-2">

                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center
                                               justify-center rounded-full
                                               bg-violet-100
                                               text-[10px] font-bold
                                               text-violet-700"
                                    >
                                        {{
                                            strtoupper(
                                                substr(
                                                    $course->teacher?->name
                                                        ?? 'T',
                                                    0,
                                                    1
                                                )
                                            )
                                        }}
                                    </div>


                                    <div class="min-w-0">

                                        <p class="text-[10px] text-slate-400">
                                            Instructor
                                        </p>

                                        <p
                                            class="truncate text-xs font-semibold
                                                   text-slate-700"
                                        >
                                            {{ $course->teacher?->name ?? 'PathWise Instructor' }}
                                        </p>

                                    </div>

                                </div>



                                {{-- INFO --}}
                                <div
                                    class="mt-4 grid grid-cols-2 gap-2
                                           border-t border-slate-100 pt-4"
                                >

                                    <div
                                        class="rounded-xl bg-slate-50
                                               px-3 py-2.5"
                                    >

                                        <p class="text-[9px] font-semibold uppercase tracking-wide text-slate-400">
                                            Duration
                                        </p>

                                        <p class="mt-1 text-xs font-bold text-slate-700">
                                            @if($course->estimated_hours)
                                                {{ $course->estimated_hours }} hrs
                                            @else
                                                Flexible
                                            @endif
                                        </p>

                                    </div>


                                    <div
                                        class="rounded-xl bg-slate-50
                                               px-3 py-2.5"
                                    >

                                        <p class="text-[9px] font-semibold uppercase tracking-wide text-slate-400">
                                            Certificate
                                        </p>

                                        <p class="mt-1 text-xs font-bold text-slate-700">
                                            {{
                                                $course->certificate_available
                                                    ? 'Available'
                                                    : 'Not included'
                                            }}
                                        </p>

                                    </div>

                                </div>



                                {{-- BUTTON --}}
                                <a
                                    href="{{ route('student.course.show', $course) }}"
                                    class="mt-5 inline-flex h-10 w-full
                                           items-center justify-center gap-2
                                           rounded-xl bg-violet-600
                                           text-xs font-semibold text-white
                                           transition hover:bg-violet-700"
                                >
                                    View Course

                                    <svg
                                        class="h-3.5 w-3.5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path d="m9 18 6-6-6-6"></path>
                                    </svg>

                                </a>

                            </div>

                        </article>

                    @empty
                    @endforelse

                </section>



                {{-- =================================================
                    FILTER EMPTY STATE
                ================================================== --}}

                <section
                    id="noCourseResults"
                    class="pw-card mt-6 hidden px-6 py-16 text-center"
                >

                    <div
                        class="mx-auto flex h-14 w-14 items-center
                               justify-center rounded-2xl
                               bg-slate-100 text-slate-400"
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
                        Try changing your search or filters.
                    </p>


                    <button
                        type="button"
                        id="emptyResetButton"
                        class="mt-4 inline-flex h-10 items-center justify-center
                               rounded-xl border border-violet-200
                               bg-white px-4 text-xs font-semibold
                               text-violet-700 transition
                               hover:bg-violet-50"
                    >
                        Clear Filters
                    </button>

                </section>


            @else


                {{-- =================================================
                    NO PUBLISHED COURSES
                ================================================== --}}

                <section class="pw-card mt-6 px-6 py-20 text-center">

                    <div
                        class="mx-auto flex h-16 w-16 items-center
                               justify-center rounded-2xl
                               bg-violet-50 text-violet-600"
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
                        No courses available yet
                    </h3>


                    <p
                        class="mx-auto mt-2 max-w-md
                               text-sm leading-6 text-slate-500"
                    >
                        There are currently no published courses
                        available in the marketplace. Check back later
                        for new learning opportunities.
                    </p>


                    <a
                        href="{{ route('student.dashboard') }}"
                        class="mt-5 inline-flex h-11 items-center justify-center
                               rounded-xl bg-violet-600 px-5
                               text-sm font-semibold text-white
                               transition hover:bg-violet-700"
                    >
                        Back to Dashboard
                    </a>

                </section>

            @endif

        </div>

    </main>

</div>



<script>
    function initializeMarketplacePage() {

        const searchInput =
            document.getElementById('courseSearch');

        const categoryFilter =
            document.getElementById('categoryFilter');

        const difficultyFilter =
            document.getElementById('difficultyFilter');

        const priceFilter =
            document.getElementById('priceFilter');

        const clearButton =
            document.getElementById('clearCourseFilters');

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
                searchInput.value.trim().toLowerCase();

            const category =
                categoryFilter.value;

            const difficulty =
                difficultyFilter.value;

            const price =
                priceFilter.value;

            let visible = 0;


            cards.forEach(function (card) {

                const matchesSearch =
                    !search ||
                    card.dataset.search.includes(search);

                const matchesCategory =
                    !category ||
                    card.dataset.category === category;

                const matchesDifficulty =
                    !difficulty ||
                    card.dataset.difficulty === difficulty;

                const matchesPrice =
                    !price ||
                    card.dataset.price === price;


                const show =
                    matchesSearch &&
                    matchesCategory &&
                    matchesDifficulty &&
                    matchesPrice;


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


        function clearFilters() {

            searchInput.value = '';
            categoryFilter.value = '';
            difficultyFilter.value = '';
            priceFilter.value = '';

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


        difficultyFilter.addEventListener(
            'change',
            filterCourses
        );


        priceFilter.addEventListener(
            'change',
            filterCourses
        );


        if (clearButton) {

            clearButton.addEventListener(
                'click',
                clearFilters
            );

        }


        if (emptyResetButton) {

            emptyResetButton.addEventListener(
                'click',
                clearFilters
            );

        }


        filterCourses();

    }


    document.addEventListener(
        'DOMContentLoaded',
        initializeMarketplacePage
    );


    document.addEventListener(
        'livewire:navigated',
        initializeMarketplacePage
    );
</script>


</x-layouts::app>