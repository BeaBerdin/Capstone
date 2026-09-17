<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\User;
use App\Notifications\CourseStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;


class CourseController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ADMIN - COURSES
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = Course::with(['category', 'teacher']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $courses = $query
            ->latest()
            ->get();

        $categories = CourseCategory::orderBy('name')->get();

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
        $categories = CourseCategory::orderBy('name')->get();

        return view(
            'courses.create',
            compact('categories')
        );
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' =>
                'required|exists:course_categories,id',

            'title' =>
                'required|string|max:255',

            'description' =>
                'required|string',

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
                'nullable|boolean',
        ]);

        $validated['teacher_id'] =
            auth()->id();

        $validated['certificate_available'] =
            $request->boolean(
                'certificate_available'
            );

        Course::create($validated);

        return redirect()
            ->route('courses.index')
            ->with(
                'success',
                'Course created successfully.'
            );
    }


    public function edit(Course $course)
    {
        $categories = CourseCategory::orderBy('name')->get();

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
        $validated = $request->validate([
            'category_id' =>
                'required|exists:course_categories,id',

            'title' =>
                'required|string|max:255',

            'description' =>
                'required|string',

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
                'nullable|boolean',
        ]);

        $validated['certificate_available'] =
            $request->boolean(
                'certificate_available'
            );

        $course->update($validated);

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


    /*
    |--------------------------------------------------------------------------
    | STUDENT - MARKETPLACE
    |--------------------------------------------------------------------------
    */

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
        abort_unless(
            $course->status === 'published',
            404
        );

        return view(
            'student.course-show',
            compact('course')
        );
    }


    public function enroll(
        Course $course
    ) {
        abort_unless(
            strtolower(
                trim(
                    (string) $course->status
                )
            ) === 'published',
            404
        );


        /*
        |--------------------------------------------------------------------------
        | EXISTING ENROLLMENT
        |--------------------------------------------------------------------------
        | If the student already has access, do not create or change anything.
        */

        $existingEnrollment =
            Enrollment::where(
                'student_id',
                auth()->id()
            )
                ->where(
                    'course_id',
                    $course->id
                )
                ->first();


        if ($existingEnrollment) {
            return redirect()
                ->route('student.my-courses')
                ->with(
                    'success',
                    'You are already enrolled in this course.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | PAID COURSE PROTECTION
        |--------------------------------------------------------------------------
        | This endpoint is for FREE enrollment only.
        |
        | Paid courses must go through TransactionController / PayMongo.
        | Never create an enrollment from this route when the course price
        | is greater than zero.
        */

        $price =
            (float) ($course->price ?? 0);


        if ($price > 0) {
            return redirect()
                ->route(
                    'student.course.show',
                    $course
                )
                ->with(
                    'error',
                    'This is a paid course. Please complete the payment process before enrollment.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | FREE COURSE ENROLLMENT
        |--------------------------------------------------------------------------
        */

        Enrollment::create([
            'student_id' =>
                auth()->id(),

            'course_id' =>
                $course->id,

            'status' =>
                'active',

            'enrolled_at' =>
                now(),

            'progress_percentage' =>
                0,
        ]);


        return redirect()
            ->route('student.my-courses')
            ->with(
                'success',
                'Successfully enrolled in course.'
            );
    }


    public function myCourses()
    {
        $enrollments = Enrollment::with(
                'course.category'
            )
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


    /*
    |--------------------------------------------------------------------------
    | TEACHER - MY COURSES
    |--------------------------------------------------------------------------
    */

    public function teacherCourses(
        Request $request
    ) {
        $query = Course::with(
                'category'
            )
            ->withCount([
                'lessons',
                'enrollments',
            ])
            ->where(
                'teacher_id',
                auth()->id()
            );

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->search
            );

            $query->where(
                function ($q) use ($search) {
                    $q->where(
                        'title',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'description',
                        'like',
                        "%{$search}%"
                    );
                }
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

        $courses = $query
            ->latest()
            ->get();

        $categories = CourseCategory::orderBy(
            'name'
        )->get();

        return view(
            'teacher.my-courses',
            compact(
                'courses',
                'categories'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | TEACHER - CREATE COURSE
    |--------------------------------------------------------------------------
    */

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
        /*
        |--------------------------------------------------------------------------
        | NORMALIZE TITLE
        |--------------------------------------------------------------------------
        */

        $request->merge([
            'title' => trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    (string) $request->title
                )
            ),
        ]);


        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate(
            [
                'title' => [
                    'required',
                    'string',
                    'max:255',

                    Rule::unique(
                        'courses',
                        'title'
                    )
                    ->where(
                        function ($query) {
                            return $query->where(
                                'teacher_id',
                                auth()->id()
                            );
                        }
                    ),
                ],

                'description' => [
                    'required',
                    'string',
                ],

                'category_id' => [
                    'required',
                    'exists:course_categories,id',
                ],

                'price' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'thumbnail' => [
                    'nullable',
                    'image',
                    'mimes:jpeg,png,jpg,webp',
                    'max:4096',
                ],

                'intro_video' => [
                    'nullable',
                    'url',
                    'max:500',
                ],

                'difficulty_level' => [
                    'required',
                    'in:beginner,intermediate,advanced',
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
            ],
            [
                'title.unique' =>
                    'You already have a course with this title. Please use a different title.',
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | SYSTEM VALUES
        |--------------------------------------------------------------------------
        */

        $validated['teacher_id'] =
            auth()->id();

        $validated['status'] =
            'draft';

        $validated['price'] =
            $validated['price'] ?? 0;

        $validated['certificate_available'] =
            $request->boolean(
                'certificate_available'
            );


        /*
        |--------------------------------------------------------------------------
        | THUMBNAIL
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('thumbnail')) {

            $validated['thumbnail'] =
                $request
                    ->file('thumbnail')
                    ->store(
                        'courses',
                        'public'
                    );
        }


        /*
        |--------------------------------------------------------------------------
        | CREATE COURSE
        |--------------------------------------------------------------------------
        */

        Course::create($validated);


        return redirect()
            ->route('teacher.my-courses')
            ->with(
                'success',
                'Course created successfully and saved as draft.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | TEACHER - EDIT COURSE
    |--------------------------------------------------------------------------
    */

    public function teacherEditCourse(
        Course $course
    ) {
        $this->ensureTeacherOwnsCourse(
            $course
        );

        $categories = CourseCategory::orderBy(
            'name'
        )->get();

        return view(
            'teacher.courses.edit',
            compact(
                'course',
                'categories'
            )
        );
    }


    public function teacherUpdateCourse(
        Request $request,
        Course $course
    ) {
        $this->ensureTeacherOwnsCourse(
            $course
        );


        /*
        |--------------------------------------------------------------------------
        | NORMALIZE TITLE
        |--------------------------------------------------------------------------
        */

        $request->merge([
            'title' => trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    (string) $request->title
                )
            ),
        ]);


        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate(
            [
                'title' => [
                    'required',
                    'string',
                    'max:255',

                    Rule::unique(
                        'courses',
                        'title'
                    )
                    ->where(
                        function ($query) {
                            return $query->where(
                                'teacher_id',
                                auth()->id()
                            );
                        }
                    )
                    ->ignore(
                        $course->id
                    ),
                ],

                'description' => [
                    'required',
                    'string',
                ],

                'category_id' => [
                    'required',
                    'exists:course_categories,id',
                ],

                'price' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'thumbnail' => [
                    'nullable',
                    'image',
                    'mimes:jpeg,png,jpg,webp',
                    'max:4096',
                ],

                'intro_video' => [
                    'nullable',
                    'url',
                    'max:500',
                ],

                'difficulty_level' => [
                    'required',
                    'in:beginner,intermediate,advanced',
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
            ],
            [
                'title.unique' =>
                    'You already have a course with this title. Please use a different title.',
            ]
        );


        $validated['price'] =
            $validated['price'] ?? 0;

        $validated['certificate_available'] =
            $request->boolean(
                'certificate_available'
            );


        /*
        |--------------------------------------------------------------------------
        | THUMBNAIL
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('thumbnail')) {

            if ($course->thumbnail) {

                Storage::disk(
                    'public'
                )->delete(
                    $course->thumbnail
                );
            }

            $validated['thumbnail'] =
                $request
                    ->file('thumbnail')
                    ->store(
                        'courses',
                        'public'
                    );
        }


        $course->update($validated);


        return redirect()
            ->route('teacher.my-courses')
            ->with(
                'success',
                'Course details updated successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | TEACHER - SUBMIT COURSE FOR APPROVAL
    |--------------------------------------------------------------------------
    */

    public function submitForApproval(
        Course $course
    ) {
        $this->ensureTeacherOwnsCourse(
            $course
        );


        /*
        |--------------------------------------------------------------------------
        | STATUS CHECK
        |--------------------------------------------------------------------------
        */

        if (
            ! in_array(
                $course->status,
                [
                    'draft',
                    'rejected',
                ],
                true
            )
        ) {
            return redirect()
                ->route(
                    'teacher.my-courses'
                )
                ->with(
                    'error',
                    'This course cannot be submitted for approval in its current status.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | LESSON REQUIREMENT
        |--------------------------------------------------------------------------
        */

        if (
            ! $course
                ->lessons()
                ->exists()
        ) {
            return redirect()
                ->route(
                    'teacher.my-courses'
                )
                ->with(
                    'error',
                    'Please add at least one lesson before submitting the course for approval.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | SUBMIT
        |--------------------------------------------------------------------------
        */

        $course->update([
            'status' =>
                'pending',
        ]);


        return redirect()
            ->route(
                'teacher.my-courses'
            )
            ->with(
                'success',
                'Course submitted for approval successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | TEACHER - COURSE STUDENTS
    |--------------------------------------------------------------------------
    */

    public function teacherCourseStudents(
        Course $course
    ) {
        $this->ensureTeacherOwnsCourse(
            $course
        );

        $enrollments = Enrollment::with(
                'student'
            )
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


    /*
    |--------------------------------------------------------------------------
    | TEACHER - STUDENT PROGRESS
    |--------------------------------------------------------------------------
    */

    public function studentProgress(
        Course $course,
        User $student
    ) {
        $this->ensureTeacherOwnsCourse(
            $course
        );

        $isEnrolled =
            Enrollment::where(
                'course_id',
                $course->id
            )
            ->where(
                'student_id',
                $student->id
            )
            ->exists();

        if (! $isEnrolled) {
            abort(404);
        }


        $lessons =
            $course
                ->lessons()
                ->orderBy(
                    'lesson_order'
                )
                ->get();


        $progressByLesson =
            LessonProgress::where(
                'student_id',
                $student->id
            )
            ->whereIn(
                'lesson_id',
                $lessons->pluck('id')
            )
            ->get()
            ->keyBy(
                'lesson_id'
            );


        $enrollment =
            Enrollment::where(
                'course_id',
                $course->id
            )
            ->where(
                'student_id',
                $student->id
            )
            ->firstOrFail();


        return view(
            'teacher.student-progress',
            compact(
                'course',
                'student',
                'lessons',
                'progressByLesson',
                'enrollment'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TEACHER - DELETE COURSE
|--------------------------------------------------------------------------
*/

public function teacherDestroyCourse(
    Course $course
) {
    /*
    |--------------------------------------------------------------------------
    | OWNERSHIP CHECK
    |--------------------------------------------------------------------------
    */

    $this->ensureTeacherOwnsCourse(
        $course
    );


    /*
    |--------------------------------------------------------------------------
    | STATUS CHECK
    |--------------------------------------------------------------------------
    | Teachers may only delete Draft or Returned/Rejected courses.
    */

    if (
        ! in_array(
            $course->status,
            [
                'draft',
                'rejected',
            ],
            true
        )
    ) {
        return redirect()
            ->route(
                'teacher.my-courses'
            )
            ->with(
                'error',
                'Only draft or returned courses can be deleted.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE THUMBNAIL
    |--------------------------------------------------------------------------
    */

    if ($course->thumbnail) {

        Storage::disk(
            'public'
        )->delete(
            $course->thumbnail
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE COURSE
    |--------------------------------------------------------------------------
    */

    $course->delete();


    return redirect()
        ->route(
            'teacher.my-courses'
        )
        ->with(
            'success',
            'Course deleted successfully.'
        );
}
    /*
    |--------------------------------------------------------------------------
    | ADMIN - COURSE APPROVAL
    |--------------------------------------------------------------------------
    */

    public function approve(
        Course $course
    ) {
        if (
            $course->status
            !==
            'pending'
        ) {
            return redirect()
                ->route(
                    'courses.index'
                )
                ->with(
                    'error',
                    'Only pending courses can be approved.'
                );
        }

        $course->update([
            'status' =>
                'published',
        ]);

        /*
        |--------------------------------------------------------------------------
        | NOTIFY TEACHER
        |--------------------------------------------------------------------------
        */

        $teacher = $course->teacher;

        if ($teacher) {
            $teacher->notify(
                new CourseStatusNotification(
                    $course->id,
                    $course->title,
                    'published'
                )
            );
        }

        return redirect()
            ->route('courses.index')
            ->with(
                'success',
                'Course approved and published successfully.'
            );
    }


    public function reject(
        Course $course
    ) {
        if (
            $course->status
            !==
            'pending'
        ) {
            return redirect()
                ->route(
                    'courses.index'
                )
                ->with(
                    'error',
                    'Only pending courses can be returned for revision.'
                );
        }

        $course->update([
            'status' =>
                'rejected',
        ]);

        /*
        |--------------------------------------------------------------------------
        | NOTIFY TEACHER
        |--------------------------------------------------------------------------
        */

        $teacher = $course->teacher;

        if ($teacher) {
            $teacher->notify(
                new CourseStatusNotification(
                    $course->id,
                    $course->title,
                    'rejected'
                )
            );
        }

        return redirect()
            ->route('courses.index')
            ->with(
                'success',
                'Course returned to the teacher for revision.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | HELPER - COURSE OWNERSHIP
    |--------------------------------------------------------------------------
    */

    private function ensureTeacherOwnsCourse(
        Course $course
    ): void {
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
    }
}