<?php

use App\Models\StudentApplication;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/*
| Teacher registration requires one Teacher ID (faculty or employee ID),
| handled like a student's Study Load: same file limits and checks, private
| disk with random names, served only to the Super Admin (EDP). It reuses the
| Study Load file columns of the teacher's application row (no migration).
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    $this->withoutVite();
    fakeFileDisks();
    Notification::fake();
});

function teacherIdRegistration(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.teacher@example.org',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'teacher',
        'teacher_id' => teacherIdUpload(),
    ], $overrides);
}

function registeredTeacherReview(): StudentApplication
{
    test()->post(route('register.store'), teacherIdRegistration())->assertSessionHasNoErrors();
    auth()->guard('web')->logout();

    return StudentApplication::where('applicant_role', StudentApplication::TEACHER)->sole();
}

/*
|--------------------------------------------------------------------------
| Registration
|--------------------------------------------------------------------------
*/

test('teacher registration fails without a valid Teacher ID and nothing is saved', function (array $overrides, string $message) {
    $this->post(route('register.store'), teacherIdRegistration($overrides))
        ->assertSessionHasErrors(['teacher_id' => $message]);

    $this->assertGuest();
    expect(User::count())->toBe(0)
        ->and(StudentApplication::count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([])
        ->and(Storage::disk('public')->allFiles())->toBe([]);
})->with([
    'missing' => [['teacher_id' => null], 'Please upload your Teacher ID (faculty or employee ID): PDF, JPG or PNG.'],
    'Word file' => [['teacher_id' => UploadedFile::fake()->create('id.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')], 'The Teacher ID must be a PDF, JPG or PNG file.'],
    'renamed .jpg that is not an image' => [['teacher_id' => UploadedFile::fake()->create('id.jpg', 100, 'application/x-msdownload')], 'The Teacher ID must be a PDF, JPG or PNG file.'],
    'GIF image' => [['teacher_id' => UploadedFile::fake()->create('id.gif', 100, 'image/gif')], 'The Teacher ID must be a PDF, JPG or PNG file.'],
    'over 10 MB' => [['teacher_id' => UploadedFile::fake()->create('id.pdf', 10241, 'application/pdf')], StudentApplication::TOO_LARGE_MESSAGE],
]);

test('a Teacher ID of exactly 10 MB, a PDF or a PNG is accepted', function (UploadedFile $file) {
    $this->post(route('register.store'), teacherIdRegistration(['teacher_id' => $file]))
        ->assertSessionHasNoErrors();

    expect(StudentApplication::sole()->hasStudyLoad())->toBeTrue();
})->with([
    '10 MB PDF' => [fn () => UploadedFile::fake()->create('id.pdf', 10240, 'application/pdf')],
    'PNG' => [fn () => UploadedFile::fake()->image('id.png')],
]);

test('the Teacher ID is stored privately with a random name in the Study Load columns', function () {
    $review = registeredTeacherReview();
    $teacher = $review->user;

    expect($review->isTeacherApplication())->toBeTrue()
        ->and($review->review_origin)->toBe('registration')
        ->and($review->status)->toBe(StudentApplication::PENDING)
        ->and($review->study_load_path)->toStartWith('teacher-ids/'.$teacher->id.'/')
        ->and($review->study_load_path)->not->toContain('faculty-id')
        ->and($review->study_load_original_name)->toBe('faculty-id.jpg')
        ->and($review->study_load_mime)->toBe('image/jpeg')
        ->and($review->study_load_size)->toBeGreaterThan(0);

    Storage::disk('local')->assertExists($review->study_load_path);
    expect(Storage::disk('public')->allFiles())->toBe([]);

    // Never reachable through a public /storage URL.
    $this->get('/storage/'.$review->study_load_path)->assertNotFound();
});

test('student registration is unchanged: no Teacher ID needed, one sent anyway is ignored', function () {
    $this->post(route('register.store'), [
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan.student@example.org',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'student',
        'study_load' => UploadedFile::fake()->create('load.pdf', 200, 'application/pdf'),
        'teacher_id' => teacherIdUpload('sneaky.jpg'),
    ])->assertSessionHasNoErrors();

    $application = StudentApplication::sole();
    $files = Storage::disk('local')->allFiles();

    expect($application->isTeacherApplication())->toBeFalse()
        ->and($application->study_load_path)->toStartWith('study-loads/')
        ->and($files)->toHaveCount(1)
        ->and($files[0])->toStartWith('study-loads/');
});

test('the registration form shows the Teacher ID field and its privacy note only for Teacher', function () {
    $html = $this->get(route('register'))->getContent();

    preg_match('/<input[^>]*name="teacher_id"[^>]*>/s', $html, $input);

    expect($html)->toMatch('/data-teacher-id-section\s+hidden/')
        ->and($html)->toContain('data-teacher-privacy-notice')
        ->and($html)->toContain('used only to verify your faculty status, is accessed only by authorized EDP (Super Admin) personnel, and is stored securely')
        ->and($input[0])->toContain('disabled')
        ->and($input[0])->not->toMatch('/\srequired[\s>]/')
        // No extra checkbox was added.
        ->and($html)->not->toContain('type="checkbox"');

    $teacherHtml = $this->withSession(['_old_input' => ['role' => 'teacher']])
        ->get(route('register'))
        ->getContent();

    preg_match('/<input[^>]*name="teacher_id"[^>]*>/s', $teacherHtml, $teacherInput);

    expect($teacherHtml)->not->toMatch('/data-teacher-id-section\s+hidden/')
        ->and($teacherInput[0])->toContain('required')
        ->and($teacherInput[0])->not->toContain('disabled');
});

/*
|--------------------------------------------------------------------------
| Access: Super Admin only
|--------------------------------------------------------------------------
*/

test('only the Super Admin can open a Teacher ID; everyone else gets 403', function () {
    $review = registeredTeacherReview();
    $url = route('super-admin.student-applications.study-load', $review);

    foreach ([pathwiseApprovedTeacher(), pathwiseUser('student'), pathwiseUser('admin')] as $user) {
        $this->actingAs($user)->get($url)->assertForbidden();
    }

    // The applicant is stopped by the EDP review before any file route.
    $this->actingAs($review->user)->get($url)->assertRedirect(route('student-application.status'));

    auth()->guard('web')->logout();
    $this->get($url)->assertRedirect(route('login'));

    $this->actingAs(pathwiseUser('super_admin'))->get($url)
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Disposition', 'inline; filename=teacher-id-'.$review->user_id.'-submission-'.$review->submission_no.'.jpg');
});

/*
|--------------------------------------------------------------------------
| EDP review page
|--------------------------------------------------------------------------
*/

test('the EDP detail page previews the Teacher ID like a Study Load', function () {
    $review = registeredTeacherReview();
    $url = route('super-admin.student-applications.study-load', $review);

    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('super-admin.student-applications.show', $review))
        ->assertOk()
        ->assertSee('Teacher ID · Submission #'.$review->submission_no)
        ->assertSee('data-test="teacher-id-preview"', false)
        ->assertSee('<img src="'.$url.'"', false)
        ->assertSee('faculty-id.jpg')
        ->assertSee('Open Teacher ID')
        ->assertDontSee('No Teacher ID uploaded')
        ->assertDontSee('/storage/teacher-ids', false);
});

