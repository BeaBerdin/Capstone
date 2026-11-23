<x-layouts::app :title="$assignment->title">
@include('partials.soft3d')

{{-- Administrator - assignment details (route: assignments.show) --}}

<div class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">
<div class="mx-auto max-w-3xl space-y-6">

    <a href="{{ route('assignments.index') }}" class="text-sm font-semibold text-violet-600 hover:text-violet-700">
        &larr; Back to Assignments
    </a>

    <div class="sad-card rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

        <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $assignment->title }}</h1>

        <dl class="mt-5 grid gap-4 text-sm text-slate-800 sm:grid-cols-2">
            <div>
                <dt class="font-medium text-slate-500">Course</dt>
                <dd>{{ $assignment->course?->title ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-slate-500">Lesson</dt>
                <dd>{{ $assignment->lesson?->title ?? 'Whole course' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-slate-500">Maximum score</dt>
                <dd>{{ $assignment->max_score }}</dd>
            </div>
            <div>
                <dt class="font-medium text-slate-500">Status</dt>
                <dd>{{ $assignment->is_published ? 'Published' : 'Hidden' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-slate-500">AI evaluation</dt>
                <dd>{{ $assignment->ai_evaluation_enabled ? 'Enabled' : 'Disabled' }}</dd>
            </div>
        </dl>

        @if($assignment->description)
            <div class="mt-6">
                <h2 class="text-sm font-medium text-slate-500">Instructions</h2>
                <p class="mt-1 whitespace-pre-line text-sm">{{ $assignment->description }}</p>
            </div>
        @endif

        @if(! $assignment->is_activity)
            <div class="mt-6">
                <a href="{{ route('assignments.edit', $assignment) }}"
                   class="sad-btn-primary inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">
                    Edit Assignment
                </a>
            </div>
        @endif

    </div>

</div>
</div>

</x-layouts::app>
