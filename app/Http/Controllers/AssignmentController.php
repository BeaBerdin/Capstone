<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Submission;
use App\Notifications\PathwiseNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssignmentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ADMIN - EXISTING CRUD
    |--------------------------------------------------------------------------
    */

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

        return view('assignments.create', compact(
            'courses',
            'lessons'
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validateAssignment($request);

        $this->ensureLessonBelongsToCourse($validated);

        $validated = $this->normalizeAiEvaluationFields(
            $validated
        );

        $assignment = Assignment::create($validated);

        $this->notifyEnrolledStudentsAboutPublishedAssignment(
            $assignment
        );

        return redirect()
            ->route('assignments.index')
            ->with('success', 'Assignment created successfully.');
    }

    public function show(Assignment $assignment)
    {
        $assignment->load([
            'course',
            'lesson',
        ]);

        return view(
            'assignments.show',
            compact('assignment')
        );
    }

    public function edit(Assignment $assignment)
    {
        $courses = Course::all();
        $lessons = Lesson::all();

        return view(
            'assignments.edit',
            compact(
                'assignment',
                'courses',
                'lessons'
            )
        );
    }

    public function update(
        Request $request,
        Assignment $assignment
    ) {
        $validated = $this->validateAssignment($request);

        $this->ensureLessonBelongsToCourse($validated);

        $validated = $this->normalizeAiEvaluationFields(
            $validated
        );

        $wasPublished = (bool) $assignment->is_published;

        $previousCourseId = (int) $assignment->course_id;

        $assignment->update($validated);

        $assignment->refresh();

        $shouldNotifyStudents =
            (bool) $assignment->is_published
            &&
            (
                ! $wasPublished
                ||
                $previousCourseId !== (int) $assignment->course_id
            );

        if ($shouldNotifyStudents) {
            $this->notifyEnrolledStudentsAboutPublishedAssignment(
                $assignment
            );
        }

        return redirect()
            ->route('assignments.index')
            ->with(
                'success',
                'Assignment updated successfully.'
            );
    }

    public function destroy(Assignment $assignment)
    {
        $assignment->delete();

        return redirect()
            ->route('assignments.index')
            ->with(
                'success',
                'Assignment deleted successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | TEACHER - ASSIGNMENTS
    |--------------------------------------------------------------------------
    */

    public function teacherIndex()
    {
        $assignments = Assignment::with([
                'course',
                'lesson',
            ])
            ->whereHas('course', function ($query) {
                $query->where(
                    'teacher_id',
                    auth()->id()
                );
            })
            ->latest()
            ->get();

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        | Teachers can now create assignments for:
        |
        | - Draft courses
        | - Rejected courses
        | - Published courses
        |
        */

        $courses = Course::where(
                'teacher_id',
                auth()->id()
            )
            ->whereIn(
                'status',
                [
                    'draft',
                    'rejected',
                    'published',
                ]
            )
            ->orderBy('title')
            ->get();

        return view(
            'Teacher.assignments.index',
            compact(
                'assignments',
                'courses'
            )
        );
    }

    public function teacherCreate(Course $course)
    {
        $this->ensureTeacherOwnsCourse($course);

        $this->ensureTeacherCanEditCourse($course);

        $lessons = $course->lessons()
            ->orderBy('title')
            ->get();

        return view(
            'Teacher.assignments.create',
            compact(
                'course',
                'lessons'
            )
        );
    }

    public function teacherStore(
        Request $request,
        Course $course
    ) {
        $this->ensureTeacherOwnsCourse($course);

        $this->ensureTeacherCanEditCourse($course);

        $validated = $this->validateTeacherAssignment(
            $request,
            $course
        );

        $validated['course_id'] = $course->id;

        $assignment = Assignment::create($validated);

        $this->notifyEnrolledStudentsAboutPublishedAssignment(
            $assignment
        );

        return redirect()
            ->route('teacher.assignments.index')
            ->with(
                'success',
                'Assignment created successfully.'
            );
    }

    public function teacherEdit(
        Assignment $assignment
    ) {
        $assignment->load('course');

        abort_unless(
            $assignment->course,
            404
        );

        $this->ensureTeacherOwnsCourse(
            $assignment->course
        );

        $this->ensureTeacherCanEditCourse(
            $assignment->course
        );

        $course = $assignment->course;

        $lessons = $course->lessons()
            ->orderBy('title')
            ->get();

        return view(
            'Teacher.assignments.edit',
            compact(
                'assignment',
                'course',
                'lessons'
            )
        );
    }

    public function teacherUpdate(
        Request $request,
        Assignment $assignment
    ) {
        $assignment->load('course');

        abort_unless(
            $assignment->course,
            404
        );

        $this->ensureTeacherOwnsCourse(
            $assignment->course
        );

        $this->ensureTeacherCanEditCourse(
            $assignment->course
        );

        $validated = $this->validateTeacherAssignment(
            $request,
            $assignment->course
        );

        $wasPublished = (bool) $assignment->is_published;

        $assignment->update($validated);

        $assignment->refresh();

        if (
            ! $wasPublished
            &&
            (bool) $assignment->is_published
        ) {
            $this->notifyEnrolledStudentsAboutPublishedAssignment(
                $assignment
            );
        }

        return redirect()
            ->route('teacher.assignments.index')
            ->with(
                'success',
                'Assignment updated successfully.'
            );
    }

    public function teacherDestroy(
        Assignment $assignment
    ) {
        $assignment->load('course');

        abort_unless(
            $assignment->course,
            404
        );

        $this->ensureTeacherOwnsCourse(
            $assignment->course
        );

        $this->ensureTeacherCanEditCourse(
            $assignment->course
        );

        $assignment->delete();

        return redirect()
            ->route('teacher.assignments.index')
            ->with(
                'success',
                'Assignment deleted successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | STUDENT - ASSIGNMENTS
    |--------------------------------------------------------------------------
    */

    public function studentIndex()
    {
        $studentId = auth()->id();

        $assignments = Assignment::with([
                'course.teacher',
                'lesson',
            ])
            ->where(
                'is_published',
                true
            )
            ->whereHas(
                'course',
                function ($query) use ($studentId) {

                    $query->where(
                        'status',
                        'published'
                    )
                    ->whereHas(
                        'enrollments',
                        function ($enrollmentQuery) use (
                            $studentId
                        ) {

                            $enrollmentQuery->where(
                                'student_id',
                                $studentId
                            );
                        }
                    );
                }
            )
            ->latest()
            ->get();

        return view(
            'Student.assignments.index',
            compact('assignments')
        );
    }

    public function studentShow(
        Assignment $assignment
    ) {
        $this->ensureStudentCanAccessAssignment(
            $assignment
        );

        $assignment->load([
            'course.teacher',
            'lesson',
        ]);

        $submission = Submission::where(
                'assignment_id',
                $assignment->id
            )
            ->where(
                'student_id',
                auth()->id()
            )
            ->first();

        return view(
            'Student.assignments.show',
            compact(
                'assignment',
                'submission'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    private function validateAssignment(
        Request $request
    ): array {
        $validated = $request->validate([

            'course_id' => [
                'required',
                'exists:courses,id',
            ],

            'lesson_id' => [
                'nullable',
                'exists:lessons,id',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'submission_type' => [
                'required',
                Rule::in([
                    'essay',
                    'code',
                    'multiple_choice',
                ]),
            ],

            'answer_format' => [
                'nullable',
                Rule::in(array_keys(Assignment::ANSWER_FORMATS)),
            ],

            'evaluation_criteria' => [
                'nullable',
                'string',
            ],

            'programming_language' => [
                'nullable',
                Rule::in(array_keys(Assignment::PROGRAMMING_LANGUAGES)),
            ],

            'ai_evaluation_enabled' => [
                'nullable',
                'boolean',
            ],

            'due_date' => [
                'nullable',
                'date',
            ],

            'max_score' => [
                'required',
                'integer',
                'min:1',
                'max:10000',
            ],

            'is_published' => [
                'required',
                'boolean',
            ],
        ]);

        $validated['ai_evaluation_enabled'] =
            $request->boolean(
                'ai_evaluation_enabled'
            );

        // The admin form has no answer-format field: keep the current value,
        // or allow either a typed answer or a file for new assignments.
        if (! isset($validated['answer_format'])) {
            unset($validated['answer_format']);
        }

        return $validated;
    }


    private function validateTeacherAssignment(
        Request $request,
        Course $course
    ): array {
        $validated = $request->validate([

            'lesson_id' => [
                'nullable',

                Rule::exists(
                    'lessons',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'course_id',
                            $course->id
                        )
                ),
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'submission_type' => [
                'required',
                Rule::in([
                    'essay',
                    'code',
                    'multiple_choice',
                ]),
            ],

            'answer_format' => [
                'nullable',
                Rule::in(array_keys(Assignment::ANSWER_FORMATS)),
            ],

            'evaluation_criteria' => [
                'nullable',
                'string',
            ],

            'programming_language' => [
                'nullable',
                Rule::in(array_keys(Assignment::PROGRAMMING_LANGUAGES)),
            ],

            'ai_evaluation_enabled' => [
                'nullable',
                'boolean',
            ],

            'due_date' => [
                'nullable',
                'date',
            ],

            'max_score' => [
                'required',
                'integer',
                'min:1',
                'max:10000',
            ],

            'is_published' => [
                'nullable',
                'boolean',
            ],
        ]);

        $validated['is_published'] =
            $request->boolean(
                'is_published'
            );

        $validated =
            $this->normalizeAiEvaluationFields(
                $validated
            );

        return $validated;
    }


    /*
    |--------------------------------------------------------------------------
    | AI EVALUATION FIELD NORMALIZATION
    |--------------------------------------------------------------------------
    */

    private function normalizeAiEvaluationFields(
        array $validated
    ): array {

        $submissionType =
            $validated['submission_type']
            ?? 'essay';

        $aiEnabled =
            (bool) (
                $validated['ai_evaluation_enabled']
                ?? false
            );

        // An unticked checkbox sends nothing; store false explicitly so
        // editing an assignment can switch AI evaluation off.
        $validated['ai_evaluation_enabled'] = $aiEnabled;

        // Forms without the answer-format field keep the existing behaviour
        // (typed answer or file upload).
        $validated['answer_format'] = $validated['answer_format'] ?? 'either';

        /*
        |--------------------------------------------------------------------------
        | Multiple Choice
        |--------------------------------------------------------------------------
        | Multiple choice should be evaluated deterministically
        | by the system rather than Gemini.
        */

        if (
            $submissionType ===
            'multiple_choice'
        ) {
            $validated[
                'ai_evaluation_enabled'
            ] = false;

            $validated[
                'evaluation_criteria'
            ] = null;

            $validated[
                'programming_language'
            ] = null;

            return $validated;
        }


        /*
        |--------------------------------------------------------------------------
        | Essay / Written Response
        |--------------------------------------------------------------------------
        */

        if (
            $submissionType ===
            'essay'
        ) {
            $validated[
                'programming_language'
            ] = null;
        }


        /*
        |--------------------------------------------------------------------------
        | Code Submission
        |--------------------------------------------------------------------------
        */

        if (
            $submissionType ===
            'code'
            &&
            $aiEnabled
            &&
            empty(
                $validated['programming_language']
            )
        ) {
            abort(
                422,
                'A programming language is required when AI evaluation is enabled for code submissions.'
            );
        }

        return $validated;
    }


    /*
    |--------------------------------------------------------------------------
    | LESSON VALIDATION
    |--------------------------------------------------------------------------
    */

    private function ensureLessonBelongsToCourse(
        array $validated
    ): void {
        if (
            empty(
                $validated['lesson_id']
            )
        ) {
            return;
        }

        $lessonBelongsToCourse =
            Lesson::whereKey(
                $validated['lesson_id']
            )
            ->where(
                'course_id',
                $validated['course_id']
            )
            ->exists();

        abort_unless(
            $lessonBelongsToCourse,
            422,
            'Selected lesson does not belong to the selected course.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | NOTIFY ENROLLED STUDENTS
    |--------------------------------------------------------------------------
    */

    private function notifyEnrolledStudentsAboutPublishedAssignment(
        Assignment $assignment
    ): void {
        $assignment->loadMissing('course');

        if (
            ! (bool) $assignment->is_published
            ||
            ! $assignment->course
            ||
            $assignment->course->status !== 'published'
        ) {
            return;
        }

        $students = $assignment
            ->course
            ->enrollments()
            ->with('student')
            ->get()
            ->pluck('student')
            ->filter()
            ->unique('id');

        foreach ($students as $student) {

            $student->notify(
                new PathwiseNotification(
                    title:
                        'New assignment available',

                    message:
                        '"' .
                        $assignment->title .
                        '" is now available in ' .
                        $assignment->course->title .
                        '.',

                    type:
                        'assignment_published',

                    courseId:
                        $assignment->course->id,

                    assignmentId:
                        $assignment->id
                )
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | TEACHER AUTHORIZATION
    |--------------------------------------------------------------------------
    */

    private function ensureTeacherOwnsCourse(
        Course $course
    ): void {
        abort_unless(
            (int) $course->teacher_id ===
            (int) auth()->id(),

            403,

            'You may only manage assignments for your own courses.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | COURSE STATUS
    |--------------------------------------------------------------------------
    |
    | Teachers can manage assignments for Draft,
    | Rejected, AND Published courses.
    |
    */

    private function ensureTeacherCanEditCourse(
        Course $course
    ): void {
        abort_unless(
            in_array(
                $course->status,
                [
                    'draft',
                    'rejected',
                    'published',
                ],
                true
            ),

            403,

            'Assignments can only be managed for Draft, Rejected, or Published courses.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STUDENT ACCESS
    |--------------------------------------------------------------------------
    */

    private function ensureStudentCanAccessAssignment(
        Assignment $assignment
    ): void {
        $assignment->loadMissing('course');

        abort_unless(
            $assignment->is_published
                &&
                $assignment->course
                &&
                $assignment->course->status ===
                    'published',

            404
        );

        $isEnrolled =
            $assignment
                ->course
                ->enrollments()
                ->where(
                    'student_id',
                    auth()->id()
                )
                // Cancelled enrollments no longer give access.
                ->whereIn(
                    'status',
                    \App\Models\Enrollment::ACCESS_STATUSES
                )
                ->exists();

        abort_unless(
            $isEnrolled,
            403,
            'You must be enrolled in this course to access this assignment.'
        );
    }
}