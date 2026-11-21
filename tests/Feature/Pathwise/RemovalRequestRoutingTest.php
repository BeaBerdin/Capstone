<?php

use App\Models\Department;
use App\Models\Enrollment;
use App\Models\StudentRemovalRequest;
use App\Models\User;

/*
| Removal requests follow the owning course's department. A Super Admin
| can review requests for courses with no assigned department or head.
*/

function routedRemovalRequest(?Department $teacherDepartment): array
{
    $teacher = pathwiseUser('teacher', ['department_id' => $teacherDepartment?->id]);
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 0]);

    $enrollment = Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    $request = StudentRemovalRequest::create([
        'instructor_id' => $teacher->id,
        'student_id' => $student->id,
        'course_id' => $course->id,
        'reason' => 'Routing test request '.uniqid(),
        'status' => 'pending',
    ]);

    return compact('teacher', 'student', 'course', 'enrollment', 'request');
}

function reviewRemovalRequest(User $administrator, StudentRemovalRequest $request)
{
    return test()->actingAs($administrator)
        ->post(route('admin.student-removal-requests.review', $request), [
            'decision' => 'rejected',
            'review_note' => 'Reviewed in a routing test.',
        ]);
}

test('a teacher without a department can still submit a removal request', function () {
    $teacher = pathwiseUser('teacher', ['department_id' => null]);
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 0]);

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    $this->actingAs($teacher)
        ->from(route('teacher.course.students', $course))
        ->post(route('teacher.student-removal-requests.store', $course), [
            'student_id' => $student->id,
            'reason' => 'The student asked to drop the course.',
        ])
        ->assertSessionHas('success', fn ($message) => str_contains($message, 'an authorized reviewer approves it'));

    expect(StudentRemovalRequest::where('instructor_id', $teacher->id)->where('status', 'pending')->count())->toBe(1);
});

test('a request for a course without a department can be reviewed by a super admin', function () {
    $computing = Department::create(['name' => 'College of Computing']);
    $adminWithDepartment = pathwiseUser('admin', ['department_id' => $computing->id]);
    $adminWithoutDepartment = pathwiseUser('admin', ['department_id' => null]);

    $setup = routedRemovalRequest(null);

    foreach ([$adminWithDepartment, $adminWithoutDepartment] as $administrator) {
        $this->actingAs($administrator)
            ->get(route('admin.student-removal-requests.index'))
            ->assertOk()
            ->assertDontSee($setup['request']->reason);
        reviewRemovalRequest($administrator, $setup['request'])->assertForbidden();
    }

    $edp = pathwiseUser('super_admin');
    $this->actingAs($edp)
        ->get(route('admin.student-removal-requests.index'))
        ->assertOk()
        ->assertSee($setup['request']->reason);
    reviewRemovalRequest($edp, $setup['request'])->assertSessionHas('success');

    expect($setup['request']->fresh()->status)->toBe('rejected');
});

test('an unassigned administrator cannot see or review departmental requests', function () {
    $computing = Department::create(['name' => 'College of Computing']);
    $nursing = Department::create(['name' => 'College of Nursing']);
    pathwiseUser('admin', ['department_id' => $computing->id]);
    pathwiseUser('admin', ['department_id' => $nursing->id]);
    $campusAdmin = pathwiseUser('admin', ['department_id' => null]);

    $fromComputing = routedRemovalRequest($computing);
    $fromNursing = routedRemovalRequest($nursing);

    $this->actingAs($campusAdmin)
        ->get(route('admin.student-removal-requests.index'))
        ->assertOk()
        ->assertDontSee('Requests from All Departments')
        ->assertDontSee($fromComputing['request']->reason)
        ->assertDontSee($fromNursing['request']->reason);

    reviewRemovalRequest($campusAdmin, $fromNursing['request'])->assertForbidden();
});

test('an administrator still cannot see or review another department that has its own administrator', function () {
    $computing = Department::create(['name' => 'College of Computing']);
    $nursing = Department::create(['name' => 'College of Nursing']);
    $computingHead = pathwiseUser('admin', ['department_id' => $computing->id]);
    pathwiseUser('admin', ['department_id' => $nursing->id]);

    $own = routedRemovalRequest($computing);
    $other = routedRemovalRequest($nursing);

    $this->actingAs($computingHead)
        ->get(route('admin.student-removal-requests.index'))
        ->assertOk()
        ->assertSee($own['request']->reason)
        ->assertDontSee($other['request']->reason);

    reviewRemovalRequest($computingHead, $other['request'])->assertForbidden();

    expect($other['request']->fresh()->status)->toBe('pending')
        ->and($other['enrollment']->fresh()->status)->toBe('active');
});

test('a request from a department without an administrator can be reviewed by a super admin', function () {
    $computing = Department::create(['name' => 'College of Computing']);
    $engineering = Department::create(['name' => 'College of Engineering']);
    $computingHead = pathwiseUser('admin', ['department_id' => $computing->id]);

    $setup = routedRemovalRequest($engineering);

    $this->actingAs($computingHead)
        ->get(route('admin.student-removal-requests.index'))
        ->assertOk()
        ->assertDontSee($setup['request']->reason);

    reviewRemovalRequest($computingHead, $setup['request'])->assertForbidden();
    reviewRemovalRequest(pathwiseUser('super_admin'), $setup['request'])->assertSessionHas('success');
});

test('teachers and students cannot open or review removal requests', function () {
    $setup = routedRemovalRequest(null);

    foreach ([$setup['teacher'], $setup['student']] as $user) {
        $this->actingAs($user)
            ->get(route('admin.student-removal-requests.index'))
            ->assertForbidden();

        reviewRemovalRequest($user, $setup['request'])->assertForbidden();
    }

    expect($setup['request']->fresh()->status)->toBe('pending');
});
