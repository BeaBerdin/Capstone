<x-layouts::app :title="'Transaction Details'">

<div class="mx-auto max-w-4xl space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Transaction Details
        </h1>
        <p class="text-sm text-gray-500">
            Review your payment transaction and upload your proof of payment.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-100 p-4 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg bg-red-100 p-4 text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-xl border border-neutral-700 bg-neutral-900 p-6">

        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm text-gray-400">Transaction No.</p>
                <h2 class="text-xl font-bold text-white">
                    {{ $transaction->transaction_no }}
                </h2>
            </div>

            @if($transaction->status === 'approved')
                <span class="rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-700">
                    Approved
                </span>
            @elseif($transaction->status === 'rejected')
                <span class="rounded-full bg-red-100 px-3 py-1 text-sm font-semibold text-red-700">
                    Rejected
                </span>
            @else
                <span class="rounded-full bg-yellow-100 px-3 py-1 text-sm font-semibold text-yellow-700">
                    Pending Verification
                </span>
            @endif
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-lg bg-neutral-800 p-4">
                <p class="text-sm text-gray-400">Course</p>
                <p class="mt-1 font-semibold text-white">
                    {{ $transaction->course->title ?? 'Course unavailable' }}
                </p>
            </div>

            <div class="rounded-lg bg-neutral-800 p-4">
                <p class="text-sm text-gray-400">Amount</p>
                <p class="mt-1 font-semibold text-white">
                    ₱{{ number_format($transaction->amount, 2) }}
                </p>
            </div>

            <div class="rounded-lg bg-neutral-800 p-4">
                <p class="text-sm text-gray-400">Payment Method</p>
                <p class="mt-1 font-semibold text-white">
                    {{ $transaction->payment_method ?? 'Not yet submitted' }}
                </p>
            </div>
        </div>

        @if($transaction->payment_reference)
            <div class="mt-4 rounded-lg bg-neutral-800 p-4">
                <p class="text-sm text-gray-400">Payment Reference</p>
                <p class="mt-1 font-semibold text-white">
                    {{ $transaction->payment_reference }}
                </p>
            </div>
        @endif

        @if($transaction->payment_proof)
            <div class="mt-6">
                <p class="mb-2 text-sm font-medium text-gray-300">
                    Uploaded Receipt
                </p>

                <a href="{{ asset('storage/' . $transaction->payment_proof) }}"
                   target="_blank"
                   class="inline-block">
                    <img src="{{ asset('storage/' . $transaction->payment_proof) }}"
                         alt="Payment Proof"
                         class="max-h-72 w-auto rounded-lg border border-neutral-700 object-contain shadow">
                </a>

                <p class="mt-2 text-xs text-gray-500">
                    Click image to view full receipt.
                </p>
            </div>
        @endif

    </div>

    @if($transaction->status === 'pending')

        <div class="rounded-xl border border-neutral-700 bg-neutral-900 p-6">

            <h3 class="text-lg font-semibold text-white">
                Upload / Replace Payment Proof
            </h3>

            <p class="mt-1 text-sm text-gray-400">
                Submit your GCash, Maya, or bank transfer receipt for admin verification.
            </p>

            <form action="{{ route('student.transactions.upload-proof', $transaction) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="mt-5 space-y-4">
                @csrf

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-300">
                        Payment Method
                    </label>

                    <select name="payment_method"
                            class="w-full rounded-lg border border-neutral-700 bg-neutral-800 p-3 text-white"
                            required>
                        <option value="">Select payment method</option>
                        <option value="GCash" @selected($transaction->payment_method === 'GCash')>GCash</option>
                        <option value="Maya" @selected($transaction->payment_method === 'Maya')>Maya</option>
                        <option value="Bank Transfer" @selected($transaction->payment_method === 'Bank Transfer')>Bank Transfer</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-300">
                        Reference Number
                    </label>

                    <input type="text"
                           name="payment_reference"
                           value="{{ old('payment_reference', $transaction->payment_reference) }}"
                           class="w-full rounded-lg border border-neutral-700 bg-neutral-800 p-3 text-white"
                           required>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-300">
                        Upload Receipt
                    </label>

                    <label class="flex h-36 w-full cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-neutral-600 bg-neutral-800 px-4 transition hover:border-blue-500 hover:bg-neutral-700">
                        <div class="text-center">
                            <p id="file-name" class="text-sm text-gray-400">
                                Click to upload payment receipt
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                JPG, JPEG, or PNG only
                            </p>
                        </div>

                        <input type="file"
                               name="payment_proof"
                               id="payment_proof"
                               accept=".jpg,.jpeg,.png"
                               class="hidden"
                               {{ $transaction->payment_proof ? '' : 'required' }}>
                    </label>

                    @error('payment_proof')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-300">
                        Remarks
                    </label>

                    <textarea name="remarks"
                              rows="3"
                              class="w-full rounded-lg border border-neutral-700 bg-neutral-800 p-3 text-white">{{ old('remarks', $transaction->remarks) }}</textarea>
                </div>

                <button type="submit"
                        class="rounded-lg bg-blue-600 px-5 py-2 text-white hover:bg-blue-700">
                    Submit Payment Proof
                </button>
            </form>
        </div>

    @else

        <div class="rounded-xl border border-neutral-700 bg-neutral-900 p-6">
            <h3 class="text-lg font-semibold text-white">
                Payment Status
            </h3>

            @if($transaction->status === 'approved')
                <p class="mt-2 text-green-400">
                    Your payment has been approved. You may now access the course.
                </p>

                <a href="{{ route('student.learn.course', $transaction->course) }}"
                   class="mt-4 inline-block rounded-lg bg-green-600 px-5 py-2 text-white hover:bg-green-700">
                    Go to Course
                </a>
            @else
                <p class="mt-2 text-red-400">
                    Your payment was rejected. Please contact the administrator or submit another purchase request.
                </p>
            @endif
        </div>

    @endif

</div>

<script>
    const paymentProofInput = document.getElementById('payment_proof');
    const fileName = document.getElementById('file-name');

    if (paymentProofInput && fileName) {
        paymentProofInput.addEventListener('change', function () {
            if (this.files.length > 0) {
                fileName.innerText = this.files[0].name;
            }
        });
    }
</script>

</x-layouts::app>