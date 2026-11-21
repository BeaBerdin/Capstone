<?php

namespace App\Http\Controllers;

use App\Models\QuizQuestion;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizQuestionController extends Controller
{
    public function index(Request $request)
    {
        $questions = QuizQuestion::with('quiz')
            ->whereHas('quiz', fn ($quiz) => $quiz->manageableBy($request->user()))
            ->latest()
            ->get();

        return view('quiz-questions.index', compact('questions'));
    }

    public function create(Request $request)
    {
        $quizzes = Quiz::manageableBy($request->user())->orderBy('title')->get();

        return view('quiz-questions.create', compact('quizzes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'question' => 'required',
            'option_a' => 'required|max:255',
            'option_b' => 'required|max:255',
            'option_c' => 'nullable|max:255',
            'option_d' => 'nullable|max:255',
            'correct_answer' => 'required|in:A,B,C,D',
            'points' => 'required|integer|min:1',
        ]);

        abort_unless(Quiz::manageableBy($request->user())->whereKey($validated['quiz_id'])->exists(), 403);

        QuizQuestion::create([
            'quiz_id' => $validated['quiz_id'],
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

    public function edit(Request $request, QuizQuestion $quiz_question)
    {
        abort_unless($quiz_question->quiz()->manageableBy($request->user())->exists(), 403);

        $quizzes = Quiz::manageableBy($request->user())->orderBy('title')->get();

        return view('quiz-questions.edit', compact('quiz_question', 'quizzes'));
    }

    public function update(Request $request, QuizQuestion $quiz_question)
    {
        abort_unless($quiz_question->quiz()->manageableBy($request->user())->exists(), 403);

        $validated = $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'question' => 'required',
            'option_a' => 'required|max:255',
            'option_b' => 'required|max:255',
            'option_c' => 'nullable|max:255',
            'option_d' => 'nullable|max:255',
            'correct_answer' => 'required|in:A,B,C,D',
            'points' => 'required|integer|min:1',
        ]);

        abort_unless(Quiz::manageableBy($request->user())->whereKey($validated['quiz_id'])->exists(), 403);

        $quiz_question->update([
            'quiz_id' => $validated['quiz_id'],
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

    public function destroy(Request $request, QuizQuestion $quiz_question)
    {
        abort_unless($quiz_question->quiz()->manageableBy($request->user())->exists(), 403);

        $quiz_question->delete();

        return redirect()
            ->route('quiz-questions.index')
            ->with('success', 'Question deleted successfully.');
    }
}