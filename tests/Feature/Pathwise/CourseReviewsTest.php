<?php

use App\Http\Controllers\CourseReviewController;
use App\Models\Course;
use App\Models\Department;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use App\Notifications\PathwiseNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Notification;

/*
| Course reviews: students with an active or completed enrollment rate a
| published course (1-5) with an optional comment; one review each.
| Administrators hide reviews with a reason; hidden reviews are left out
| of the average, the count and the public lists.
*/

function reviewCourse(array $attributes = [], ?Department $department = null): Course
{
    return pathwiseCourse(pathwiseApprovedTeacher( ['department_id' => $department?->id]), array_merge(['status' => 'published'], $attributes));
}

function reviewEnroll(Course $course, string $status = 'active', ?User $user = null): User
{
    $user ??= pathwiseUser('student');

    Enrollment::create([
        'student_id' => $user->id,
        'course_id' => $course->id,
        'status' => $status,
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    return $user;
}

function postReview(Course $course, User $student, int $rating = 5, ?string $comment = 'Great course.')
{
    return test()->actingAs($student)->post(route('student.reviews.store', $course), [
        'rating' => $rating,
        'comment' => $comment,
    ]);
}

function makeReview(Course $course, User $student, int $rating, bool $hidden = false): CourseReview
{
    $review = new CourseReview(['rating' => $rating, 'comment' => 'Review '.$rating]);
    $review->course_id = $course->id;
    $review->student_id = $student->id;
    $review->forceFill([
        'is_hidden' => $hidden,
        'hidden_reason' => $hidden ? 'Spam' : null,
    ])->save();

    return $review;
}

beforeEach(fn () => Notification::fake());

/*
|--------------------------------------------------------------------------
| Who may review
|--------------------------------------------------------------------------
*/

test('a student with an active or completed enrollment can review', function (string $status) {
    $course = reviewCourse();
    $student = reviewEnroll($course, $status);

    postReview($course, $student, 4, 'Clear lessons.')
        ->assertRedirect(route('student.course.show', $course).'#reviews')
        ->assertSessionHas('success');

    $review = CourseReview::sole();

    expect($review->rating)->toBe(4)
        ->and($review->comment)->toBe('Clear lessons.')
        ->and($review->student_id)->toBe($student->id)
        ->and($review->is_hidden)->toBeFalse();
})->with(['active', 'completed']);

test('cancelled and unenrolled students cannot review', function (string $case) {
    $course = reviewCourse();
    $student = $case === 'cancelled' ? reviewEnroll($course, 'cancelled') : pathwiseUser('student');

    postReview($course, $student)->assertForbidden();

    expect(CourseReview::count())->toBe(0);
})->with(['cancelled', 'not enrolled']);

test('teachers, administrators and the Super Admin cannot review, even when enrolled', function (string $role) {
    $course = reviewCourse();
    $user = pathwiseRoleBoundaryUser($role);
    $studentRole = Role::firstOrCreate(['name' => 'student']);
    $user->roles()->attach($studentRole->id);
    reviewEnroll($course, 'active', $user);

    postReview($course, $user)->assertForbidden();

    expect(CourseReview::count())->toBe(0);
})->with(['teacher', 'admin', 'super_admin']);

test('the course\'s own teacher cannot review it', function () {
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher, ['status' => 'published']);

    $this->actingAs($teacher)
        ->post(route('student.reviews.store', $course), ['rating' => 5])
        ->assertForbidden();

    expect(CourseReview::studentCanReview($teacher, $course))->toBeFalse();
});

test('unpublished courses cannot be reviewed', function (string $status) {
    $course = reviewCourse(['status' => $status]);
    $student = reviewEnroll($course);

    postReview($course, $student)->assertForbidden();

    expect(CourseReview::count())->toBe(0);
})->with(['draft', 'pending', 'rejected']);

test('a student can review a course only once', function () {
    $course = reviewCourse();
    $student = reviewEnroll($course);

    postReview($course, $student, 5);
    postReview($course, $student, 1)->assertSessionHas('error');

    expect(CourseReview::count())->toBe(1)
        ->and(CourseReview::sole()->rating)->toBe(5);
});

test('the database allows one review per student per course', function () {
    $course = reviewCourse();
    $student = reviewEnroll($course);

    makeReview($course, $student, 5);

    expect(fn () => makeReview($course, $student, 3))
        ->toThrow(QueryException::class);
});

/*
|--------------------------------------------------------------------------
| Edit and delete
|--------------------------------------------------------------------------
*/

test('a student can edit and delete their own review', function () {
    $course = reviewCourse();
    $student = reviewEnroll($course);
    $review = makeReview($course, $student, 3);

    $this->actingAs($student)
        ->put(route('student.reviews.update', $review), ['rating' => 5, 'comment' => 'Better after the last lessons.'])
        ->assertSessionHas('success');

    expect($review->fresh()->rating)->toBe(5)
        ->and($review->fresh()->comment)->toBe('Better after the last lessons.');

    $this->actingAs($student)
        ->delete(route('student.reviews.destroy', $review))
        ->assertSessionHas('success');

    expect(CourseReview::count())->toBe(0);
});

test('nobody can edit or delete another student\'s review', function (string $role) {
    $course = reviewCourse();
    $review = makeReview($course, reviewEnroll($course), 4);

    $other = $role === 'student' ? reviewEnroll($course) : pathwiseRoleBoundaryUser($role);

    $this->actingAs($other)->put(route('student.reviews.update', $review), ['rating' => 1])->assertForbidden();
    $this->actingAs($other)->delete(route('student.reviews.destroy', $review))->assertForbidden();

    expect($review->fresh()->rating)->toBe(4);
})->with(['another enrolled student' => 'student', 'teacher' => 'teacher', 'admin' => 'admin', 'super admin' => 'super_admin']);

test('a hidden review is locked for its author', function () {
    $course = reviewCourse();
    $student = reviewEnroll($course);
    $review = makeReview($course, $student, 2, hidden: true);

    $this->actingAs($student)->put(route('student.reviews.update', $review), ['rating' => 5])->assertForbidden();
    $this->actingAs($student)->delete(route('student.reviews.destroy', $review))->assertForbidden();
    postReview($course, $student, 5)->assertSessionHas('error');

    expect(CourseReview::count())->toBe(1)
        ->and($review->fresh()->rating)->toBe(2);
});

test('a student whose enrollment was cancelled can no longer change their review', function () {
    $course = reviewCourse();
    $student = reviewEnroll($course);
    $review = makeReview($course, $student, 4);

    Enrollment::where('student_id', $student->id)->update(['status' => 'cancelled']);

    $this->actingAs($student)->put(route('student.reviews.update', $review), ['rating' => 1])->assertForbidden();

    expect($review->fresh()->rating)->toBe(4);
});

/*
|--------------------------------------------------------------------------
| Validation and escaping
|--------------------------------------------------------------------------
*/

test('the rating must be from 1 to 5', function ($rating) {
    $course = reviewCourse();
    $student = reviewEnroll($course);

    $this->actingAs($student)
        ->post(route('student.reviews.store', $course), ['rating' => $rating])
        ->assertSessionHasErrors('rating');

    expect(CourseReview::count())->toBe(0);
})->with([0, 6, -1, 'five', null, 3.5]);

test('the comment is optional and at most 1,000 characters', function () {
    $course = reviewCourse();
    $student = reviewEnroll($course);

    $this->actingAs($student)
        ->post(route('student.reviews.store', $course), ['rating' => 4, 'comment' => str_repeat('a', 1001)])
        ->assertSessionHasErrors('comment');

    $this->actingAs($student)
        ->post(route('student.reviews.store', $course), ['rating' => 4, 'comment' => str_repeat('a', 1000)])
        ->assertSessionHasNoErrors();

    $other = reviewEnroll($course);
    postReview($course, $other, 3, null)->assertSessionHasNoErrors();

    expect(CourseReview::count())->toBe(2);
});

test('comments are shown as escaped text, never as HTML', function () {
    $course = reviewCourse();
    $student = reviewEnroll($course);
    $author = reviewEnroll($course);

    postReview($course, $author, 4, '<script>alert("x")</script><b>bold</b>');

    $page = $this->actingAs($student)->get(route('student.course.show', $course));

    $page->assertOk()
        ->assertDontSee('<script>alert("x")</script>', false)
        ->assertSee('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;&lt;b&gt;bold&lt;/b&gt;', false);
});

test('review submissions are rate-limited', function () {
    $course = reviewCourse();
    $student = reviewEnroll($course);
    $review = makeReview($course, $student, 3);

    for ($i = 1; $i <= 5; $i++) {
        $this->actingAs($student)->put(route('student.reviews.update', $review), ['rating' => 4])->assertRedirect();
    }

    $this->actingAs($student)
        ->put(route('student.reviews.update', $review), ['rating' => 5])
        ->assertStatus(429);
});

/*
|--------------------------------------------------------------------------
| Averages, counts and lists
|--------------------------------------------------------------------------
*/

test('averages and counts leave out hidden reviews', function () {
    $course = reviewCourse();
    makeReview($course, reviewEnroll($course), 5);
    makeReview($course, reviewEnroll($course), 4);
    makeReview($course, reviewEnroll($course), 1, hidden: true);

    expect(CourseReviewController::summary($course))
        ->toBe(['average' => 4.5, 'count' => 2]);
});

test('the course page shows the average, the count and only visible reviews', function () {
    $course = reviewCourse();
    $viewer = reviewEnroll($course);
    makeReview($course, reviewEnroll($course), 5);
    makeReview($course, reviewEnroll($course), 4);
    makeReview($course, reviewEnroll($course), 1, hidden: true);

    $this->actingAs($viewer)
        ->get(route('student.course.show', $course))
        ->assertOk()
        ->assertSee('4.5')
        ->assertSee('2 reviews')
        ->assertSee('Review 5')
        ->assertSee('Review 4')
        ->assertDontSee('Review 1')
        ->assertSee('Write a Review');
});

test('the course page offers "Edit your review" once the student has reviewed', function () {
    $course = reviewCourse();
    $student = reviewEnroll($course);
    makeReview($course, $student, 4);

    $this->actingAs($student)
        ->get(route('student.course.show', $course))
        ->assertSee('Edit your review')
        ->assertSee('Delete my review')
        ->assertDontSee('Write a Review');
});

test('students who may not review see no form', function () {
    $course = reviewCourse();

    $this->actingAs(pathwiseUser('student'))
        ->get(route('student.course.show', $course))
        ->assertOk()
        ->assertSee('No reviews yet')
        ->assertDontSee('Write a Review');
});

test('the student still sees their own hidden review, marked and with the reason', function () {
    $course = reviewCourse();
    $student = reviewEnroll($course);
    makeReview($course, $student, 2, hidden: true);

    $this->actingAs($student)
        ->get(route('student.course.show', $course))
        ->assertSee('Hidden by an administrator')
        ->assertSee('Reason: Spam')
        ->assertDontSee('Edit your review')
        ->assertDontSee('Write a Review');

    $this->actingAs(reviewEnroll($course))
        ->get(route('student.course.show', $course))
        ->assertDontSee('Review 2')
        ->assertSee('No reviews yet');
});

test('reviews on the course page are paginated', function () {
    $course = reviewCourse();
    foreach ([5, 4, 3, 5, 4, 3] as $rating) {
        makeReview($course, reviewEnroll($course), $rating);
    }

    $this->actingAs(pathwiseUser('student'))
        ->get(route('student.course.show', $course))
        ->assertSee('6 reviews')
        ->assertSee('reviews_page=2', false);
});

test('marketplace cards show the average and count, or "No reviews yet"', function () {
    $reviewed = reviewCourse(['title' => 'Reviewed Course']);
    $empty = reviewCourse(['title' => 'Empty Course']);
    makeReview($reviewed, reviewEnroll($reviewed), 5);
    makeReview($reviewed, reviewEnroll($reviewed), 3);
    makeReview($reviewed, reviewEnroll($reviewed), 1, hidden: true);

    $page = $this->actingAs(pathwiseUser('student'))->get(route('student.marketplace'));

    $page->assertOk()
        ->assertSee('4.0')
        ->assertSee('(2 reviews)')
        ->assertSee('No reviews yet');
});

test('My Courses links to the review form', function () {
    $course = reviewCourse();
    $student = reviewEnroll($course);

    $this->actingAs($student)
        ->get(route('student.my-courses'))
        ->assertSee('Rate this course')
        ->assertSee(route('student.course.show', $course).'#reviews', false);

    makeReview($course, $student, 4);

    $this->actingAs($student)
        ->get(route('student.my-courses'))
        ->assertSee('Your review');
});

/*
|--------------------------------------------------------------------------
| Teacher
|--------------------------------------------------------------------------
*/

test('a teacher sees the visible reviews of their own courses only', function () {
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher, ['status' => 'published']);
    makeReview($course, reviewEnroll($course), 5);
    makeReview($course, reviewEnroll($course), 1, hidden: true);

    $this->actingAs($teacher)
        ->get(route('teacher.courses.reviews', $course))
        ->assertOk()
        ->assertSee('Review 5')
        ->assertDontSee('Review 1')
        ->assertSee('1 review');

    $this->actingAs(pathwiseApprovedTeacher())
        ->get(route('teacher.courses.reviews', $course))
        ->assertForbidden();
});

