<x-layouts::app :title="'Payment Successful'">

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    <div class="text-center">

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
                            Payment Successful!
                        </h3>

                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Your payment for
                            <strong>
                                {{ $transaction->course->title ?? 'the course' }}
                            </strong>
                            has been completed successfully.
                        </p>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Transaction No:
                            {{ $transaction->transaction_no }}
                        </p>

                        <div class="mt-6 flex justify-center gap-3">

                            <a href="{{ route('student.marketplace') }}"
                               class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 transition">
                                Back to Marketplace
                            </a>

                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>

</x-layouts::app>