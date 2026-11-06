
<x-layouts::app :title="'Transaction Cancelled'">
@include('partials.soft3d')

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 text-slate-900">

                    <div class="text-center">

                        <svg class="mx-auto h-12 w-12 text-amber-500"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>

                        <h3 class="mt-4 text-lg font-medium">
                            Payment Cancelled
                        </h3>

                        <p class="mt-2 text-sm text-slate-500">
                            Your payment for
                            <strong>{{ $transaction->course->title ?? 'the course' }}</strong>
                            was cancelled.
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Transaction No: {{ $transaction->transaction_no }}
                        </p>

                        <div class="mt-6">
                            <a href="{{ route('student.marketplace') }}"
                               class="sad-btn-primary inline-flex h-11 items-center justify-center gap-2 rounded-xl px-5 text-sm font-semibold text-white transition">
                                Back to Marketplace
                            </a>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>

</x-layouts::app>

