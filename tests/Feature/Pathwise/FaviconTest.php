<?php

/*
| The PathWise icon is used everywhere; the default Laravel favicon.svg is gone.
*/

test('the welcome page uses the PathWise favicon links and no favicon.svg', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<link rel="icon" href="/favicon.ico?v=3" sizes="any">', false)
        ->assertSee('<link rel="apple-touch-icon" href="/apple-touch-icon.png?v=3">', false)
        ->assertSee('<link rel="icon" href="/favicon.png?v=3" type="image/png">', false)
        ->assertDontSee('favicon.svg', false);
});

test('the login, register and forgot-password pages use the PathWise favicon links', function (string $route) {
    $this->withoutVite();

    $this->get(route($route))
        ->assertOk()
        ->assertSee('<link rel="icon" href="/favicon.ico?v=3" sizes="any">', false)
        ->assertSee('<link rel="apple-touch-icon" href="/apple-touch-icon.png?v=3">', false)
        ->assertSee('<link rel="icon" href="/favicon.png?v=3" type="image/png">', false)
        ->assertDontSee('favicon.svg', false);
})->with(['login', 'register', 'password.request']);

test('the shared head partial (every app layout) uses the PathWise favicon links and no favicon.svg', function () {
    $this->withoutVite();

    $html = view('partials.head')->render();

    expect($html)->toContain('<link rel="icon" href="/favicon.ico?v=3" sizes="any">')
        ->and($html)->toContain('<link rel="apple-touch-icon" href="/apple-touch-icon.png?v=3">')
        ->and($html)->toContain('<link rel="icon" href="/favicon.png?v=3" type="image/png">')
        ->and($html)->not->toContain('favicon.svg');
});

test('the Laravel favicon.svg file is removed and the PathWise icons exist', function () {
    expect(file_exists(public_path('favicon.svg')))->toBeFalse()
        ->and(file_exists(public_path('favicon.ico')))->toBeTrue()
        ->and(file_exists(public_path('favicon.png')))->toBeTrue()
        ->and(getimagesize(public_path('apple-touch-icon.png'))[0])->toBe(180);
});
