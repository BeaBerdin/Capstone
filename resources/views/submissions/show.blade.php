<x-layouts::app :title="__('Submission')">
@include('partials.soft3d')

{{-- Administrator - submission details (route: submissions.show) --}}

<style>
    .pw-card {
        background: #ffffff;
        border: 1px solid #e7e9ef;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.035);
    }
</style>

<div class="sad-page min-h-screen bg-[#f8f9fc]">

    <main class="px-4 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-3xl">

            <a href="{{ route('submissions.index') }}" class="text-xs font-semibold text-violet-600 hover:text-violet-700">
                &larr; Back to Submissions
            </a>

            <p class="mt-5 text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                {{ $submission->assignment?->course?->title ?? 'Assessments' }}
            </p>

            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                {{ $submission->assignment?->title ?? 'Submission' }}
            </h1>


            <section class="sad-card pw-card mt-7 p-5 sm:p-7">

                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                    <div class="rounded-xl bg-slate-50 px-4 py-3">
                        <dt class="text-xs font-bold uppercase tracking-[.08em] text-slate-500">Student</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $submission->student?->name ?? '—' }}</dd>
                    </div>

                    <div class="rounded-xl bg-slate-50 px-4 py-3">
                        <dt class="text-xs font-bold uppercase tracking-[.08em] text-slate-500">Status</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-800">{{ ucfirst((string) $submission->status) }}</dd>
                    </div>

                    <div class="rounded-xl bg-slate-50 px-4 py-3">
                        <dt class="text-xs font-bold uppercase tracking-[.08em] text-slate-500">Submitted</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $submission->submitted_at?->format('M d, Y h:i A') ?? '—' }}</dd>
                    </div>

                    <div class="rounded-xl bg-slate-50 px-4 py-3">
                        <dt class="text-xs font-bold uppercase tracking-[.08em] text-slate-500">Score</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-800">
                            {{ $submission->score !== null ? $submission->score . ' / ' . ($submission->assignment?->max_score ?? '?') : 'Not graded yet' }}
                        </dd>
                    </div>

                </dl>


                @if($submission->answer_text)
                    <div class="mt-7">
                        <h2 class="text-sm font-bold text-slate-900">Answer</h2>
                        {{-- Shown exactly as submitted: escaped, indentation and line breaks kept. --}}
                        <pre class="mt-2 overflow-x-auto whitespace-pre-wrap wrap-break-word rounded-xl border border-slate-200 bg-slate-50 p-4 font-mono text-sm leading-6 text-slate-800">{{ $submission->answer_text }}</pre>
                    </div>
                @endif


                @if($submission->file_path)
                    <a
                        href="{{ route('files.submission', $submission) }}"
                        target="_blank"
                        rel="noopener"
                        class="mt-6 inline-flex h-10 items-center gap-2 rounded-xl border border-violet-200 bg-white px-4 text-xs font-semibold text-violet-700 transition hover:bg-violet-50"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/>
                            <path d="M14 3v5h5"/>
                        </svg>
                        Open attached file
                    </a>
                @endif


                @if($submission->feedback)
                    <div class="mt-7">
                        <h2 class="text-sm font-bold text-slate-900">Teacher feedback</h2>
                        <p class="mt-2 whitespace-pre-line rounded-xl bg-slate-50 p-4 text-sm leading-6 text-slate-700">{{ $submission->feedback }}</p>
                    </div>
                @endif


                @if($submission->ai_feedback)
                    <div class="mt-7">
                        <h2 class="text-sm font-bold text-slate-900">AI evaluation (reviewed by the teacher)</h2>
                        <p class="mt-2 whitespace-pre-wrap wrap-break-word rounded-xl border border-violet-100 bg-violet-50/60 p-4 text-sm leading-6 text-slate-700">{{ $submission->ai_feedback }}</p>
                    </div>
                @endif

            </section>

        </div>

    </main>

</div>

</x-layouts::app>
