<x-layouts::app :title="__('Admin Dashboard')">

@php
    // Course category & approval stats (Manuscript Screen 5: Course Categories / Courses for Approval)
    $totalCategories = \App\Models\CourseCategory::count();
    $pendingCourses = \App\Models\Course::where('status', 'pending')->count();

    // Certificates & learning path stats (Manuscript Screen 5: Certificates Issued / Active Learning Paths)
    $certificatesIssued = \App\Models\Certificate::count();
    $activeLearningPaths = \App\Models\LearningPath::count();

    $totalTeachers = \App\Models\User::whereHas('roles', function ($query) {
        $query->where('name', 'teacher');
    })->count();

    $publishedCourses = \App\Models\Course::where('status', 'published')->count();

    // Courses Pending Approval List (Manuscript Screen 5 table)
    $coursesPendingApproval = \App\Models\Course::with(['teacher', 'category'])
        ->where('status', 'pending')
        ->latest()
        ->take(5)
        ->get();

    $hour = now()->hour;

    if ($hour < 12) {
        $greeting = 'Good Morning';
    } elseif ($hour < 18) {
        $greeting = 'Good Afternoon';
    } else {
        $greeting = 'Good Evening';
    }

    $currentDate = now()->format('l, F d, Y');
@endphp

<div class="space-y-6">

    {{-- Hero Banner --}}
    <div class="rounded-2xl border border-purple-500/30 bg-linear-to-r from-purple-900 via-neutral-900 to-neutral-900 p-8 shadow-lg">
        <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-medium text-purple-300">
                    PathWise Admin Portal
                </p>

                <h1 class="mt-2 text-4xl font-bold text-white">
                    {{ $greeting }}, Department Head 👋
                </h1>

                <p class="mt-2 text-sm text-gray-300">
                    {{ $currentDate }}
                </p>

                <p class="mt-4 max-w-2xl text-sm text-gray-300">
                    Manage course categories, review course submissions, and oversee
                    academic content across the PathWise platform.
                </p>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('courses.index') }}"
                   class="rounded-xl bg-green-600 px-5 py-3 text-sm font-semibold text-white hover:bg-green-700">
                    Approve/Reject Courses
                </a>

                <a href="{{ route('course-categories.index') }}"
                   class="rounded-xl border border-neutral-700 bg-neutral-800 px-5 py-3 text-sm font-semibold text-white transition hover:border-neutral-500 hover:bg-neutral-700">
                    Manage Categories
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Row 1 --}}
    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
            <p class="text-sm text-gray-400">Total Students</p>
            <h2 class="mt-2 text-3xl font-bold text-white">{{ $totalStudents }}</h2>
            <p class="mt-1 text-xs text-gray-400">Registered learners</p>
        </div>

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
            <p class="text-sm text-gray-400">Total Teachers</p>
            <h2 class="mt-2 text-3xl font-bold text-white">{{ $totalTeachers }}</h2>
            <p class="mt-1 text-xs text-gray-400">Course instructors</p>
        </div>

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
            <p class="text-sm text-gray-400">Total Courses</p>
            <h2 class="mt-2 text-3xl font-bold text-white">{{ $totalCourses }}</h2>
            <p class="mt-1 text-xs text-gray-400">{{ $publishedCourses }} published courses</p>
        </div>

        <div class="rounded-2xl border border-green-500/40 bg-neutral-900 p-5">
            <p class="text-sm text-green-400">System Status</p>
            <h2 class="mt-2 text-3xl font-bold text-green-400">Online</h2>
            <p class="mt-1 text-xs text-gray-400">All services operating</p>
        </div>
    </div>

    {{-- Stats Row 2 (Manuscript Screen 5: Course Categories / Courses for Approval / Certificates Issued / Active Learning Paths) --}}
    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
            <p class="text-sm text-gray-400">Course Categories</p>
            <h2 class="mt-2 text-3xl font-bold text-white">{{ $totalCategories }}</h2>
            <p class="mt-1 text-xs text-gray-400">Active learning categories</p>
        </div>

        <div class="rounded-2xl border border-yellow-500/40 bg-neutral-900 p-5">
            <p class="text-sm text-yellow-400">Courses for Approval</p>
            <h2 class="mt-2 text-3xl font-bold text-yellow-400">{{ $pendingCourses }}</h2>
            <p class="mt-1 text-xs text-gray-400">Waiting for admin review</p>
        </div>

        <div class="rounded-2xl border border-green-500/40 bg-neutral-900 p-5">
            <p class="text-sm text-green-400">Certificates Issued</p>
            <h2 class="mt-2 text-3xl font-bold text-green-400">{{ $certificatesIssued }}</h2>
            <p class="mt-1 text-xs text-gray-400">Completion certificates</p>
        </div>

        <div class="rounded-2xl border border-purple-500/40 bg-neutral-900 p-5">
            <p class="text-sm text-purple-400">Active Learning Paths</p>
            <h2 class="mt-2 text-3xl font-bold text-purple-400">{{ $activeLearningPaths }}</h2>
            <p class="mt-1 text-xs text-gray-400">Guided course sequences</p>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">

        {{-- Quick Actions (Manuscript Screen 5 button list) --}}
        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
            <h3 class="text-lg font-semibold text-white">Quick Actions</h3>

            <div class="mt-4 space-y-3">
                <a href="{{ route('course-categories.index') }}" class="flex items-center justify-between rounded-xl border border-neutral-700 bg-neutral-800 px-4 py-3 transition hover:bg-neutral-700">
                    <div>
                        <p class="text-sm font-semibold text-white"> Manage Course Categories</p>
                        <p class="text-xs text-gray-400">Add, edit, or delete categories</p>
                    </div>
                    <span class="text-gray-400">→</span>
                </a>

                <a href="{{ route('courses.index') }}"
                   class="flex items-center justify-between rounded-xl border border-yellow-500/40 bg-neutral-800 px-4 py-3 transition hover:bg-neutral-700">
                    <div>
                        <p class="text-sm font-semibold text-yellow-400"> Approve/Reject Courses</p>
                        <p class="text-xs text-gray-400">Review courses submitted by teachers</p>
                    </div>

                    @if($pendingCourses > 0)
                        <span class="rounded-full bg-yellow-400 px-2 py-1 text-xs font-bold text-black">
                            {{ $pendingCourses }}
                        </span>
                    @else
                        <span class="text-yellow-400">→</span>
                    @endif
                </a>

                <a href="{{ route('courses.index') }}" class="block rounded-xl border border-neutral-700 bg-neutral-800 px-4 py-3 transition hover:bg-neutral-700">
                    <p class="text-sm font-semibold text-white"> Manage Courses</p>
                    <p class="text-xs text-gray-400">Review and maintain course content</p>
                </a>

                <a href="{{ route('lessons.index') }}" class="block rounded-xl border border-neutral-700 bg-neutral-800 px-4 py-3 transition hover:bg-neutral-700">
                    <p class="text-sm font-semibold text-white"> Manage Lessons</p>
                    <p class="text-xs text-gray-400">Monitor learning materials</p>
                </a>

                <a href="{{ route('quizzes.index') }}" class="block rounded-xl border border-neutral-700 bg-neutral-800 px-4 py-3 transition hover:bg-neutral-700">
                    <p class="text-sm font-semibold text-white"> Manage Quizzes</p>
                    <p class="text-xs text-gray-400">Check quizzes and assessments</p>
                </a>

                <a href="{{ route('quiz-questions.index') }}" class="block rounded-xl border border-neutral-700 bg-neutral-800 px-4 py-3 transition hover:bg-neutral-700">
                    <p class="text-sm font-semibold text-white"> Manage Quiz Questions</p>
                    <p class="text-xs text-gray-400">Maintain assessment content</p>
                </a>

                <a href="{{ route('certificate-management.index') }}" class="block rounded-xl border border-neutral-700 bg-neutral-800 px-4 py-3 transition hover:bg-neutral-700">
                    <p class="text-sm font-semibold text-white"> Manage Certificates</p>
                    <p class="text-xs text-gray-400">Configure and issue completion certificates</p>
                </a>

                <a href="{{ route('ai-recommendations.index') }}" class="block rounded-xl border border-purple-500/40 bg-neutral-800 px-4 py-3 transition hover:bg-neutral-700">
                    <p class="text-sm font-semibold text-purple-400"> Manage AI Recommendations</p>
                    <p class="text-xs text-gray-400">Configure AI-based course suggestions</p>
                </a>

                <a href="{{ route('learning-paths.index') }}" class="block rounded-xl border border-neutral-700 bg-neutral-800 px-4 py-3 transition hover:bg-neutral-700">
                    <p class="text-sm font-semibold text-white"> Manage Learning Paths</p>
                    <p class="text-xs text-gray-400">Configure guided course sequences</p>
                </a>

                <a href="{{ route('student-progress.index') }}" class="block rounded-xl border border-neutral-700 bg-neutral-800 px-4 py-3 transition hover:bg-neutral-700">
                    <p class="text-sm font-semibold text-white"> Student Progress Reports</p>
                    <p class="text-xs text-gray-400">Track learner completion</p>
                </a>
            </div>
        </div>

        {{-- Course Approval Workflow --}}
        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5 lg:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-white">Course Approval Workflow</h3>
                    <p class="mt-1 text-sm text-gray-400">
                        Review and approve course content submitted by teachers.
                    </p>
                </div>

                <a href="{{ route('courses.index') }}"
                   class="rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                    Review Courses
                </a>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-4">
                <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4">
                    <p class="text-xs text-gray-500">Step 1</p>
                    <h4 class="mt-1 font-semibold text-white"> Teacher Creates Course</h4>
                    <p class="mt-1 text-xs text-gray-400">Teacher builds course content and lessons.</p>
                </div>

                <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4">
                    <p class="text-xs text-gray-500">Step 2</p>
                    <h4 class="mt-1 font-semibold text-white"> Submits for Approval</h4>
                    <p class="mt-1 text-xs text-gray-400">Course is sent for admin review.</p>
                </div>

                <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4">
                    <p class="text-xs text-gray-500">Step 3</p>
                    <h4 class="mt-1 font-semibold text-white"> Admin Reviews</h4>
                    <p class="mt-1 text-xs text-gray-400">Course is approved or rejected.</p>
                </div>

                <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4">
                    <p class="text-xs text-gray-500">Step 4</p>
                    <h4 class="mt-1 font-semibold text-white"> Published</h4>
                    <p class="mt-1 text-xs text-gray-400">Approved course appears in the marketplace.</p>
                </div>
            </div>

            <div class="mt-6">
                <h4 class="mb-3 font-semibold text-white">Courses Pending Approval</h4>

                <div class="overflow-x-auto rounded-xl border border-neutral-700">
                    <table class="w-full text-sm">
                        <thead class="bg-neutral-800">
                            <tr class="text-left text-gray-400">
                                <th class="px-4 py-3">Course Title</th>
                                <th class="px-4 py-3">Teacher</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Submitted</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-neutral-800">
                            @forelse($coursesPendingApproval as $course)
                                <tr class="hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-white">
                                        {{ $course->title }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-400">
                                        {{ $course->teacher->name ?? 'Teacher unavailable' }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-400">
                                        {{ $course->category->name ?? 'Uncategorized' }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-400">
                                        {{ $course->created_at->format('M d, Y') }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="rounded-full bg-yellow-500/15 px-3 py-1 text-xs font-semibold text-yellow-400">
                                            Pending
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                        No courses pending approval.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- System Modules --}}
    <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
        <h3 class="text-lg font-semibold text-white">PathWise System Modules</h3>

        <div class="mt-4 grid gap-3 md:grid-cols-3">

            <a href="{{ route('courses.index') }}"
               class="block rounded-xl border border-neutral-700 bg-neutral-800 p-4 transition hover:border-purple-500 hover:bg-neutral-700">
                <h4 class="font-semibold text-white">
                     Course Marketplace
                </h4>
                <p class="mt-1 text-sm text-gray-400">
                    Students browse paid and published courses.
                </p>
            </a>

            <a href="{{ route('course-categories.index') }}"
               class="block rounded-xl border border-neutral-700 bg-neutral-800 p-4 transition hover:border-purple-500 hover:bg-neutral-700">
                <h4 class="font-semibold text-white">
                     Category Management
                </h4>
                <p class="mt-1 text-sm text-gray-400">
                    Organize how courses are grouped for learners.
                </p>
            </a>

            <a href="{{ route('courses.index') }}"
               class="block rounded-xl border border-neutral-700 bg-neutral-800 p-4 transition hover:border-purple-500 hover:bg-neutral-700">
                <h4 class="font-semibold text-white">
                     Course Management
                </h4>
                <p class="mt-1 text-sm text-gray-400">
                    Teachers create courses, lessons, quizzes, and materials.
                </p>
            </a>

            <a href="{{ route('quizzes.index') }}"
               class="block rounded-xl border border-neutral-700 bg-neutral-800 p-4 transition hover:border-purple-500 hover:bg-neutral-700">
                <h4 class="font-semibold text-white">
                     Assessment Management
                </h4>
                <p class="mt-1 text-sm text-gray-400">
                    Manages quizzes, questions, and assessment results.
                </p>
            </a>

            <a href="{{ route('ai-recommendations.index') }}"
               class="block rounded-xl border border-purple-500/40 bg-neutral-800 p-4 transition hover:bg-neutral-700">
                <h4 class="font-semibold text-purple-400">
                     AI Recommendation Engine
                </h4>
                <p class="mt-1 text-sm text-gray-400">
                    Recommends next courses based on learner performance.
                </p>
            </a>

            <a href="{{ route('student-progress.index') }}"
               class="block rounded-xl border border-neutral-700 bg-neutral-800 p-4 transition hover:border-purple-500 hover:bg-neutral-700">
                <h4 class="font-semibold text-white">
                     Performance Tracker
                </h4>
                <p class="mt-1 text-sm text-gray-400">
                    Tracks progress, quiz results, completion, and certificates.
                </p>
            </a>

        </div>
    </div>

</div>

</x-layouts::app>