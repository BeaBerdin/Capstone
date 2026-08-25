<?php

namespace App\Http\Controllers;

use App\Models\QuizResult;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\Request;

class QuizResultController extends Controller
{
    public function index()
    {
        $results = QuizResult::with(['student', 'quiz'])->latest()->get();

        return view('quiz-results.index', compact('results'));
    }

    public function create()
    {
        $students = User::orderBy('name')->get();
        $quizzes = Quiz::orderBy('title')->get();

        return view('quiz-results.create', compact('students', 'quizzes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'quiz_id' => 'required|exists:quizzes,id',
            'score' => 'required|integer|min:0',
            'total_items' => 'required|integer|min:1',
            'attempt_number' => 'required|integer|min:1',
        ]);

        $percentage = ($request->score / $request->total_items) * 100;
        $remarks = $percentage >= 75 ? 'passed' : 'failed';

        QuizResult::create([
            'student_id' => $request->student_id,
            'quiz_id' => $request->quiz_id,
            'score' => $request->score,
            'total_items' => $request->total_items,
            'percentage' => $percentage,
            'remarks' => $remarks,
            'attempt_number' => $request->attempt_number,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('teacher.quiz-results.index')
            ->with('success', 'Quiz result recorded successfully.');
    }

    public function edit(QuizResult $quiz_result)
    {
        $students = User::orderBy('name')->get();
        $quizzes = Quiz::orderBy('title')->get();

        return view('quiz-results.edit', compact('quiz_result', 'students', 'quizzes'));
    }

    public function update(Request $request, QuizResult $quiz_result)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'quiz_id' => 'required|exists:quizzes,id',
            'score' => 'required|integer|min:0',
            'total_items' => 'required|integer|min:1',
            'attempt_number' => 'required|integer|min:1',
        ]);

        $percentage = ($request->score / $request->total_items) * 100;
        $remarks = $percentage >= 75 ? 'passed' : 'failed';

        $quiz_result->update([
            'student_id' => $request->student_id,
            'quiz_id' => $request->quiz_id,
            'score' => $request->score,
            'total_items' => $request->total_items,
            'percentage' => $percentage,
            'remarks' => $remarks,
            'attempt_number' => $request->attempt_number,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('teacher.quiz-results.index')
            ->with('success', 'Quiz result updated successfully.');
    }

    public function destroy(QuizResult $quiz_result)
    {
        $quiz_result->delete();

        return redirect()
            ->route('teacher.quiz-results.index')
            ->with('success', 'Quiz result deleted successfully.');
    }
}