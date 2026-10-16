<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsAccounts;
use Illuminate\Database\Seeder;
use App\Models\CourseCategory;
use App\Models\Course;

class PathWiseDemoSeeder extends Seeder
{
    use SeedsAccounts;

    public function run(): void
    {
        // Demo accounts and demo courses: local and testing only.
        if (! $this->isLocalEnvironment()) {
            $this->command?->info('Skipping demo data outside local/testing.');

            return;
        }

        $teacher = $this->seedAccount(
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

        $accounting = CourseCategory::firstOrCreate(
            ['name' => 'Accounting'],
            ['description' => 'Accounting and Financial Management Courses']
        );

        $business = CourseCategory::firstOrCreate(
            ['name' => 'Business'],
            ['description' => 'Business and Entrepreneurship']
        );

        $technology = CourseCategory::firstOrCreate(
            ['name' => 'Technology'],
            ['description' => 'Information Technology and Programming']
        );

        $marketing = CourseCategory::firstOrCreate(
            ['name' => 'Marketing'],
            ['description' => 'Digital Marketing and Sales']
        );

        Course::firstOrCreate(
            ['title' => 'Basic Accounting Fundamentals'],
            [
                'teacher_id' => $teacher->id,
                'category_id' => $accounting->id,
                'description' => 'Learn accounting principles, journal entries, and financial statements.',
                'difficulty_level' => 'beginner',
                'price' => 499,
                'status' => 'published',
                'estimated_hours' => 10,
                'certificate_available' => true,
            ]
        );

        Course::firstOrCreate(
            ['title' => 'Financial Accounting'],
            [
                'teacher_id' => $teacher->id,
                'category_id' => $accounting->id,
                'description' => 'Master preparation and analysis of financial statements.',
                'difficulty_level' => 'intermediate',
                'price' => 899,
                'status' => 'published',
                'estimated_hours' => 20,
                'certificate_available' => true,
            ]
        );

        Course::firstOrCreate(
            ['title' => 'Advanced Cost Accounting'],
            [
                'teacher_id' => $teacher->id,
                'category_id' => $accounting->id,
                'description' => 'Cost accumulation, budgeting, and managerial decision making.',
                'difficulty_level' => 'advanced',
                'price' => 1299,
                'status' => 'published',
                'estimated_hours' => 30,
                'certificate_available' => true,
            ]
        );

        Course::firstOrCreate(
            ['title' => 'Introduction to Web Development'],
            [
                'teacher_id' => $teacher->id,
                'category_id' => $technology->id,
                'description' => 'Learn HTML, CSS, JavaScript and modern web applications.',
                'difficulty_level' => 'beginner',
                'price' => 599,
                'status' => 'published',
                'estimated_hours' => 15,
                'certificate_available' => true,
            ]
        );

        Course::firstOrCreate(
            ['title' => 'Laravel Web Development'],
            [
                'teacher_id' => $teacher->id,
                'category_id' => $technology->id,
                'description' => 'Build modern applications using Laravel framework.',
                'difficulty_level' => 'intermediate',
                'price' => 1499,
                'status' => 'published',
                'estimated_hours' => 35,
                'certificate_available' => true,
            ]
        );

        Course::firstOrCreate(
            ['title' => 'Entrepreneurship Essentials'],
            [
                'teacher_id' => $teacher->id,
                'category_id' => $business->id,
                'description' => 'Learn how to start and manage your own business.',
                'difficulty_level' => 'beginner',
                'price' => 699,
                'status' => 'published',
                'estimated_hours' => 12,
                'certificate_available' => true,
            ]
        );

        Course::firstOrCreate(
            ['title' => 'Digital Marketing Masterclass'],
            [
                'teacher_id' => $teacher->id,
                'category_id' => $marketing->id,
                'description' => 'SEO, Social Media Marketing, and Online Advertising.',
                'difficulty_level' => 'intermediate',
                'price' => 999,
                'status' => 'published',
                'estimated_hours' => 18,
                'certificate_available' => true,
            ]
        );
    }
}