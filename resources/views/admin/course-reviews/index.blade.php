<x-layouts::app :title="'Course Reviews'">
@include('partials.soft3d')

<div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">

    {{-- Header card, as on My Certificates. --}}
    <section class="sad-banner overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">
        <div class="relative px-6 py-6 sm:px-7">
            <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full bg-violet-100/70 blur-3xl"></div>

    <div class="relative">
        <h1 class="pw-heading text-2xl font-bold">Course Reviews</h1>
        <p class="pw-muted mt-1 text-sm">
            Hide reviews that are inappropriate. Hidden reviews are left out of the course rating and the public lists.
        </p>
    </div>
        </div>
    </section>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.course-reviews.index') }}" class="flex flex-wrap items-end gap-2">
        <div>
            <label for="review_status" class="pw-text block text-xs font-semibold">Show</label>
            <select id="review_status" name="status" class="pw-select mt-1 h-10 px-3 text-sm">
                <option value="all" @selected($filter === 'all')>All reviews</option>
                <option value="visible" @selected($filter === 'visible')>Visible</option>
                <option value="hidden" @selected($filter === 'hidden')>Hidden</option>
            </select>
        </div>
        <button type="submit" class="pw-btn-secondary h-10 rounded-xl px-4 text-sm font-semibold">
            Filter
        </button>
    </form>

    <div class="sad-card pw-card overflow-hidden">
        @if ($reviews->isEmpty())
            <div class="p-10 text-center">
                <h2 class="pw-heading font-semibold">No reviews found</h2>
                <p class="pw-muted mt-2 text-sm">Student reviews of published courses will appear here.</p>
            </div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($reviews as $review)
                    <li class="grid gap-4 p-5 lg:grid-cols-[1fr_20rem]">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="pw-heading text-sm font-semibold">{{ $review->student?->name ?? 'Student' }}</span>
                                <span class="pw-soft text-xs">{{ $review->student?->email }}</span>
                                @if ($review->is_hidden)
                                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">Hidden</span>
                                @else
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Visible</span>
                                @endif
                            </div>

                            <p class="pw-muted mt-1 truncate text-xs">
                                {{ $review->course?->title ?? 'Course' }} · {{ $review->created_at?->format('M d, Y') }}
                            </p>

                            <x-rating-stars :rating="$review->rating" class="mt-2" />

                            @if (filled($review->comment))
                                <p class="pw-text mt-2 whitespace-pre-line break-words text-sm leading-6">{{ $review->comment }}</p>
                            @else
                                <p class="pw-soft mt-2 text-sm italic">No comment</p>
                            @endif

                            @if ($review->is_hidden)
                                <p class="mt-2 text-xs text-amber-800">
                                    Hidden by {{ $review->hiddenBy?->name ?? 'an administrator' }}: {{ $review->hidden_reason }}
                                </p>
                            @endif
                        </div>

                        <div>
                            @if ($review->is_hidden)
                                <form method="POST" action="{{ route('admin.course-reviews.unhide', $review) }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        onclick="return confirm('Show this review again?')"
                                        class="pw-btn-secondary h-9 w-full rounded-lg px-3 text-xs font-semibold"
                                    >
                                        Unhide
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.course-reviews.hide', $review) }}" class="space-y-2">
                                    @csrf
                                    <label for="hidden_reason_{{ $review->id }}" class="sr-only">Reason for hiding</label>
                                    <textarea
                                        id="hidden_reason_{{ $review->id }}"
                                        name="hidden_reason"
                                        rows="2"
                                        required
                                        maxlength="{{ \App\Models\CourseReview::HIDDEN_REASON_MAX_LENGTH }}"
                                        placeholder="Reason for hiding (required)"
                                        class="pw-textarea p-2 text-sm"
                                    ></textarea>
                                    <button
                                        type="submit"
                                        class="h-9 w-full rounded-lg bg-red-600 px-3 text-xs font-semibold text-white hover:bg-red-700"
                                    >
                                        Hide
                                    </button>
                                </form>
                            @endif
                        </div>
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
