{{--
    Banner on a preview lesson (student not enrolled). Uses the same Enroll
    (free) and Purchase (paid) forms as the course page.
    Needs $course; optional $pendingTransaction, $previewEnrollmentCancelled.
--}}
@php
    $previewIsFree = (float) ($course->price ?? 0) <= 0;
    $pendingTransaction = $pendingTransaction ?? null;
    $previewEnrollmentCancelled = $previewEnrollmentCancelled ?? false;
@endphp

<section
    data-preview-banner
    class="sad-card mt-6 rounded-2xl border border-violet-200 bg-violet-50 p-5 sm:p-6"
>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-600">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </div>

            <div>
                <p class="text-sm font-bold text-violet-900">
                    You are previewing this lesson. Enroll to access the full course.
                </p>
                <p class="mt-1 text-xs leading-5 text-violet-700">
                    Your progress is not saved while previewing.
                </p>
            </div>
        </div>

        <div class="w-full shrink-0 lg:w-72 [&>form]:mt-0">
            @if($previewEnrollmentCancelled)
                <p class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-medium text-rose-700">
                    Your enrollment was cancelled. Contact the course administrator.
                </p>
            @elseif($pendingTransaction)
                <a
                    href="{{ route('student.transactions.show', $pendingTransaction) }}"
                    class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-orange-500 text-sm font-semibold text-white transition hover:bg-orange-600"
                >
                    View Transaction
                </a>
            @elseif(! $previewIsFree)
                @include('Student.partials.purchase-course-form', ['course' => $course])
            @else
                @include('Student.partials.enroll-course-form', ['course' => $course])
            @endif
        </div>
    </div>
</section>
