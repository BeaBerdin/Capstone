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
        'name' => 'John Doe',
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
        'name' => 'Jane Doe',
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
        'name' => 'Tina Teacher',
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
        'name' => 'No Role',
        'email' => 'norole@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('role');

    $this->assertGuest();
});
