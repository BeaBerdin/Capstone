<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseTemplate;
use App\Models\Lesson;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\User;
use App\Notifications\CourseStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $courses = $query->latest()->get();

        $categories = CourseCategory::orderBy('name')->get();

        return view(
            'courses.index',
            compact('courses', 'categories')
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
            'category_id' => 'required|exists:course_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',

            'thumbnail' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120',
            ],

            'intro_video' => 'nullable|max:255',

            'difficulty_level' => [
                'required',
                'in:beginner,intermediate,advanced',
            ],

            'price' => 'required|numeric|min:0',

            'status' => [
                'required',
                'in:draft,pending,approved,rejected,published',
            ],

            'estimated_hours' => 'nullable|integer|min:1',
            'certificate_available' => 'nullable|boolean',
        ]);

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request
                ->file('thumbnail')
                ->store('courses', 'public');
        }

        $validated['teacher_id'] = auth()->id();

        $validated['certificate_available'] =
            $request->boolean('certificate_available');

        Course::create($validated);

        return redirect()
            ->route('courses.index')
            ->with('success', 'Course created successfully.');
    }

    public function edit(Course $course)
    {
        $categories = CourseCategory::orderBy('name')->get();

        return view(
            'courses.edit',
            compact('course', 'categories')
        );
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:course_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',

            'thumbnail' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120',
            ],

            'intro_video' => 'nullable|max:255',

            'difficulty_level' => [
                'required',
                'in:beginner,intermediate,advanced',
            ],

            'price' => 'required|numeric|min:0',

            'status' => [
                'required',
                'in:draft,pending,approved,rejected,published',
            ],

            'estimated_hours' => 'nullable|integer|min:1',
            'certificate_available' => 'nullable|boolean',
        ]);

        if ($request->hasFile('thumbnail')) {
            if (
                $course->thumbnail &&
                Storage::disk('public')->exists($course->thumbnail)
            ) {
                Storage::disk('public')->delete($course->thumbnail);
            }

            $validated['thumbnail'] = $request
                ->file('thumbnail')
                ->store('courses', 'public');
        }

        $validated['certificate_available'] =
            $request->boolean('certificate_available');

        $course->update($validated);

        return redirect()
            ->route('courses.index')
            ->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        if ($course->enrollments()->exists()) {
            return redirect()
                ->route('courses.index')
                ->with(
                    'error',
                    'This course cannot be deleted because it has enrollment history.'
                );
        }

        if ($course->transactions()->exists()) {
            return redirect()
                ->route('courses.index')
                ->with(
                    'error',
                    'This course cannot be deleted because it has transaction history.'
                );
        }

        if ($course->certificates()->exists()) {
            return redirect()
                ->route('courses.index')
                ->with(
                    'error',
                    'This course cannot be deleted because it has certificate history.'
                );
        }

        if ($course->thumbnail) {
            Storage::disk('public')->delete($course->thumbnail);
        }

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
            ->where('status', 'published')
            ->latest()
            ->get();

        return view(
            'Student.marketplace',
            compact('courses')
        );
    }

    public function showStudentCourse(Course $course)
    {
        abort_unless(
            $course->status === 'published',
            404
        );

        return view(
            'Student.course-show',
            compact('course')
        );
    }

    public function enroll(Course $course)
    {
        abort_unless(
            $course->status === 'published',
            404
        );

        /*
         * SECURITY (P0 fix): this route may only enroll students in FREE
         * courses. Paid courses must go through the PayMongo purchase
         * route (student.transactions.store), which creates the
         * enrollment only after the payment is verified.
         */
        if ((float) $course->price > 0) {
            return redirect()
                ->route('student.course.show', $course)
                ->with(
                    'error',
                    'This is a paid course. Please complete the payment to enroll.'
                );
        }

        $existingEnrollment = Enrollment::where('student_id', auth()->id())
            ->where('course_id', $course->id)
            ->first();

        // A cancelled enrollment is not silently reused: the administrator
        // must restore it (Admin > Enrollments).
        if ($existingEnrollment && ! $existingEnrollment->grantsAccess()) {
            return redirect()
                ->route('student.course.show', $course)
                ->with(
                    'error',
                    'Your enrollment in this course was cancelled. Please contact the course administrator to restore it.'
                );
        }

        Enrollment::firstOrCreate(
            [
                'student_id' => auth()->id(),
                'course_id' => $course->id,
            ],
            [
                'status' => 'active',
                'enrolled_at' => now(),
                'progress_percentage' => 0,
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
            'Student.my-courses',
            compact('enrollments')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TEACHER - MY COURSES
    |--------------------------------------------------------------------------
    */

    public function teacherCourses(Request $request)
    {
        $query = Course::with('category')
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

            $query->where(function ($q) use ($search) {
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
            });
        }

        if (
            $request->filled('status') &&
            $request->status !== 'all'
        ) {
            $query->where(
                'status',
                $request->status
            );
        }

        if (
            $request->filled('category') &&
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
            'Teacher.my-courses',
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

        $templates = CourseTemplate::active()
            ->orderBy('name')
            ->get();

        return view(
            'Teacher.courses.create',
            compact('categories', 'templates')
        );
    }

    public function teacherStoreCourse(Request $request)
    {
        $request->merge([
            'title' => trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    (string) $request->title
                )
            ),
        ]);

        $validated = $request->validate(
            [
                'title' => [
                    'required',
                    'string',
                    'max:255',

                    Rule::unique(
                        'courses',
                        'title'
                    )->where(
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

                // Optional built-in curriculum template (must be active).
                'template_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('course_templates', 'id')
                        ->where('is_active', true),
                ],
            ],
            [
                'title.unique' =>
                    'You already have a course with this title. Please use a different title.',
            ]
        );

        $validated['teacher_id'] = auth()->id();

        $validated['status'] = 'draft';

        $validated['price'] =
            $validated['price'] ?? 0;

        $validated['certificate_available'] =
            $request->boolean(
                'certificate_available'
            );

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] =
                $request
                    ->file('thumbnail')
                    ->store(
                        'courses',
                        'public'
                    );
        }

        $template = filled($validated['template_id'] ?? null)
            ? CourseTemplate::find($validated['template_id'])
            : null;

        $validated['template_id'] = $template?->id;

        /*
         * Course templates (Manuscript: pre-designed curriculum templates).
         * When a template is chosen, its lesson outline is copied into the
         * new course as UNPUBLISHED text lessons. Each lesson contains
         * writing guidance that the teacher replaces with real content.
         * Students never see unpublished lessons.
         */
        $createdLessons = DB::transaction(function () use ($validated, $template) {
            $course = Course::create($validated);

            if (! $template) {
                return 0;
            }

            $order = 1;

            foreach ($template->lessonOutline() as $outline) {
                Lesson::create([
                    'course_id' => $course->id,
                    'title' => $outline['title'],
                    'content' => "[Template guide - replace this text with your lesson content, then publish the lesson.]\n\n"
                        . $outline['guidance'],
                    'lesson_type' => 'text',
                    'lesson_order' => $order++,
                    'duration_minutes' => $outline['duration_minutes'],
                    'is_preview' => false,
                    'is_published' => false,
                ]);
            }

            return $order - 1;
        });

        $message = $template
            ? 'Course created from the "' . $template->name . '" template with '
                . $createdLessons . ' draft lessons. Open Manage Lessons to write and publish them.'
            : 'Course created successfully and saved as draft.';

        return redirect()
            ->route('teacher.my-courses')
            ->with(
                'success',
                $message
            );
    }

    /*
    |--------------------------------------------------------------------------
    | TEACHER - EDIT COURSE
    |--------------------------------------------------------------------------
    */

    public function teacherEditCourse(Course $course)
    {
        $this->ensureTeacherOwnsCourse($course);

        if ($redirect = $this->redirectIfCourseLocked($course)) {
            return $redirect;
        }

        $categories = CourseCategory::orderBy(
            'name'
        )->get();

        return view(
            'Teacher.courses.edit',
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
        $this->ensureTeacherOwnsCourse($course);

        if ($redirect = $this->redirectIfCourseLocked($course)) {
            return $redirect;
        }

        $request->merge([
            'title' => trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    (string) $request->title
                )
            ),
        ]);

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
                        ->ignore($course->id),
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

        if ($request->hasFile('thumbnail')) {
            if ($course->thumbnail) {
                Storage::disk('public')->delete(
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
        $this->ensureTeacherOwnsCourse($course);

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
         * A course can only be completed when every lesson is completed, so
         * hidden (unpublished) lessons would make completion impossible.
         * This also catches template outline lessons the teacher has not
         * written yet (Manuscript Figure 28 precondition: "a complete
         * course draft with lessons ready for submission").
         */
        $unpublishedLessons = $course
            ->lessons()
            ->where('is_published', false)
            ->count();

        if ($unpublishedLessons > 0) {
            return redirect()
                ->route(
                    'teacher.my-courses'
                )
                ->with(
                    'error',
                    'This course has ' . $unpublishedLessons
                    . ' unpublished lesson(s). Finish and publish them, or delete them, before submitting for approval.'
                );
        }

        $course->update([
            'status' => 'pending',
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
        $this->ensureTeacherOwnsCourse($course);

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
            'Teacher.course-students',
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
        $this->ensureTeacherOwnsCourse($course);

        $isEnrolled = Enrollment::where(
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

        $lessons = $course
            ->lessons()
            ->orderBy('lesson_order')
            ->get();

        $progressByLesson = LessonProgress::where(
            'student_id',
            $student->id
        )
            ->whereIn(
                'lesson_id',
                $lessons->pluck('id')
            )
            ->get()
            ->keyBy('lesson_id');

        $enrollment = Enrollment::where(
            'course_id',
            $course->id
        )
            ->where(
                'student_id',
                $student->id
            )
            ->firstOrFail();

        return view(
            'Teacher.student-progress',
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
        $this->ensureTeacherOwnsCourse($course);

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

        if ($course->thumbnail) {
            Storage::disk('public')->delete(
                $course->thumbnail
            );
        }

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

    public function approve(Course $course)
    {
        if ($course->status !== 'pending') {
            return redirect()
                ->route('courses.index')
                ->with(
                    'error',
                    'Only pending courses can be approved.'
                );
        }

        $course->update([
            'status' => 'published',
            'review_remarks' => null,
        ]);

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

    public function reject(Request $request, Course $course)
    {
        $validated = $request->validate(
            [
                'remarks' => ['required', 'string', 'min:5', 'max:2000'],
            ],
            [
                'remarks.required' => 'Please write feedback so the teacher knows what to revise.',
            ]
        );

        if ($course->status !== 'pending') {
            return redirect()
                ->route('courses.index')
                ->with(
                    'error',
                    'Only pending courses can be returned for revision.'
                );
        }

        $course->update([
            'status' => 'rejected',
            'review_remarks' => trim($validated['remarks']),
        ]);

        $teacher = $course->teacher;

        if ($teacher) {
            $teacher->notify(
                new CourseStatusNotification(
                    $course->id,
                    $course->title,
                    'rejected',
                    $course->review_remarks
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

    /**
     * Manuscript Figure 28: submitting a course "finalizes and locks the
     * course details for review". Teachers may edit course details only
     * while the course is a draft or has been returned for revision.
     * (Lessons and quizzes already follow the same rule.)
     */
    private function redirectIfCourseLocked(Course $course)
    {
        if (in_array($course->status, ['draft', 'rejected'], true)) {
            return null;
        }

        return redirect()
            ->route('teacher.my-courses')
            ->with(
                'error',
                'Course details cannot be edited while the course is pending approval or published.'
            );
    }

    private function ensureTeacherOwnsCourse(
        Course $course
    ): void {
        if (
            (int) $course->teacher_id !==
            (int) auth()->id()
        ) {
            abort(
                403,
                'Unauthorized'
            );
        }
    }
}