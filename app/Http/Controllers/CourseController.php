<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\LessonProgress;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  ADMIN — COURSES                                                   */
    /* ------------------------------------------------------------------ */

    public function index(Request $request)
    {
        $query = Course::with([
            'category',
            'teacher',
        ]);

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(
                function ($q) use ($search) {

                    $q->where(
                        'title',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'description',
                        'like',
                        '%' . $search . '%'
                    );
                }
            );
        }


        if (
            $request->filled('category')
            &&
            $request->category !== 'all'
        ) {
            $query->where(
                'category_id',
                $request->category
            );
        }


        if (
            $request->filled('status')
            &&
            $request->status !== 'all'
        ) {
            $query->where(
                'status',
                $request->status
            );
        }


        $courses = $query
            ->latest()
            ->get();


        $categories = CourseCategory::orderBy(
            'name'
        )->get();


        return view(
            'courses.index',
            compact(
                'courses',
                'categories'
            )
        );
    }



    public function create()
    {
        $categories = CourseCategory::orderBy(
            'name'
        )->get();


        return view(
            'courses.create',
            compact('categories')
        );
    }



    public function store(Request $request)
    {
        $request->validate([
            'category_id' =>
                'required|exists:course_categories,id',

            'title' =>
                'required|max:255',

            'description' =>
                'required',

            'intro_video' =>
                'nullable|max:255',

            'difficulty_level' =>
                'required|in:beginner,intermediate,advanced',

            'price' =>
                'required|numeric|min:0',

            'status' =>
                'required|in:draft,pending,approved,rejected,published',

            'estimated_hours' =>
                'nullable|integer|min:1',

            'certificate_available' =>
                'nullable',
        ]);


        Course::create([
            'teacher_id' =>
                auth()->id(),

            'category_id' =>
                $request->category_id,

            'title' =>
                $request->title,

            'description' =>
                $request->description,

            'intro_video' =>
                $request->intro_video,

            'difficulty_level' =>
                $request->difficulty_level,

            'price' =>
                $request->price,

            'status' =>
                $request->status,

            'estimated_hours' =>
                $request->estimated_hours,

            'certificate_available' =>
                $request->has(
                    'certificate_available'
                ),
        ]);


        return redirect()
            ->route('courses.index')
            ->with(
                'success',
                'Course created successfully.'
            );
    }



    public function edit(Course $course)
    {
        $categories = CourseCategory::orderBy(
            'name'
        )->get();


        return view(
            'courses.edit',
            compact(
                'course',
                'categories'
            )
        );
    }



    public function update(
        Request $request,
        Course $course
    ) {
        $request->validate([
            'category_id' =>
                'required|exists:course_categories,id',

            'title' =>
                'required|max:255',

            'description' =>
                'required',

            'intro_video' =>
                'nullable|max:255',

            'difficulty_level' =>
                'required|in:beginner,intermediate,advanced',

            'price' =>
                'required|numeric|min:0',

            'status' =>
                'required|in:draft,pending,approved,rejected,published',

            'estimated_hours' =>
                'nullable|integer|min:1',

            'certificate_available' =>
                'nullable',
        ]);


        $course->update([
            'category_id' =>
                $request->category_id,

            'title' =>
                $request->title,

            'description' =>
                $request->description,

            'intro_video' =>
                $request->intro_video,

            'difficulty_level' =>
                $request->difficulty_level,

            'price' =>
                $request->price,

            'status' =>
                $request->status,

            'estimated_hours' =>
                $request->estimated_hours,

            'certificate_available' =>
                $request->has(
                    'certificate_available'
                ),
        ]);


        return redirect()
            ->route('courses.index')
            ->with(
                'success',
                'Course updated successfully.'
            );
    }



    public function destroy(Course $course)
    {
        $course->delete();


        return redirect()
            ->route('courses.index')
            ->with(
                'success',
                'Course deleted successfully.'
            );
    }



    /* ------------------------------------------------------------------ */
    /*  STUDENT — MARKETPLACE                                             */
    /* ------------------------------------------------------------------ */

    public function marketplace()
    {
        $courses = Course::with([
                'category',
                'teacher',
            ])
            ->where(
                'status',
                'published'
            )
            ->latest()
            ->get();


        return view(
            'student.marketplace',
            compact('courses')
        );
    }



    public function showStudentCourse(
        Course $course
    ) {
        return view(
            'student.course-show',
            compact('course')
        );
    }



    public function enroll(Course $course)
    {
        Enrollment::firstOrCreate(
            [
                'student_id' =>
                    auth()->id(),

                'course_id' =>
                    $course->id,
            ],
            [
                'status' =>
                    'active',

                'enrolled_at' =>
                    now(),

                'progress_percentage' =>
                    0,
            ]
        );


        return redirect()
            ->route('student.dashboard')
            ->with(
                'success',
                'Successfully enrolled in course.'
            );
    }



    public function myCourses()
    {
        $enrollments = Enrollment::with([
                'course.category',
            ])
            ->where(
                'student_id',
                auth()->id()
            )
            ->latest()
            ->get();


        return view(
            'student.my-courses',
            compact('enrollments')
        );
    }



    /* ------------------------------------------------------------------ */
    /*  TEACHER — MY COURSES                                              */
    /* ------------------------------------------------------------------ */

    public function teacherCourses()
    {
        $courses = Course::with([
                'category',
            ])
            ->withCount([
                'lessons',
                'enrollments',
            ])
            ->where(
                'teacher_id',
                auth()->id()
            )
            ->latest()
            ->get();


        return view(
            'teacher.my-courses',
            compact('courses')
        );
    }



    /* ------------------------------------------------------------------ */
    /*  TEACHER — CREATE COURSE                                           */
    /* ------------------------------------------------------------------ */

    public function teacherCreateCourse()
    {
        $categories = CourseCategory::orderBy(
            'name'
        )->get();


        return view(
            'teacher.courses.create',
            compact('categories')
        );
    }



    public function teacherStoreCourse(
        Request $request
    ) {
        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'required',
                'string',
            ],

            'category_id' => [
                'required',
                'exists:course_categories,id',
            ],

            'thumbnail' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120',
            ],

            'intro_video' => [
                'nullable',
                'url',
                'max:255',
            ],

            'difficulty_level' => [
                'required',
                'in:beginner,intermediate,advanced',
            ],

            'price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'estimated_hours' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'certificate_available' => [
                'nullable',
                'boolean',
            ],
        ]);


        $validated['teacher_id'] =
            auth()->id();


        $validated['status'] =
            'draft';


        $validated['price'] =
            $validated['price']
            ?? 0;


        $validated['certificate_available'] =
            $request->boolean(
                'certificate_available'
            );


        if (
            $request->hasFile(
                'thumbnail'
            )
        ) {
            $validated['thumbnail'] =
                $request
                    ->file('thumbnail')
                    ->store(
                        'courses',
                        'public'
                    );
        }


        Course::create(
            $validated
        );


        return redirect()
            ->route(
                'teacher.my-courses'
            )
            ->with(
                'success',
                'Course created successfully! You can now add lessons.'
            );
    }



    /* ------------------------------------------------------------------ */
    /*  TEACHER — COURSE STUDENTS                                         */
    /* ------------------------------------------------------------------ */

    public function teacherCourseStudents(
        Course $course
    ) {
        /*
        |--------------------------------------------------------------------------
        | SECURITY
        |--------------------------------------------------------------------------
        */

        if (
            (int) $course->teacher_id
            !==
            (int) auth()->id()
        ) {
            abort(
                403,
                'Unauthorized'
            );
        }


        $course->load([
            'category',
        ]);


        $enrollments = Enrollment::with([
                'student',
            ])
            ->where(
                'course_id',
                $course->id
            )
            ->latest()
            ->get();


        return view(
            'teacher.course-students',
            compact(
                'course',
                'enrollments'
            )
        );
    }



    /* ------------------------------------------------------------------ */
    /*  TEACHER — INDIVIDUAL STUDENT PROGRESS                             */
    /* ------------------------------------------------------------------ */

    public function studentProgress(
        Course $course,
        User $student
    ) {
        /*
        |--------------------------------------------------------------------------
        | SECURITY
        |--------------------------------------------------------------------------
        | Teacher can only access progress from their own course.
        |--------------------------------------------------------------------------
        */

        if (
            (int) $course->teacher_id
            !==
            (int) auth()->id()
        ) {
            abort(
                403,
                'Unauthorized'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | LOAD COURSE
        |--------------------------------------------------------------------------
        */

        $course->load([
            'category',

            'lessons' => function ($query) {

                $query->orderBy(
                    'lesson_order'
                );

            },
        ]);


        /*
        |--------------------------------------------------------------------------
        | VERIFY ENROLLMENT
        |--------------------------------------------------------------------------
        */

        $enrollment = Enrollment::where(
                'course_id',
                $course->id
            )
            ->where(
                'student_id',
                $student->id
            )
            ->firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | GET STUDENT LESSON PROGRESS
        |--------------------------------------------------------------------------
        */

        $progress = LessonProgress::with([
                'lesson',
            ])
            ->where(
                'student_id',
                $student->id
            )
            ->whereHas(
                'lesson',
                function ($query) use ($course) {

                    $query->where(
                        'course_id',
                        $course->id
                    );

                }
            )
            ->get()
            ->keyBy(
                'lesson_id'
            );


        return view(
            'teacher.student-progress',
            compact(
                'course',
                'student',
                'enrollment',
                'progress'
            )
        );
    }



    /* ------------------------------------------------------------------ */
    /*  ADMIN — COURSE APPROVAL                                           */
    /* ------------------------------------------------------------------ */

    public function approve(
        Course $course
    ) {
        $course->update([
            'status' =>
                'published',
        ]);


        return redirect()
            ->route('courses.index')
            ->with(
                'success',
                'Course approved and published successfully.'
            );
    }
}