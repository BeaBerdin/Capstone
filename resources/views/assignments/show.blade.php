<x-layouts::app :title="$assignment->title">

{{-- Administrator - assignment details (route: assignments.show) --}}

<div class="mx-auto max-w-3xl space-y-6">

    <a href="{{ route('assignments.index') }}" class="text-sm font-semibold text-blue-600 hover:underline">
        &larr; Back to Assignments
    </a>

    <div class="rounded-xl border bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">

        <h1 class="text-2xl font-bold">{{ $assignment->title }}</h1>

        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
            <div>
                <dt class="font-semibold text-gray-500">Course</dt>
                <dd>{{ $assignment->course?->title ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-500">Lesson</dt>
                <dd>{{ $assignment->lesson?->title ?? 'Whole course' }}</dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-500">Due date</dt>
                <dd>{{ $assignment->due_date ? \Illuminate\Support\Carbon::parse($assignment->due_date)->format('M d, Y h:i A') : 'No due date' }}</dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-500">Maximum score</dt>
                <dd>{{ $assignment->max_score }}</dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-500">Status</dt>
                <dd>{{ $assignment->is_published ? 'Published' : 'Hidden' }}</dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-500">AI evaluation</dt>
                <dd>{{ $assignment->ai_evaluation_enabled ? 'Enabled' : 'Disabled' }}</dd>
            </div>
        </dl>

        @if($assignment->description)
            <div class="mt-6">
                <h2 class="text-sm font-semibold text-gray-500">Instructions</h2>
                <p class="mt-1 whitespace-pre-line text-sm">{{ $assignment->description }}</p>
            </div>
        @endif

        <div class="mt-6">
            <a href="{{ route('assignments.edit', $assignment) }}"
               class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white">
                Edit Assignment
            </a>
        </div>

    </div>

</div>

</x-layouts::app>
