<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Certificate;
use App\Models\AIRecommendation;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\LessonSection;
use App\Models\Assignment;
use App\Support\PrivateFiles;
use App\Support\ReadingContent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class LessonController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ADMIN - LESSONS
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $lessons = Lesson::with('course')
            ->whereHas('course', fn ($course) => $course->manageableBy($request->user()))
            ->orderBy('lesson_order')
            ->get();

        return view('Lessons.index', compact('lessons'));
    }


    public function create(Request $request)
    {
        $courses = Course::manageableBy($request->user())->orderBy('title')->get();

        return view('Lessons.create', compact('courses'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'title' => 'required|max:255',
            'content' => 'nullable',
            'lesson_type' => 'required|in:video,document,text,quiz',
            'video_url' => 'nullable|max:500',
            'lesson_order' => 'required|integer|min:1',
            'duration_minutes' => 'nullable|integer|min:1',
            'is_preview' => 'nullable',
            'is_published' => 'nullable',
        ]);

        Course::authorizeManagement($request->user(), $validated['course_id']);

        Lesson::create([
            'course_id' => $validated['course_id'],
            'title' => $request->title,
            'content' => $request->content,
            'lesson_type' => $request->lesson_type,
            'video_url' => $request->video_url,
            'lesson_order' => $request->lesson_order,
            'duration_minutes' => $request->duration_minutes,
            'is_preview' => $request->has('is_preview'),
            'is_published' => $request->has('is_published'),
        ]);

        return redirect()
            ->route('lessons.index')
            ->with('success', 'Lesson created successfully.');
    }


    public function edit(Request $request, Lesson $lesson)
    {
        Course::authorizeManagement($request->user(), $lesson->course_id);
        abort_if($lesson->lesson_type === 'activity', 403, 'Manage this Activity from its teacher lesson editor.');

        $courses = Course::manageableBy($request->user())->orderBy('title')->get();

        return view(
            'Lessons.edit',
            compact('lesson', 'courses')
        );
    }


    public function update(Request $request, Lesson $lesson)
    {
        Course::authorizeManagement($request->user(), $lesson->course_id);
        abort_if($lesson->lesson_type === 'activity', 403, 'Manage this Activity from its teacher lesson editor.');

        $validated = $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'title' => 'required|max:255',
            'content' => 'nullable|string',
            'lesson_type' => 'required|in:video,document,text,quiz',
            'video_url' => 'nullable|max:500',
            'lesson_order' => 'required|integer|min:1',
            'duration_minutes' => 'nullable|integer|min:1',
            'is_preview' => 'nullable',
            'is_published' => 'nullable',
        ]);

        $destination = (int) $validated['course_id'];
        Course::authorizeManagement($request->user(), $destination);

        $paths = DB::transaction(function () use ($request, $lesson, $destination) {
            $lesson = Lesson::query()->lockForUpdate()->findOrFail($lesson->id);
            Course::authorizeManagement($request->user(), $lesson->course_id);
            abort_if($lesson->lesson_type === 'activity', 403, 'Manage this Activity from its teacher lesson editor.');
            if ($destination !== (int) $lesson->course_id) {
                abort_if(
                    $lesson->hasReferencesOutsideCourse($destination),
                    422,
                    'Detach the linked quizzes and assignments before moving this lesson.'
                );
            }

            $this->guardReadingSectionConversion($lesson, (string) $request->lesson_type);
            $previousContent = $lesson->content;
            $lesson->update([
                'course_id' => $destination,
                'title' => $request->title,
                'content' => $request->content,
                'lesson_type' => $request->lesson_type,
                'video_url' => $request->video_url,
                'lesson_order' => $request->lesson_order,
                'duration_minutes' => $request->duration_minutes,
                'is_preview' => $request->has('is_preview'),
                'is_published' => $request->has('is_published'),
            ]);

            if ($lesson->lesson_type !== 'text') {
                return $this->removeSections($lesson);
            }

            if ((string) $lesson->content !== (string) $previousContent && $lesson->sections()->exists()) {
                $this->syncLegacyReadingEdit($lesson, (string) $lesson->content);
            }

            return [];
        });
        $this->removeLessonFiles($paths);

        return redirect()
            ->route('lessons.index')
            ->with('success', 'Lesson updated successfully.');
    }


    public function destroy(Request $request, Lesson $lesson)
    {
        $paths = DB::transaction(function () use ($request, $lesson) {
            $lesson = Lesson::query()->lockForUpdate()->findOrFail($lesson->id);
            Course::authorizeManagement($request->user(), $lesson->course_id);
            abort_if($lesson->lesson_type === 'activity', 403, 'Manage this Activity from its teacher lesson editor.');
            abort_if(! $request->user()->hasRole('super_admin') && $lesson->hasReferencesOutsideCourse((int) $lesson->course_id), 403);

            return $this->deleteLesson($lesson);
        });
        $this->removeLessonFiles($paths);

        return redirect()
            ->route('lessons.index')
            ->with('success', 'Lesson deleted successfully.');
    }

    private function guardReadingSectionConversion(Lesson $lesson, string $newType): void
    {
        if ($lesson->exists && $newType !== 'text' && $lesson->sections()->exists()) {
            throw ValidationException::withMessages([
                'lesson_type' => 'This Reading lesson contains saved sections. Keep its type or create a separate lesson to preserve its content.',
            ]);
        }
    }

    /** Legacy textareas may edit one plain text section, but cannot flatten a structured reading. */
    private function syncLegacyReadingEdit(Lesson $lesson, string $content): void
    {
        $textSections = $lesson->sections()->where('type', 'text')->get();
        if ($textSections->count() > 1 || $textSections->contains(fn ($section) => $section->body_format === 'html' || filled($section->caption))) {
            throw ValidationException::withMessages([
                'content' => 'This Reading lesson has multiple, headed, or formatted text sections. Use the section builder; the legacy textarea cannot safely replace its content.',
            ]);
        }

        $first = $textSections->first();
        if ($first && blank($content)) {
            throw ValidationException::withMessages([
                'content' => 'Remove this text section explicitly in the section builder instead of clearing the legacy textarea.',
            ]);
        }
        if (filled($content) && $first) {
            $first->update(['body' => $content, 'caption' => null]);
        } elseif (filled($content)) {
            $lesson->sections()->create([
                'type' => 'text', 'body_format' => 'plain', 'body' => $content,
                'position' => ($lesson->sections()->max('position') ?? -1) + 1,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STUDENT - ACCESS GUARDS
    |--------------------------------------------------------------------------
    */

    private function ensureStudentCanAccessCourse(
        Course $course
    ): void {
        if (
            strtolower(
                trim(
                    (string) $course->status
                )
            )
            !==
            'published'
        ) {
            abort(
                404,
                'Course not found.'
            );
        }

        // Cancelled enrollments no longer give access (Enrollment::ACCESS_STATUSES).
        $isEnrolled = Enrollment::where(
                'student_id',
                auth()->id()
            )
            ->where(
                'course_id',
                $course->id
            )
            ->withAccess()
            ->exists();

        if (!$isEnrolled) {
            abort(
                403,
                'You must have an active enrollment in this course to access its learning content.'
            );
        }
    }


    private function ensureStudentCanAccessLesson(
        Lesson $lesson
    ): void {
        $lesson->loadMissing('course');

        if (!$lesson->course) {
            abort(
                404,
                'Course not found.'
            );
        }

        $this->ensureStudentCanAccessCourse(
            $lesson->course
        );

        if (!(bool) $lesson->is_published) {
            abort(
                404,
                'Lesson not found.'
            );
        }
    }



    /*
    |--------------------------------------------------------------------------
    | STUDENT - COURSE LESSONS
    |--------------------------------------------------------------------------
    */

    public function studentCourse(Course $course)
    {
        $this->ensureStudentCanAccessCourse(
            $course
        );

        $lessons = $course
            ->lessons()
            ->where(
                'is_published',
                true
            )
            ->orderBy(
                'lesson_order'
            )
            ->get();

        return view(
            'Student.learn-course',
            compact(
                'course',
                'lessons'
            )
        );
    }


    public function studentLesson(Lesson $lesson)
    {
        $this->ensureStudentCanAccessLesson(
            $lesson
        );

        if ($lesson->lesson_type === 'text') {
            $lesson->load('sections');
        }


        /*
         * Quiz lessons must be taken through QuizController so the
         * enrollment, publication, scoring, and completion guards there
         * cannot be bypassed through the ordinary lesson route.
         */
        if (
            strtolower(
                trim(
                    (string) $lesson->lesson_type
                )
            )
            ===
            'quiz'
        ) {
            $quiz = Quiz::where(
                    'lesson_id',
                    $lesson->id
                )
                ->where(
                    'course_id',
                    $lesson->course_id
                )
                ->where(
                    'is_published',
                    true
                )
                ->first();

            if (!$quiz) {
                abort(
                    404,
                    'Quiz not found.'
                );
            }

            return redirect()
                ->route(
                    'student.quiz.take',
                    $quiz
                );
        }

        if (strtolower(trim((string) $lesson->lesson_type)) === 'activity') {
            $activity = Assignment::where('lesson_id', $lesson->id)
                ->where('course_id', $lesson->course_id)
                ->where('is_activity', true)
                ->publishedForStudents()
                ->forEnrolledStudent(auth()->id())
                ->first();

            abort_unless($activity, 404, 'Activity not found.');

            return redirect()->route('student.assignments.show', $activity);
        }

        $progress = LessonProgress::firstOrCreate(
            [
                'student_id' =>
                    auth()->id(),

                'lesson_id' =>
                    $lesson->id,
            ],
            [
                'status' =>
                    'in_progress',

                'started_at' =>
                    now(),
            ]
        );

        $previousLesson = Lesson::where(
                'course_id',
                $lesson->course_id
            )
            ->where(
                'is_published',
                true
            )
            ->where(
                'lesson_order',
                '<',
                $lesson->lesson_order
            )
            ->orderByDesc(
                'lesson_order'
            )
            ->first();

        $nextLesson = Lesson::where(
                'course_id',
                $lesson->course_id
            )
            ->where(
                'is_published',
                true
            )
            ->where(
                'lesson_order',
                '>',
                $lesson->lesson_order
            )
            ->orderBy(
                'lesson_order'
            )
            ->first();

        return view(
            'Student.lesson-view',
            compact(
                'lesson',
                'progress',
                'previousLesson',
                'nextLesson'
            )
        );
    }


    public function markComplete(Lesson $lesson)
    {
        DB::transaction(function () use ($lesson) {
            $course = Course::query()->lockForUpdate()->findOrFail($lesson->course_id);
            $lesson = $course->lessons()->lockForUpdate()->findOrFail($lesson->id)->setRelation('course', $course);

            if (strtolower(trim((string) $lesson->lesson_type)) === 'activity') {
                $activity = Assignment::lockStudentActivity(
                    $lesson,
                    auth()->id(),
                    'You must have an active enrollment in this course to access its learning content.'
                );
                $this->completeStudentActivity($activity);

                return;
            }

            $this->ensureStudentCanAccessLesson($lesson);

            /*
             * A quiz lesson cannot be completed by directly calling the
             * lesson-complete endpoint. It is completed only after the quiz
             * is passed through QuizController.
             */
            if (
                strtolower(
                    trim(
                        (string) $lesson->lesson_type
                    )
                )
                ===
                'quiz'
            ) {
                abort(
                    403,
                    'Quiz lessons can only be completed by passing the quiz.'
                );
            }

            $this->recordLessonCompletion($lesson);
        });

        return back()->with('success', 'Lesson marked as completed.');
    }

    /**
     * Record completion for an Activity loaded by Assignment::lockStudentActivity().
     */
    public function completeStudentActivity(Assignment $activity): void
    {
        abort_unless(
            $activity->submissions()->where('student_id', auth()->id())->exists(),
            403,
            'Submit this Activity before marking it complete.'
        );

        $this->recordLessonCompletion($activity->lesson);
    }

    private function recordLessonCompletion(Lesson $lesson): void
    {
        $studentId =
            auth()->id();

        $course =
            $lesson->course;

        LessonProgress::updateOrCreate(
            [
                'student_id' =>
                    $studentId,

                'lesson_id' =>
                    $lesson->id,
            ],
            [
                'status' =>
                    'completed',

                'completed_at' =>
                    now(),
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | RECALCULATE PUBLISHED LESSON PROGRESS
        |--------------------------------------------------------------------------
        */

        $publishedLessonIds = $course
            ->lessons()
            ->where(
                'is_published',
                true
            )
            ->pluck('id');

        $totalLessons =
            $publishedLessonIds->count();

        $completedLessons = LessonProgress::where(
                'student_id',
                $studentId
            )
            ->where(
                'status',
                'completed'
            )
            ->whereIn(
                'lesson_id',
                $publishedLessonIds
            )
            ->count();

        $progressPercentage =
            $totalLessons > 0
                ? round(
                    (
                        $completedLessons
                        /
                        $totalLessons
                    )
                    * 100,
                    2
                )
                : 0;


        /*
        |--------------------------------------------------------------------------
        | OPTIONAL STANDALONE FINAL QUIZ
        |--------------------------------------------------------------------------
        | A published quiz with no lesson_id is treated as the final
        | assessment. If one exists, the latest attempt must be passed
        | before the course can be completed.
        */

        $finalQuiz = Quiz::where(
                'course_id',
                $course->id
            )
            ->where(
                'is_published',
                true
            )
            ->whereNull(
                'lesson_id'
            )
            ->first();

        $passedFinalQuiz =
            true;

        if ($finalQuiz) {
            $latestFinalQuizResult =
                QuizResult::where(
                    'student_id',
                    $studentId
                )
                    ->where(
                        'quiz_id',
                        $finalQuiz->id
                    )
                    ->orderByDesc(
                        'completed_at'
                    )
                    ->orderByDesc(
                        'id'
                    )
                    ->first();

            $passedFinalQuiz =
                $latestFinalQuizResult
                &&
                strtolower(
                    trim(
                        (string) (
                            $latestFinalQuizResult
                                ->remarks
                            ??
                            ''
                        )
                    )
                )
                ===
                'passed';
        }

        $allLessonsCompleted =
            $totalLessons > 0
            &&
            $completedLessons >= $totalLessons;

        $courseCompleted =
            $allLessonsCompleted
            &&
            $passedFinalQuiz;


        /*
        |--------------------------------------------------------------------------
        | SYNC ENROLLMENT
        |--------------------------------------------------------------------------
        */

        $enrollment = Enrollment::where(
                'student_id',
                $studentId
            )
            ->where(
                'course_id',
                $course->id
            )
            ->firstOrFail();

        $enrollmentData = [
            'progress_percentage' =>
                $progressPercentage,

            'status' =>
                $courseCompleted
                    ? 'completed'
                    : 'active',
        ];

        if ($courseCompleted) {
            $enrollmentData['completed_at'] =
                $enrollment->completed_at
                ??
                now();
        }

        $enrollment->update(
            $enrollmentData
        );


        /*
        |--------------------------------------------------------------------------
        | CERTIFICATE
        |--------------------------------------------------------------------------
        */

        if (
            $courseCompleted
            &&
            $course->certificate_available
        ) {
            $certificate = Certificate::firstOrCreate(
                [
                    'student_id' =>
                        $studentId,

                    'course_id' =>
                        $course->id,
                ],
                [
                    'certificate_number' =>
                        Certificate::generateNumber(),

                    'issued_date' =>
                        now()->toDateString(),

                    'status' =>
                        'issued',
                ]
            );

            // Only a newly issued certificate notifies the student.
            if ($certificate->wasRecentlyCreated) {
                $certificate->notifyStudent();
            }
        }

    }



    /*
    |--------------------------------------------------------------------------
    | TEACHER - ALL LESSONS
    |--------------------------------------------------------------------------
    */

    public function teacherAllLessons()
    {
        $courses = Course::with([
                'lessons' => function ($query) {
                    $query->orderBy('lesson_order');
                },
                'category',
            ])
            ->where(
                'teacher_id',
                auth()->id()
            )
            ->latest()
            ->get();

        return view(
            'Teacher.lessons-index',
            compact('courses')
        );
    }




    /*
    |--------------------------------------------------------------------------
    | TEACHER - COURSE MODIFICATION GUARD
    |--------------------------------------------------------------------------
    */

    /**
     * Final validation audit SYS-2: the owner of a course that is pending
     * approval or published is sent back to the lesson list with an
     * explanation instead of a 403 error page. Other teachers still get 403
     * (checked in ensureTeacherCanModifyCourse), and saving stays blocked.
     */
    private function redirectIfLessonsLocked(Course $course): ?RedirectResponse
    {
        if ((int) $course->teacher_id !== (int) auth()->id()) {
            return null;
        }

        $status = strtolower(trim((string) $course->status));

        if (in_array($status, ['draft', 'rejected'], true)) {
            return null;
        }

        $message = $status === 'pending'
            ? 'This course is waiting for administrator approval, so its lessons cannot be added or changed. You can edit it again if the administrator returns it for revision.'
            : 'This course is published, so its lessons cannot be added or changed by the teacher. Contact an administrator if a change is needed.';

        return redirect()
            ->route('teacher.lessons', $course)
            ->with('error', $message);
    }

    private function ensureTeacherCanModifyCourse(Course $course): void
    {
        if (
            (int) $course->teacher_id
            !==
            (int) auth()->id()
        ) {
            abort(403, 'Unauthorized');
        }

        if (
            !in_array(
                strtolower(trim((string) $course->status)),
                ['draft', 'rejected'],
                true
            )
        ) {
            abort(
                403,
                'This course cannot be modified while it is pending approval or published.'
            );
        }
    }



    /*
    |--------------------------------------------------------------------------
    | TEACHER - MANAGE COURSE LESSONS
    |--------------------------------------------------------------------------
    */

    public function teacherLessons(Course $course)
    {
        if (
            (int) $course->teacher_id
            !==
            (int) auth()->id()
        ) {
            abort(403, 'Unauthorized');
        }

        $course->load('category');

        $lessons = $course
            ->lessons()
            ->orderBy('lesson_order')
            ->get();

        $enrollments = Enrollment::where(
            'course_id',
            $course->id
        )->get();

        return view(
            'Teacher.lessons',
            compact(
                'course',
                'lessons',
                'enrollments'
            )
        );
    }



    /*
    |--------------------------------------------------------------------------
    | TEACHER - CREATE LESSON
    |--------------------------------------------------------------------------
    */

    public function teacherCreateLesson(Course $course)
    {
        if ($redirect = $this->redirectIfLessonsLocked($course)) {
            return $redirect;
        }

        $this->ensureTeacherCanModifyCourse($course);

        $course->load('category');

        $nextOrder =
            ($course->lessons()->max('lesson_order') ?? 0)
            + 1;

        return view(
            'Teacher.create-lesson',
            compact(
                'course',
                'nextOrder'
            )
        );
    }



    /*
    |--------------------------------------------------------------------------
    | TEACHER - STORE LESSON
    |--------------------------------------------------------------------------
    */

    public function teacherStoreLesson(Request $request, Course $course)
    {
        $this->ensureTeacherCanModifyCourse($course);
        $this->saveTeacherLesson($request, $course);

        return redirect()->route('teacher.lessons', $course)
            ->with('success', 'Lesson added successfully.');
    }



    /*
    |--------------------------------------------------------------------------
    | TEACHER - EDIT LESSON
    |--------------------------------------------------------------------------
    */

    public function teacherEditLesson(Lesson $lesson)
    {
        $lesson->load('course.category');

        if ($redirect = $this->redirectIfLessonsLocked($lesson->course)) {
            return $redirect;
        }

        $this->ensureTeacherCanModifyCourse($lesson->course);

        $lesson->load('sections');

        $initialSections = $lesson->sections->map(fn (LessonSection $section) => [
            'id' => $section->id,
            'type' => $section->type,
            'body' => $section->body,
            'body_format' => $section->body_format,
            'caption' => $section->caption,
            'file_path' => $section->file_path,
            'original_name' => $section->original_name,
        ])->all();

        if ($initialSections === [] && filled($lesson->content)) {
            $initialSections[] = ['type' => 'text', 'body' => $lesson->content];
        }

        $activityAssignment = $lesson->assignments()->where('course_id', $lesson->course_id)->where('is_activity', true)->first();

        return view('Teacher.edit-lesson', compact('lesson', 'initialSections', 'activityAssignment'));
    }



    /*
    |--------------------------------------------------------------------------
    | TEACHER - UPDATE LESSON
    |--------------------------------------------------------------------------
    */

    public function teacherUpdateLesson(Request $request, Lesson $lesson)
    {
        $this->ensureTeacherCanModifyCourse($lesson->course);
        $this->saveTeacherLesson($request, $lesson->course, $lesson);

        return redirect()->route('teacher.lessons', $lesson->course)
            ->with('success', 'Lesson updated successfully.');
    }

    private function saveTeacherLesson(Request $request, Course $course, ?Lesson $lesson = null): void
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'lesson_type' => ['required', 'in:video,document,text,quiz,activity'],
            'content' => ['nullable', 'string', 'required_if:lesson_type,activity'],
            'activity_answer_format' => ['exclude_unless:lesson_type,activity', 'nullable', 'in:text,file,either'],
            'activity_max_score' => ['exclude_unless:lesson_type,activity', 'nullable', 'integer', 'min:1', 'max:10000'],
            'activity_due_date' => ['exclude_unless:lesson_type,activity', 'nullable', 'date_format:Y-m-d\TH:i'],
            'activity_ai_enabled' => ['exclude_unless:lesson_type,activity', 'nullable', 'boolean'],
            'activity_criteria' => ['exclude_unless:lesson_type,activity', 'nullable', 'string', 'max:10000'],
            'video_url' => ['nullable', 'url', 'max:500', 'required_if:lesson_type,video'],
            'lesson_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt', 'max:20480'],
            'lesson_order' => ['required', 'integer', 'min:1'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'is_preview' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'reading_builder' => ['exclude_unless:lesson_type,text', 'sometimes', 'boolean'],
            'sections' => ['exclude_unless:lesson_type,text', 'sometimes', 'array', 'list', 'max:50'],
            'sections.*' => ['required', 'array:id,type,body,body_format,caption,file'],
            'sections.*.id' => ['nullable', 'integer', 'min:1', 'distinct'],
            'sections.*.type' => ['required', 'in:text,image,file'],
            'sections.*.body' => ['nullable', 'string'],
            'sections.*.body_format' => ['sometimes', 'required', 'in:plain,html'],
            'sections.*.caption' => ['nullable', 'string', 'max:500'],
            'sections.*.file' => ['nullable', 'file', 'max:20480'],
        ], [
            'video_url.required_if' => 'Please provide a video URL for this video lesson.',
        ]);

        $newPaths = [];
        $obsoletePaths = [];

        try {
            DB::transaction(function () use ($request, $validated, $course, $lesson, &$newPaths, &$obsoletePaths) {
                $course = Course::query()->lockForUpdate()->findOrFail($course->id);
                $this->ensureTeacherCanModifyCourse($course);
                $lesson = $lesson
                    ? $course->lessons()->lockForUpdate()->findOrFail($lesson->id)
                    : new Lesson(['course_id' => $course->id]);

                $type = $validated['lesson_type'];
                $this->guardReadingSectionConversion($lesson, $type);
                $activities = $this->lockActivityAssignments($lesson);
                if ($type === 'activity' && $lesson->exists && $lesson->lesson_type !== 'activity'
                    && LessonProgress::where('lesson_id', $lesson->id)->lockForUpdate()->first(['id'])) {
                    throw ValidationException::withMessages([
                        'lesson_type' => 'Create a separate Activity to preserve the student progress on this lesson.',
                    ]);
                }
                if ($this->hasActivitySubmissions($activities)) {
                    if ($type !== 'activity') {
                        throw ValidationException::withMessages([
                            'lesson_type' => 'This Activity already has student submissions and cannot be changed to another lesson type.',
                        ]);
                    }

                    $activity = $activities->first();
                    if ((int) ($validated['activity_max_score'] ?? 100) !== $activity->max_score
                        || ($validated['activity_answer_format'] ?? 'either') !== $activity->answer_format) {
                        throw ValidationException::withMessages([
                            'activity_max_score' => 'The maximum score and answer format cannot change after students submit an Activity.',
                        ]);
                    }
                }
                $filePath = $type === 'document' ? $lesson->file_path : null;
                if ($type === 'document' && $request->hasFile('lesson_file')) {
                    $filePath = $this->storeLessonUpload($request->file('lesson_file'), 'lesson-files/'.$course->id, 'lesson_file');
                    $newPaths[] = $filePath;
                }

                if ($type === 'document' && ! $filePath) {
                    throw ValidationException::withMessages([
                        'lesson_file' => 'Please upload a document for this lesson.',
                    ]);
                }
                if ($lesson->file_path && $lesson->file_path !== $filePath) {
                    $obsoletePaths[] = $lesson->file_path;
                }

                $content = $validated['content'] ?? null;
                $usingBuilder = $type === 'text' && (
                    ($validated['reading_builder'] ?? false) || array_key_exists('sections', $validated)
                );
                if ($type === 'text' && ! $usingBuilder && blank($content)) {
                    if (! $lesson->exists || ! $lesson->sections()->exists()) {
                        throw ValidationException::withMessages([
                            'content' => 'Please add content for this reading lesson.',
                        ]);
                    }
                    $content = $lesson->content;
                }

                $lesson->fill([
                    'title' => $validated['title'],
                    'lesson_type' => $type,
                    'video_url' => $type === 'video' ? $validated['video_url'] : null,
                    'file_path' => $filePath,
                    'lesson_order' => $validated['lesson_order'],
                    'duration_minutes' => $validated['duration_minutes'] ?? null,
                    'is_preview' => $type !== 'activity' && $request->boolean('is_preview'),
                    'is_published' => $request->boolean('is_published'),
                ])->save();

                if ($usingBuilder) {
                    $content = $this->syncSections($lesson, $validated['sections'] ?? [], $newPaths, $obsoletePaths);
                } elseif ($type !== 'text') {
                    array_push($obsoletePaths, ...$this->removeSections($lesson));
                } elseif ((string) $content !== (string) $lesson->content && $lesson->sections()->exists()) {
                    $this->syncLegacyReadingEdit($lesson, (string) $content);
                }

                $lesson->update(['content' => $content]);

                if ($type === 'activity') {
                    $this->syncActivityAssignment($lesson, $validated, $request);
                } else {
                    foreach ($activities as $activity) {
                        $activity->delete();
                    }
                }
            });
        } catch (Throwable $exception) {
            $this->removeLessonFiles($newPaths);
            throw $exception;
        }

        $this->removeLessonFiles($obsoletePaths);
    }

    private function storeLessonUpload(UploadedFile $upload, string $directory, string $field): string
    {
        $path = $upload->store($directory, PrivateFiles::DISK);
        if ($path === false) {
            throw ValidationException::withMessages([
                $field => 'The file could not be saved. Please try again.',
            ]);
        }

        return $path;
    }



    /**
     * Persist a complete ordered section list and return the plain text
     * mirror kept in lessons.content for existing reports and AI sources.
     * Uploaded section files stay on the same private disk as lesson files.
     *
     * @param array<int, string> $newPaths
     * @param array<int, string> $obsoletePaths
     */
    private function syncSections(
        Lesson $lesson,
        array $rows,
        array &$newPaths,
        array &$obsoletePaths
    ): string {
        if ($rows === []) {
            throw ValidationException::withMessages([
                'sections' => 'Add at least one lesson section before saving this Reading lesson.',
            ]);
        }

        $existing = $lesson->sections()->get()->keyBy('id');
        foreach ($rows as $row) {
            if (isset($row['id']) && ! $existing->has((int) $row['id'])) {
                throw ValidationException::withMessages([
                    'sections' => 'A section does not belong to this lesson. Reload the lesson and try again.',
                ]);
            }
        }

        $retainedIds = [];
        $plainText = [];

        foreach ($rows as $position => $row) {
            $type = $row['type'] ?? '';
            $body = (string) ($row['body'] ?? '');
            $caption = trim((string) ($row['caption'] ?? ''));
            $section = filled($row['id'] ?? null)
                ? $existing->get((int) $row['id'])
                : new LessonSection();
            $oldPath = $section->file_path;
            $upload = $row['file'] ?? null;

            $format = $row['body_format'] ?? ($section->body_format ?? 'plain');
            // HTML keeps its existing sanitizer/trim behavior; plain bodies retain user whitespace.
            if ($format === 'html') {
                $body = trim($body);
            }
            $originalBody = $type === 'text'
                ? ($section->exists ? $section->body : ($existing->isEmpty() ? $lesson->content : null))
                : null;
            // Textareas and native multipart forms normalize line breaks. Compare only that
            // transport representation; never trim it or persist a normalized original.
            $samePlainBody = $type === 'text' && $format === 'plain' && $originalBody !== null
                && str_replace(["\r\n", "\r"], "\n", $body)
                    === str_replace(["\r\n", "\r"], "\n", (string) $originalBody);
            if ($section->exists && $section->body_format === 'html'
                && ($type !== 'text' || $format !== 'html'
                    || (! array_key_exists('body_format', $row) && $body !== trim((string) $section->body)))) {
                throw ValidationException::withMessages([
                    "sections.$position.body_format" => 'Formatted content must retain its HTML format marker. Use a compatible editor or remove the section explicitly.',
                ]);
            }

            $textBody = $body;
            if ($type === 'text' && $format === 'html') {
                try {
                    $body = app(ReadingContent::class)->sanitize($body);
                    $textBody = app(ReadingContent::class)->plainText($body);
                } catch (\InvalidArgumentException $exception) {
                    throw ValidationException::withMessages(["sections.$position.body" => $exception->getMessage()]);
                }
                if (mb_strlen($textBody) > 30000) {
                    throw ValidationException::withMessages(["sections.$position.body" => 'Formatted text may contain up to 30,000 readable characters.']);
                }
            }

            if ($type === 'text') {
                $empty = trim($textBody) === '' && $caption === '';
                if ($empty || ($format === 'plain' && mb_strlen($body) > 30000 && ! $samePlainBody)) {
                    throw ValidationException::withMessages([
                        "sections.$position.body" => $empty
                            ? 'Add a heading or text to this section, or delete the empty section.'
                            : 'Text sections may contain up to 30,000 characters.',
                    ]);
                }
            } elseif ($upload) {
                $rules = $type === 'image'
                    ? ['mimes:jpg,jpeg,png,gif,webp', 'max:10240']
                    : ['mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt', 'max:20480'];
                Validator::make(['sections' => [$position => ['file' => $upload]]], [
                    "sections.$position.file" => $rules,
                ], [
                    "sections.$position.file.mimes" => $type === 'image'
                        ? 'Image sections must be JPG, PNG, GIF or WebP files.'
                        : 'File sections must be PDF, Office, or TXT documents.',
                    "sections.$position.file.max" => $type === 'image'
                        ? 'Images may not be larger than 10 MB.'
                        : 'Learning files may not be larger than 20 MB.',
                ])->validate();
            }

            $path = null;
            $originalName = null;
            $mimeType = null;

            if (in_array($type, ['image', 'file'], true)) {
                if ($upload) {
                    $path = $this->storeLessonUpload(
                        $upload,
                        'lesson-files/'.$lesson->course_id.'/sections',
                        "sections.$position.file"
                    );
                    $newPaths[] = $path;
                    $name = basename(str_replace('\\', '/', $upload->getClientOriginalName()));
                    $originalName = mb_substr(preg_replace('/[\x00-\x1f\x7f]/u', '', $name) ?? '', 0, 255) ?: null;
                    $mimeType = $upload->getMimeType();
                } elseif ($section->exists && $section->type === $type && $section->file_path) {
                    $path = $section->file_path;
                    $originalName = $section->original_name;
                    $mimeType = $section->mime_type;
                } else {
                    throw ValidationException::withMessages([
                        "sections.$position.file" => $type === 'image'
                            ? 'Choose an image for this section.'
                            : 'Choose a file for this section.',
                    ]);
                }
            }

            if ($oldPath && $oldPath !== $path) {
                $obsoletePaths[] = $oldPath;
            }

            if ($samePlainBody) {
                // Restore original bytes, including mixed line endings and outer whitespace.
                $body = $originalBody;
                $textBody = $body;
            }
            if ($section->exists && $caption === trim((string) $section->caption)) {
                $caption = (string) $section->caption;
            }

            $section->fill([
                'body_format' => $type === 'text' ? $format : 'plain',
                'type' => $type,
                'body' => $type === 'text' && $body !== '' ? $body : null,
                'caption' => $caption !== '' ? $caption : null,
                'file_path' => $path,
                'original_name' => $originalName,
                'mime_type' => $mimeType,
                'position' => $position,
            ]);
            $section->lesson()->associate($lesson);
            $section->save();
            $retainedIds[] = $section->id;

            if ($type === 'text') {
                $plainText[] = implode("\n", array_filter([$caption, $textBody], fn ($text) => $text !== ''));
            }
        }

        foreach ($existing as $section) {
            if (! in_array($section->id, $retainedIds, true)) {
                if ($section->file_path) {
                    $obsoletePaths[] = $section->file_path;
                }
                $section->delete();
            }
        }

        return implode("\n\n", $plainText);
    }


    /*
    |--------------------------------------------------------------------------
    | TEACHER - DELETE LESSON
    |--------------------------------------------------------------------------
    */

    public function teacherDeleteLesson(Lesson $lesson)
    {
        $course = $lesson->course;
        $this->ensureTeacherCanModifyCourse($course);

        $paths = DB::transaction(function () use ($lesson, $course) {
            $course = Course::query()->lockForUpdate()->findOrFail($course->id);
            $this->ensureTeacherCanModifyCourse($course);
            $lesson = $course->lessons()->lockForUpdate()->findOrFail($lesson->id);
            abort_if($lesson->hasReferencesOutsideCourse((int) $course->id), 403);

            $activities = $this->lockActivityAssignments($lesson);
            abort_if($this->hasActivitySubmissions($activities), 422, 'This Activity has student submissions and cannot be deleted.');
            foreach ($activities as $activity) {
                $activity->delete();
            }

            return $this->deleteLesson($lesson);
        });
        $this->removeLessonFiles($paths);

        return redirect()->route('teacher.lessons', $course)
            ->with('success', 'Lesson deleted successfully.');
    }

    private function deleteLesson(Lesson $lesson): array
    {
        $paths = $lesson->sections()->whereNotNull('file_path')->pluck('file_path')->all();
        $paths[] = $lesson->file_path;
        $lesson->delete();

        return $paths;
    }

    private function removeSections(Lesson $lesson): array
    {
        $paths = $lesson->sections()->whereNotNull('file_path')->pluck('file_path')->all();
        $lesson->sections()->delete();

        return $paths;
    }

    private function removeLessonFiles(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            try {
                PrivateFiles::delete($path);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    private function syncActivityAssignment(Lesson $lesson, array $validated, Request $request): Assignment
    {
        $dueDate = filled($validated['activity_due_date'] ?? null)
            ? \Illuminate\Support\Carbon::createFromFormat('Y-m-d\\TH:i', $validated['activity_due_date'])
            : null;

        return Assignment::updateOrCreate(
            [
                'lesson_id' => $lesson->id,
                'course_id' => $lesson->course_id,
                'is_activity' => true,
            ],
            [
                'title' => $lesson->title,
                'description' => $validated['content'],
                'submission_type' => 'essay',
                'answer_format' => $validated['activity_answer_format'] ?? 'either',
                'evaluation_criteria' => $validated['activity_criteria'] ?? null,
                'programming_language' => null,
                'ai_evaluation_enabled' => $request->boolean('activity_ai_enabled'),
                'due_date' => $dueDate,
                'max_score' => $validated['activity_max_score'] ?? 100,
                'is_published' => $request->boolean('is_published'),
            ]
        );
    }

    private function lockActivityAssignments(Lesson $lesson): \Illuminate\Database\Eloquent\Collection
    {
        if (! $lesson->exists) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return $lesson->assignments()->where('is_activity', true)->orderBy('id')->lockForUpdate()->get();
    }

    private function hasActivitySubmissions(\Illuminate\Database\Eloquent\Collection $assignments): bool
    {
        // A locking read sees a committed submission even under MySQL repeatable read.
        return $assignments->contains(fn (Assignment $assignment) =>
            $assignment->submissions()->orderBy('id')->lockForUpdate()->first(['id']) !== null);
    }
}
