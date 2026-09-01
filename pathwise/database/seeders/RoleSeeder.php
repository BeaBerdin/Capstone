<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate([
            'name' => 'super_admin'
        ], [
             'description' => 'Super Administrator / EDP'
        ]);
        
        Role::firstOrCreate([
            'name' => 'admin'
        ], [
            'description' => 'System Administrator'
        ]);

        Role::firstOrCreate([
            'name' => 'teacher'
        ], [
            'description' => 'Course Instructor'
        ]);

        Role::firstOrCreate([
            'name' => 'student'
        ], [
            'description' => 'Platform Learner'
        ]);
    }
}