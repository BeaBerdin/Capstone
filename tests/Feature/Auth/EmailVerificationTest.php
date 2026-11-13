<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::emailVerification());
});

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('verification.notice'));

    $response->assertOk();
});

test('verification screen explains unavailable email delivery without claiming a link was sent', function () {
    config(['mail.default' => 'log']);
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->withSession(['status' => 'verification-link-sent'])
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Email delivery is not configured yet.')
        ->assertDontSee('A new verification link has been sent');
});

test('verification screen shows the resend confirmation when email delivery is configured', function () {
    config(['mail.default' => 'smtp']);
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->withSession(['status' => 'verification-link-sent'])
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('A new verification link has been sent')
        ->assertDontSee('Email delivery is not configured yet.');
});

test('email can be verified', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertOk()->assertViewIs('pages.auth.email-verified');
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')],
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('already verified user sees confirmation without firing event again', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->actingAs($user)->get($verificationUrl)
        ->assertOk()->assertViewIs('pages.auth.email-verified');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertNotDispatched(Verified::class);
});

test('a signed email link verifies a guest browser without authenticating it', function () {
    $user = User::factory()->unverified()->create();
    Event::fake([Verified::class]);
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);

    $response = $this->get($url);

    $response->assertOk()
        ->assertViewIs('pages.auth.email-verified')
        ->assertSee('Return to the original PathWise tab')
        ->assertHeader('Referrer-Policy', 'no-referrer');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class, fn ($event) => $event->user->id === $user->id);
    $this->assertGuest();
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('verification does not switch an account already logged in to the email browser', function () {
    $target = User::factory()->unverified()->create();
    $other = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $target->id, 'hash' => sha1($target->email),
    ]);

    $this->actingAs($other)->get($url)->assertOk();

    $this->assertAuthenticatedAs($other);
    expect($target->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and($other->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('unsigned and tampered email links cannot verify an account', function () {
    $user = User::factory()->unverified()->create();
    Event::fake([Verified::class]);
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);

    $this->get(explode('?', $url)[0])->assertForbidden();
    $this->get(str_replace('signature=', 'signature=invalid', $url))->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    Event::assertNotDispatched(Verified::class);
});

test('expired email links cannot verify an account', function () {
    $user = User::factory()->unverified()->create();
    Event::fake([Verified::class]);
    $url = URL::temporarySignedRoute('verification.verify', now()->subMinute(), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);

    $this->get($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    Event::assertNotDispatched(Verified::class);
});

test('a link for an old email address cannot verify a changed address', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);
    $user->update(['email' => 'new-address@example.test']);

    $this->get($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('inactive users cannot be verified through a signed link', function () {
    $user = User::factory()->unverified()->create(['is_active' => false]);
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);

    $this->get($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    $this->assertGuest();
});

test('repeated verification clicks only dispatch the verified event once', function () {
    $user = User::factory()->unverified()->create();
    Event::fake([Verified::class]);
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);

    $this->get($url)->assertOk();
    $this->get($url)->assertOk();

    Event::assertDispatchedTimes(Verified::class, 1);
    $this->assertGuest();
});

test('verification status requires an authenticated browser', function () {
    $this->get(route('verification.status'))->assertRedirect(route('login'));
});

test('the original browser sees fresh verification status without signing in again', function () {
    $user = pathwiseUser('teacher', ['email_verified_at' => null]);

    $this->actingAs($user)->getJson(route('verification.status'))
        ->assertOk()->assertExactJson(['verified' => false]);

    // A separate model instance represents the other browser verifying the link.
    $user->fresh()->markEmailAsVerified();

    $response = $this->getJson(route('verification.status'))
        ->assertOk()->assertExactJson(['verified' => true]);
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    $this->assertAuthenticatedAs($user);
    $this->actingAs($user->fresh())->get(route('dashboard'))
        ->assertRedirect(route('teacher.dashboard'));
});

test('status polling cannot exhaust the signed link verification rate limit', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user);
    for ($i = 0; $i < 8; $i++) {
        $this->getJson(route('verification.status'))->assertOk();
    }
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);

    $this->get($url)->assertOk();
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});
