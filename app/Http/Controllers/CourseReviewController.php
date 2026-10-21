<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseReview;
use App\Notifications\PathwiseNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Course reviews: students rate and review courses they are enrolled in
 * (CourseReview::studentCanReview), teachers read the visible reviews of
 * their own courses, and administrators hide or unhide reviews.
 */
class CourseReviewController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | STUDENT
    |--------------------------------------------------------------------------
    */

    public function store(Request $request, Course $course)
    {
        abort_unless(CourseReview::studentCanReview($request->user(), $course), 403);

        $existing = CourseReview::where('course_id', $course->id)
            ->where('student_id', $request->user()->id)
            ->first();

        if ($existing) {
            return $this->backToCourse($course)->with(
                'error',
                $existing->is_hidden
                    ? 'Your review was hidden by an administrator and cannot be changed.'
                    : 'You have already reviewed this course. You can edit your review instead.'
            );
        }

        $review = new CourseReview($this->validated($request));
        $review->course_id = $course->id;
        $review->student_id = $request->user()->id;
        $review->save();

        $course->teacher?->notify(new PathwiseNotification(
            'New course review',
            $request->user()->name.' rated '.$course->title.' '.$review->rating.' out of 5.',
            'course_review',
            $course->id
        ));

        return $this->backToCourse($course)->with('success', 'Thank you! Your review has been posted.');
    }

    public function update(Request $request, CourseReview $courseReview)
    {
        $this->ensureOwnEditableReview($request, $courseReview);

        $courseReview->update($this->validated($request));

        return $this->backToCourse($courseReview->course)->with('success', 'Your review has been updated.');
    }

    public function destroy(Request $request, CourseReview $courseReview)
    {
        $this->ensureOwnEditableReview($request, $courseReview);

        $course = $courseReview->course;
        $courseReview->delete();

        return $this->backToCourse($course)->with('success', 'Your review has been deleted.');
    }

    /*
    |--------------------------------------------------------------------------
    | TEACHER
    |--------------------------------------------------------------------------
    */

    public function teacherIndex(Request $request, Course $course)
    {
        abort_unless((int) $course->teacher_id === (int) $request->user()->id, 403);

        $reviews = $course->visibleReviews()
            ->with('student:id,name')
            ->latest()
            ->paginate(10);

        $summary = self::summary($course);

        return view('Teacher.course-reviews', compact('course', 'reviews', 'summary'));
    }

    /**
     * Visible reviews from all of the teacher's own courses, newest first,
     * with an optional course filter. A course that is not the teacher's
     * own matches nothing, so other teachers' reviews never appear.
     */
    public function teacherAll(Request $request)
    {
        $courses = Course::where('teacher_id', $request->user()->id)
            ->orderBy('title')
            ->get(['id', 'title']);

        $courseId = $request->integer('course') ?: null;

        $reviews = CourseReview::visible()
            ->whereIn('course_id', $courses->pluck('id'))
            ->when($courseId, fn ($query) => $query->where('course_id', $courseId))
            ->with(['student:id,name', 'course:id,title'])
            ->latest()
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('Teacher.course-reviews-all', compact('courses', 'courseId', 'reviews'));
    }

    /*
    |--------------------------------------------------------------------------
    | ADMINISTRATOR
    |--------------------------------------------------------------------------
    */

    public function adminIndex(Request $request)
    {
        $filter = $request->query('status', 'all');

        $reviews = CourseReview::with(['course:id,title', 'student:id,name,email', 'hiddenBy:id,name'])
            ->when($filter === 'visible', fn ($query) => $query->where('is_hidden', false))
            ->when($filter === 'hidden', fn ($query) => $query->where('is_hidden', true))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.course-reviews.index', compact('reviews', 'filter'));
    }

    public function hide(Request $request, CourseReview $courseReview)
    {
        $validated = $request->validate(
            ['hidden_reason' => ['required', 'string', 'max:'.CourseReview::HIDDEN_REASON_MAX_LENGTH]],
            ['hidden_reason.required' => 'Please write the reason for hiding this review.']
        );

        $courseReview->forceFill([
            'is_hidden' => true,
            'hidden_reason' => trim($validated['hidden_reason']),
            'hidden_by' => $request->user()->id,
        ])->save();

        return back()->with('success', 'The review is now hidden.');
    }

    public function unhide(CourseReview $courseReview)
    {
        $courseReview->forceFill([
            'is_hidden' => false,
            'hidden_reason' => null,
            'hidden_by' => null,
        ])->save();

        return back()->with('success', 'The review is visible again.');
    }

    /*
    |--------------------------------------------------------------------------
    | SHARED
    |--------------------------------------------------------------------------
    */

    /**
     * Average rating (one decimal) and number of visible reviews.
     *
     * @return array{average: ?float, count: int}
     */
    public static function summary(Course $course): array
    {
        $visible = $course->visibleReviews();
        $count = (clone $visible)->count();

        return [
            'average' => $count > 0 ? round((float) (clone $visible)->avg('rating'), 1) : null,
            'count' => $count,
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'rating' => [
                'required',
                'integer',
                Rule::in(range(CourseReview::MIN_RATING, CourseReview::MAX_RATING)),
            ],
            'comment' => ['nullable', 'string', 'max:'.CourseReview::COMMENT_MAX_LENGTH],
        ], [
            'rating.required' => 'Please choose a rating from 1 to 5 stars.',
            'rating.in' => 'The rating must be from 1 to 5 stars.',
            'comment.max' => 'The comment may not be longer than '.CourseReview::COMMENT_MAX_LENGTH.' characters.',
        ]);
    }

    /**
     * Students may change only their own review, only while they still
     * have access to the course, and never while it is hidden.
     */
    private function ensureOwnEditableReview(Request $request, CourseReview $review): void
    {
        abort_unless((int) $review->student_id === (int) $request->user()->id, 403);

        abort_if($review->is_hidden, 403, 'This review was hidden by an administrator and cannot be changed.');

        abort_unless(CourseReview::studentCanReview($request->user(), $review->course), 403);
    }

    private function backToCourse(Course $course)
    {
        return redirect()->to(route('student.course.show', $course).'#reviews');
    }
}
