<x-layouts::app :title="'Review Submission - ' . ($submission->student?->name ?? 'Student')">

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
@endphp

<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-6xl">

            {{-- Breadcrumb --}}
            <div
                class="mb-5 flex flex-wrap items-center
                       gap-2 text-xs text-slate-400"
            >
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
            <div
                class="flex flex-col gap-4
                       lg:flex-row lg:items-end
                       lg:justify-between"
            >

                <div>

                    <p
                        class="text-xs font-bold uppercase
                               tracking-[.12em] text-violet-600"
                    >
                        Teacher Evaluation
                    </p>

                    <h1
                        class="mt-2 text-3xl font-bold
                               tracking-tight text-slate-950"
                    >
                        Review Submission
                    </h1>

                    <p class="mt-2 text-sm text-slate-500">

                        {{
                            $submission->student?->name
                            ?? 'Unknown Student'
                        }}

                        <span class="mx-1 text-slate-300">
                            •
                        </span>

                        {{ $assignment->title }}

                    </p>

                </div>


                <span
                    class="inline-flex self-start rounded-full
                           border px-3 py-1.5
                           text-xs font-semibold
                           {{ $statusClass }}"
                >
                    {{ ucfirst($submission->status) }}
                </span>

            </div>


            {{-- Success --}}
            @if(session('success'))

                <div
                    class="mt-6 rounded-2xl border
                           border-emerald-200 bg-emerald-50
                           px-5 py-4 text-sm font-medium
                           text-emerald-700"
                >
                    ✓ {{ session('success') }}
                </div>

            @endif


            {{-- Errors --}}
            @if($errors->any())

                <div
                    class="mt-6 rounded-2xl border
                           border-rose-200 bg-rose-50
                           px-5 py-4"
                >

                    <p class="text-sm font-bold text-rose-700">
                        Please correct the following:
                    </p>

                    <ul
                        class="mt-2 list-disc space-y-1
                               pl-5 text-sm text-rose-600"
                    >

                        @foreach($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            <div
                class="mt-7 grid gap-6
                       lg:grid-cols-[1.25fr_.75fr]"
            >

                {{-- LEFT SIDE --}}
                <div class="space-y-6">


                    {{-- Assignment --}}
                    <section
                        class="overflow-hidden rounded-2xl
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
                                Assignment
                            </h2>
                        </div>


                        <div class="p-5 sm:p-6">

                            <h3
                                class="text-base font-bold
                                       text-slate-900"
                            >
                                {{ $assignment->title }}
                            </h3>


                            <p
                                class="mt-2 whitespace-pre-line
                                       text-sm leading-6
                                       text-slate-600"
                            >
                                {{
                                    $assignment->description
                                    ?: 'No additional instructions.'
                                }}
                            </p>


                            <div
                                class="mt-4 flex flex-wrap
                                       gap-x-5 gap-y-2
                                       text-xs text-slate-500"
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
                                                    ->format(
                                                        'M d, Y · h:i A'
                                                    )
                                            }}
                                        </strong>
                                    </span>

                                @endif

                            </div>

                        </div>

                    </section>


                    {{-- Student Answer --}}
                    <section
                        class="overflow-hidden rounded-2xl
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
                                Student Answer
                            </h2>

                        </div>


                        <div class="p-5 sm:p-6">

                            @if($submission->answer_text)

                                <div
                                    class="rounded-xl border
                                           border-slate-200
                                           bg-slate-50
                                           px-4 py-4"
                                >

                                    <p
                                        class="whitespace-pre-line
                                               text-sm leading-7
                                               text-slate-700"
                                    >
                                        {{ $submission->answer_text }}
                                    </p>

                                </div>

                            @else

                                <p class="text-sm text-slate-400">
                                    No written answer submitted.
                                </p>

                            @endif


                            @if($submission->file_path)

                                <div
                                    class="mt-5 rounded-xl
                                           border border-violet-100
                                           bg-violet-50 p-4"
                                >

                                    <div
                                        class="flex flex-col gap-3
                                               sm:flex-row
                                               sm:items-center
                                               sm:justify-between"
                                    >

                                        <div>

                                            <p
                                                class="text-xs font-bold
                                                       uppercase
                                                       tracking-wider
                                                       text-violet-500"
                                            >
                                                Attachment
                                            </p>

                                            <p
                                                class="mt-1 break-all
                                                       text-sm font-semibold
                                                       text-violet-800"
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
                                            class="inline-flex h-9
                                                   items-center
                                                   justify-center
                                                   rounded-xl
                                                   bg-violet-600
                                                   px-4
                                                   text-xs font-semibold
                                                   text-white
                                                   hover:bg-violet-700"
                                        >
                                            Open File
                                        </a>

                                    </div>

                                </div>

                            @endif


                            <p
                                class="mt-4 text-xs
                                       text-slate-400"
                            >
                                Submitted

                                {{
                                    $submission->submitted_at
                                        ?->format(
                                            'M d, Y · h:i A'
                                        )
                                    ?? '—'
                                }}
                            </p>

                        </div>

                    </section>

                </div>


                {{-- RIGHT SIDE: EVALUATION --}}
                <section
                    class="h-fit overflow-hidden
                           rounded-2xl border
                           border-slate-200
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
                            Evaluation
                        </h2>

                        <p class="mt-1 text-xs text-slate-400">
                            Grade the work or return it for revision.
                        </p>

                    </div>


                    <form
                        method="POST"
                        action="{{
                            route(
                                'teacher.submissions.grade',
                                $submission
                            )
                        }}"
                        class="space-y-5 p-5"
                    >

                        @csrf
                        @method('PUT')


                        {{-- Score --}}
                        <div>

                            <label
                                for="score"
                                class="text-sm font-semibold
                                       text-slate-700"
                            >
                                Score
                            </label>


                            <div
                                class="mt-2 flex items-center
                                       gap-2"
                            >

                                <input
                                    id="score"
                                    name="score"
                                    type="number"
                                    min="0"
                                    max="{{ $assignment->max_score }}"
                                    value="{{
                                        old(
                                            'score',
                                            $submission->score
                                        )
                                    }}"
                                    class="h-11 min-w-0 flex-1
                                           rounded-xl border
                                           border-slate-200
                                           px-4 text-sm
                                           outline-none
                                           focus:border-violet-300
                                           focus:ring-4
                                           focus:ring-violet-100"
                                    placeholder="0"
                                >


                                <span
                                    class="shrink-0
                                           text-sm font-semibold
                                           text-slate-500"
                                >
                                    /
                                    {{ $assignment->max_score }}
                                </span>

                            </div>

                        </div>


                        {{-- Feedback --}}
                        <div>

                            <label
                                for="feedback"
                                class="text-sm font-semibold
                                       text-slate-700"
                            >
                                Feedback
                            </label>


                            <textarea
                                id="feedback"
                                name="feedback"
                                rows="7"
                                maxlength="10000"
                                class="mt-2 w-full
                                       rounded-xl border
                                       border-slate-200
                                       px-4 py-3 text-sm
                                       outline-none
                                       focus:border-violet-300
                                       focus:ring-4
                                       focus:ring-violet-100"
                                placeholder="Write feedback for the student..."
                            >{{ old('feedback', $submission->feedback) }}</textarea>

                        </div>


                        {{-- Buttons --}}
                        <div class="grid gap-3">

                            <button
                                type="submit"
                                name="status"
                                value="graded"
                                class="inline-flex h-11
                                       items-center justify-center
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
                                Save Grade
                            </button>


                            <button
                                type="submit"
                                name="status"
                                value="returned"
                                class="inline-flex h-11
                                       items-center justify-center
                                       rounded-xl
                                       border border-amber-200
                                       bg-amber-50
                                       px-5 text-sm
                                       font-semibold
                                       text-amber-700
                                       transition
                                       hover:bg-amber-100"
                            >
                                Return for Revision
                            </button>

                        </div>

                    </form>

                </section>

            </div>

        </div>

    </main>

</div>

</x-layouts::app>