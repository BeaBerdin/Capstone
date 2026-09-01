<?php

namespace App\Services;

use App\Models\AIRecommendation;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;

class RecommendationService
{
    public function generate(int $studentId, Quiz $quiz, float $percentage): void
    {
        $currentCourse = $quiz->course;

        if (!$currentCourse) {
            return;
        }

        // Delete previous recommendation
        AIRecommendation::where('student_id', $studentId)->delete();

        // Exclude only completed courses and current course
        $completedCourseIds = Enrollment::where('student_id', $studentId)
            ->where('status', 'completed')
            ->pluck('course_id')
            ->toArray();

        $excludedCourseIds = array_merge(
            [$currentCourse->id],
            $completedCourseIds
        );

        // Determine target difficulty
        if ($percentage >= 85) {
            $targetDifficulty = match ($currentCourse->difficulty_level) {
                'beginner' => 'intermediate',
                'intermediate' => 'advanced',
                default => 'advanced',
            };

            $fallbackReason = "Excellent work! Based on your strong performance in {$currentCourse->title}, this course is the next step to continue improving your skills.";
        } elseif ($percentage >= $quiz->passing_score) {
            $targetDifficulty = $currentCourse->difficulty_level;

            $fallbackReason = "You passed {$currentCourse->title}. This course will help reinforce your knowledge before moving to more advanced topics.";
        } else {
            $targetDifficulty = 'beginner';

            $fallbackReason = "Your recent quiz shows that strengthening your foundation will help you succeed. This course is recommended to improve your understanding.";
        }

        /*
        |--------------------------------------------------------------------------
        | Priority 1
        | Same category + target difficulty
        |--------------------------------------------------------------------------
        */

        $recommendedCourse = Course::where('category_id', $currentCourse->category_id)
            ->where('difficulty_level', $targetDifficulty)
            ->where('status', 'published')
            ->whereNotIn('id', $excludedCourseIds)
            ->has('lessons')
            ->has('quizzes')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Priority 2
        | Same category (any difficulty)
        |--------------------------------------------------------------------------
        */

        if (!$recommendedCourse) {

            $recommendedCourse = Course::where('category_id', $currentCourse->category_id)
                ->where('status', 'published')
                ->whereNotIn('id', $excludedCourseIds)
                ->has('lessons')
                ->has('quizzes')
                ->orderByRaw("
                    CASE difficulty_level
                        WHEN 'beginner' THEN 1
                        WHEN 'intermediate' THEN 2
                        WHEN 'advanced' THEN 3
                    END
                ")
                ->first();
        }

        /*
        |--------------------------------------------------------------------------
        | Priority 3
        | Any category
        |--------------------------------------------------------------------------
        */

        if (!$recommendedCourse) {

            $recommendedCourse = Course::where('status', 'published')
                ->whereNotIn('id', $excludedCourseIds)
                ->has('lessons')
                ->has('quizzes')
                ->orderByRaw("
                    CASE difficulty_level
                        WHEN 'beginner' THEN 1
                        WHEN 'intermediate' THEN 2
                        WHEN 'advanced' THEN 3
                    END
                ")
                ->first();

            if ($recommendedCourse) {
                $fallbackReason = "You have completed the available courses in this learning path. We recommend exploring this course to continue developing your knowledge and skills.";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Priority 4
        | Last fallback
        |--------------------------------------------------------------------------
        */

        if (!$recommendedCourse) {

            $recommendedCourse = Course::where('status', 'published')
                ->where('id', '!=', $currentCourse->id)
                ->first();

            if ($recommendedCourse) {
                $fallbackReason = "This course is recommended to help you continue your learning journey.";
            }
        }

        if (!$recommendedCourse) {
            return;
        }

        $geminiReason = app(GeminiRecommendationService::class)
            ->generateReason($quiz, $recommendedCourse, $percentage);

        AIRecommendation::create([
            'student_id' => $studentId,
            'course_id' => $recommendedCourse->id,
            'recommendation_score' => $percentage,
            'reason' => $geminiReason ?: $fallbackReason,
            'is_viewed' => false,
        ]);
    }
}