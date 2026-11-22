<?php

use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());

    // Registration requires the student/teacher roles to exist.
    $this->seed(\Database\Seeders\RoleSeeder::class);

    // Study Loads go to a fake private disk, never to real storage.
    fakeFileDisks();
});

/** A valid Study Load upload for student registration tests. */
function studyLoadPdf(string $name = 'study-load.pdf', int $kilobytes = 200): Illuminate\Http\UploadedFile
{
    return Illuminate\Http\UploadedFile::fake()->create($name, $kilobytes, 'application/pdf');
}

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'student',
        'study_load' => studyLoadPdf(),
    ]);

    // A new student waits for the EDP on the application status page.
    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('student-application.status'));

    $this->assertAuthenticated();
});

test('users cannot self-register as an administrator', function () {
    $response = $this->post(route('register.store'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'admin',
    ]);

    $response->assertSessionHasErrors('role');

    $this->assertGuest();
});

test('a teacher can register but needs EDP approval before email verification', function () {
    Illuminate\Support\Facades\Notification::fake();

    $this->post(route('register.store'), [
        'first_name' => 'Tina',
        'last_name' => 'Teacher',
        'email' => 'tina@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'teacher',
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('student-application.status'));

    $user = App\Models\User::where('email', 'tina@example.com')->sole();
    $review = $user->teacherApplications()->sole();

    expect($user->hasRole('teacher'))->toBeTrue()
        ->and($user->hasRole('student'))->toBeFalse()
        ->and($user->hasRole('admin'))->toBeFalse()
        ->and($user->hasVerifiedEmail())->toBeFalse()
        ->and($review->status)->toBe(App\Models\StudentApplication::PENDING)
        ->and($review->review_origin)->toBe('registration')
        ->and($review->review_email)->toBe($user->email);

    Illuminate\Support\Facades\Notification::assertNotSentTo($user, Illuminate\Auth\Notifications\VerifyEmail::class);

    // Teacher pages wait for both current EDP approval and email verification.
    $this->get(route('teacher.dashboard'))->assertRedirect(route('student-application.status'));
});

test('registration requires choosing student or teacher', function () {
    $this->post(route('register.store'), [
        'first_name' => 'No',
        'last_name' => 'Role',
        'email' => 'norole@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('role');

    $this->assertGuest();
});

/*
| Registration asks for First Name, Middle Initial (optional) and Last Name,
| saved together in the existing name column as "First M. Last".
*/

function registerWith(array $fields): Illuminate\Testing\TestResponse
{
    return test()->post(route('register.store'), array_merge([
        'email' => 'juan@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'student',
        'study_load' => studyLoadPdf(),
    ], $fields));
}

test('the registration form asks for first name, middle initial and last name', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('First Name')
        ->assertSee('Middle Initial')
        ->assertSee('(optional)')
        ->assertSee('Last Name')
        ->assertSee('name="first_name"', false)
        ->assertSee('name="middle_initial"', false)
        ->assertSee('name="last_name"', false)
        ->assertDontSee('name="name"', false);
});

test('the three name fields are saved as "First M. Last"', function () {
    registerWith(['first_name' => '  Juan ', 'middle_initial' => 'd', 'last_name' => 'Dela   Cruz'])
        ->assertSessionHasNoErrors();

    expect(App\Models\User::where('email', 'juan@example.com')->value('name'))->toBe('Juan D. Dela Cruz');
});

test('the middle initial is optional and may include a period', function () {
    registerWith(['first_name' => 'Maria', 'last_name' => 'Santos'])->assertSessionHasNoErrors();
    expect(App\Models\User::where('email', 'juan@example.com')->value('name'))->toBe('Maria Santos');

    // Registering logs the new user in; start the next one as a guest.
    auth()->guard('web')->logout();

    registerWith(['email' => 'ana@example.com', 'first_name' => 'Ma. Ana', 'middle_initial' => 'R.', 'last_name' => "O'Neil"])
        ->assertSessionHasNoErrors();
    expect(App\Models\User::where('email', 'ana@example.com')->value('name'))->toBe("Ma. Ana R. O'Neil");
});

test('a curly apostrophe from a phone keyboard is accepted and saved as a straight one', function () {
    registerWith(['first_name' => "D\u{2019}Angelo", 'last_name' => "O\u{2019}Neil"])
        ->assertSessionHasNoErrors();

    expect(App\Models\User::where('email', 'juan@example.com')->value('name'))->toBe("D'Angelo O'Neil");
});

test('first and last name are required and the middle initial is one letter', function () {
    registerWith(['first_name' => '', 'last_name' => ''])
        ->assertSessionHasErrors(['first_name', 'last_name']);

    registerWith(['first_name' => 'Juan', 'middle_initial' => 'Dc', 'last_name' => 'Cruz'])
        ->assertSessionHasErrors('middle_initial');

    registerWith(['first_name' => 'Juan1', 'last_name' => 'Cruz'])
        ->assertSessionHasErrors('first_name');

    $this->assertGuest();
    expect(App\Models\User::where('email', 'juan@example.com')->exists())->toBeFalse();
});
