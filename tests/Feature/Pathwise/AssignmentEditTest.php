<?php

use App\Models\Assignment;

/*
| Teachers must be able to edit an assignment with the same fields as the
| create form: submission type, programming language and AI settings.
*/

function editableAssignment(array $attributes = []): array
{
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'draft']);

    $assignment = Assignment::create(array_merge([
        'course_id' => $course->id,
        'title' => 'Original Title',
        'submission_type' => 'code',
        'programming_language' => 'c',
        'ai_evaluation_enabled' => true,
        'evaluation_criteria' => 'Correct output and clean code',
        'max_score' => 100,
        'is_published' => false,
    ], $attributes));

    return [$teacher, $assignment];
}

function editPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Original Title',
        'submission_type' => 'code',
        'programming_language' => 'c',
        'ai_evaluation_enabled' => 1,
        'evaluation_criteria' => 'Correct output and clean code',
        'max_score' => 100,
    ], $overrides);
}

test('the edit form shows the current submission type, language and AI settings', function () {
    [$teacher, $assignment] = editableAssignment(['programming_language' => 'java']);

    $content = $this->actingAs($teacher)
        ->get(route('teacher.assignments.edit', $assignment))
        ->assertOk()
        ->assertSee('name="submission_type"', false)
        ->assertSee('name="programming_language"', false)
        ->assertSee('name="ai_evaluation_enabled"', false)
        ->assertSee('Correct output and clean code')
        ->getContent();

    expect($content)->toMatch('/value="code"\s+selected/')
        ->toMatch('/value="java"\s+selected/')
        ->toMatch('/name="ai_evaluation_enabled"\s+value="1"\s+checked/');
});

test('a teacher can save changes to an assignment', function () {
    [$teacher, $assignment] = editableAssignment();

    $this->actingAs($teacher)
        ->put(route('teacher.assignments.update', $assignment), editPayload([
            'title' => 'Updated Title',
            'max_score' => 50,
        ]))
        ->assertRedirect(route('teacher.assignments.index'))
        ->assertSessionHasNoErrors();

    $assignment->refresh();

    expect($assignment->title)->toBe('Updated Title')
        ->and($assignment->max_score)->toBe(50)
        ->and($assignment->programming_language)->toBe('c');
});

test('a teacher can change the programming language of a code assignment', function () {
    [$teacher, $assignment] = editableAssignment(['programming_language' => 'c']);

    $this->actingAs($teacher)
        ->put(route('teacher.assignments.update', $assignment), editPayload([
            'programming_language' => 'php',
        ]))
        ->assertSessionHasNoErrors();

    expect($assignment->fresh()->programming_language)->toBe('php');
});

test('a teacher can change an essay into a code assignment', function () {
    [$teacher, $assignment] = editableAssignment([
        'submission_type' => 'essay',
        'programming_language' => null,
    ]);

    $this->actingAs($teacher)
        ->put(route('teacher.assignments.update', $assignment), editPayload([
            'submission_type' => 'code',
            'programming_language' => 'java',
        ]))
        ->assertSessionHasNoErrors();

    $assignment->refresh();

    expect($assignment->submission_type)->toBe('code')
        ->and($assignment->programming_language)->toBe('java');
});

test('unticking AI evaluation switches it off', function () {
    [$teacher, $assignment] = editableAssignment(['ai_evaluation_enabled' => true]);

    $payload = editPayload();
    unset($payload['ai_evaluation_enabled']);

    $this->actingAs($teacher)
        ->put(route('teacher.assignments.update', $assignment), $payload)
        ->assertSessionHasNoErrors();

    expect($assignment->fresh()->ai_evaluation_enabled)->toBeFalse();
});

test('an unknown language is rejected when editing', function () {
    [$teacher, $assignment] = editableAssignment();

    $this->actingAs($teacher)
        ->put(route('teacher.assignments.update', $assignment), editPayload([
            'programming_language' => 'ruby',
        ]))
        ->assertSessionHasErrors('programming_language');

    expect($assignment->fresh()->programming_language)->toBe('c');
});

test('the create form still shows the same fields', function () {
    $teacher = pathwiseUser('teacher');
    $course = pathwiseCourse($teacher, ['status' => 'draft']);

    $this->actingAs($teacher)
        ->get(route('teacher.assignments.create', $course))
        ->assertOk()
        ->assertSee('name="submission_type"', false)
        ->assertSee('name="programming_language"', false)
        ->assertSee('name="ai_evaluation_enabled"', false);
});
