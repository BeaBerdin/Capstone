<?php

namespace App\Http\Controllers;

use App\Exceptions\AiEvaluationException;
use App\Models\Assignment;
use App\Models\Submission;
use App\Notifications\PathwiseNotification;
use App\Services\GeminiEvaluationService;
use App\Support\AiAvailability;
use App\Support\PrivateFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SubmissionController extends Controller
{
    private const STUDENT_ENROLLMENT_MESSAGE = 'You must be enrolled in this course to submit this assignment.';

    /*
    |--------------------------------------------------------------------------
    | ADMIN - VIEW ONLY
    |--------------------------------------------------------------------------
    | Administrators can list and open submissions. They cannot create,
    | edit, grade or delete them: grading belongs to the course teacher.
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $submissions = Submission::with(['assignment', 'student'])
            ->whereHas('assignment', fn ($assignment) => $assignment->manageableBy($request->user()))
            ->latest()
            ->get();

        return view('submissions.index', compact('submissions'));
    }

    public function show(Request $request, Submission $submission)
    {
        abort_unless($submission->assignment()->manageableBy($request->user())->exists(), 403);

        $submission->load([
            'assignment.course',
            'student',
        ]);

        return view('submissions.show', compact('submission'));
    }

    /*
    |--------------------------------------------------------------------------
    | STUDENT - SUBMIT / RESUBMIT
    |--------------------------------------------------------------------------
    */

    public function studentStore(Request $request, Assignment $assignment)
    {
        $newFilePath = null;
        $obsoleteFilePath = null;

        try {
            if ($assignment->is_activity) {
                $lesson = $assignment->lesson;
                abort_unless($lesson && (int) $assignment->course_id === (int) $lesson->course_id, 404);

                $submission = DB::transaction(function () use ($request, $assignment, $lesson, &$newFilePath, &$obsoleteFilePath) {
                    $activity = Assignment::lockStudentActivity(
                        $lesson,
                        auth()->id(),
                        self::STUDENT_ENROLLMENT_MESSAGE,
                        $assignment->getKey()
                    );

                    $submission = $this->saveStudentSubmission($request, $activity, $newFilePath, $obsoleteFilePath);
                    app(LessonController::class)->completeStudentActivity($activity);

                    return $submission;
                });
            } else {
                $assignment->authorizeStudentAccess(auth()->id(), self::STUDENT_ENROLLMENT_MESSAGE);
                $submission = $this->saveStudentSubmission($request, $assignment, $newFilePath, $obsoleteFilePath);
            }
        } catch (Throwable $exception) {
            PrivateFiles::delete($newFilePath);

            throw $exception;
        }

        PrivateFiles::delete($obsoleteFilePath);

        $teacher = $assignment->course?->teacher;

        if ($teacher) {
            $teacher->notify(
                new PathwiseNotification(
                    title: $submission->wasRecentlyCreated ? 'Assignment submitted' : 'Assignment resubmitted',
                    message: 'A student submitted "' . $assignment->title . '".',
                    type: $submission->wasRecentlyCreated ? 'assignment_submitted' : 'assignment_resubmitted',
                    courseId: $assignment->course_id,
                    assignmentId: $assignment->id,
                    submissionId: $submission->id
                )
            );
        }

        return redirect()
            ->route('student.assignments.show', $assignment)
            ->with(
                'success',
                $submission->wasRecentlyCreated
                    ? 'Assignment submitted successfully.'
                    : 'Assignment resubmitted successfully.'
            );
    }

    private function saveStudentSubmission(
        Request $request,
        Assignment $assignment,
        ?string &$newFilePath,
        ?string &$obsoleteFilePath
    ): Submission {
        if (
            $assignment->due_date
            && now()->greaterThan($assignment->due_date)
        ) {
            throw ValidationException::withMessages([
                'submission' => 'The deadline for this assignment has already passed.',
            ]);
        }

        $isCodeSubmission =
            $assignment->submission_type === 'code';

        // Code assignments accept one source file in the assignment's
        // language (or .txt); other assignments accept documents and images.
        // Any approved file may be up to 25 MB.
        $fileRules = $isCodeSubmission
            ? [
                'nullable',
                'file',
                'extensions:'.implode(',', $assignment->sourceFileExtensions()),
                'max:'.Assignment::UPLOAD_MAX_KB,
            ]
            : [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,ppt,pptx,txt,jpg,jpeg,png',
                'max:'.Assignment::UPLOAD_MAX_KB,
            ];

        $validated = $request->validate([
            'answer_text' => 'nullable|string|max:20000',
            'submission_file' => $fileRules,
        ], [
            'submission_file.extensions' => 'Upload one source file of type: '
                .implode(', ', array_map(fn ($extension) => '.'.$extension, $assignment->sourceFileExtensions())).'.',
            'submission_file.max' => 'The file may not be larger than 25 MB.',
            'submission_file.uploaded' => 'The file could not be uploaded. Files may be up to 25 MB.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | VALIDATE THE STUDENT RESPONSE
        |--------------------------------------------------------------------------
        |
        | The teacher decides whether students type their answer (or paste
        | code), upload a file, or may do either.
        |
        */

        if ($isCodeSubmission && blank($assignment->programming_language)) {
            throw ValidationException::withMessages([
                'submission' =>
                    'This code assignment does not have a programming language configured.',
            ]);
        }

        // Whitespace-only text counts as no answer. Real text is kept
        // exactly as typed, including indentation and line breaks.
        if (blank($validated['answer_text'] ?? null)) {
            $validated['answer_text'] = null;
        }

        $hasText = $validated['answer_text'] !== null;
        $hasFile = $request->hasFile('submission_file');

        // Messages use the same wording as the teacher's "How Students
        // Answer" choice, e.g. "Pasted code only" or "File upload only".
        $formatRule = 'This assignment accepts: '.$assignment->answerFormatLabel().'.';

        if ($hasText && ! $assignment->acceptsTypedAnswer()) {
            throw ValidationException::withMessages([
                'answer_text' => $formatRule.' Please upload a file instead.',
            ]);
        }

        if ($hasFile && ! $assignment->acceptsFileUpload()) {
            throw ValidationException::withMessages([
                'submission_file' => $formatRule.($isCodeSubmission
                    ? ' Please paste your code instead.'
                    : ' Please type your answer instead.'),
            ]);
        }

        if (! $hasText && ! $hasFile) {
            throw ValidationException::withMessages(
                match (true) {
                    ! $assignment->acceptsFileUpload() => [
                        'answer_text' => $isCodeSubmission
                            ? 'Please paste your code before submitting.'
                            : 'Please type your answer before submitting.',
                    ],
                    ! $assignment->acceptsTypedAnswer() => [
                        'submission_file' => 'Please upload a file before submitting.',
                    ],
                    $isCodeSubmission => [
                        'answer_text' => 'Please paste your code or upload a file before submitting.',
                    ],
                    default => [
                        'submission' => 'Please type your answer or upload a file before submitting.',
                    ],
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FIND EXISTING SUBMISSION
        |--------------------------------------------------------------------------
        */

        $submission = Submission::where(
            'assignment_id',
            $assignment->id
        )
            ->where('student_id', auth()->id())
            ->when($assignment->is_activity, fn ($query) => $query->lockForUpdate())
            ->first();

        if ($submission && $submission->status === 'graded') {
            throw ValidationException::withMessages([
                'submission' =>
                    'This assignment has already been graded and can no longer be changed.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | HANDLE ATTACHMENTS
        |--------------------------------------------------------------------------
        */

        $filePath = $submission?->file_path;

        // Typed code replaces an earlier uploaded source file, so the
        // teacher never sees an old file next to new code. The same applies
        // when the assignment no longer accepts file uploads.
        if (
            ($isCodeSubmission || ! $assignment->acceptsFileUpload())
            && ! $request->hasFile('submission_file')
            && $filePath
        ) {
            $obsoleteFilePath = $filePath;
            $filePath = null;
        }

        if ($request->hasFile('submission_file')) {
            $newPath = $request->file('submission_file')->store(
                'assignment-submissions/' . $assignment->id,
                PrivateFiles::DISK
            );

            if ($newPath === false) {
                throw ValidationException::withMessages([
                    'submission_file' => 'The file could not be saved. Please try again.',
                ]);
            }

            $obsoleteFilePath = $filePath;
            $newFilePath = $filePath = $newPath;
        }

        /*
        |--------------------------------------------------------------------------
        | SAVE SUBMISSION
        |--------------------------------------------------------------------------
        |
        | Code assignments store typed source code in answer_text, or one
        | uploaded source file in file_path. No code execution is performed.
        |
        */

        $submissionData = [
            'assignment_id' => $assignment->id,
            'student_id' => auth()->id(),
            'answer_text' => $validated['answer_text'] ?? null,
            'file_path' => $filePath,
            'status' => 'submitted',
            'submitted_at' => now(),

            // Reset the previous teacher grade.
            'score' => null,
            'feedback' => null,

            // Reset the previous AI evaluation.
            'ai_score' => null,
            'ai_feedback' => null,
            'ai_strengths' => null,
            'ai_improvements' => null,
            'ai_evaluation_status' => 'not_requested',
            'ai_evaluated_at' => null,
        ];

        if ($submission) {
            $submission->update($submissionData);
            return $submission;
        }

        return Submission::create($submissionData);
    }

    /*
    |--------------------------------------------------------------------------
    | TEACHER - REVIEW SUBMISSIONS
    |--------------------------------------------------------------------------
    */

    public function teacherIndex(Assignment $assignment)
    {
        $assignment->load([
            'course',
            'lesson',
        ]);

        abort_unless($assignment->course !== null, 404);

        $this->ensureTeacherOwnsAssignment($assignment);

        $submissions = Submission::with('student')
            ->where('assignment_id', $assignment->id)
            ->latest('submitted_at')
            ->get();

        return view(
            'Teacher.submissions.index',
            compact('assignment', 'submissions')
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
    | Gemini reads the student's response or source code.
    | No code is executed.
    | The AI score remains separate from the teacher's final grade.
    |
    */

    public function aiEvaluate(
        Submission $submission,
        GeminiEvaluationService $geminiEvaluationService
    ) {
        set_time_limit(120);

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

        if (! $assignment->ai_evaluation_enabled) {
            return redirect()
                ->route('teacher.submissions.show', $submission)
                ->with(
                    'error',
                    'AI evaluation is not enabled for this assignment.'
                );
        }

        if ($submission->ai_evaluation_status === 'completed') {
            return redirect()
                ->route('teacher.submissions.show', $submission)
                ->with(
                    'error',
                    'This submission already has an AI evaluation.'
                );
        }

        if ($submission->aiEvaluationInProgress()) {
            return redirect()
                ->route('teacher.submissions.show', $submission)
                ->with(
                    'error',
                    'An AI evaluation for this submission is already in progress.'
                );
        }

        if (
            blank($submission->answer_text)
            && ! $submission->file_path
        ) {
            return redirect()
                ->route('teacher.submissions.show', $submission)
                ->with(
                    'error',
                    'There is no student response available for AI evaluation.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | PREPARE THE STUDENT RESPONSE
        |--------------------------------------------------------------------------
        |
        | Gemini only receives real text: the typed answer and/or the text of
        | an uploaded plain-text or source file. If there is nothing readable,
        | AI evaluation is not started.
        |
        */

        $fileText = null;

        if ($submission->file_path) {
            $file = $this->readSubmissionFileText($submission);

            if ($file['error'] !== null && blank($submission->answer_text)) {
                return redirect()
                    ->route('teacher.submissions.show', $submission)
                    ->with(
                        'error',
                        $file['error'].' AI evaluation was not started; please grade this submission manually.'
                    );
            }

            $fileText = $file['text'];
        }

        $studentAnswer = trim(implode("\n\n", array_filter([
            $submission->answer_text,
            $fileText !== null
                ? 'Uploaded file '.basename($submission->file_path).":\n".$fileText
                : null,
        ])));

        // Clear any earlier failed attempt before trying again.
        $submission->update([
            'ai_score' => null,
            'ai_feedback' => null,
            'ai_strengths' => null,
            'ai_improvements' => null,
            'ai_evaluated_at' => null,
            'ai_evaluation_status' => 'processing',
        ]);

        try {
            /*
            |--------------------------------------------------------------------------
            | REQUEST AI EVALUATION
            |--------------------------------------------------------------------------
            */

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
                    $assignment->programmingLanguageLabel()
            );

            /*
            |--------------------------------------------------------------------------
            | SAVE THE AI SUGGESTED EVALUATION
            |--------------------------------------------------------------------------
            */

            $submission->update([
                'ai_score' => $result['score'],

                'ai_feedback' => $result['feedback'],

                'ai_strengths' => $this->formatAiList(
                    $result['strengths']
                ),

                'ai_improvements' => $this->formatAiList(
                    $result['improvements']
                ),

                'ai_evaluation_status' => 'completed',

                'ai_evaluated_at' => now(),
            ]);

            return redirect()
                ->route('teacher.submissions.show', $submission)
                ->with(
                    'success',
                    'AI evaluation generated successfully. Review the suggested score and feedback before assigning the final grade.'
                );

        } catch (Throwable $e) {
            // Never leave a partial or invalid result: the submission is
            // kept, no AI score is stored, and the teacher can retry or
            // grade manually.
            $submission->update([
                'ai_score' => null,
                'ai_feedback' => null,
                'ai_strengths' => null,
                'ai_improvements' => null,
                'ai_evaluated_at' => null,
                'ai_evaluation_status' => 'failed',
            ]);

            // Gemini unreachable, not configured, over quota or answering
            // with an HTTP/server error: the friendly message only; the
            // details go to the log.
            if (AiAvailability::isUnavailable($e)) {
                AiAvailability::logUnavailable('AI evaluation', $e, [
                    'submission_id' => $submission->id,
                    'teacher_id' => auth()->id(),
                ]);

                return redirect()
                    ->route('teacher.submissions.show', $submission)
                    ->with(
                        'error',
                        AiAvailability::MESSAGE.' No AI score was saved. You can grade this submission manually.'
                    );
            }

            // The AI answered, but its evaluation could not be used (for
            // example unreadable or incomplete): that reason is shown.
            if (! $e instanceof AiEvaluationException) throw $e;
            AiAvailability::logUnavailable('AI evaluation', $e, ['submission_id' => $submission->id]);

            return redirect()
                ->route('teacher.submissions.show', $submission)
                ->with(
                    'error',
                    'AI evaluation failed: '.$e->getMessage()
                        .' No AI score was saved. You can retry or grade this submission manually.'
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
            'status' => 'required|in:graded,returned',
            'score' => 'nullable|integer|min:0',
            'feedback' => 'nullable|string|max:10000',
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
            && $validated['score'] > $submission->assignment->max_score
        ) {
            throw ValidationException::withMessages([
                'score' =>
                    'Score cannot exceed the assignment maximum score of '
                    . $submission->assignment->max_score . '.',
            ]);
        }

        if ($validated['status'] === 'returned') {
            $validated['score'] = null;
        }

        $previousStatus = (string) $submission->status;
        $previousScore = $submission->score;
        $previousFeedback = (string) ($submission->feedback ?? '');

        /*
        |--------------------------------------------------------------------------
        | SAVE THE TEACHER'S FINAL GRADE
        |--------------------------------------------------------------------------
        */

        $submission->update([
            'score' => $validated['score'] ?? null,
            'feedback' => $validated['feedback'] ?? null,
            'status' => $validated['status'],
        ]);

        $submission->refresh();

        /*
        |--------------------------------------------------------------------------
        | NOTIFY STUDENT IF GRADING CHANGED
        |--------------------------------------------------------------------------
        */

        $currentStatus = (string) $submission->status;
        $currentScore = $submission->score;
        $currentFeedback = (string) ($submission->feedback ?? '');

        $gradingChanged =
            $previousStatus !== $currentStatus
            || (string) $previousScore !== (string) $currentScore
            || $previousFeedback !== $currentFeedback;

        $student = $submission->student;

        if ($gradingChanged && $student) {
            if ($currentStatus === 'graded') {
                $student->notify(
                    new PathwiseNotification(
                        title: $previousStatus === 'graded'
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

                        type: $previousStatus === 'graded'
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

    private function ensureTeacherOwnsAssignment(
        Assignment $assignment
    ): void {
        $assignment->loadMissing('course');

        abort_unless(
            $assignment->course
                && (int) $assignment->course->teacher_id
                    === (int) auth()->id()
                && $assignment->hasConsistentLesson(),
            403,
            'You may only review submissions from your own courses.'
        );
    }

    /**
     * Read the text of an uploaded submission file for AI evaluation.
     *
     * Only plain-text and source files (.txt and the code-assignment file
     * types) up to Assignment::AI_TEXT_MAX_KB are read. Documents such
     * as PDF or Word files, binary files and empty files are not.
     *
     * @return array{text: ?string, error: ?string}
     */
    private function readSubmissionFileText(Submission $submission): array
    {
        $fileName = basename((string) $submission->file_path);
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $readableExtensions = array_values(array_unique([
            'txt',
            ...collect(Assignment::PROGRAMMING_LANGUAGES)->pluck('extensions')->flatten()->all(),
        ]));

        if (! in_array($extension, $readableExtensions, true)) {
            return [
                'text' => null,
                'error' => 'The uploaded file ('.$fileName.') cannot be read for AI evaluation. Only text and source files ('
                    .implode(', ', array_map(fn ($readable) => '.'.$readable, $readableExtensions)).') can be read.',
            ];
        }

        $disk = PrivateFiles::diskFor($submission->file_path);

        if (! $disk) {
            return [
                'text' => null,
                'error' => 'The uploaded file ('.$fileName.') could not be found.',
            ];
        }

        if ($disk->size($submission->file_path) > Assignment::AI_TEXT_MAX_KB * 1024) {
            return [
                'text' => null,
                'error' => 'The uploaded file ('.$fileName.') is larger than '.Assignment::AI_TEXT_MAX_KB.' KB and cannot be read for AI evaluation.',
            ];
        }

        $contents = (string) $disk->get($submission->file_path);

        // Drop a UTF-8 byte-order mark.
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);

        if (! mb_check_encoding($contents, 'UTF-8') || str_contains($contents, "\0")) {
            return [
                'text' => null,
                'error' => 'The uploaded file ('.$fileName.') is not readable text.',
            ];
        }

        if (trim($contents) === '') {
            return [
                'text' => null,
                'error' => 'The uploaded file ('.$fileName.') is empty.',
            ];
        }

        return [
            'text' => $contents,
            'error' => null,
        ];
    }

    /**
     * @param  array<int|string, mixed>  $items
     */
    private function formatAiList(array $items): string
    {
        if (empty($items)) {
            return '';
        }

        return collect($items)
            ->map(fn ($item) => '• ' . trim((string) $item))
            ->implode("\n");
    }
}
