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
    | STUDENT - COURSE LESSONS
    |--------------------------------------------------------------------------
    */

    public function studentCourse(Course $course)
    {
        $lessons = $course->lessons()
            ->orderBy('lesson_order')
            ->get();

        return view(
            'student.learn-course',
            compact('course', 'lessons')
        );
    }


    public function studentLesson(Lesson $lesson)
    {
        $progress = LessonProgress::firstOrCreate(
            [
                'student_id' => auth()->id(),
                'lesson_id' => $lesson->id,
            ],
            [
                'status' => 'in_progress',
                'started_at' => now(),
            ]
        );

        $previousLesson = Lesson::where(
            'course_id',
            $lesson->course_id
        )
            ->where(
                'lesson_order',
                '<',
                $lesson->lesson_order
            )
            ->orderByDesc('lesson_order')
            ->first();

        $nextLesson = Lesson::where(
            'course_id',
            $lesson->course_id
        )
            ->where(
                'lesson_order',
                '>',
                $lesson->lesson_order
            )
            ->orderBy('lesson_order')
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
        $studentId = auth()->id();

        LessonProgress::updateOrCreate(
            [
                'student_id' => $studentId,
                'lesson_id' => $lesson->id,
            ],
            [
                'status' => 'completed',
                'completed_at' => now(),
            ]
        );

        $course = $lesson->course;

        $totalLessons = $course
            ->lessons()
            ->count();

        $completedLessons = LessonProgress::where(
            'student_id',
            $studentId
        )
            ->where('status', 'completed')
            ->whereIn(
                'lesson_id',
                $course->lessons()->pluck('id')
            )
            ->count();

        $progressPercentage = 0;

        if ($totalLessons > 0) {
            $progressPercentage = round(
                ($completedLessons / $totalLessons) * 100,
                2
            );
        }

        $enrollment = Enrollment::where(
            'student_id',
            $studentId
        )
            ->where(
                'course_id',
                $course->id
            )
            ->first();

        if ($enrollment) {

            $enrollment->update([
                'progress_percentage' =>
                    $progressPercentage,
            ]);

            if ($progressPercentage >= 100) {

                $courseQuizIds = $course
                    ->quizzes()
                    ->pluck('id');

                $hasQuiz =
                    $courseQuizIds->isNotEmpty();

                $hasPassed = false;

                if ($hasQuiz) {

                    $bestResult = QuizResult::where(
                        'student_id',
                        $studentId
                    )
                        ->whereIn(
                            'quiz_id',
                            $courseQuizIds
                        )
                        ->orderByDesc('percentage')
                        ->first();

                    if ($bestResult) {

                        $quiz = Quiz::find(
                            $bestResult->quiz_id
                        );

                        $passingScore =
                            $quiz->passing_score ?? 75;

                        $hasPassed =
                            $bestResult->percentage
                            >=
                            $passingScore;
                    }
                }

                $eligibleForCertificate =
                    $hasQuiz
                        ? $hasPassed
                        : true;

                if ($eligibleForCertificate) {

                    $enrollment->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);

                    Certificate::firstOrCreate(
                        [
                            'student_id' => $studentId,
                            'course_id' => $course->id,
                        ],
                        [
                            'certificate_number' =>
                                'PW-' .
                                now()->format('Y') .
                                '-' .
                                str_pad(
                                    $studentId .
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
            }
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
        if (
            (int) $course->teacher_id
            !==
            (int) auth()->id()
        ) {
            abort(403, 'Unauthorized');
        }

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
        if (
            (int) $course->teacher_id
            !==
            (int) auth()->id()
        ) {
            abort(403, 'Unauthorized');
        }

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

        if (
            (int) $lesson->course->teacher_id
            !==
            (int) auth()->id()
        ) {
            abort(403, 'Unauthorized');
        }

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

        if (
            (int) $lesson->course->teacher_id
            !==
            (int) auth()->id()
        ) {
            abort(403, 'Unauthorized');
        }

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

        if (
            (int) $lesson->course->teacher_id
            !==
            (int) auth()->id()
        ) {
            abort(403, 'Unauthorized');
        }

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