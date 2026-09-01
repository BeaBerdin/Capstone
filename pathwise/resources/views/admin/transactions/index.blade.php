<x-layouts::app :title="__('Manage Transactions')">
    <div class="space-y-6">

        <div>
            <h1 class="text-3xl font-bold text-black">Manage Transactions</h1>
            <p class="mt-1 text-sm text-gray-400">
                Review student payments, verify proof, and approve enrollments.
            </p>
        </div>

        @php
            $totalTransactions = $transactions->count();
            $pendingTransactions = $transactions->where('status', 'pending')->count();
            $approvedTransactions = $transactions->where('status', 'approved')->count();
            $rejectedTransactions = $transactions->where('status', 'rejected')->count();
            $approvedRevenue = $transactions->where('status', 'approved')->sum('amount');
        @endphp

        <div class="grid gap-4 md:grid-cols-4">

            <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
                <p class="text-sm text-gray-400">Total Transactions</p>
                <h2 class="mt-2 text-3xl font-bold text-white">
                    {{ $totalTransactions }}
                </h2>
                <p class="mt-1 text-xs text-gray-400">
                    All payment transactions
                </p>
            </div>

            <div class="rounded-2xl border border-yellow-500/40 bg-neutral-900 p-5">
                <p class="text-sm text-yellow-400">Pending Review</p>
                <h2 class="mt-2 text-3xl font-bold text-yellow-400">
                    {{ $pendingTransactions }}
                </h2>
                <p class="mt-1 text-xs text-gray-400">
                    Awaiting verification
                </p>
            </div>

            <div class="rounded-2xl border border-green-500/40 bg-neutral-900 p-5">
                <p class="text-sm text-green-400">Approved</p>
                <h2 class="mt-2 text-3xl font-bold text-green-400">
                    {{ $approvedTransactions }}
                </h2>
                <p class="mt-1 text-xs text-gray-400">
                    Successful payments
                </p>
            </div>

            <div class="rounded-2xl border border-purple-500/40 bg-neutral-900 p-5">
                <p class="text-sm text-purple-400">Approved Revenue</p>
                <h2 class="mt-2 text-3xl font-bold text-purple-400">
                    ₱{{ number_format($approvedRevenue, 2) }}
                </h2>
                <p class="mt-1 text-xs text-gray-400">
                    From approved transactions
                </p>
            </div>

        </div>

        @if(session('success'))
            <div class="rounded-xl border border-green-700/40 bg-green-950/40 px-4 py-3 text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-xl border border-red-700/40 bg-red-950/40 px-4 py-3 text-red-300">
                {{ session('error') }}
            </div>
        @endif

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 shadow-lg shadow-purple-950/10">

            <div class="flex flex-col gap-3 border-b border-neutral-700 px-6 py-4 md:flex-row md:items-center md:justify-between">

                <div>
                    <h2 class="text-lg font-semibold text-white">
                        Payment Verification Queue
                    </h2>

                    <p class="text-sm text-gray-400">
                        Review student payment transactions and proof of payment.
                    </p>
                </div>

                <div class="rounded-xl border border-neutral-700 bg-neutral-800 px-4 py-2 text-sm text-white">
                    {{ $pendingTransactions }} pending approval
                </div>

            </div>

            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead class="bg-neutral-800 text-xs uppercase tracking-wider text-white">
                        <tr>
                            <th class="px-6 py-4">Transaction</th>
                            <th class="px-6 py-4">Student</th>
                            <th class="px-6 py-4">Course</th>
                            <th class="px-6 py-4">Payment</th>
                            <th class="px-6 py-4">Reference</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Proof</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-neutral-800">

                        @forelse($transactions as $transaction)

                            @php
                                $status = strtolower($transaction->status);

                                $statusClass = match ($status) {
                                    'approved' => 'bg-green-500/15 text-green-400',
                                    'rejected' => 'bg-red-500/15 text-red-400',
                                    default => 'bg-yellow-500/15 text-yellow-400',
                                };
                            @endphp

                            <tr class="hover:bg-white/5">

                                {{-- Transaction --}}
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-white">
                                        {{ $transaction->transaction_no }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ $transaction->created_at?->format('M d, Y h:i A') }}
                                    </p>
                                </td>

                                {{-- Student --}}
                                <td class="px-6 py-4">

                                    <div class="flex items-center gap-3">

                                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-600/20 text-sm font-bold text-purple-300">
                                            {{ strtoupper(substr($transaction->student->name ?? 'S', 0, 1)) }}
                                        </div>

                                        <div>
                                            <p class="font-semibold text-white">
                                                {{ $transaction->student->name ?? 'Student unavailable' }}
                                            </p>

                                            <p class="text-xs text-gray-500">
                                                Learner
                                            </p>
                                        </div>

                                    </div>

                                </td>

                                {{-- Course --}}
                                <td class="px-6 py-4 text-gray-400">
                                    {{ $transaction->course->title ?? 'Course unavailable' }}
                                </td>

                                {{-- Payment --}}
                                <td class="px-6 py-4">

                                    <p class="font-semibold text-white">
                                        ₱{{ number_format($transaction->amount, 2) }}
                                    </p>

                                    <p class="text-xs text-gray-500">
                                        {{ $transaction->payment_method ?? 'No method yet' }}
                                    </p>

                                </td>

                                {{-- Reference --}}
                                <td class="px-6 py-4 text-gray-400">
                                    {{ $transaction->payment_reference ?? 'Not submitted' }}
                                </td>

                                {{-- Status --}}
                                <td class="px-6 py-4">

                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                        {{ ucfirst($transaction->status) }}
                                    </span>

                                </td>

                                {{-- Proof --}}
                                <td class="px-6 py-4">

                                    @if($transaction->payment_proof)

                                        <a href="{{ asset('storage/' . $transaction->payment_proof) }}"
                                           target="_blank"
                                           class="rounded-lg border border-blue-500/30 bg-blue-500/10 px-3 py-1.5 text-xs font-semibold text-blue-400 hover:bg-blue-500/20">
                                            View Proof
                                        </a>

                                    @else

                                        <span class="rounded-full bg-gray-500/15 px-3 py-1 text-xs font-semibold text-gray-400">
                                            No proof
                                        </span>

                                    @endif

                                </td>

                                {{-- Action --}}
                                <td class="px-6 py-4">

                                    <div class="flex justify-end gap-2">

                                        @if($transaction->status === 'pending')

                                            {{-- APPROVE --}}
                                            <form action="{{ route('super_admin.transactions.approve', $transaction) }}"
                                                  method="POST">

                                                @csrf

                                                <button type="submit"
                                                        onclick="return confirm('Approve this transaction and enroll the student?')"
                                                        class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700">
                                                    Approve
                                                </button>

                                            </form>

                                            {{-- REJECT --}}
                                            <form action="{{ route('super_admin.transactions.reject', $transaction) }}"
                                                  method="POST">

                                                @csrf

                                                <button type="submit"
                                                        onclick="return confirm('Reject this transaction?')"
                                                        class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">
                                                    Reject
                                                </button>

                                            </form>

                                        @elseif($transaction->status === 'approved')

                                            <span class="rounded-full bg-green-500/15 px-3 py-1 text-xs font-semibold text-green-400">
                                                ✓ Completed
                                            </span>

                                        @elseif($transaction->status === 'rejected')

                                            <span class="rounded-full bg-red-500/15 px-3 py-1 text-xs font-semibold text-red-400">
                                                ✕ Rejected
                                            </span>

                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="8" class="px-6 py-16 text-center">

                                    <h3 class="text-lg font-semibold text-white">
                                        No transactions available
                                    </h3>

                                    <p class="mt-1 text-sm text-gray-400">
                                        Student payment transactions will appear here.
                                    </p>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>
</x-layouts::app>