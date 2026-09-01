<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
        ]);

        $teacher = User::firstOrCreate(
            ['email' => 'teacher@pathwise.test'],
            [
                'name' => 'Teacher Account',
                'password' => bcrypt('password'),
            ]
        );

        $student = User::firstOrCreate(
            ['email' => 'student@pathwise.test'],
            [
                'name' => 'Student Account',
                'password' => bcrypt('password'),
            ]
        );

        $teacherRole = Role::where('name', 'teacher')->first();
        $studentRole = Role::where('name', 'student')->first();

        if ($teacherRole) {
            $teacher->roles()->syncWithoutDetaching([$teacherRole->id]);
        }

        if ($studentRole) {
            $student->roles()->syncWithoutDetaching([$studentRole->id]);
        }
    }
}