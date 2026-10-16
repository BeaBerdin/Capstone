<?php

namespace App\Services;

use App\Models\AIRecommendation;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;

class RecommendationService
{
    public function generate(
        int $studentId,
        Quiz $quiz,
        float $percentage
    ): void {
        $currentCourse = $quiz->course;

        if (!$currentCourse) {
            return;
        }

        /*
         * Remove previous recommendations for this student.
         */
        AIRecommendation::where('student_id', $studentId)->delete();

        /*
         * Get courses that the student has already completed
         * or is currently enrolled in.
         */
        $completedCourseIds = Enrollment::where('student_id', $studentId)
            ->where('status', 'completed')
            ->pluck('course_id')
            ->toArray();

        $enrolledCourseIds = Enrollment::where('student_id', $studentId)
            ->pluck('course_id')
            ->toArray();

        /*
         * Do not recommend:
         * - the current course
         * - completed courses
         * - courses already enrolled in
         */
        $excludedCourseIds = array_unique(array_merge(
            [$currentCourse->id],
            $completedCourseIds,
            $enrolledCourseIds
        ));

        /*
         * Determine a suitable difficulty using the student's
         * current quiz result only.
         *
         * This is ordinary application logic, not machine learning.
         */
        if ($percentage >= 85) {
            $targetDifficulty = match ($currentCourse->difficulty_level) {
                'beginner' => 'intermediate',
                'intermediate' => 'advanced',
                default => 'advanced',
            };
        } elseif ($percentage >= $quiz->passing_score) {
            $targetDifficulty = $currentCourse->difficulty_level;
        } else {
            $targetDifficulty = 'beginner';
        }

        /*
         * Fallback explanation in case Gemini is unavailable.
         */
        if ($percentage >= $quiz->passing_score) {
            $fallbackReason =
                "You performed well on the quiz, and this course "
                . "can help you continue developing your knowledge "
                . "and skills.";
        } else {
            $fallbackReason =
                "Strengthening your foundation can help you build "
                . "the knowledge and skills needed for your next "
                . "learning step.";
        }

        /*
         * Priority 1:
         * Find a course with the target difficulty in the
         * same category.
         */
        $recommendedCourse = Course::where(
            'category_id',
            $currentCourse->category_id
        )
            ->where(
                'difficulty_level',
                $targetDifficulty
            )
            ->where('status', 'published')
            ->whereNotIn('id', $excludedCourseIds)
            ->has('lessons')
            ->has('quizzes')
            ->first();

        /*
         * Priority 2:
         * Find the closest difficulty in the same category.
         */
        if (!$recommendedCourse) {
            $difficultyRank = [
                'beginner' => 1,
                'intermediate' => 2,
                'advanced' => 3,
            ];

            $targetRank = $difficultyRank[$targetDifficulty] ?? 2;

            $recommendedCourse = Course::where(
                'category_id',
                $currentCourse->category_id
            )
                ->where('status', 'published')
                ->whereNotIn('id', $excludedCourseIds)
                ->has('lessons')
                ->has('quizzes')
                // Standard SQL single quotes, so this works on MySQL in any
                // mode (including ANSI_QUOTES) and on SQLite.
                ->orderByRaw(
                    "ABS(
                        CASE difficulty_level
                            WHEN 'beginner' THEN 1
                            WHEN 'intermediate' THEN 2
                            WHEN 'advanced' THEN 3
                            ELSE 2
                        END - ?
                    )",
                    [$targetRank]
                )
                ->first();
        }

        /*
         * Priority 3:
         * If there is no suitable course in the same category,
         * use any published course with lessons and quizzes.
         */
        if (!$recommendedCourse) {
            $recommendedCourse = Course::where('status', 'published')
                ->whereNotIn('id', $excludedCourseIds)
                ->has('lessons')
                ->has('quizzes')
                ->first();
        }

        /*
         * Final fallback:
         * Any published course that the student has not taken.
         */
        if (!$recommendedCourse) {
            $recommendedCourse = Course::where('status', 'published')
                ->whereNotIn('id', $excludedCourseIds)
                ->first();
        }

        if (!$recommendedCourse) {
            return;
        }

        /*
         * Ask Gemini to generate the personalized explanation.
         *
         * Gemini is used only for generating the recommendation
         * explanation. No machine-learning model is involved.
         */
        $geminiReason = app(GeminiRecommendationService::class)
            ->generateReason(
                $quiz,
                $recommendedCourse,
                $percentage
            );

        /*
         * Recommendation score.
         *
         * This is simply the student's quiz percentage.
         * It is not an ML prediction or classification score.
         */
        $recommendationScore = round($percentage, 2);

        /*
         * Save the recommendation.
         */
        AIRecommendation::create([
            'student_id' => $studentId,
            'course_id' => $recommendedCourse->id,
            'recommendation_score' => $recommendationScore,
            'reason' => $geminiReason ?: $fallbackReason,
            'is_viewed' => false,
        ]);
    }
}