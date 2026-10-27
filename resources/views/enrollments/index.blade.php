<x-layouts::app title="Enrollments">
@include('partials.soft3d')
<div class="space-y-6 px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

    {{-- Header card, as on My Certificates. --}}
    <section class="sad-banner overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">
        <div class="relative px-6 py-6 sm:px-7">
            <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full bg-violet-100/70 blur-3xl"></div>

    <x-pw.page-header title="Enrollments" class="relative">
        <x-slot:actions>
            <a href="{{ route('enrollments.create') }}"
               class="sad-btn-primary inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">
                Add Enrollment
            </a>
        </x-slot:actions>
    </x-pw.page-header>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="sad-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="pw-table-head px-5 py-3">Student</th>
                        <th class="pw-table-head px-5 py-3">Course</th>
                        <th class="pw-table-head px-5 py-3">Status</th>
                        <th class="pw-table-head px-5 py-3">Progress</th>
                        <th class="pw-table-head px-5 py-3">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($enrollments as $enrollment)
                        @php
                            $statusTone = match ($enrollment->status) {
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'violet',
                            };
                        @endphp

                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-4 font-medium text-slate-900">
                                {{ $enrollment->student->name }}
                            </td>

                            <td class="px-5 py-4 text-slate-700">
                                {{ $enrollment->course->title }}
                            </td>

                            <td class="px-5 py-4">
                                <x-pw.badge :tone="$statusTone" class="capitalize">{{ $enrollment->status }}</x-pw.badge>
                            </td>

                            <td class="px-5 py-4 text-slate-700">
                                {{ $enrollment->progress_percentage }}%
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2 whitespace-nowrap">
                                    <a href="{{ route('enrollments.edit', $enrollment) }}"
                                       class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition hover:border-violet-200 hover:text-violet-700">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"></path></svg>
                                        Edit
                                    </a>

                                    <form action="{{ route('enrollments.destroy', $enrollment) }}"
                                          method="POST"
                                          onsubmit="return confirm('Delete this enrollment?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-rose-100 bg-white px-3 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"></path></svg>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-pw.empty-state title="No enrollments yet." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

</div>

</x-layouts::app>
