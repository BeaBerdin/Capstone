<x-layouts::app :title="__('Assignments')">
@include('partials.soft3d')
<div class="space-y-6 px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

<x-pw.page-header title="Assignments">
    <x-slot:actions>
        <a href="{{ route('assignments.create') }}"
           class="sad-btn-primary inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">
            Add Assignment
        </a>
    </x-slot:actions>
</x-pw.page-header>

@if(session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        {{ session('success') }}
    </div>
@endif

<div class="sad-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
<div class="overflow-x-auto">
<table class="w-full min-w-[820px] text-left text-sm">
    <thead class="border-b border-slate-200 bg-slate-50">
        <tr>
            <th class="pw-table-head px-5 py-3">Course</th>
            <th class="pw-table-head px-5 py-3">Lesson</th>
            <th class="pw-table-head px-5 py-3">Title</th>
            <th class="pw-table-head px-5 py-3">Due Date</th>
            <th class="pw-table-head px-5 py-3">Max Score</th>
            <th class="pw-table-head px-5 py-3">Status</th>
            <th class="pw-table-head px-5 py-3">Actions</th>
        </tr>
    </thead>

<tbody class="divide-y divide-slate-100">
    @forelse($assignments as $assignment)
        <tr class="transition hover:bg-slate-50/70">
            <td class="px-5 py-4 text-slate-700">
                {{ $assignment->course->title ?? 'No course' }}
            </td>

            <td class="px-5 py-4 text-slate-700">
                {{ $assignment->lesson->title ?? 'No lesson' }}
            </td>

            <td class="px-5 py-4 font-medium text-slate-900">
                {{ $assignment->title }}
            </td>

            <td class="px-5 py-4 whitespace-nowrap text-slate-600">
                {{ $assignment->due_date
                    ? \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y')
                    : 'No due date'
                }}
            </td>

            <td class="px-5 py-4 text-slate-700">
                {{ $assignment->max_score }}
            </td>

            <td class="px-5 py-4">
                <x-pw.badge :tone="$assignment->is_published ? 'success' : 'neutral'">{{ $assignment->is_published ? 'Published' : 'Draft' }}</x-pw.badge>
            </td>

            <td class="px-5 py-4">
                <div class="flex items-center gap-4 whitespace-nowrap">
                    <a href="{{ route('assignments.edit', $assignment) }}"
                       class="text-sm font-semibold text-violet-600 hover:text-violet-700">
                        Edit
                    </a>

                    <form action="{{ route('assignments.destroy', $assignment) }}"
                          method="POST"
                          class="inline">

                        @csrf
                        @method('DELETE')

                        <button onclick="return confirm('Delete this assignment?')"
                                class="text-sm font-semibold text-red-600 hover:text-red-700">
                            Delete
                        </button>

                    </form>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="7">
                <x-pw.empty-state title="No assignments found." />
            </td>
        </tr>
    @endforelse
</tbody>

</table>
</div>
</div>

</div>

</x-layouts::app>
