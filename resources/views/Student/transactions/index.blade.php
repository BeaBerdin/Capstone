<x-layouts::app :title="'My Transactions'">

@php
    $totalTransactions = $transactions->count();
    $approvedTransactions = $transactions->where('status', 'approved')->count();
    $pendingTransactions = $transactions->where('status', 'pending')->count();
    $rejectedTransactions = $transactions->where('status', 'rejected')->count();
@endphp

<div class="space-y-6">

    {{-- Hero Banner --}}
    <div class="rounded-2xl border border-purple-500/30 bg-linear-to-r from-purple-900 via-neutral-900 to-neutral-900 p-6">
        <h1 class="text-3xl font-bold text-white">
            My Transactions
        </h1>
        <p class="mt-2 text-sm text-gray-400">
            Track your course purchases, uploaded payment receipts, and approval status.
        </p>
    </div>

    {{-- Stats Row --}}
    <div class="grid gap-4 md:grid-cols-4">

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
            <p class="text-sm text-gray-400">Total Transactions</p>
            <h2 class="mt-2 text-3xl font-bold text-white">{{ $totalTransactions }}</h2>
            <p class="mt-1 text-xs text-gray-400">All purchases</p>
        </div>

        <div class="rounded-2xl border border-green-500/40 bg-neutral-900 p-5">
            <p class="text-sm text-green-400">Approved</p>
            <h2 class="mt-2 text-3xl font-bold text-green-400">{{ $approvedTransactions }}</h2>
            <p class="mt-1 text-xs text-gray-400">Verified payments</p>
        </div>

        <div class="rounded-2xl border border-yellow-500/40 bg-neutral-900 p-5">
            <p class="text-sm text-yellow-400">Pending</p>
            <h2 class="mt-2 text-3xl font-bold text-yellow-400">{{ $pendingTransactions }}</h2>
            <p class="mt-1 text-xs text-gray-400">Awaiting review</p>
        </div>

        <div class="rounded-2xl border border-red-500/40 bg-neutral-900 p-5">
            <p class="text-sm text-red-400">Rejected</p>
            <h2 class="mt-2 text-3xl font-bold text-red-400">{{ $rejectedTransactions }}</h2>
            <p class="mt-1 text-xs text-gray-400">Declined payments</p>
        </div>

    </div>

    {{-- Transaction Cards --}}
    <div class="grid gap-5 lg:grid-cols-2">

        @forelse($transactions as $transaction)

            <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-6 shadow-lg shadow-purple-950/10">

                <div class="flex items-start justify-between gap-4">

                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">
                            Transaction No.
                        </p>
                        <h2 class="mt-1 text-lg font-bold text-white">
                            {{ $transaction->transaction_no }}
                        </h2>
                    </div>

                    {{-- ✅ DARK THEME BADGES --}}
                    @if($transaction->status === 'approved')
                        <span class="rounded-full bg-green-500/15 px-3 py-1 text-xs font-semibold text-green-400">
                            Approved
                        </span>
                    @elseif($transaction->status === 'rejected')
                        <span class="rounded-full bg-red-500/15 px-3 py-1 text-xs font-semibold text-red-400">
                            Rejected
                        </span>
                    @else
                        <span class="rounded-full bg-yellow-500/15 px-3 py-1 text-xs font-semibold text-yellow-400">
                            Pending Verification
                        </span>
                    @endif

                </div>

                {{-- Course Info --}}
                <div class="mt-5 rounded-xl border border-neutral-700 bg-neutral-800 p-4">
                    <p class="text-sm text-gray-400">Course Purchased</p>
                    <h3 class="mt-1 text-lg font-semibold text-white">
                        {{ $transaction->course->title ?? 'Course unavailable' }}
                    </h3>
                </div>

                {{-- Details Grid --}}
                <div class="mt-4 grid gap-3 md:grid-cols-3">

                    <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4">
                        <p class="text-xs text-gray-400">Amount</p>
                        <p class="mt-1 font-bold text-white">
                            ₱{{ number_format($transaction->amount, 2) }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4">
                        <p class="text-xs text-gray-400">Method</p>
                        <p class="mt-1 font-bold text-white">
                            {{ $transaction->payment_method ?? 'Not submitted' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4">
                        <p class="text-xs text-gray-400">Reference</p>
                        <p class="mt-1 font-bold text-white">
                            {{ $transaction->payment_reference ?? 'Not submitted' }}
                        </p>
                    </div>

                </div>

                {{-- Status Message + Action --}}
                <div class="mt-5 flex flex-wrap items-center justify-between gap-3">

                    <div class="text-sm text-gray-400">
                        @if($transaction->status === 'approved')
                            ✅ Payment approved. Course access granted.
                        @elseif($transaction->status === 'rejected')
                            ❌ Payment was rejected. Please review details.
                        @else
                            ⏳ Waiting for administrator verification.
                        @endif
                    </div>

                    <a href="{{ route('student.transactions.show', $transaction) }}"
                       class="rounded-xl bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700">
                        View Details
                    </a>

                </div>

            </div>

        @empty

            {{-- Empty State --}}
            <div class="col-span-full rounded-2xl border border-neutral-700 bg-neutral-900 p-10 text-center">
                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-purple-600/20 text-2xl mx-auto">
                    💳
                </div>

                <h3 class="text-xl font-bold text-white">
                    No transactions yet
                </h3>

                <p class="mt-2 text-sm text-gray-400">
                    Your course purchases and payment records will appear here.
                </p>

                <a href="{{ route('student.marketplace') }}"
                   class="mt-5 inline-block rounded-xl bg-purple-600 px-5 py-2 text-sm font-semibold text-white hover:bg-purple-700">
                    Browse Courses →
                </a>
            </div>

        @endforelse

    </div>

</div>

</x-layouts::app>