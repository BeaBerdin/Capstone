<?php

use App\Models\Enrollment;

/*
| Manuscript: Figure 23 "Enroll in Course" and Functional Requirement (m)
| "Payment Processing". Paid courses must never be enrolled for free.
*/

test('a student cannot enroll in a paid course through the free enroll route', function () {
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 499]);

    $this->actingAs($student)
        ->post(route('student.enroll', $course))
        ->assertRedirect(route('student.course.show', $course))
        ->assertSessionHas('error');

    expect(Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->exists())
        ->toBeFalse();
});

test('a student can enroll in a free published course', function () {
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 0]);

    $this->actingAs($student)
        ->post(route('student.enroll', $course))
        ->assertRedirect(route('student.dashboard'));

    expect(Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->exists())
        ->toBeTrue();
});

test('a student cannot enroll in a course that is not published', function () {
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'pending', 'price' => 0]);

    $this->actingAs($student)
        ->post(route('student.enroll', $course))
        ->assertNotFound();
});

test('a teacher cannot use the student enroll route', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 0]);

    $this->actingAs($teacher)
        ->post(route('student.enroll', $course))
        ->assertForbidden();
});

test('the PayMongo webhook is rejected when no signing secret is configured', function () {
    config(['services.paymongo.webhook_secret' => null]);

    $this->postJson(route('paymongo.webhook'), ['data' => []])
        ->assertStatus(503);
});

test('the PayMongo webhook is rejected without a signature', function () {
    config(['services.paymongo.webhook_secret' => 'whsk_test_secret']);

    $this->postJson(route('paymongo.webhook'), ['data' => []])
        ->assertStatus(401);
});

test('the PayMongo webhook is excluded from CSRF protection', function () {
    // PayMongo cannot send a CSRF token, so the URL must be in the
    // except list configured in bootstrap/app.php.
    $middleware = app(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

    expect($middleware->getExcludedPaths())->toContain('paymongo/webhook');
});
