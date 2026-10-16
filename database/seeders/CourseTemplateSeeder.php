<?php

namespace Database\Seeders;

use App\Models\CourseTemplate;
use Illuminate\Database\Seeder;

/**
 * Built-in curriculum templates (Manuscript: "pre-designed curriculum
 * templates" in the Rationale, Scope, and Definition of Terms).
 *
 * Safe to run more than once: templates are matched by name and updated.
 *
 *   php artisan db:seed --class=CourseTemplateSeeder
 */
class CourseTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $template) {
            CourseTemplate::updateOrCreate(
                ['name' => $template['name']],
                [
                    'description' => $template['description'],
                    'structure' => ['lessons' => $template['lessons']],
                    'is_active' => true,
                ]
            );
        }
    }

    private function templates(): array
    {
        return [
            [
                'name' => 'Standard Course (3 Modules)',
                'description' => 'A complete course with an overview, three topic modules, and a final review. Suitable for most subjects.',
                'lessons' => [
                    [
                        'title' => 'Course Overview and Learning Outcomes',
                        'duration_minutes' => 20,
                        'guidance' => "Introduce the course and yourself.\nList 3 to 5 learning outcomes: what students will be able to do after the course.\nExplain how the course is organized and how students will be assessed.",
                    ],
                    [
                        'title' => 'Module 1: Core Concepts',
                        'duration_minutes' => 45,
                        'guidance' => "Explain the basic terms and ideas students must know first.\nUse simple examples.\nEnd with a short summary of the key points.",
                    ],
                    [
                        'title' => 'Module 2: Applying the Concepts',
                        'duration_minutes' => 45,
                        'guidance' => "Show how the core concepts are used in real situations.\nInclude a worked example, step by step.\nAdd a practice activity students can try on their own.",
                    ],
                    [
                        'title' => 'Module 3: Deeper Topics',
                        'duration_minutes' => 45,
                        'guidance' => "Build on Modules 1 and 2 with more advanced material.\nPoint out common mistakes and how to avoid them.",
                    ],
                    [
                        'title' => 'Course Summary and Review',
                        'duration_minutes' => 20,
                        'guidance' => "Summarize the most important ideas from each module.\nRemind students to take the final quiz.\nSuggest what to learn next.",
                    ],
                ],
            ],
            [
                'name' => 'Hands-on Skills Workshop',
                'description' => 'A practical, activity-based course: demonstration, guided practice, and an independent exercise.',
                'lessons' => [
                    [
                        'title' => 'Workshop Introduction and Setup',
                        'duration_minutes' => 20,
                        'guidance' => "Describe the skill students will practice.\nList the tools, software, or materials needed and how to set them up.",
                    ],
                    [
                        'title' => 'Demonstration',
                        'duration_minutes' => 30,
                        'guidance' => "Demonstrate the skill from start to finish (a video works well).\nExplain each step and why it matters.",
                    ],
                    [
                        'title' => 'Guided Practice',
                        'duration_minutes' => 40,
                        'guidance' => "Give a task similar to the demonstration with step-by-step hints.\nInclude the expected result so students can check their work.",
                    ],
                    [
                        'title' => 'Independent Exercise',
                        'duration_minutes' => 60,
                        'guidance' => "Give a new task students complete on their own.\nYou can create an assignment for this lesson so students can submit their output.",
                    ],
                    [
                        'title' => 'Reflection and Next Steps',
                        'duration_minutes' => 15,
                        'guidance' => "Ask students to reflect on what was difficult and what they learned.\nRecommend further practice or a follow-up course.",
                    ],
                ],
            ],
            [
                'name' => 'Short Course (Micro-credential)',
                'description' => 'A compact course focused on one specific skill or topic, ideal for a short certificate course.',
                'lessons' => [
                    [
                        'title' => 'Introduction',
                        'duration_minutes' => 15,
                        'guidance' => "State the single skill or topic this short course covers and why it is useful.",
                    ],
                    [
                        'title' => 'Key Lesson',
                        'duration_minutes' => 40,
                        'guidance' => "Teach the main content in clear, small steps with at least one example.",
                    ],
                    [
                        'title' => 'Practice and Recap',
                        'duration_minutes' => 20,
                        'guidance' => "Provide a short practice activity and a recap of the key points before the final quiz.",
                    ],
                ],
            ],
        ];
    }
}
