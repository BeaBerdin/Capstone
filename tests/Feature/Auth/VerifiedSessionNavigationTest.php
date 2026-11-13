<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('a teacher keeps their browser session when opening courses after cross-browser verification', function () {
    Notification::fake();
    $this->seed(\Database\Seeders\RoleSeeder::class);

    // Exercise saved sessions and encrypted cookies, not actingAs(), which
    // retains an authenticated guard even if the next request loses its cookie.
    config(['session.driver' => 'database', 'session.lottery' => [0, 100]]);
    $request = function (array &$cookies, string $method, string $url, array $data = [], array $headers = []) {
        Auth::forgetGuards();
        app('session')->forgetDrivers();
        app()->forgetInstance('session.store');

        $response = $this->call($method, $url, $data, $cookies, [], $headers);

        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        return $response;
    };
    $originalBrowser = [];
    $emailBrowser = [];

    $request($originalBrowser, 'POST', route('register.store'), [
        'name' => 'Teacher Navigation Test',
        'email' => 'teacher-navigation@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'teacher',
    ])->assertRedirect('/dashboard');

    $teacher = User::where('email', 'teacher-navigation@example.test')->sole();
    $request($originalBrowser, 'GET', route('dashboard'))
        ->assertRedirect(route('verification.notice'));
    $request($originalBrowser, 'GET', route('verification.notice'))->assertOk();
    $request($originalBrowser, 'GET', route('verification.status'))
        ->assertExactJson(['verified' => false]);

    $link = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $teacher->id,
        'hash' => sha1($teacher->email),
    ]);
    $request($emailBrowser, 'GET', $link)->assertOk();
    $request($emailBrowser, 'GET', route('teacher.my-courses'))
        ->assertRedirect(route('login'));

    $request($originalBrowser, 'GET', route('verification.status'))
        ->assertExactJson(['verified' => true]);
    $request($originalBrowser, 'GET', route('dashboard'))
        ->assertRedirect(route('teacher.dashboard'));
    $request($originalBrowser, 'GET', route('teacher.dashboard'))->assertOk();
    $request($originalBrowser, 'GET', route('teacher.my-courses'), [], [
        'HTTP_X_LIVEWIRE_NAVIGATE' => '1',
    ])->assertOk()->assertViewIs('Teacher.my-courses');
    $request($originalBrowser, 'GET', route('teacher.my-courses'))
        ->assertOk()->assertViewIs('Teacher.my-courses');
});
