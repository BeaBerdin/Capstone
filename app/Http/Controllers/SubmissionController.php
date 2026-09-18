<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use App\Notifications\PathwiseNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SubmissionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ADMIN - EXISTING CRUD
    |--------------------------------------------------------------------------
    */

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

        return view('submissions.create', compact('assignments'));
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

        $assignment = Assignment::findOrFail($validated['assignment_id']);

        if (isset($validated['score']) && $validated['score'] > $assignment->max_score) {
            throw ValidationException::withMessages([
                'score' => 'Score cannot exceed the assignment maximum score.',
            ]);
        }

        $validated['submitted_at'] = now();

        Submission::create($validated);

        return redirect()
            ->route('submissions.index')
            ->with('success', 'Submission created successfully.');
    }

    public function show(Submission $submission)
    {
        $submission->load(['assignment.course', 'student']);

        return view('submissions.show', compact('submission'));
    }

    public function edit(Submission $submission)
    {
        $assignments = Assignment::orderBy('title')->get();

        return view('submissions.edit', compact(
            'submission',
            'assignments'
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

        $assignment = Assignment::findOrFail($validated['assignment_id']);

        if (isset($validated['score']) && $validated['score'] > $assignment->max_score) {
            throw ValidationException::withMessages([
                'score' => 'Score cannot exceed the assignment maximum score.',
            ]);
        }

        $submission->update($validated);

        return redirect()
            ->route('submissions.index')
            ->with('success', 'Submission updated successfully.');
    }

    public function destroy(Submission $submission)
    {
        if ($submission->file_path) {
            Storage::disk('public')->delete($submission->file_path);
        }

        $submission->delete();

        return redirect()
            ->route('submissions.index')
            ->with('success', 'Submission deleted successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | STUDENT - SUBMIT / RESUBMIT
    |--------------------------------------------------------------------------
    */

    public function studentStore(Request $request, Assignment $assignment)
    {
        $this->ensureStudentCanAccessAssignment($assignment);

        if ($assignment->due_date && now()->greaterThan($assignment->due_date)) {
            throw ValidationException::withMessages([
                'submission' => 'The deadline for this assignment has already passed.',
            ]);
        }

        $validated = $request->validate([
            'answer_text' => 'nullable|string|max:20000',
            'submission_file' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,ppt,pptx,txt,jpg,jpeg,png',
                'max:10240',
            ],
        ]);

        if (
            blank($validated['answer_text'] ?? null)
            && ! $request->hasFile('submission_file')
        ) {
            throw ValidationException::withMessages([
                'submission' => 'Please enter an answer or attach a file before submitting.',
            ]);
        }

        $submission = Submission::where('assignment_id', $assignment->id)
            ->where('student_id', auth()->id())
            ->first();

        if ($submission && $submission->status === 'graded') {
            throw ValidationException::withMessages([
                'submission' => 'This assignment has already been graded and can no longer be changed.',
            ]);
        }

        $filePath = $submission?->file_path;

        if ($request->hasFile('submission_file')) {
            if ($filePath) {
                Storage::disk('public')->delete($filePath);
            }

            $filePath = $request->file('submission_file')
                ->store(
                    'assignment-submissions/' . auth()->id(),
                    'public'
                );
        }

        $isResubmission =
            (bool) $submission;

        if ($submission) {
            $submission->update([
                'answer_text' => $validated['answer_text'] ?? null,
                'file_path' => $filePath,
                'score' => null,
                'feedback' => null,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            $submission->refresh();
        } else {
            $submission = Submission::create([
                'assignment_id' => $assignment->id,
                'student_id' => auth()->id(),
                'answer_text' => $validated['answer_text'] ?? null,
                'file_path' => $filePath,
                'score' => null,
                'feedback' => null,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | NOTIFY TEACHER
        |--------------------------------------------------------------------------
        | Send exactly one database notification after a successful submission.
        | A resubmission gets its own clear label instead of creating duplicate
        | notifications during the same request.
        */

        $assignment->loadMissing('course.teacher');

        $teacher =
            $assignment->course?->teacher;

        $student =
            auth()->user();

        if ($teacher && $student) {
            $teacher->notify(
                new PathwiseNotification(
                    title:
                        $isResubmission
                            ? 'Assignment resubmitted'
                            : 'New assignment submission',
                    message:
                        $student->name
                        . (
                            $isResubmission
                                ? ' resubmitted "'
                                : ' submitted "'
                        )
                        . $assignment->title
                        . '".',
                    type:
                        $isResubmission
                            ? 'assignment_resubmitted'
                            : 'assignment_submitted',
                    courseId:
                        $assignment->course?->id,
                    assignmentId:
                        $assignment->id,
                    submissionId:
                        $submission->id
                )
            );
        }


        return redirect()
            ->route('student.assignments.show', $assignment)
            ->with('success', 'Assignment submitted successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | TEACHER - REVIEW / GRADE
    |--------------------------------------------------------------------------
    */

    public function teacherIndex(Assignment $assignment)
    {
        $assignment->load(['course', 'lesson']);

        abort_unless($assignment->course, 404);

        $this->ensureTeacherOwnsAssignment($assignment);

        $submissions = Submission::with('student')
            ->where('assignment_id', $assignment->id)
            ->latest('submitted_at')
            ->get();

        return view('Teacher.submissions.index', compact(
            'assignment',
            'submissions'
        ));
    }

    public function teacherShow(Submission $submission)
    {
        $submission->load([
            'assignment.course',
            'assignment.lesson',
            'student',
        ]);

        abort_unless(
            $submission->assignment && $submission->assignment->course,
            404
        );

        $this->ensureTeacherOwnsAssignment($submission->assignment);

        return view('Teacher.submissions.show', compact('submission'));
    }

    public function teacherGrade(Request $request, Submission $submission)
    {
        $submission->load([
            'assignment.course',
            'student',
        ]);

        abort_unless(
            $submission->assignment && $submission->assignment->course,
            404
        );

        $this->ensureTeacherOwnsAssignment($submission->assignment);

        $validated = $request->validate([
            'status' => 'required|in:graded,returned',
            'score' => 'nullable|integer|min:0',
            'feedback' => 'nullable|string|max:10000',
        ]);

        if ($validated['status'] === 'graded' && ! isset($validated['score'])) {
            throw ValidationException::withMessages([
                'score' => 'A score is required when marking a submission as graded.',
            ]);
        }

        if (
            isset($validated['score'])
            && $validated['score'] > $submission->assignment->max_score
        ) {
            throw ValidationException::withMessages([
                'score' => 'Score cannot exceed the assignment maximum score of '
                    . $submission->assignment->max_score
                    . '.',
            ]);
        }

        if ($validated['status'] === 'returned') {
            $validated['score'] = null;
        }

        $previousStatus =
            (string) $submission->status;

        $previousScore =
            $submission->score;

        $previousFeedback =
            (string) ($submission->feedback ?? '');

        $submission->update([
            'score' => $validated['score'] ?? null,
            'feedback' => $validated['feedback'] ?? null,
            'status' => $validated['status'],
        ]);

        $submission->refresh();


        /*
        |--------------------------------------------------------------------------
        | NOTIFY STUDENT
        |--------------------------------------------------------------------------
        | Notify only when the grading result actually changes. This avoids
        | duplicate bell notifications when the teacher submits the same
        | status, score, and feedback again.
        */

        $currentStatus =
            (string) $submission->status;

        $currentScore =
            $submission->score;

        $currentFeedback =
            (string) ($submission->feedback ?? '');

        $gradingChanged =
            $previousStatus !== $currentStatus
            ||
            (string) $previousScore !== (string) $currentScore
            ||
            $previousFeedback !== $currentFeedback;

        $student =
            $submission->student;

        if (
            $gradingChanged
            &&
            $student
        ) {
            if ($currentStatus === 'graded') {
                $student->notify(
                    new PathwiseNotification(
                        title:
                            $previousStatus === 'graded'
                                ? 'Assignment grade updated'
                                : 'Assignment graded',
                        message:
                            'Your submission for "'
                            . $submission->assignment->title
                            . '" was graded '
                            . $submission->score
                            . '/'
                            . $submission->assignment->max_score
                            . '.',
                        type:
                            $previousStatus === 'graded'
                                ? 'assignment_grade_updated'
                                : 'assignment_graded',
                        courseId:
                            $submission->assignment->course?->id,
                        assignmentId:
                            $submission->assignment->id,
                        submissionId:
                            $submission->id
                    )
                );
            }

            if ($currentStatus === 'returned') {
                $student->notify(
                    new PathwiseNotification(
                        title: 'Assignment returned for revision',
                        message:
                            'Your submission for "'
                            . $submission->assignment->title
                            . '" was returned for revision.',
                        type: 'assignment_returned',
                        courseId:
                            $submission->assignment->course?->id,
                        assignmentId:
                            $submission->assignment->id,
                        submissionId:
                            $submission->id
                    )
                );
            }
        }


        return redirect()
            ->route('teacher.submissions.show', $submission)
            ->with(
                'success',
                $validated['status'] === 'graded'
                    ? 'Submission graded successfully.'
                    : 'Submission returned to the student for revision.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    private function ensureStudentCanAccessAssignment(Assignment $assignment): void
    {
        $assignment->loadMissing('course');

        abort_unless(
            $assignment->is_published
                && $assignment->course
                && $assignment->course->status === 'published',
            404
        );

        $isEnrolled = $assignment->course
            ->enrollments()
            ->where('student_id', auth()->id())
            ->exists();

        abort_unless(
            $isEnrolled,
            403,
            'You must be enrolled in this course to submit this assignment.'
        );
    }

    private function ensureTeacherOwnsAssignment(Assignment $assignment): void
    {
        $assignment->loadMissing('course');

        abort_unless(
            $assignment->course
                && (int) $assignment->course->teacher_id === (int) auth()->id(),
            403,
            'You may only review submissions from your own courses.'
        );
    }
}
