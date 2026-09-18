<x-layouts::app :title="__('Manage Transactions')">
<div class="min-h-screen bg-gray-50">
    <div class="mx-auto w-full max-w-6xl space-y-6">
        {{-- =====================================================
            HEADER
        ====================================================== --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

            <div>
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-violet-600">
                    Super Admin
                </p>

                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                    Manage Transactions
                </h1>

                <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                    Review student payments, verify proof of payment,
                    and manage enrollment transactions.
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
            OVERVIEW CARDS
        ====================================================== --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            {{-- TOTAL --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-start justify-between">

                    <div>
                        <p class="text-xs font-semibold text-slate-500">
                            Total Transactions
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-950">
                            {{ $totalTransactions }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            All payment records
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                            <path d="M3 10h18"/>
                        </svg>
                    </div>

                </div>

            </div>


            {{-- PENDING --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-start justify-between">

                    <div>
                        <p class="text-xs font-semibold text-slate-500">
                            Pending Review
                        </p>

                        <p class="mt-2 text-3xl font-bold text-amber-600">
                            {{ $pendingTransactions }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            Awaiting verification
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 7v5l3 2"/>
                        </svg>
                    </div>

                </div>

            </div>


            {{-- APPROVED --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-start justify-between">

                    <div>
                        <p class="text-xs font-semibold text-slate-500">
                            Approved
                        </p>

                        <p class="mt-2 text-3xl font-bold text-emerald-600">
                            {{ $approvedTransactions }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            Successful payments
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M20 6 9 17l-5-5"/>
                        </svg>
                    </div>

                </div>

            </div>


            {{-- REVENUE --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-start justify-between">

                    <div>
                        <p class="text-xs font-semibold text-slate-500">
                            Approved Revenue
                        </p>

                        <p class="mt-2 text-2xl font-bold text-violet-600">
                            ₱{{ number_format($approvedRevenue, 2) }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            From approved payments
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M12 1v22"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/>
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

                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-sm font-bold text-emerald-600">
                    ✓
                </div>

                <p class="text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </p>

            </div>

        @endif


        @if(session('error'))

            <div class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3">

                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100 text-sm font-bold text-red-600">
                    !
                </div>

                <p class="text-sm font-medium text-red-700">
                    {{ session('error') }}
                </p>

            </div>

        @endif


        {{-- =====================================================
            TRANSACTION MANAGEMENT
        ====================================================== --}}
        <div class="-mx-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm sm:-mx-6 lg:-mx-10">

            {{-- HEADER --}}
            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 md:flex-row md:items-center md:justify-between">

                <div>

                    <h2 class="text-lg font-bold text-slate-900">
                        Payment Verification Queue
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        Review submitted payments and verify proof before enrollment approval.
                    </p>

                </div>


                <div class="inline-flex w-fit items-center gap-2 rounded-lg bg-amber-50 px-3 py-2">

                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>

                    <span class="text-xs font-semibold text-amber-700">
                        {{ $pendingTransactions }} pending
                    </span>

                </div>

            </div>


            {{-- TABLE --}}
            <div class="overflow-x-auto">

               {{-- FIX: removed min-w-[1250px]; columns now share the
                    available width and wrap instead of forcing scroll --}}
               <table class="w-full table-fixed text-left">

                    <thead class="border-b border-slate-200 bg-slate-50">

                        <tr class="text-[10px] font-bold uppercase tracking-[0.08em] text-slate-400">

                            <th class="w-[16%] px-4 py-3">
                                Transaction
                            </th>

                            <th class="w-[20%] px-4 py-3">
                                Student
                            </th>

                            <th class="w-[18%] px-4 py-3">
                                Course
                            </th>

                            <th class="w-[12%] px-4 py-3">
                                Payment
                            </th>

                            {{-- Hidden below lg so the table always fits --}}
                            <th class="hidden w-[12%] px-4 py-3 lg:table-cell">
                                Reference
                            </th>

                            <th class="w-[12%] px-4 py-3">
                                Status
                            </th>

                            <th class="w-[12%] px-4 py-3">
                                Proof
                            </th>

                            <th class="px-4 py-3 text-right">
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

                                $initial = strtoupper(
                                    substr(
                                        $transaction->student->name ?? 'S',
                                        0,
                                        1
                                    )
                                );

                            @endphp


                            <tr class="transition hover:bg-slate-50">


                                {{-- TRANSACTION --}}
                                <td class="px-4 py-4 align-top">

                                    <p class="break-all text-xs font-bold text-slate-800" title="{{ $transaction->transaction_no }}">
                                        {{ $transaction->transaction_no }}
                                    </p>

                                    <p class="mt-1 text-[10px] text-slate-400">
                                        {{ $transaction->created_at?->format('M d, Y h:i A') }}
                                    </p>

                                </td>


                                {{-- STUDENT --}}
                                <td class="px-4 py-4 align-top">

                                    <div class="flex items-center gap-2">

                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-xs font-bold text-violet-600">
                                            {{ $initial }}
                                        </div>

                                        <div class="min-w-0">

                                            <p class="break-words text-xs font-semibold text-slate-800">
                                                {{ $transaction->student->name ?? 'Student unavailable' }}
                                            </p>

                                            <p class="mt-1 text-[10px] text-slate-400">
                                                Learner
                                            </p>

                                        </div>

                                    </div>

                                </td>


                                {{-- COURSE --}}
                                <td class="px-4 py-4 align-top">

                                    <p class="break-words text-xs font-medium text-slate-700" title="{{ $transaction->course->title ?? '' }}">
                                        {{ $transaction->course->title ?? 'Course unavailable' }}
                                    </p>

                                </td>


                                {{-- PAYMENT --}}
                                <td class="px-4 py-4 align-top">

                                    <p class="text-xs font-bold text-slate-800">
                                        ₱{{ number_format($transaction->amount, 2) }}
                                    </p>

                                    <p class="mt-1 break-words text-[10px] text-slate-400">
                                        {{ $transaction->payment_method ?? 'No method' }}
                                    </p>

                                </td>


                                {{-- REFERENCE (hidden below lg) --}}
                                <td class="hidden px-4 py-4 align-top lg:table-cell">

                                    <p class="break-all text-[10px] font-medium text-slate-600" title="{{ $transaction->payment_reference }}">
                                        {{ $transaction->payment_reference ?? 'Not submitted' }}
                                    </p>

                                </td>


                                {{-- STATUS --}}
                                <td class="px-4 py-4 align-top">

                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold {{ $statusClass }}">

                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>

                                        {{ ucfirst($transaction->status) }}

                                    </span>

                                </td>


                                {{-- PROOF --}}
                                <td class="px-4 py-4 align-top">

                                    @if($transaction->payment_proof)

                                        <a
                                            href="{{ asset('storage/' . $transaction->payment_proof) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1 rounded-lg border border-violet-200 bg-violet-50 px-2 py-1.5 text-[10px] font-bold text-violet-600 transition hover:bg-violet-100"
                                        >

                                            <svg
                                                class="h-3 w-3"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path d="M15 12a3 3 0 1 1-6 0Z"/>
                                                <path d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7Z"/>
                                            </svg>

                                            View

                                        </a>

                                    @else

                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-400">
                                            No proof
                                        </span>

                                    @endif

                                </td>


                                {{-- ACTION --}}
                                <td class="px-4 py-4 align-top">

                                    <div class="flex flex-col items-end justify-start gap-1.5 sm:flex-row sm:items-center">

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
                                                    class="inline-flex items-center rounded-lg bg-emerald-600 px-2.5 py-1.5 text-[10px] font-bold text-white transition hover:bg-emerald-700"
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
                                                    class="inline-flex items-center rounded-lg bg-red-600 px-2.5 py-1.5 text-[10px] font-bold text-white transition hover:bg-red-700"
                                                >
                                                    Reject
                                                </button>

                                            </form>

                                        @elseif($transaction->status === 'approved')

                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1.5 text-[10px] font-bold text-emerald-700">
                                                ✓ Done
                                            </span>

                                        @elseif($transaction->status === 'rejected')

                                            <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-1.5 text-[10px] font-bold text-red-600">
                                                ✕ Rejected
                                            </span>

                                        @endif

                                    </div>

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td colspan="8" class="px-6 py-20 text-center">

                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-xl bg-slate-100 text-2xl">
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

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">

                <div class="flex items-start gap-4">

                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-100 font-bold text-amber-600">
                        !
                    </div>

                    <div>

                        <h3 class="text-sm font-bold text-amber-800">
                            Payment verification required
                        </h3>

                        <p class="mt-1 text-xs leading-5 text-amber-700/80">
                            There are {{ $pendingTransactions }}
                            pending
                            {{ \Illuminate\Support\Str::plural('transaction', $pendingTransactions) }}.
                            Review the submitted payment proof before approving the transaction.
                        </p>

                    </div>

                </div>

            </div>

        @endif

    </div>

</x-layouts::app>