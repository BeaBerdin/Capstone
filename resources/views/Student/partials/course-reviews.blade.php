{{--
    Course reviews on the course page (Student.course-show).
    Needs: $course, $reviewSummary, $reviews, $myReview, $canReview.
    Comments are printed with {{ }}, so they are always escaped text.
--}}
<section id="reviews" class="sad-card pw-card mt-6 scroll-mt-24 overflow-hidden">

    <div class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
        <div>
            <h2 class="pw-heading text-lg font-bold">Reviews</h2>
            <p class="pw-muted mt-1 text-sm">What students say about this course.</p>
        </div>

        <div class="flex items-center gap-3">
            @if ($reviewSummary['count'] > 0)
                <span class="pw-heading text-3xl font-bold">{{ number_format($reviewSummary['average'], 1) }}</span>
                <div>
                    <x-rating-stars :rating="$reviewSummary['average']" size="h-5 w-5" />
                    <p class="pw-muted mt-1 text-xs">
                        {{ $reviewSummary['count'] }} {{ \Illuminate\Support\Str::plural('review', $reviewSummary['count']) }}
                    </p>
                </div>
            @else
                <x-rating-stars :rating="null" size="h-5 w-5" />
                <span class="pw-muted text-sm">No reviews yet</span>
            @endif
        </div>
    </div>

    <div class="space-y-6 p-5 sm:p-6">

        {{-- THE STUDENT'S OWN REVIEW / FORM --}}
        @if ($myReview && $myReview->is_hidden)

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                        Hidden by an administrator
                    </span>
                    <x-rating-stars :rating="$myReview->rating" />
                </div>

                @if (filled($myReview->comment))
                    <p class="mt-3 whitespace-pre-line break-words text-sm text-slate-700">{{ $myReview->comment }}</p>
                @endif

                <p class="mt-3 text-xs text-amber-800">
                    Reason: {{ $myReview->hidden_reason }}
                </p>
                <p class="mt-1 text-xs text-amber-700">
                    Only you can see this review. It cannot be edited or deleted while it is hidden.
                </p>
            </div>

        @elseif ($canReview)

            <form
                method="POST"
                action="{{ $myReview ? route('student.reviews.update', $myReview) : route('student.reviews.store', $course) }}"
                class="rounded-xl border border-violet-100 bg-violet-50/50 p-4 sm:p-5"
            >
                @csrf
                @if ($myReview)
                    @method('PUT')
                @endif

                <h3 class="pw-heading text-sm font-bold">
                    {{ $myReview ? 'Edit your review' : 'Write a Review' }}
                </h3>

                <fieldset class="mt-3">
                    <legend class="pw-text text-xs font-semibold">Your rating</legend>

                    @php
                        $selectedRating = (int) old('rating', $myReview?->rating);
                    @endphp

                    <div class="mt-2 flex flex-wrap gap-2">
                        @for ($value = 1; $value <= 5; $value++)
                            <label class="cursor-pointer">
                                <input
                                    type="radio"
                                    name="rating"
                                    value="{{ $value }}"
                                    class="peer sr-only"
                                    @checked($selectedRating === $value)
                                    required
                                >
                                <span class="inline-flex h-10 items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 transition peer-checked:border-amber-400 peer-checked:bg-amber-50 peer-checked:text-amber-700 peer-focus-visible:ring-2 peer-focus-visible:ring-violet-500">
                                    <svg class="h-4 w-4 text-amber-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.36 4.18a1 1 0 0 0 .95.69h4.4c.97 0 1.37 1.24.59 1.81l-3.56 2.59a1 1 0 0 0-.36 1.12l1.36 4.18c.3.92-.75 1.69-1.54 1.12l-3.56-2.59a1 1 0 0 0-1.18 0l-3.56 2.59c-.79.57-1.84-.2-1.54-1.12l1.36-4.18a1 1 0 0 0-.36-1.12L1.75 9.6c-.78-.57-.38-1.81.59-1.81h4.4a1 1 0 0 0 .95-.69l1.36-4.18Z" />
                                    </svg>
                                    {{ $value }}
                                    <span class="sr-only">{{ \Illuminate\Support\Str::plural('star', $value) }}</span>
                                </span>
                            </label>
                        @endfor
                    </div>

                    @error('rating')
                        <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </fieldset>

                <label for="review_comment" class="pw-text mt-4 block text-xs font-semibold">
                    Comment <span class="pw-muted font-normal">(optional)</span>
                </label>
                <textarea
                    id="review_comment"
                    name="comment"
                    rows="4"
                    maxlength="{{ \App\Models\CourseReview::COMMENT_MAX_LENGTH }}"
                    placeholder="What did you like? What could be better?"
                    class="pw-textarea mt-2 p-3 text-sm"
                >{{ old('comment', $myReview?->comment) }}</textarea>
                <p class="pw-muted mt-1 text-xs">Up to {{ number_format(\App\Models\CourseReview::COMMENT_MAX_LENGTH) }} characters.</p>

                @error('comment')
                    <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror

                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="submit" class="sad-btn-primary inline-flex h-10 items-center justify-center rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white transition hover:bg-violet-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2">
                        {{ $myReview ? 'Save Changes' : 'Post Review' }}
                    </button>
                </div>
            </form>

            @if ($myReview)
                <form
                    method="POST"
                    action="{{ route('student.reviews.destroy', $myReview) }}"
                    onsubmit="return confirm('Delete your review of this course?')"
                    class="-mt-3"
                >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700 hover:underline">
                        Delete my review
                    </button>
                </form>
            @endif

        @endif

        {{-- PUBLIC LIST (visible reviews only) --}}
        @if ($reviews->isEmpty())
            <p class="pw-muted py-4 text-center text-sm">
                No reviews yet. Students who take this course can rate it here.
            </p>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($reviews as $review)
                    <li class="py-4 first:pt-0 last:pb-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="pw-heading text-sm font-semibold">{{ $review->student?->name ?? 'Student' }}</span>
                                @if ((int) $review->student_id === (int) auth()->id())
                                    <span class="rounded-full bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-700">You</span>
                                @endif
                            </div>
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
                <div class="border-t border-slate-100 pt-4">
                    {{ $reviews->links() }}
                </div>
            @endif
        @endif

    </div>
</section>
