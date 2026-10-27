<x-layouts::app :title="'Review Submission - ' . ($submission->student?->name ?? 'Student')">
@include('partials.soft3d')

@php
    $assignment = $submission->assignment;

    $statusClass = match($submission->status) {
        'graded' =>
            'bg-emerald-50 text-emerald-700 border-emerald-200',

        'returned' =>
            'bg-amber-50 text-amber-700 border-amber-200',

        default =>
            'bg-blue-50 text-blue-700 border-blue-200',
    };

    // A "processing" status that never finished counts as not finished.
    $aiDidNotFinish = $submission->aiEvaluationDidNotFinish();
    $aiInProgress = $submission->aiEvaluationInProgress();

    $aiStatusClass = match(true) {
        $submission->ai_evaluation_status === 'completed' =>
            'bg-emerald-50 text-emerald-700 border-emerald-200',

        $aiInProgress =>
            'bg-blue-50 text-blue-700 border-blue-200',

        $aiDidNotFinish =>
            'bg-rose-50 text-rose-700 border-rose-200',

        default =>
            'bg-slate-50 text-slate-600 border-slate-200',
    };

    $aiStatusLabel = match(true) {
        $submission->ai_evaluation_status === 'completed' => 'Completed',
        $aiInProgress => 'Processing',
        $aiDidNotFinish => 'Failed',
        default => 'Not requested',
    };
@endphp

