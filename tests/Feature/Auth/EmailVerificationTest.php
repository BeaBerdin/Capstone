<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;
use Livewire\Livewire;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::emailVerification());
});

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('verification.notice'));

    $response->assertOk();
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
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
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

test('already verified user visiting verification link is redirected without firing event again', function () {
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
        ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertNotDispatched(Verified::class);
});
test('verification screen watches for verification without reloading', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('wire:poll.5s.keep-alive="checkVerification"', false)
        ->assertSee('This page will update automatically once your email is verified.');
});

test('verification status keeps waiting while the email is unverified', function () {
    $user = User::factory()->unverified()->create();

    Livewire::actingAs($user)
        ->test('pages::auth.verification-status')
        ->call('checkVerification')
        ->assertSet('verified', false)
        ->assertDontSee('Email verified successfully!');
});

test('verification status detects a verification made in another tab', function () {
    $user = User::factory()->unverified()->create();

    $component = Livewire::actingAs($user)->test('pages::auth.verification-status');

    // The link is clicked elsewhere (e.g. from Gmail), so only the database changes.
    User::whereKey($user->id)->update(['email_verified_at' => now()]);

    $component->call('checkVerification')
        ->assertSet('verified', true)
        ->assertSee('Email verified successfully! Redirecting you to your dashboard...')
        ->assertSee('data-redirect="'.route('dashboard').'"', false)
        ->assertDontSee('wire:poll', false);
});

test('verification email uses the PathWise design and the signed link', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create(['name' => 'Juan Dela Cruz']);
    $user->sendEmailVerificationNotification();

    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user) {
        $mail = $notification->toMail($user);

        expect($mail->subject)->toBe('Welcome to PathWise! Please Verify Your Email');
        expect($mail->view)->toBe(['emails.verify-email', 'emails.verify-email-text']);

        $url = $mail->viewData['url'];
        expect($url)->toContain('/email/verify/'.$user->id.'/'.sha1($user->email))
            ->toContain('signature=');

        $html = (string) $mail->render();
        expect($html)
            ->toContain('Welcome to PathWise!')
            ->toContain('Hi Juan Dela Cruz,')
            ->toContain('Verify Email Address')
            ->toContain('alt="PathWise"')
            ->toContain("If you didn't create a PathWise account, you can safely ignore this email.")
            ->toContain('href="'.e($url).'"');

        // The emailed link really verifies the account.
        $this->actingAs($user)->get($url)
            ->assertRedirect(route('dashboard', absolute: false).'?verified=1');
        expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

        return true;
    });
});

/*
| The emailed link works in any browser (Gmail app, phone, a second browser)
| without logging in there, and never logs anyone in. Laravel's signature,
| expiry and email hash checks still apply.
*/

function verificationLinkFor(User $user, ?Carbon\CarbonInterface $expires = null, ?string $hash = null): string
{
    return URL::temporarySignedRoute(
        'verification.verify',
        $expires ?? now()->addMinutes(60),
        ['id' => $user->id, 'hash' => $hash ?? sha1($user->email)],
    );
}

test('the link verifies the account in a browser where nobody is logged in, without logging in', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $this->get(verificationLinkFor($user))
        ->assertOk()
        ->assertSee('Your email address has been verified.')
        ->assertSee('You do not need to log in again there.')
        ->assertSee(route('login'), false);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
    $this->assertGuest();
});

test('the original logged-in browser gets dashboard access after a refresh once verified elsewhere', function () {
    $user = pathwiseUser('student', ['email_verified_at' => null]);

    // Original browser: logged in but not verified yet.
    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('verification.notice'));

    // The link is opened somewhere else (no session there).
    auth()->logout();
    $this->get(verificationLinkFor($user))->assertOk();

    // Back in the original browser, a refresh is enough.
    $this->actingAs($user->fresh())->get(route('dashboard'))->assertRedirect(route('student.dashboard'));
    $this->actingAs($user->fresh())->get(route('verification.notice'))->assertRedirect();
});

test('the link verifies its own account but leaves another logged-in account untouched', function () {
    $owner = User::factory()->unverified()->create();
    $other = User::factory()->create();

    $this->actingAs($other)
        ->get(verificationLinkFor($owner))
        ->assertOk()
        ->assertSee('This browser is logged in to a different PathWise account.');

    expect($owner->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->assertAuthenticatedAs($other);
});

test('a link with a bad signature, an expired link or a wrong hash is refused without a login', function () {
    $user = User::factory()->unverified()->create();

    // Tampered signature.
    $this->get(verificationLinkFor($user).'x')->assertForbidden();

    // Expired.
    $this->get(verificationLinkFor($user, now()->subMinute()))->assertForbidden();

    // Signed, but for another email address.
    $this->get(verificationLinkFor($user, hash: sha1('someone@else.test')))->assertForbidden();

    // Unsigned.
    $this->get('/email/verify/'.$user->id.'/'.sha1($user->email))->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    $this->assertGuest();
});

test('a signed link for an account that does not exist is refused', function () {
    $this->get(URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => 999999, 'hash' => sha1('x@y.test')]))
        ->assertForbidden();
});
