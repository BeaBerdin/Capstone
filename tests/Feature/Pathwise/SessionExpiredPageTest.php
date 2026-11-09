<?php

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;

/*
| An expired security token (for example the email verification page left
| open longer than the session lifetime) shows a friendly page with a
| "Log in again" button instead of Laravel's plain "419 Page Expired".
|
| Laravel skips the CSRF check while tests run, so a test-only route throws
| the same TokenMismatchException the check throws for an expired token.
*/

beforeEach(function () {
    Route::middleware('web')->post('/__test/expired-token', function () {
        throw new TokenMismatchException('CSRF token mismatch.');
    });
});

test('an expired-token request shows the friendly session expired page', function () {
    $this->post('/__test/expired-token')
        ->assertStatus(419)
        ->assertSee('Your session has expired')
        ->assertSee('Log in again')
        ->assertSee('action="'.route('login').'"', false)
        ->assertSee('href="'.route('home').'"', false)
        ->assertDontSee('Page Expired');
});

test('the session expired page also appears for a signed-in user whose token expired', function () {
    $this->actingAs(pathwiseUser('student'))
        ->post('/__test/expired-token')
        ->assertStatus(419)
        ->assertSee('Your session has expired')
        ->assertSee('Log in again');
});
