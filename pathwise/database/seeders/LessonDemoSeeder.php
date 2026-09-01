<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Lesson;

class LessonDemoSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::all();

        foreach ($courses as $course) {

            if (str_contains(strtolower($course->title), 'accounting')) {

                $lessons = [
                    'Introduction to Accounting',
                    'Accounting Equation',
                    'Journal Entries',
                    'Ledger Posting',
                    'Trial Balance'
                ];

            } elseif (str_contains(strtolower($course->title), 'laravel')) {

                $lessons = [
                    'Installing Laravel',
                    'MVC Architecture',
                    'Routing',
                    'Controllers',
                    'Eloquent ORM'
                ];

            } elseif (str_contains(strtolower($course->title), 'web')) {

                $lessons = [
                    'Introduction to HTML',
                    'CSS Fundamentals',
                    'JavaScript Basics',
                    'Responsive Design',
                    'Deploying Websites'
                ];

            } elseif (str_contains(strtolower($course->title), 'marketing')) {

                $lessons = [
                    'Introduction to Digital Marketing',
                    'SEO Fundamentals',
                    'Social Media Marketing',
                    'Google Ads',
                    'Marketing Analytics'
                ];

            } elseif (str_contains(strtolower($course->title), 'entrepreneurship')) {

                $lessons = [
                    'Business Planning',
                    'Market Research',
                    'Financial Management',
                    'Business Operations',
                    'Business Growth Strategies'
                ];

            } else {

                $lessons = [
                    'Module 1',
                    'Module 2',
                    'Module 3',
                    'Module 4',
                    'Module 5'
                ];
            }

            foreach ($lessons as $index => $lessonTitle) {

                Lesson::create([
                    'course_id' => $course->id,
                    'title' => $lessonTitle,
                    'content' => $lessonTitle . ' lesson content.',
                    'lesson_type' => 'text',
                    'lesson_order' => $index + 1,
                    'duration_minutes' => rand(10, 30),
                    'is_preview' => $index === 0,
                    'is_published' => true,
                ]);
            }
        }
    }
}