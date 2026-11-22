<?php

use App\Models\StudentApplication;
use App\Models\User;
use App\Notifications\StudentApplicationApproved;
use App\Support\VerificationLogin;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/*
| Opening the verification link in another browser signs the student in
| there too, safely: the link never logs in by itself; opening it stores a
| one-time grant in that browser's session, redeemed by a CSRF-protected POST;
| one sign-in per link; no silent account switching.
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    fakeFileDisks();
});

function approvedStudent(array $attributes = []): User
{
    $user = pathwiseUser('student', array_merge(['email_verified_at' => null], $attributes));
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

/** The real 72-hour approval link for this student. */
function approvalLink(User $student): string
{
    return (new StudentApplicationApproved)->toMail($student)->viewData['buttonUrl'];
}

test('a browser with nobody signed in is signed in as the student and reaches the dashboard', function () {
    $student = approvedStudent();
    $link = approvalLink($student);

    // Opening the link verifies but does not log in by itself.
    $page = $this->get($link)
        ->assertOk()
        ->assertSee('data-test="verification-continue"', false)
        ->assertSee(route('verification.continue'), false)
        ->assertHeader('Referrer-Policy', 'no-referrer');

    expect($page->headers->get('Cache-Control'))->toContain('no-store');
    $this->assertGuest();
    expect($student->fresh()->hasVerifiedEmail())->toBeTrue();

    // The student must click: nothing on the page submits by itself.
    expect($page->getContent())
        ->not->toContain('.submit()')
        ->not->toContain('requestSubmit');
    $page->assertSee('Continue as '.$student->name);

    // The grant is in this browser's session, bound to the student; the link
    // itself is stored only as a hash.
    $grant = session(VerificationLogin::SESSION_KEY);
    expect($grant['user_id'])->toBe($student->id)
        ->and($grant['link'])->not->toContain('signature')
        ->and(strlen($grant['link']))->toBe(64);

    $sessionBefore = session()->getId();

    // The page's POST redeems it.
    $this->post(route('verification.continue'))->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($student);
    expect(session()->getId())->not->toBe($sessionBefore)
        ->and(session(VerificationLogin::SESSION_KEY))->toBeNull();

    $this->get(route('dashboard'))->assertRedirect(route('student.dashboard'));
    $this->get(route('student.dashboard'))->assertOk();
});

