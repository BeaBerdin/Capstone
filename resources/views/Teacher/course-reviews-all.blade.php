<x-layouts::app :title="'Course Reviews'">

<div class="mx-auto max-w-4xl space-y-6 p-4 md:p-6">

    <div>
        <h1 class="pw-heading text-2xl font-bold">Course Reviews</h1>
        <p class="pw-muted mt-1 text-sm">
            What students say about your courses, newest first.
        </p>
    </div>

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

    <div class="pw-card overflow-hidden">
        @if ($reviews->isEmpty())
            <div class="p-10 text-center">
                <h2 class="pw-heading font-semibold">No reviews yet</h2>
                <p class="pw-muted mt-2 text-sm">
                    Students with an active or completed enrollment can rate your published courses.
                </p>
            </div>
        @else
            <ul class="divide-y divide-slate-100 dark:divide-zinc-800">
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
                <div class="border-t border-slate-100 p-4 dark:border-zinc-800">
                    {{ $reviews->links() }}
                </div>
            @endif
        @endif
    </div>

</div>

</x-layouts::app>
