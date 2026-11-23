<?php

use App\Http\Controllers\EnrollmentController;
use App\Models\Department;
use App\Models\Enrollment;
use Illuminate\Database\UniqueConstraintViolationException;

/*
| Manage Enrollments (Administrator): one enrollment per student and course.
| A duplicate is refused with a validation message instead of a 500 error.
*/

beforeEach(function () {
    $this->withoutVite();
    $this->department = Department::create(['name' => 'Enrollment Department']);
    $this->admin = pathwiseUser('admin', ['department_id' => $this->department->id]);
    $teacher = pathwiseUser('teacher', ['department_id' => $this->department->id]);
    $this->course = pathwiseCourse($teacher, ['status' => 'published']);
    $this->otherCourse = pathwiseCourse($teacher, ['status' => 'published']);
    $this->student = pathwiseUser('student');
});

function adminEnrollmentFields(array $overrides = []): array
{
    return array_merge(['status' => 'active', 'progress_percentage' => 0], $overrides);
}

test('the admin can enroll a student who is not enrolled yet', function () {
    $this->actingAs($this->admin)
        ->post(route('enrollments.store'), adminEnrollmentFields(['student_id' => $this->student->id, 'course_id' => $this->course->id]))
        ->assertRedirect(route('enrollments.index'))
        ->assertSessionHas('success', 'Enrollment created successfully.');

    expect(Enrollment::where('student_id', $this->student->id)->where('course_id', $this->course->id)->count())->toBe(1);
});

test('enrolling a student who is already enrolled shows a message instead of an error', function () {
    Enrollment::create(['student_id' => $this->student->id, 'course_id' => $this->course->id, 'status' => 'active', 'enrolled_at' => now(), 'progress_percentage' => 40]);

    $this->actingAs($this->admin)
        ->from(route('enrollments.create'))
        ->post(route('enrollments.store'), adminEnrollmentFields(['student_id' => $this->student->id, 'course_id' => $this->course->id]))
        ->assertRedirect(route('enrollments.create'))
        ->assertSessionHasErrors(['student_id' => EnrollmentController::DUPLICATE_MESSAGE]);

    expect(Enrollment::where('student_id', $this->student->id)->count())->toBe(1)
        ->and(Enrollment::sole()->progress_percentage)->toEqual(40);
});

test('the duplicate message is shown on the form', function () {
    Enrollment::create(['student_id' => $this->student->id, 'course_id' => $this->course->id, 'status' => 'active', 'enrolled_at' => now(), 'progress_percentage' => 0]);

    $this->actingAs($this->admin)
        ->from(route('enrollments.create'))
        ->followingRedirects()
        ->post(route('enrollments.store'), adminEnrollmentFields(['student_id' => $this->student->id, 'course_id' => $this->course->id]))
        ->assertOk()
        ->assertSee('This student is already enrolled in this course.');
});

test('a cancelled enrollment still counts as an existing enrollment', function () {
    Enrollment::create(['student_id' => $this->student->id, 'course_id' => $this->course->id, 'status' => 'cancelled', 'enrolled_at' => now(), 'progress_percentage' => 0]);

    $this->actingAs($this->admin)
        ->post(route('enrollments.store'), adminEnrollmentFields(['student_id' => $this->student->id, 'course_id' => $this->course->id]))
        ->assertSessionHasErrors(['student_id' => EnrollmentController::DUPLICATE_MESSAGE]);
});

test('the same student can be enrolled in a different course', function () {
    Enrollment::create(['student_id' => $this->student->id, 'course_id' => $this->course->id, 'status' => 'active', 'enrolled_at' => now(), 'progress_percentage' => 0]);

    $this->actingAs($this->admin)
        ->post(route('enrollments.store'), adminEnrollmentFields(['student_id' => $this->student->id, 'course_id' => $this->otherCourse->id]))
        ->assertSessionHasNoErrors();

    expect(Enrollment::where('student_id', $this->student->id)->count())->toBe(2);
});

test('editing an enrollment into an existing student and course pair shows the message', function () {
    Enrollment::create(['student_id' => $this->student->id, 'course_id' => $this->course->id, 'status' => 'active', 'enrolled_at' => now(), 'progress_percentage' => 0]);
    $other = Enrollment::create(['student_id' => $this->student->id, 'course_id' => $this->otherCourse->id, 'status' => 'active', 'enrolled_at' => now(), 'progress_percentage' => 0]);

    $this->actingAs($this->admin)
        ->put(route('enrollments.update', $other), adminEnrollmentFields(['student_id' => $this->student->id, 'course_id' => $this->course->id]))
        ->assertSessionHasErrors(['student_id' => EnrollmentController::DUPLICATE_MESSAGE]);

    expect($other->fresh()->course_id)->toBe($this->otherCourse->id);
});

test('saving an enrollment without changing its student and course is allowed', function () {
    $enrollment = Enrollment::create(['student_id' => $this->student->id, 'course_id' => $this->course->id, 'status' => 'active', 'enrolled_at' => now(), 'progress_percentage' => 0]);

    $this->actingAs($this->admin)
        ->put(route('enrollments.update', $enrollment), adminEnrollmentFields(['student_id' => $this->student->id, 'course_id' => $this->course->id, 'status' => 'completed', 'progress_percentage' => 100]))
        ->assertSessionHasNoErrors();

    expect($enrollment->fresh()->status)->toBe('completed');
});

test('a duplicate that slips past the check (two saves at once) is shown as the same message', function () {
    // Simulate the race: the database refuses the insert after validation passed.
    Enrollment::creating(function () {
        throw new UniqueConstraintViolationException('sqlite', 'insert into enrollments', [], new Exception('UNIQUE constraint failed'));
    });

    $this->actingAs($this->admin)
        ->post(route('enrollments.store'), adminEnrollmentFields(['student_id' => $this->student->id, 'course_id' => $this->course->id]))
        ->assertSessionHasErrors(['student_id' => EnrollmentController::DUPLICATE_MESSAGE]);
});
