<?php

use App\Models\CertificateSetting;
use App\Models\CourseCategory;
use App\Models\Department;

beforeEach(function () {
    $this->withoutVite();
    fakeFileDisks();
});

test('global settings reject direct requests from every non Super Admin and preserve existing data', function (string $actor) {
    $department = Department::create(['name' => 'Computing']);
    $user = $actor === 'assigned admin'
        ? pathwiseUser('admin', ['department_id' => $department->id])
        : pathwiseRoleBoundaryUser($actor === 'unassigned admin' ? 'admin' : $actor);
    $category = CourseCategory::create(['name' => 'Keep Category']);
    $template = CertificateSetting::create(CertificateSetting::DEFAULTS);
    $before = $template->fresh()->getAttributes();
    $this->actingAs($user);
    foreach (['course-categories.index', 'course-categories.create', 'certificate-template.edit', 'certificate-template.preview'] as $route) {
        $this->get(route($route))->assertForbidden();
    }
    $this->get(route('course-categories.edit', $category))->assertForbidden();
    $this->post(route('course-categories.store'), ['name' => 'Unauthorized Category'])->assertForbidden();
    $this->put(route('course-categories.update', $category), ['name' => 'Changed'])->assertForbidden();
    $this->delete(route('course-categories.destroy', $category))->assertForbidden();
    $this->put(route('certificate-template.update'), [
        'title' => 'Changed', 'body_text' => 'Changed',
        'signatory_name' => 'Changed', 'signatory_position' => 'Changed',
    ])->assertForbidden();
    expect($category->fresh()->name)->toBe('Keep Category')
        ->and(CourseCategory::count())->toBe(1)
        ->and($template->fresh()->getAttributes())->toBe($before);
})->with(['assigned admin', 'unassigned admin', 'teacher', 'student']);

test('global settings links belong to Super Admin navigation and not Department Admin dashboards', function () {
    $department = Department::create(['name' => 'Computing']);
    $this->actingAs(pathwiseUser('admin', ['department_id' => $department->id]))
        ->get(route('admin.dashboard'))->assertOk()
        ->assertDontSee(route('course-categories.index'), false)
        ->assertDontSee(route('certificate-template.edit'), false);
    $this->actingAs(pathwiseUser('super_admin'))->get(route('super_admin.dashboard'))->assertOk()
        ->assertSee(route('course-categories.index'), false)
        ->assertSee(route('certificate-template.edit'), false);
    $this->get(route('course-categories.index'))->assertOk();
    $this->get(route('course-categories.create'))->assertOk();
});
