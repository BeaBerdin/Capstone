<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentAssignmentController extends Controller
{
    /**
     * Show an assignment to the authenticated student.
     */
    public function show(Assignment $assignment)
    {
        $this->ensureStudentCanAccessAssignment($assignment);

        $submission = Submission::where('assignment_id', $assignment->id)
            ->where('student_id', auth()->id())
            ->latest('submitted_at')
            ->first();

        return view('student.assignments.show', compact(
            'assignment',
            'submission'
        ));
    }

    /**
     * Submit an assignment.
     */
    public function submit(Request $request, Assignment $assignment)
    {
        $this->ensureStudentCanAccessAssignment($assignment);

        $validated = $request->validate([
            'answer_text' => 'nullable|string',
            'file' => 'nullable|file|max:10240',
        ]);

        if (
            empty($validated['answer_text']) &&
            !$request->hasFile('file')
        ) {
            return back()
                ->withErrors([
                    'submission' =>
                        'Please provide an answer or upload a file.',
                ])
                ->withInput();
        }

        /*
         * Students may resubmit only while the submission is not graded.
         */
        $existingSubmission = Submission::where(
            'assignment_id',
            $assignment->id
        )
            ->where(
                'student_id',
                auth()->id()
            )
            ->latest('submitted_at')
            ->first();

        if (
            $existingSubmission &&
            $existingSubmission->status === 'graded'
        ) {
            return back()->withErrors([
                'submission' =>
                    'This assignment has already been graded.',
            ]);
        }

        $filePath = $existingSubmission?->file_path;

        if ($request->hasFile('file')) {
            if (
                $filePath &&
                Storage::disk('public')->exists($filePath)
            ) {
                Storage::disk('public')->delete($filePath);
            }

            $filePath = $request->file('file')->store(
                'assignment-submissions',
                'public'
            );
        }

        $data = [
            'assignment_id' => $assignment->id,
            'student_id' => auth()->id(),
            'answer_text' => $validated['answer_text'] ?? null,
            'file_path' => $filePath,
            'status' => 'submitted',
            'submitted_at' => now(),
        ];

        if ($existingSubmission) {
            $existingSubmission->update($data);
        } else {
            Submission::create($data);
        }

        return redirect()
            ->route('student.assignment.show', $assignment)
            ->with(
                'success',
                'Assignment submitted successfully.'
            );
    }

    /**
     * Make sure the student is enrolled in the assignment's course.
     */
    private function ensureStudentCanAccessAssignment(
        Assignment $assignment
    ): void {
        $studentId = auth()->id();

        $isEnrolled = $assignment->course
            ->enrollments()
            ->where('student_id', $studentId)
            ->exists();

        if (!$isEnrolled) {
            abort(
                403,
                'You are not enrolled in this course.'
            );
        }

        if (!$assignment->is_published) {
            abort(
                404,
                'Assignment not found.'
            );
        }
    }
}php -l app\Http\Controllers\StudentAssignmentController.php