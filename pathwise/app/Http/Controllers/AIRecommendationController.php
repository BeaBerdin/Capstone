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

        $averageScore = QuizResult::where('student_id', $studentId)
            ->avg('percentage');

        if (!$averageScore) {
            return redirect()
                ->back()
                ->with('error', 'No quiz results found for this student.');
        }

        if ($averageScore >= 85) {
            $difficulty = 'advanced';
            $reason = 'The student has demonstrated strong performance based on quiz results and is ready for advanced learning materials.';
        } elseif ($averageScore >= 70) {
            $difficulty = 'intermediate';
            $reason = 'The student has shown satisfactory understanding and is recommended to continue with intermediate-level courses.';
        } else {
            $difficulty = 'beginner';
            $reason = 'The student needs foundational reinforcement based on quiz performance and is recommended to review beginner-level courses.';
        }

        $course = Course::where('difficulty_level', $difficulty)
            ->where('status', 'published')
            ->first();

        if (!$course) {
            $course = Course::where('difficulty_level', $difficulty)->first();
        }

        if (!$course) {
            return redirect()
                ->back()
                ->with('error', 'No matching course found for recommendation.');
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

    public function destroy(string $id)
    {
        AIRecommendation::findOrFail($id)->delete();

        return back()->with(
            'success',
            'Recommendation deleted successfully.'
        );
    }
    public function studentRecommendations()
{
    $recommendations = AIRecommendation::with('course')
        ->where('student_id', auth()->id())
        ->latest()
        ->get();

    return view(
        'student.recommendations.index',
        compact('recommendations')
    );
}
}