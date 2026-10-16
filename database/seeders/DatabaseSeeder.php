<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsAccounts;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use SeedsAccounts;
    use WithoutModelEvents;

    public function run(): void
    {
        // Safe in every environment, including production.
        $this->call([
            RoleSeeder::class,
            SuperAdminSeeder::class,
            CourseTemplateSeeder::class,
        ]);

        // Demo accounts with the password "password": local and testing only.
        if (! $this->isLocalEnvironment()) {
            $this->command?->info('Skipping demo accounts outside local/testing.');

            return;
        }

        $this->call(AdminUserSeeder::class);

        $this->seedAccount(
            'teacher@pathwise.test',
            'Teacher Account',
            fn () => 'password',
            'teacher'
        );

        $this->seedAccount(
            'student@pathwise.test',
            'Student Account',
            fn () => 'password',
            'student'
        );
    }
}
