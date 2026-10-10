<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use App\Notifications\PathwiseNotification;
use App\Services\GeminiEvaluationService;
use App\Services\CodeExecutionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

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

        if (
            isset($validated['score'])
            && $validated['score'] > $assignment->max_score
        ) {
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
        $submission->load([
            'assignment.course',
            'student',
        ]);

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

        if (
            isset($validated['score'])
            && $validated['score'] > $assignment->max_score
        ) {
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

        $isCodeSubmission =
            $assignment->submission_type === 'code';

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'answer_text' => 'nullable|string|max:20000',

            'submission_file' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,ppt,pptx,txt,jpg,jpeg,png',
                'max:10240',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | CODE SUBMISSION
        |--------------------------------------------------------------------------
        |
        | For code assignments, the student's source code is stored in
        | answer_text. The programming language comes from the assignment,
        | not from the student, so students cannot change the expected language.
        |
        */

        $codeLanguage = null;

        if ($isCodeSubmission) {
            $codeLanguage = $assignment->programming_language;

            if (blank($codeLanguage)) {
                throw ValidationException::withMessages([
                    'submission' =>
                        'This code assignment does not have a programming language configured.',
                ]);
            }

            if (blank($validated['answer_text'] ?? null)) {
                throw ValidationException::withMessages([
                    'answer_text' =>
                        'Please enter your code before submitting.',
                ]);
            }
        } else {
            /*
            |----------------------------------------------------------------------
            | ESSAY / WRITTEN SUBMISSION
            |----------------------------------------------------------------------
            */

            if (
                blank($validated['answer_text'] ?? null)
                && ! $request->hasFile('submission_file')
            ) {
                throw ValidationException::withMessages([
                    'submission' =>
                        'Please enter an answer or attach a file before submitting.',
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FIND EXISTING SUBMISSION
        |--------------------------------------------------------------------------
        */

        $submission = Submission::where('assignment_id', $assignment->id)
            ->where('student_id', auth()->id())
            ->first();

        if ($submission && $submission->status === 'graded') {
            throw ValidationException::withMessages([
                'submission' =>
                    'This assignment has already been graded and can no longer be changed.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | FILE HANDLING
        |--------------------------------------------------------------------------
        */

        $filePath = $submission?->file_path;

        /*
        | Code submissions do not need a file.
        | If an old file exists from a previous submission, remove it.
        */

        if ($isCodeSubmission && $filePath) {
            Storage::disk('public')->delete($filePath);
            $filePath = null;
        }

        if (
            ! $isCodeSubmission
            && $request->hasFile('submission_file')
        ) {
            if ($filePath) {
                Storage::disk('public')->delete($filePath);
            }

            $filePath = $request->file('submission_file')
                ->store(
                    'assignment-submissions/' . $assignment->id,
                    'public'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | SAVE SUBMISSION
        |--------------------------------------------------------------------------
        */

        $submissionData = [
            'assignment_id' => $assignment->id,
            'student_id' => auth()->id(),

            'answer_text' =>
                $validated['answer_text'] ?? null,

            'file_path' => $filePath,

            'status' => 'submitted',

            'submitted_at' => now(),

            /*
            | Reset teacher/AI evaluation when the student resubmits.
            */

            'score' => null,
            'feedback' => null,

            'ai_score' => null,
            'ai_feedback' => null,
            'ai_strengths' => null,
            'ai_improvements' => null,
            'ai_evaluation_status' => 'not_requested',
            'ai_evaluated_at' => null,

            /*
            | Code-related fields.
            */

            'code_language' => $codeLanguage,
            'execution_output' => null,
            'execution_error' => null,
            'execution_status' => null,
        ];

        if ($submission) {
            $submission->update($submissionData);
            $submission->refresh();

            $notificationTitle = 'Assignment resubmitted';

            $notificationType = 'assignment_resubmitted';
        } else {
            $submission = Submission::create($submissionData);

            $notificationTitle = 'Assignment submitted';

            $notificationType = 'assignment_submitted';
        }

        /*
        |--------------------------------------------------------------------------
        | NOTIFY TEACHER
        |--------------------------------------------------------------------------
        */

        $teacher = $assignment->course?->teacher;

        if ($teacher) {
            $teacher->notify(
                new PathwiseNotification(
                    title: $notificationTitle,
                    message:
                        'A student submitted "' .
                        $assignment->title .
                        '".',
                    type: $notificationType,
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
            ->route(
                'student.assignments.show',
                $assignment
            )
            ->with(
                'success',
                $submission->wasRecentlyCreated
                    ? 'Assignment submitted successfully.'
                    : 'Assignment resubmitted successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | TEACHER - REVIEW / GRADE
    |--------------------------------------------------------------------------
    */

    public function teacherIndex(Assignment $assignment)
    {
        $assignment->load([
            'course',
            'lesson',
        ]);

        abort_unless(
            $assignment->course,
            404
        );

        $this->ensureTeacherOwnsAssignment($assignment);

        $submissions = Submission::with('student')
            ->where('assignment_id', $assignment->id)
            ->latest('submitted_at')
            ->get();

        return view(
            'Teacher.submissions.index',
            compact(
                'assignment',
                'submissions'
            )
        );
    }

    public function teacherShow(Submission $submission)
    {
        $submission->load([
            'assignment.course',
            'assignment.lesson',
            'student',
        ]);

        abort_unless(
            $submission->assignment
                && $submission->assignment->course,
            404
        );

        $this->ensureTeacherOwnsAssignment(
            $submission->assignment
        );

        return view(
            'Teacher.submissions.show',
            compact('submission')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TEACHER - AI EVALUATION
    |--------------------------------------------------------------------------
    |
    | Generates an AI SUGGESTED evaluation.
    |
    | The AI score is stored separately from the teacher's final score.
    | The teacher still decides the final grade.
    |
    */

    public function aiEvaluate(
        Submission $submission,
        GeminiEvaluationService $geminiEvaluationService
    ) {
        $submission->load([
            'assignment.course',
            'assignment.lesson',
            'student',
        ]);

        abort_unless(
            $submission->assignment
                && $submission->assignment->course,
            404
        );

        $this->ensureTeacherOwnsAssignment(
            $submission->assignment
        );

        $assignment = $submission->assignment;

        /*
        |--------------------------------------------------------------------------
        | Check whether AI evaluation is enabled.
        |--------------------------------------------------------------------------
        */

        if (! $assignment->ai_evaluation_enabled) {
            return redirect()
                ->route(
                    'teacher.submissions.show',
                    $submission
                )
                ->with(
                    'error',
                    'AI evaluation is not enabled for this assignment.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Check whether there is something to evaluate.
        |--------------------------------------------------------------------------
        */

        if (
            blank($submission->answer_text)
            && ! $submission->file_path
        ) {
            return redirect()
                ->route(
                    'teacher.submissions.show',
                    $submission
                )
                ->with(
                    'error',
                    'There is no student response available for AI evaluation.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Mark evaluation as processing.
        |--------------------------------------------------------------------------
        */

        $submission->update([
            'ai_evaluation_status' => 'processing',
        ]);

        try {
            /*
            |--------------------------------------------------------------------------
            | For now, the evaluator uses answer_text directly.
            |
            | File-content extraction and secure code execution will be
            | added separately.
            |--------------------------------------------------------------------------
            */

            $studentAnswer = $submission->answer_text;

            if (
                blank($studentAnswer)
                && $submission->file_path
            ) {
                $studentAnswer =
                    'The student submitted a file: '
                    . $submission->file_path
                    . '. File content extraction is not yet configured.';
            }

            $result = $geminiEvaluationService->evaluate(
                submissionType:
                    (string) $assignment->submission_type,

                assignmentTitle:
                    (string) $assignment->title,

                assignmentDescription:
                    (string) $assignment->description,

                studentAnswer:
                    $studentAnswer,

                evaluationCriteria:
                    $assignment->evaluation_criteria,

                maxScore:
                    (int) $assignment->max_score,

                codeLanguage:
                     $assignment->programming_language,

                executionOutput:
                    $submission->execution_output,

                executionError:
                    $submission->execution_error,

                executionStatus:
                    $submission->execution_status
            );

            /*
            |--------------------------------------------------------------------------
            | Save AI SUGGESTED evaluation.
            |--------------------------------------------------------------------------
            */

            $submission->update([
                'ai_score' =>
                    $result['score'],

                'ai_feedback' =>
                    $result['feedback'],

                'ai_strengths' =>
                    $this->formatAiList(
                        $result['strengths']
                    ),

                'ai_improvements' =>
                    $this->formatAiList(
                        $result['improvements']
                    ),

                'ai_evaluation_status' =>
                    'completed',

                'ai_evaluated_at' =>
                    now(),
            ]);

            $submission->refresh();

            return redirect()
                ->route(
                    'teacher.submissions.show',
                    $submission
                )
                ->with(
                    'success',
                    'AI evaluation generated successfully. Review the suggested score and feedback before assigning the final grade.'
                );
        } catch (Throwable $e) {
            /*
            |--------------------------------------------------------------------------
            | Do not destroy the submission if Gemini fails.
            |--------------------------------------------------------------------------
            */

            $submission->update([
                'ai_evaluation_status' => 'failed',
            ]);

            report($e);

            return redirect()
                ->route(
                    'teacher.submissions.show',
                    $submission
                )
                ->with(
                    'error',
                    'AI evaluation could not be generated. Please check your Gemini API configuration and try again.'
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | STUDENT - RUN CODE
    |--------------------------------------------------------------------------
    |
    | Lets the student execute their own code submission (code assignments
    | only) and stores the output, error, and status on the submission.
    |
    */

    public function runCode(
        Request $request,
        Submission $submission,
        CodeExecutionService $codeExecutionService
    ) {
        if ($submission->student_id !== auth()->id()) {
            abort(403);
        }

        $assignment = $submission->assignment;

        if (! $assignment) {
            abort(404);
        }

        if ($assignment->submission_type !== 'code') {
            return back()->with(
                'error',
                'Code execution is only available for code assignments.'
            );
        }

        if (blank($submission->answer_text)) {
            return back()->with(
                'error',
                'Please submit your code before running it.'
            );
        }

        $language =
            $assignment->programming_language
            ?? $submission->code_language;

        if (blank($language)) {
            return back()->with(
                'error',
                'No programming language is configured for this assignment.'
            );
        }

        try {
            $result = $codeExecutionService->execute(
                $submission->answer_text,
                $language
            );

            $submission->update([
                'execution_status' =>
                    $result['status'],

                'execution_output' =>
                    $result['output'] ?: null,

                'execution_error' =>
                    $result['error'] ?: null,
            ]);

            if ($result['status'] === 'success') {
                return back()->with(
                    'success',
                    'Code executed successfully.'
                );
            }

            return back()->with(
                'error',
                'Code execution completed with an error.'
            );

        } catch (\Throwable $e) {

            $submission->update([
                'execution_status' => 'failed',
                'execution_output' => null,
                'execution_error' => $e->getMessage(),
            ]);

            return back()->with(
                'error',
                'Unable to execute the code.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | TEACHER - FINAL GRADING
    |--------------------------------------------------------------------------
    */

    public function teacherGrade(
        Request $request,
        Submission $submission
    ) {
        $submission->load([
            'assignment.course',
            'student',
        ]);

        abort_unless(
            $submission->assignment
                && $submission->assignment->course,
            404
        );

        $this->ensureTeacherOwnsAssignment(
            $submission->assignment
        );

        $validated = $request->validate([
            'status' =>
                'required|in:graded,returned',

            'score' =>
                'nullable|integer|min:0',

            'feedback' =>
                'nullable|string|max:10000',
        ]);

        if (
            $validated['status'] === 'graded'
            && ! isset($validated['score'])
        ) {
            throw ValidationException::withMessages([
                'score' =>
                    'A score is required when marking a submission as graded.',
            ]);
        }

        if (
            isset($validated['score'])
            && $validated['score']
                > $submission->assignment->max_score
        ) {
            throw ValidationException::withMessages([
                'score' =>
                    'Score cannot exceed the assignment maximum score of '
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

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT:
        | This score is the TEACHER'S FINAL SCORE.
        |
        | We intentionally do not overwrite ai_score.
        |--------------------------------------------------------------------------
        */

        $submission->update([
            'score' =>
                $validated['score'] ?? null,

            'feedback' =>
                $validated['feedback'] ?? null,

            'status' =>
                $validated['status'],
        ]);

        $submission->refresh();

        /*
        |--------------------------------------------------------------------------
        | NOTIFY STUDENT
        |--------------------------------------------------------------------------
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
            (string) $previousScore
                !== (string) $currentScore
            ||
            $previousFeedback
                !== $currentFeedback;

        $student =
            $submission->student;

        if (
            $gradingChanged
            && $student
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
                            $submission->assignment
                                ->course?->id,

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
                        title:
                            'Assignment returned for revision',

                        message:
                            'Your submission for "'
                            . $submission->assignment->title
                            . '" was returned for revision.',

                        type:
                            'assignment_returned',

                        courseId:
                            $submission->assignment
                                ->course?->id,

                        assignmentId:
                            $submission->assignment->id,

                        submissionId:
                            $submission->id
                    )
                );
            }
        }

        return redirect()
            ->route(
                'teacher.submissions.show',
                $submission
            )
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

    private function ensureStudentCanAccessAssignment(
        Assignment $assignment
    ): void {
        $assignment->loadMissing('course');

        abort_unless(
            $assignment->is_published
                && $assignment->course
                && $assignment->course->status === 'published',
            404
        );

        $isEnrolled = $assignment->course
            ->enrollments()
            ->where(
                'student_id',
                auth()->id()
            )
            ->exists();

        abort_unless(
            $isEnrolled,
            403,
            'You must be enrolled in this course to submit this assignment.'
        );
    }

    private function ensureTeacherOwnsAssignment(
        Assignment $assignment
    ): void {
        $assignment->loadMissing('course');

        abort_unless(
            $assignment->course
                && (int) $assignment->course->teacher_id
                    === (int) auth()->id(),
            403,
            'You may only review submissions from your own courses.'
        );
    }

    private function formatAiList(
        array $items
    ): string {
        if (empty($items)) {
            return '';
        }

        return collect($items)
            ->map(
                fn ($item) =>
                    '• ' . trim((string) $item)
            )
            ->implode("\n");
    }
}