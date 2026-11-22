<?php

use App\Models\Department;

/*
| Department short code (e.g. "CCS"): optional, up to 10 letters/numbers,
| stored in upper case, unique when set. The admin greeting uses it, or the
| full department name when no code is set; no department: "Department Head".
*/

beforeEach(function () {
    $this->withoutVite();
});

test('the Super Admin can set a short code; it is stored in upper case', function () {
    $edp = pathwiseUser('super_admin');

    $this->actingAs($edp)
        ->post(route('departments.store'), ['name' => 'College of Computer Studies', 'code' => ' ccs '])
        ->assertSessionHasNoErrors();

    $department = Department::where('name', 'College of Computer Studies')->sole();
    expect($department->code)->toBe('CCS');

    $this->actingAs($edp)
        ->put(route('departments.update', $department), ['name' => 'College of Computer Studies', 'code' => 'ccs2'])
        ->assertSessionHasNoErrors();
    expect($department->fresh()->code)->toBe('CCS2');

    // Clearing it is allowed (the code is optional).
    $this->actingAs($edp)
        ->put(route('departments.update', $department), ['name' => 'College of Computer Studies', 'code' => ''])
        ->assertSessionHasNoErrors();
    expect($department->fresh()->code)->toBeNull();
});

test('the short code is validated: max 10, letters and numbers only, unique when set', function (string $code, string $message) {
    $edp = pathwiseUser('super_admin');
    Department::create(['name' => 'College of Engineering', 'code' => 'COE']);

    $this->actingAs($edp)
        ->post(route('departments.store'), ['name' => 'College of Computer Studies', 'code' => $code])
        ->assertSessionHasErrors(['code' => $message]);

    expect(Department::where('name', 'College of Computer Studies')->exists())->toBeFalse();
})->with([
    'too long' => ['ABCDEFGHIJK', 'The short code may not be longer than 10 characters.'],
    'spaces or symbols' => ['C S-1', 'The short code may only contain letters and numbers, e.g. CCS.'],
    'already used (any case)' => ['coe', 'Another department already uses this short code.'],
]);

test('several departments may have no code', function () {
    $edp = pathwiseUser('super_admin');

    $this->actingAs($edp)->post(route('departments.store'), ['name' => 'Department A'])->assertSessionHasNoErrors();
    $this->actingAs($edp)->post(route('departments.store'), ['name' => 'Department B', 'code' => ''])->assertSessionHasNoErrors();

    expect(Department::whereNull('code')->count())->toBe(2);
});

test('the department form and list show the short code', function () {
    $edp = pathwiseUser('super_admin');
    $department = Department::create(['name' => 'College of Computer Studies', 'code' => 'CCS']);

    $this->actingAs($edp)->get(route('departments.edit', $department))
        ->assertOk()
        ->assertSee('name="code"', false)
        ->assertSee('value="CCS"', false);

    $this->actingAs($edp)->get(route('departments.index'))
        ->assertOk()
        ->assertSee('data-test="department-code">CCS<', false);
});

test('the admin greeting uses the short code, else the full name, else "Department Head"', function () {
    $withCode = Department::create(['name' => 'College of Computer Studies', 'code' => 'CCS']);
    $noCode = Department::create(['name' => 'College of Engineering']);

    $this->actingAs(pathwiseUser('admin', ['department_id' => $withCode->id]))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(', CCS Department Head!', false)
        // The assignment line keeps the full name.
        ->assertSee('Department Assignment: College of Computer Studies');

    $this->actingAs(pathwiseUser('admin', ['department_id' => $noCode->id]))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(', College of Engineering Department Head!', false);

    $this->actingAs(pathwiseUser('admin', ['department_id' => null]))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(', Department Head!', false);
});
