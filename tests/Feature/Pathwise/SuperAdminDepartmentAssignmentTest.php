<?php

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
    fakeFileDisks();
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

test('Super Admin creates a Department Admin with a persisted department relationship', function () {
    $department = Department::create(['name' => 'Computing']);
    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('users.create'))->assertOk()->assertSee('name="department_id"', false);
    $this->post(route('users.store'), [
        'name' => 'Computing Head', 'email' => 'head@example.test',
        'password' => 'SafePassword123!', 'password_confirmation' => 'SafePassword123!',
        'role' => 'admin', 'department_id' => $department->id,
    ])->assertSessionHasNoErrors()->assertRedirect(route('users.index'));

    $admin = User::where('email', 'head@example.test')->sole();
    expect($admin->hasRole('admin'))->toBeTrue()
        ->and((int) $admin->department_id)->toBe($department->id)
        ->and($admin->department->id)->toBe($department->id);
    $this->assertDatabaseHas('users', ['id' => $admin->id, 'department_id' => $department->id]);
});

test('reassignment persists and replaces old department course access on the next request', function () {
    $old = Department::create(['name' => 'Computing']);
    $new = Department::create(['name' => 'Health']);
    $admin = pathwiseUser('admin', ['department_id' => $old->id]);
    $edp = pathwiseUser('super_admin');
    $oldCourse = pathwiseCourse(pathwiseUser('teacher', ['department_id' => $old->id]), ['title' => 'Old department course']);
    $newCourse = pathwiseCourse(pathwiseUser('teacher', ['department_id' => $new->id]), ['title' => 'New department course']);
    $this->actingAs($admin)->get(route('courses.edit', $oldCourse))->assertOk();
    $this->get(route('courses.edit', $newCourse))->assertForbidden();

    $this->actingAs($edp)->get(route('departments.assign'))->assertOk()->assertSee($admin->email);
    $this->post(route('departments.assign.store'), [
        'user_id' => $admin->id, 'department_id' => $new->id,
    ])->assertSessionHasNoErrors()->assertRedirect(route('departments.assign'));

    $reloaded = User::findOrFail($admin->id);
    expect((int) DB::table('users')->where('id', $admin->id)->value('department_id'))->toBe($new->id)
        ->and($reloaded->department->id)->toBe($new->id);
    $this->actingAs($reloaded)->get(route('courses.index'))->assertOk()
        ->assertSee($newCourse->title)->assertDontSee($oldCourse->title);
    $this->get(route('courses.edit', $newCourse))->assertOk();
    $this->get(route('courses.edit', $oldCourse))->assertForbidden();
    $this->post(route('courses.approve', $oldCourse))->assertForbidden();
    expect($oldCourse->fresh()->status)->toBe('draft');

    $this->actingAs($edp->fresh())->get(route('courses.index'))->assertOk()
        ->assertSee($oldCourse->title)->assertSee($newCourse->title);
    $this->get(route('courses.edit', $oldCourse))->assertOk();
    $this->get(route('courses.edit', $newCourse))->assertOk();
});

test('unassigned Department Admin cannot access any department course or create courses', function () {
    $department = Department::create(['name' => 'Computing']);
    $course = pathwiseCourse(pathwiseUser('teacher', ['department_id' => $department->id]));
    $unassigned = pathwiseCourse(pathwiseUser('teacher'));
    $this->actingAs(pathwiseUser('admin'))->get(route('courses.index'))->assertOk()
        ->assertDontSee($course->title)->assertDontSee($unassigned->title);
    foreach ([$course, $unassigned] as $record) {
        $this->get(route('courses.edit', $record))->assertForbidden();
        $this->post(route('courses.approve', $record))->assertForbidden();
    }
    $this->get(route('courses.create'))->assertForbidden();
    $this->actingAs(pathwiseUser('super_admin'))->get(route('courses.index'))->assertOk()
        ->assertSee($course->title)->assertSee($unassigned->title);
});

test('invalid department IDs reject assignment and account creation without changing records', function () {
    $department = Department::create(['name' => 'Computing']);
    $admin = pathwiseUser('admin', ['department_id' => $department->id]);
    $this->actingAs(pathwiseUser('super_admin'))
        ->post(route('departments.assign.store'), ['user_id' => $admin->id, 'department_id' => 999999])
        ->assertSessionHasErrors('department_id');
    expect((int) $admin->fresh()->department_id)->toBe($department->id);
    $this->post(route('users.store'), [
        'name' => 'Invalid Head', 'email' => 'invalid@example.test',
        'password' => 'SafePassword123!', 'password_confirmation' => 'SafePassword123!',
        'role' => 'admin', 'department_id' => 999999,
    ])->assertSessionHasErrors('department_id');
    $this->assertDatabaseMissing('users', ['email' => 'invalid@example.test']);
});

test('non Super Admin actors cannot assign departments or create Department Admin accounts', function (string $role) {
    $old = Department::create(['name' => 'Computing']);
    $new = Department::create(['name' => 'Health']);
    $target = pathwiseUser('admin', ['department_id' => $old->id]);
    $this->actingAs(pathwiseRoleBoundaryUser($role))->get(route('departments.assign'))->assertForbidden();
    $this->post(route('departments.assign.store'), ['user_id' => $target->id, 'department_id' => $new->id])->assertForbidden();
    $this->post(route('users.store'), [
        'name' => 'Unauthorized Head', 'email' => 'unauthorized@example.test',
        'password' => 'SafePassword123!', 'password_confirmation' => 'SafePassword123!',
        'role' => 'admin', 'department_id' => $new->id,
    ])->assertForbidden();
    expect((int) $target->fresh()->department_id)->toBe($old->id);
    $this->assertDatabaseMissing('users', ['email' => 'unauthorized@example.test']);
})->with(['admin', 'teacher', 'student']);

test('guests cannot assign a department', function () {
    $department = Department::create(['name' => 'Computing']);
    $admin = pathwiseUser('admin');
    $this->get(route('departments.assign'))->assertRedirect(route('login'));
    $this->post(route('departments.assign.store'), ['user_id' => $admin->id, 'department_id' => $department->id])
        ->assertRedirect(route('login'));
    expect($admin->fresh()->department_id)->toBeNull();
});
