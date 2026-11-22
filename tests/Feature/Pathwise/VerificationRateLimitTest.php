<?php

use App\Models\StudentApplication;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Support\VerificationLogin;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/*
| Rate limits for email verification and the application status page
| (audit items V-4 and R-1).
|
| Laravel's plain `throttle:6,1` kept one counter per IP for guests (and one
| per user when signed in), shared by every route using it. Students on one
| campus IP used up each other's limits, and the status page's polling used
| up the student's own "resend" limit. Each route now has its own named
| limiter: a tight limit per account / session / user, and a wide per-IP
| ceiling.
|
| Note on sessions in tests: without a session cookie every test request
| gets a new session id, so tests that need "the same browser" send the
| session cookie explicitly (asBrowser()).
*/

const CAMPUS_IP = '203.0.113.10';

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    fakeFileDisks();
    $this->withServerVariables(['REMOTE_ADDR' => CAMPUS_IP]);
});

/** An approved student whose email is not verified yet. */
function rateLimitStudent(): User
{
    $user = pathwiseUser('student', ['email_verified_at' => null]);
    Storage::disk('local')->put('study-loads/'.$user->id.'/a.pdf', '%PDF-1.4');

    StudentApplication::create([
        'user_id' => $user->id,
        'submission_no' => 1,
        'study_load_path' => 'study-loads/'.$user->id.'/a.pdf',
        'study_load_original_name' => 'a.pdf',
        'study_load_mime' => 'application/pdf',
        'study_load_size' => 8,
        'status' => 'approved',
        'submitted_at' => now(),
    ]);

    return $user->fresh();
}

function rateLimitLink(User $student): string
{
    return URL::temporarySignedRoute('verification.verify', now()->addHours(72), [
        'id' => $student->id,
        'hash' => sha1($student->email),
    ]);
}

/**
 * A separate browser: signed out, empty session, its own session cookie
 * (so consecutive requests really share one session id).
 */
function asBrowser($test, ?string $sessionId = null)
{
    auth()->guard('web')->logout();
    session()->flush();

    return $test->withCookie(config('session.cookie'), $sessionId ?? Str::random(40));
}

function verificationLimit(string $name): int
{
    return AppServiceProvider::VERIFICATION_LIMITS[$name];
}

test('30 students on one campus IP can each open their link and continue', function () {
    $students = collect(range(1, 30))->map(fn () => rateLimitStudent());

    foreach ($students as $student) {
        asBrowser($this);

        $this->get(rateLimitLink($student))
            ->assertOk()
            ->assertSee('data-test="verification-continue"', false);

        $this->post(route('verification.continue'))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($student);
    }

    expect(DB::table(VerificationLogin::TABLE)->count())->toBe(30);
});

test('one student opening their own link too often is limited without affecting other students on the same IP', function () {
    $student = rateLimitStudent();
    $classmate = rateLimitStudent();
    $link = rateLimitLink($student);

    for ($i = 1; $i <= verificationLimit('link_per_account_per_minute'); $i++) {
        asBrowser($this);
        $this->get($link)->assertOk();
    }

    asBrowser($this);
    $this->get($link)->assertStatus(429);

    // The classmate on the same IP is not affected and can sign in.
    asBrowser($this);
    $this->get(rateLimitLink($classmate))->assertOk()->assertSee('data-test="verification-continue"', false);
    $this->post(route('verification.continue'))->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($classmate);

    // After a minute the student's own limit resets.
    $this->travel(61)->seconds();
    asBrowser($this);
    $this->get($link)->assertOk();
});

test('60 polls of the status page do not use up the resend limit', function () {
    Notification::fake();
    $student = rateLimitStudent();

    for ($i = 1; $i <= verificationLimit('check_per_user_per_minute'); $i++) {
        $this->actingAs($student)->getJson(route('student-application.check'))->assertOk();
    }

    // Polling has its own limit...
    $this->actingAs($student)->getJson(route('student-application.check'))->assertStatus(429);

    // ...and the resend still works.
    $this->actingAs($student)
        ->post(route('verification.send'))
        ->assertRedirect()
        ->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentTo($student, VerifyEmail::class);
});

test('resending is limited per student per minute and per hour, without blocking polling', function () {
    Notification::fake();
    $student = rateLimitStudent();

    for ($i = 1; $i <= verificationLimit('resend_per_user_per_minute'); $i++) {
        $this->actingAs($student)->post(route('verification.send'))->assertSessionHas('status', 'verification-link-sent');
    }

    $this->actingAs($student)->post(route('verification.send'))->assertStatus(429);

    // Polling is unaffected by the resends.
    $this->actingAs($student)->getJson(route('student-application.check'))->assertOk();

    // Another student is unaffected.
    $this->actingAs(rateLimitStudent())->post(route('verification.send'))->assertSessionHas('status', 'verification-link-sent');

    // Hourly ceiling: spread over several minutes, still at most 20.
    $other = rateLimitStudent();
    for ($i = 1; $i <= verificationLimit('resend_per_user_per_hour'); $i++) {
        $this->travel(61)->seconds();
        $this->actingAs($other)->post(route('verification.send'))->assertSessionHas('status', 'verification-link-sent');
    }

    $this->travel(61)->seconds();
    $this->actingAs($other)->post(route('verification.send'))->assertStatus(429);
});

