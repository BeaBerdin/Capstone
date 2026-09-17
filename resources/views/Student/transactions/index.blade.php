<x-layouts::app :title="'My Transactions'">

@php
    $totalTransactions = $transactions->count();
    $approvedTransactions = $transactions->where('status', 'approved')->count();
    $pendingTransactions = $transactions->where('status', 'pending')->count();
    $rejectedTransactions = $transactions->where('status', 'rejected')->count();
@endphp


<div class="min-h-screen bg-slate-50/70">

    <main class="px-4 py-6 sm:px-5 lg:px-6 lg:py-7">

        <div class="w-full max-w-none space-y-6">


            {{-- =====================================================
                HEADER
            ====================================================== --}}

            <section class="overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">

                <div class="relative px-6 py-7 sm:px-8">

                    <div class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-violet-100/70 blur-3xl"></div>
                    <div class="pointer-events-none absolute right-36 top-12 h-24 w-24 rounded-full bg-indigo-100/70 blur-2xl"></div>

                    <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">

                        <div class="max-w-2xl">

                            <div class="inline-flex items-center gap-2 rounded-full bg-violet-50 px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-violet-700">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                    <path d="M3 10h18"></path>
                                </svg>
                                Payment Records
                            </div>

                            <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                                My Transactions
                            </h1>

                            <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">
                                Review your course purchases, payment references, and current transaction status.
                            </p>

                        </div>


                        <a
                            href="{{ route('student.marketplace') }}"
                            class="inline-flex h-11 w-fit items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>

                            Browse Courses
                        </a>

                    </div>

                </div>

            </section>



            {{-- =====================================================
                SUMMARY
            ====================================================== --}}

            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold text-slate-500">
                                Total Transactions
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                                {{ $totalTransactions }}
                            </p>

                            <p class="mt-2 text-xs text-slate-400">
                                All purchase attempts
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                <path d="M3 10h18"></path>
                            </svg>
                        </div>
                    </div>
                </div>


                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold text-slate-500">
                                Approved
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-600">
                                {{ $approvedTransactions }}
                            </p>

                            <p class="mt-2 text-xs text-slate-400">
                                Verified payments
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 6 9 17l-5-5"></path>
                            </svg>
                        </div>
                    </div>
                </div>


                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold text-slate-500">
                                Pending
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-amber-600">
                                {{ $pendingTransactions }}
                            </p>

                            <p class="mt-2 text-xs text-slate-400">
                                Awaiting verification
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M12 7v5l3 2"></path>
                            </svg>
                        </div>
                    </div>
                </div>


                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold text-slate-500">
                                Rejected
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight text-rose-600">
                                {{ $rejectedTransactions }}
                            </p>

                            <p class="mt-2 text-xs text-slate-400">
                                Declined transactions
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 6 6 18"></path>
                                <path d="m6 6 12 12"></path>
                            </svg>
                        </div>
                    </div>
                </div>

            </section>



            {{-- =====================================================
                TRANSACTION HISTORY
            ====================================================== --}}

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-violet-500">
                            Payment Activity
                        </p>

                        <h2 class="mt-1 text-xl font-bold text-slate-900">
                            Transaction History
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Review your latest course purchase and payment records.
                        </p>
                    </div>


                    <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1.5 text-[11px] font-semibold text-slate-500">
                        {{ $totalTransactions }}
                        {{ \Illuminate\Support\Str::plural('record', $totalTransactions) }}
                    </span>

                </div>


                @forelse($transactions as $transaction)

                    @php
                        $status = strtolower($transaction->status ?? 'pending');

                        $statusLabel = 'Pending';
                        $statusClass = 'bg-amber-50 text-amber-700';
                        $dotClass = 'bg-amber-500';
                        $message = 'Waiting for payment verification.';
                        $messageClass = 'text-amber-700';

                        if ($status === 'approved') {
                            $statusLabel = 'Approved';
                            $statusClass = 'bg-emerald-50 text-emerald-700';
                            $dotClass = 'bg-emerald-500';
                            $message = 'Payment approved. Course access granted.';
                            $messageClass = 'text-emerald-700';
                        } elseif ($status === 'rejected') {
                            $statusLabel = 'Rejected';
                            $statusClass = 'bg-rose-50 text-rose-700';
                            $dotClass = 'bg-rose-500';
                            $message = 'Payment was rejected. Review the transaction details.';
                            $messageClass = 'text-rose-600';
                        }
                    @endphp


                    <article class="border-b border-slate-100 last:border-b-0">

                        <div class="grid grid-cols-1 gap-5 px-5 py-5 transition hover:bg-slate-50/70 sm:px-6 lg:grid-cols-[minmax(0,1.7fr)_140px_160px_180px_130px] lg:items-center">


                            {{-- TRANSACTION / COURSE --}}
                            <div class="min-w-0">

                                <div class="flex flex-wrap items-center gap-2">

                                    <p class="text-[10px] font-bold uppercase tracking-[0.1em] text-violet-500">
                                        {{ $transaction->transaction_no }}
                                    </p>

                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold {{ $statusClass }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $dotClass }}"></span>
                                        {{ $statusLabel }}
                                    </span>

                                </div>


                                <h3 class="mt-2 truncate text-base font-bold text-slate-900">
                                    {{ $transaction->course->title ?? 'Course unavailable' }}
                                </h3>


                                <p class="mt-1 text-xs font-medium {{ $messageClass }}">
                                    {{ $message }}
                                </p>

                            </div>



                            {{-- AMOUNT --}}
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                    Amount
                                </p>

                                <p class="mt-1 text-sm font-bold text-slate-900">
                                    ₱{{ number_format($transaction->amount, 2) }}
                                </p>
                            </div>



                            {{-- METHOD --}}
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                    Method
                                </p>

                                <p class="mt-1 truncate text-sm font-semibold text-slate-700">
                                    {{ $transaction->payment_method ?? 'Not submitted' }}
                                </p>
                            </div>



                            {{-- REFERENCE --}}
                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                    Reference
                                </p>

                                <p
                                    class="mt-1 truncate text-sm font-semibold text-slate-700"
                                    title="{{ $transaction->payment_reference ?? 'Not submitted' }}"
                                >
                                    {{ $transaction->payment_reference ?? 'Not submitted' }}
                                </p>
                            </div>



                            {{-- ACTION --}}
                            <div class="flex lg:justify-end">

                                <a
                                    href="{{ route('student.transactions.show', $transaction) }}"
                                    class="inline-flex h-9 w-full items-center justify-center gap-1.5 rounded-lg border border-violet-200 bg-white px-3 text-[11px] font-semibold text-violet-700 transition hover:bg-violet-50 lg:w-auto"
                                >
                                    View Details

                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="m9 18 6-6-6-6"></path>
                                    </svg>
                                </a>

                            </div>

                        </div>

                    </article>


                @empty


                    {{-- EMPTY STATE --}}
                    <div class="px-6 py-16 text-center">

                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                <path d="M3 10h18"></path>
                                <path d="M7 15h3"></path>
                            </svg>
                        </div>


                        <h3 class="mt-4 text-base font-bold text-slate-900">
                            No transactions yet
                        </h3>


                        <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">
                            Your course purchases and payment records will appear here.
                        </p>


                        <a
                            href="{{ route('student.marketplace') }}"
                            class="mt-5 inline-flex h-10 items-center justify-center rounded-xl bg-violet-600 px-5 text-xs font-semibold text-white transition hover:bg-violet-700"
                        >
                            Browse Courses
                        </a>

                    </div>

                @endforelse

            </section>

        </div>

    </main>

</div>

</x-layouts::app>
