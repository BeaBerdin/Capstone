<x-layouts::app :title="'Transaction Details'">
@include('partials.soft3d')

<style>
    .pw-card {
        background: #ffffff;
        border: 1px solid #e7e9ef;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.035);
    }

    .pw-label {
        display: block;
        margin-bottom: 6px;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
    }

    .pw-input {
        width: 100%;
        min-height: 44px;
        border: 1px solid #e1e4ea;
        border-radius: 12px;
        background: #ffffff;
        padding: 10px 14px;
        color: #334155;
        font-size: 14px;
        transition: border-color 160ms ease, box-shadow 160ms ease;
    }

    .pw-input:focus {
        outline: none;
        border-color: #8b5cf6;
        box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.08);
    }
</style>

<div class="sad-page min-h-screen bg-[#f8f9fc]">

    <main class="px-4 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-4xl space-y-6">

            {{-- =====================================================
                HEADER
            ====================================================== --}}

            {{-- Header card, as on My Certificates. --}}
            <section class="sad-banner overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">
                <div class="relative px-6 py-6 sm:px-7">
                    <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full bg-violet-100/70 blur-3xl"></div>

            <div class="relative">
                <a href="{{ route('student.transactions') }}" class="text-xs font-semibold text-violet-600 hover:text-violet-700">
                    &larr; Back to Transactions
                </a>

                <p class="mt-5 text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                    Payments
                </p>

                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                    Transaction Details
                </h1>

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Review your payment transaction and upload your proof of payment.
                </p>
            </div>
                </div>
            </section>

            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700">
                    {{ session('error') }}
                </div>
            @endif


            {{-- =====================================================
                TRANSACTION
            ====================================================== --}}

            <section class="sad-card pw-card p-5 sm:p-7">

                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-slate-500">Transaction No.</p>
                        <h2 class="mt-1 break-all text-xl font-bold text-slate-950">
                            {{ $transaction->transaction_no }}
                        </h2>
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

                <dl class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
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
                </dl>

                @if($transaction->payment_reference)
                    <div class="mt-4 rounded-xl bg-slate-50 px-4 py-3">
                        <p class="text-xs font-bold uppercase tracking-[.08em] text-slate-500">Payment Reference</p>
                        <p class="mt-1 break-all text-sm font-semibold text-slate-800">
                            {{ $transaction->payment_reference }}
                        </p>
                    </div>
                @endif

                @if($transaction->payment_proof)
                    <div class="mt-6">
                        <p class="mb-2 text-sm font-semibold text-slate-700">
                            Uploaded Receipt
                        </p>

                        <a href="{{ route('files.receipt', $transaction) }}"
                           target="_blank"
                           class="inline-block">
                            <img src="{{ route('files.receipt', $transaction) }}"
                                 alt="Payment Proof"
                                 class="max-h-72 w-auto max-w-full rounded-xl border border-slate-200 object-contain shadow-sm">
                        </a>

                        <p class="mt-2 text-xs text-slate-500">
                            Click image to view full receipt.
                        </p>
                    </div>
                @endif

            </section>


            @if($transaction->status === 'pending')

                {{-- =====================================================
                    UPLOAD PROOF
                ====================================================== --}}

                <section class="sad-card pw-card p-5 sm:p-7">

                    <h3 class="text-lg font-bold text-slate-900">
                        Upload / Replace Payment Proof
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Submit your {{ implode(', ', array_values(\App\Support\PaymentMethods::labels())) }} or bank transfer receipt for admin verification.
                    </p>

                    <form action="{{ route('student.transactions.upload-proof', $transaction) }}"
                          method="POST"
                          enctype="multipart/form-data"
                          class="mt-6 space-y-5">
                        @csrf

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label for="payment_method" class="pw-label">
                                    Payment Method
                                </label>

                                <select id="payment_method"
                                        name="payment_method"
                                        class="pw-input"
                                        required>
                                    <option value="">Select payment method</option>
                                    {{-- Enabled PayMongo methods (PAYMONGO_PAYMENT_METHODS) plus bank transfer. --}}
                                    @foreach (\App\Support\PaymentMethods::proofOptions() as $methodLabel)
                                        <option value="{{ $methodLabel }}" @selected(old('payment_method', $transaction->payment_method) === $methodLabel)>{{ $methodLabel }}</option>
                                    @endforeach
                                </select>

                                @error('payment_method')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="payment_reference" class="pw-label">
                                    Reference Number
                                </label>

                                <input type="text"
                                       id="payment_reference"
                                       name="payment_reference"
                                       value="{{ old('payment_reference', $transaction->payment_reference) }}"
                                       class="pw-input"
                                       required>

                                @error('payment_reference')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <span class="pw-label">
                                Upload Receipt
                            </span>

                            <label class="flex h-36 w-full cursor-pointer items-center justify-center rounded-2xl border-2 border-dashed border-violet-200 bg-violet-50/40 px-4 transition hover:border-violet-400 hover:bg-violet-50">
                                <div class="text-center">
                                    <svg class="mx-auto h-7 w-7 text-violet-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M12 16V4m0 0-4 4m4-4 4 4"/>
                                        <path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                                    </svg>
                                    <p id="file-name" class="mt-2 break-all text-sm font-medium text-slate-600">
                                        Click to upload payment receipt
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
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
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="remarks" class="pw-label">
                                Remarks
                            </label>

                            <textarea id="remarks"
                                      name="remarks"
                                      rows="3"
                                      class="pw-input">{{ old('remarks', $transaction->remarks) }}</textarea>
                        </div>

                        <button type="submit"
                                class="sad-btn-primary inline-flex h-11 w-full items-center justify-center rounded-xl bg-violet-600 px-5 text-sm font-semibold text-white transition hover:bg-violet-700 sm:w-auto">
                            Submit Payment Proof
                        </button>
                    </form>
                </section>

            @else

                {{-- =====================================================
                    STATUS
                ====================================================== --}}

                <section class="sad-card pw-card p-5 sm:p-7">
                    <h3 class="text-lg font-bold text-slate-900">
                        Payment Status
                    </h3>

                    @if($transaction->status === 'approved')
                        <p class="mt-2 text-sm text-emerald-700">
                            Your payment has been approved. You may now access the course.
                        </p>

                        <a href="{{ route('student.learn.course', $transaction->course) }}"
                           class="mt-4 inline-flex h-11 items-center justify-center rounded-xl bg-emerald-600 px-5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            Go to Course
                        </a>
                    @else
                        <p class="mt-2 text-sm text-rose-700">
                            Your payment was rejected. Please contact the administrator or submit another purchase request.
                        </p>
                    @endif
                </section>

            @endif

        </div>

    </main>

</div>

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
