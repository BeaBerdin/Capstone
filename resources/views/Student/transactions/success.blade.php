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

                            <p class="mt-2 text-sm text-slate-600">
                                {{ $courseAccessible ? 'Your enrollment is active. You can start learning.' : 'Your payment is on record. Contact support with your transaction number if you need help with course access.' }}
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
                                {{ ($paymentNeedsReview ?? false) ? 'Payment Requires Review' : 'Payment Verification Pending' }}
                            </h3>

                            @unless ($paymentNeedsReview ?? false)
                                <p class="mt-2 text-sm text-slate-500">
                                    PayMongo confirmation is pending for <strong>{{ $transaction->course->title ?? 'this course' }}</strong>.
                                </p>
                            @endunless

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

                                @if ($courseAccessible)
                                    <a href="{{ route('student.learn.course', $transaction->course_id) }}"
                                       class="sad-btn-primary inline-flex h-11 items-center justify-center gap-2 rounded-xl px-5 text-sm font-semibold text-white transition"
                                       data-test="start-learning">
                                        Start Learning
                                    </a>
                                @endif

                                <a href="{{ route('student.my-courses') }}"
                                   class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-600 transition hover:border-violet-200 hover:text-violet-700">
                                    Go to My Courses
                                </a>

                            @else

                                <a href="{{ route('student.transactions.success', $transaction) }}"
                                   class="sad-btn-primary inline-flex h-11 items-center justify-center gap-2 rounded-xl px-5 text-sm font-semibold text-white transition">
                                    Check Payment Status Again
                                </a>

                                <a href="{{ route('student.transactions') }}"
                                   class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-600 transition hover:border-violet-200 hover:text-violet-700">
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
