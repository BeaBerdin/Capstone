<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Certificate;
use App\Models\AIRecommendation;
use App\Models\Quiz;
use App\Models\QuizResult;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ADMIN - LESSONS
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $lessons = Lesson::with('course')
            ->orderBy('lesson_order')
            ->get();

        return view('lessons.index', compact('lessons'));
    }


    public function create()
    {
        $courses = Course::orderBy('title')->get();

        return view('lessons.create', compact('courses'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|max:255',
            'content' => 'nullable',
            'lesson_type' => 'required|in:video,document,text,quiz',
            'video_url' => 'nullable|max:500',
            'lesson_order' => 'required|integer|min:1',
            'duration_minutes' => 'nullable|integer|min:1',
            'is_preview' => 'nullable',
            'is_published' => 'nullable',
        ]);

        Lesson::create([
            'course_id' => $request->course_id,
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


    public function edit(Lesson $lesson)
    {
        $courses = Course::orderBy('title')->get();

        return view(
            'lessons.edit',
            compact('lesson', 'courses')
        );
    }


    public function update(Request $request, Lesson $lesson)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|max:255',
            'content' => 'nullable',
            'lesson_type' => 'required|in:video,document,text,quiz',
            'video_url' => 'nullable|max:500',
            'lesson_order' => 'required|integer|min:1',
            'duration_minutes' => 'nullable|integer|min:1',
            'is_preview' => 'nullable',
            'is_published' => 'nullable',
        ]);

        $lesson->update([
            'course_id' => $request->course_id,
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
            ->with('success', 'Lesson updated successfully.');
    }


    public function destroy(Lesson $lesson)
    {
        if (
            $lesson->file_path &&
            Storage::disk('public')->exists($lesson->file_path)
        ) {
            Storage::disk('public')->delete($lesson->file_path);
        }

        $lesson->delete();

        return redirect()
            ->route('lessons.index')
            ->with('success', 'Lesson deleted successfully.');
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

        $isEnrolled = Enrollment::where(
                'student_id',
                auth()->id()
            )
            ->where(
                'course_id',
                $course->id
            )
            ->exists();

        if (!$isEnrolled) {
            abort(
                403,
                'You must be enrolled in this course to access its learning content.'
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
            'student.learn-course',
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
            'student.lesson-view',
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
        $this->ensureStudentCanAccessLesson(
            $lesson
        );

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
            Certificate::firstOrCreate(
                [
                    'student_id' =>
                        $studentId,

                    'course_id' =>
                        $course->id,
                ],
                [
                    'certificate_number' =>
                        'PW-'
                        .
                        now()->format('Y')
                        .
                        '-'
                        .
                        str_pad(
                            $studentId
                            .
                            $course->id,
                            5,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'issued_date' =>
                        now()->toDateString(),

                    'status' =>
                        'issued',
                ]
            );
        }

        return back()->with(
            'success',
            'Lesson marked as completed.'
        );
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
            'teacher.lessons-index',
            compact('courses')
        );
    }




    /*
    |--------------------------------------------------------------------------
    | TEACHER - COURSE MODIFICATION GUARD
    |--------------------------------------------------------------------------
    */

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
            'teacher.lessons',
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
        $this->ensureTeacherCanModifyCourse($course);

        $course->load('category');

        $nextOrder =
            ($course->lessons()->max('lesson_order') ?? 0)
            + 1;

        return view(
            'teacher.create-lesson',
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

    public function teacherStoreLesson(
        Request $request,
        Course $course
    ) {
        $this->ensureTeacherCanModifyCourse($course);

        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'lesson_type' => [
                'required',
                'in:video,document,text,quiz',
            ],

            'content' => [
                'nullable',
                'string',
            ],

            'video_url' => [
                'nullable',
                'url',
                'max:500',
            ],

            'lesson_file' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt',
                'max:20480',
            ],

            'lesson_order' => [
                'required',
                'integer',
                'min:1',
            ],

            'duration_minutes' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'is_preview' => [
                'nullable',
                'boolean',
            ],

            'is_published' => [
                'nullable',
                'boolean',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Lesson type validation
        |--------------------------------------------------------------------------
        */

        if (
            $validated['lesson_type'] === 'video'
            &&
            !$request->filled('video_url')
        ) {
            return back()
                ->withErrors([
                    'video_url' =>
                        'Please provide a video URL for this video lesson.',
                ])
                ->withInput();
        }


        if (
            $validated['lesson_type'] === 'text'
            &&
            !$request->filled('content')
        ) {
            return back()
                ->withErrors([
                    'content' =>
                        'Please add content for this reading lesson.',
                ])
                ->withInput();
        }


        if (
            $validated['lesson_type'] === 'document'
            &&
            !$request->hasFile('lesson_file')
        ) {
            return back()
                ->withErrors([
                    'lesson_file' =>
                        'Please upload a document for this lesson.',
                ])
                ->withInput();
        }


        /*
        |--------------------------------------------------------------------------
        | Upload lesson document
        |--------------------------------------------------------------------------
        */

        $filePath = null;

        if ($request->hasFile('lesson_file')) {

            $filePath = $request
                ->file('lesson_file')
                ->store(
                    'lesson-files/' . $course->id,
                    'public'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Create lesson
        |--------------------------------------------------------------------------
        */

        Lesson::create([
            'course_id' =>
                $course->id,

            'title' =>
                $validated['title'],

            'content' =>
                $validated['content']
                ?? null,

            'lesson_type' =>
                $validated['lesson_type'],

            'video_url' =>
                $validated['lesson_type'] === 'video'
                    ? ($validated['video_url'] ?? null)
                    : null,

            'file_path' =>
                $validated['lesson_type'] === 'document'
                    ? $filePath
                    : null,

            'lesson_order' =>
                $validated['lesson_order'],

            'duration_minutes' =>
                $validated['duration_minutes']
                ?? null,

            'is_preview' =>
                $request->boolean('is_preview'),

            'is_published' =>
                $request->boolean('is_published'),
        ]);


        return redirect()
            ->route(
                'teacher.lessons',
                $course
            )
            ->with(
                'success',
                'Lesson added successfully.'
            );
    }



    /*
    |--------------------------------------------------------------------------
    | TEACHER - EDIT LESSON
    |--------------------------------------------------------------------------
    */

    public function teacherEditLesson(Lesson $lesson)
    {
        $lesson->load('course.category');

        $this->ensureTeacherCanModifyCourse($lesson->course);

        return view(
            'teacher.edit-lesson',
            compact('lesson')
        );
    }



    /*
    |--------------------------------------------------------------------------
    | TEACHER - UPDATE LESSON
    |--------------------------------------------------------------------------
    */

    public function teacherUpdateLesson(
        Request $request,
        Lesson $lesson
    ) {
        $lesson->load('course');

        $this->ensureTeacherCanModifyCourse($lesson->course);

        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'lesson_type' => [
                'required',
                'in:video,document,text,quiz',
            ],

            'content' => [
                'nullable',
                'string',
            ],

            'video_url' => [
                'nullable',
                'url',
                'max:500',
            ],

            'lesson_file' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt',
                'max:20480',
            ],

            'lesson_order' => [
                'required',
                'integer',
                'min:1',
            ],

            'duration_minutes' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'is_preview' => [
                'nullable',
                'boolean',
            ],

            'is_published' => [
                'nullable',
                'boolean',
            ],
        ]);


        if (
            $validated['lesson_type'] === 'video'
            &&
            !$request->filled('video_url')
        ) {
            return back()
                ->withErrors([
                    'video_url' =>
                        'Please provide a video URL for this video lesson.',
                ])
                ->withInput();
        }


        if (
            $validated['lesson_type'] === 'text'
            &&
            !$request->filled('content')
        ) {
            return back()
                ->withErrors([
                    'content' =>
                        'Please add content for this reading lesson.',
                ])
                ->withInput();
        }


        $filePath =
            $lesson->file_path;


        /*
        |--------------------------------------------------------------------------
        | New document uploaded
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('lesson_file')) {

            if (
                $lesson->file_path
                &&
                Storage::disk('public')
                    ->exists($lesson->file_path)
            ) {
                Storage::disk('public')
                    ->delete($lesson->file_path);
            }

            $filePath = $request
                ->file('lesson_file')
                ->store(
                    'lesson-files/'
                    . $lesson->course_id,
                    'public'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | If lesson changes away from document, remove old document
        |--------------------------------------------------------------------------
        */

        if (
            $validated['lesson_type'] !== 'document'
            &&
            $lesson->file_path
        ) {

            if (
                Storage::disk('public')
                    ->exists($lesson->file_path)
            ) {
                Storage::disk('public')
                    ->delete($lesson->file_path);
            }

            $filePath = null;
        }


        if (
            $validated['lesson_type'] === 'document'
            &&
            !$filePath
        ) {
            return back()
                ->withErrors([
                    'lesson_file' =>
                        'Please upload a document for this lesson.',
                ])
                ->withInput();
        }


        $lesson->update([
            'title' =>
                $validated['title'],

            'content' =>
                $validated['content']
                ?? null,

            'lesson_type' =>
                $validated['lesson_type'],

            'video_url' =>
                $validated['lesson_type'] === 'video'
                    ? ($validated['video_url'] ?? null)
                    : null,

            'file_path' =>
                $validated['lesson_type'] === 'document'
                    ? $filePath
                    : null,

            'lesson_order' =>
                $validated['lesson_order'],

            'duration_minutes' =>
                $validated['duration_minutes']
                ?? null,

            'is_preview' =>
                $request->boolean('is_preview'),

            'is_published' =>
                $request->boolean('is_published'),
        ]);


        return redirect()
            ->route(
                'teacher.lessons',
                $lesson->course
            )
            ->with(
                'success',
                'Lesson updated successfully.'
            );
    }



    /*
    |--------------------------------------------------------------------------
    | TEACHER - DELETE LESSON
    |--------------------------------------------------------------------------
    */

    public function teacherDeleteLesson(Lesson $lesson)
    {
        $lesson->load('course');

        $this->ensureTeacherCanModifyCourse($lesson->course);

        $course = $lesson->course;


        if (
            $lesson->file_path
            &&
            Storage::disk('public')
                ->exists($lesson->file_path)
        ) {
            Storage::disk('public')
                ->delete($lesson->file_path);
        }


        $lesson->delete();


        return redirect()
            ->route(
                'teacher.lessons',
                $course
            )
            ->with(
                'success',
                'Lesson deleted successfully.'
            );
    }
}