<x-layouts::app :title="__('Manage Transactions')">

    <div class="min-h-screen space-y-6">

        {{-- =====================================================
            HEADER
        ====================================================== --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

            <div>
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-blue-500">
                    Super Admin
                </p>

                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                    Manage Transactions
                </h1>

                <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                    Review student payments, verify proof of payment,
                    and approve or reject enrollment transactions.
                </p>
            </div>

        </div>


        @php
            $totalTransactions = $transactions->count();

            $pendingTransactions = $transactions
                ->where('status', 'pending')
                ->count();

            $approvedTransactions = $transactions
                ->where('status', 'approved')
                ->count();

            $rejectedTransactions = $transactions
                ->where('status', 'rejected')
                ->count();

            $approvedRevenue = $transactions
                ->where('status', 'approved')
                ->sum('amount');
        @endphp


        {{-- =====================================================
            STATISTICS
        ====================================================== --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            {{-- Total --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-start justify-between">

                    <div>
                        <p class="text-xs font-semibold text-slate-500">
                            Total Transactions
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-950">
                            {{ $totalTransactions }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            All payment transactions
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">

                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                            <path d="M3 10h18"></path>
                        </svg>

                    </div>

                </div>

            </div>


            {{-- Pending --}}
            <div class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm">

                <div class="flex items-start justify-between">

                    <div>
                        <p class="text-xs font-semibold text-amber-600">
                            Pending Review
                        </p>

                        <p class="mt-2 text-3xl font-bold text-amber-600">
                            {{ $pendingTransactions }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            Awaiting verification
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600">

                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M12 7v5l3 2"></path>
                        </svg>

                    </div>

                </div>

            </div>


            {{-- Approved --}}
            <div class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm">

                <div class="flex items-start justify-between">

                    <div>
                        <p class="text-xs font-semibold text-emerald-600">
                            Approved
                        </p>

                        <p class="mt-2 text-3xl font-bold text-emerald-600">
                            {{ $approvedTransactions }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            Successful payments
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">

                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M20 6 9 17l-5-5"></path>
                        </svg>

                    </div>

                </div>

            </div>


            {{-- Revenue --}}
            <div class="rounded-2xl border border-violet-200 bg-white p-5 shadow-sm">

                <div class="flex items-start justify-between">

                    <div>
                        <p class="text-xs font-semibold text-violet-600">
                            Approved Revenue
                        </p>

                        <p class="mt-2 text-2xl font-bold text-violet-600">
                            ₱{{ number_format($approvedRevenue, 2) }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            From approved transactions
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">

                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M12 1v22"></path>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
            ALERTS
        ====================================================== --}}

        @if(session('success'))

            <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">

                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                    ✓
                </div>

                <p class="text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </p>

            </div>

        @endif


        @if(session('error'))

            <div class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3">

                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">
                    !
                </div>

                <p class="text-sm font-medium text-red-700">
                    {{ session('error') }}
                </p>

            </div>

        @endif


        {{-- =====================================================
            TRANSACTION TABLE
        ====================================================== --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            {{-- Table Header --}}
            <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 md:flex-row md:items-center md:justify-between">

                <div>

                    <h2 class="text-lg font-bold text-slate-900">
                        Payment Verification Queue
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        Review submitted payment transactions and proof of payment.
                    </p>

                </div>

                <div class="inline-flex w-fit items-center gap-2 rounded-xl bg-amber-50 px-3 py-2">

                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>

                    <span class="text-xs font-semibold text-amber-700">
                        {{ $pendingTransactions }} pending approval
                    </span>

                </div>

            </div>


            {{-- Table --}}
            <div class="overflow-x-auto">

                <table class="w-full min-w-[1100px] text-left">

                    <thead class="border-b border-slate-100 bg-slate-50">

                        <tr class="text-[10px] font-bold uppercase tracking-[0.08em] text-slate-400">

                            <th class="px-6 py-4">
                                Transaction
                            </th>

                            <th class="px-6 py-4">
                                Student
                            </th>

                            <th class="px-6 py-4">
                                Course
                            </th>

                            <th class="px-6 py-4">
                                Payment
                            </th>

                            <th class="px-6 py-4">
                                Reference
                            </th>

                            <th class="px-6 py-4">
                                Status
                            </th>

                            <th class="px-6 py-4">
                                Proof
                            </th>

                            <th class="px-6 py-4 text-right">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @forelse($transactions as $transaction)

                            @php

                                $status = strtolower($transaction->status);

                                $statusClass = match ($status) {

                                    'approved' =>
                                        'bg-emerald-50 text-emerald-700',

                                    'rejected' =>
                                        'bg-red-50 text-red-600',

                                    default =>
                                        'bg-amber-50 text-amber-700',

                                };

                                $initial =
                                    strtoupper(
                                        substr(
                                            $transaction->student->name ?? 'S',
                                            0,
                                            1
                                        )
                                    );

                            @endphp


                            <tr class="transition hover:bg-slate-50/70">


                                {{-- TRANSACTION --}}
                                <td class="px-6 py-5">

                                    <p class="text-sm font-bold text-slate-800">
                                        {{ $transaction->transaction_no }}
                                    </p>

                                    <p class="mt-1 text-[10px] text-slate-400">
                                        {{ $transaction->created_at?->format('M d, Y h:i A') }}
                                    </p>

                                </td>


                                {{-- STUDENT --}}
                                <td class="px-6 py-5">

                                    <div class="flex items-center gap-3">

                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-xs font-bold text-violet-600">
                                            {{ $initial }}
                                        </div>

                                        <div class="min-w-0">

                                            <p class="truncate text-sm font-semibold text-slate-800">
                                                {{ $transaction->student->name ?? 'Student unavailable' }}
                                            </p>

                                            <p class="mt-1 text-[10px] text-slate-400">
                                                Learner
                                            </p>

                                        </div>

                                    </div>

                                </td>


                                {{-- COURSE --}}
                                <td class="px-6 py-5">

                                    <p class="max-w-[200px] truncate text-sm font-medium text-slate-700">
                                        {{ $transaction->course->title ?? 'Course unavailable' }}
                                    </p>

                                </td>


                                {{-- PAYMENT --}}
                                <td class="px-6 py-5">

                                    <p class="text-sm font-bold text-slate-800">
                                        ₱{{ number_format($transaction->amount, 2) }}
                                    </p>

                                    <p class="mt-1 text-[10px] text-slate-400">
                                        {{ $transaction->payment_method ?? 'No method yet' }}
                                    </p>

                                </td>


                                {{-- REFERENCE --}}
                                <td class="px-6 py-5">

                                    <p class="max-w-[150px] truncate text-xs font-medium text-slate-600">
                                        {{ $transaction->payment_reference ?? 'Not submitted' }}
                                    </p>

                                </td>


                                {{-- STATUS --}}
                                <td class="px-6 py-5">

                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-[10px] font-bold {{ $statusClass }}">

                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>

                                        {{ ucfirst($transaction->status) }}

                                    </span>

                                </td>


                                {{-- PROOF --}}
                                <td class="px-6 py-5">

                                    @if($transaction->payment_proof)

                                        <a
                                            href="{{ asset('storage/' . $transaction->payment_proof) }}"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-[10px] font-bold text-blue-600 transition hover:bg-blue-100"
                                        >

                                            <svg
                                                class="h-3.5 w-3.5"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
                                                <path d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7Z"></path>
                                            </svg>

                                            View Proof

                                        </a>

                                    @else

                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1.5 text-[10px] font-semibold text-slate-400">
                                            No proof
                                        </span>

                                    @endif

                                </td>


                                {{-- ACTION --}}
                                <td class="px-6 py-5">

                                    <div class="flex justify-end gap-2">

                                        @if($transaction->status === 'pending')

                                            {{-- APPROVE --}}
                                            <form
                                                action="{{ route('super_admin.transactions.approve', $transaction) }}"
                                                method="POST"
                                            >

                                                @csrf

                                                <button
                                                    type="submit"
                                                    onclick="return confirm('Approve this transaction and enroll the student?')"
                                                    class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-2 text-[10px] font-bold text-white transition hover:bg-emerald-700"
                                                >
                                                    Approve
                                                </button>

                                            </form>


                                            {{-- REJECT --}}
                                            <form
                                                action="{{ route('super_admin.transactions.reject', $transaction) }}"
                                                method="POST"
                                            >

                                                @csrf

                                                <button
                                                    type="submit"
                                                    onclick="return confirm('Reject this transaction?')"
                                                    class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-[10px] font-bold text-white transition hover:bg-red-700"
                                                >
                                                    Reject
                                                </button>

                                            </form>

                                        @elseif($transaction->status === 'approved')

                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-2 text-[10px] font-bold text-emerald-700">
                                                ✓ Completed
                                            </span>

                                        @elseif($transaction->status === 'rejected')

                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-2 text-[10px] font-bold text-red-600">
                                                ✕ Rejected
                                            </span>

                                        @endif

                                    </div>

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td colspan="8" class="px-6 py-20 text-center">

                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-2xl">
                                        💳
                                    </div>

                                    <h3 class="mt-4 text-lg font-bold text-slate-800">
                                        No transactions available
                                    </h3>

                                    <p class="mt-1 text-sm text-slate-400">
                                        Student payment transactions will appear here.
                                    </p>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        {{-- =====================================================
            VERIFICATION NOTICE
        ====================================================== --}}
        @if($pendingTransactions > 0)

            <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5">

                <div class="flex items-start gap-4">

                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                        !
                    </div>

                    <div>

                        <h3 class="text-sm font-bold text-amber-800">
                            Payment verification required
                        </h3>

                        <p class="mt-1 text-xs leading-5 text-amber-700/70">
                            There are {{ $pendingTransactions }}
                            pending {{ \Illuminate\Support\Str::plural('transaction', $pendingTransactions) }}.
                            Review the submitted payment proof before approving the transaction.
                        </p>

                    </div>

                </div>

            </div>

        @endif

    </div>

</x-layouts::app>