test('the same link cannot sign in twice (replay)', function () {
    $student = approvedStudent();
    $link = approvalLink($student);

    $this->get($link);
    $this->post(route('verification.continue'))->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($student);

    // A second browser opens the same link later.
    auth()->guard('web')->logout();
    session()->flush();

    $this->get($link)
        ->assertOk()
        ->assertDontSee('data-test="verification-continue"', false)
        ->assertSee('Log in on this device');

    $this->post(route('verification.continue'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('two browsers opening the link at the same time: only one is signed in', function () {
    $student = approvedStudent();
    $link = approvalLink($student);

    // Both open the link before either continues: both hold a grant.
    $this->get($link);
    $grantA = session(VerificationLogin::SESSION_KEY);

    session()->flush();
    $this->get($link);
    $grantB = session(VerificationLogin::SESSION_KEY);
    expect($grantA['link'])->toBe($grantB['link']);

    // Browser A redeems first.
    session()->flush();
    $this->withSession([VerificationLogin::SESSION_KEY => $grantA])->post(route('verification.continue'));
    $this->assertAuthenticatedAs($student);

    // Browser B is refused: the link was used.
    auth()->guard('web')->logout();
    session()->flush();
    $this->withSession([VerificationLogin::SESSION_KEY => $grantB])
        ->post(route('verification.continue'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', fn ($status) => str_contains($status, 'already used'));
    $this->assertGuest();
});

test('posting without a grant, or with an expired grant, signs no one in', function () {
    $student = approvedStudent(['email_verified_at' => now()]);

    $this->post(route('verification.continue'))->assertRedirect(route('login'));
    $this->assertGuest();

    $this->withSession([VerificationLogin::SESSION_KEY => [
        'user_id' => $student->id,
        'link' => str_repeat('a', 64),
        'expires_at' => now()->subSecond()->getTimestamp(),
    ]])->post(route('verification.continue'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('the grant expires after 5 minutes', function () {
    $student = approvedStudent();
    $this->get(approvalLink($student));

    $this->travel(6)->minutes();

    $this->post(route('verification.continue'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('a browser signed in to another account is never switched automatically', function () {
    $student = approvedStudent();
    $edp = pathwiseUser('super_admin', ['name' => 'EDP Officer']);
    $link = approvalLink($student);

    $this->actingAs($edp)->get($link)
        ->assertOk()
        ->assertSee('This browser is signed in as EDP Officer.', false)
        ->assertSee('data-test="verification-switch"', false)
        ->assertSee('Stay signed in as EDP Officer')
        ->assertDontSee('data-test="verification-continue"', false);

    $this->assertAuthenticatedAs($edp);

    // Posting without choosing to switch changes nothing.
    $this->post(route('verification.continue'))->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($edp);
});

test('choosing to switch signs the other account out and the student in', function () {
    $student = approvedStudent();
    $edp = pathwiseUser('super_admin');
    $link = approvalLink($student);

    $this->actingAs($edp)->get($link);
    $sessionBefore = session()->getId();

    $this->post(route('verification.continue'), ['switch' => '1'])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($student);
    expect(session()->getId())->not->toBe($sessionBefore);
});

test('the browser already signed in as the student goes straight to the dashboard', function () {
    $student = approvedStudent();

    $this->actingAs($student)->get(approvalLink($student))
        ->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('expired, tampered, pending and rejected links never create a grant', function () {
    $student = approvedStudent();

    $expired = URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $student->id, 'hash' => sha1($student->email)]);
    $this->get($expired)->assertForbidden();
    $this->get(approvalLink($student).'x')->assertForbidden();

    foreach (['pending', 'rejected'] as $status) {
        $other = approvedStudent();
        StudentApplication::where('user_id', $other->id)->update(['status' => $status]);
        $this->get(approvalLink($other->fresh()))->assertForbidden();
    }

    expect(session(VerificationLogin::SESSION_KEY))->toBeNull();
    $this->post(route('verification.continue'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('no sign-in for two-factor accounts, deactivated accounts, teachers, or long after verification', function (string $case) {
    $user = match ($case) {
        'two-factor' => tap(approvedStudent(), fn ($u) => $u->forceFill(['two_factor_secret' => encrypt('secret'), 'two_factor_confirmed_at' => now()])->save()),
        'deactivated' => tap(approvedStudent(), fn ($u) => $u->forceFill(['is_active' => false])->save()),
        'teacher' => pathwiseUser('teacher', ['email_verified_at' => null]),
        'verified long ago' => approvedStudent(['email_verified_at' => now()->subHours(2)]),
    };

    $link = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

    $this->get($link)->assertOk()->assertDontSee('data-test="verification-continue"', false);
    $this->post(route('verification.continue'));

    $this->assertGuest();
})->with(['two-factor', 'deactivated', 'teacher', 'verified long ago']);

test('a grant is checked again when redeemed (e.g. account deactivated meanwhile)', function () {
    $student = approvedStudent();
    $this->get(approvalLink($student));

    $student->forceFill(['is_active' => false])->save();

    $this->post(route('verification.continue'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('the used-link record stores only a hash, never the link or its signature', function () {
    $student = approvedStudent();
    $link = approvalLink($student);
    parse_str((string) parse_url($link, PHP_URL_QUERY), $query);

    $this->get($link);
    $this->post(route('verification.continue'));
    $this->assertAuthenticatedAs($student);

    $rows = DB::table(VerificationLogin::TABLE)->get();

    expect($rows)->toHaveCount(1)
        ->and($rows[0]->user_id)->toBe($student->id)
        ->and($rows[0]->link_hash)->toBe(hash('sha256', $query['signature']))
        ->and(Schema::getColumnListing(VerificationLogin::TABLE))
        ->toEqualCanonicalizing(['id', 'user_id', 'link_hash', 'used_at']);

    // Neither the signature nor the URL appears anywhere in the row.
    $stored = json_encode($rows[0]);
    expect($stored)->not->toContain($query['signature'])
        ->not->toContain('verify');
});

/*
| Click-to-continue (V-1) and the database-backed used-link record (V-2/V-3).
*/

test('opening the link (GET) never signs in and never uses the link up, however often', function () {
    $student = approvedStudent();
    $link = approvalLink($student);

    // An email security scanner, a link preview and the student's own
    // first click are all just GETs.
    foreach (range(1, 3) as $i) {
        session()->flush();
        $this->get($link)->assertOk()->assertSee('data-test="verification-continue"', false);
        $this->assertGuest();
    }

    expect(DB::table(VerificationLogin::TABLE)->count())->toBe(0);

    // The student's own click still works afterwards.
    $this->post(route('verification.continue'))->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($student);
});

test('the page shows which account Continue opens, with the email masked', function () {
    $student = approvedStudent(['name' => 'Juan D. Dela Cruz', 'email' => 'juan.delacruz@gmail.com']);

    $html = $this->get(approvalLink($student))
        ->assertOk()
        ->assertSee('data-test="verification-account"', false)
        ->assertSee('Juan D. Dela Cruz')
        ->assertSee('j•••z@gmail.com')
        ->assertSee('Continue as Juan D. Dela Cruz')
        ->getContent();

    expect($html)->not->toContain('juan.delacruz@gmail.com');
});

test('the account switch page also names the account, and still needs the explicit switch', function () {
    $student = approvedStudent(['name' => 'Maria Santos', 'email' => 'maria.santos@school.edu.ph']);
    $edp = pathwiseUser('super_admin', ['name' => 'EDP Officer']);

    $this->actingAs($edp)->get(approvalLink($student))
        ->assertOk()
        ->assertSee('m•••s@school.edu.ph')
        ->assertSee('data-test="verification-switch"', false)
        ->assertDontSee('data-test="verification-continue"', false)
        ->assertDontSee('maria.santos@school.edu.ph');

    $this->post(route('verification.continue'))->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($edp);
    expect(DB::table(VerificationLogin::TABLE)->count())->toBe(0);
});

test('emails are masked to the first and last letter of the name part', function (string $email, string $masked) {
    expect(VerificationLogin::maskEmail($email))->toBe($masked);
})->with([
    ['juan.delacruz@gmail.com', 'j•••z@gmail.com'],
    ['ana@up.edu.ph', 'a•••a@up.edu.ph'],
    ['jo@x.ph', 'j•••@x.ph'],
    ['j@x.ph', 'j•••@x.ph'],
    ['not-an-email', '•••'],
]);

test('a used link stays used after the cache is cleared', function () {
    $student = approvedStudent();
    $link = approvalLink($student);

    $this->get($link);
    $this->post(route('verification.continue'))->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($student);

    // What `php artisan optimize:clear` / `cache:clear` does to the store.
    Cache::flush();

    auth()->guard('web')->logout();
    session()->flush();

    $this->get($link)->assertOk()->assertDontSee('data-test="verification-continue"', false);
    $this->post(route('verification.continue'))->assertRedirect(route('login'));
    $this->assertGuest();

    // A grant obtained before the clear is refused too.
    parse_str((string) parse_url($link, PHP_URL_QUERY), $query);
    $this->withSession([VerificationLogin::SESSION_KEY => [
        'user_id' => $student->id,
        'link' => hash('sha256', $query['signature']),
        'expires_at' => now()->addMinute()->getTimestamp(),
    ]])->post(route('verification.continue'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('two different valid links for the same account can each be used once', function () {
    // Eight requests in one minute from one IP, with the real rate limits
    // (named, per account / session; see VerificationRateLimitTest).
    $student = approvedStudent();

    // The 72-hour approval link, and a later one with its own expiry
    // (for example a resent link): different signatures.
    $first = approvalLink($student);
    $second = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $student->id,
        'hash' => sha1($student->email),
    ]);
    expect($second)->not->toBe($first);

    $signIn = function (string $link) {
        auth()->guard('web')->logout();
        session()->flush();
        $this->get($link);

        return $this->post(route('verification.continue'));
    };

    $signIn($first)->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($student);

    $signIn($first)->assertRedirect(route('login'));
    $this->assertGuest();

    $signIn($second)->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($student);

    $signIn($second)->assertRedirect(route('login'));
    $this->assertGuest();

    expect(DB::table(VerificationLogin::TABLE)->where('user_id', $student->id)->count())->toBe(2);
});

test('consuming is atomic: only the first attempt wins, enforced by a unique index', function () {
    $student = approvedStudent();
    $hash = hash('sha256', 'some-signature');

    expect(VerificationLogin::consume($student, $hash))->toBeTrue()
        ->and(VerificationLogin::consume($student, $hash))->toBeFalse()
        ->and(VerificationLogin::consume(approvedStudent(), $hash))->toBeFalse()
        ->and(DB::table(VerificationLogin::TABLE)->count())->toBe(1);

    // The database itself refuses a second row for the same link, whatever
    // the code does (two requests racing past every check).
    expect(fn () => DB::table(VerificationLogin::TABLE)->insert([
        'user_id' => $student->id,
        'link_hash' => $hash,
        'used_at' => now(),
    ]))->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

test('a malformed link hash is never consumed', function (string $hash) {
    expect(VerificationLogin::consume(approvedStudent(), $hash))->toBeFalse()
        ->and(DB::table(VerificationLogin::TABLE)->count())->toBe(0);
})->with(['', 'abc', str_repeat('g', 64), str_repeat('A', 64)]);

test('without the used-links table, no sign-in is offered or completed (fails closed)', function () {
    $student = approvedStudent();
    $link = approvalLink($student);

    // A grant taken while the table still existed.
    $this->get($link);
    $grant = session(VerificationLogin::SESSION_KEY);
    expect($grant)->not->toBeNull();

    Log::spy();
    Schema::drop(VerificationLogin::TABLE);

    $this->post(route('verification.continue'))->assertRedirect(route('login'));
    $this->assertGuest();

    session()->flush();
    $this->get($link)->assertOk()->assertDontSee('data-test="verification-continue"', false);
    $this->assertGuest();

    Log::shouldHaveReceived('warning')->atLeast()->once();
});

test('both browsers reach the dashboard: the registration browser by polling, the link browser by the grant', function () {
    $student = approvedStudent();
    $link = approvalLink($student);

    // Browser B (another browser, nobody signed in) opens the link and continues.
    $this->get($link);
    $this->post(route('verification.continue'))->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($student);

    // Browser A (where the student registered) polls its own session.
    auth()->guard('web')->logout();
    $this->actingAs($student->fresh())->getJson(route('student-application.check'))
        ->assertJson(['ready' => true, 'dashboard_url' => route('dashboard')]);
});
