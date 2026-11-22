<?php

use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\StudentRemovalRequest;

/*
| Decision D8: an approved student removal request cancels the enrollment
| instead of deleting it, so the student's history is kept and the
| Department Head (admin) can restore it.
*/

function removalRequestSetup(string $enrollmentStatus = 'active'): array
{
    $department = Department::create(['name' => 'College of Computing']);

    $departmentHead = pathwiseUser('admin', ['department_id' => $department->id]);
    $teacher = pathwiseApprovedTeacher( ['department_id' => $department->id]);
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 0]);

    $lesson = Lesson::create([
        'course_id' => $course->id,
        'title' => 'Lesson 1',
        'content' => 'Content',
        'lesson_type' => 'text',
        'lesson_order' => 1,
        'is_preview' => false,
        'is_published' => true,
    ]);

    LessonProgress::create([
        'student_id' => $student->id,
        'lesson_id' => $lesson->id,
        'status' => 'completed',
        'completed_at' => now(),
    ]);

    $enrollment = Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => $enrollmentStatus,
        'enrolled_at' => now(),
        'progress_percentage' => 100,
    ]);

    $removalRequest = StudentRemovalRequest::create([
        'instructor_id' => $teacher->id,
        'student_id' => $student->id,
        'course_id' => $course->id,
        'reason' => 'Student asked to drop this course.',
        'status' => 'pending',
    ]);

    return compact('departmentHead', 'teacher', 'student', 'course', 'enrollment', 'removalRequest');
}

test('approving a removal request cancels the enrollment and keeps its history', function () {
    $setup = removalRequestSetup();

    $this->actingAs($setup['departmentHead'])
        ->post(route('admin.student-removal-requests.review', $setup['removalRequest']), [
            'decision' => 'approved',
            'review_note' => 'Approved as requested.',
        ])
        ->assertSessionHas('success');

    $enrollment = Enrollment::find($setup['enrollment']->id);

    expect($enrollment)->not->toBeNull()
        ->and($enrollment->status)->toBe('cancelled')
        ->and((float) $enrollment->progress_percentage)->toBe(100.0)
        ->and(LessonProgress::where('student_id', $setup['student']->id)->count())->toBe(1)
        ->and($setup['removalRequest']->fresh()->status)->toBe('approved')
        ->and($setup['student']->fresh())->not->toBeNull();

    // The student no longer has access to the course.
    $this->actingAs($setup['student'])
        ->get(route('student.learn.course', $setup['course']))
        ->assertForbidden();
});

test('rejecting a removal request leaves the enrollment active', function () {
    $setup = removalRequestSetup();

    $this->actingAs($setup['departmentHead'])
        ->post(route('admin.student-removal-requests.review', $setup['removalRequest']), [
            'decision' => 'rejected',
        ])
        ->assertSessionHas('success');

    expect($setup['enrollment']->fresh()->status)->toBe('active')
        ->and($setup['removalRequest']->fresh()->status)->toBe('rejected');
});

test('a removal request for an enrollment that is already cancelled cannot be approved', function () {
    $setup = removalRequestSetup('cancelled');

    $this->actingAs($setup['departmentHead'])
        ->post(route('admin.student-removal-requests.review', $setup['removalRequest']), [
            'decision' => 'approved',
        ])
        ->assertStatus(409);

    expect($setup['removalRequest']->fresh()->status)->toBe('pending');
});

test('a teacher cannot request removal of a student whose enrollment is already cancelled', function () {
    $setup = removalRequestSetup('cancelled');
    $setup['removalRequest']->delete();

    $this->actingAs($setup['teacher'])
        ->post(route('teacher.student-removal-requests.store', $setup['course']), [
            'student_id' => $setup['student']->id,
            'reason' => 'Student asked to drop this course.',
        ])
        ->assertSessionHasErrors('student_id');

    expect(StudentRemovalRequest::count())->toBe(0);
});

test('the administrator can restore a cancelled enrollment', function () {
    $setup = removalRequestSetup('cancelled');

    $this->actingAs($setup['departmentHead'])
        ->put(route('enrollments.update', $setup['enrollment']), [
            'student_id' => $setup['student']->id,
            'course_id' => $setup['course']->id,
            'status' => 'active',
            'progress_percentage' => 100,
        ])
        ->assertRedirect(route('enrollments.index'));

    expect($setup['enrollment']->fresh()->status)->toBe('active');

    $this->actingAs($setup['student'])
        ->get(route('student.learn.course', $setup['course']))
        ->assertOk();
});