test('the teacher gets an in-app notification for a new review, not for edits', function () {
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher, ['status' => 'published']);
    $student = reviewEnroll($course);

    postReview($course, $student, 5);

    Notification::assertSentTo($teacher, PathwiseNotification::class, function ($notification, $channels) {
        return $notification->type === 'course_review'
            && $channels === ['database'];
    });

    $this->actingAs($student)->put(route('student.reviews.update', CourseReview::sole()), ['rating' => 4]);

    Notification::assertSentToTimes($teacher, PathwiseNotification::class, 1);
});

/*
|--------------------------------------------------------------------------
| Moderation
|--------------------------------------------------------------------------
*/

test('an administrator can hide a review with a reason and unhide it', function (string $role) {
    $department = Department::create(['name' => 'Computing']);
    $admin = pathwiseRoleBoundaryUser($role, ['department_id' => $role === 'admin' ? $department->id : null]);
    $course = reviewCourse(department: $department);
    $review = makeReview($course, reviewEnroll($course), 1);

    $this->actingAs($admin)
        ->post(route('admin.course-reviews.hide', $review), ['hidden_reason' => 'Offensive language'])
        ->assertSessionHas('success');

    $review->refresh();

    expect($review->is_hidden)->toBeTrue()
        ->and($review->hidden_reason)->toBe('Offensive language')
        ->and($review->hidden_by)->toBe($admin->id);

    $this->actingAs($admin)
        ->post(route('admin.course-reviews.unhide', $review))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $review->refresh();

    expect($review->is_hidden)->toBeFalse()
        ->and($review->hidden_reason)->toBeNull()
        ->and($review->hidden_by)->toBeNull();
})->with(['admin', 'super_admin']);