test('a teacher review created before this requirement still shows and can be approved', function () {
    $teacher = pathwiseUser('teacher', ['email_verified_at' => null]);
    $review = StudentApplication::submitForTeacher($teacher, 'registration');
    $edp = pathwiseUser('super_admin');

    $this->actingAs($edp)
        ->get(route('super-admin.student-applications.show', $review))
        ->assertOk()
        ->assertSee('No Teacher ID uploaded (registered before this requirement)')
        ->assertSee('data-test="approve-button"', false);

    $this->actingAs($edp)
        ->post(route('super-admin.student-applications.approve', $review), ['faculty_confirmed' => '1'])
        ->assertSessionHas('success');

    expect($review->fresh()->status)->toBe(StudentApplication::APPROVED);
});

test('a review started by the EDP has no Teacher ID and says why', function () {
    $edp = pathwiseUser('super_admin');

    $this->actingAs($edp)->post(route('users.store'), [
        'name' => 'Faculty Member',
        'email' => 'faculty@uclm.edu.ph',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'role' => 'teacher',
    ])->assertSessionHasNoErrors();

    $review = StudentApplication::where('applicant_role', StudentApplication::TEACHER)->sole();

    expect($review->review_origin)->toBe('edp_created')
        ->and($review->hasStudyLoad())->toBeFalse();

    $this->actingAs($edp)
        ->get(route('super-admin.student-applications.show', $review))
        ->assertOk()
        ->assertSee('this review was started by the EDP office');
});

