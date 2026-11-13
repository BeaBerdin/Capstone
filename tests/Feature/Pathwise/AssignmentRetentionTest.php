<?php

use App\Exceptions\DeletionBlockedException;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    fakeFileDisks();
    Notification::fake();
});

function retentionAssignment(string $courseStatus = 'published'): array
{
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => $courseStatus]);
    $assignment = Assignment::create([
        'course_id' => $course->id,
        'title' => 'Retained assignment',
        'description' => 'Student work must survive attempted deletion.',
        'submission_type' => 'essay',
        'max_score' => 100,
        'is_published' => true,
    ]);

    return [$teacher, $course, $assignment];
}

test('assignment deletion preserves every submission state for admins and teachers', function (string $actorRole, string $status) {
    [$teacher, , $assignment] = retentionAssignment();
    $student = pathwiseUser('student');
    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
        'answer_text' => "  Original answer\nwith indentation.\n",
        'file_path' => 'assignment-submissions/retained.txt',
        'score' => $status === 'graded' ? 87 : null,
        'feedback' => 'Teacher feedback must remain.',
        'status' => $status,
        'submitted_at' => now(),
        'ai_score' => 90,
        'ai_feedback' => 'AI feedback must remain.',
        'ai_strengths' => 'Clear explanation',
        'ai_improvements' => 'Add examples',
        'ai_evaluation_status' => 'completed',
        'ai_evaluated_at' => now(),
    ]);
    Storage::disk('local')->put($submission->file_path, 'private uploaded answer');
    Storage::disk('public')->put($submission->file_path, 'legacy uploaded answer');
    $before = $submission->fresh()->getRawOriginal();
    $actor = $actorRole === 'teacher' ? $teacher : pathwiseUser('admin');
    $index = $actorRole === 'teacher' ? 'teacher.assignments.index' : 'assignments.index';
    $destroy = $actorRole === 'teacher' ? 'teacher.assignments.destroy' : 'assignments.destroy';

    $this->actingAs($actor)->delete(route($destroy, $assignment))
        ->assertRedirect(route($index))
        ->assertSessionHas('error', fn ($message) => str_contains($message, 'student submissions exist'));

    expect($assignment->fresh())->not->toBeNull()
        ->and($submission->fresh()->getRawOriginal())->toBe($before)
        ->and(Storage::disk('local')->get($submission->file_path))->toBe('private uploaded answer')
        ->and(Storage::disk('public')->get($submission->file_path))->toBe('legacy uploaded answer');

    $this->get(route($index))->assertOk()->assertSee('student submissions exist');
})->with(['admin', 'teacher'])->with(['submitted', 'returned', 'graded']);

test('direct Eloquent assignment deletion refuses history even with a stale loaded relation', function (string $status) {
    [, , $assignment] = retentionAssignment();
    $assignment->load('submissions');
    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'student_id' => pathwiseUser('student')->id,
        'answer_text' => 'Keep this answer',
        'status' => $status,
        'score' => $status === 'graded' ? 80 : null,
    ]);

    expect(fn () => $assignment->delete())->toThrow(DeletionBlockedException::class)
        ->and($assignment->fresh())->not->toBeNull()
        ->and($submission->fresh()->answer_text)->toBe('Keep this answer');
})->with(['submitted', 'returned', 'graded']);

test('an unused assignment remains deletable through both existing role routes', function (string $actorRole) {
    [$teacher, , $assignment] = retentionAssignment();
    $actor = $actorRole === 'teacher' ? $teacher : pathwiseUser('admin');
    $destroy = $actorRole === 'teacher' ? 'teacher.assignments.destroy' : 'assignments.destroy';

    $this->actingAs($actor)->delete(route($destroy, $assignment))
        ->assertRedirect()->assertSessionHas('success')->assertSessionMissing('error');

    $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
})->with(['admin', 'teacher']);

test('direct Eloquent deletion still allows an assignment with no submissions', function () {
    [, , $assignment] = retentionAssignment();

    expect($assignment->delete())->toBeTrue();
    $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
});

test('assignment deletion cannot cross teacher ownership or role boundaries', function (string $actorRole, string $routeName) {
    [, , $assignment] = retentionAssignment();

    $this->actingAs(pathwiseUser($actorRole))->delete(route($routeName, $assignment))->assertForbidden();

    $this->assertDatabaseHas('assignments', ['id' => $assignment->id]);
})->with([
    ['teacher', 'teacher.assignments.destroy'],
    ['student', 'teacher.assignments.destroy'],
    ['student', 'assignments.destroy'],
    ['teacher', 'assignments.destroy'],
    ['super_admin', 'assignments.destroy'],
]);

test('the existing pending-course assignment lock is preserved', function () {
    [$teacher, , $assignment] = retentionAssignment('pending');

    $this->actingAs($teacher)->delete(route('teacher.assignments.destroy', $assignment))->assertForbidden();
    $this->assertDatabaseHas('assignments', ['id' => $assignment->id]);
});

test('a model deletion guard rolls back a preceding change in the same transaction', function () {
    [, , $assignment] = retentionAssignment();
    Submission::create([
        'assignment_id' => $assignment->id,
        'student_id' => pathwiseUser('student')->id,
        'status' => 'submitted',
        'answer_text' => 'Original answer',
    ]);

    expect(fn () => DB::transaction(function () use ($assignment) {
        $assignment->update(['title' => 'Should roll back']);
        $assignment->delete();
    }))->toThrow(DeletionBlockedException::class);

    expect($assignment->fresh()->title)->toBe('Retained assignment');
});
