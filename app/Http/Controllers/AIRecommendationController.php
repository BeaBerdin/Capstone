<?php

namespace App\Http\Controllers;

use App\Models\AIRecommendation;
use App\Models\Course;
use App\Models\QuizResult;
use App\Models\User;
use Illuminate\Http\Request;

class AIRecommendationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ADMIN - AI RECOMMENDATIONS
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $recommendations = AIRecommendation::with([
                'student',
                'course',
            ])
            ->latest()
            ->get();

        return view(
            'ai-recommendations.index',
            compact('recommendations')
        );
    }


    public function create()
    {
        $students = User::whereHas(
                'roles',
                function ($query) {
                    $query->where(
                        'name',
                        'student'
                    );
                }
            )
            ->orderBy('name')
            ->get();

        return view(
            'ai-recommendations.create',
            compact('students')
        );
    }


    public function store(Request $request)
    {
        $request->validate([
            'student_id' =>
                'required|exists:users,id',
        ]);

        $student = User::findOrFail(
            $request->student_id
        );

        /*
         * The request must point to a real student account.
         * This prevents an administrator from accidentally generating a
         * student recommendation for a teacher/admin account.
         */
        if (!$student->hasRole('student')) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'AI recommendations can only be generated for student accounts.'
                );
        }

        $studentId =
            (int) $student->id;


        /*
        |--------------------------------------------------------------------------
        | USE ONLY CURRENTLY VALID QUIZ RESULTS
        |--------------------------------------------------------------------------
        | Stale quiz results from draft/rejected/unpublished courses or
        | unpublished quizzes should not affect a student's recommendation.
        */

        $averageScore = QuizResult::where(
                'student_id',
                $studentId
            )
            ->whereHas(
                'quiz',
                function ($query) {
                    $query
                        ->where(
                            'is_published',
                            true
                        )
                        ->whereHas(
                            'course',
                            function ($courseQuery) {
                                $courseQuery->where(
                                    'status',
                                    'published'
                                );
                            }
                        );
                }
            )
            ->avg('percentage');

        if ($averageScore === null) {
            return back()->with(
                'error',
                'No eligible published quiz results were found for this student.'
            );
        }


        if ($averageScore >= 85) {
            $difficulty =
                'advanced';

            $reason =
                'The student has demonstrated strong performance based on quiz results and is ready for advanced learning materials.';
        } elseif ($averageScore >= 70) {
            $difficulty =
                'intermediate';

            $reason =
                'The student has shown satisfactory understanding and is recommended to continue with intermediate-level courses.';
        } else {
            $difficulty =
                'beginner';

            $reason =
                'The student needs foundational reinforcement based on quiz performance and is recommended to review beginner-level courses.';
        }


        /*
        |--------------------------------------------------------------------------
        | RECOMMEND ONLY AVAILABLE PUBLISHED COURSES
        |--------------------------------------------------------------------------
        */

        $course = Course::where(
                'difficulty_level',
                $difficulty
            )
            ->where(
                'status',
                'published'
            )
            ->whereHas(
                'lessons',
                function ($query) {
                    $query->where(
                        'is_published',
                        true
                    );
                }
            )
            ->whereHas(
                'quizzes',
                function ($query) {
                    $query->where(
                        'is_published',
                        true
                    );
                }
            )
            ->latest()
            ->first();

        if (!$course) {
            return back()->with(
                'error',
                'No complete published course is currently available for the recommended difficulty level.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CREATE OR REFRESH THE SAME RECOMMENDATION
        |--------------------------------------------------------------------------
        | Avoid creating duplicate rows for the same student/course pair.
        */

        AIRecommendation::updateOrCreate(
            [
                'student_id' =>
                    $studentId,

                'course_id' =>
                    $course->id,
            ],
            [
                'recommendation_score' =>
                    round(
                        (float) $averageScore,
                        2
                    ),

                'reason' =>
                    $reason,

                'is_viewed' =>
                    false,
            ]
        );

        return redirect()
            ->route(
                'ai-recommendations.index'
            )
            ->with(
                'success',
                'AI recommendation generated successfully.'
            );
    }


    public function destroy(string $id)
    {
        AIRecommendation::findOrFail(
            $id
        )->delete();

        return back()->with(
            'success',
            'Recommendation deleted successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STUDENT - OWN RECOMMENDATIONS ONLY
    |--------------------------------------------------------------------------
    */

    public function studentRecommendations()
    {
        $recommendations = AIRecommendation::with([
                'course.category',
            ])
            ->where(
                'student_id',
                auth()->id()
            )
            ->whereHas(
                'course',
                function ($query) {
                    $query->where(
                        'status',
                        'published'
                    );
                }
            )
            ->latest()
            ->get();

        return view(
            'student.recommendations.index',
            compact('recommendations')
        );
    }
}