<div class="sad-page min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-6xl">

            {{-- Breadcrumb --}}
            <div class="mb-5 flex flex-wrap items-center gap-2 text-xs text-slate-500">

                <a
                    href="{{ route('teacher.assignments.index') }}"
                    class="font-medium hover:text-violet-600"
                >
                    Assignments
                </a>

                <span>›</span>

                <a
                    href="{{ route('teacher.submissions.index', $assignment) }}"
                    class="font-medium hover:text-violet-600"
                >
                    Submissions
                </a>

                <span>›</span>

                <span class="font-semibold text-slate-600">
                    Review
                </span>

            </div>


            {{-- Header --}}
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

                <div>

                    <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                        Teacher Evaluation
                    </p>

                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                        Review Submission
                    </h1>

                    <p class="mt-2 text-sm text-slate-500">

                        {{ $submission->student?->name ?? 'Unknown Student' }}

                        <span class="mx-1 text-slate-300">
                            •
                        </span>

                        {{ $assignment->title }}

                    </p>

                </div>


                <span
                    class="inline-flex self-start rounded-full border px-3 py-1.5
                           text-xs font-semibold {{ $statusClass }}"
                >
                    {{ ucfirst($submission->status) }}
                </span>

            </div>


            {{-- Success --}}
            @if(session('success'))

                <div
                    class="mt-6 rounded-2xl border border-emerald-200
                           bg-emerald-50 px-5 py-4 text-sm font-medium
                           text-emerald-700"
                >
                    ✓ {{ session('success') }}
                </div>

            @endif


            {{-- Errors --}}
            @if($errors->any())

                <div
                    class="mt-6 rounded-2xl border border-rose-200
                           bg-rose-50 px-5 py-4"
                >

                    <p class="text-sm font-bold text-rose-700">
                        Please correct the following:
                    </p>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-600">

                        @foreach($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            <div class="mt-7 grid gap-6 lg:grid-cols-[1.25fr_.75fr]">

                {{-- LEFT SIDE --}}
                <div class="space-y-6">


                    {{-- Assignment --}}
                    <section
                        class="sad-card overflow-hidden rounded-2xl border
                               border-slate-200 bg-white shadow-sm"
                    >

                        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">

                            <h2 class="text-sm font-bold text-slate-800">
                                Assignment
                            </h2>

                        </div>


                        <div class="p-5 sm:p-6">

                            <h3 class="text-base font-bold text-slate-900">
                                {{ $assignment->title }}
                            </h3>


                            <p
                                class="mt-2 whitespace-pre-line text-sm
                                       leading-6 text-slate-600"
                            >
                                {{
                                    $assignment->description
                                    ?: 'No additional instructions.'
                                }}
                            </p>


                            <div
                                class="mt-4 flex flex-wrap gap-x-5
                                       gap-y-2 text-xs text-slate-500"
                            >

                                <span>
                                    Max score:

                                    <strong class="text-slate-700">
                                        {{ $assignment->max_score }}
                                    </strong>
                                </span>


                                @if($assignment->due_date)

                                    <span>
                                        Due:

                                        <strong class="text-slate-700">
                                            {{
                                                $assignment->due_date
                                                    ->format('M d, Y · h:i A')
                                            }}
                                        </strong>
                                    </span>

                                @endif


                                <span>
                                    Type:

                                    <strong class="text-slate-700">
                                        {{
                                            match($assignment->submission_type) {
                                                'code' => 'Code Submission',
                                                'multiple_choice' => 'Multiple Choice',
                                                default => 'Essay / Written Response',
                                            }
                                        }}
                                    </strong>
                                </span>

                            </div>

                        </div>

                    </section>


                    {{-- Student Answer --}}
                    <section
                        class="sad-card overflow-hidden rounded-2xl border
                               border-slate-200 bg-white shadow-sm"
                    >

                        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">

                            <h2 class="text-sm font-bold text-slate-800">
                                Student Answer
                            </h2>

                        </div>


                        <div class="p-5 sm:p-6">

                            @if($submission->answer_text)

                                <div
                                    class="rounded-xl border border-slate-200
                                           bg-slate-50 px-4 py-4"
                                >

                                    {{-- Shown exactly as submitted: escaped text, line breaks and indentation kept, never run. --}}
                                    @if($assignment->submission_type === 'code')
                                        <pre class="overflow-x-auto whitespace-pre font-mono text-sm leading-6 text-slate-800">{{ $submission->answer_text }}</pre>
                                    @else
                                        <p class="whitespace-pre-wrap wrap-break-word text-sm leading-7 text-slate-700">{{ $submission->answer_text }}</p>
                                    @endif

                                </div>

                            @else

                                <p class="text-sm text-slate-500">
                                    No written answer submitted.
                                </p>

                            @endif


                            @if($submission->file_path)

                                <div
                                    class="mt-5 rounded-xl border
                                           border-violet-100 bg-violet-50 p-4"
                                >

                                    <div
                                        class="flex flex-col gap-3 sm:flex-row
                                               sm:items-center sm:justify-between"
                                    >

                                        <div>

                                            <p
                                                class="text-xs font-bold uppercase
                                                       tracking-wider text-violet-500"
                                            >
                                                Attachment
                                            </p>

                                            <p
                                                class="mt-1 break-all text-sm
                                                       font-semibold text-violet-800"
                                            >
                                                {{
                                                    basename(
                                                        $submission->file_path
                                                    )
                                                }}
                                            </p>

                                        </div>


                                        <a
                                            href="{{
                                                route(
                                                    'files.submission',
                                                    $submission
                                                )
                                            }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="sad-btn-primary inline-flex h-9 items-center
                                                   justify-center rounded-xl
                                                   bg-violet-600 px-4 text-xs
                                                   font-semibold text-white
                                                   hover:bg-violet-700"
                                        >
                                            Open File
                                        </a>

                                    </div>

                                </div>

                            @endif


                            <p class="mt-4 text-xs text-slate-500">

                                Submitted

                                {{
                                    $submission->submitted_at
                                        ?->format('M d, Y · h:i A')
                                    ?? '—'
                                }}

                            </p>

                        </div>

                    </section>


                    {{-- AI EVALUATION --}}
                    @if($assignment->ai_evaluation_enabled)

                        <section
                            class="sad-card overflow-hidden rounded-2xl border
                                   border-violet-200 bg-white shadow-sm"
                        >

                            {{-- AI Header --}}
                            <div
                                class="border-b border-violet-100
                                       bg-linear-to-r from-violet-50
                                       to-indigo-50 px-5 py-5 sm:px-6"
                            >

                                <div
                                    class="flex flex-col gap-4
                                           sm:flex-row sm:items-center
                                           sm:justify-between"
                                >

                                    <div>

                                        <div class="flex items-center gap-2">

                                            <div
                                                class="flex h-9 w-9 items-center
                                                       justify-center rounded-xl
                                                       bg-violet-600 text-lg text-white"
                                            >
                                                ✦
                                            </div>

                                            <div>

                                                <h2
                                                    class="text-sm font-bold
                                                           text-slate-900"
                                                >
                                                    AI Evaluation
                                                </h2>

                                                <p class="mt-0.5 text-xs text-slate-500">
                                                    AI-generated evaluation for teacher review
                                                </p>

                                            </div>

                                        </div>

                                    </div>


                                    {{-- AI Status --}}
                                    <span
                                        class="inline-flex w-fit rounded-full
                                               border px-3 py-1.5 text-xs
                                               font-semibold {{ $aiStatusClass }}"
                                    >
                                        {{ $aiStatusLabel }}
                                    </span>

                                </div>

                            </div>


                            <div class="p-5 sm:p-6">

                                {{-- Generate Button --}}
                                @if(
                                    $submission->ai_evaluation_status !== 'completed'
                                    && ! $aiInProgress
                                )

                                    @if($aiDidNotFinish)
                                        <div
                                            class="mb-4 rounded-xl border border-rose-200
                                                   bg-rose-50 px-4 py-3"
                                        >
                                            <p class="text-sm font-semibold text-rose-800">
                                                The last AI evaluation did not finish
                                            </p>

                                            <p class="mt-1 text-xs leading-5 text-rose-700">
                                                Gemini failed, timed out or returned an unusable answer.
                                                No AI score was saved and the student's submission is unchanged.
                                                You can retry, or grade this submission manually below.
                                            </p>
                                        </div>
                                    @endif

                                    <div
                                        class="rounded-xl border
                                               border-violet-100
                                               bg-violet-50/70 p-4"
                                    >

                                        <div
                                            class="flex flex-col gap-4
                                                   sm:flex-row sm:items-center
                                                   sm:justify-between"
                                        >

                                            <div>

                                                <p
                                                    class="text-sm font-semibold
                                                           text-violet-900"
                                                >
                                                    AI-assisted evaluation is available
                                                </p>

                                                <p
                                                    class="mt-1 text-xs leading-5
                                                           text-violet-700"
                                                >
                                                    Generate a suggested score and
                                                    feedback. The teacher still makes
                                                    the final grading decision.
                                                </p>

                                            </div>


                                            <form
                                                method="POST"
                                                action="{{
                                                    route(
                                                        'teacher.submissions.ai-evaluate',
                                                        $submission
                                                    )
                                                }}"
                                            >

                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="inline-flex h-10 w-full
                                                           items-center justify-center
                                                           rounded-xl bg-linear-to-r
                                                           from-violet-600 to-indigo-600
                                                           px-5 text-sm font-semibold
                                                           text-white shadow-md
                                                           shadow-violet-200
                                                           transition hover:-translate-y-0.5
                                                           sm:w-auto"
                                                >
                                                    ✦ {{ $aiDidNotFinish ? 'Retry AI Evaluation' : 'Generate AI Evaluation' }}
                                                </button>

                                            </form>

                                        </div>

                                    </div>

                                @elseif($aiInProgress)

                                    <div
                                        class="rounded-xl border border-blue-200
                                               bg-blue-50 px-4 py-4"
                                    >

                                        <div class="flex items-center gap-3">

                                            <div
                                                class="h-5 w-5 animate-spin
                                                       rounded-full border-2
                                                       border-blue-200
                                                       border-t-blue-600"
                                            ></div>

                                            <div>

                                                <p
                                                    class="text-sm font-semibold
                                                           text-blue-800"
                                                >
                                                    AI evaluation is being processed
                                                </p>

                                                <p class="mt-1 text-xs text-blue-600">
                                                    Please wait while the submission
                                                    is evaluated.
                                                </p>

                                            </div>

                                        </div>

                                    </div>

                                @else

                                    {{-- AI SCORE --}}
                                    <div class="grid gap-4 sm:grid-cols-3">

                                        <div
                                            class="rounded-xl border
                                                   border-violet-100
                                                   bg-violet-50 p-4"
                                        >

                                            <p
                                                class="text-xs font-semibold
                                                       uppercase tracking-wider
                                                       text-violet-500"
                                            >
                                                AI Suggested Score
                                            </p>

                                            <div class="mt-2 flex items-end gap-1">

                                                <span
                                                    class="text-3xl font-bold
                                                           text-violet-700"
                                                >
                                                    {{ $submission->ai_score ?? '—' }}
                                                </span>

                                                <span
                                                    class="mb-1 text-sm
                                                           text-violet-400"
                                                >
                                                    / {{ $assignment->max_score }}
                                                </span>

                                            </div>

                                        </div>


                                        <div
                                            class="rounded-xl border
                                                   border-slate-200
                                                   bg-slate-50 p-4 sm:col-span-2"
                                        >

                                            <p
                                                class="text-xs font-semibold
                                                       uppercase tracking-wider
                                                       text-slate-500"
                                            >
                                                Evaluation Status
                                            </p>

                                            <p
                                                class="mt-2 text-sm font-semibold
                                                       text-slate-700"
                                            >
                                                AI evaluation completed
                                            </p>

                                            @if($submission->ai_evaluated_at)

                                                <p class="mt-1 text-xs text-slate-500">

                                                    {{
                                                        $submission->ai_evaluated_at
                                                            ->format('M d, Y · h:i A')
                                                    }}

                                                </p>

                                            @endif

                                        </div>

                                    </div>


                                    {{-- AI FEEDBACK --}}
                                    @if($submission->ai_feedback)

                                        <div class="mt-5">

                                            <h3
                                                class="text-sm font-bold
                                                       text-slate-800"
                                            >
                                                AI Feedback
                                            </h3>

                                            <div
                                                class="mt-2 rounded-xl border
                                                       border-slate-200
                                                       bg-slate-50 px-4 py-4"
                                            >

                                                {{-- pre-wrap keeps indentation in code shown by Gemini; content is escaped. --}}
                                                <p class="whitespace-pre-wrap wrap-break-word text-sm leading-6 text-slate-700">{{ $submission->ai_feedback }}</p>

                                            </div>

                                        </div>

                                    @endif


                                    {{-- STRENGTHS --}}
                                    @if($submission->ai_strengths)

                                        <div class="mt-5">

                                            <h3
                                                class="text-sm font-bold
                                                       text-emerald-700"
                                            >
                                                ✓ Strengths
                                            </h3>

                                            <div
                                                class="mt-2 rounded-xl border
                                                       border-emerald-100
                                                       bg-emerald-50 px-4 py-4"
                                            >

                                                <p class="whitespace-pre-wrap wrap-break-word text-sm leading-6 text-emerald-800">{{ $submission->ai_strengths }}</p>

                                            </div>

                                        </div>

                                    @endif


                                    {{-- IMPROVEMENTS --}}
                                    @if($submission->ai_improvements)

                                        <div class="mt-5">

                                            <h3
                                                class="text-sm font-bold
                                                       text-amber-700"
                                            >
                                                ↗ Areas for Improvement
                                            </h3>

                                            <div
                                                class="mt-2 rounded-xl border
                                                       border-amber-100
                                                       bg-amber-50 px-4 py-4"
                                            >

                                                <p class="whitespace-pre-wrap wrap-break-word text-sm leading-6 text-amber-800">{{ $submission->ai_improvements }}</p>

                                            </div>

                                        </div>

                                    @endif


                                    {{-- Teacher reminder --}}
                                    <div
                                        class="mt-5 rounded-xl border
                                               border-indigo-100
                                               bg-indigo-50 px-4 py-3"
                                    >

                                        <p class="text-xs leading-5 text-indigo-700">

                                            <strong>Teacher Review:</strong>
                                            The AI score is only a recommendation.
                                            Review the AI feedback and student answer
                                            before assigning the final grade.

                                        </p>

                                    </div>


                                    {{-- Regenerate --}}
                                    <form
                                        method="POST"
                                        action="{{
                                            route(
                                                'teacher.submissions.ai-evaluate',
                                                $submission
                                            )
                                        }}"
                                        class="mt-4"
                                    >

                                        @csrf

                                        <button
                                            type="submit"
                                            class="text-xs font-semibold
                                                   text-violet-600
                                                   hover:text-violet-800"
                                        >
                                            ↻ Generate Again
                                        </button>

                                    </form>

                                @endif

                            </div>

                        </section>

                    @endif

                </div>


                {{-- RIGHT SIDE: TEACHER EVALUATION --}}
                <form
                    method="POST"
                    action="{{ route('teacher.submissions.grade', $submission) }}"
                    class="sad-card h-fit overflow-hidden lg:sticky lg:top-24"
                >
                    @csrf
                    @method('PUT')

                    <x-pw.form-section title="Teacher Evaluation" first>
                        <x-slot:icon>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z"></path><path d="m8.5 12.5 2.5 2.5 4.5-5"></path></svg>
                        </x-slot:icon>

                        <p class="-mt-2 text-xs text-slate-500">
                            Review the AI suggestion and assign the final grade.
                        </p>

                        {{-- AI score shortcut --}}
                        @if(
                            $assignment->ai_evaluation_enabled
                            && $submission->ai_evaluation_status === 'completed'
                            && $submission->ai_score !== null
                        )
                            <div class="rounded-2xl border border-violet-100 bg-violet-50/60 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wider text-violet-500">
                                    AI Suggestion
                                </p>

                                <div class="mt-2 flex items-center justify-between gap-3">
                                    <p class="text-sm text-violet-800">
                                        Suggested:
                                        <strong>{{ $submission->ai_score }} / {{ $assignment->max_score }}</strong>
                                    </p>

                                    <button
                                        type="button"
                                        onclick="document.getElementById('score').value = '{{ $submission->ai_score }}'"
                                        class="rounded-lg border border-violet-200 bg-white px-3 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-100"
                                    >
                                        Use AI Score
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- Score --}}
                        <x-pw.field label="Final Score" for="score">
                            <div class="flex items-center gap-2">
                                <input
                                    id="score"
                                    name="score"
                                    type="number"
                                    min="0"
                                    max="{{ $assignment->max_score }}"
                                    value="{{ old('score', $submission->score) }}"
                                    class="sad-field min-w-0 flex-1"
                                    placeholder="0"
                                >

                                <span class="mt-2 shrink-0 text-sm font-semibold text-slate-500">
                                    / {{ $assignment->max_score }}
                                </span>
                            </div>
                        </x-pw.field>

                        {{-- Feedback --}}
                        <x-pw.field label="Final Feedback" for="feedback" hint="The student sees this with their grade.">
                            <textarea
                                id="feedback"
                                name="feedback"
                                rows="7"
                                maxlength="10000"
                                class="sad-field"
                                placeholder="Write feedback for the student..."
                            >{{ old('feedback', $submission->feedback) }}</textarea>
                        </x-pw.field>
                    </x-pw.form-section>

                    {{-- Buttons --}}
                    <div class="grid gap-3 border-t border-slate-100 bg-slate-50/70 px-5 py-4 sm:px-6">
                        <button type="submit" name="status" value="graded" class="sad-btn sad-btn-primary w-full">
                            Save Final Grade
                        </button>

                        <button
                            type="submit"
                            name="status"
                            value="returned"
                            class="sad-btn w-full border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100"
                        >
                            Return for Revision
                        </button>
                    </div>
                </form>

            </div>

        </div>

    </main>

</div>

</x-layouts::app>