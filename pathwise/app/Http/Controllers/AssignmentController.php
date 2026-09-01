<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index()
    {
        $assignments = Assignment::with(['course', 'lesson'])
            ->latest()
            ->get();

        return view('assignments.index', compact('assignments'));
    }

    public function create()
    {
        $courses = Course::all();
        $lessons = Lesson::all();

        return view('assignments.create', compact('courses', 'lessons'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'max_score' => 'required|integer|min:1',
            'is_published' => 'required|boolean',
        ]);

        Assignment::create($validated);

        return redirect()
            ->route('assignments.index')
            ->with('success', 'Assignment created successfully.');
    }

    public function show(Assignment $assignment)
    {
        return view('assignments.show', compact('assignment'));
    }

    public function edit(Assignment $assignment)
    {
        $courses = Course::all();
        $lessons = Lesson::all();

        return view('assignments.edit', compact(
            'assignment',
            'courses',
            'lessons'
        ));
    }

    public function update(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'max_score' => 'required|integer|min:1',
            'is_published' => 'required|boolean',
        ]);

        $assignment->update($validated);

        return redirect()
            ->route('assignments.index')
            ->with('success', 'Assignment updated successfully.');
    }

    public function destroy(Assignment $assignment)
    {
        $assignment->delete();

        return redirect()
            ->route('assignments.index')
            ->with('success', 'Assignment deleted successfully.');
    }
}