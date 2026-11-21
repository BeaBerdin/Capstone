<?php

use App\Models\Certificate;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\Transaction;

/*
| The marketplace only offers courses the student does not already take or
| has finished. Cancelled enrollments stay listed with a message; started
| payments can be continued. The course page shows the right action for
| each enrollment state.
*/

function enrollStudent(App\Models\User $student, App\Models\Course $course, string $status, array $extra = []): Enrollment
{
    return Enrollment::create(array_merge([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => $status,
        'enrolled_at' => now()->subWeek(),
        'progress_percentage' => $status === 'completed' ? 100 : 40,
        'completed_at' => $status === 'completed' ? '2026-10-01 10:00:00' : null,
    ], $extra));
}

function marketplaceCourses(): array
{
    $teacher = pathwiseUser('teacher');

    return [
        'active' => pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Financial Accounting', 'price' => 499]),
        'completed' => pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Business Strategy and Leadership', 'price' => 499]),
        'cancelled' => pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Cancelled Marketing Course', 'price' => 499]),
        'pending' => pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Pending Payment Course', 'price' => 499]),
        'open' => pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Open Data Science Course', 'price' => 0]),
    ];
}

test('courses with an active or completed enrollment are not in the marketplace', function () {
    $student = pathwiseUser('student');
    $courses = marketplaceCourses();

    enrollStudent($student, $courses['active'], 'active');
    enrollStudent($student, $courses['completed'], 'completed');

    $this->actingAs($student)
        ->get(route('student.marketplace'))
        ->assertOk()
        ->assertDontSee('Financial Accounting')
        ->assertDontSee('Business Strategy and Leadership')
        ->assertSee('Cancelled Marketing Course')
        ->assertSee('Pending Payment Course')
        ->assertSee('Open Data Science Course')
        // Search works on data-search; hidden courses are not in it either.
        ->assertDontSee('financial accounting', false)
        ->assertViewHas('courses', fn ($list) => $list->count() === 3);
});

test('another student\'s enrollments do not hide courses', function () {
    $courses = marketplaceCourses();
    enrollStudent(pathwiseUser('student'), $courses['active'], 'active');

    $this->actingAs(pathwiseUser('student'))
        ->get(route('student.marketplace'))
        ->assertOk()
        ->assertSee('Financial Accounting')
        ->assertViewHas('courses', fn ($list) => $list->count() === 5);
});

test('a category whose only course the student owns disappears from the filter', function () {
    $student = pathwiseUser('student');
    $teacher = pathwiseUser('teacher');

    $accounting = CourseCategory::create(['name' => 'Accounting Only Category', 'is_active' => true]);

    $owned = pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Owned Accounting Course', 'category_id' => $accounting->id]);
    pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Other Course']);

    enrollStudent($student, $owned, 'active');

    $this->actingAs($student)
        ->get(route('student.marketplace'))
        ->assertOk()
        ->assertDontSee('Accounting Only Category')
        ->assertSee('Other Course');
});

test('a cancelled enrollment stays listed with a message', function () {
    $student = pathwiseUser('student');
    $courses = marketplaceCourses();
    enrollStudent($student, $courses['cancelled'], 'cancelled');

    $content = $this->actingAs($student)
        ->get(route('student.marketplace'))
        ->assertOk()
        ->assertSee('Cancelled Marketing Course')
        ->assertSee('Your enrollment was cancelled. Contact the course administrator.')
        ->getContent();

    expect(substr_count($content, 'data-enrollment-cancelled'))->toBe(1);
});

test('a pending payment can resume through the guarded checkout route', function () {
    $student = pathwiseUser('student');
    $courses = marketplaceCourses();

    Transaction::create([
        'student_id' => $student->id,
        'course_id' => $courses['pending']->id,
        'transaction_no' => 'TXN-PENDING-1',
        'amount' => 499,
        'payment_method' => 'PayMongo',
        'status' => 'pending',
        'checkout_url' => 'https://checkout.paymongo.com/cs_test_pending',
        'payment_reference' => 'cs_test_pending',
    ]);

    $this->actingAs($student)
        ->get(route('student.marketplace'))
        ->assertOk()
        ->assertSee('Pending Payment Course')
        ->assertSee('Payment pending')
        ->assertSee(route('student.transactions.store', $courses['pending']), false)
        ->assertSee('Continue payment')
        ->assertDontSee('https://checkout.paymongo.com/cs_test_pending', false);

    $this->post(route('student.transactions.store', $courses['pending']))
        ->assertRedirect('https://checkout.paymongo.com/cs_test_pending');

    expect(Transaction::count())->toBe(1);
});

test('an active enrollment opened by URL shows Continue Learning', function () {
    $student = pathwiseUser('student');
    $courses = marketplaceCourses();
    enrollStudent($student, $courses['active'], 'active');

    $this->actingAs($student)
        ->get(route('student.course.show', $courses['active']))
        ->assertOk()
        ->assertSee('Continue Learning')
        ->assertDontSee('Purchase Course')
        ->assertDontSee('Review Course');
});

test('a completed enrollment shows Completed, View Certificate and Review Course', function () {
    $student = pathwiseUser('student');
    $courses = marketplaceCourses();
    enrollStudent($student, $courses['completed'], 'completed');

    $certificate = Certificate::create([
        'student_id' => $student->id,
        'course_id' => $courses['completed']->id,
        'certificate_number' => 'PATH-2026-00077',
        'issued_date' => '2026-10-01',
        'status' => 'issued',
    ]);

    $this->actingAs($student)
        ->get(route('student.course.show', $courses['completed']))
        ->assertOk()
        ->assertSee('Completed')
        ->assertSee('Oct 01, 2026')
        ->assertSee('View Certificate')
        ->assertSee(route('student.certificate.view', $certificate), false)
        ->assertSee('Review Course')
        ->assertDontSee('Continue Learning')
        ->assertDontSee('Purchase Course');
});

test('a completed enrollment without a certificate shows Review Course only', function () {
    $student = pathwiseUser('student');
    $courses = marketplaceCourses();
    enrollStudent($student, $courses['completed'], 'completed');

    $this->actingAs($student)
        ->get(route('student.course.show', $courses['completed']))
        ->assertOk()
        ->assertSee('Review Course')
        ->assertDontSee('View Certificate')
        ->assertDontSee('Continue Learning');
});

test('a cancelled enrollment opened by URL shows the message and no buy or learn button', function () {
    $student = pathwiseUser('student');
    $courses = marketplaceCourses();
    enrollStudent($student, $courses['cancelled'], 'cancelled');

    $this->actingAs($student)
        ->get(route('student.course.show', $courses['cancelled']))
        ->assertOk()
        ->assertSee('Your enrollment was cancelled. Contact the course administrator.')
        ->assertDontSee('Purchase Course')
        ->assertDontSee('Continue Learning')
        ->assertDontSee('Review Course');
});

test('a course the student has not joined still shows the buy button', function () {
    $student = pathwiseUser('student');
    $courses = marketplaceCourses();

    $this->actingAs($student)
        ->get(route('student.course.show', $courses['pending']))
        ->assertOk()
        ->assertSee('Purchase Course')
        ->assertDontSee('Continue Learning');
});
