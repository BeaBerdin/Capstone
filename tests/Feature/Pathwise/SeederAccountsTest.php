<?php

use App\Models\Course;
use App\Models\CourseTemplate;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PathWiseDemoSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

/*
| Manuscript Fig. 22/35: a fresh install needs a Super Admin (EDP).
| Seeding must be safe to run on a live database: it never resets a
| password, and seeded accounts are verified only in local/testing.
*/

function runSeeder(string $class): string
{
    Artisan::call('db:seed', ['--class' => $class, '--force' => true]);

    return Artisan::output();
}

function seededAccountEmails(): array
{
    return [
        SuperAdminSeeder::EMAIL,
        'admin@pathwise.test',
        'teacher@pathwise.test',
        'student@pathwise.test',
    ];
}

test('the Super Admin uses SEED_SUPERADMIN_PASSWORD when it is set and does not print it', function () {
    config(['auth.seed_super_admin_password' => 'Configured-Pass-2026']);

    $this->seed(RoleSeeder::class);
    $output = runSeeder(SuperAdminSeeder::class);

    $superAdmin = User::where('email', SuperAdminSeeder::EMAIL)->sole();

    expect(Hash::check('Configured-Pass-2026', $superAdmin->password))->toBeTrue()
        ->and($superAdmin->hasRole('super_admin'))->toBeTrue()
        ->and($output)->not->toContain('Configured-Pass-2026')
        ->and($output)->not->toContain('shown only once');
});

test('without SEED_SUPERADMIN_PASSWORD a random password is generated and printed once', function () {
    config(['auth.seed_super_admin_password' => null]);

    $this->seed(RoleSeeder::class);
    $firstOutput = runSeeder(SuperAdminSeeder::class);

    preg_match('/shown only once\): (\S+)/', $firstOutput, $matches);
    $printedPassword = $matches[1] ?? null;

    $superAdmin = User::where('email', SuperAdminSeeder::EMAIL)->sole();

    expect($printedPassword)->not->toBeNull()
        ->and(strlen($printedPassword))->toBe(20)
        ->and($printedPassword)->not->toBe('password')
        ->and(Hash::check($printedPassword, $superAdmin->password))->toBeTrue();

    // A second run prints nothing and keeps the same password.
    $secondOutput = runSeeder(SuperAdminSeeder::class);

    expect($secondOutput)->not->toContain('shown only once')
        ->and(Hash::check($printedPassword, $superAdmin->fresh()->password))->toBeTrue();
});

test('seeding never changes the password or name of an existing account', function () {
    config(['auth.seed_super_admin_password' => 'Seeder-Would-Set-This']);

    foreach (seededAccountEmails() as $email) {
        User::factory()->create([
            'email' => $email,
            'name' => 'Real Person',
            'password' => 'my-own-live-password',
        ]);
    }

    $output = runSeeder(DatabaseSeeder::class);

    foreach (seededAccountEmails() as $email) {
        $user = User::where('email', $email)->sole();

        expect(Hash::check('my-own-live-password', $user->password))->toBeTrue("password changed for {$email}")
            ->and($user->name)->toBe('Real Person');
    }

    expect($output)->not->toContain('shown only once');
});

test('seeded accounts are email-verified in the local and testing environments', function () {
    config(['auth.seed_super_admin_password' => 'Local-Pass-2026']);

    runSeeder(DatabaseSeeder::class);

    foreach (seededAccountEmails() as $email) {
        expect(User::where('email', $email)->value('email_verified_at'))
            ->not->toBeNull("{$email} should be verified");
    }

    expect(User::where('email', SuperAdminSeeder::EMAIL)->sole()->hasRole('super_admin'))->toBeTrue();
});

test('in production no demo account is created, but roles, templates and the Super Admin are', function () {
    config(['auth.seed_super_admin_password' => 'Production-Pass-2026']);
    $this->app['env'] = 'production';

    runSeeder(DatabaseSeeder::class);

    foreach (['admin@pathwise.test', 'teacher@pathwise.test', 'student@pathwise.test'] as $email) {
        expect(User::where('email', $email)->exists())->toBeFalse("{$email} must not exist in production");
    }

    $superAdmin = User::where('email', SuperAdminSeeder::EMAIL)->sole();

    expect(Role::pluck('name')->sort()->values()->all())
        ->toBe(['admin', 'student', 'super_admin', 'teacher'])
        ->and(CourseTemplate::count())->toBe(3)
        ->and($superAdmin->hasRole('super_admin'))->toBeTrue()
        ->and(Hash::check('Production-Pass-2026', $superAdmin->password))->toBeTrue()
        ->and($superAdmin->email_verified_at)->toBeNull();
});

test('the demo seeders create nothing when run on their own in production', function () {
    $this->app['env'] = 'production';

    runSeeder(RoleSeeder::class);
    runSeeder(AdminUserSeeder::class);
    runSeeder(PathWiseDemoSeeder::class);

    expect(User::count())->toBe(0)
        ->and(Course::count())->toBe(0);
});

test('an existing unverified account is not verified by seeding in production', function () {
    config(['auth.seed_super_admin_password' => 'Production-Pass-2026']);
    $this->app['env'] = 'production';

    User::factory()->unverified()->create([
        'email' => SuperAdminSeeder::EMAIL,
        'password' => 'my-own-live-password',
    ]);

    runSeeder(DatabaseSeeder::class);

    $superAdmin = User::where('email', SuperAdminSeeder::EMAIL)->sole();

    expect($superAdmin->email_verified_at)->toBeNull()
        ->and(Hash::check('my-own-live-password', $superAdmin->password))->toBeTrue();
});

test('a generated password with console-tag symbols is printed exactly', function () {
    config(['auth.seed_super_admin_password' => null]);

    // Symbols that the console would otherwise read as colour tags.
    $tricky = 'Ab1</>cd<info>x\y9';

    $this->app->bind(SuperAdminSeeder::class, fn () => new class($tricky) extends SuperAdminSeeder
    {
        public function __construct(private string $fixed) {}

        protected function generatePassword(): string
        {
            return $this->fixed;
        }
    });

    $this->seed(RoleSeeder::class);
    $output = runSeeder(SuperAdminSeeder::class);

    expect($output)->toContain('shown only once): '.$tricky)
        ->and(Hash::check($tricky, User::where('email', SuperAdminSeeder::EMAIL)->sole()->password))->toBeTrue();
});
