<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;

/*
| First name, middle initial (optional, 1–2 letters) and last name on
| self-registration and the Super Admin's Create Account / Edit forms. The
| existing `name` column is always filled as "First M. Last" so every screen
| keeps working. Existing users keep their name; their parts stay null.
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    $this->withoutVite();
    fakeFileDisks();
    Illuminate\Support\Facades\Notification::fake();
});

test('the combined name is "First M. Last", without "M." when there is no initial', function (string $first, ?string $initial, string $last, string $expected) {
    expect(User::composeName($first, $initial, $last))->toBe($expected);
})->with([
    ['Juan', 'd', 'Dela Cruz', 'Juan D. Dela Cruz'],
    ['Juan', 'D.', 'Dela Cruz', 'Juan D. Dela Cruz'],
    ['Juan', 'dc', 'Dela Cruz', 'Juan DC. Dela Cruz'],
    ['Juan', null, 'Dela Cruz', 'Juan Dela Cruz'],
    ['Juan', '', 'Dela Cruz', 'Juan Dela Cruz'],
    ['  Ma.  Clara ', ' r ', ' Santos  ', 'Ma. Clara R. Santos'],
]);

test('student self-registration stores the parts and the combined name', function () {
    $this->post(route('register.store'), [
        'first_name' => 'Juan',
        'middle_initial' => 'd',
        'last_name' => 'Dela Cruz',
        'email' => 'juan@example.org',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'student',
        'study_load' => UploadedFile::fake()->create('load.pdf', 100, 'application/pdf'),
    ])->assertSessionHasNoErrors();

    $user = User::where('email', 'juan@example.org')->sole();

    expect($user->name)->toBe('Juan D. Dela Cruz')
        ->and($user->first_name)->toBe('Juan')
        ->and($user->middle_initial)->toBe('D')
        ->and($user->last_name)->toBe('Dela Cruz');
});

test('teacher self-registration stores the parts too, with a two-letter initial', function () {
    $this->post(route('register.store'), [
        'first_name' => 'Maria',
        'middle_initial' => 'DC',
        'last_name' => 'Santos',
        'email' => 'maria@example.org',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'teacher',
        'teacher_id' => teacherIdUpload(),
    ])->assertSessionHasNoErrors();

    $user = User::where('email', 'maria@example.org')->sole();

    expect($user->name)->toBe('Maria DC. Santos')
        ->and($user->middle_initial)->toBe('DC');
});

test('the Super Admin creates an account with separate name fields', function () {
    $edp = pathwiseUser('super_admin');

    $this->actingAs($edp)
        ->get(route('users.create'))
        ->assertOk()
        ->assertSee('name="first_name"', false)
        ->assertSee('name="middle_initial"', false)
        ->assertSee('name="last_name"', false)
        ->assertDontSee('name="name"', false);

    $this->actingAs($edp)->post(route('users.store'), [
        'first_name' => 'Ana',
        'middle_initial' => 'R.',
        'last_name' => 'Reyes',
        'email' => 'ana.reyes@uclm.edu.ph',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'role' => 'admin',
    ])->assertSessionHasNoErrors();

    $user = User::where('email', 'ana.reyes@uclm.edu.ph')->sole();

    expect($user->name)->toBe('Ana R. Reyes')
        ->and([$user->first_name, $user->middle_initial, $user->last_name])->toBe(['Ana', 'R', 'Reyes']);
});

test('the Super Admin create form validates the parts', function (array $overrides, string $field) {
    $this->actingAs(pathwiseUser('super_admin'))->post(route('users.store'), array_merge([
        'first_name' => 'Ana',
        'last_name' => 'Reyes',
        'email' => 'ana.reyes@uclm.edu.ph',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'role' => 'admin',
    ], $overrides))->assertSessionHasErrors($field);

    expect(User::where('email', 'ana.reyes@uclm.edu.ph')->exists())->toBeFalse();
})->with([
    'no first name' => [['first_name' => ''], 'first_name'],
    'no last name' => [['last_name' => ''], 'last_name'],
    'initial too long' => [['middle_initial' => 'ABC'], 'middle_initial'],
    'initial with a digit' => [['middle_initial' => 'A1'], 'middle_initial'],
    'first name with digits' => [['first_name' => 'Ana2'], 'first_name'],
]);

test('editing in parts updates both the parts and the combined name', function () {
    $edp = pathwiseUser('super_admin');
    $student = pathwiseUser('student', [
        'name' => 'Juan D. Dela Cruz', 'first_name' => 'Juan', 'middle_initial' => 'D', 'last_name' => 'Dela Cruz',
    ]);

    $this->actingAs($edp)->get(route('users.edit', $student))
        ->assertOk()
        ->assertSee('value="Juan"', false)
        ->assertSee('value="Dela Cruz"', false)
        ->assertDontSee('data-test="legacy-full-name"', false);

    $this->actingAs($edp)->put(route('users.update', $student), [
        'first_name' => 'Juan', 'middle_initial' => '', 'last_name' => 'Cruz', 'email' => $student->email,
    ])->assertSessionHasNoErrors();

    $student = $student->fresh();

    expect($student->name)->toBe('Juan Cruz')
        ->and($student->middle_initial)->toBeNull()
        ->and($student->last_name)->toBe('Cruz');
});

test('existing users keep their name; the edit form shows the full name until it is edited in parts', function () {
    $edp = pathwiseUser('super_admin');
    $legacy = pathwiseUser('teacher', ['name' => 'Professor Legacy Person', 'email' => 'legacy@uclm.edu.ph']);

    expect($legacy->first_name)->toBeNull()
        ->and($legacy->middle_initial)->toBeNull()
        ->and($legacy->last_name)->toBeNull();

    $this->actingAs($edp)->get(route('users.edit', $legacy))
        ->assertOk()
        ->assertSee('data-test="legacy-full-name"', false)
        ->assertSee('Professor Legacy Person');

    // Saving with the name fields left empty keeps the current name.
    $this->actingAs($edp)->put(route('users.update', $legacy), [
        'first_name' => '', 'middle_initial' => '', 'last_name' => '', 'email' => 'legacy@uclm.edu.ph',
    ])->assertSessionHasNoErrors();

    expect($legacy->fresh()->name)->toBe('Professor Legacy Person')
        ->and($legacy->fresh()->first_name)->toBeNull();

    // Everywhere else the full name still shows.
    $this->actingAs($edp)->get(route('users.index'))->assertSee('Professor Legacy Person');
});

test('a name changed as one field (e.g. profile settings) clears the stale parts', function () {
    $user = pathwiseUser('student', [
        'name' => 'Juan D. Dela Cruz', 'first_name' => 'Juan', 'middle_initial' => 'D', 'last_name' => 'Dela Cruz',
    ]);

    $user->update(['name' => 'Juanito Cruz']);

    $user = $user->fresh();
    expect($user->name)->toBe('Juanito Cruz')
        ->and($user->first_name)->toBeNull()
        ->and($user->last_name)->toBeNull();
});
