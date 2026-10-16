<?php

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;

/*
| An enrollment marked "cancelled" by the Administrator must no longer give
| access to the course (Manuscript: role-based access; Fig. 23 Enroll).
*/

function cancelledEnrollmentSetup(): array
{
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 0]);

    Lesson::create([
        'course_id' => $course->id,
        'title' => 'Lesson 1',
        'content' => 'Content',
        'lesson_type' => 'text',
        'lesson_order' => 1,
        'is_preview' => false,
        'is_published' => true,
    ]);

    $enrollment = Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'cancelled',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    return [$student, $course, $enrollment];
}

test('a student with a cancelled enrollment cannot open the course lessons', function () {
    [$student, $course] = cancelledEnrollmentSetup();

    $this->actingAs($student)
        ->get(route('student.learn.course', $course))
        ->assertForbidden();
});

test('a student with a cancelled enrollment cannot open a quiz', function () {
    [$student, $course] = cancelledEnrollmentSetup();

    $quiz = Quiz::create([
        'course_id' => $course->id,
        'title' => 'Quiz',
        'passing_score' => 50,
        'is_published' => true,
    ]);

    $this->actingAs($student)
        ->get(route('student.quiz.take', $quiz))
        ->assertForbidden();
});

test('a student with a cancelled enrollment cannot silently re-enroll', function () {
    [$student, $course, $enrollment] = cancelledEnrollmentSetup();

    $this->actingAs($student)
        ->post(route('student.enroll', $course))
        ->assertRedirect(route('student.course.show', $course))
        ->assertSessionHas('error');

    expect($enrollment->fresh()->status)->toBe('cancelled');
});

test('an active enrollment still gives access', function () {
    [$student, $course, $enrollment] = cancelledEnrollmentSetup();

    $enrollment->update(['status' => 'active']);

    $this->actingAs($student)
        ->get(route('student.learn.course', $course))
        ->assertOk();
});

test('the enrollment model reports which statuses give access', function () {
    expect((new Enrollment(['status' => 'active']))->grantsAccess())->toBeTrue()
        ->and((new Enrollment(['status' => 'completed']))->grantsAccess())->toBeTrue()
        ->and((new Enrollment(['status' => 'cancelled']))->grantsAccess())->toBeFalse();
});
