{{-- Shared by the course page and the preview lesson banner. Needs $course. --}}
<form
    action="{{ route('student.transactions.store', $course) }}"
    method="POST"
    class="mt-4"
    onsubmit="
        const button =
            this.querySelector(
                '[data-purchase-button]'
            );

        if (button) {
            button.disabled = true;
            button.innerText =
                'Opening PayMongo...';
        }
    "
>
    @csrf

    <button
        type="submit"
        data-purchase-button
        class="sad-btn-primary inline-flex
               h-11 w-full
               items-center
               justify-center gap-2
               rounded-xl
               bg-violet-600
               text-sm font-semibold
               text-white
               transition
               hover:bg-violet-700"
    >

        <svg
            class="h-4 w-4"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
        >
            <rect
                x="3"
                y="5"
                width="18"
                height="14"
                rx="2"
            ></rect>

            <path d="M3 10h18"></path>
        </svg>

        Purchase Course
    </button>

    {{-- Enabled PayMongo methods (PAYMONGO_PAYMENT_METHODS). --}}
    <p class="mt-2 text-center text-xs text-slate-500">
        Pay securely with {{ implode(', ', array_values(\App\Support\PaymentMethods::labels())) }} via PayMongo.
    </p>

</form>
