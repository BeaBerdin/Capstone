<?php

use App\Models\StudentApplication;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
| The application status page follows the application by itself: it polls
| /application-status/check for the signed-in account only, reloads when the
| EDP decides, and goes to the dashboard once the account is approved and
| its email is verified (verification usually happens in another browser).
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    fakeFileDisks();
});

function pollingApplicant(string $status = 'pending', bool $verified = false): User
{
    $user = pathwiseUser('student', ['email_verified_at' => $verified ? now() : null]);
    Storage::disk('local')->put('study-loads/'.$user->id.'/a.pdf', '%PDF-1.4');

    StudentApplication::create([
        'user_id' => $user->id,
        'submission_no' => 1,
        'study_load_path' => 'study-loads/'.$user->id.'/a.pdf',
        'study_load_original_name' => 'a.pdf',
        'study_load_mime' => 'application/pdf',
        'study_load_size' => 8,
        'status' => $status,
        'submitted_at' => now(),
    ]);

    return $user->fresh();
}

test('the status page starts polling the check endpoint for its own account', function () {
    $student = pollingApplicant();

    $html = $this->actingAs($student)->get(route('student-application.status'))->assertOk()->getContent();

    expect($html)
        ->toContain('data-application-poll')
        ->toContain('data-check-url="'.route('student-application.check').'"')
        ->toContain('data-user-id="'.$student->id.'"')
        ->toContain('data-status="pending"')
        ->toContain('data-verified="0"')
        ->toContain('window.location.assign(data.dashboard_url)')
        ->toContain('if (Number(data.user_id) !== userId)')
        ->toContain("cache: 'no-store'");
});

test('the check endpoint reports pending, then approved, then ready after verification', function () {
    $student = pollingApplicant();

    $this->actingAs($student)->getJson(route('student-application.check'))
        ->assertOk()
        ->assertExactJson([
            'user_id' => $student->id,
            'status' => 'pending',
            'verified' => false,
            'ready' => false,
            'dashboard_url' => route('dashboard'),
        ]);

    // The EDP approves (another request).
    StudentApplication::query()->update(['status' => 'approved']);
    $this->actingAs($student)->getJson(route('student-application.check'))
        ->assertJson(['status' => 'approved', 'verified' => false, 'ready' => false]);

    // The email is verified in another browser.
    User::whereKey($student->id)->update(['email_verified_at' => now()]);
    $this->actingAs($student)->getJson(route('student-application.check'))
        ->assertJson(['status' => 'approved', 'verified' => true, 'ready' => true]);

    // And the dashboard really lets the student in.
    $this->actingAs($student->fresh())->get(route('dashboard'))->assertRedirect(route('student.dashboard'));
});

test('a rejected or pending student is never ready, even if verified somehow', function (string $status) {
    $student = pollingApplicant($status, verified: true);

    $this->actingAs($student)->getJson(route('student-application.check'))
        ->assertJson(['status' => $status, 'verified' => true, 'ready' => false]);

    $this->actingAs($student)->get(route('dashboard'))->assertRedirect(route('student-application.status'));
})->with(['pending', 'rejected']);

test('the check endpoint only describes the signed-in account and is never cached', function () {
    $student = pollingApplicant();
    $other = pollingApplicant('approved', verified: true);

    $response = $this->actingAs($student)
        ->getJson(route('student-application.check', ['user_id' => $other->id, 'id' => $other->id]))
        ->assertOk()
        ->assertJson(['user_id' => $student->id, 'status' => 'pending', 'ready' => false]);

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

test('a guest gets no status from the check endpoint, only the login redirect', function () {
    pollingApplicant('approved', verified: true);

    $response = $this->getJson(route('student-application.check'))->assertRedirect(route('login'));

    expect($response->getContent())->not->toContain('"status"')->not->toContain('"ready"');

    // The page script stops polling on a redirected or non-JSON answer.
    $student = pollingApplicant();
    expect($this->actingAs($student)->get(route('student-application.status'))->getContent())
        ->toContain('response.redirected')
        ->toContain("type.indexOf('application/json') === -1");
});

test('the check endpoint is rate limited', function () {
    $student = pollingApplicant();

    foreach (range(1, 60) as $i) {
        $this->actingAs($student)->getJson(route('student-application.check'))->assertOk();
    }

    $this->actingAs($student)->getJson(route('student-application.check'))->assertStatus(429);
});

test('opening the link in a browser signed in to another account does not move that account', function () {
    $student = pollingApplicant('approved');
    $edp = pathwiseUser('super_admin');

    $link = Illuminate\Support\Facades\URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $student->id,
        'hash' => sha1($student->email),
    ]);

    $this->actingAs($edp)->get($link)
        ->assertOk()
        ->assertSee('To open', false)
        ->assertSee('data-test="verification-switch"', false)
        ->assertSee('data-test="verification-stay"', false);

    $this->assertAuthenticatedAs($edp);
    expect($student->fresh()->hasVerifiedEmail())->toBeTrue();

    // The other account's own check never reports the student's status.
    $this->actingAs($edp)->getJson(route('student-application.check'))
        ->assertJson(['user_id' => $edp->id]);
});