test('hiding a review requires a reason', function () {
    $department = Department::create(['name' => 'Computing']);
    $review = makeReview($course = reviewCourse(department: $department), reviewEnroll($course), 1);

    $this->actingAs(pathwiseUser('admin', ['department_id' => $department->id]))
        ->post(route('admin.course-reviews.hide', $review), ['hidden_reason' => ''])
        ->assertSessionHasErrors('hidden_reason');

    expect($review->fresh()->is_hidden)->toBeFalse();
});

test('other roles cannot hide or unhide reviews', function (string $role) {
    $course = reviewCourse();
    $visible = makeReview($course, reviewEnroll($course), 1);
    $hidden = makeReview($course, reviewEnroll($course), 2, hidden: true);

    $this->actingAs(pathwiseRoleBoundaryUser($role))
        ->post(route('admin.course-reviews.hide', $visible), ['hidden_reason' => 'Nope'])
        ->assertForbidden();

    $this->actingAs(pathwiseRoleBoundaryUser($role))
        ->post(route('admin.course-reviews.unhide', $hidden))
        ->assertForbidden();

    expect($visible->fresh()->is_hidden)->toBeFalse()
        ->and($hidden->fresh()->is_hidden)->toBeTrue();
})->with(['student', 'teacher', 'admin']);

