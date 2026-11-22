<?php

use App\Models\Department;
use App\Models\Lesson;

/*
| Course Management: each course card's cover has a Preview that opens that
| course's own details in a dialog the admin can close again.
*/

test('each course card has a preview with that course\'s own information', function () {
    $department = Department::create(['name' => 'Computing']);
    $python = pathwiseCourse(pathwiseApprovedTeacher( ['name' => 'Ana Teacher', 'department_id' => $department->id]), [
        'title' => 'Python Preview Course',
        'description' => 'Learn Python from scratch.',
        'status' => 'pending',
        'price' => 499,
        'estimated_hours' => 7,
    ]);

    $web = pathwiseCourse(pathwiseApprovedTeacher( ['name' => 'Ben Teacher', 'department_id' => $department->id]), [
        'title' => 'Web Preview Course',
        'description' => 'Build web pages.',
        'status' => 'published',
        'price' => 0,
    ]);

    foreach (['Variables', 'Loops'] as $order => $title) {
        Lesson::create(['course_id' => $python->id, 'title' => 'Python: '.$title, 'content' => 'x', 'lesson_type' => 'text', 'lesson_order' => $order + 1, 'is_published' => true]);
    }

    Lesson::create(['course_id' => $web->id, 'title' => 'Web: HTML basics', 'content' => 'x', 'lesson_type' => 'text', 'lesson_order' => 1, 'is_published' => true]);

    $html = $this->actingAs(pathwiseUser('admin', ['department_id' => $department->id]))
        ->get(route('courses.index'))
        ->assertOk()
        ->getContent();

    // A clickable Preview on each card, opening that card's dialog.
    expect(substr_count($html, 'data-test="course-preview-button"'))->toBe(2)
        ->and($html)->toContain("getElementById('course-preview-{$python->id}').showModal()")
        ->and($html)->toContain("getElementById('course-preview-{$web->id}').showModal()");

    $dialog = function (int $id) use ($html): string {
        $start = strpos($html, 'id="course-preview-'.$id.'"');

        return substr($html, $start, strpos($html, '</dialog>', $start) - $start);
    };

    $pythonDialog = $dialog($python->id);
    $webDialog = $dialog($web->id);

    expect($pythonDialog)
        ->toContain('Python Preview Course')
        ->toContain('Ana Teacher')
        ->toContain('Learn Python from scratch.')
        ->toContain('₱499.00')
        ->toContain('7 hrs')
        ->toContain('Python: Variables')
        ->toContain('Python: Loops')
        ->toContain('Pending')
        ->not->toContain('Web: HTML basics');

    expect($webDialog)
        ->toContain('Web Preview Course')
        ->toContain('Ben Teacher')
        ->toContain('Free')
        ->toContain('Web: HTML basics')
        ->not->toContain('Python: Variables');

    // It can be closed: close buttons, plus Esc / outside click on a native dialog.
    expect($pythonDialog)
        ->toContain("this.closest('dialog').close()")
        ->toContain('if (event.target === this) this.close()')
        ->toContain('data-test="course-preview-close"');

    // Approve and Return for Revision are still on the pending card.
    expect($html)->toContain(route('courses.approve', $python))
        ->toContain(route('courses.reject', $python));
});

test('only administrators can open course management and its previews', function () {
    pathwiseCourse(pathwiseApprovedTeacher(), ['status' => 'pending']);

    $this->actingAs(pathwiseUser('student'))->get(route('courses.index'))->assertForbidden();
    $this->actingAs(pathwiseApprovedTeacher())->get(route('courses.index'))->assertForbidden();
});
