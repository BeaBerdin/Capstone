<x-layouts::app :title="'Transaction Details'">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Transactions' => route('student.transactions'), 'Transaction Details' => null]"
    title="Transaction Details"
    subtitle="Review your payment transaction and upload your proof of payment."
    :back-url="route('student.transactions')"
    back-label="Back to Transactions"
    :required="$transaction->status === 'pending'"
>
    @if(session('success') || session('error'))
        <x-slot:intro>
            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700" role="alert">
                    {{ session('error') }}
                </div>
            @endif
        </x-slot:intro>
    @endif

    <div class="space-y-6">

        {{-- ---------- Transaction ---------- --}}
        <section class="sad-card overflow-hidden">
            <x-pw.form-section title="Transaction" first>
                <x-slot:icon>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M2 10h20"></path></svg>
                </x-slot:icon>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-slate-500">Transaction No.</p>
                        <p class="mt-1 break-all text-xl font-bold text-slate-950">
                            {{ $transaction->transaction_no }}
                        </p>
                    </div>

                    @if($transaction->status === 'approved')
                        <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Approved
                        </span>
                    @elseif($transaction->status === 'rejected')
                        <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                            Rejected
                        </span>
                    @else
                        <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            Pending Verification
                        </span>
                    @endif
                </div>

                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-xl bg-slate-50 px-4 py-3">
                        <dt class="text-xs font-bold uppercase tracking-[.08em] text-slate-500">Course</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-800">
                            {{ $transaction->course->title ?? 'Course unavailable' }}
                        </dd>
                    </div>

                    <div class="rounded-xl bg-slate-50 px-4 py-3">
                        <dt class="text-xs font-bold uppercase tracking-[.08em] text-slate-500">Amount</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-800">
                            ₱{{ number_format($transaction->amount, 2) }}
                        </dd>
                    </div>

                    <div class="rounded-xl bg-slate-50 px-4 py-3">
                        <dt class="text-xs font-bold uppercase tracking-[.08em] text-slate-500">Payment Method</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-800">
                            {{ \App\Support\PaymentMethods::forTransaction($transaction) ?? 'Not yet submitted' }}
                        </dd>
                    </div>

                    @if($transaction->payment_reference)
                        <div class="rounded-xl bg-slate-50 px-4 py-3 sm:col-span-3">
                            <dt class="text-xs font-bold uppercase tracking-[.08em] text-slate-500">Payment Reference</dt>
                            <dd class="mt-1 break-all text-sm font-semibold text-slate-800">
                                {{ $transaction->payment_reference }}
                            </dd>
                        </div>
                    @endif
                </dl>

                @if($transaction->payment_proof)
                    <div>
                        <p class="mb-2 text-sm font-semibold text-slate-700">Uploaded Receipt</p>

                        <a href="{{ route('files.receipt', $transaction) }}" target="_blank" class="inline-block">
                            <img src="{{ route('files.receipt', $transaction) }}"
                                 alt="Payment Proof"
                                 class="max-h-72 w-auto max-w-full rounded-xl border border-slate-200 object-contain shadow-sm">
                        </a>

                        <p class="mt-2 text-xs text-slate-500">Click image to view full receipt.</p>
                    </div>
                @endif
            </x-pw.form-section>
        </section>

        @if($transaction->status === 'pending')

            {{-- ---------- Upload proof ---------- --}}
            <form method="POST"
                  action="{{ route('student.transactions.upload-proof', $transaction) }}"
                  enctype="multipart/form-data"
                  class="sad-card overflow-hidden">
                @csrf

                <x-pw.form-errors />

                <x-pw.form-section title="Upload / Replace Payment Proof" first>
                    <x-slot:icon>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 16V4m0 0-4 4m4-4 4 4"></path><path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"></path></svg>
                    </x-slot:icon>

                    <p class="-mt-2 text-sm text-slate-500">
                        Submit your {{ implode(', ', array_values(\App\Support\PaymentMethods::labels())) }} or bank transfer receipt for admin verification.
                    </p>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-pw.field label="Payment Method" for="payment_method" required>
                            <select id="payment_method" name="payment_method" required class="sad-field">
                                <option value="">Select payment method</option>
                                {{-- Enabled PayMongo methods (PAYMONGO_PAYMENT_METHODS) plus bank transfer. --}}
                                @foreach (\App\Support\PaymentMethods::proofOptions() as $methodLabel)
                                    <option value="{{ $methodLabel }}" @selected(old('payment_method', $transaction->payment_method) === $methodLabel)>{{ $methodLabel }}</option>
                                @endforeach
                            </select>
                        </x-pw.field>

                        <x-pw.field label="Reference Number" for="payment_reference" required>
                            <input id="payment_reference" type="text" name="payment_reference" required
                                   value="{{ old('payment_reference', $transaction->payment_reference) }}"
                                   class="sad-field">
                        </x-pw.field>
                    </div>

                    <x-pw.field label="Upload Receipt" for="payment_proof" :required="! $transaction->payment_proof" error="payment_proof">
                        <label class="mt-2 flex h-36 w-full cursor-pointer items-center justify-center rounded-2xl border-2 border-dashed border-violet-200 bg-violet-50/40 px-4 transition hover:border-violet-400 hover:bg-violet-50">
                            <span class="text-center">
                                <svg class="mx-auto h-7 w-7 text-violet-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M12 16V4m0 0-4 4m4-4 4 4"/>
                                    <path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                                </svg>
                                <span id="file-name" class="mt-2 block break-all text-sm font-medium text-slate-600">
                                    {{ $transaction->payment_proof ? 'Click to replace the uploaded receipt' : 'Click to upload payment receipt' }}
                                </span>
                                <span class="mt-1 block text-xs text-slate-500">JPG, JPEG, or PNG only</span>
                            </span>

                            <input type="file"
                                   name="payment_proof"
                                   id="payment_proof"
                                   accept=".jpg,.jpeg,.png"
                                   class="sr-only"
                                   @required(! $transaction->payment_proof)>
                        </label>
                    </x-pw.field>

                    <x-pw.field label="Remarks" for="remarks" hint="Optional. Anything the admin should know about this payment.">
                        <textarea id="remarks" name="remarks" rows="3"
                                  class="sad-field">{{ old('remarks', $transaction->remarks) }}</textarea>
                    </x-pw.field>
                </x-pw.form-section>

                <x-pw.form-footer submit="Submit Payment Proof" :cancel-url="route('student.transactions')" />
            </form>

        @else

            {{-- ---------- Status ---------- --}}
            <section class="sad-card overflow-hidden">
                <x-pw.form-section title="Payment Status" first>
                    <x-slot:icon>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v4M12 16h.01"></path></svg>
                    </x-slot:icon>

                    @if($transaction->status === 'approved')
                        <p class="text-sm text-emerald-700">
                            Your payment has been approved. You may now access the course.
                        </p>

                        <a href="{{ route('student.learn.course', $transaction->course) }}"
                           class="sad-btn w-fit bg-emerald-600 text-white hover:bg-emerald-700">
                            Go to Course
                        </a>
                    @else
                        <p class="text-sm text-rose-700">
                            Your payment was rejected. Please contact the administrator or submit another purchase request.
                        </p>
                    @endif
                </x-pw.form-section>
            </section>

        @endif

    </div>

    <x-slot:aside>
        <x-pw.form-aside
            title="Payment Guidelines"
            :tips="[
                'Upload a clear photo or screenshot of the receipt.',
                'Enter the reference number exactly as it appears on the receipt.',
                'An admin checks the receipt; you can open the course once it is approved.',
                'While the payment is pending you can replace the receipt.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

<script>
    // In a block, because wire:navigate runs this script again on each visit.
    {
        const paymentProofInput = document.getElementById('payment_proof');
        const fileName = document.getElementById('file-name');

        if (paymentProofInput && fileName) {
            paymentProofInput.addEventListener('change', function () {
                if (this.files.length > 0) {
                    fileName.innerText = this.files[0].name;
                }
            });
        }
    }
</script>

</x-layouts::app>
