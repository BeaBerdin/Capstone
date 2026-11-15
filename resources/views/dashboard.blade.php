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

{{--
    Admin (Department Head) dashboard in the soft-3D style (styles in
    partials/soft3d). All figures come from the block above.
--}}

@php
    $adminStats = [
        ['label' => 'Total Students', 'value' => $totalStudents, 'sub' => 'Registered learners', 'tone' => 'sad-violet', 'href' => route('student-progress.index'),
            'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75'],
        ['label' => 'Total Teachers', 'value' => $totalTeachers, 'sub' => 'Course instructors', 'tone' => 'sad-sky', 'href' => null,
            'icon' => 'M12 14l9-5-9-5-9 5 9 5zM5 12.5V17c0 1.66 3.13 3 7 3s7-1.34 7-3v-4.5'],
        ['label' => 'Total Courses', 'value' => $totalCourses, 'sub' => $publishedCourses.' published courses', 'tone' => 'sad-emerald', 'href' => route('courses.index'),
            'icon' => 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z'],
        ['label' => 'Courses for Approval', 'value' => $pendingCourses, 'sub' => 'Waiting for review', 'tone' => 'sad-amber', 'href' => route('courses.index'),
            'icon' => 'M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z'],
    ];

    $academicRows = [
        ['label' => 'Course Categories', 'value' => $totalCategories, 'tone' => 'sad-violet', 'href' => route('course-categories.index'),
            'icon' => 'M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'],
        ['label' => 'Certificates Issued', 'value' => $certificatesIssued, 'tone' => 'sad-amber', 'href' => route('certificates.index'),
            'icon' => 'M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10zM8.5 12.5 7 21l5-2 5 2-1.5-8.5'],
        ['label' => 'Active Learning Paths', 'value' => $activeLearningPaths, 'tone' => 'sad-emerald', 'href' => route('learning-paths.index'),
            'icon' => 'M6 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM18 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM8 19h7a3 3 0 0 0 0-6H9a3 3 0 0 1 0-6h7'],
    ];

    $approvalSteps = [
        ['step' => 'Step 1', 'title' => 'Teacher Creates', 'text' => 'Teacher builds course content and lessons.', 'tone' => 'sad-violet', 'icon' => 'M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z'],
        ['step' => 'Step 2', 'title' => 'Submit', 'text' => 'Course is sent for admin review.', 'tone' => 'sad-sky', 'icon' => 'M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z'],
        ['step' => 'Step 3', 'title' => 'Admin Reviews', 'text' => 'Course is approved or rejected.', 'tone' => 'sad-amber', 'icon' => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.35-4.35'],
        ['step' => 'Step 4', 'title' => 'Published', 'text' => 'Approved course appears in the marketplace.', 'tone' => 'sad-emerald', 'icon' => 'M5 12l4 4L19 6'],
    ];

    // The former "PATHWISE System Modules" links, now Quick Actions.
    $quickActions = [
        ['label' => 'Courses', 'href' => route('courses.index'), 'tone' => 'sad-violet', 'icon' => 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z'],
        ['label' => 'Categories', 'href' => route('course-categories.index'), 'tone' => 'sad-sky', 'icon' => 'M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'],
        ['label' => 'Assessments', 'href' => route('quizzes.index'), 'tone' => 'sad-emerald', 'icon' => 'M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11'],
        ['label' => 'AI Recommendations', 'href' => route('ai-recommendations.index'), 'tone' => 'sad-violet', 'icon' => 'm12 3 1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3zM19 16l.8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8L19 16z'],
        ['label' => 'Student Progress', 'href' => route('student-progress.index'), 'tone' => 'sad-rose', 'icon' => 'M4 19V9M9 19V5M14 19v-7M19 19V3'],
        ['label' => 'Enrollments', 'href' => route('enrollments.index'), 'tone' => 'sad-amber', 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M19 8v6M22 11h-6'],
    ];
@endphp

@include('partials.soft3d')

<div class="sad-page">

    <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">

        <div class="mx-auto grid max-w-375 grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">

            {{-- =====================================================
                MAIN COLUMN
            ====================================================== --}}

            <div class="min-w-0 space-y-6">

                {{-- WELCOME BANNER --}}
                <section class="sad-banner">

                    <div class="relative px-6 py-7 sm:px-8 md:pr-[38%]">

                        <p class="text-xs font-bold uppercase tracking-[.14em] text-violet-600">
                            PATHWISE ADMIN PORTAL
                        </p>

                        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-[26px]">
                            {{ $greeting }}, Department Head!&nbsp;<span aria-hidden="true">👋</span>
                        </h1>

                        <p class="mt-2 max-w-xl text-sm leading-6 text-slate-600">
                            Manage courses, learning content, certificates, student progress,
                            and academic activities across the PATHWISE platform.
                        </p>

                        <p class="mt-1 text-xs font-medium text-slate-500">{{ $currentDate }}</p>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="{{ route('courses.index') }}" class="sad-btn sad-btn-primary">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.35-4.35"></path></svg>
                                Review Courses
                            </a>

                            <a href="{{ route('student-progress.index') }}" class="sad-btn sad-btn-light">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V9M9 19V5M14 19v-7M19 19V3"></path></svg>
                                Student Progress
                            </a>
                        </div>

                    </div>

                    {{-- Soft-3D artwork: a course card with an approval check, drawn inline. --}}
                    <svg class="sad-banner-art" viewBox="0 0 360 220" aria-hidden="true">
                        <defs>
                            <linearGradient id="admCard" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#ffffff" />
                                <stop offset="1" stop-color="#ede9fe" />
                            </linearGradient>
                            <linearGradient id="admCover" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#a78bfa" />
                                <stop offset="1" stop-color="#6366f1" />
                            </linearGradient>
                            <linearGradient id="admCheck" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#6ee7b7" />
                                <stop offset="1" stop-color="#059669" />
                            </linearGradient>
                            <linearGradient id="admBadge" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#fde68a" />
                                <stop offset="1" stop-color="#f59e0b" />
                            </linearGradient>
                            <filter id="admSoft" x="-20%" y="-20%" width="140%" height="160%">
                                <feDropShadow dx="0" dy="8" stdDeviation="7" flood-color="#4c1d95" flood-opacity="0.18" />
                            </filter>
                        </defs>

                        <circle cx="210" cy="112" r="96" fill="#ffffff" opacity="0.35" />
                        <ellipse cx="200" cy="206" rx="130" ry="10" fill="#7c3aed" opacity="0.08" />

                        {{-- Back card --}}
                        <g filter="url(#admSoft)">
                            <rect x="96" y="58" width="120" height="132" rx="16" fill="url(#admCard)" transform="rotate(-8 156 124)" />
                        </g>

                        {{-- Front course card --}}
                        <g filter="url(#admSoft)">
                            <rect x="140" y="40" width="130" height="150" rx="18" fill="url(#admCard)" />
                            <rect x="152" y="52" width="106" height="58" rx="12" fill="url(#admCover)" />
                            <path d="M190 72 L205 66 L220 72 L205 78 Z" fill="#ffffff" opacity="0.9" />
                            <rect x="152" y="122" width="80" height="8" rx="4" fill="#c4b5fd" />
                            <rect x="152" y="138" width="106" height="6" rx="3" fill="#ede9fe" />
                            <rect x="152" y="150" width="90" height="6" rx="3" fill="#ede9fe" />
                            <rect x="152" y="166" width="54" height="12" rx="6" fill="#ddd6fe" />
                        </g>

                        {{-- Approval check --}}
                        <g filter="url(#admSoft)">
                            <circle cx="276" cy="58" r="22" fill="url(#admCheck)" />
                            <path d="M266 58 l7 7 13 -14" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                        </g>

                        {{-- Badge --}}
                        <g filter="url(#admSoft)">
                            <circle cx="300" cy="160" r="20" fill="url(#admBadge)" />
                            <path d="M290 176 L286 200 L300 192 L314 200 L310 176" fill="#f59e0b" />
                            <circle cx="300" cy="160" r="10" fill="#fffbeb" opacity="0.7" />
                        </g>
                    </svg>

                </section>


                {{-- PLATFORM OVERVIEW --}}
                <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 2xl:grid-cols-4">
                    @foreach($adminStats as $stat)
                        @php $tag = $stat['href'] ? 'a' : 'div'; @endphp
                        <{{ $tag }} @if($stat['href']) href="{{ $stat['href'] }}" @endif
                            class="sad-card {{ $stat['href'] ? 'sad-lift' : '' }} block p-5">
                            <span class="sad-chip {{ $stat['tone'] }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['icon'] }}"></path></svg>
                            </span>
                            <p class="mt-4 text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                            <p class="mt-1 text-3xl font-bold tracking-tight {{ $stat['label'] === 'Courses for Approval' && $pendingCourses > 0 ? 'text-amber-600' : 'text-slate-950' }}">{{ $stat['value'] }}</p>
                            <p class="mt-2 text-xs font-medium text-slate-400">{{ $stat['sub'] }}</p>
                        </{{ $tag }}>
                    @endforeach
                </section>


                {{-- COURSE APPROVAL: workflow + pending courses --}}
                <section class="sad-card overflow-hidden">

                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
                        <div>
                            <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                                <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                    <path d="M9 12l2 2 4-4"></path>
                                </svg>
                                Course Approval
                            </h2>
                            <p class="mt-1 text-xs text-slate-500">Review courses submitted by teachers.</p>
                        </div>
                        <a href="{{ route('courses.index') }}" class="sad-btn sad-btn-primary h-9! px-4! text-xs!">Review Courses</a>
                    </div>

                    <div class="grid grid-cols-1 gap-3 border-t border-slate-100 p-5 sm:grid-cols-2 sm:p-6 2xl:grid-cols-4">
                        @foreach($approvalSteps as $step)
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4">
                                <div class="flex items-center gap-3">
                                    <span class="sad-chip sad-chip-sm {{ $step['tone'] }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $step['icon'] }}"></path></svg>
                                    </span>
                                    <span class="text-[11px] font-bold uppercase tracking-wide text-violet-600">{{ $step['step'] }}</span>
                                </div>
                                <h3 class="mt-3 text-sm font-semibold text-slate-900">{{ $step['title'] }}</h3>
                                <p class="mt-1 text-xs leading-5 text-slate-500">{{ $step['text'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-5 pt-4 sm:px-6">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Courses Pending Approval</h3>
                            <p class="mt-0.5 text-xs text-slate-500">Latest course submissions</p>
                        </div>
                        @if($pendingCourses > 0)
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ $pendingCourses }} Pending</span>
                        @endif
                    </div>

                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full min-w-[560px] text-left text-sm">
                            <thead>
                                <tr class="border-y border-slate-100 bg-slate-50/70 text-xs text-slate-500">
                                    <th class="px-5 py-2.5 font-medium sm:px-6">Course</th>
                                    <th class="px-3 py-2.5 font-medium">Teacher</th>
                                    <th class="px-3 py-2.5 font-medium">Category</th>
                                    <th class="px-3 py-2.5 font-medium">Submitted</th>
                                    <th class="px-3 py-2.5 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($coursesPendingApproval as $course)
                                    <tr class="transition-colors hover:bg-violet-50/40">
                                        <td class="px-5 py-3 font-semibold text-slate-800 sm:px-6">
                                            <div class="flex items-center gap-3">
                                                <x-pw.course-cover :course="$course" variant="thumb" />
                                                <span class="min-w-0">{{ $course->title }}</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-slate-600">{{ $course->teacher->name ?? 'Teacher unavailable' }}</td>
                                        <td class="px-3 py-3 text-slate-600">{{ $course->category->name ?? 'Uncategorized' }}</td>
                                        <td class="whitespace-nowrap px-3 py-3 text-slate-500">{{ $course->created_at->format('M d, Y') }}</td>
                                        <td class="px-3 py-3"><span class="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700">Pending</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center">
                                            <p class="text-sm font-semibold text-slate-800">No courses pending approval.</p>
                                            <p class="mt-1 text-xs text-slate-500">New teacher submissions will appear here.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </section>

            </div>


            {{-- =====================================================
                RIGHT RAIL
            ====================================================== --}}

            <aside class="min-w-0 space-y-6">

                {{-- Academic information --}}
                <section class="sad-card p-5">
                    <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                        <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7" rx="2"></rect>
                            <rect x="14" y="3" width="7" height="7" rx="2"></rect>
                            <rect x="3" y="14" width="7" height="7" rx="2"></rect>
                            <rect x="14" y="14" width="7" height="7" rx="2"></rect>
                        </svg>
                        Academic Information
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">Current academic resources available on the platform.</p>

                    <ul class="mt-4 space-y-2.5">
                        @foreach($academicRows as $row)
                            <li>
                                <a href="{{ $row['href'] }}" class="sad-lift flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/60 p-2.5">
                                    <span class="sad-chip sad-chip-sm {{ $row['tone'] }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $row['icon'] }}"></path></svg>
                                    </span>
                                    <span class="flex-1 text-sm font-medium text-slate-600">{{ $row['label'] }}</span>
                                    <span class="text-base font-bold text-slate-950">{{ $row['value'] }}</span>
                                    <svg class="h-4 w-4 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"></path></svg>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>

                {{-- Quick actions (the former System Modules) --}}
                <section class="sad-card p-5">
                    <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                        <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"></path></svg>
                        Quick Actions
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">Core academic and learning management functions.</p>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        @foreach($quickActions as $action)
                            <a href="{{ $action['href'] }}" class="sad-lift flex flex-col items-center gap-2 rounded-2xl border border-slate-100 bg-slate-50/60 px-2 py-4 text-center">
                                <span class="sad-chip sad-chip-sm {{ $action['tone'] }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $action['icon'] }}"></path></svg>
                                </span>
                                <span class="text-xs font-semibold text-slate-700">{{ $action['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>

            </aside>

        </div>

    </main>

</div>

</x-layouts::app>
