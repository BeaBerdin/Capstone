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
