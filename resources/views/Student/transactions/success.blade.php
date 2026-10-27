<x-layouts::app :title="$paymentVerified ? 'Payment Confirmed' : 'Payment Verification'">
@include('partials.soft3d')

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-5 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 text-slate-900">

                    <div class="text-center">

                        @if($paymentVerified)

                            <svg class="mx-auto h-12 w-12 text-green-500"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="2"
                                      d="M5 13l4 4L19 7"/>
                            </svg>

                            <h3 class="mt-4 text-lg font-medium">
                                Payment Confirmed!
                            </h3>

                            <p class="mt-2 text-sm text-slate-500">
                                Your payment for
                                <strong>
                                    {{ $transaction->course->title ?? 'the course' }}
                                </strong>
                                has been securely verified through PayMongo.
                            </p>

                            <p class="mt-2 text-sm text-green-600">
                                You are now enrolled and can start learning.
                            </p>

                        @else

                            <svg class="mx-auto h-12 w-12 text-amber-500"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor">
                                <circle cx="12" cy="12" r="9" stroke-width="2"></circle>
                                <path stroke-linecap="round"
                                      stroke-width="2"
                                      d="M12 7v5m0 4h.01"/>
                            </svg>

                            <h3 class="mt-4 text-lg font-medium">
                                Payment Verification Pending
                            </h3>

                            <p class="mt-2 text-sm text-slate-500">
                                PathWise has not confirmed a completed PayMongo payment for
                                <strong>
                                    {{ $transaction->course->title ?? 'this course' }}
                                </strong>
                                yet.
                            </p>

                            <p class="mt-2 text-sm text-amber-600">
                                {{ $verificationMessage ?? 'Please check the payment status again.' }}
                            </p>

                        @endif


                        <div class="mt-6 rounded-lg bg-slate-50 p-4 text-left">

                            <div class="grid gap-3 sm:grid-cols-2">

                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">
                                        Transaction No.
                                    </p>
                                    <p class="mt-1 text-sm font-semibold">
                                        {{ $transaction->transaction_no }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">
                                        Status
                                    </p>
                                    <p class="mt-1 text-sm font-semibold {{ $paymentVerified ? 'text-green-600' : 'text-amber-600' }}">
                                        {{ ucfirst($transaction->status) }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">
                                        Amount
                                    </p>
                                    <p class="mt-1 text-sm font-semibold">
                                        ₱{{ number_format((float) $transaction->amount, 2) }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">
                                        Payment Method
                                    </p>
                                    <p class="mt-1 text-sm font-semibold">
                                        {{ \App\Support\PaymentMethods::forTransaction($transaction) ?? 'PayMongo' }}
                                    </p>
                                </div>

                            </div>

                        </div>


                        <div class="mt-6 flex flex-wrap justify-center gap-3">

                            @if($paymentVerified)

                                <a href="{{ route('student.my-courses') }}"
                                   class="sad-btn-primary inline-flex items-center px-4 py-2 bg-violet-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-violet-700 transition">
                                    Go to My Courses
                                </a>

                                <a href="{{ route('student.marketplace') }}"
                                   class="inline-flex items-center px-4 py-2 bg-slate-100 border border-slate-200 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest hover:bg-slate-200 transition">
                                    Back to Marketplace
                                </a>

                            @else

                                <a href="{{ route('student.transactions.success', $transaction) }}"
                                   class="sad-btn-primary inline-flex items-center px-4 py-2 bg-violet-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-violet-700 transition">
                                    Check Payment Status Again
                                </a>

                                <a href="{{ route('student.transactions') }}"
                                   class="inline-flex items-center px-4 py-2 bg-slate-100 border border-slate-200 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest hover:bg-slate-200 transition">
                                    Transaction History
                                </a>

                            @endif

                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>

</x-layouts::app>
