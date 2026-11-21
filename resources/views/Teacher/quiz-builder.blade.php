<x-layouts::app :title="'Quiz Builder'">
@include('partials.soft3d')

@php
    $questionCount = $quiz ? $quiz->questions->count() : 0;
    $totalPoints = $quiz ? $quiz->questions->sum('points') : 0;

    $quizCanBeModified =
        $quizCanBeModified
        ?? in_array(
            strtolower(
                trim(
                    (string) ($lesson->course->status ?? 'draft')
                )
            ),
            ['draft', 'rejected'],
            true
        );
@endphp

<style>
    /* Cards come from .sad-card; fields match .sad-field in partials.soft3d. */
    .pw-field {
        width: 100%;
        border: 1px solid #e2e8f0;
        border-radius: .75rem;
        background: #ffffff;
        color: #0f172a;
        font-size: .875rem;
        transition: border-color .15s, box-shadow .15s;
    }

    .pw-field::placeholder {
        color: #94a3b8;
    }

    .pw-field:focus {
        outline: none !important;
        border-color: #c4b5fd !important;
        box-shadow: 0 0 0 4px #ede9fe !important;
    }

    .pw-field:disabled,
    .pw-field[readonly] {
        background: #f8fafc;
        color: #64748b;
        cursor: not-allowed;
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


<div class="sad-page min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-[1450px]">


            {{-- BREADCRUMB --}}
            <div class="mb-5 flex flex-wrap items-center gap-2 text-xs text-slate-500">

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


            {{-- LOCKED COURSE NOTICE --}}
            @if(!$quizCanBeModified)

                <div
                    class="mt-6 flex flex-col gap-3 rounded-2xl
                           border border-slate-200 bg-slate-100
                           px-5 py-4 sm:flex-row
                           sm:items-center sm:justify-between"
                >

                    <div>

                        <p class="text-sm font-bold text-slate-800">
                            🔒 Quiz content is locked
                        </p>

                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            This course is currently
                            <span class="font-semibold">
                                {{ ucfirst($lesson->course->status) }}
                            </span>.
                            Quiz settings and questions can only be changed
                            while the course is Draft or Rejected.
                        </p>

                    </div>


                    <span
                        class="inline-flex self-start rounded-full
                               bg-white px-3 py-1.5 text-xs
                               font-semibold text-slate-500
                               shadow-sm"
                    >
                        Read only
                    </span>

                </div>

            @endif



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



            <section aria-label="Quiz saving and publication" class="sad-card sticky top-[68px] z-20 mt-6 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:px-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $quiz?->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                            {{ $quiz?->is_published ? 'Published' : 'Draft' }}
                        </span>
                        @if ($quizCanBeModified)
                            <span role="status" aria-live="polite" class="font-medium text-slate-700" data-quiz-save-status>
                                @if (session('quiz_saved'))
                                    All changes saved
                                @elseif ($errors->any() && session()->hasOldInput('title'))
                                    Unsaved changes
                                @else
                                    {{ $quiz ? 'No unsaved changes' : 'Ready to save' }}
                                @endif
                            </span>
                        @endif
                    </div>
                    @if ($quizCanBeModified)
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="submit" form="quiz-edit-form" name="intent" value="save"
                                    class="sad-btn-primary min-h-11 rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">
                                Save Changes
                            </button>
                            @if ($quiz?->is_published)
                                <button type="submit" form="quiz-edit-form" name="intent" value="unpublish"
                                        class="min-h-11 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 disabled:opacity-60">
                                    Return to Draft
                                </button>
                            @else
                                <button type="submit" form="quiz-edit-form" name="intent" value="publish"
                                        class="min-h-11 rounded-xl border border-violet-200 bg-violet-50 px-4 py-2 text-sm font-semibold text-violet-800 disabled:opacity-60">
                                    Publish Quiz
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </section>

            {{-- STATS --}}
            <section class="mt-7 grid grid-cols-2 gap-4 lg:grid-cols-4">


                <div class="sad-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <p class="text-xs font-semibold text-slate-500">
                        Questions
                    </p>

                    <p class="mt-2 text-3xl font-bold text-slate-950">
                        {{ $questionCount }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        Quiz items
                    </p>

                </div>



                <div class="sad-card rounded-2xl border border-violet-100 bg-white p-5 shadow-sm">

                    <p class="text-xs font-semibold text-slate-500">
                        Total Points
                    </p>

                    <p class="mt-2 text-3xl font-bold text-violet-600">
                        {{ $totalPoints }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        Available points
                    </p>

                </div>



                <div class="sad-card rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">

                    <p class="text-xs font-semibold text-slate-500">
                        Passing Score
                    </p>

                    <p class="mt-2 text-3xl font-bold text-emerald-600">
                        {{ $quiz ? $quiz->passing_score : 75 }}%
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        Required to pass
                    </p>

                </div>



                <div class="sad-card rounded-2xl border border-orange-100 bg-white p-5 shadow-sm">

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

                    <p class="mt-1 text-xs text-slate-500">
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
                    <section class="sad-card pw-card p-5 sm:p-7">

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
                            id="quiz-edit-form"
                            action="{{ route('teacher.quiz.save', $lesson) }}"
                            method="POST"
                            class="mt-6 space-y-6"
                            @if ($quizCanBeModified) data-quiz-edit-form @endif
                            @if ($errors->any() && session()->hasOldInput('title')) data-quiz-has-unsaved @endif
                        >
                            @csrf
                            <input type="hidden" name="revision" value="{{ session()->hasOldInput('title') ? old('revision', $quizRevision) : $quizRevision }}">



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
                                    @readonly(!$quizCanBeModified)
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
                                    @readonly(!$quizCanBeModified)
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
                                            @readonly(!$quizCanBeModified)
                                        >

                                        <span
                                            class="pointer-events-none absolute right-4
                                                   top-1/2 -translate-y-1/2
                                                   text-sm text-slate-500"
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
                                            @readonly(!$quizCanBeModified)
                                        >

                                        <span
                                            class="pointer-events-none absolute right-4
                                                   top-1/2 -translate-y-1/2
                                                   text-xs text-slate-500"
                                        >
                                            minutes
                                        </span>

                                    </div>

                                </div>

                            </div>



                            @if ($quizCanBeModified)
                                <p class="rounded-xl border border-violet-100 bg-violet-50 p-4 text-sm text-violet-900">
                                    Save Changes keeps the current status. Publish Quiz saves this form and makes the quiz available when its lesson and course are published.
                                </p>
                            @endif
                        </form>

                    </section>



                    {{-- AI QUIZ GENERATOR --}}
                    @if($quiz)

                        <section class="sad-card pw-card overflow-hidden">

                            <div
                                class="border-b border-violet-100
                                       bg-gradient-to-r from-violet-50
                                       via-white to-fuchsia-50
                                       px-5 py-5 sm:px-7"
                            >

                                <div
                                    class="flex flex-col gap-4
                                           sm:flex-row sm:items-start
                                           sm:justify-between"
                                >

                                    <div>

                                        <div class="flex items-center gap-2">

                                            <span
                                                class="inline-flex h-9 w-9
                                                       items-center justify-center
                                                       rounded-xl bg-violet-600
                                                       text-base font-bold
                                                       text-white shadow-sm"
                                            >
                                                ✦
                                            </span>

                                            <div>

                                                <p
                                                    class="text-xs font-bold
                                                           uppercase tracking-[.13em]
                                                           text-violet-500"
                                                >
                                                    Gemini AI
                                                </p>

                                                <h2
                                                    class="mt-0.5 text-lg
                                                           font-bold text-slate-900"
                                                >
                                                    Generate quiz questions
                                                </h2>

                                            </div>

                                        </div>


                                        <p
                                            class="mt-3 max-w-2xl
                                                   text-sm leading-6
                                                   text-slate-500"
                                        >
                                            PathWise asks Gemini to create
                                            multiple-choice questions using only
                                            the published Reading lessons from
                                            this course. Generated questions are saved to the quiz for review.
                                            If new questions are added, a published quiz returns to draft.
                                        </p>

                                    </div>


                                    <span
                                        class="inline-flex self-start
                                               rounded-full border
                                               border-violet-100 bg-white
                                               px-3 py-1.5 text-xs
                                               font-semibold text-violet-600"
                                    >
                                        1 generation = 1 API request
                                    </span>

                                </div>

                            </div>


                            @if($quizCanBeModified)

                                <form
                                    action="{{ route('teacher.quiz.questions.generate', $quiz) }}"
                                    method="POST"
                                    class="p-5 sm:p-7"
                                    data-quiz-operation="generate"
                                >
                                    @csrf
                                    <input type="hidden" name="revision" value="{{ $quizRevision }}">

                                    <div
                                        class="grid grid-cols-1 gap-5
                                               md:grid-cols-2"
                                    >

                                        <div>

                                            <label
                                                for="question_count"
                                                class="text-sm font-semibold
                                                       text-slate-800"
                                            >
                                                Number of questions
                                            </label>

                                            <select
                                                id="question_count"
                                                name="question_count"
                                                required
                                                class="pw-field mt-2.5
                                                       h-12 px-4"
                                            >
                                                @foreach([2, 3, 5, 10] as $count)

                                                    <option
                                                        value="{{ $count }}"
                                                        @selected(
                                                            (int) old(
                                                                'question_count',
                                                                5
                                                            )
                                                            ===
                                                            $count
                                                        )
                                                    >
                                                        {{ $count }}
                                                        questions
                                                    </option>

                                                @endforeach
                                            </select>

                                            <p
                                                class="mt-2 text-xs
                                                       leading-5
                                                       text-slate-500"
                                            >
                                                Generate several questions in
                                                one request to conserve the
                                                shared Gemini quota.
                                            </p>

                                        </div>


                                        <div>

                                            <label
                                                for="difficulty"
                                                class="text-sm font-semibold
                                                       text-slate-800"
                                            >
                                                Difficulty
                                            </label>

                                            @php
                                                $defaultDifficulty =
                                                    strtolower(
                                                        (string) (
                                                            $lesson
                                                                ->course
                                                                ->difficulty_level
                                                            ?? 'beginner'
                                                        )
                                                    );

                                                if (
                                                    !in_array(
                                                        $defaultDifficulty,
                                                        [
                                                            'beginner',
                                                            'intermediate',
                                                            'advanced',
                                                        ],
                                                        true
                                                    )
                                                ) {
                                                    $defaultDifficulty =
                                                        'beginner';
                                                }
                                            @endphp

                                            <select
                                                id="difficulty"
                                                name="difficulty"
                                                required
                                                class="pw-field mt-2.5
                                                       h-12 px-4"
                                            >
                                                @foreach([
                                                    'beginner' => 'Beginner',
                                                    'intermediate' => 'Intermediate',
                                                    'advanced' => 'Advanced',
                                                ] as $value => $label)

                                                    <option
                                                        value="{{ $value }}"
                                                        @selected(
                                                            old(
                                                                'difficulty',
                                                                $defaultDifficulty
                                                            )
                                                            ===
                                                            $value
                                                        )
                                                    >
                                                        {{ $label }}
                                                    </option>

                                                @endforeach
                                            </select>

                                            <p
                                                class="mt-2 text-xs
                                                       leading-5
                                                       text-slate-500"
                                            >
                                                Difficulty changes how deeply
                                                Gemini tests the lesson
                                                concepts.
                                            </p>

                                        </div>

                                    </div>


                                    <div
                                        class="mt-6 rounded-xl border
                                               border-violet-100
                                               bg-violet-50/60
                                               px-4 py-3"
                                    >

                                        <p
                                            class="text-xs leading-5
                                                   text-violet-700"
                                        >
                                            <span class="font-bold">
                                                Quota protection:
                                            </span>

                                            PathWise allows at most
                                            4 AI generation requests per minute
                                            and 20 per day across this app.
                                            One request can generate up to
                                            10 questions.
                                        </p>

                                    </div>


                                    <div
                                        class="mt-6 flex flex-col gap-3
                                               sm:flex-row
                                               sm:items-center
                                               sm:justify-between"
                                    >

                                        <p
                                            class="text-xs leading-5
                                                   text-slate-500"
                                        >
                                            AI questions are appended.
                                            Existing manual questions are
                                            never overwritten.
                                        </p>


                                        <button
                                            type="submit"
                                            data-ai-generate-button
                                            class="sad-btn-primary inline-flex h-11
                                                   items-center justify-center
                                                   rounded-xl bg-violet-600
                                                   px-5 text-sm font-semibold
                                                   text-white transition
                                                   hover:bg-violet-700
                                                   disabled:cursor-not-allowed
                                                   disabled:opacity-60"
                                        >
                                            ✦ Generate with Gemini
                                        </button>

                                    </div>

                                </form>

                            @else

                                <div
                                    class="flex items-start gap-3
                                           bg-slate-50 px-5 py-5
                                           sm:px-7"
                                >

                                    <span
                                        class="flex h-9 w-9 shrink-0
                                               items-center justify-center
                                               rounded-xl bg-slate-200
                                               text-sm"
                                    >
                                        🔒
                                    </span>

                                    <div>

                                        <p
                                            class="text-sm font-bold
                                                   text-slate-700"
                                        >
                                            AI generation is locked
                                        </p>

                                        <p
                                            class="mt-1 text-xs
                                                   leading-5
                                                   text-slate-500"
                                        >
                                            Questions can only be generated
                                            while the course is Draft or
                                            Rejected.
                                        </p>

                                    </div>

                                </div>

                            @endif

                        </section>

                    @endif



                    <section aria-labelledby="quiz-questions-heading" class="space-y-4">
                        <div>
                            <h2 id="quiz-questions-heading" class="text-lg font-bold text-slate-900">Questions</h2>
                            @if ($quizCanBeModified)
                                <p class="mt-1 text-sm text-slate-600">Use Save Changes above to keep your question edits.</p>
                            @endif
                        </div>

                        @forelse ($quiz?->questions ?? [] as $question)
                            <article class="sad-card pw-question-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                                <h3 class="text-base font-semibold text-slate-900">Question {{ $loop->iteration }}</h3>
                                @include('Teacher.partials.quiz-question-fields', [
                                    'fieldPrefix' => 'questions['.$question->id.']',
                                    'inputPrefix' => 'questions.'.$question->id,
                                    'newQuestion' => false,
                                ])

                                @if ($quizCanBeModified)
                                    <form action="{{ route('teacher.quiz.question.delete', [$quiz, $question]) }}"
                                          method="POST" class="mt-4 text-right" data-quiz-operation="delete">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="revision" value="{{ $quizRevision }}">
                                        <button type="submit" class="rounded-lg px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50"
                                                aria-label="Delete question {{ $loop->iteration }}">
                                            Delete Question
                                        </button>
                                    </form>
                                @endif
                            </article>
                        @empty
                            @unless ($quizCanBeModified)
                                <p class="text-sm text-slate-600">This quiz has no questions.</p>
                            @endunless
                        @endforelse

                        @if ($quizCanBeModified)
                            <section aria-labelledby="new-question-heading" class="sad-card rounded-2xl border border-violet-100 bg-white p-5 shadow-sm sm:p-6">
                                <h3 id="new-question-heading" class="text-base font-semibold text-slate-900">Add a Question</h3>
                                <p class="mt-1 text-sm text-slate-600">Complete the question and use Save Changes to add it.</p>
                                @include('Teacher.partials.quiz-question-fields', [
                                    'fieldPrefix' => 'new_question',
                                    'inputPrefix' => 'new_question',
                                    'newQuestion' => true,
                                    'question' => null,
                                ])
                            </section>
                        @endif
                    </section>

                </div>



                {{-- RIGHT SIDEBAR --}}
                <aside>

                    <div class="sticky top-7 space-y-5">


                        {{-- COURSE --}}
                        <section class="sad-card pw-card p-5">

                            <p
                                class="text-xs font-bold uppercase
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

                                    <span class="text-slate-500">
                                        Lesson order
                                    </span>

                                    <span class="font-semibold text-slate-700">
                                        {{ $lesson->lesson_order }}
                                    </span>

                                </div>

                            </div>

                        </section>



                        {{-- CHECKLIST --}}
                        <section class="sad-card pw-card p-5">

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
                                                       : 'bg-slate-100 text-slate-500'
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
                                                       : 'bg-slate-100 text-slate-500'
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
                                                       : 'bg-slate-100 text-slate-500'
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

                                        <span class="text-slate-500">
                                            Passing
                                        </span>

                                        <span class="font-bold text-slate-700">
                                            {{ $quiz->passing_score }}%
                                        </span>

                                    </div>


                                    <div class="mt-2 flex justify-between text-xs">

                                        <span class="text-slate-500">
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

@if ($quizCanBeModified)
    <script>
        (() => {
            const form = document.querySelector('[data-quiz-edit-form]');
            const status = document.querySelector('[data-quiz-save-status]');

            if (!form || !status || form.dataset.saveStatusBound) return;

            form.dataset.saveStatusBound = 'true';
            let dirty = form.hasAttribute('data-quiz-has-unsaved');
            let submitting = false;

            function markUnsaved() {
                dirty = true;
                status.textContent = 'Unsaved changes';
            }

            for (const field of form.elements) {
                if (field.matches('input:not([type="hidden"]), textarea, select')) {
                    field.addEventListener('input', markUnsaved);
                    field.addEventListener('change', markUnsaved);
                }
            }

            form.addEventListener('submit', (event) => {
                if (submitting) {
                    event.preventDefault();
                    return;
                }

                submitting = true;
                status.textContent = 'Saving...';
            });

            for (const operation of document.querySelectorAll('[data-quiz-operation]')) {
                operation.addEventListener('submit', (event) => {
                    const deleting = operation.dataset.quizOperation === 'delete';
                    const prompt = deleting
                        ? 'Delete this question?'
                        : 'Generate and save questions from the last saved version of the quiz?';
                    const message = dirty
                        ? 'You have unsaved edits that will be lost. ' + prompt
                        : prompt;

                    if (submitting || ((deleting || dirty) && !window.confirm(message))) {
                        event.preventDefault();
                        return;
                    }

                    submitting = true;
                    operation.querySelector('button[type="submit"]').disabled = true;
                    status.textContent = deleting ? 'Deleting question...' : 'Generating with Gemini...';
                });
            }
        })();
    </script>
@endif

</x-layouts::app>