<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseInvitation;
use App\Models\CourseCategory;
use App\Models\CourseReview;
use App\Models\CourseTemplate;
use App\Models\Lesson;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Submission;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\CourseStatusNotification;
use App\Notifications\PathwiseNotification;
use Illuminate\Http\RedirectResponse;
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
        $query = Course::with([
            'category',
            'teacher',
            // For the Preview window on each course card.
            'lessons' => fn ($lessons) => $lessons
                ->select(['id', 'course_id', 'title', 'lesson_type', 'lesson_order', 'duration_minutes', 'is_preview'])
                ->orderBy('lesson_order'),
        ])->manageableBy($request->user());

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
        abort_unless(Course::canBeCreatedByAdministrator(auth()->user()), 403);

        $categories = CourseCategory::orderBy('name')->get();

        return view(
            'courses.create',
            compact('categories')
        );
    }

    public function store(Request $request)
    {
        abort_unless(Course::canBeCreatedByAdministrator($request->user()), 403);

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

            'intro_video' => Course::introVideoRules(),

            'difficulty_level' => [
                'required',
                'in:beginner,intermediate,advanced',
            ],

            'price' => 'required|numeric|min:0',

            'status' => [
                'required',
                'in:draft,pending,rejected,published',
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
        abort_unless($course->isManageableBy(auth()->user()), 403);

        $categories = CourseCategory::orderBy('name')->get();

        return view(
            'courses.edit',
            compact('course', 'categories')
        );
    }

    public function update(Request $request, Course $course)
    {
        abort_unless($course->isManageableBy($request->user()), 403);

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

            'intro_video' => Course::introVideoRules(),

            'difficulty_level' => [
                'required',
                'in:beginner,intermediate,advanced',
            ],

            'price' => 'required|numeric|min:0',

            'status' => [
                'required',
                'in:draft,pending,rejected,published',
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
        return DB::transaction(function () use ($course) {
            // Student Activity writes take this same course lock first.
            $course = Course::query()->lockForUpdate()->findOrFail($course->id);
            abort_unless($course->isManageableBy(auth()->user()), 403);
            abort_if(! auth()->user()->hasRole('super_admin') && $course->hasExternalLessonReferences(), 403);

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

            if ($this->hasProtectedActivityWork($course)) {
                return redirect()->route('courses.index')
                    ->with('error', 'This course cannot be deleted because it has Activity submissions or student progress.');
            }

            $this->deleteLockedCourse($course);

            return redirect()
                ->route('courses.index')
                ->with(
                    'success',
                    'Course deleted successfully.'
                );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | STUDENT - MARKETPLACE
    |--------------------------------------------------------------------------
    */

    public function marketplace()
    {
        $studentId = auth()->id();

        $enrollmentStatuses = Enrollment::where('student_id', $studentId)
            ->pluck('status', 'course_id');

        // Courses the student is already taking or has finished are not
        // offered again. Search, category filter and counts on the page all
        // work on this list, so they leave them out too.
        $ownedCourseIds = $enrollmentStatuses
            ->filter(fn ($status) => in_array($status, Enrollment::ACCESS_STATUSES, true))
            ->keys();

        // Average rating and number of reviews; hidden reviews do not count.
        $courses = Course::with([
            'category',
            'teacher',
        ])
            ->withCount('visibleReviews')
            ->withAvg('visibleReviews', 'rating')
            ->where('status', 'published')
            ->whereNotIn('id', $ownedCourseIds)
            ->latest()
            ->get();

        // Cancelled enrollments stay listed, with a message instead of a
        // way to enroll again.
        $cancelledCourseIds = $enrollmentStatuses
            ->filter(fn ($status) => $status === 'cancelled')
            ->keys()
            ->all();

        // A started but unfinished payment, so the student can continue it.
        $pendingPayments = Transaction::where('student_id', $studentId)
            ->where('status', 'pending')
            ->whereIn('course_id', $courses->pluck('id'))
            ->latest()
            ->get()
            ->unique('course_id')
            ->keyBy('course_id');

        return view(
            'Student.marketplace',
            compact('courses', 'cancelledCourseIds', 'pendingPayments')
        );
    }

    public function showStudentCourse(Course $course)
    {
        abort_unless(
            $course->status === 'published',
            404
        );

        /*
        | Reviews: the average and count leave out hidden reviews, and so
        | does the public list. The student's own review is loaded
        | separately so they also see it when it is hidden.
        */
        $reviewSummary = CourseReviewController::summary($course);

        $reviews = $course->visibleReviews()
            ->with('student:id,name')
            ->latest()
            ->paginate(5, ['*'], 'reviews_page')
            ->fragment('reviews');

        $myReview = CourseReview::where('course_id', $course->id)
            ->where('student_id', auth()->id())
            ->first();

        $canReview = CourseReview::studentCanReview(auth()->user(), $course);

        return view(
            'Student.course-show',
            compact('course', 'reviewSummary', 'reviews', 'myReview', 'canReview')
        );
    }

    public function enroll(Course $course)
    {
        $studentId = (int) auth()->id();
        $enrollment = DB::transaction(function () use ($course, $studentId): RedirectResponse|Enrollment {
            $course = Course::lockForUpdate()->findOrFail($course->id);
            abort_unless($course->status === 'published', 404);

            if ((float) $course->price > 0) {
                return redirect()->route('student.course.show', $course)
                    ->with('error', 'This is a paid course. Please complete the payment to enroll.');
            }

            $existing = Enrollment::where('student_id', $studentId)->where('course_id', $course->id)
                ->lockForUpdate()->first();
            if ($existing) {
                return $existing;
            }

            if (CourseInvitation::redeemedFor($studentId, $course->id)->exists()) {
                return redirect()->route('student.course.show', $course)
                    ->with('error', 'You previously joined this course by invitation. Contact the course administrator for access.');
            }

            if (Transaction::blockingCoursePayment($studentId, $course->id)->lockForUpdate()->first()) {
                return redirect()->route('student.course.show', $course)
                    ->with('error', Transaction::ENROLLMENT_PAYMENT_CONFLICT);
            }

            return Enrollment::firstOrCreate(
                ['student_id' => $studentId, 'course_id' => $course->id],
                ['status' => 'active', 'enrolled_at' => now(), 'progress_percentage' => 0]
            );
        }, 3);

        if ($enrollment instanceof RedirectResponse) {
            return $enrollment;
        }

        if (! $enrollment->grantsAccess()) {
            return redirect()->route('student.course.show', $course)->with(
                'error',
                'Your enrollment in this course was cancelled. Please contact the course administrator to restore it.'
            );
        }

        // In-app notification, plus email when mail is configured.
        if ($enrollment->wasRecentlyCreated) {
            auth()->user()->notify(new PathwiseNotification(
                title: 'Enrollment confirmed',
                message: 'You are now enrolled in "'.$course->title.'".',
                type: 'enrollment_activated',
                courseId: $course->id
            ));
        }

        // Straight into the course the student just joined.
        return redirect()
            ->route('student.learn.course', $course)
            ->with(
                'success',
                $enrollment->wasRecentlyCreated
                    ? 'Successfully enrolled in course.'
                    : 'You are already enrolled in this course.'
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
            ->get()
            ->filter(fn ($enrollment) => $enrollment->course !== null)
            ->values();

        // In Progress, Completed and Cancelled sections. Any other status
        // is treated as in progress, matching the badge on the card.
        $sections = [
            'active' => $enrollments->reject(
                fn ($enrollment) => in_array($enrollment->status, ['completed', 'cancelled'], true)
            )->values(),
            'completed' => $enrollments
                ->where('status', 'completed')
                ->sortByDesc(fn ($enrollment) => $enrollment->completed_at ?? $enrollment->updated_at)
                ->values(),
            'cancelled' => $enrollments->where('status', 'cancelled')->values(),
        ];

        // Issued certificates by course, for the completed cards.
        $certificates = Certificate::where('student_id', auth()->id())
            ->where('status', 'issued')
            ->get()
            ->keyBy('course_id');

        // Courses the student has already reviewed ("Your review" instead
        // of "Rate this course" on the card).
        $reviewedCourseIds = CourseReview::where('student_id', auth()->id())
            ->pluck('course_id')
            ->all();

        return view(
            'Student.my-courses',
            compact('enrollments', 'sections', 'certificates', 'reviewedCourseIds')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TEACHER - MY COURSES
    |--------------------------------------------------------------------------
    */

    public function teacherCourses(Request $request)
    {
        // Review average and count: visible reviews only, like the public
        // course page.
        $query = Course::with('category')
            ->withCount([
                'lessons',
                'enrollments',
                'visibleReviews',
            ])
            ->withAvg('visibleReviews', 'rating')
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

                'intro_video' => Course::introVideoRules(),

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
                'intro_video.url' =>
                    'The introduction video must be a link that starts with https://.',
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
            ? CourseTemplate::find((int) $validated['template_id'])
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

                'intro_video' => Course::introVideoRules(),

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
                'intro_video.url' =>
                    'The introduction video must be a link that starts with https://.',
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

        $redirect = redirect()
            ->route(
                'teacher.my-courses'
            )
            ->with(
                'success',
                'Course submitted for approval successfully.'
            );

        // Department heads only review courses of their own department.
        if (! auth()->user()->department_id) {
            $redirect->with(
                'warning',
                'Your account has no department, so no department head can review this course yet. Please contact the EDP office.'
            );
        }

        return $redirect;
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

        // The progress view lists enrollments; here it is this one student.
        $enrollments = collect([
            $enrollment->load(['student', 'course.category']),
        ]);

        return view(
            'Teacher.student-progress',
            compact(
                'course',
                'student',
                'lessons',
                'progressByLesson',
                'enrollment',
                'enrollments'
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
        return DB::transaction(function () use ($course) {
            // Student Activity writes take this same course lock first.
            $course = Course::query()->lockForUpdate()->findOrFail($course->id);
            $this->ensureTeacherOwnsCourse($course);
            abort_if($course->hasExternalLessonReferences(), 403);

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

            if ($this->hasProtectedActivityWork($course)) {
                return redirect()->route('teacher.my-courses')
                    ->with('error', 'This course cannot be deleted because it has Activity submissions or student progress.');
            }

            $this->deleteLockedCourse($course);

            return redirect()
                ->route(
                    'teacher.my-courses'
                )
                ->with(
                    'success',
                    'Course deleted successfully.'
                );
        });
    }

    private function hasProtectedActivityWork(Course $course): bool
    {
        $submission = Submission::query()
            ->whereHas('assignment', fn ($assignment) => $assignment
                ->where('is_activity', true)
                ->where(fn ($link) => $link->where('course_id', $course->id)
                    ->orWhereHas('lesson', fn ($lesson) => $lesson->where('course_id', $course->id))))
            ->orderBy('id')
            ->lockForUpdate()
            ->first(['id']);

        return $submission !== null || LessonProgress::query()
            ->whereHas('lesson', fn ($lesson) => $lesson
                ->where('course_id', $course->id)
                ->where('lesson_type', 'activity'))
            ->orderBy('id')
            ->lockForUpdate()
            ->first(['id']) !== null;
    }

    private function deleteLockedCourse(Course $course): void
    {
        $thumbnail = $course->thumbnail;
        $course->delete();

        if ($thumbnail) {
            DB::afterCommit(fn () => Storage::disk('public')->delete($thumbnail));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN - COURSE APPROVAL
    |--------------------------------------------------------------------------
    */

    public function approve(Course $course)
    {
        abort_unless($course->isManageableBy(auth()->user()), 403);

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
        abort_unless($course->isManageableBy($request->user()), 403);

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