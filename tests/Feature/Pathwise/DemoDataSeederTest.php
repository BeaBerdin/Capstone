<?php

use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\Submission;
use App\Models\StudentApplication;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/*
| DemoDataSeeder: sample data for demonstrations, local computers only.
| Password from DEMO_SEED_PASSWORD; existing data is never changed.
*/

function runDemoSeeder(string $environment = 'local', ?string $password = 'demo-secret-123'): void
{
    app()['env'] = $environment;
    config(['auth.seed_demo_password' => $password]);

    // Called directly: in production `db:seed` would first ask for confirmation.
    app(DemoDataSeeder::class)->setContainer(app())->__invoke();
}

/**
 * Row counts of every table the seeder writes to.
 */
function demoTableCounts(): array
{
    return collect([
        'users', 'user_roles', 'course_categories', 'courses', 'lessons', 'quizzes',
        'quiz_questions', 'quiz_results', 'assignments', 'submissions', 'enrollments',
        'lesson_progress', 'certificates', 'transactions', 'notifications', 'course_reviews',
    ])->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()])->all();
}

beforeEach(function () {
    fakeFileDisks();
    Notification::fake();
});

test('it creates the demo accounts with the password from the setting', function () {
    runDemoSeeder();

    $teachers = User::where('email', 'like', 'demo.teacher%@example.com')->get();
    $students = User::where('email', 'like', 'demo.student%@example.com')->get();

    expect($teachers)->toHaveCount(3)
        ->and($students)->toHaveCount(15)
        ->and($teachers->every(fn ($user) => $user->hasRole('teacher')))->toBeTrue()
        ->and($students->every(fn ($user) => $user->hasRole('student')))->toBeTrue()
        ->and($teachers->concat($students)->every(fn ($user) => str_ends_with($user->name, '(Demo)')
            && $user->hasVerifiedEmail()
            && Hash::check('demo-secret-123', $user->password)))->toBeTrue();
});

test('it creates courses, lessons, quizzes, assignments and enrollments', function () {
    runDemoSeeder();

    $courses = Course::where('title', 'like', '%(Demo)')->get();

    expect($courses)->toHaveCount(6)
        ->and($courses->where('status', 'published'))->toHaveCount(4)
        ->and($courses->where('status', 'pending'))->toHaveCount(1)
        ->and($courses->where('status', 'rejected'))->toHaveCount(1)
        ->and($courses->firstWhere('status', 'rejected')->review_remarks)->not->toBeEmpty()
        ->and($courses->pluck('category_id')->unique()->count())->toBe(3)
        ->and(Lesson::whereIn('course_id', $courses->pluck('id'))->count())->toBe(16)
        ->and(Quiz::count())->toBe(4)
        ->and(Quiz::first()->questions()->count())->toBe(5)
        ->and(QuizResult::where('remarks', 'passed')->exists())->toBeTrue()
        ->and(QuizResult::where('remarks', 'failed')->exists())->toBeTrue()
        ->and(Assignment::count())->toBe(4);

    foreach (['active', 'completed', 'cancelled'] as $status) {
        expect(Enrollment::where('status', $status)->exists())->toBeTrue();
    }

    // Every completed enrollment has a certificate in the normal number format.
    $completed = Enrollment::where('status', 'completed')->get();
    expect(Certificate::count())->toBe($completed->count())
        ->and(Certificate::pluck('certificate_number')->every(fn ($number) => preg_match('/^PATH-\d{4}-\d{5}$/', $number)))->toBeTrue();
});

test('one finished AI evaluation is waiting for the teacher', function () {
    runDemoSeeder();

    $awaiting = Submission::where('status', 'submitted')
        ->where('ai_evaluation_status', 'completed')
        ->get();

    expect($awaiting)->toHaveCount(1)
        ->and($awaiting->first()->ai_score)->not->toBeNull()
        ->and(Submission::where('status', 'graded')->exists())->toBeTrue();

    $teacher = $awaiting->first()->assignment->course->teacher;
    $edp = pathwiseUser('super_admin');
    $review = StudentApplication::submitForTeacher($teacher, 'test_fixture');
    $review->forceFill([
        'status' => StudentApplication::APPROVED,
        'reviewed_by' => $edp->id,
        'reviewed_at' => now(),
    ])->save();
    $teacher->forceFill(['email_verified_at' => now()])->save();

    $this->actingAs($teacher)
        ->get(route('teacher.dashboard'))
        ->assertOk()
        ->assertSee('Grade Calculator');
});

