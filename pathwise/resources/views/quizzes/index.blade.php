<x-layouts::app :title="__('Quizzes')">

    <div class="space-y-8">

        {{-- =========================================================
             PAGE HEADER
        ========================================================== --}}

        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">

            <div>
                <p class="text-sm font-medium text-purple-600 dark:text-purple-400">
                    Assessment Management
                </p>

                <h1 class="mt-1 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                    Quizzes
                </h1>

                <p class="mt-2 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                    Manage course assessments, questions, and passing scores.
                </p>
            </div>

            <a
                href="{{ route('quizzes.create') }}"
                class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-700"
            >
                <svg
                    class="mr-2 h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 4v16m8-8H4"
                    />
                </svg>

                Add Quiz
            </a>

        </div>


        {{-- =========================================================
             STATISTICS
        ========================================================== --}}

        @php
            $totalQuizzes = $quizzes->count();

            $publishedQuizzes = $quizzes
                ->where('is_published', true)
                ->count();

            $totalQuestions = $quizzes->sum(
                fn ($quiz) => $quiz->questions->count()
            );

            $averagePassingScore = $totalQuizzes > 0
                ? round($quizzes->avg('passing_score'), 2)
                : 0;
        @endphp


        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

            {{-- Total Quizzes --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Total Quizzes
                        </p>

                        <h2
                            id="totalQuizCount"
                            class="mt-2 text-3xl font-bold text-gray-900 dark:text-white"
                        >
                            {{ $totalQuizzes }}
                        </h2>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400">

                        <svg
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M9 5h6m-7 4h8m-8 4h5m-8 5h10a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"
                            />
                        </svg>

                    </div>

                </div>

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    All assessments created
                </p>

            </div>


            {{-- Published --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Published
                        </p>

                        <h2
                            id="publishedQuizCount"
                            class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400"
                        >
                            {{ $publishedQuizzes }}
                        </h2>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-green-100 text-green-600 dark:bg-green-500/10 dark:text-green-400">

                        <svg
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>

                    </div>

                </div>

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Live assessments
                </p>

            </div>


            {{-- Question Bank --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Question Bank
                        </p>

                        <h2 class="mt-2 text-3xl font-bold text-purple-600 dark:text-purple-400">
                            {{ $totalQuestions }}
                        </h2>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400">

                        <svg
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M8 9h8m-8 4h5m7-1a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"
                            />
                        </svg>

                    </div>

                </div>

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Total questions
                </p>

            </div>


            {{-- Average Passing Score --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Avg. Passing Score
                        </p>

                        <h2 class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">
                            {{ $averagePassingScore }}%
                        </h2>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">

                        <svg
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                            />
                        </svg>

                    </div>

                </div>

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Across all quizzes
                </p>

            </div>

        </div>


        {{-- =========================================================
             SUCCESS MESSAGE
        ========================================================== --}}

        @if(session('success'))

            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-300">

                {{ session('success') }}

            </div>

        @endif


        {{-- =========================================================
             SEARCH AND FILTER
        ========================================================== --}}

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">

            <div class="flex flex-col gap-3 lg:flex-row">

                {{-- Search --}}
                <div class="relative flex-1">

                    <svg
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
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
                        id="quizSearch"
                        type="text"
                        placeholder="Search quizzes or courses..."
                        autocomplete="off"
                        class="w-full rounded-lg border-gray-300 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >

                </div>


                {{-- Course Filter --}}
                <select
                    id="courseFilter"
                    class="rounded-lg border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-700 focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >

                    <option value="all">
                        All Courses
                    </option>

                    @foreach($quizzes->pluck('course')->filter()->unique('id') as $course)

                        <option value="{{ $course->id }}">
                            {{ $course->title }}
                        </option>

                    @endforeach

                </select>


                {{-- Status Filter --}}
                <select
                    id="statusFilter"
                    class="rounded-lg border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-700 focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >

                    <option value="all">
                        All Status
                    </option>

                    <option value="published">
                        Published
                    </option>

                    <option value="draft">
                        Draft
                    </option>

                </select>

            </div>

        </div>


        {{-- =========================================================
             QUIZ COUNT
        ========================================================== --}}

        <div class="flex items-center justify-between">

            <div>

                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    All Quizzes
                </h2>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">

                    <span id="quizCount">
                        {{ $totalQuizzes }}
                    </span>

                    <span id="quizCountText">
                        {{ Str::plural('quiz', $totalQuizzes) }}
                    </span>

                    available

                </p>

            </div>

        </div>


        {{-- =========================================================
             QUIZ GRID
        ========================================================== --}}

        <div
            id="quizGrid"
            class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3"
        >

            @forelse($quizzes as $quiz)

                @php
                    $questionCount = $quiz->questions->count();

                    $courseTitle = $quiz->course->title ?? 'No Course';

                    $searchText = strtolower(
                        $quiz->title . ' ' .
                        $courseTitle . ' ' .
                        ($quiz->description ?? '')
                    );
                @endphp


                {{-- QUIZ CARD --}}
                <div
                    class="quiz-card group overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg dark:border-gray-700 dark:bg-gray-900"
                    data-search="{{ $searchText }}"
                    data-course="{{ $quiz->course_id ?? '' }}"
                    data-status="{{ $quiz->is_published ? 'published' : 'draft' }}"
                >

                    {{-- TOP --}}
                    <div class="relative h-40 overflow-hidden bg-linear-to-br from-purple-700 via-purple-600 to-indigo-700">

                        <div class="absolute inset-0 flex items-center justify-center">

                            <div class="text-center text-white">

                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 backdrop-blur">

                                    <svg
                                        class="h-8 w-8"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.5"
                                            d="M9 5h6m-7 4h8m-8 4h5m-8 5h10a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"
                                        />
                                    </svg>

                                </div>

                                <p class="mt-2 text-sm font-medium text-white/90">
                                    PathWise Assessment
                                </p>

                            </div>

                        </div>


                        {{-- STATUS --}}
                        <div class="absolute right-3 top-3">

                            @if($quiz->is_published)

                                <span class="rounded-full bg-green-500/20 px-3 py-1 text-xs font-semibold text-green-200">
                                    Published
                                </span>

                            @else

                                <span class="rounded-full bg-gray-500/30 px-3 py-1 text-xs font-semibold text-gray-200">
                                    Draft
                                </span>

                            @endif

                        </div>

                    </div>


                    {{-- DETAILS --}}
                    <div class="p-5">

                        {{-- COURSE --}}
                        <p class="text-xs font-semibold uppercase tracking-wide text-purple-600 dark:text-purple-400">

                            {{ $courseTitle }}

                        </p>


                        {{-- TITLE --}}
                        <h3 class="mt-2 line-clamp-2 text-lg font-bold leading-snug text-gray-900 dark:text-white">

                            {{ $quiz->title }}

                        </h3>


                        {{-- DESCRIPTION --}}
                        @if($quiz->description)

                            <p class="mt-2 line-clamp-2 text-sm text-gray-500 dark:text-gray-400">

                                {{ $quiz->description }}

                            </p>

                        @else

                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Final course assessment.
                            </p>

                        @endif


                        {{-- INFORMATION --}}
                        <div class="mt-4 flex flex-wrap gap-2">

                            <span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700 dark:bg-purple-500/10 dark:text-purple-400">

                                {{ $questionCount }}
                                {{ Str::plural('Question', $questionCount) }}

                            </span>


                            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">

                                {{ $quiz->passing_score }}% Passing

                            </span>

                        </div>


                        {{-- PASSING SCORE --}}
                        <div class="mt-4">

                            <div class="mb-1 flex items-center justify-between">

                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                    Passing Score
                                </span>

                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $quiz->passing_score }}%
                                </span>

                            </div>

                            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">

                                <div
                                    class="h-full rounded-full bg-green-500"
                                    style="width: {{ min($quiz->passing_score, 100) }}%"
                                ></div>

                            </div>

                        </div>


                        {{-- DIVIDER --}}
                        <div class="my-5 border-t border-gray-100 dark:border-gray-800"></div>


                        {{-- ACTIONS --}}
                        <div class="flex gap-2">

                            <a
                                href="{{ route('quizzes.edit', $quiz) }}"
                                class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-center text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                            >
                                Edit
                            </a>


                            <form
                                action="{{ route('quizzes.destroy', $quiz) }}"
                                method="POST"
                                class="flex-1"
                            >

                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    onclick="return confirm('Delete this quiz?')"
                                    class="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50 dark:border-red-900/50 dark:text-red-400 dark:hover:bg-red-950/30"
                                >
                                    Delete
                                </button>

                            </form>

                        </div>

                    </div>

                </div>

            @empty

                {{-- EMPTY STATE --}}
                <div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center dark:border-gray-700 dark:bg-gray-900">

                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-purple-100 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400">

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
                                d="M9 5h6m-7 4h8m-8 4h5m-8 5h10a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"
                            />
                        </svg>

                    </div>

                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                        No quizzes yet
                    </h3>

                    <p class="mx-auto mt-1 max-w-md text-sm text-gray-500 dark:text-gray-400">
                        Create your first course assessment to start tracking learner performance.
                    </p>

                    <a
                        href="{{ route('quizzes.create') }}"
                        class="mt-5 inline-flex rounded-lg bg-purple-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-purple-700"
                    >
                        Add Quiz
                    </a>

                </div>

            @endforelse


            {{-- NO SEARCH RESULTS --}}
            <div
                id="noResults"
                class="col-span-full hidden rounded-xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center dark:border-gray-700 dark:bg-gray-900"
            >

                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">

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

                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                    No quizzes found
                </h3>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Try changing your search or filters.
                </p>

            </div>

        </div>

    </div>


    {{-- =========================================================
         AUTOMATIC SEARCH + FILTER SCRIPT
    ========================================================== --}}

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const searchInput = document.getElementById('quizSearch');
            const courseFilter = document.getElementById('courseFilter');
            const statusFilter = document.getElementById('statusFilter');

            const cards = document.querySelectorAll('.quiz-card');

            const noResults = document.getElementById('noResults');

            const quizCount = document.getElementById('quizCount');
            const quizCountText = document.getElementById('quizCountText');


            function filterQuizzes() {

                const searchValue = searchInput.value
                    .toLowerCase()
                    .trim();

                const courseValue = courseFilter.value;

                const statusValue = statusFilter.value;

                let visibleCount = 0;


                cards.forEach(function (card) {

                    const searchText = card.dataset.search || '';

                    const course = card.dataset.course || '';

                    const status = card.dataset.status || '';


                    const matchesSearch =
                        searchText.includes(searchValue);

                    const matchesCourse =
                        courseValue === 'all' ||
                        course === courseValue;

                    const matchesStatus =
                        statusValue === 'all' ||
                        status === statusValue;


                    if (
                        matchesSearch &&
                        matchesCourse &&
                        matchesStatus
                    ) {

                        card.classList.remove('hidden');

                        visibleCount++;

                    } else {

                        card.classList.add('hidden');

                    }

                });


                quizCount.textContent = visibleCount;

                quizCountText.textContent =
                    visibleCount === 1
                        ? 'quiz'
                        : 'quizzes';


                if (visibleCount === 0) {

                    noResults.classList.remove('hidden');

                } else {

                    noResults.classList.add('hidden');

                }

            }


            {{-- AUTOMATIC SEARCH WHILE TYPING --}}
            searchInput.addEventListener(
                'input',
                filterQuizzes
            );


            {{-- AUTOMATIC COURSE FILTER --}}
            courseFilter.addEventListener(
                'change',
                filterQuizzes
            );


            {{-- AUTOMATIC STATUS FILTER --}}
            statusFilter.addEventListener(
                'change',
                filterQuizzes
            );


            {{-- INITIAL FILTER --}}
            filterQuizzes();

        });
    </script>

</x-layouts::app>