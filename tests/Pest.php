<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

// RefreshDatabase gives every test a fresh, empty in-memory SQLite
// database. tests/TestCase.php refuses to run if any other database is
// configured, so your real MySQL data can never be touched.
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions (PathWise test helpers)
|--------------------------------------------------------------------------
|
| Helpers used by the tests in tests/Feature/Pathwise.
|
*/

/**
 * Create a user that has one PathWise role
 * (super_admin, admin, teacher, or student).
 */
function pathwiseUser(string $role, array $attributes = []): App\Models\User
{
    $roleModel = App\Models\Role::firstOrCreate(['name' => $role]);

    $user = App\Models\User::factory()->create($attributes);

    $user->roles()->sync([$roleModel->id]);

    return $user;
}

/** Teacher fixtures in unrelated regression tests are fully approved users. */
function pathwiseApprovedTeacher(array $attributes = []): App\Models\User
{
    $teacher = pathwiseUser('teacher', array_merge(['email_verified_at' => now()], $attributes));
    $edp = App\Models\User::query()
        ->whereHas('roles', fn ($query) => $query->where('name', 'super_admin'))
        ->first() ?? pathwiseUser('super_admin');
    $review = App\Models\StudentApplication::submitForTeacher($teacher, 'test_fixture');
    $review->forceFill([
        'status' => App\Models\StudentApplication::APPROVED,
        'reviewed_by' => $edp->id,
        'reviewed_at' => now(),
    ])->save();

    return $teacher;
}

/** A fixture for role-boundary tests where teachers must reach the role check. */
function pathwiseRoleBoundaryUser(string $role, array $attributes = []): App\Models\User
{
    return $role === 'teacher'
        ? pathwiseApprovedTeacher($attributes)
        : pathwiseUser($role, $attributes);
}

/**
 * Create a course owned by the given teacher.
 */
function pathwiseCourse(App\Models\User $teacher, array $attributes = []): App\Models\Course
{
    $category = App\Models\CourseCategory::firstOrCreate(
        ['name' => 'Information Technology'],
        ['is_active' => true]
    );

    return App\Models\Course::create(array_merge([
        'teacher_id' => $teacher->id,
        'category_id' => $category->id,
        'title' => 'Intro to Web Development '.uniqid(),
        'description' => 'A beginner course.',
        'difficulty_level' => 'beginner',
        'price' => 0,
        'status' => 'draft',
        'estimated_hours' => 5,
        'certificate_available' => true,
    ], $attributes));
}

/**
 * Fake the private disk (receipts, submissions, lesson files) and the
 * public disk (thumbnails, photos, and private files not moved yet), so
 * tests never touch the real storage folders.
 */
function fakeFileDisks(): void
{
    Illuminate\Support\Facades\Storage::fake('local');
    Illuminate\Support\Facades\Storage::fake('public');
}

/**
 * A Faculty Verification Document upload for teacher registration.
 */
function teacherIdUpload(string $name = 'faculty-id.jpg'): Illuminate\Http\UploadedFile
{
    return Illuminate\Http\UploadedFile::fake()->image($name, 600, 380);
}

/**
 * Give a teacher review a Faculty Verification Document (fake private file),
 * as the EDP cannot approve a teacher review without one.
 */
function withFacultyDocument(App\Models\StudentApplication $review): App\Models\StudentApplication
{
    $path = 'teacher-ids/'.$review->user_id.'/document-'.$review->id.'.pdf';
    Illuminate\Support\Facades\Storage::disk('local')->put($path, '%PDF-1.4 faculty verification document');

    $review->forceFill([
        'study_load_path' => $path,
        'study_load_original_name' => 'faculty-document.pdf',
        'study_load_mime' => 'application/pdf',
        'study_load_size' => 38,
    ])->save();

    return $review->fresh();
}
