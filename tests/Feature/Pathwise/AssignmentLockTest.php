<?php

use App\Models\Assignment;
use App\Models\Course;

/*
| Teachers can add, edit and delete assignments for Draft, Rejected and
| Published courses. Only a course waiting for admin review (pending) is
| locked. The assignments page and AssignmentController use one rule.
*/

function lockTestAssignment(string $courseStatus): array
{
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher, ['status' => $courseStatus, 'title' => 'Lock Test Course']);

    $assignment = Assignment::create([
        'course_id' => $course->id,
        'title' => 'Lock Test Assignment',
        'submission_type' => 'essay',
        'max_score' => 100,
        'is_published' => false,
    ]);

    return [$teacher, $course, $assignment];
}

function lockTestPayload(): array
{
    return [
        'title' => 'Renamed Assignment',
        'submission_type' => 'essay',
        'max_score' => 100,
    ];
}

test('one rule decides which course statuses allow assignment changes', function (string $status, bool $allowed) {
    expect((new Course(['status' => $status]))->allowsAssignmentChanges())->toBe($allowed);
})->with([
    'draft' => ['draft', true],
    'rejected' => ['rejected', true],
    'published' => ['published', true],
    'pending' => ['pending', false],
    'approved (unused status)' => ['approved', false],
]);

test('a teacher can manage assignments of a draft, rejected or published course', function (string $status) {
    [$teacher, $course, $assignment] = lockTestAssignment($status);

    $this->actingAs($teacher)
        ->get(route('teacher.assignments.index'))
        ->assertOk()
        ->assertSee(route('teacher.assignments.edit', $assignment), false)
        ->assertSee(route('teacher.assignments.create', $course), false)
        ->assertDontSee('Content Locked');

    $this->actingAs($teacher)
        ->get(route('teacher.assignments.edit', $assignment))
        ->assertOk();

    $this->actingAs($teacher)
        ->put(route('teacher.assignments.update', $assignment), lockTestPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('teacher.assignments.index'));

    expect($assignment->fresh()->title)->toBe('Renamed Assignment');

    $this->actingAs($teacher)
        ->get(route('teacher.assignments.create', $course))
        ->assertOk();

    $this->actingAs($teacher)
        ->delete(route('teacher.assignments.destroy', $assignment))
        ->assertRedirect();

    expect(Assignment::find($assignment->id))->toBeNull();
})->with(['draft', 'rejected', 'published']);

test('assignments of a course pending admin review are locked on the page and in the controller', function () {
    [$teacher, $course, $assignment] = lockTestAssignment('pending');

    $this->actingAs($teacher)
        ->get(route('teacher.assignments.index'))
        ->assertOk()
        ->assertSee('Content Locked')
        ->assertDontSee(route('teacher.assignments.edit', $assignment), false)
        ->assertDontSee(route('teacher.assignments.create', $course), false);

    $this->actingAs($teacher)
        ->get(route('teacher.assignments.edit', $assignment))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->put(route('teacher.assignments.update', $assignment), lockTestPayload())
        ->assertForbidden();

    $this->actingAs($teacher)
        ->get(route('teacher.assignments.create', $course))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->post(route('teacher.assignments.store', $course), lockTestPayload())
        ->assertForbidden();

    $this->actingAs($teacher)
        ->delete(route('teacher.assignments.destroy', $assignment))
        ->assertForbidden();

    expect($assignment->fresh()->title)->toBe('Lock Test Assignment');
});
