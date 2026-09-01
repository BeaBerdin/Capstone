<x-layouts::app :title="'Quiz Builder'">

@php
    $questionCount = $quiz ? $quiz->questions->count() : 0;
    $totalPoints = $quiz ? $quiz->questions->sum('points') : 0;
@endphp

<style>
    .pw-card {
        background: #ffffff;
        border: 1px solid #e7e9ef;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .035);
    }

    .pw-field {
        width: 100%;
        border: 1px solid #dfe2e9;
        border-radius: 12px;
        background: #ffffff;
        color: #172033;
        font-size: 14px;
        transition: all 160ms ease;
    }

    .pw-field:focus {
        outline: none !important;
        border-color: #8b5cf6 !important;
        box-shadow: 0 0 0 4px rgba(139, 92, 246, .10) !important;
    }

    .pw-question-card {
        transition:
            border-color 160ms ease,
            box-shadow 160ms ease;
    }

    .pw-question-card:hover {
        border-color: #ddd6fe;
        box-shadow: 0 12px 30px rgba(76, 29, 149, .05);
    }
</style>


<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-[1450px]">


            {{-- BREADCRUMB --}}
            <div class="mb-5 flex flex-wrap items-center gap-2 text-xs text-slate-400">

                <a
                    href="{{ route('teacher.my-courses') }}"
                    class="font-medium hover:text-violet-600"
                >
                    My Courses
                </a>

                <span>›</span>

                <a
                    href="{{ route('teacher.lessons', $lesson->course) }}"
                    class="font-medium hover:text-violet-600"
                >
                    {{ $lesson->course->title }}
                </a>

                <span>›</span>

                <span class="font-semibold text-slate-600">
                    Quiz Builder
                </span>

            </div>



            {{-- HEADER --}}
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">

                <div>

                    <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                        Assessment Builder
                    </p>

                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                        Quiz Builder
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                        Build the assessment for

                        <span class="font-semibold text-slate-700">
                            {{ $lesson->title }}
                        </span>.
                    </p>

                </div>


                <a
                    href="{{ route('teacher.lessons', $lesson->course) }}"
                    class="inline-flex h-11 items-center justify-center
                           self-start rounded-xl border border-slate-200
                           bg-white px-5 text-sm font-semibold
                           text-slate-600 transition hover:bg-slate-50"
                >
                    ← Back to Lessons
                </a>

            </div>



            {{-- SUCCESS --}}
            @if(session('success'))

                <div
                    class="mt-6 rounded-2xl border border-emerald-200
                           bg-emerald-50 px-5 py-4
                           text-sm font-medium text-emerald-700"
                >
                    ✓ {{ session('success') }}
                </div>

            @endif



            {{-- ERROR --}}
            @if(session('error'))

                <div
                    class="mt-6 rounded-2xl border border-red-200
                           bg-red-50 px-5 py-4
                           text-sm font-medium text-red-700"
                >
                    {{ session('error') }}
                </div>

            @endif



            {{-- VALIDATION ERRORS --}}
            @if($errors->any())

                <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-5">

                    <p class="text-sm font-bold text-red-800">
                        Please check the information below.
                    </p>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-xs text-red-700">

                        @foreach($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif



            {{-- STATS --}}
            <section class="mt-7 grid grid-cols-2 gap-4 lg:grid-cols-4">


                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <p class="text-xs font-semibold text-slate-500">
                        Questions
                    </p>

                    <p class="mt-2 text-3xl font-bold text-slate-950">
                        {{ $questionCount }}
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        Quiz items
                    </p>

                </div>



                <div class="rounded-2xl border border-violet-100 bg-white p-5 shadow-sm">

                    <p class="text-xs font-semibold text-slate-500">
                        Total Points
                    </p>

                    <p class="mt-2 text-3xl font-bold text-violet-600">
                        {{ $totalPoints }}
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        Available points
                    </p>

                </div>



                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">

                    <p class="text-xs font-semibold text-slate-500">
                        Passing Score
                    </p>

                    <p class="mt-2 text-3xl font-bold text-emerald-600">
                        {{ $quiz ? $quiz->passing_score : 75 }}%
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        Required to pass
                    </p>

                </div>



                <div class="rounded-2xl border border-orange-100 bg-white p-5 shadow-sm">

                    <p class="text-xs font-semibold text-slate-500">
                        Time Limit
                    </p>

                    <p class="mt-2 text-3xl font-bold text-orange-500">
                        {{
                            $quiz && $quiz->time_limit_minutes
                                ? $quiz->time_limit_minutes
                                : '—'
                        }}
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        {{
                            $quiz && $quiz->time_limit_minutes
                                ? 'Minutes'
                                : 'No time limit'
                        }}
                    </p>

                </div>

            </section>



            <div
                class="mt-6 grid grid-cols-1 gap-6
                       xl:grid-cols-[minmax(0,1fr)_350px]"
            >


                {{-- MAIN --}}
                <div class="space-y-6">


                    {{-- QUIZ SETTINGS --}}
                    <section class="pw-card p-5 sm:p-7">

                        <div class="border-b border-slate-100 pb-5">

                            <h2 class="text-lg font-bold text-slate-900">
                                Quiz settings
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Set the title, instructions,
                                passing score, and time limit.
                            </p>

                        </div>



                        <form
                            action="{{ route('teacher.quiz.save', $lesson) }}"
                            method="POST"
                            class="mt-6 space-y-6"
                        >

                            @csrf



                            {{-- TITLE --}}
                            <div>

                                <label
                                    for="quiz_title"
                                    class="text-sm font-semibold text-slate-800"
                                >
                                    Quiz title

                                    <span class="text-red-500">
                                        *
                                    </span>
                                </label>

                                <input
                                    type="text"
                                    id="quiz_title"
                                    name="title"
                                    required
                                    maxlength="255"
                                    value="{{ old('title', $quiz?->title ?? $lesson->title) }}"
                                    placeholder="e.g. Accounting Fundamentals Quiz"
                                    class="pw-field mt-2.5 h-12 px-4"
                                >

                            </div>



                            {{-- DESCRIPTION --}}
                            <div>

                                <label
                                    for="quiz_description"
                                    class="text-sm font-semibold text-slate-800"
                                >
                                    Quiz instructions
                                </label>

                                <textarea
                                    id="quiz_description"
                                    name="description"
                                    rows="5"
                                    placeholder="Give students instructions before starting this quiz..."
                                    class="pw-field mt-2.5 resize-none px-4 py-3 leading-6"
                                >{{ old('description', $quiz?->description ?? $lesson->content) }}</textarea>

                            </div>



                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">


                                {{-- PASSING SCORE --}}
                                <div>

                                    <label
                                        for="passing_score"
                                        class="text-sm font-semibold text-slate-800"
                                    >
                                        Passing score
                                    </label>

                                    <div class="relative mt-2.5">

                                        <input
                                            type="number"
                                            id="passing_score"
                                            name="passing_score"
                                            min="1"
                                            max="100"
                                            required
                                            value="{{ old('passing_score', $quiz?->passing_score ?? 75) }}"
                                            class="pw-field h-12 px-4 pr-12"
                                        >

                                        <span
                                            class="pointer-events-none absolute right-4
                                                   top-1/2 -translate-y-1/2
                                                   text-sm text-slate-400"
                                        >
                                            %
                                        </span>

                                    </div>

                                </div>



                                {{-- TIME LIMIT --}}
                                <div>

                                    <label
                                        for="time_limit_minutes"
                                        class="text-sm font-semibold text-slate-800"
                                    >
                                        Time limit
                                    </label>

                                    <div class="relative mt-2.5">

                                        <input
                                            type="number"
                                            id="time_limit_minutes"
                                            name="time_limit_minutes"
                                            min="1"
                                            value="{{ old('time_limit_minutes', $quiz?->time_limit_minutes) }}"
                                            placeholder="No limit"
                                            class="pw-field h-12 px-4 pr-20"
                                        >

                                        <span
                                            class="pointer-events-none absolute right-4
                                                   top-1/2 -translate-y-1/2
                                                   text-xs text-slate-400"
                                        >
                                            minutes
                                        </span>

                                    </div>

                                </div>

                            </div>



                            {{-- PUBLISH --}}
                            <label
                                class="flex cursor-pointer items-start
                                       justify-between gap-5 rounded-xl
                                       border border-slate-200
                                       bg-slate-50 p-4"
                            >

                                <div>

                                    <p class="text-sm font-bold text-slate-800">
                                        Publish quiz
                                    </p>

                                    <p class="mt-1 text-xs leading-5 text-slate-500">
                                        Make this assessment available
                                        when the course is published.
                                    </p>

                                </div>


                                <div class="relative shrink-0">

                                    <input
                                        type="checkbox"
                                        name="is_published"
                                        value="1"
                                        class="peer sr-only"
                                        @checked(
                                            old(
                                                'is_published',
                                                $quiz?->is_published ?? false
                                            )
                                        )
                                    >

                                    <div
                                        class="h-6 w-11 rounded-full
                                               bg-slate-300 transition

                                               after:absolute
                                               after:left-[2px]
                                               after:top-[2px]
                                               after:h-5
                                               after:w-5
                                               after:rounded-full
                                               after:bg-white
                                               after:transition-all
                                               after:content-['']

                                               peer-checked:bg-violet-600

                                               peer-checked:after:translate-x-full"
                                    ></div>

                                </div>

                            </label>



                            <div class="flex justify-end">

                                <button
                                    type="submit"
                                    class="inline-flex h-11 items-center
                                           justify-center rounded-xl
                                           bg-violet-600 px-5
                                           text-sm font-semibold text-white
                                           transition hover:bg-violet-700"
                                >
                                    Save Quiz Settings
                                </button>

                            </div>

                        </form>

                    </section>



                    {{-- QUESTIONS --}}
                    @if($quiz)

                        <section>

                            <div class="mb-4">

                                <h2 class="text-lg font-bold text-slate-900">
                                    Questions
                                </h2>

                                <p class="mt-1 text-sm text-slate-500">
                                    Manage the questions,
                                    answer choices, correct answers,
                                    and points.
                                </p>

                            </div>



                            <div class="space-y-4">


                                @forelse($quiz->questions as $question)

                                    <article
                                        class="pw-question-card
                                               rounded-2xl border
                                               border-slate-200
                                               bg-white p-5
                                               shadow-sm sm:p-6"
                                    >


                                        <form
                                            action="{{ route('teacher.quiz.question.update', [$quiz, $question]) }}"
                                            method="POST"
                                        >

                                            @csrf
                                            @method('PUT')



                                            {{-- QUESTION NUMBER --}}
                                            <div class="flex items-center gap-3">

                                                <div
                                                    class="flex h-9 w-9
                                                           items-center justify-center
                                                           rounded-full bg-violet-50
                                                           text-sm font-bold
                                                           text-violet-600"
                                                >
                                                    {{ $loop->iteration }}
                                                </div>


                                                <div>

                                                    <p
                                                        class="text-xs font-bold uppercase
                                                               tracking-wide text-violet-500"
                                                    >
                                                        Question
                                                        {{ $loop->iteration }}
                                                    </p>

                                                    <p class="mt-0.5 text-[11px] text-slate-400">
                                                        {{ $question->points }}
                                                        {{
                                                            \Illuminate\Support\Str::plural(
                                                                'point',
                                                                $question->points
                                                            )
                                                        }}
                                                    </p>

                                                </div>

                                            </div>



                                            {{-- QUESTION --}}
                                            <div class="mt-5">

                                                <label class="text-sm font-semibold text-slate-700">
                                                    Question
                                                </label>

                                                <textarea
                                                    name="question"
                                                    rows="3"
                                                    required
                                                    class="pw-field mt-2
                                                           resize-none
                                                           px-4 py-3"
                                                >{{ $question->question }}</textarea>

                                            </div>



                                            {{-- OPTIONS --}}
                                            <div
                                                class="mt-5 grid grid-cols-1
                                                       gap-4 md:grid-cols-2"
                                            >

                                                @foreach([
                                                    'A' => 'option_a',
                                                    'B' => 'option_b',
                                                    'C' => 'option_c',
                                                    'D' => 'option_d',
                                                ] as $letter => $field)

                                                    <div>

                                                        <label
                                                            class="text-xs font-bold
                                                                   text-slate-500"
                                                        >
                                                            Option {{ $letter }}
                                                        </label>


                                                        <div class="relative mt-1.5">

                                                            <span
                                                                class="absolute left-3
                                                                       top-1/2
                                                                       flex h-6 w-6
                                                                       -translate-y-1/2
                                                                       items-center
                                                                       justify-center
                                                                       rounded-full
                                                                       bg-slate-100
                                                                       text-[10px]
                                                                       font-bold
                                                                       text-slate-600"
                                                            >
                                                                {{ $letter }}
                                                            </span>


                                                            <input
                                                                type="text"
                                                                name="{{ $field }}"
                                                                value="{{ $question->{$field} }}"
                                                                {{ in_array($letter, ['A', 'B']) ? 'required' : '' }}
                                                                class="pw-field h-11 pl-11 pr-3"
                                                            >

                                                        </div>

                                                    </div>

                                                @endforeach

                                            </div>



                                            {{-- ANSWER AND POINTS --}}
                                            <div
                                                class="mt-5 grid
                                                       grid-cols-1 gap-4
                                                       sm:grid-cols-2"
                                            >

                                                <div>

                                                    <label class="text-sm font-semibold text-slate-700">
                                                        Correct answer
                                                    </label>

                                                    <select
                                                        name="correct_answer"
                                                        required
                                                        class="pw-field mt-2 h-11 px-4"
                                                    >

                                                        @foreach(['A', 'B', 'C', 'D'] as $answer)

                                                            <option
                                                                value="{{ $answer }}"
                                                                @selected(
                                                                    $question->correct_answer
                                                                    === $answer
                                                                )
                                                            >
                                                                Option {{ $answer }}
                                                            </option>

                                                        @endforeach

                                                    </select>

                                                </div>



                                                <div>

                                                    <label class="text-sm font-semibold text-slate-700">
                                                        Points
                                                    </label>

                                                    <input
                                                        type="number"
                                                        name="points"
                                                        min="1"
                                                        max="100"
                                                        required
                                                        value="{{ $question->points }}"
                                                        class="pw-field mt-2 h-11 px-4"
                                                    >

                                                </div>

                                            </div>



                                            <div class="mt-5 flex justify-end">

                                                <button
                                                    type="submit"
                                                    class="inline-flex h-10
                                                           items-center justify-center
                                                           rounded-xl bg-violet-600
                                                           px-4 text-xs font-semibold
                                                           text-white
                                                           hover:bg-violet-700"
                                                >
                                                    Save Question
                                                </button>

                                            </div>

                                        </form>



                                        {{-- DELETE --}}
                                        <form
                                            action="{{ route('teacher.quiz.question.delete', [$quiz, $question]) }}"
                                            method="POST"
                                            class="mt-3"
                                            onsubmit="return confirm('Delete this question?');"
                                        >

                                            @csrf
                                            @method('DELETE')


                                            <div class="flex justify-end">

                                                <button
                                                    type="submit"
                                                    class="text-xs font-semibold
                                                           text-red-500
                                                           hover:text-red-700"
                                                >
                                                    Delete Question
                                                </button>

                                            </div>

                                        </form>

                                    </article>


                                @empty

                                    <div
                                        class="rounded-2xl
                                               border-2 border-dashed
                                               border-slate-200
                                               bg-white px-6 py-10
                                               text-center"
                                    >

                                        <p class="text-sm font-semibold text-slate-700">
                                            No questions yet.
                                        </p>

                                        <p class="mt-1 text-xs text-slate-400">
                                            Add your first question below.
                                        </p>

                                    </div>

                                @endforelse

                            </div>

                        </section>



                        {{-- ADD QUESTION --}}
                        <section class="pw-card p-5 sm:p-7">

                            <div class="flex items-start gap-4">

                                <div
                                    class="flex h-11 w-11
                                           shrink-0 items-center
                                           justify-center rounded-xl
                                           bg-violet-50
                                           text-xl font-bold
                                           text-violet-600"
                                >
                                    +
                                </div>


                                <div>

                                    <h2 class="text-lg font-bold text-slate-900">
                                        Add question
                                    </h2>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Add a multiple-choice question
                                        to this quiz.
                                    </p>

                                </div>

                            </div>



                            <form
                                action="{{ route('teacher.quiz.question.store', $quiz) }}"
                                method="POST"
                                class="mt-6"
                            >

                                @csrf



                                {{-- NEW QUESTION --}}
                                <div>

                                    <label class="text-sm font-semibold text-slate-800">
                                        Question

                                        <span class="text-red-500">
                                            *
                                        </span>
                                    </label>

                                    <textarea
                                        name="question"
                                        rows="3"
                                        required
                                        placeholder="Enter your question..."
                                        class="pw-field mt-2.5
                                               resize-none
                                               px-4 py-3"
                                    ></textarea>

                                </div>



                                {{-- NEW OPTIONS --}}
                                <div
                                    class="mt-5 grid grid-cols-1
                                           gap-4 md:grid-cols-2"
                                >

                                    @foreach([
                                        'A' => 'option_a',
                                        'B' => 'option_b',
                                        'C' => 'option_c',
                                        'D' => 'option_d',
                                    ] as $letter => $field)

                                        <div>

                                            <label
                                                class="text-xs font-bold
                                                       text-slate-500"
                                            >
                                                Option {{ $letter }}

                                                @if(in_array($letter, ['A', 'B']))

                                                    <span class="text-red-500">
                                                        *
                                                    </span>

                                                @endif

                                            </label>


                                            <div class="relative mt-1.5">

                                                <span
                                                    class="absolute left-3
                                                           top-1/2
                                                           flex h-6 w-6
                                                           -translate-y-1/2
                                                           items-center
                                                           justify-center
                                                           rounded-full
                                                           bg-slate-100
                                                           text-[10px]
                                                           font-bold
                                                           text-slate-600"
                                                >
                                                    {{ $letter }}
                                                </span>


                                                <input
                                                    type="text"
                                                    name="{{ $field }}"
                                                    {{ in_array($letter, ['A', 'B']) ? 'required' : '' }}
                                                    placeholder="Enter option {{ $letter }}"
                                                    class="pw-field h-11 pl-11 pr-3"
                                                >

                                            </div>

                                        </div>

                                    @endforeach

                                </div>



                                {{-- CORRECT ANSWER AND POINTS --}}
                                <div
                                    class="mt-5 grid grid-cols-1
                                           gap-5 sm:grid-cols-2"
                                >

                                    <div>

                                        <label class="text-sm font-semibold text-slate-800">
                                            Correct answer
                                        </label>

                                        <select
                                            name="correct_answer"
                                            required
                                            class="pw-field mt-2.5 h-11 px-4"
                                        >
                                            <option value="A">
                                                Option A
                                            </option>

                                            <option value="B">
                                                Option B
                                            </option>

                                            <option value="C">
                                                Option C
                                            </option>

                                            <option value="D">
                                                Option D
                                            </option>
                                        </select>

                                    </div>



                                    <div>

                                        <label class="text-sm font-semibold text-slate-800">
                                            Points
                                        </label>

                                        <input
                                            type="number"
                                            name="points"
                                            min="1"
                                            max="100"
                                            value="1"
                                            required
                                            class="pw-field mt-2.5 h-11 px-4"
                                        >

                                    </div>

                                </div>



                                <div class="mt-6 flex justify-end">

                                    <button
                                        type="submit"
                                        class="inline-flex h-11
                                               items-center justify-center
                                               rounded-xl bg-violet-600
                                               px-5 text-sm font-semibold
                                               text-white
                                               hover:bg-violet-700"
                                    >
                                        + Add Question
                                    </button>

                                </div>

                            </form>

                        </section>


                    @else


                        {{-- QUIZ DOESN'T EXIST YET --}}
                        <section
                            class="rounded-2xl border-2
                                   border-dashed border-violet-200
                                   bg-violet-50/30
                                   px-6 py-12 text-center"
                        >

                            <div
                                class="mx-auto flex h-14 w-14
                                       items-center justify-center
                                       rounded-2xl bg-white
                                       text-2xl font-black
                                       text-violet-600 shadow-sm"
                            >
                                ?
                            </div>

                            <h3 class="mt-4 text-base font-bold text-slate-900">
                                Save the quiz settings first
                            </h3>

                            <p
                                class="mx-auto mt-2 max-w-md
                                       text-sm leading-6 text-slate-500"
                            >
                                After saving the quiz settings,
                                the question builder will appear here.
                            </p>

                        </section>

                    @endif

                </div>



                {{-- RIGHT SIDEBAR --}}
                <aside>

                    <div class="sticky top-7 space-y-5">


                        {{-- COURSE --}}
                        <section class="pw-card p-5">

                            <p
                                class="text-[10px] font-bold uppercase
                                       tracking-[.12em] text-violet-500"
                            >
                                Quiz Lesson
                            </p>

                            <h3 class="mt-2 text-base font-bold text-slate-900">
                                {{ $lesson->title }}
                            </h3>

                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                {{ $lesson->course->title }}
                            </p>


                            <div class="mt-4 border-t border-slate-100 pt-4">

                                <div class="flex items-center justify-between text-xs">

                                    <span class="text-slate-400">
                                        Lesson order
                                    </span>

                                    <span class="font-semibold text-slate-700">
                                        {{ $lesson->lesson_order }}
                                    </span>

                                </div>

                            </div>

                        </section>



                        {{-- CHECKLIST --}}
                        <section class="pw-card p-5">

                            <h3 class="text-sm font-bold text-slate-900">
                                Quiz checklist
                            </h3>


                            <div class="mt-4 space-y-4">


                                <div class="flex items-center gap-3">

                                    <div
                                        class="flex h-8 w-8
                                               items-center justify-center
                                               rounded-full
                                               {{
                                                   $quiz
                                                       ? 'bg-emerald-50 text-emerald-600'
                                                       : 'bg-slate-100 text-slate-400'
                                               }}
                                               text-xs font-bold"
                                    >
                                        {{ $quiz ? '✓' : '1' }}
                                    </div>

                                    <span class="text-xs font-medium text-slate-600">
                                        Save quiz settings
                                    </span>

                                </div>



                                <div class="flex items-center gap-3">

                                    <div
                                        class="flex h-8 w-8
                                               items-center justify-center
                                               rounded-full
                                               {{
                                                   $questionCount > 0
                                                       ? 'bg-emerald-50 text-emerald-600'
                                                       : 'bg-slate-100 text-slate-400'
                                               }}
                                               text-xs font-bold"
                                    >
                                        {{
                                            $questionCount > 0
                                                ? '✓'
                                                : '2'
                                        }}
                                    </div>

                                    <span class="text-xs font-medium text-slate-600">
                                        Add quiz questions
                                    </span>

                                </div>



                                <div class="flex items-center gap-3">

                                    <div
                                        class="flex h-8 w-8
                                               items-center justify-center
                                               rounded-full
                                               {{
                                                   $quiz?->is_published
                                                       ? 'bg-emerald-50 text-emerald-600'
                                                       : 'bg-slate-100 text-slate-400'
                                               }}
                                               text-xs font-bold"
                                    >
                                        {{
                                            $quiz?->is_published
                                                ? '✓'
                                                : '3'
                                        }}
                                    </div>

                                    <span class="text-xs font-medium text-slate-600">
                                        Publish quiz
                                    </span>

                                </div>

                            </div>

                        </section>



                        {{-- SUMMARY --}}
                        @if($quiz && $questionCount > 0)

                            <section
                                class="rounded-2xl
                                       border border-violet-100
                                       bg-violet-50 p-5"
                            >

                                <p class="text-sm font-bold text-violet-900">
                                    Assessment summary
                                </p>

                                <p class="mt-2 text-xs leading-5 text-violet-700">
                                    This quiz contains

                                    {{ $questionCount }}

                                    {{
                                        \Illuminate\Support\Str::plural(
                                            'question',
                                            $questionCount
                                        )
                                    }}

                                    with {{ $totalPoints }}

                                    {{
                                        \Illuminate\Support\Str::plural(
                                            'point',
                                            $totalPoints
                                        )
                                    }}.
                                </p>


                                <div class="mt-4 rounded-xl bg-white p-3">

                                    <div class="flex justify-between text-xs">

                                        <span class="text-slate-400">
                                            Passing
                                        </span>

                                        <span class="font-bold text-slate-700">
                                            {{ $quiz->passing_score }}%
                                        </span>

                                    </div>


                                    <div class="mt-2 flex justify-between text-xs">

                                        <span class="text-slate-400">
                                            Status
                                        </span>

                                        <span
                                            class="font-bold
                                                   {{
                                                       $quiz->is_published
                                                           ? 'text-emerald-600'
                                                           : 'text-slate-500'
                                                   }}"
                                        >
                                            {{
                                                $quiz->is_published
                                                    ? 'Published'
                                                    : 'Draft'
                                            }}
                                        </span>

                                    </div>

                                </div>

                            </section>

                        @endif

                    </div>

                </aside>

            </div>

        </div>

    </main>

</div>

</x-layouts::app>