test('Continue is limited per browser session; another session on the same IP is not affected', function () {
    $session = Str::random(40);

    for ($i = 1; $i <= verificationLimit('continue_per_session_per_minute'); $i++) {
        asBrowser($this, $session);
        // No grant in this session: nothing happens, only the login page.
        $this->post(route('verification.continue'))->assertRedirect(route('login'));
    }

    asBrowser($this, $session);
    $this->post(route('verification.continue'))->assertStatus(429);
    $this->assertGuest();

    // A student in another browser on the same IP signs in normally.
    $student = rateLimitStudent();
    asBrowser($this);
    $this->get(rateLimitLink($student))->assertOk();
    $this->post(route('verification.continue'))->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($student);
});

test('one IP has a wide ceiling for opening links', function () {
    $perAccount = verificationLimit('link_per_account_per_minute');
    $ceiling = verificationLimit('link_per_ip_per_minute');

    // Many different students, each within their own limit, up to the ceiling.
    $sent = 0;
    while ($sent < $ceiling) {
        $link = rateLimitLink(rateLimitStudent());

        for ($i = 1; $i <= $perAccount && $sent < $ceiling; $i++, $sent++) {
            $this->get($link)->assertOk();
        }
    }

    // The next request from this IP is refused, even for a new student...
    $next = rateLimitStudent();
    $this->get(rateLimitLink($next))->assertStatus(429);

    // ...while the same student from another network is fine.
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
        ->get(rateLimitLink($next))
        ->assertOk();
});

test('one IP has a wide ceiling for Continue', function () {
    $perSession = verificationLimit('continue_per_session_per_minute');
    $ceiling = verificationLimit('continue_per_ip_per_minute');

    $sent = 0;
    while ($sent < $ceiling) {
        $session = Str::random(40);

        for ($i = 1; $i <= $perSession && $sent < $ceiling; $i++, $sent++) {
            asBrowser($this, $session);
            $this->post(route('verification.continue'))->assertRedirect(route('login'));
        }
    }

    asBrowser($this);
    $this->post(route('verification.continue'))->assertStatus(429);

    asBrowser($this);
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
        ->post(route('verification.continue'))
        ->assertRedirect(route('login'));
});

test('a forwarded IP header cannot change the address that is counted', function () {
    $student = rateLimitStudent();

    $this->withHeaders(['X-Forwarded-For' => '198.51.100.7'])
        ->get(rateLimitLink($student))
        ->assertOk();

    // No proxy is trusted, so the connecting address is what is counted.
    $counted = fn (string $ip) => RateLimiter::attempts(md5('verification-link'.'verification-link-ip|'.$ip));

    expect($counted(CAMPUS_IP))->toBe(1)
        ->and($counted('198.51.100.7'))->toBe(0);
});

test('forged links with a student\'s id cannot lock that student out, but count towards the IP ceiling', function () {
    $student = rateLimitStudent();
    $link = rateLimitLink($student);
    $forged = verificationLimit('link_per_account_per_minute') + 5;

    // Far more than the per-account limit, all with a broken signature
    // (anyone can put a student's id in a URL; only the EDP's link is signed).
    for ($i = 1; $i <= $forged; $i++) {
        $this->get($link.'x')->assertForbidden();
    }

    $attempts = fn (string $key) => RateLimiter::attempts(md5('verification-link'.$key));

    expect($attempts('verification-link-account|'.$student->id))->toBe(0)
        ->and($attempts('verification-link-ip|'.CAMPUS_IP))->toBe($forged);

    // The genuine link still works and is counted for its account.
    asBrowser($this);
    $this->get($link)->assertOk()->assertSee('data-test="verification-continue"', false);
    expect($attempts('verification-link-account|'.$student->id))->toBe(1);
});

test('signing in through Continue regenerates the session id (same browser session)', function () {
    $student = rateLimitStudent();
    $session = Str::random(40);

    asBrowser($this, $session);
    $this->get(rateLimitLink($student))->assertOk();
    expect(session()->getId())->toBe($session);

    // Same session cookie: only a successful sign-in changes the id.
    $this->post(route('verification.continue'))->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($student);
    expect(session()->getId())->not->toBe($session);
});

test('a refused Continue keeps the browser session as it was', function () {
    $session = Str::random(40);

    asBrowser($this, $session);
    $this->post(route('verification.continue'))->assertRedirect(route('login'));

    $this->assertGuest();
    expect(session()->getId())->toBe($session);
});
