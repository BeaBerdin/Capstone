<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Models\Assignment;
use App\Models\User;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function index()
    {
        $submissions = Submission::with(['assignment', 'student'])
            ->latest()
            ->get();

        return view('submissions.index', compact('submissions'));
    }

    public function create()
    {
        $assignments = Assignment::orderBy('title')->get();
        $students = User::orderBy('name')->get();

        return view('submissions.create', compact('assignments', 'students'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'student_id' => 'required|exists:users,id',
            'answer_text' => 'nullable|string',
            'file_path' => 'nullable|string|max:255',
            'score' => 'nullable|integer|min:0',
            'feedback' => 'nullable|string',
            'status' => 'required|in:submitted,graded,returned',
        ]);

        $validated['submitted_at'] = now();

        Submission::create($validated);

        return redirect()
            ->route('submissions.index')
            ->with('success', 'Submission created successfully.');
    }

    public function edit(Submission $submission)
    {
        $assignments = Assignment::orderBy('title')->get();
        $students = User::orderBy('name')->get();

        return view('submissions.edit', compact(
            'submission',
            'assignments',
            'students'
        ));
    }

    public function update(Request $request, Submission $submission)
    {
        $validated = $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'student_id' => 'required|exists:users,id',
            'answer_text' => 'nullable|string',
            'file_path' => 'nullable|string|max:255',
            'score' => 'nullable|integer|min:0',
            'feedback' => 'nullable|string',
            'status' => 'required|in:submitted,graded,returned',
        ]);

        $submission->update($validated);

        return redirect()
            ->route('submissions.index')
            ->with('success', 'Submission updated successfully.');
    }

    public function destroy(Submission $submission)
    {
        $submission->delete();

        return redirect()
            ->route('submissions.index')
            ->with('success', 'Submission deleted successfully.');
    }
}