<x-layouts::app :title="__('Submissions')">
@include('partials.soft3d')

<style>
    .pw-card {
        background: #ffffff;
        border: 1px solid #e7e9ef;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.035);
    }
</style>

@php
    $statusStyles = [
        'graded' => ['bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
        'returned' => ['bg-amber-50 text-amber-700', 'bg-amber-500'],
        'submitted' => ['bg-blue-50 text-blue-700', 'bg-blue-500'],
    ];
@endphp

<div class="sad-page min-h-screen bg-[#f8f9fc]">

    <main class="px-4 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-[1500px]">

            {{-- =====================================================
                HEADER
            ====================================================== --}}

            <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">

                <div>
                    <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                        Assessments
                    </p>

                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                        Submissions
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                        All assignment submissions across PathWise courses.
                    </p>
                </div>

            </div>


            @if(session('success'))
                <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif


            {{-- =====================================================
                TABLE
            ====================================================== --}}

            <section class="sad-card pw-card mt-7 overflow-hidden">

                <div class="overflow-x-auto">

                    <table class="w-full min-w-[760px] text-left text-sm">

                        <thead class="border-b border-slate-100 bg-slate-50/80">
                            <tr class="text-xs font-bold uppercase tracking-[.08em] text-slate-500">
                                <th class="px-6 py-4">Student</th>
                                <th class="px-6 py-4">Assignment</th>
                                <th class="px-6 py-4">Score</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Submitted At</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @forelse($submissions as $submission)

                                @php
                                    [$badge, $dot] = $statusStyles[$submission->status] ?? ['bg-slate-100 text-slate-600', 'bg-slate-400'];
                                @endphp

                                <tr class="transition hover:bg-slate-50/70">

                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700">
                                                {{ strtoupper(substr($submission->student->name ?? 'S', 0, 1)) }}
                                            </div>
                                            <p class="text-sm font-semibold text-slate-800">
                                                {{ $submission->student->name ?? 'N/A' }}
                                            </p>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5 text-sm text-slate-600">
                                        {{ $submission->assignment->title ?? 'N/A' }}
                                    </td>

                                    <td class="px-6 py-5 text-sm font-semibold text-slate-800">
                                        {{ $submission->score ?? '-' }}
                                    </td>

                                    <td class="px-6 py-5">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold {{ $badge }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>
                                            {{ ucfirst($submission->status) }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-5 text-xs font-semibold text-slate-600">
                                        {{ $submission->submitted_at
                                            ? \Carbon\Carbon::parse($submission->submitted_at)->format('M d, Y')
                                            : '-'
                                        }}
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="flex items-center justify-end gap-2">

                                            <a
                                                href="{{ route('submissions.show', $submission) }}"
                                                class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition hover:border-violet-200 hover:text-violet-700"
                                            >
                                                View
                                            </a>


                                        </div>
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="6" class="px-6 py-16 text-center">

                                        <div class="sad-chip sad-violet mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/>
                                                <path d="M14 3v5h5M9 13h6M9 17h4"/>
                                            </svg>
                                        </div>

                                        <h3 class="mt-4 text-sm font-bold text-slate-800">
                                            No submissions found.
                                        </h3>

                                        <p class="mt-1 text-xs text-slate-500">
                                            Submissions appear here when students hand in assignments.
                                        </p>

                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </section>

        </div>

    </main>

</div>

</x-layouts::app>
