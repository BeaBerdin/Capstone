<x-layouts::app :title="$paymentVerified ? 'Payment Confirmed' : 'Payment Verification'">

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

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

                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Your payment for
                                <strong>
                                    {{ $transaction->course->title ?? 'the course' }}
                                </strong>
                                has been securely verified through PayMongo.
                            </p>

                            <p class="mt-2 text-sm text-green-600 dark:text-green-400">
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

                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                PathWise has not confirmed a completed PayMongo payment for
                                <strong>
                                    {{ $transaction->course->title ?? 'this course' }}
                                </strong>
                                yet.
                            </p>

                            <p class="mt-2 text-sm text-amber-600 dark:text-amber-400">
                                {{ $verificationMessage ?? 'Please check the payment status again.' }}
                            </p>

                        @endif


                        <div class="mt-6 rounded-lg bg-gray-50 p-4 text-left dark:bg-gray-900/50">

                            <div class="grid gap-3 sm:grid-cols-2">

                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-400">
                                        Transaction No.
                                    </p>
                                    <p class="mt-1 text-sm font-semibold">
                                        {{ $transaction->transaction_no }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-400">
                                        Status
                                    </p>
                                    <p class="mt-1 text-sm font-semibold {{ $paymentVerified ? 'text-green-600' : 'text-amber-600' }}">
                                        {{ ucfirst($transaction->status) }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-400">
                                        Amount
                                    </p>
                                    <p class="mt-1 text-sm font-semibold">
                                        ₱{{ number_format((float) $transaction->amount, 2) }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-400">
                                        Payment Method
                                    </p>
                                    <p class="mt-1 text-sm font-semibold">
                                        {{ $transaction->payment_method ?? 'PayMongo' }}
                                    </p>
                                </div>

                            </div>

                        </div>


                        <div class="mt-6 flex flex-wrap justify-center gap-3">

                            @if($paymentVerified)

                                <a href="{{ route('student.my-courses') }}"
                                   class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 transition">
                                    Go to My Courses
                                </a>

                                <a href="{{ route('student.marketplace') }}"
                                   class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-200 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 transition dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                    Back to Marketplace
                                </a>

                            @else

                                <a href="{{ route('student.transactions.success', $transaction) }}"
                                   class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 transition">
                                    Check Payment Status Again
                                </a>

                                <a href="{{ route('student.transactions') }}"
                                   class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-200 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 transition dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
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
