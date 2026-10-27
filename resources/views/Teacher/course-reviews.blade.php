<x-layouts::app :title="'Course Reviews'">
@include('partials.soft3d')

<div class="mx-auto max-w-4xl space-y-6 p-4 md:p-6">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <a href="{{ route('teacher.my-courses') }}" class="text-xs font-semibold text-violet-600 hover:text-violet-800">
                ← My Courses
            </a>
            <h1 class="pw-heading mt-2 text-2xl font-bold">Course Reviews</h1>
            <p class="pw-muted mt-1 truncate text-sm">{{ $course->title }}</p>
        </div>

        <div class="flex items-center gap-3">
            @if ($summary['count'] > 0)
                <span class="pw-heading text-3xl font-bold">{{ number_format($summary['average'], 1) }}</span>
                <div>
                    <x-rating-stars :rating="$summary['average']" size="h-5 w-5" />
                    <p class="pw-muted mt-1 text-xs">
                        {{ $summary['count'] }} {{ \Illuminate\Support\Str::plural('review', $summary['count']) }}
                    </p>
                </div>
            @else
                <span class="pw-muted text-sm">No reviews yet</span>
            @endif
        </div>
    </div>

    <div class="sad-card pw-card overflow-hidden">
        @if ($reviews->isEmpty())
            <div class="p-10 text-center">
                <h2 class="pw-heading font-semibold">No reviews yet</h2>
                <p class="pw-muted mt-2 text-sm">
                    Students with an active or completed enrollment can rate this course from the course page.
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

                        <x-rating-stars :rating="$review->rating" class="mt-1" />

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
