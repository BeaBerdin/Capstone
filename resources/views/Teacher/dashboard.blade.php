<x-layouts::app :title="'Teacher Dashboard'">

@php
    $recentCourses = \App\Models\Course::withCount(['lessons', 'enrollments'])
        ->where('teacher_id', auth()->id())
        ->latest()
        ->take(4)
        ->get();

    $recentQuizResults = \App\Models\QuizResult::with(['student', 'quiz.course'])
        ->whereHas('quiz.course', function ($query) {
            $query->where('teacher_id', auth()->id());
        })
        ->latest()
        ->take(5)
        ->get();

    $publishedCourses = \App\Models\Course::where('teacher_id', auth()->id())
        ->where('status', 'published')
        ->count();

    $pendingCourses = \App\Models\Course::where('teacher_id', auth()->id())
        ->where('status', 'pending')
        ->count();

    $averageScore = \App\Models\QuizResult::whereHas('quiz.course', function ($query) {
            $query->where('teacher_id', auth()->id());
        })
        ->avg('percentage');

    $averageScore = $averageScore ? round($averageScore, 2) : 0;

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
    <div class="rounded-2xl border border-purple-500/30 bg-linear-to-r from-purple-900 via-neutral-900 to-neutral-900 p-6">
        <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-medium text-purple-300">
                    PathWise Teaching Portal
                </p>

                <h1 class="mt-2 text-4xl font-bold text-white">
                    {{ $greeting }}, {{ auth()->user()->name }} 👋
                </h1>

                <p class="mt-2 text-sm text-purple-200">
                    {{ $currentDate }}
                </p>

                <p class="mt-4 max-w-2xl text-gray-400">
                    Ready to inspire your students today? Manage your courses, monitor learner progress,
                    review quiz performance, and improve your teaching content.
                </p>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('teacher.my-courses') }}"
                   class="rounded-xl bg-purple-600 px-5 py-3 text-sm font-semibold text-white hover:bg-purple-700">
                    View My Courses
                </a>

                <a href="{{ route('teacher.courses') }}"
                   class="rounded-xl border border-neutral-700 bg-neutral-800 px-5 py-3 text-sm font-semibold text-white hover:border-neutral-500 hover:bg-neutral-700">
                    Manage Courses
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="grid gap-4 md:grid-cols-4">

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
            <p class="text-sm text-gray-400">Total Courses</p>
            <h2 class="mt-2 text-3xl font-bold text-white">{{ $totalCourses }}</h2>
            <p class="mt-1 text-xs text-gray-400">All teacher-created courses</p>
        </div>

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
            <p class="text-sm text-gray-400">Total Lessons</p>
            <h2 class="mt-2 text-3xl font-bold text-white">{{ $totalLessons }}</h2>
            <p class="mt-1 text-xs text-gray-400">Learning materials uploaded</p>
        </div>

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
            <p class="text-sm text-gray-400">Total Students</p>
            <h2 class="mt-2 text-3xl font-bold text-white">{{ $totalStudents }}</h2>
            <p class="mt-1 text-xs text-gray-400">Students enrolled in courses</p>
        </div>

        <div class="rounded-2xl border border-green-500/40 bg-neutral-900 p-5">
            <p class="text-sm text-green-400">Average Quiz Score</p>
            <h2 class="mt-2 text-3xl font-bold text-green-400">{{ $averageScore }}%</h2>
            <p class="mt-1 text-xs text-gray-400">Based on student attempts</p>
        </div>

    </div>

    <div class="grid gap-4 lg:grid-cols-3">

        {{-- Quick Actions --}}
        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">

            <h3 class="text-lg font-semibold text-white">
                Quick Actions
            </h3>

            <div class="mt-4 grid gap-3">

                <a href="{{ route('teacher.my-courses') }}"
                   class="rounded-xl border border-purple-500/40 bg-purple-500/10 p-4 transition hover:bg-purple-500/20">
                    <p class="font-semibold text-purple-300">My Courses</p>
                    <p class="mt-1 text-xs text-gray-400">View and manage your course library</p>
                </a>

                <a href="{{ route('teacher.my-courses') }}"
                   class="rounded-xl border border-neutral-700 bg-neutral-800 p-4 transition hover:bg-neutral-700">
                    <p class="font-semibold text-white">Create Lessons</p>
                    <p class="mt-1 text-xs text-gray-400">Add lessons and learning content</p>
                </a>

                <a href="{{ route('teacher.quiz-results.index') }}"
                   class="rounded-xl border border-neutral-700 bg-neutral-800 p-4 transition hover:bg-neutral-700">
                    <p class="font-semibold text-white">Quiz Results</p>
                    <p class="mt-1 text-xs text-gray-400">Review student quiz performance</p>
                </a>

                <a href="{{ route('teacher.student-progress.index') }}"
                   class="rounded-xl border border-neutral-700 bg-neutral-800 p-4 transition hover:bg-neutral-700">
                    <p class="font-semibold text-white">Student Progress</p>
                    <p class="mt-1 text-xs text-gray-400">Monitor learning completion</p>
                </a>

            </div>
        </div>

        {{-- Course Publishing Overview --}}
        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5 lg:col-span-2">

            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-white">
                        Course Publishing Overview
                    </h3>
                    <p class="text-sm text-gray-400">
                        Track the approval and publishing status of your courses.
                    </p>
                </div>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-3">

                <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4">
                    <p class="text-sm text-gray-400">Published</p>
                    <h4 class="mt-2 text-2xl font-bold text-green-400">{{ $publishedCourses }}</h4>
                </div>

                <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4">
                    <p class="text-sm text-gray-400">Pending Approval</p>
                    <h4 class="mt-2 text-2xl font-bold text-yellow-400">{{ $pendingCourses }}</h4>
                </div>

                <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4">
                    <p class="text-sm text-gray-400">Enrollments</p>
                    <h4 class="mt-2 text-2xl font-bold text-blue-400">{{ $totalEnrollments }}</h4>
                </div>

            </div>

            <div class="mt-6">
                <h4 class="mb-3 font-semibold text-white">
                    Recent Courses
                </h4>

                <div class="overflow-x-auto rounded-xl border border-neutral-700">
                    <table class="w-full text-sm">
                        <thead class="bg-neutral-800 text-left text-xs uppercase tracking-wider text-white">
                            <tr>
                                <th class="px-4 py-3">Course</th>
                                <th class="px-4 py-3">Lessons</th>
                                <th class="px-4 py-3">Students</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-neutral-800">
                            @forelse($recentCourses as $course)
                                <tr class="hover:bg-white/5">
                                    <td class="px-4 py-3 font-semibold text-white">
                                        {{ $course->title }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-400">
                                        {{ $course->lessons_count }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-400">
                                        {{ $course->enrollments_count }}
                                    </td>

                                    <td class="px-4 py-3">
                                        @if($course->status === 'published')
                                            <span class="rounded-full bg-green-500/15 px-3 py-1 text-xs font-semibold text-green-400">
                                                Published
                                            </span>
                                        @elseif($course->status === 'pending')
                                            <span class="rounded-full bg-yellow-500/15 px-3 py-1 text-xs font-semibold text-yellow-400">
                                                Pending
                                            </span>
                                        @elseif($course->status === 'rejected')
                                            <span class="rounded-full bg-red-500/15 px-3 py-1 text-xs font-semibold text-red-400">
                                                Rejected
                                            </span>
                                        @else
                                            <span class="rounded-full bg-gray-500/15 px-3 py-1 text-xs font-semibold text-gray-400">
                                                {{ ucfirst($course->status) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center">
                                        <h3 class="text-lg font-semibold text-white">No courses created yet</h3>
                                        <p class="mt-1 text-sm text-gray-400">
                                            Start creating your first course to see it here.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

    <div class="grid gap-4 lg:grid-cols-2">

        {{-- Recent Student Activity --}}
        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">

            <h3 class="text-lg font-semibold text-white">
                Recent Student Activity
            </h3>

            <p class="mt-1 text-sm text-gray-400">
                Latest quiz submissions from learners in your courses.
            </p>

            <div class="mt-4 space-y-3">
                @forelse($recentQuizResults as $result)
                    <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4">
                        <div class="flex justify-between gap-4">
                            <div>
                                <p class="font-semibold text-white">
                                    {{ $result->student->name ?? 'Student' }}
                                </p>

                                <p class="mt-1 text-sm text-gray-400">
                                    {{ $result->quiz->title ?? 'Quiz' }}
                                </p>

                                <p class="text-xs text-gray-500">
                                    {{ $result->quiz->course->title ?? 'Course unavailable' }}
                                </p>
                            </div>

                            <div class="text-right">
                                <p class="font-bold text-white">
                                    {{ $result->percentage }}%
                                </p>

                                @if($result->remarks === 'passed')
                                    <span class="text-xs font-semibold text-green-400">Passed</span>
                                @else
                                    <span class="text-xs font-semibold text-red-400">Failed</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4 text-center">
                        <p class="text-sm text-gray-400">
                            No recent student activity yet.
                        </p>
                    </div>
                @endforelse
            </div>

        </div>

        {{-- Teaching Analytics --}}
        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">

            <h3 class="text-lg font-semibold text-white">
                Teaching Analytics
            </h3>

            <p class="mt-1 text-sm text-gray-400">
                Summary of your teaching performance and course engagement.
            </p>

            <div class="mt-5 space-y-4">

                <div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">Published Course Ratio</span>
                        <span class="text-white">
                            {{ $totalCourses > 0 ? round(($publishedCourses / $totalCourses) * 100, 2) : 0 }}%
                        </span>
                    </div>

                    <div class="mt-2 h-2 rounded-full bg-neutral-700">
                        <div class="h-2 rounded-full bg-purple-600"
                             style="width: {{ $totalCourses > 0 ? round(($publishedCourses / $totalCourses) * 100, 2) : 0 }}%">
                        </div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">Student Engagement</span>
                        <span class="text-white">
                            {{ $totalStudents }}
                        </span>
                    </div>

                    <div class="mt-2 h-2 rounded-full bg-neutral-700">
                        <div class="h-2 rounded-full bg-blue-600"
                             style="width: {{ min($totalStudents * 10, 100) }}%">
                        </div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">Average Quiz Performance</span>
                        <span class="text-white">
                            {{ $averageScore }}%
                        </span>
                    </div>

                    <div class="mt-2 h-2 rounded-full bg-neutral-700">
                        <div class="h-2 rounded-full bg-green-600"
                             style="width: {{ min($averageScore, 100) }}%">
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

</div>

</x-layouts::app>