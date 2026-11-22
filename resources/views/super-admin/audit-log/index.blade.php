<x-layouts::app :title="__('Audit Log')">

    <div class="space-y-6 px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <section class="overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">
            <div class="px-6 py-6 sm:px-7">
                <p class="text-sm font-medium text-violet-600">EDP Security</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">Audit Log</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-500">
                    Role changes, manual email verification, account activation and application decisions.
                    Entries are read-only and cannot be edited or deleted.
                </p>
            </div>
        </section>

        {{-- Filters --}}
        <form method="GET" action="{{ route('super-admin.audit-log.index') }}" class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-6" data-test="audit-filters">
            <label class="text-xs font-semibold text-slate-600 lg:col-span-1">
                Action
                <select name="action" class="mt-1 h-10 w-full rounded-lg border border-slate-200 px-2 text-sm text-slate-700">
                    <option value="">All actions</option>
                    @foreach ($actions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['action'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-xs font-semibold text-slate-600 lg:col-span-1">
                Done by
                <select name="actor" class="mt-1 h-10 w-full rounded-lg border border-slate-200 px-2 text-sm text-slate-700">
                    <option value="">Anyone</option>
                    @foreach ($actors as $actor)
                        <option value="{{ $actor->id }}" @selected($filters['actor'] === $actor->id)>{{ $actor->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-xs font-semibold text-slate-600 lg:col-span-2">
                User affected (name or email)
                <input type="text" name="target" value="{{ $filters['target'] }}" maxlength="100" class="mt-1 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-700">
            </label>
            <label class="text-xs font-semibold text-slate-600">
                From
                <input type="date" name="from" value="{{ $filters['from'] }}" class="mt-1 h-10 w-full rounded-lg border border-slate-200 px-2 text-sm text-slate-700">
            </label>
            <label class="text-xs font-semibold text-slate-600">
                To
                <input type="date" name="to" value="{{ $filters['to'] }}" class="mt-1 h-10 w-full rounded-lg border border-slate-200 px-2 text-sm text-slate-700">
            </label>
            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-6">
                <button type="submit" class="h-10 rounded-lg bg-violet-600 px-4 text-sm font-semibold text-white hover:bg-violet-700">Filter</button>
                <a href="{{ route('super-admin.audit-log.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">Clear</a>
            </div>
        </form>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            @if ($logs->isEmpty())
                <p class="px-6 py-14 text-center text-sm text-slate-500">No audit entries match these filters.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[860px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/70 text-xs text-slate-500">
                                <th class="px-5 py-3 font-medium">When (PH time)</th>
                                <th class="px-3 py-3 font-medium">Action</th>
                                <th class="px-3 py-3 font-medium">Done by</th>
                                <th class="px-3 py-3 font-medium">User affected</th>
                                <th class="px-3 py-3 font-medium">Change</th>
                                <th class="px-3 py-3 font-medium">Reason</th>
                                <th class="px-5 py-3 font-medium">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($logs as $log)
                                <tr class="align-top" data-test="audit-row">
                                    <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-600">{{ $log->created_at?->timezone('Asia/Manila')->format('M d, Y g:i A') }}</td>
                                    <td class="px-3 py-3"><span class="inline-flex rounded-full bg-violet-50 px-2.5 py-0.5 text-[11px] font-semibold text-violet-700">{{ $log->actionLabel() }}</span></td>
                                    <td class="px-3 py-3 text-slate-700">{{ $log->actor?->name ?? '—' }}</td>
                                    <td class="px-3 py-3">
                                        <p class="text-slate-800">{{ $log->target?->name ?? '—' }}</p>
                                        <p class="text-xs text-slate-500">{{ $log->target?->email }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-xs text-slate-600">
                                        @if (filled($log->old_value) || filled($log->new_value))
                                            {{ $log->old_value ?? '—' }} <span class="text-slate-400">→</span> <span class="font-semibold text-slate-800">{{ $log->new_value ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="max-w-xs whitespace-pre-line px-3 py-3 text-xs text-slate-600">{{ $log->reason ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-500">{{ $log->ip_address ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($logs->hasPages())
                    <div class="border-t border-slate-100 px-5 py-3">{{ $logs->links() }}</div>
                @endif
            @endif
        </section>

    </div>

</x-layouts::app>
