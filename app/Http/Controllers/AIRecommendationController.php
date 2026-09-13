<?php

namespace App\Http\Controllers;

use App\Models\AIRecommendation;
use App\Models\Course;
use App\Models\QuizResult;
use App\Models\User;
use Illuminate\Http\Request;

class AIRecommendationController extends Controller
{
    public function index()
    {
        $recommendations = AIRecommendation::with([
            'student',
            'course'
        ])->latest()->get();

        return view('ai-recommendations.index', compact('recommendations'));
    }

    public function create()
    {
        $students = User::whereHas('roles', function ($query) {
            $query->where('name', 'student');
        })->get();

        return view('ai-recommendations.create', compact('students'));
    }

   public function store(Request $request)
{
    $request->validate([
        'student_id' => 'required|exists:users,id',
    ]);

    $studentId = $request->student_id;

    // Get the student's latest quiz result
    $quizResult = QuizResult::with('quiz.course.category')
        ->where('student_id', $studentId)
        ->latest('completed_at')
        ->first();

    if (!$quizResult) {
        return redirect()
            ->back()
            ->with('error', 'No quiz results found for this student.');
    }

    $averageScore = QuizResult::where('student_id', $studentId)
        ->avg('percentage');

    // Get the course the student is currently taking
    $currentCourse = $quizResult->quiz?->course;

    if (!$currentCourse) {
        return redirect()
            ->back()
            ->with('error', 'Unable to determine the student\'s current course.');
    }

    // Get the current course category
    $categoryId = $currentCourse->category_id;

    // Determine recommended difficulty
    if ($averageScore >= 85) {
        $difficulty = 'advanced';

        $reason = "The student achieved an average quiz score of "
            . round($averageScore, 2)
            . "% and is ready for an advanced course in the same learning category.";
    } elseif ($averageScore >= 70) {
        $difficulty = 'intermediate';

        $reason = "The student achieved an average quiz score of "
            . round($averageScore, 2)
            . "% and is recommended to continue with an intermediate course in the same learning category.";
    } else {
        $difficulty = 'beginner';

        $reason = "The student achieved an average quiz score of "
            . round($averageScore, 2)
            . "% and is recommended to strengthen foundational skills with a beginner course in the same learning category.";
    }

    /*
    |--------------------------------------------------------------------------
    | Find a related course
    |--------------------------------------------------------------------------
    */

    // First priority:
    // Same category + recommended difficulty + published
    $course = Course::where('category_id', $categoryId)
        ->where('difficulty_level', $difficulty)
        ->where('status', 'published')
        ->where('id', '!=', $currentCourse->id)
        ->first();

    // Second priority:
    // Same category + any difficulty + published
    if (!$course) {
        $course = Course::where('category_id', $categoryId)
            ->where('status', 'published')
            ->where('id', '!=', $currentCourse->id)
            ->first();
    }

    // Third priority:
    // If there is no other course in the same category,
    // look for the same difficulty in another category.
    if (!$course) {
        $course = Course::where('difficulty_level', $difficulty)
            ->where('status', 'published')
            ->where('id', '!=', $currentCourse->id)
            ->first();

        if ($course) {
            $reason .= " No other published course was available in the student's current category, so a course with the appropriate difficulty from another category was selected.";
        }
    }

    if (!$course) {
        return redirect()
            ->back()
            ->with('error', 'No suitable course found for recommendation.');
    }

    AIRecommendation::create([
        'student_id' => $studentId,
        'course_id' => $course->id,
        'recommendation_score' => round($averageScore, 2),
        'reason' => $reason,
        'is_viewed' => false,
    ]);

    return redirect()
        ->route('ai-recommendations.index')
        ->with('success', 'AI recommendation generated successfully.');
}
}