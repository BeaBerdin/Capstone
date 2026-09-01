<x-layouts::app :title="__('Admin Dashboard')">

@php
    // Use your local timezone — change 'Asia/Manila' if your region differs
    $localNow = now('Asia/Manila');

    $hour = $localNow->hour;

    if ($hour < 12) {
        $greeting = 'Good Morning';
    } elseif ($hour < 18) {
        $greeting = 'Good Afternoon';
    } else {
        $greeting = 'Good Evening';
    }

    $currentDate = $localNow->format('l, F d, Y');

    // Missing stats
    $totalStudents = \App\Models\User::whereHas('roles', function ($query) {
        $query->where('name', 'student');
    })->count();

    $totalCourses = \App\Models\Course::count();

    // Course category & approval stats
    $totalCategories = \App\Models\CourseCategory::count();
    $pendingCourses = \App\Models\Course::where('status', 'pending')->count();

    // Certificates & learning path stats
    $certificatesIssued = \App\Models\Certificate::count();
    $activeLearningPaths = \App\Models\LearningPath::count();

    $totalTeachers = \App\Models\User::whereHas('roles', function ($query) {
        $query->where('name', 'teacher');
    })->count();

    $publishedCourses = \App\Models\Course::where('status', 'published')->count();

    // Courses Pending Approval
    $coursesPendingApproval = \App\Models\Course::with(['teacher', 'category'])
        ->where('status', 'pending')
        ->latest()
        ->take(5)
        ->get();
@endphp