/*
|--------------------------------------------------------------------------
| Rejection and resubmission
|--------------------------------------------------------------------------
*/

function rejectTeacherReview(StudentApplication $review): void
{
    test()->actingAs(pathwiseUser('super_admin'))
        ->post(route('super-admin.student-applications.reject', $review), [
            'rejection_reason' => 'The photo of the ID is too blurry to read.',
        ])->assertSessionHas('success');
}

test('a rejected teacher uploads a new Teacher ID and the old file is removed', function () {
    $review = registeredTeacherReview();
    $teacher = $review->user;
    $oldPath = $review->study_load_path;
    rejectTeacherReview($review);

    $this->actingAs($teacher)
        ->get(route('student-application.status'))
        ->assertOk()
        ->assertSee('The photo of the ID is too blurry to read.')
        ->assertSee('data-test="teacher-id-resubmit-form"', false)
        ->assertSee('name="teacher_id"', false)
        ->assertDontSee('Corrected Study Load');

    $this->actingAs($teacher)
        ->post(route('student-application.resubmit'), ['teacher_id' => teacherIdUpload('clear-id.png')])
        ->assertRedirect(route('student-application.status'))
        ->assertSessionHas('success', 'Your new Teacher ID was submitted. The EDP office will review your teacher registration again.');

    $latest = $teacher->fresh()->currentTeacherApplication();

    expect($latest->review_origin)->toBe('resubmission')
        ->and($latest->status)->toBe(StudentApplication::PENDING)
        ->and($latest->study_load_original_name)->toBe('clear-id.png')
        ->and($latest->study_load_path)->toStartWith('teacher-ids/'.$teacher->id.'/');

    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($latest->study_load_path);

    // The rejected review keeps its decision and reason; its file is gone.
    $old = $review->fresh();
    expect($old->status)->toBe(StudentApplication::REJECTED)
        ->and($old->rejection_reason)->toBe('The photo of the ID is too blurry to read.')
        ->and($old->hasStudyLoad())->toBeFalse();

    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('super-admin.student-applications.show', $old))
        ->assertOk()
        ->assertSee('Teacher ID replaced by a newer upload; this older file was removed.');
});

test('a rejected teacher must upload a valid Teacher ID to resubmit', function (array $payload) {
    $review = registeredTeacherReview();
    rejectTeacherReview($review);

    $this->actingAs($review->user)
        ->post(route('student-application.resubmit'), $payload)
        ->assertSessionHasErrors('teacher_id');

    expect(StudentApplication::count())->toBe(1)
        ->and(Storage::disk('local')->allFiles())->toHaveCount(1);
})->with([
    'no file' => [[]],
    'wrong type' => [fn () => ['teacher_id' => UploadedFile::fake()->create('id.txt', 5, 'text/plain')]],
    'too large' => [fn () => ['teacher_id' => UploadedFile::fake()->create('id.pdf', 10241, 'application/pdf')]],
]);

test('a pending teacher cannot upload another Teacher ID', function () {
    $review = registeredTeacherReview();

    $this->actingAs($review->user)
        ->post(route('student-application.resubmit'), ['teacher_id' => teacherIdUpload()])
        ->assertSessionHas('error');

    expect(StudentApplication::count())->toBe(1)
        ->and(Storage::disk('local')->allFiles())->toHaveCount(1);
});

/*
|--------------------------------------------------------------------------
| Unchanged rules
|--------------------------------------------------------------------------
*/

test('approved teachers keep their access and are never asked for a Teacher ID', function () {
    $teacher = pathwiseApprovedTeacher();

    $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertOk();
    $this->actingAs($teacher)->get(route('student-application.status'))->assertDontSee('name="teacher_id"', false);
});

test('Mark verified still refuses teachers', function () {
    $review = registeredTeacherReview();

    $this->actingAs(pathwiseUser('super_admin'))
        ->patch(route('users.verify-email', $review->user))
        ->assertSessionHas('error');

    expect($review->user->fresh()->hasVerifiedEmail())->toBeFalse();
});
