<?php

namespace App\Http\Controllers;

use App\Models\QuizQuestion;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizQuestionController extends Controller
{
    public function index()
    {
        $questions = QuizQuestion::with('quiz')->latest()->get();

        return view('quiz-questions.index', compact('questions'));
    }

    public function create()
    {
        $quizzes = Quiz::orderBy('title')->get();

        return view('quiz-questions.create', compact('quizzes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'question' => 'required',
            'option_a' => 'required|max:255',
            'option_b' => 'required|max:255',
            'option_c' => 'nullable|max:255',
            'option_d' => 'nullable|max:255',
            'correct_answer' => 'required|in:A,B,C,D',
            'points' => 'required|integer|min:1',
        ]);

        QuizQuestion::create([
            'quiz_id' => $request->quiz_id,
            'question' => $request->question,
            'option_a' => $request->option_a,
            'option_b' => $request->option_b,
            'option_c' => $request->option_c,
            'option_d' => $request->option_d,
            'correct_answer' => $request->correct_answer,
            'points' => $request->points,
        ]);

        return redirect()
            ->route('quiz-questions.index')
            ->with('success', 'Question created successfully.');
    }

    public function edit(QuizQuestion $quiz_question)
    {
        $quizzes = Quiz::orderBy('title')->get();

        return view('quiz-questions.edit', compact('quiz_question', 'quizzes'));
    }

    public function update(Request $request, QuizQuestion $quiz_question)
    {
        $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'question' => 'required',
            'option_a' => 'required|max:255',
            'option_b' => 'required|max:255',
            'option_c' => 'nullable|max:255',
            'option_d' => 'nullable|max:255',
            'correct_answer' => 'required|in:A,B,C,D',
            'points' => 'required|integer|min:1',
        ]);

        $quiz_question->update([
            'quiz_id' => $request->quiz_id,
            'question' => $request->question,
            'option_a' => $request->option_a,
            'option_b' => $request->option_b,
            'option_c' => $request->option_c,
            'option_d' => $request->option_d,
            'correct_answer' => $request->correct_answer,
            'points' => $request->points,
        ]);

        return redirect()
            ->route('quiz-questions.index')
            ->with('success', 'Question updated successfully.');
    }

    public function destroy(QuizQuestion $quiz_question)
    {
        $quiz_question->delete();

        return redirect()
            ->route('quiz-questions.index')
            ->with('success', 'Question deleted successfully.');
    }
}