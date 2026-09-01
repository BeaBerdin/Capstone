<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LessonProgress;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

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

        // Search courses
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Category filter
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category_id', $request->category);
        }

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $courses = $query
            ->latest()
            ->get();

        // Categories for dropdown
        $categories = CourseCategory::orderBy('name')->get();

        return view('courses.index', compact(
            'courses',
            'categories'
        ));
    }

    public function create()
    {
        $categories = CourseCategory::orderBy('name')->get();

        return view('courses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:course_categories,id',
            'title' => 'required|max:255',
            'description' => 'required',
            'intro_video' => 'nullable|max:255',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:draft,pending,approved,rejected,published',
            'estimated_hours' => 'nullable|integer|min:1',
            'certificate_available' => 'nullable',
        ]);

        Course::create([
            'teacher_id' => auth()->id(),
            'category_id' => $request->category_id,
            'title' => $request->title,
            'description' => $request->description,
            'intro_video' => $request->intro_video,
            'difficulty_level' => $request->difficulty_level,
            'price' => $request->price,
            'status' => $request->status,
            'estimated_hours' => $request->estimated_hours,
            'certificate_available' => $request->has('certificate_available'),
        ]);

        return redirect()
            ->route('courses.index')
            ->with('success', 'Course created successfully.');
    }

    public function edit(Course $course)
    {
        $categories = CourseCategory::orderBy('name')->get();

        return view('courses.edit', compact('course', 'categories'));
    }

    public function update(Request $request, Course $course)
    {
        $request->validate([
            'category_id' => 'required|exists:course_categories,id',
            'title' => 'required|max:255',
            'description' => 'required',
            'intro_video' => 'nullable|max:255',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:draft,pending,approved,rejected,published',
            'estimated_hours' => 'nullable|integer|min:1',
            'certificate_available' => 'nullable',
        ]);

        $course->update([
            'category_id' => $request->category_id,
            'title' => $request->title,
            'description' => $request->description,
            'intro_video' => $request->intro_video,
            'difficulty_level' => $request->difficulty_level,
            'price' => $request->price,
            'status' => $request->status,
            'estimated_hours' => $request->estimated_hours,
            'certificate_available' => $request->has('certificate_available'),
        ]);

        return redirect()
            ->route('courses.index')
            ->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()
            ->route('courses.index')
            ->with('success', 'Course deleted successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | STUDENT - MARKETPLACE
    |--------------------------------------------------------------------------
    */

    public function marketplace()
    {
        $courses = Course::with(['category', 'teacher'])
            ->where('status', 'published')
            ->latest()
            ->get();

        return view('student.marketplace', compact('courses'));
    }

    public function showStudentCourse(Course $course)
    {
        return view('student.course-show', compact('course'));
    }

    public function enroll(Course $course)
    {
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
            ->with('success', 'Successfully enrolled in course.');
    }

    public function myCourses()
    {
        $enrollments = Enrollment::with('course')
            ->where('student_id', auth()->id())
            ->latest()
            ->get();

        return view('student.my-courses', compact('enrollments'));
    }

    /*
    |--------------------------------------------------------------------------
    | TEACHER
    |--------------------------------------------------------------------------
    */

    public function teacherCourses(Request $request)
    {
        $query = Course::with('category')
            ->withCount(['lessons', 'enrollments'])
            ->where('teacher_id', auth()->id());

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category_id', $request->category);
        }

        $courses = $query->latest()->get();
        $categories = CourseCategory::orderBy('name')->get();

        return view('teacher.my-courses', compact('courses', 'categories'));
    }

    // ============================================
    // NEW: Create Course (Teacher)
    // ============================================
    public function teacherCreateCourse()
    {
        $categories = CourseCategory::orderBy('name')->get();
        return view('teacher.courses.create', compact('categories'));
    }

    // ============================================
    // NEW: Store Course (Teacher)
    // ============================================
    public function teacherStoreCourse(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:course_categories,id',
            'price' => 'nullable|numeric|min:0',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'intro_video' => 'nullable|url|max:500',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'estimated_hours' => 'nullable|integer|min:1',
            'certificate_available' => 'nullable|boolean',
        ]);

        $validated['teacher_id'] = auth()->id();
        $validated['status'] = 'draft';
        $validated['price'] = $validated['price'] ?? 0;
        $validated['certificate_available'] = $request->boolean('certificate_available');

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')->store('courses', 'public');
        }

        Course::create($validated);

        return redirect()
            ->route('teacher.my-courses')
            ->with('success', 'Course created successfully. Add lessons when you are ready.');
    }

    public function teacherEditCourse(Course $course)
    {
        if ($course->teacher_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $categories = CourseCategory::orderBy('name')->get();

        return view('teacher.courses.edit', compact('course', 'categories'));
    }

    public function teacherUpdateCourse(Request $request, Course $course)
    {
        if ($course->teacher_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:course_categories,id',
            'price' => 'nullable|numeric|min:0',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'intro_video' => 'nullable|url|max:500',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'estimated_hours' => 'nullable|integer|min:1',
            'certificate_available' => 'nullable|boolean',
        ]);

        $validated['price'] = $validated['price'] ?? 0;
        $validated['certificate_available'] = $request->boolean('certificate_available');

        if ($request->hasFile('thumbnail')) {
            if ($course->thumbnail) {
                Storage::disk('public')->delete($course->thumbnail);
            }
            $validated['thumbnail'] = $request->file('thumbnail')->store('courses', 'public');
        }

        $course->update($validated);

        return redirect()
            ->route('teacher.my-courses')
            ->with('success', 'Course details updated successfully.');
    }

    public function teacherCourseStudents(Course $course)
    {
        if ($course->teacher_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $enrollments = Enrollment::with('student')
            ->where('course_id', $course->id)
            ->latest()
            ->get();

        return view('teacher.course-students', compact(
            'course',
            'enrollments'
        ));
    }

    public function studentProgress(Course $course, User $student)
    {
        if ($course->teacher_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $isEnrolled = Enrollment::where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->exists();

        if (! $isEnrolled) {
            abort(404);
        }

        $lessons = $course->lessons()->orderBy('lesson_order')->get();
        $progressByLesson = LessonProgress::where('student_id', $student->id)
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->get()
            ->keyBy('lesson_id');

        $enrollment = Enrollment::where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        return view(
            'teacher.student-progress',
            compact('course', 'student', 'lessons', 'progressByLesson', 'enrollment')
        );
    }

    public function submitForApproval(Course $course)
    {
        if ($course->teacher_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $course->update([
            'status' => 'pending',
        ]);

        return redirect()
            ->route('teacher.my-courses')
            ->with('success', 'Course submitted for approval.');
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN - APPROVAL
    |--------------------------------------------------------------------------
    */

    public function approve(Course $course)
    {
        $course->update([
            'status' => 'published',
        ]);

        return redirect()
            ->route('courses.index')
            ->with('success', 'Course approved and published successfully.');
    }

    public function reject(Course $course)
    {
        $course->update([
            'status' => 'rejected',
        ]);

        return redirect()
            ->route('courses.index')
            ->with('success', 'Course rejected successfully.');
    }
}