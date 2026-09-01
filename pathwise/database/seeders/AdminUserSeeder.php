<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@pathwise.test'],
            [
                'name' => 'System Administrator',
                'password' => bcrypt('password'),
            ]
        );

        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole) {
            $user->roles()->syncWithoutDetaching([$adminRole->id]);
        }
    }
}