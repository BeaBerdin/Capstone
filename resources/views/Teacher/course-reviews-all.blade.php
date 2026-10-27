<x-layouts::app :title="'Course Reviews'">
@include('partials.soft3d')

<div class="mx-auto max-w-4xl space-y-6 p-4 md:p-6">

    {{-- Header card, as on My Certificates. --}}
    <section class="sad-banner overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">
        <div class="relative px-6 py-6 sm:px-7">
            <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full bg-violet-100/70 blur-3xl"></div>

    <div class="relative">
        <h1 class="pw-heading text-2xl font-bold">Course Reviews</h1>
        <p class="pw-muted mt-1 text-sm">
            What students say about your courses, newest first.
        </p>
    </div>
        </div>
    </section>

    <form method="GET" action="{{ route('teacher.course-reviews.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
        <div class="min-w-0 flex-1">
            <label for="review_course" class="pw-text block text-xs font-semibold">Course</label>
            <select id="review_course" name="course" class="pw-select mt-1 h-10 px-3 text-sm">
                <option value="">All my courses</option>
                @foreach ($courses as $courseOption)
                    <option value="{{ $courseOption->id }}" @selected($courseId === $courseOption->id)>
                        {{ $courseOption->title }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="pw-btn-secondary h-10 rounded-xl px-4 text-sm font-semibold">
            Filter
        </button>
    </form>

    <div class="sad-card pw-card overflow-hidden">
        @if ($reviews->isEmpty())
            <div class="p-10 text-center">
                <h2 class="pw-heading font-semibold">No reviews yet</h2>
                <p class="pw-muted mt-2 text-sm">
                    Students with an active or completed enrollment can rate your published courses.
                </p>
            </div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($reviews as $review)
                    <li class="p-5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="pw-heading text-sm font-semibold">{{ $review->student?->name ?? 'Student' }}</span>
                            <span class="pw-soft text-xs">{{ $review->created_at?->format('M d, Y') }}</span>
                        </div>

                        <a
                            href="{{ route('teacher.courses.reviews', $review->course_id) }}"
                            class="mt-1 block truncate text-xs font-semibold text-violet-600 hover:text-violet-800"
                        >
                            {{ $review->course?->title ?? 'Course' }}
                        </a>

                        <x-rating-stars :rating="$review->rating" class="mt-2" />

                        @if (filled($review->comment))
                            <p class="pw-text mt-2 whitespace-pre-line break-words text-sm leading-6">{{ $review->comment }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>

            @if ($reviews->hasPages())
                <div class="border-t border-slate-100 p-4">
                    {{ $reviews->links() }}
                </div>
            @endif
        @endif
    </div>

</div>

</x-layouts::app>