<div class="space-y-8">

    {{-- ========================================================= --}}
    {{-- WELCOME HEADER --}}
    {{-- ========================================================= --}}
    <section class="rounded-xl border border-gray-200 bg-white px-6 py-7 shadow-sm dark:border-gray-700 dark:bg-gray-900">

        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">

            <div>
                <p class="text-sm font-semibold text-purple-600 dark:text-purple-400">
                    PATHWISE ADMIN PORTAL
                </p>

                <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                    {{ $greeting }}, Department Head
                </h1>

                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ $currentDate }}
                </p>

                <p class="mt-4 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                    Manage courses, learning content, certificates, student progress,
                    and academic activities across the PATHWISE platform.
                </p>
            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- PLATFORM OVERVIEW --}}
    {{-- ========================================================= --}}
    <section>

        <div class="mb-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                Platform Overview
            </h2>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Overview of the current PATHWISE academic platform.
            </p>
        </div>


        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">


            {{-- Students --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">

                <div class="flex items-start justify-between">

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Total Students
                        </p>

                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $totalStudents }}
                        </p>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Registered learners
                        </p>
                    </div>

                    <div class="rounded-lg bg-purple-50 p-3 dark:bg-purple-500/10">
                        <svg class="h-6 w-6 text-purple-600 dark:text-purple-400"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-8a4 4 0 11-8 0 4 4 0 018 0zm6 3a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>

                </div>

            </div>


            {{-- Teachers --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">

                <div class="flex items-start justify-between">

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Total Teachers
                        </p>

                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $totalTeachers }}
                        </p>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Course instructors
                        </p>
                    </div>

                    <div class="rounded-lg bg-blue-50 p-3 dark:bg-blue-500/10">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M12 14l9-5-9-5-9 5 9 5z"/>
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M12 14l6.16-3.42A12.08 12.08 0 0118 15.5c0 2.49-2.69 4.5-6 4.5s-6-2.01-6-4.5c0-.58.13-1.14.37-1.66L12 14z"/>
                        </svg>
                    </div>

                </div>

            </div>


            {{-- Courses --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">

                <div class="flex items-start justify-between">

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Total Courses
                        </p>

                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $totalCourses }}
                        </p>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $publishedCourses }} published courses
                        </p>
                    </div>

                    <div class="rounded-lg bg-green-50 p-3 dark:bg-green-500/10">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5s3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>

                </div>

            </div>


            {{-- Pending --}}
            <div class="rounded-xl border border-yellow-200 bg-white p-5 shadow-sm dark:border-yellow-500/30 dark:bg-gray-900">

                <div class="flex items-start justify-between">

                    <div>
                        <p class="text-sm font-medium text-yellow-600 dark:text-yellow-400">
                            Courses for Approval
                        </p>

                        <p class="mt-2 text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                            {{ $pendingCourses }}
                        </p>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Waiting for review
                        </p>
                    </div>

                    <div class="rounded-lg bg-yellow-50 p-3 dark:bg-yellow-500/10">
                        <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>

                </div>

            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- ACADEMIC STATISTICS --}}
    {{-- ========================================================= --}}
    <section>

        <div class="mb-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                Academic Information
            </h2>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Current academic resources available on the platform.
            </p>
        </div>


        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">


            {{-- Categories --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">

                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Course Categories
                </p>

                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $totalCategories }}
                </p>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Active learning categories
                </p>

            </div>


            {{-- Certificates --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">

                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Certificates Issued
                </p>

                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $certificatesIssued }}
                </p>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Completion certificates
                </p>

            </div>


            {{-- Learning Paths --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">

                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Active Learning Paths
                </p>

                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $activeLearningPaths }}
                </p>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Guided course sequences
                </p>

            </div>

        </div>

    </section>


   
        {{-- ===================================================== --}}
        {{-- COURSE APPROVAL --}}
        {{-- ===================================================== --}}
        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm xl:col-span-2 dark:border-gray-700 dark:bg-gray-900">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        Course Approval
                    </h2>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Review courses submitted by teachers.
                    </p>
                </div>

                <a href="{{ route('courses.index') }}"
                   class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-700">

                    Review Courses

                </a>

            </div>


            {{-- Workflow --}}
            <div class="mt-6 grid gap-3 md:grid-cols-4">

                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="text-xs font-semibold text-purple-600 dark:text-purple-400">
                        STEP 1
                    </p>

                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                        Teacher Creates
                    </h3>

                    <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">
                        Teacher builds course content and lessons.
                    </p>
                </div>


                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="text-xs font-semibold text-purple-600 dark:text-purple-400">
                        STEP 2
                    </p>

                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                        Submit
                    </h3>

                    <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">
                        Course is sent for admin review.
                    </p>
                </div>


                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="text-xs font-semibold text-purple-600 dark:text-purple-400">
                        STEP 3
                    </p>

                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                        Admin Reviews
                    </h3>

                    <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">
                        Course is approved or rejected.
                    </p>
                </div>


                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="text-xs font-semibold text-purple-600 dark:text-purple-400">
                        STEP 4
                    </p>

                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                        Published
                    </h3>

                    <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">
                        Approved course appears in the marketplace.
                    </p>
                </div>

            </div>


            {{-- Pending Courses Table --}}
            <div class="mt-7">

                <div class="mb-3 flex items-center justify-between">

                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            Courses Pending Approval
                        </h3>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Latest course submissions
                        </p>
                    </div>

                    @if($pendingCourses > 0)

                        <span class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-300">
                            {{ $pendingCourses }} Pending
                        </span>

                    @endif

                </div>


                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">

                    <div class="overflow-x-auto">

                        <table class="w-full text-left text-sm">

                            <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">

                                <tr>
                                    <th class="px-5 py-3 font-semibold">
                                        Course
                                    </th>

                                    <th class="px-5 py-3 font-semibold">
                                        Teacher
                                    </th>

                                    <th class="px-5 py-3 font-semibold">
                                        Category
                                    </th>

                                    <th class="px-5 py-3 font-semibold">
                                        Submitted
                                    </th>

                                    <th class="px-5 py-3 font-semibold">
                                        Status
                                    </th>
                                </tr>

                            </thead>


                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">

                                @forelse($coursesPendingApproval as $course)

                                    <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/60">

                                        <td class="px-5 py-4">

                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                {{ $course->title }}
                                            </p>

                                        </td>


                                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">
                                            {{ $course->teacher->name ?? 'Teacher unavailable' }}
                                        </td>


                                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">
                                            {{ $course->category->name ?? 'Uncategorized' }}
                                        </td>


                                        <td class="px-5 py-4 text-gray-500 dark:text-gray-400">
                                            {{ $course->created_at->format('M d, Y') }}
                                        </td>


                                        <td class="px-5 py-4">

                                            <span class="inline-flex rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-semibold text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-300">
                                                Pending
                                            </span>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="5"
                                            class="px-5 py-10 text-center">

                                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                No courses pending approval.
                                            </p>

                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                New teacher submissions will appear here.
                                            </p>

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </section>

    </div>


    {{-- ========================================================= --}}
    {{-- PATHWISE SYSTEM MODULES --}}
    {{-- ========================================================= --}}
    <section>

        <div class="mb-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                PATHWISE System Modules
            </h2>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Core academic and learning management functions.
            </p>
        </div>


        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">


            <a href="{{ route('courses.index') }}"
               class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-purple-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-900 dark:hover:border-purple-500/50">

                <div class="flex items-center justify-between">

                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        Course Marketplace
                    </h3>

                    <span class="text-gray-400 transition group-hover:translate-x-1 group-hover:text-purple-600">
                        →
                    </span>

                </div>

                <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                    Students browse paid and published courses.
                </p>

            </a>


            <a href="{{ route('course-categories.index') }}"
               class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-purple-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-900 dark:hover:border-purple-500/50">

                <div class="flex items-center justify-between">

                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        Category Management
                    </h3>

                    <span class="text-gray-400 transition group-hover:translate-x-1 group-hover:text-purple-600">
                        →
                    </span>

                </div>

                <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                    Organize how courses are grouped for learners.
                </p>

            </a>


            <a href="{{ route('courses.index') }}"
               class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-purple-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-900 dark:hover:border-purple-500/50">

                <div class="flex items-center justify-between">

                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        Course Management
                    </h3>

                    <span class="text-gray-400 transition group-hover:translate-x-1 group-hover:text-purple-600">
                        →
                    </span>

                </div>

                <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                    Teachers create courses, lessons, quizzes, and materials.
                </p>

            </a>


            <a href="{{ route('quizzes.index') }}"
               class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-purple-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-900 dark:hover:border-purple-500/50">

                <div class="flex items-center justify-between">

                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        Assessment Management
                    </h3>

                    <span class="text-gray-400 transition group-hover:translate-x-1 group-hover:text-purple-600">
                        →
                    </span>

                </div>

                <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                    Manages quizzes, questions, and assessment results.
                </p>

            </a>


            <a href="{{ route('ai-recommendations.index') }}"
               class="group rounded-xl border border-purple-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:bg-purple-50 hover:shadow-md dark:border-purple-500/30 dark:bg-gray-900 dark:hover:bg-purple-500/10">

                <div class="flex items-center justify-between">

                    <h3 class="font-semibold text-purple-700 dark:text-purple-400">
                        AI Recommendation Engine
                    </h3>

                    <span class="text-purple-500 transition group-hover:translate-x-1">
                        →
                    </span>

                </div>

                <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                    Recommends next courses based on learner performance.
                </p>

            </a>


            <a href="{{ route('student-progress.index') }}"
               class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-purple-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-900 dark:hover:border-purple-500/50">

                <div class="flex items-center justify-between">

                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        Performance Tracker
                    </h3>

                    <span class="text-gray-400 transition group-hover:translate-x-1 group-hover:text-purple-600">
                        →
                    </span>

                </div>

                <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                    Tracks progress, quiz results, completion, and certificates.
                </p>

            </a>

        </div>

    </section>

</div>

</x-layouts::app>