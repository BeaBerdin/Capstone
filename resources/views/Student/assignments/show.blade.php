<x-layouts::app :title="$assignment->title">

@php
    $deadlinePassed =
        $assignment->due_date
        && $assignment->due_date->isPast();

    $isGraded =
        $submission
        && $submission->status === 'graded';

    $isReturned =
        $submission
        && $submission->status === 'returned';

    $canSubmit =
        ! $deadlinePassed
        && ! $isGraded;

    $statusClass = match($submission?->status) {
        'graded' =>
            'border-emerald-200 bg-emerald-50 text-emerald-700',

        'returned' =>
            'border-amber-200 bg-amber-50 text-amber-700',

        'submitted' =>
            'border-blue-200 bg-blue-50 text-blue-700',

        default =>
            'border-slate-200 bg-slate-50 text-slate-600',
    };
@endphp


<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-6xl">


            {{-- BREADCRUMB --}}
            <div
                class="mb-5 flex flex-wrap items-center
                       gap-2 text-xs text-slate-400"
            >

                <a
                    href="{{ route('student.assignments.index') }}"
                    class="font-medium hover:text-violet-600"
                >
                    Assignments
                </a>

                <span>›</span>

                <span class="font-semibold text-slate-600">
                    {{ $assignment->title }}
                </span>

            </div>


            {{-- HEADER --}}
            <div
                class="flex flex-col gap-4
                       lg:flex-row lg:items-end
                       lg:justify-between"
            >

                <div>

                    <p
                        class="text-xs font-bold uppercase
                               tracking-[.12em]
                               text-violet-600"
                    >
                        {{ $assignment->course?->title }}
                    </p>

                    <h1
                        class="mt-2 text-3xl font-bold
                               tracking-tight
                               text-slate-950"
                    >
                        {{ $assignment->title }}
                    </h1>

                    <p class="mt-2 text-sm text-slate-500">
                        Review the instructions and submit your work below.
                    </p>

                </div>


                @if($submission)

                    <span
                        class="inline-flex self-start
                               rounded-full border
                               px-3 py-1.5
                               text-xs font-semibold
                               {{ $statusClass }}"
                    >
                        {{ ucfirst($submission->status) }}
                    </span>

                @endif

            </div>


            {{-- SUCCESS --}}
            @if(session('success'))

                <div
                    class="mt-6 rounded-2xl border
                           border-emerald-200
                           bg-emerald-50
                           px-5 py-4
                           text-sm font-medium
                           text-emerald-700"
                >
                    ✓ {{ session('success') }}
                </div>

            @endif


            {{-- VALIDATION ERRORS --}}
            @if($errors->any())

                <div
                    class="mt-6 rounded-2xl border
                           border-rose-200
                           bg-rose-50 px-5 py-4"
                >

                    <p class="text-sm font-bold text-rose-700">
                        Please correct the following:
                    </p>

                    <ul
                        class="mt-2 list-disc
                               space-y-1 pl-5
                               text-sm text-rose-600"
                    >

                        @foreach($errors->all() as $error)

                            <li>{{ $error }}</li>

                        @endforeach

                    </ul>

                </div>

            @endif


            <div
                class="mt-7 grid gap-6
                       lg:grid-cols-[1.1fr_.9fr]"
            >


                {{-- LEFT: ASSIGNMENT DETAILS --}}
                <div class="space-y-6">


                    <section
                        class="overflow-hidden
                               rounded-2xl
                               border border-slate-200
                               bg-white shadow-sm"
                    >

                        <div
                            class="border-b border-slate-100
                                   px-5 py-4 sm:px-6"
                        >

                            <h2
                                class="text-sm font-bold
                                       text-slate-800"
                            >
                                Assignment Instructions
                            </h2>

                        </div>


                        <div class="p-5 sm:p-6">

                            <p
                                class="whitespace-pre-line
                                       text-sm leading-7
                                       text-slate-700"
                            >
                                {{
                                    $assignment->description
                                    ?: 'No additional instructions were provided.'
                                }}
                            </p>


                            <div
                                class="mt-6 grid gap-3
                                       sm:grid-cols-2"
                            >

                                <div
                                    class="rounded-xl
                                           bg-violet-50
                                           px-4 py-3"
                                >

                                    <p
                                        class="text-[10px]
                                               font-bold uppercase
                                               tracking-wider
                                               text-violet-500"
                                    >
                                        Maximum Score
                                    </p>

                                    <p
                                        class="mt-1 text-lg
                                               font-bold
                                               text-violet-700"
                                    >
                                        {{ $assignment->max_score }}
                                    </p>

                                </div>


                                <div
                                    class="rounded-xl
                                           {{
                                               $deadlinePassed
                                               ? 'bg-rose-50'
                                               : 'bg-blue-50'
                                           }}
                                           px-4 py-3"
                                >

                                    <p
                                        class="text-[10px]
                                               font-bold uppercase
                                               tracking-wider
                                               {{
                                                   $deadlinePassed
                                                   ? 'text-rose-500'
                                                   : 'text-blue-500'
                                               }}"
                                    >
                                        Due Date
                                    </p>

                                    <p
                                        class="mt-1 text-sm
                                               font-bold
                                               {{
                                                   $deadlinePassed
                                                   ? 'text-rose-700'
                                                   : 'text-blue-700'
                                               }}"
                                    >
                                        {{
                                            $assignment->due_date
                                                ? $assignment->due_date
                                                    ->format(
                                                        'M d, Y · h:i A'
                                                    )
                                                : 'No deadline'
                                        }}
                                    </p>

                                </div>

                            </div>


                            @if($assignment->lesson)

                                <div
                                    class="mt-4 rounded-xl
                                           border border-slate-200
                                           bg-slate-50
                                           px-4 py-3"
                                >

                                    <p
                                        class="text-[10px]
                                               font-bold uppercase
                                               tracking-wider
                                               text-slate-400"
                                    >
                                        Related Lesson
                                    </p>

                                    <p
                                        class="mt-1 text-sm
                                               font-semibold
                                               text-slate-700"
                                    >
                                        {{ $assignment->lesson->title }}
                                    </p>

                                </div>

                            @endif

                        </div>

                    </section>


                    {{-- TEACHER FEEDBACK --}}
                    @if(
                        $submission
                        && (
                            $submission->feedback
                            || $submission->status === 'graded'
                            || $submission->status === 'returned'
                        )
                    )

                        <section
                            class="overflow-hidden
                                   rounded-2xl
                                   border border-slate-200
                                   bg-white shadow-sm"
                        >

                            <div
                                class="border-b border-slate-100
                                       px-5 py-4 sm:px-6"
                            >

                                <h2
                                    class="text-sm font-bold
                                           text-slate-800"
                                >
                                    Teacher Evaluation
                                </h2>

                            </div>


                            <div class="p-5 sm:p-6">

                                @if($submission->status === 'graded')

                                    <div
                                        class="rounded-xl
                                               border border-emerald-100
                                               bg-emerald-50
                                               px-4 py-4"
                                    >

                                        <p
                                            class="text-xs font-bold
                                                   uppercase
                                                   tracking-wider
                                                   text-emerald-600"
                                        >
                                            Final Score
                                        </p>

                                        <p
                                            class="mt-1 text-2xl
                                                   font-bold
                                                   text-emerald-700"
                                        >
                                            {{ $submission->score }}
                                            /
                                            {{ $assignment->max_score }}
                                        </p>

                                    </div>

                                @elseif($submission->status === 'returned')

                                    <div
                                        class="rounded-xl
                                               border border-amber-100
                                               bg-amber-50
                                               px-4 py-4"
                                    >

                                        <p
                                            class="text-sm font-semibold
                                                   text-amber-700"
                                        >
                                            Your work was returned for revision.
                                        </p>

                                        <p
                                            class="mt-1 text-xs
                                                   text-amber-600"
                                        >
                                            Review your teacher's feedback and submit an updated answer.
                                        </p>

                                    </div>

                                @endif


                                @if($submission->feedback)

                                    <div
                                        class="mt-4 rounded-xl
                                               border border-slate-200
                                               bg-slate-50
                                               px-4 py-4"
                                    >

                                        <p
                                            class="text-[10px]
                                                   font-bold uppercase
                                                   tracking-wider
                                                   text-slate-400"
                                        >
                                            Feedback
                                        </p>

                                        <p
                                            class="mt-2 whitespace-pre-line
                                                   text-sm leading-6
                                                   text-slate-700"
                                        >
                                            {{ $submission->feedback }}
                                        </p>

                                    </div>

                                @endif

                            </div>

                        </section>

                    @endif

                </div>


                {{-- RIGHT: SUBMISSION --}}
                <section
                    class="h-fit overflow-hidden
                           rounded-2xl
                           border border-slate-200
                           bg-white shadow-sm"
                >

                    <div
                        class="border-b border-slate-100
                               px-5 py-4"
                    >

                        <h2
                            class="text-sm font-bold
                                   text-slate-800"
                        >
                            Your Submission
                        </h2>

                        <p class="mt-1 text-xs text-slate-400">

                            @if($isGraded)

                                This submission has been graded.

                            @elseif($deadlinePassed)

                                The submission deadline has passed.

                            @elseif($submission)

                                You may update your work until it is graded.

                            @else

                                Submit a written answer, a file, or both.

                            @endif

                        </p>

                    </div>


                    @if($submission)

                        <div
                            class="border-b border-slate-100
                                   bg-slate-50
                                   px-5 py-4"
                        >

                            <div
                                class="flex flex-wrap
                                       items-center
                                       justify-between
                                       gap-2"
                            >

                                <div>

                                    <p
                                        class="text-[10px]
                                               font-bold uppercase
                                               tracking-wider
                                               text-slate-400"
                                    >
                                        Last Submitted
                                    </p>

                                    <p
                                        class="mt-1 text-xs
                                               font-semibold
                                               text-slate-700"
                                    >
                                        {{
                                            $submission->submitted_at
                                                ?->format(
                                                    'M d, Y · h:i A'
                                                )
                                            ?? '—'
                                        }}
                                    </p>

                                </div>


                                <span
                                    class="rounded-full border
                                           px-2.5 py-1
                                           text-[11px]
                                           font-semibold
                                           {{ $statusClass }}"
                                >
                                    {{ ucfirst($submission->status) }}
                                </span>

                            </div>

                        </div>

                    @endif


                    @if($canSubmit)

                        <form
                            method="POST"
                            action="{{
                                route(
                                    'student.assignments.submit',
                                    $assignment
                                )
                            }}"
                            enctype="multipart/form-data"
                            class="space-y-5 p-5"
                        >

                            @csrf


                            {{-- ANSWER --}}
                            <div>

                                <label
                                    for="answer_text"
                                    class="text-sm
                                           font-semibold
                                           text-slate-700"
                                >
                                    Written Answer
                                </label>

                                <textarea
                                    id="answer_text"
                                    name="answer_text"
                                    rows="9"
                                    maxlength="20000"
                                    class="mt-2 w-full
                                           rounded-xl border
                                           border-slate-200
                                           px-4 py-3
                                           text-sm outline-none
                                           focus:border-violet-300
                                           focus:ring-4
                                           focus:ring-violet-100"
                                    placeholder="Write your answer here..."
                                >{{ old('answer_text', $submission?->answer_text) }}</textarea>

                            </div>


                            {{-- CURRENT FILE --}}
                            @if($submission?->file_path)

                                <div
                                    class="rounded-xl border
                                           border-violet-100
                                           bg-violet-50
                                           px-4 py-3"
                                >

                                    <p
                                        class="text-[10px]
                                               font-bold uppercase
                                               tracking-wider
                                               text-violet-500"
                                    >
                                        Current Attachment
                                    </p>

                                    <a
                                        href="{{
                                            asset(
                                                'storage/' .
                                                ltrim(
                                                    $submission->file_path,
                                                    '/'
                                                )
                                            )
                                        }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="mt-1 block
                                               break-all
                                               text-sm font-semibold
                                               text-violet-700
                                               hover:underline"
                                    >
                                        {{
                                            basename(
                                                $submission->file_path
                                            )
                                        }}
                                    </a>

                                </div>

                            @endif


                            {{-- FILE --}}
                            <div>

                                <label
                                    for="submission_file"
                                    class="text-sm
                                           font-semibold
                                           text-slate-700"
                                >
                                    Attach File
                                </label>

                                <input
                                    id="submission_file"
                                    name="submission_file"
                                    type="file"
                                    accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.jpg,.jpeg,.png"
                                    class="mt-2 block w-full
                                           rounded-xl border
                                           border-slate-200
                                           bg-white px-3 py-2.5
                                           text-sm text-slate-600
                                           file:mr-4
                                           file:rounded-lg
                                           file:border-0
                                           file:bg-violet-50
                                           file:px-3
                                           file:py-2
                                           file:text-xs
                                           file:font-semibold
                                           file:text-violet-700"
                                >

                                <p
                                    class="mt-1.5 text-xs
                                           text-slate-400"
                                >
                                    PDF, Word, PowerPoint, TXT, JPG or PNG.
                                    Maximum 10 MB.
                                </p>

                            </div>


                            <button
                                type="submit"
                                class="inline-flex h-11
                                       w-full items-center
                                       justify-center
                                       rounded-xl
                                       bg-gradient-to-r
                                       from-violet-600
                                       to-indigo-600
                                       px-5 text-sm
                                       font-semibold text-white
                                       shadow-md
                                       shadow-violet-200
                                       transition
                                       hover:-translate-y-0.5"
                            >

                                @if($submission)

                                    Update Submission

                                @else

                                    Submit Assignment

                                @endif

                            </button>

                        </form>


                    @elseif($isGraded)

                        <div class="p-5">

                            <div
                                class="rounded-xl border
                                       border-emerald-100
                                       bg-emerald-50
                                       px-4 py-4"
                            >

                                <p
                                    class="text-sm font-semibold
                                           text-emerald-700"
                                >
                                    This assignment has been graded.
                                </p>

                                <p
                                    class="mt-1 text-xs
                                           text-emerald-600"
                                >
                                    Your submission can no longer be edited.
                                </p>

                            </div>

                        </div>


                    @elseif($deadlinePassed)

                        <div class="p-5">

                            <div
                                class="rounded-xl border
                                       border-rose-100
                                       bg-rose-50
                                       px-4 py-4"
                            >

                                <p
                                    class="text-sm font-semibold
                                           text-rose-700"
                                >
                                    Submission closed
                                </p>

                                <p
                                    class="mt-1 text-xs
                                           text-rose-600"
                                >
                                    The deadline for this assignment has passed.
                                </p>

                            </div>

                        </div>

                    @endif

                </section>

            </div>

        </div>

    </main>

</div>

</x-layouts::app>