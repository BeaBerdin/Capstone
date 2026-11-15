<?php

use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());

    // Registration requires the student/teacher roles to exist.
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'student',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('users cannot self-register as an administrator', function () {
    $response = $this->post(route('register.store'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'admin',
    ]);

    $response->assertSessionHasErrors('role');

    $this->assertGuest();
});

test('a teacher can register and is asked to verify their email', function () {
    Illuminate\Support\Facades\Notification::fake();

    $this->post(route('register.store'), [
        'first_name' => 'Tina',
        'last_name' => 'Teacher',
        'email' => 'tina@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'teacher',
    ])->assertSessionHasNoErrors();

    $user = App\Models\User::where('email', 'tina@example.com')->sole();

    expect($user->hasRole('teacher'))->toBeTrue()
        ->and($user->hasRole('student'))->toBeFalse()
        ->and($user->hasRole('admin'))->toBeFalse()
        ->and($user->hasVerifiedEmail())->toBeFalse();

    Illuminate\Support\Facades\Notification::assertSentTo($user, Illuminate\Auth\Notifications\VerifyEmail::class);

    // Teacher pages wait until the email is verified.
    $this->get(route('teacher.dashboard'))->assertRedirect(route('verification.notice'));
});

test('registration requires choosing student or teacher', function () {
    $this->post(route('register.store'), [
        'first_name' => 'No',
        'last_name' => 'Role',
        'email' => 'norole@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('role');

    $this->assertGuest();
});

/*
| Registration asks for First Name, Middle Initial (optional) and Last Name,
| saved together in the existing name column as "First M. Last".
*/

function registerWith(array $fields): Illuminate\Testing\TestResponse
{
    return test()->post(route('register.store'), array_merge([
        'email' => 'juan@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'student',
    ], $fields));
}

test('the registration form asks for first name, middle initial and last name', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('First Name')
        ->assertSee('Middle Initial')
        ->assertSee('(optional)')
        ->assertSee('Last Name')
        ->assertSee('name="first_name"', false)
        ->assertSee('name="middle_initial"', false)
        ->assertSee('name="last_name"', false)
        ->assertDontSee('name="name"', false);
});

test('the three name fields are saved as "First M. Last"', function () {
    registerWith(['first_name' => '  Juan ', 'middle_initial' => 'd', 'last_name' => 'Dela   Cruz'])
        ->assertSessionHasNoErrors();

    expect(App\Models\User::where('email', 'juan@example.com')->value('name'))->toBe('Juan D. Dela Cruz');
});

test('the middle initial is optional and may include a period', function () {
    registerWith(['first_name' => 'Maria', 'last_name' => 'Santos'])->assertSessionHasNoErrors();
    expect(App\Models\User::where('email', 'juan@example.com')->value('name'))->toBe('Maria Santos');

    // Registering logs the new user in; start the next one as a guest.
    auth()->guard('web')->logout();

    registerWith(['email' => 'ana@example.com', 'first_name' => 'Ma. Ana', 'middle_initial' => 'R.', 'last_name' => "O'Neil"])
        ->assertSessionHasNoErrors();
    expect(App\Models\User::where('email', 'ana@example.com')->value('name'))->toBe("Ma. Ana R. O'Neil");
});

test('first and last name are required and the middle initial is one letter', function () {
    registerWith(['first_name' => '', 'last_name' => ''])
        ->assertSessionHasErrors(['first_name', 'last_name']);

    registerWith(['first_name' => 'Juan', 'middle_initial' => 'Dc', 'last_name' => 'Cruz'])
        ->assertSessionHasErrors('middle_initial');

    registerWith(['first_name' => 'Juan1', 'last_name' => 'Cruz'])
        ->assertSessionHasErrors('first_name');

    $this->assertGuest();
    expect(App\Models\User::where('email', 'juan@example.com')->exists())->toBeFalse();
});
