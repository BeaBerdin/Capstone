<?php

use App\Models\User;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('profile.edit'))->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('first_name', 'Test')
        ->set('middle_initial', 'q')
        ->set('last_name', 'User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test Q. User')
        ->and($user->first_name)->toBe('Test')
        ->and($user->middle_initial)->toBe('Q')
        ->and($user->last_name)->toBe('User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('first_name', 'Test')
        ->set('last_name', 'User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can deactivate their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deactivateAccount');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    // The account still exists; it is only switched off.
    expect($user->fresh())->not->toBeNull()
        ->and($user->fresh()->is_active)->toBeFalse();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to deactivate account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'wrong-password')
        ->call('deactivateAccount');

    $response->assertHasErrors(['password']);

    expect($user->fresh()->is_active)->not->toBeFalse();
});

test('the name fields are prefilled from the saved name parts', function () {
    $user = User::factory()->create(User::nameAttributes('Lea', 'R', 'Fernandez'));

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->assertSet('first_name', 'Lea')
        ->assertSet('middle_initial', 'R')
        ->assertSet('last_name', 'Fernandez')
        ->assertSeeHtml('wire:model="first_name"')
        ->assertSeeHtml('wire:model="middle_initial"')
        ->assertSeeHtml('wire:model="last_name"')
        ->assertDontSeeHtml('wire:model="name"');
});

test('older accounts without name parts are prefilled by splitting the full name', function (string $name, string $first, string $initial, string $last) {
    $user = User::factory()->create(['name' => $name, 'first_name' => null, 'middle_initial' => null, 'last_name' => null]);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->assertSet('first_name', $first)
        ->assertSet('middle_initial', $initial)
        ->assertSet('last_name', $last);
})->with([
    'first, initial, last' => ['Juan D. Dela Cruz', 'Juan', 'D', 'Dela Cruz'],
    'no initial' => ['Maria Santos', 'Maria', '', 'Santos'],
    'two-letter initial' => ['Ana DC. Reyes', 'Ana', 'DC', 'Reyes'],
    'particle kept in the last part' => ['Juan De La Cruz', 'Juan De La', '', 'Cruz'],
    'abbreviated first name' => ['Ma. Clara Reyes', 'Ma. Clara', '', 'Reyes'],
]);

test('saving the profile keeps users.name and the name parts in sync', function () {
    $user = User::factory()->create(['name' => 'Juan D. Dela Cruz', 'first_name' => null, 'middle_initial' => null, 'last_name' => null]);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('last_name', 'Santos')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toBe('Juan D. Santos')
        ->and($user->first_name)->toBe('Juan')
        ->and($user->middle_initial)->toBe('D')
        ->and($user->last_name)->toBe('Santos');
});

test('profile name fields use the registration rules', function (array $input, string $error) {
    $user = User::factory()->create(User::nameAttributes('Lea', 'R', 'Fernandez'));

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.profile');
    foreach ($input as $field => $value) {
        $component->set($field, $value);
    }

    $component->call('updateProfileInformation')->assertHasErrors([$error]);

    expect($user->fresh()->name)->toBe('Lea R. Fernandez');
})->with([
    'first name required' => [['first_name' => ''], 'first_name'],
    'last name required' => [['last_name' => ''], 'last_name'],
    'first name max 100' => [['first_name' => str_repeat('a', 101)], 'first_name'],
    'last name max 100' => [['last_name' => str_repeat('a', 101)], 'last_name'],
    'initial at most 2 letters' => [['middle_initial' => 'ABC'], 'middle_initial'],
    'initial letters only' => [['middle_initial' => '1'], 'middle_initial'],
    'first name letters only' => [['first_name' => 'Lea1'], 'first_name'],
]);
