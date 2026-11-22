<?php

use App\Models\StudentApplication;
use App\Notifications\StudentApplicationApproved;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Notification;

/*
| The application status page follows the EDP's decision by itself: it polls
| a lightweight, rate-limited JSON endpoint (signed-in account only) about
| every 10 seconds; approved + verified -> dashboard; approved but not
| verified -> "Approved — please verify your email". The verification link
| opened in the same browser lands on the dashboard.
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    $this->withoutVite();
    fakeFileDisks();
    Notification::fake();
});

function autoRedirectTeacherReview(): array
{
    $teacher = pathwiseUser('teacher', ['email_verified_at' => null]);
    $review = withFacultyDocument(StudentApplication::submitForTeacher($teacher, 'registration'));

    return [$teacher, $review];
}

test('the status page polls the check endpoint about every 10 seconds', function () {
    [$teacher] = autoRedirectTeacherReview();

    $this->actingAs($teacher)
        ->get(route('student-application.status'))
        ->assertOk()
        ->assertSee('data-application-poll', false)
        ->assertSee('data-check-url="'.route('student-application.check').'"', false)
        ->assertSee('var interval = 10000;', false)
        ->assertSee('window.location.assign(data.dashboard_url)', false);
});

test('pending: the check endpoint reports pending and not ready', function () {
    [$teacher] = autoRedirectTeacherReview();

    $this->actingAs($teacher)
        ->getJson(route('student-application.check'))
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
        ->assertJson([
            'user_id' => $teacher->id,
            'status' => StudentApplication::PENDING,
            'verified' => false,
            'ready' => false,
        ]);
});

test('a teacher who is approved but not verified sees "Approved — please verify your email"; the verification link then lands on the dashboard', function () {
    [$teacher, $review] = autoRedirectTeacherReview();

    $this->actingAs(pathwiseUser('super_admin'))
        ->post(route('super-admin.student-applications.approve', $review), ['faculty_confirmed' => '1'])
        ->assertSessionHas('success');

    $this->actingAs($teacher->fresh())
        ->getJson(route('student-application.check'))
        ->assertJson(['status' => StudentApplication::APPROVED, 'verified' => false, 'ready' => false]);

    $this->get(route('student-application.status'))
        ->assertOk()
        ->assertSee('Approved — please verify your email')
        ->assertSee('data-test="resend-verification"', false);

    // The acceptance email's link, opened in the same (signed-in) browser.
    $link = null;
    Notification::assertSentTo($teacher, StudentApplicationApproved::class, function ($notification) use ($teacher, &$link) {
        $link = $notification->toMail($teacher)->viewData['buttonUrl'];

        return true;
    });

    $this->get($link)->assertRedirect(route('dashboard').'?verified=1');
    expect($teacher->fresh()->hasVerifiedEmail())->toBeTrue();

    // Same browser session, now verified.
    $this->actingAs($teacher->fresh());
    $this->get(route('dashboard'))->assertRedirect(route('teacher.dashboard'));
    $this->get(route('teacher.dashboard'))->assertOk();

    // The original tab's next poll sends it to the dashboard.
    $this->getJson(route('student-application.check'))
        ->assertJson(['status' => StudentApplication::APPROVED, 'verified' => true, 'ready' => true, 'dashboard_url' => route('dashboard')]);
});

test('a student who is approved and verified is sent to the dashboard by the next poll', function () {
    $student = pathwiseUser('student', ['email_verified_at' => null]);
    $application = StudentApplication::create([
        'user_id' => $student->id,
        'submission_no' => 1,
        'study_load_path' => 'study-loads/'.$student->id.'/load.pdf',
        'study_load_original_name' => 'load.pdf',
        'study_load_mime' => 'application/pdf',
        'study_load_size' => 10,
        'status' => StudentApplication::PENDING,
        'submitted_at' => now(),
    ]);
    Illuminate\Support\Facades\Storage::disk('local')->put($application->study_load_path, '%PDF-1.4');

    $this->actingAs(pathwiseUser('super_admin'))
        ->post(route('super-admin.student-applications.approve', $application))
        ->assertSessionHas('success');

    $this->actingAs($student->fresh())
        ->get(route('student-application.status'))
        ->assertSee('Approved — please verify your email');

    $link = null;
    Notification::assertSentTo($student, StudentApplicationApproved::class, function ($notification) use ($student, &$link) {
        $link = $notification->toMail($student)->viewData['buttonUrl'];

        return true;
    });

    $this->get($link)->assertRedirect(route('dashboard').'?verified=1');

    // Same browser session, now verified.
    $this->actingAs($student->fresh());
    $this->get(route('dashboard'))->assertRedirect(route('student.dashboard'));

    $this->getJson(route('student-application.check'))
        ->assertJson(['verified' => true, 'ready' => true]);

    $this->get(route('student-application.status'))->assertDontSee('please verify your email');
});

test('the check endpoint only describes the signed-in account', function () {
    [$teacher] = autoRedirectTeacherReview();
    [$other] = autoRedirectTeacherReview();

    // Any id in the query string is ignored.
    $this->actingAs($teacher)
        ->getJson(route('student-application.check', ['user_id' => $other->id]))
        ->assertJson(['user_id' => $teacher->id]);

    // Signed out: no status at all (sent to the login page).
    auth()->guard('web')->logout();
    $response = $this->getJson(route('student-application.check'));
    expect($response->status())->toBeIn([302, 401])
        ->and($response->getContent())->not->toContain('"user_id"');
});

test('the check endpoint is rate-limited per account', function () {
    [$teacher] = autoRedirectTeacherReview();
    $limit = AppServiceProvider::VERIFICATION_LIMITS['check_per_user_per_minute'];

    for ($i = 1; $i <= $limit; $i++) {
        $this->actingAs($teacher)->getJson(route('student-application.check'))->assertOk();
    }

    $this->actingAs($teacher)->getJson(route('student-application.check'))->assertStatus(429);
});
