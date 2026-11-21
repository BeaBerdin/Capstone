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

    public function index(Request $request)
    {
        $assignments = Assignment::with(['course', 'lesson'])
            ->manageableBy($request->user())
            ->latest()
            ->get();

        return view('assignments.index', compact('assignments'));
    }

    public function create(Request $request)
    {
        $courses = Course::manageableBy($request->user())->get();
        $lessons = Lesson::whereIn('course_id', $courses->modelKeys())->get();

        return view('assignments.create', compact(
            'courses',
            'lessons'
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validateAssignment($request);

        Course::authorizeManagement($request->user(), $validated['course_id']);

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

    public function show(Request $request, Assignment $assignment)
    {
        abort_unless(Assignment::manageableBy($request->user())->whereKey($assignment)->exists(), 403);

        $assignment->load([
            'course',
            'lesson',
        ]);

        return view(
            'assignments.show',
            compact('assignment')
        );
    }

    public function edit(Request $request, Assignment $assignment)
    {
        abort_unless(Assignment::manageableBy($request->user())->whereKey($assignment)->exists(), 403);
        abort_if($assignment->is_activity, 403, 'Manage this Activity from its teacher lesson editor.');

        $courses = Course::manageableBy($request->user())->get();
        $lessons = Lesson::whereIn('course_id', $courses->modelKeys())->get();

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
        abort_unless(Assignment::manageableBy($request->user())->whereKey($assignment)->exists(), 403);
        abort_if($assignment->is_activity, 403, 'Manage this Activity from its teacher lesson editor.');

        $validated = $this->validateAssignment($request);

        Course::authorizeManagement($request->user(), $validated['course_id']);

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

    public function destroy(Request $request, Assignment $assignment)
    {
        abort_unless(Assignment::manageableBy($request->user())->whereKey($assignment)->exists(), 403);
        abort_if($assignment->is_activity, 403, 'Manage this Activity from its teacher lesson editor.');

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
            ->withConsistentLesson()
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
                Course::ASSIGNMENT_EDITABLE_STATUSES
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
            $assignment->course !== null,
            404
        );

        $this->ensureTeacherOwnsCourse(
            $assignment->course
        );

        $this->ensureTeacherCanEditCourse(
            $assignment->course
        );

        if ($assignment->is_activity && $assignment->lesson) {
            return redirect()->route('teacher.lessons.edit', $assignment->lesson);
        }

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
            $assignment->course !== null,
            404
        );

        $this->ensureTeacherOwnsCourse(
            $assignment->course
        );

        $this->ensureTeacherCanEditCourse(
            $assignment->course
        );

        abort_unless(! $assignment->is_activity, 403, 'Manage this Activity from its lesson editor.');

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
            $assignment->course !== null,
            404
        );

        $this->ensureTeacherOwnsCourse(
            $assignment->course
        );

        $this->ensureTeacherCanEditCourse(
            $assignment->course
        );

        abort_unless(! $assignment->is_activity, 403, 'Delete this Activity from its lesson editor.');

        abort_unless($assignment->hasConsistentLesson(), 403);

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
        $assignments = Assignment::with([
                'course.teacher',
                'lesson',
            ])
            ->publishedForStudents()
            ->forEnrolledStudent(auth()->id())
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
        $assignment->authorizeStudentAccess(
            auth()->id(),
            'You must be enrolled in this course to access this assignment.'
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

    /**
     * @return array<string, mixed>
     */
    private function validateAssignment(
        Request $request
    ): array {
        $validated = $request->validate([

            'course_id' => [
                'required',
                'integer',
                'exists:courses,id',
            ],

            'lesson_id' => [
                'nullable',
                'integer',
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
                Rule::in(Assignment::ANSWER_FORMATS),
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


    /**
     * @return array<string, mixed>
     */
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
                Rule::in(Assignment::ANSWER_FORMATS),
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

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
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

    /**
     * @param  array<string, mixed>  $validated
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
            $course->allowsAssignmentChanges(),

            403,

            'Assignments can only be managed for Draft, Rejected, or Published courses.'
        );
    }


}