test('it creates PayMongo payments and a pending manual receipt', function () {
    runDemoSeeder();

    $payMongo = Transaction::where('payment_method', 'PayMongo')->where('status', 'approved')->get();

    expect($payMongo->pluck('paymongo_method')->unique()->sort()->values()->all())
        ->toBe(['card', 'gcash', 'paymaya']);

    // Every PayMongo payment belongs to an enrollment in a paid course.
    foreach ($payMongo as $transaction) {
        expect(Enrollment::where('student_id', $transaction->student_id)
            ->where('course_id', $transaction->course_id)->exists())->toBeTrue()
            ->and((float) $transaction->amount)->toBeGreaterThan(0);
    }

    $pending = Transaction::where('status', 'pending')->sole();

    expect($pending->payment_method)->toBe('Bank Transfer')
        ->and($pending->payment_proof)->toBe(DemoDataSeeder::RECEIPT_PATH);
    expect(substr(Storage::disk('local')->get(DemoDataSeeder::RECEIPT_PATH), 0, 4))->toBe("\x89PNG");

    // The Super Admin can open the receipt through the private file route.
    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('files.receipt', $pending))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');
});

test('running it twice adds nothing the second time', function () {
    runDemoSeeder();
    $afterFirst = demoTableCounts();

    runDemoSeeder();

    expect(demoTableCounts())->toBe($afterFirst);
});

test('existing accounts and data are not changed', function () {
    // An account that already uses a demo email, and unrelated data.
    $existing = pathwiseUser('admin', [
        'name' => 'Real Person',
        'email' => 'demo.teacher1@example.com',
        'password' => 'their-own-password',
        'email_verified_at' => null,
    ]);
    $category = CourseCategory::create(['name' => 'Business', 'description' => 'Kept as it is', 'is_active' => false]);
    $course = pathwiseCourse(pathwiseApprovedTeacher(), ['title' => 'A real course', 'status' => 'published']);

    $snapshot = fn () => [
        DB::table('users')->where('id', $existing->id)->first(),
        DB::table('user_roles')->where('user_id', $existing->id)->get()->all(),
        DB::table('course_categories')->where('id', $category->id)->first(),
        DB::table('courses')->where('id', $course->id)->first(),
    ];

    $before = $snapshot();

    runDemoSeeder();

    expect($snapshot())->toEqual($before)
        ->and(Hash::check('their-own-password', $existing->fresh()->password))->toBeTrue()
        ->and($existing->fresh()->hasRole('teacher'))->toBeFalse();
});

test('it does nothing outside the local environment', function (string $environment) {
    $before = demoTableCounts();

    runDemoSeeder($environment);

    expect(demoTableCounts())->toBe($before);
    Storage::disk('local')->assertMissing(DemoDataSeeder::RECEIPT_PATH);
})->with(['production', 'staging', 'testing']);

test('it does nothing without DEMO_SEED_PASSWORD', function (?string $password) {
    $before = demoTableCounts();

    runDemoSeeder('local', $password);

    expect(demoTableCounts())->toBe($before);
})->with(['not set' => null, 'empty' => '']);

test('it adds sample reviews, one of them hidden', function () {
    runDemoSeeder();

    expect(App\Models\CourseReview::count())->toBe(8)
        ->and(App\Models\CourseReview::where('is_hidden', true)->count())->toBe(1)
        ->and(App\Models\CourseReview::where('is_hidden', true)->sole()->hidden_reason)->not->toBeEmpty();

    // Every reviewer has an enrollment that gives access to the course.
    foreach (App\Models\CourseReview::all() as $review) {
        expect(Enrollment::where('student_id', $review->student_id)
            ->where('course_id', $review->course_id)
            ->withAccess()
            ->exists())->toBeTrue();
    }
});

test('no emails or notifications are sent', function () {
    runDemoSeeder();

    Notification::assertNothingSent();
    expect(DB::table('notifications')->count())->toBe(0);
});

test('demo students can open their pages', function () {
    runDemoSeeder();

    $student = User::where('email', 'demo.student01@example.com')->sole();

    $this->actingAs($student)->get(route('student.dashboard'))->assertOk();
    $this->actingAs($student)->get(route('student.my-courses'))->assertOk()
        ->assertSee('Web Development Basics (Demo)');
});
