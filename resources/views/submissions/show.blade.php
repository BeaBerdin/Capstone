<x-layouts::app :title="__('Submission')">

{{-- Administrator - submission details (route: submissions.show) --}}

<div class="mx-auto max-w-3xl space-y-6">

    <a href="{{ route('submissions.index') }}" class="text-sm font-semibold text-blue-600 hover:underline">
        &larr; Back to Submissions
    </a>

    <div class="rounded-xl border bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">

        <h1 class="text-2xl font-bold">{{ $submission->assignment?->title ?? 'Submission' }}</h1>

        <p class="mt-1 text-sm text-gray-500">
            {{ $submission->assignment?->course?->title }}
        </p>

        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
            <div>
                <dt class="font-semibold text-gray-500">Student</dt>
                <dd>{{ $submission->student?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-500">Status</dt>
                <dd>{{ ucfirst((string) $submission->status) }}</dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-500">Submitted</dt>
                <dd>{{ $submission->submitted_at?->format('M d, Y h:i A') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-500">Score</dt>
                <dd>
                    {{ $submission->score !== null ? $submission->score . ' / ' . ($submission->assignment?->max_score ?? '?') : 'Not graded yet' }}
                </dd>
            </div>
        </dl>

        @if($submission->answer_text)
            <div class="mt-6">
                <h2 class="text-sm font-semibold text-gray-500">Answer</h2>
                <p class="mt-1 whitespace-pre-line rounded border bg-gray-50 p-3 text-sm dark:bg-neutral-800">{{ $submission->answer_text }}</p>
            </div>
        @endif

        @if($submission->file_path)
            <p class="mt-4 text-sm">
                <a href="{{ asset('storage/' . $submission->file_path) }}" target="_blank" rel="noopener"
                   class="font-semibold text-blue-600 hover:underline">
                    Open attached file
                </a>
            </p>
        @endif

        @if($submission->feedback)
            <div class="mt-6">
                <h2 class="text-sm font-semibold text-gray-500">Teacher feedback</h2>
                <p class="mt-1 whitespace-pre-line text-sm">{{ $submission->feedback }}</p>
            </div>
        @endif

        @if($submission->ai_feedback)
            <div class="mt-6">
                <h2 class="text-sm font-semibold text-gray-500">AI evaluation (reviewed by the teacher)</h2>
                <p class="mt-1 whitespace-pre-line text-sm">{{ $submission->ai_feedback }}</p>
            </div>
        @endif

    </div>

</div>

</x-layouts::app>
