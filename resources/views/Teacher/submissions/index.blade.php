<x-layouts::app :title="'Submissions - ' . $assignment->title">
@php
    $total = $submissions->count();
    $submitted = $submissions->where('status', 'submitted')->count();
    $graded = $submissions->where('status', 'graded')->count();
    $returned = $submissions->where('status', 'returned')->count();
@endphp

<div class="min-h-screen bg-[#f8f9fc]">
    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">
        <div class="mx-auto max-w-[1500px]">

            <div class="mb-5 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                <a
                    href="{{ route('teacher.assignments.index') }}"
                    class="font-medium hover:text-violet-600"
                >
                    Assignments
                </a>

                <span>›</span>

                <span class="font-semibold text-slate-600">
                    Submissions
                </span>
            </div>

            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                        Output Evaluation
                    </p>

                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                        Student Submissions
                    </h1>

                    <p class="mt-2 text-sm text-slate-500">
                        {{ $assignment->title }}

                        <span class="mx-1 text-slate-300">
                            •
                        </span>

                        {{ $assignment->course?->title }}
                    </p>
                </div>

                <a
                    href="{{ route('teacher.assignments.index') }}"
                    class="inline-flex h-10 items-center justify-center rounded-xl
                           border border-slate-200 bg-white px-4 text-sm
                           font-semibold text-slate-700 shadow-sm
                           hover:bg-slate-50"
                >
                    Back to Assignments
                </a>
            </div>

            @if(session('success'))
                <div
                    class="mt-6 rounded-2xl border border-emerald-200
                           bg-emerald-50 px-5 py-4 text-sm font-medium
                           text-emerald-700"
                >
                    ✓ {{ session('success') }}
                </div>
            @endif

            <section class="mt-7 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        Total
                    </p>

                    <p class="mt-2 text-2xl font-bold text-slate-950">
                        {{ $total }}
                    </p>
                </div>

                <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-blue-600">
                        Submitted
                    </p>

                    <p class="mt-2 text-2xl font-bold text-blue-700">
                        {{ $submitted }}
                    </p>
                </div>

                <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-600">
                        Graded
                    </p>

                    <p class="mt-2 text-2xl font-bold text-emerald-700">
                        {{ $graded }}
                    </p>
                </div>

                <div class="rounded-2xl border border-amber-100 bg-amber-50 p-5">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-amber-600">
                        Returned
                    </p>

                    <p class="mt-2 text-2xl font-bold text-amber-700">
                        {{ $returned }}
                    </p>
                </div>

            </section>

            <section
                class="mt-6 overflow-hidden rounded-2xl
                       border border-slate-200 bg-white shadow-sm"
            >
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <h2 class="text-sm font-bold text-slate-800">
                        Submissions
                    </h2>

                    <p class="mt-1 text-xs text-slate-400">
                        Maximum score: {{ $assignment->max_score }}

                        @if($assignment->due_date)
                            <span class="mx-1">
                                •
                            </span>

                            Due {{ $assignment->due_date->format('M d, Y · h:i A') }}
                        @endif
                    </p>
                </div>

                @forelse($submissions as $submission)

                    @php
                        $statusClass = match($submission->status) {
                            'graded' =>
                                'bg-emerald-50 text-emerald-700',

                            'returned' =>
                                'bg-amber-50 text-amber-700',

                            default =>
                                'bg-blue-50 text-blue-700',
                        };
                    @endphp

                    <div
                        class="border-b border-slate-100 px-5 py-5
                               last:border-b-0 sm:px-6"
                    >
                        <div
                            class="flex flex-col gap-4
                                   lg:flex-row lg:items-center
                                   lg:justify-between"
                        >
                            <div class="min-w-0">

                                <div class="flex flex-wrap items-center gap-2">

                                    <h3 class="text-sm font-bold text-slate-900">
                                        {{ $submission->student?->name ?? 'Unknown Student' }}
                                    </h3>

                                    <span
                                        class="rounded-full px-2.5 py-1
                                               text-[11px] font-semibold
                                               {{ $statusClass }}"
                                    >
                                        {{ ucfirst($submission->status) }}
                                    </span>

                                </div>

                                <div
                                    class="mt-2 flex flex-wrap
                                           gap-x-5 gap-y-1
                                           text-xs text-slate-500"
                                >
                                    <span>
                                        Submitted:

                                        <strong class="text-slate-700">
                                            {{
                                                $submission->submitted_at
                                                    ?->format('M d, Y · h:i A')
                                                ?? '—'
                                            }}
                                        </strong>
                                    </span>

                                    @if($submission->status === 'graded')
                                        <span>
                                            Score:

                                            <strong class="text-slate-700">
                                                {{ $submission->score }}
                                                /
                                                {{ $assignment->max_score }}
                                            </strong>
                                        </span>
                                    @endif

                                    @if($submission->file_path)
                                        <span class="font-medium text-violet-600">
                                            File attached
                                        </span>
                                    @endif
                                </div>

                                @if($submission->answer_text)
                                    <p
                                        class="mt-3 line-clamp-2 max-w-3xl
                                               text-sm leading-6
                                               text-slate-500"
                                    >
                                        {{ $submission->answer_text }}
                                    </p>
                                @endif

                            </div>

                            <a
                                href="{{ route('teacher.submissions.show', $submission) }}"
                                class="inline-flex h-9 shrink-0 items-center
                                       justify-center rounded-xl
                                       border border-violet-200
                                       bg-violet-50 px-4
                                       text-xs font-semibold
                                       text-violet-700 transition
                                       hover:bg-violet-100"
                            >
                                Review Submission
                            </a>

                        </div>
                    </div>

                @empty

                    <div class="px-6 py-16 text-center">

                        <div
                            class="mx-auto flex h-14 w-14
                                   items-center justify-center
                                   rounded-2xl bg-violet-50"
                        >
                            <svg
                                class="h-6 w-6 text-violet-600"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path d="M9 12h6M9 16h4"/>
                                <path d="M7 3h7l5 5v13H7z"/>
                            </svg>
                        </div>

                        <h3 class="mt-4 text-base font-bold text-slate-800">
                            No submissions yet
                        </h3>

                        <p class="mt-2 text-sm text-slate-500">
                            Student submissions will appear here after enrolled
                            students submit this assignment.
                        </p>

                    </div>

                @endforelse

            </section>

        </div>
    </main>
</div>
</x-layouts::app>