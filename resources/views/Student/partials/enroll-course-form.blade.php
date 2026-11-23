{{-- Shared by the course page and the preview lesson banner. Needs $course. --}}
<form
    action="{{ route('student.enroll', $course) }}"
    method="POST"
    class="mt-4"
    onsubmit="
        const button =
            this.querySelector(
                '[data-enroll-button]'
            );

        if (button) {
            if (button.disabled) {
                return false;
            }

            button.disabled = true;
            button.innerText =
                'Enrolling...';
        }
    "
>
    @csrf

    <button
        type="submit"
        data-enroll-button
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
            <path
                d="M9 11l3 3
                   L22 4"
            ></path>

            <path
                d="M21 12v7
                   a2 2 0 01-2 2
                   H5a2 2 0 01-2-2
                   V5a2 2 0 012-2h11"
            ></path>
        </svg>

        Enroll for Free
    </button>

</form>
