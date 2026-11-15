<x-layouts::app :title="__('Student Applications')">
@include('partials.soft3d')

    <div class="space-y-6 px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        {{-- Header --}}
        <section class="sad-banner overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">
            <div class="relative px-6 py-6 sm:px-7">
                <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full bg-violet-100/70 blur-3xl"></div>

                <div class="relative">
                    <p class="text-sm font-medium text-violet-600">EDP Verification</p>
                    <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">Student Applications</h1>
                    <p class="mt-2 max-w-2xl text-sm text-slate-500">
                        Review each new student's Study Load, then approve or reject the application.
                        Approved students receive an email to verify their address before they can use PathWise.
                    </p>
                </div>
            </div>
        </section>

        @foreach (['success' => 'border-emerald-200 bg-emerald-50 text-emerald-700', 'warning' => 'border-amber-200 bg-amber-50 text-amber-800', 'error' => 'border-red-200 bg-red-50 text-red-700'] as $flash => $classes)
            @if (session($flash))
                <div class="rounded-xl border px-4 py-3 text-sm font-medium {{ $classes }}" role="status">{{ session($flash) }}</div>
            @endif
        @endforeach

        {{-- Tabs --}}
        <nav class="flex flex-wrap gap-2" aria-label="Application status">
            @foreach ($tabs as $key => $label)
                @php $active = $tab === $key; @endphp
                <a
                    href="{{ route('super-admin.student-applications.index', ['status' => $key]) }}"
                    class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition {{ $active ? 'border-violet-600 bg-violet-600 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-violet-200 hover:text-violet-700' }}"
                    @if ($active) aria-current="page" @endif
                    data-test="tab-{{ $key }}"
                >
                    {{ $label }}
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $active ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ (int) ($counts[$key] ?? 0) }}</span>
                </a>
            @endforeach
        </nav>

        {{-- List --}}
        <section class="sad-card overflow-hidden rounded-2xl border border-slate-200 bg-white">
            @if ($applications->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-sm font-semibold text-slate-800">No {{ strtolower($tabs[$tab]) }} applications.</p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ $tab === 'pending' ? 'New student registrations will appear here.' : 'Decided applications will appear here.' }}
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/70 text-xs text-slate-500">
                                <th class="px-5 py-3 font-medium sm:px-6">Student</th>
                                <th class="px-3 py-3 font-medium">Submission</th>
                                <th class="px-3 py-3 font-medium">Submitted</th>
                                <th class="px-3 py-3 font-medium">{{ $tab === 'pending' ? 'Status' : 'Decided by' }}</th>
                                <th class="px-5 py-3 text-right font-medium sm:px-6"><span class="sr-only">Open</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($applications as $application)
                                <tr class="transition-colors hover:bg-violet-50/40">
                                    <td class="px-5 py-3 sm:px-6">
                                        <p class="font-semibold text-slate-800">{{ $application->user?->name ?? 'Unknown student' }}</p>
                                        <p class="text-xs text-slate-500">{{ $application->user?->email }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-slate-600">
                                        #{{ $application->submission_no }}
                                        @if ($application->submission_no > 1)
                                            <span class="ml-1 rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-semibold text-violet-700">Resubmitted</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-slate-500">{{ $application->submitted_at?->format('M d, Y g:i A') }}</td>
                                    <td class="px-3 py-3">
                                        @if ($tab === 'pending')
                                            <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700">Pending review</span>
                                        @else
                                            <span class="text-slate-600">{{ $application->reviewer?->name ?? '—' }}</span>
                                            <span class="block text-xs text-slate-400">{{ $application->reviewed_at?->format('M d, Y') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right sm:px-6">
                                        <a href="{{ route('super-admin.student-applications.show', $application) }}"
                                           class="inline-flex items-center rounded-lg border border-violet-200 px-3 py-1.5 text-xs font-semibold text-violet-700 transition hover:bg-violet-50">
                                            {{ $tab === 'pending' ? 'Review' : 'View' }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($applications->hasPages())
                    <div class="border-t border-slate-100 px-5 py-3 sm:px-6">{{ $applications->links() }}</div>
                @endif
            @endif
        </section>

    </div>

</x-layouts::app>
