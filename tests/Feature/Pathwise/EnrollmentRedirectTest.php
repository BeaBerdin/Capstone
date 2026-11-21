<?php

use App\Models\CourseInvitation;
use App\Models\Enrollment;
use App\Models\Transaction;

/*
| After enrolling, the student lands on the course's learning page, not the
| dashboard, My Courses or the Marketplace. Payment, publication and
| cancelled-enrollment rules are unchanged, and a repeated submit never
| creates a second enrollment or an error page.
*/

test('a free enrollment goes straight to the course learning page, which opens', function () {
    $student = pathwiseUser('student');
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'price' => 0]);

    $this->actingAs($student)
        ->post(route('student.enroll', $course))
        ->assertRedirect(route('student.learn.course', $course))
        ->assertSessionHas('success', 'Successfully enrolled in course.');

    $this->actingAs($student)->get(route('student.learn.course', $course))->assertOk()->assertSee($course->title);
});

test('submitting enroll twice keeps one enrollment and still lands on the course', function () {
    $student = pathwiseUser('student');
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'price' => 0]);

    $this->actingAs($student)->post(route('student.enroll', $course));

    $this->actingAs($student)
        ->post(route('student.enroll', $course))
        ->assertRedirect(route('student.learn.course', $course))
        ->assertSessionHas('success', 'You are already enrolled in this course.');

    expect(Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->count())->toBe(1)
        ->and($student->notifications()->count())->toBe(1);
});

test('the enroll button is disabled once the form is submitted', function () {
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'price' => 0]);

    $this->actingAs(pathwiseUser('student'))
        ->get(route('student.course.show', $course))
        ->assertOk()
        ->assertSee('data-enroll-button', false)
        ->assertSee('button.disabled = true;', false);
});

test('paid, unpublished and cancelled rules still apply before any redirect to the course', function () {
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');

    $paid = pathwiseCourse($teacher, ['status' => 'published', 'price' => 499]);
    $this->actingAs($student)->post(route('student.enroll', $paid))
        ->assertRedirect(route('student.course.show', $paid));

    $pending = pathwiseCourse($teacher, ['status' => 'pending', 'price' => 0]);
    $this->actingAs($student)->post(route('student.enroll', $pending))->assertNotFound();

    $cancelled = pathwiseCourse($teacher, ['status' => 'published', 'price' => 0]);
    Enrollment::create(['student_id' => $student->id, 'course_id' => $cancelled->id, 'status' => 'cancelled', 'enrolled_at' => now(), 'progress_percentage' => 0]);
    $this->actingAs($student)->post(route('student.enroll', $cancelled))
        ->assertRedirect(route('student.course.show', $cancelled));

    expect(Enrollment::where('student_id', $student->id)->where('status', 'active')->count())->toBe(0);
});

test('accepting a free invitation goes straight to the course, also when already enrolled', function () {
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 0]);

    $invitation = CourseInvitation::create(['course_id' => $course->id, 'created_by' => $teacher->id, 'code' => 'REDIRECT1234']);

    $this->actingAs($student)
        ->get(route('course-invitations.show', $invitation->code))
        ->assertSee('data-accept-button', false);

    $this->actingAs($student)
        ->post(route('course-invitations.accept', $invitation->code))
        ->assertRedirect(route('student.learn.course', $course));

    // A second invitation to a course the student already has.
    $second = CourseInvitation::create(['course_id' => $course->id, 'created_by' => $teacher->id, 'code' => 'REDIRECT5678']);

    $this->actingAs($student)
        ->post(route('course-invitations.accept', $second->code))
        ->assertRedirect(route('student.learn.course', $course))
        ->assertSessionHas('success', 'You are already enrolled in this course.');

    expect(Enrollment::where('student_id', $student->id)->count())->toBe(1);
});

test('a verified payment offers Start Learning into the course instead of the Marketplace', function () {
    $student = pathwiseUser('student');
    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'price' => 499]);

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    $transaction = Transaction::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'transaction_no' => 'TXN-REDIRECT-1',
        'amount' => 499,
        'payment_method' => 'PayMongo',
        'status' => 'approved',
    ]);

    $this->actingAs($student)
        ->get(route('student.transactions.success', $transaction))
        ->assertOk()
        ->assertSee('Start Learning')
        ->assertSee('href="'.route('student.learn.course', $course->id).'"', false)
        ->assertDontSee('Back to Marketplace');

    expect(Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->value('status'))->toBe('active');
});