test('the administrator list shows visible and hidden reviews with a filter', function () {
    $department = Department::create(['name' => 'Computing']);
    $course = reviewCourse(department: $department);
    makeReview($course, reviewEnroll($course), 5);
    makeReview($course, reviewEnroll($course), 1, hidden: true);
    $admin = pathwiseUser('admin', ['department_id' => $department->id]);

    $this->actingAs($admin)->get(route('admin.course-reviews.index'))
        ->assertOk()
        ->assertSee('Review 5')
        ->assertSee('Review 1')
        ->assertSee('Spam');

    $this->actingAs($admin)->get(route('admin.course-reviews.index', ['status' => 'hidden']))
        ->assertSee('Review 1')
        ->assertDontSee('Review 5');

    $this->actingAs($admin)->get(route('admin.course-reviews.index', ['status' => 'visible']))
        ->assertSee('Review 5')
        ->assertDontSee('Review 1');
});

test('reviews are deleted together with their course', function () {
    $course = reviewCourse();
    makeReview($course, reviewEnroll($course), 5);

    Enrollment::where('course_id', $course->id)->delete();
    $course->delete();

    expect(CourseReview::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Teacher: My Courses cards and the all-courses reviews page
|--------------------------------------------------------------------------
*/

test('teacher course cards show the visible average and count, linked to that course\'s reviews', function () {
    $teacher = pathwiseApprovedTeacher();
    $reviewed = pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Reviewed Course']);
    $empty = pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Empty Course']);
    makeReview($reviewed, reviewEnroll($reviewed), 5);
    makeReview($reviewed, reviewEnroll($reviewed), 4);
    makeReview($reviewed, reviewEnroll($reviewed), 1, hidden: true);

    $page = $this->actingAs($teacher)->get(route('teacher.my-courses'));

    $page->assertOk()
        ->assertSee('4.5')
        ->assertSee('(2 reviews)')
        ->assertDontSee('(3 reviews)')
        ->assertSee('No reviews yet')
        ->assertSee('href="'.route('teacher.courses.reviews', $reviewed).'"', false)
        ->assertSee('href="'.route('teacher.courses.reviews', $empty).'"', false);

    // The existing 3-dot menu item stays.
    $page->assertSee('View Reviews');
});

test('the teacher reviews page lists visible reviews from the teacher\'s own courses only', function () {
    $teacher = pathwiseApprovedTeacher();
    $first = pathwiseCourse($teacher, ['status' => 'published', 'title' => 'First Course']);
    $second = pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Second Course']);
    $otherCourse = reviewCourse(['title' => 'Someone Else Course']);

    $older = makeReview($first, reviewEnroll($first), 5);
    $older->forceFill(['created_at' => now()->subDay()])->save();
    makeReview($second, reviewEnroll($second), 4);
    makeReview($first, reviewEnroll($first), 1, hidden: true);
    makeReview($otherCourse, reviewEnroll($otherCourse), 3);

    $page = $this->actingAs($teacher)->get(route('teacher.course-reviews.index'));

    $page->assertOk()
        ->assertSee('Review 5')
        ->assertSee('Review 4')
        ->assertDontSee('Review 1')
        ->assertDontSee('Review 3')
        ->assertDontSee('Someone Else Course')
        ->assertSeeInOrder(['Review 4', 'Review 5']);
});

test('the course filter on the teacher reviews page works', function () {
    $teacher = pathwiseApprovedTeacher();
    $first = pathwiseCourse($teacher, ['status' => 'published']);
    $second = pathwiseCourse($teacher, ['status' => 'published']);
    makeReview($first, reviewEnroll($first), 5);
    makeReview($second, reviewEnroll($second), 4);

    $this->actingAs($teacher)
        ->get(route('teacher.course-reviews.index', ['course' => $first->id]))
        ->assertSee('Review 5')
        ->assertDontSee('Review 4');

    $this->actingAs($teacher)
        ->get(route('teacher.course-reviews.index', ['course' => $second->id]))
        ->assertSee('Review 4')
        ->assertDontSee('Review 5');
});

test('filtering by another teacher\'s course never shows their reviews', function () {
    $teacher = pathwiseApprovedTeacher();
    pathwiseCourse($teacher, ['status' => 'published']);
    $otherCourse = reviewCourse(['title' => 'Someone Else Course']);
    makeReview($otherCourse, reviewEnroll($otherCourse), 3);

    $this->actingAs($teacher)
        ->get(route('teacher.course-reviews.index', ['course' => $otherCourse->id]))
        ->assertOk()
        ->assertDontSee('Review 3')
        ->assertDontSee('Someone Else Course')
        ->assertSee('No reviews yet');
});

test('the teacher reviews page is paginated and only for teachers', function () {
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher, ['status' => 'published']);
    for ($i = 0; $i < 11; $i++) {
        makeReview($course, reviewEnroll($course), 4);
    }

    $this->actingAs($teacher)
        ->get(route('teacher.course-reviews.index'))
        ->assertSee('page=2', false);

    $this->actingAs(pathwiseUser('student'))
        ->get(route('teacher.course-reviews.index'))
        ->assertForbidden();
});

test('the teacher sidebar links to Course Reviews', function () {
    $this->actingAs(pathwiseApprovedTeacher())
        ->get(route('teacher.course-reviews.index'))
        ->assertSee('href="'.route('teacher.course-reviews.index').'"', false);
});
