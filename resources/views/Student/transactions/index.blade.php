<x-layouts::app :title="'My Transactions'">

@php
    $totalTransactions = $transactions->count();
    $approvedTransactions = $transactions->where('status', 'approved')->count();
    $pendingTransactions = $transactions->where('status', 'pending')->count();
    $rejectedTransactions = $transactions->where('status', 'rejected')->count();
@endphp

<div class="space-y-6">

    <div class="rounded-2xl border border-purple-500/30 bg-linear-to-r from-purple-900 via-neutral-900 to-neutral-900 p-6">
        <h1 class="text-3xl font-bold text-white">
            My Transactions
        </h1>

        <p class="mt-2 text-gray-400">
            Track your course purchases, uploaded payment receipts, and approval status.
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-4">

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
            <p class="text-sm text-gray-400">Total Transactions</p>
            <h2 class="mt-2 text-3xl font-bold text-white">{{ $totalTransactions }}</h2>
        </div>

        <div class="rounded-2xl border border-green-500/40 bg-green-400/10 p-5">
            <p class="text-sm text-white">Approved</p>
            <h2 class="mt-2 text-3xl font-bold text-white">{{ $approvedTransactions }}</h2>
        </div>

        <div class="rounded-2xl border border-yellow-500/40 bg-yellow-500/10 p-5">
            <p class="text-sm text-black">Pending</p>
            <h2 class="mt-2 text-3xl font-bold text-black">{{ $pendingTransactions }}</h2>
        </div>

        <div class="rounded-2xl border border-red-500/40 bg-red-500/10 p-5">
            <p class="text-sm text-black">Rejected</p>
            <h2 class="mt-2 text-3xl font-bold text-black">{{ $rejectedTransactions }}</h2>
        </div>

    </div>

    <div class="grid gap-5 lg:grid-cols-2">

        @forelse($transactions as $transaction)

            <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-6 shadow-sm">

                <div class="flex items-start justify-between gap-4">

                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">
                            Transaction No.
                        </p>

                        <h2 class="mt-1 text-lg font-bold text-white">
                            {{ $transaction->transaction_no }}
                        </h2>
                    </div>

                    @if($transaction->status === 'approved')
                        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">
                            Approved
                        </span>
                    @elseif($transaction->status === 'rejected')
                        <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">
                            Rejected
                        </span>
                    @else
                        <span class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-bold text-yellow-700">
                            Pending Verification
                        </span>
                    @endif

                </div>

                <div class="mt-5 rounded-xl bg-neutral-800 p-4">
                    <p class="text-sm text-gray-400">Course Purchased</p>

                    <h3 class="mt-1 text-lg font-semibold text-white">
                        {{ $transaction->course->title ?? 'Course unavailable' }}
                    </h3>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-3">

                    <div class="rounded-xl bg-neutral-800 p-4">
                        <p class="text-xs text-gray-400">Amount</p>
                        <p class="mt-1 font-bold text-white">
                            ₱{{ number_format($transaction->amount, 2) }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-neutral-800 p-4">
                        <p class="text-xs text-gray-400">Method</p>
                        <p class="mt-1 font-bold text-white">
                            {{ $transaction->payment_method ?? 'Not submitted' }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-neutral-800 p-4">
                        <p class="text-xs text-gray-400">Reference</p>
                        <p class="mt-1 font-bold text-white">
                            {{ $transaction->payment_reference ?? 'Not submitted' }}
                        </p>
                    </div>

                </div>

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

            <div class="col-span-full rounded-2xl border border-neutral-700 bg-neutral-900 p-10 text-center">
                <h3 class="text-xl font-bold text-white">
                    No transactions yet
                </h3>

                <p class="mt-2 text-gray-400">
                    Your course purchases and payment records will appear here.
                </p>

                <a href="{{ route('student.marketplace') }}"
                   class="mt-5 inline-block rounded-xl bg-purple-600 px-5 py-2 text-white hover:bg-purple-700">
                    Browse Courses
                </a>
            </div>

        @endforelse

    </div>

</div>

</x-layouts::app>