<x-layouts::app :title="'My Transactions'">

@php
    $totalTransactions = $transactions->count();
    $approvedTransactions = $transactions->where('status', 'approved')->count();
    $pendingTransactions = $transactions->where('status', 'pending')->count();
    $rejectedTransactions = $transactions->where('status', 'rejected')->count();
@endphp

<style>
    .pw-card {
        background: #ffffff;
        border: 1px solid #e7e9ef;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.035);
    }

    .pw-card-hover {
        transition:
            transform 160ms ease,
            border-color 160ms ease,
            box-shadow 160ms ease;
    }

    .pw-card-hover:hover {
        transform: translateY(-2px);
        border-color: #ddd6fe;
        box-shadow: 0 12px 30px rgba(76, 29, 149, 0.06);
    }
</style>

<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-[1500px]">

            {{-- =====================================================
                HEADER
            ====================================================== --}}
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">

                <div>

                    <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                        Student
                    </p>

                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                        My Transactions
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                        Track your course purchases, payment receipts,
                        and transaction approval status.
                    </p>

                </div>

            </div>


            {{-- =====================================================
                SUMMARY CARDS
            ====================================================== --}}
            <section class="mt-7 grid grid-cols-2 gap-4 xl:grid-cols-4">

                {{-- TOTAL --}}
                <div class="pw-card p-5">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <p class="text-xs font-semibold text-slate-500">
                                Total Transactions
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                                {{ $totalTransactions }}
                            </p>

                        </div>

                        <div class="flex h-10 w-10 items-center justify-center
                                    rounded-xl bg-violet-50 text-violet-600">

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

                    <p class="mt-2 text-xs text-slate-400">
                        All purchases
                    </p>

                </div>


                {{-- APPROVED --}}
                <div class="pw-card p-5">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <p class="text-xs font-semibold text-slate-500">
                                Approved
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-600">
                                {{ $approvedTransactions }}
                            </p>

                        </div>

                        <div class="flex h-10 w-10 items-center justify-center
                                    rounded-xl bg-emerald-50 text-emerald-600">

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

                    <p class="mt-2 text-xs text-slate-400">
                        Verified payments
                    </p>

                </div>


                {{-- PENDING --}}
                <div class="pw-card p-5">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <p class="text-xs font-semibold text-slate-500">
                                Pending
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-amber-500">
                                {{ $pendingTransactions }}
                            </p>

                        </div>

                        <div class="flex h-10 w-10 items-center justify-center
                                    rounded-xl bg-amber-50 text-amber-500">

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

                    <p class="mt-2 text-xs text-slate-400">
                        Awaiting review
                    </p>

                </div>


                {{-- REJECTED --}}
                <div class="pw-card p-5">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <p class="text-xs font-semibold text-slate-500">
                                Rejected
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-red-500">
                                {{ $rejectedTransactions }}
                            </p>

                        </div>

                        <div class="flex h-10 w-10 items-center justify-center
                                    rounded-xl bg-red-50 text-red-500">

                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M18 6 6 18"></path>
                                <path d="m6 6 12 12"></path>
                            </svg>

                        </div>

                    </div>

                    <p class="mt-2 text-xs text-slate-400">
                        Declined payments
                    </p>

                </div>

            </section>


            {{-- =====================================================
                TRANSACTIONS
            ====================================================== --}}
            <section class="mt-6">

                <div class="mb-4">

                    <h2 class="text-lg font-bold text-slate-900">
                        Transaction History
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        View your course purchases and payment verification status.
                    </p>

                </div>


                <div class="grid gap-5 lg:grid-cols-2">

                    @forelse($transactions as $transaction)

                        <article class="pw-card pw-card-hover overflow-hidden">

                            {{-- CARD HEADER --}}
                            <div class="border-b border-slate-100 p-5">

                                <div class="flex items-start justify-between gap-4">

                                    <div class="min-w-0">

                                        <p class="text-[10px] font-bold uppercase
                                                  tracking-[.08em] text-violet-500">
                                            Transaction No.
                                        </p>

                                        <h3 class="mt-1 truncate text-base font-bold text-slate-900">
                                            {{ $transaction->transaction_no }}
                                        </h3>

                                    </div>


                                    {{-- STATUS --}}
                                    @if($transaction->status === 'approved')

                                        <span class="inline-flex shrink-0 items-center gap-1.5
                                                     rounded-full bg-emerald-50
                                                     px-3 py-1.5 text-[10px]
                                                     font-bold text-emerald-700">

                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>

                                            Approved

                                        </span>

                                    @elseif($transaction->status === 'rejected')

                                        <span class="inline-flex shrink-0 items-center gap-1.5
                                                     rounded-full bg-red-50
                                                     px-3 py-1.5 text-[10px]
                                                     font-bold text-red-600">

                                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>

                                            Rejected

                                        </span>

                                    @else

                                        <span class="inline-flex shrink-0 items-center gap-1.5
                                                     rounded-full bg-amber-50
                                                     px-3 py-1.5 text-[10px]
                                                     font-bold text-amber-700">

                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>

                                            Pending

                                        </span>

                                    @endif

                                </div>

                            </div>


                            {{-- COURSE --}}
                            <div class="p-5">

                                <div class="rounded-2xl bg-slate-50 p-4">

                                    <p class="text-[10px] font-bold uppercase
                                              tracking-[.08em] text-slate-400">
                                        Course Purchased
                                    </p>

                                    <h3 class="mt-2 line-clamp-2 text-base
                                               font-bold leading-6 text-slate-900">
                                        {{ $transaction->course->title ?? 'Course unavailable' }}
                                    </h3>

                                </div>


                                {{-- DETAILS --}}
                                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">

                                    {{-- AMOUNT --}}
                                    <div class="rounded-xl border border-slate-100
                                                bg-white p-3">

                                        <p class="text-[10px] font-semibold text-slate-400">
                                            Amount
                                        </p>

                                        <p class="mt-1 text-sm font-bold text-slate-800">
                                            ₱{{ number_format($transaction->amount, 2) }}
                                        </p>

                                    </div>


                                    {{-- METHOD --}}
                                    <div class="rounded-xl border border-slate-100
                                                bg-white p-3">

                                        <p class="text-[10px] font-semibold text-slate-400">
                                            Method
                                        </p>

                                        <p class="mt-1 truncate text-sm font-bold text-slate-800">
                                            {{ $transaction->payment_method ?? 'Not submitted' }}
                                        </p>

                                    </div>


                                    {{-- REFERENCE --}}
                                    <div class="rounded-xl border border-slate-100
                                                bg-white p-3">

                                        <p class="text-[10px] font-semibold text-slate-400">
                                            Reference
                                        </p>

                                        <p class="mt-1 truncate text-sm font-bold text-slate-800">
                                            {{ $transaction->payment_reference ?? 'Not submitted' }}
                                        </p>

                                    </div>

                                </div>


                                {{-- STATUS MESSAGE --}}
                                <div class="mt-5 flex flex-col gap-4
                                            border-t border-slate-100 pt-4
                                            sm:flex-row sm:items-center
                                            sm:justify-between">

                                    <div>

                                        @if($transaction->status === 'approved')

                                            <p class="text-xs font-medium text-emerald-600">
                                                Payment approved. Course access granted.
                                            </p>

                                        @elseif($transaction->status === 'rejected')

                                            <p class="text-xs font-medium text-red-500">
                                                Payment was rejected. Please review the details.
                                            </p>

                                        @else

                                            <p class="text-xs font-medium text-amber-600">
                                                Waiting for administrator verification.
                                            </p>

                                        @endif

                                    </div>


                                    <a
                                        href="{{ route('student.transactions.show', $transaction) }}"
                                        class="inline-flex h-9 shrink-0 items-center
                                               justify-center rounded-lg
                                               bg-violet-600 px-4
                                               text-[11px] font-semibold
                                               text-white transition
                                               hover:bg-violet-700"
                                    >
                                        View Details
                                    </a>

                                </div>

                            </div>

                        </article>

                    @empty

                        {{-- =================================================
                            EMPTY STATE
                        ================================================== --}}
                        <div class="col-span-full pw-card px-6 py-16 text-center">

                            <div class="mx-auto flex h-14 w-14 items-center
                                        justify-center rounded-2xl
                                        bg-violet-50 text-violet-600">

                                <svg
                                    class="h-6 w-6"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                    <path d="M3 10h18"></path>
                                    <path d="M7 15h3"></path>
                                </svg>

                            </div>


                            <h3 class="mt-4 text-sm font-bold text-slate-800">
                                No transactions yet
                            </h3>


                            <p class="mx-auto mt-1 max-w-sm text-xs
                                      leading-5 text-slate-400">
                                Your course purchases and payment records
                                will appear here.
                            </p>


                            <a
                                href="{{ route('student.marketplace') }}"
                                class="mt-5 inline-flex h-10 items-center
                                       justify-center rounded-xl
                                       bg-violet-600 px-5
                                       text-xs font-semibold text-white
                                       transition hover:bg-violet-700"
                            >
                                Browse Courses
                            </a>

                        </div>

                    @endforelse

                </div>

            </section>

        </div>

    </main>

</div>

</x-layouts::app>