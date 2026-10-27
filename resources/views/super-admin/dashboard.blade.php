<x-layouts::app :title="'Super Admin Dashboard'">

{{--
    Super Admin dashboard: light, soft-3D trial style, laid out after the
    reference image (main column with a right-hand rail).
    Everything shown comes from the controller (DashboardController@superAdmin)
    or the read-only counts below; nothing is sample data. The styles are
    scoped to this page with the sad- prefix, so no other page is affected.
--}}

@php
    $totalUsers = \App\Models\User::count();

    $totalStudents = \App\Models\User::whereHas('roles', function ($query) {
        $query->where('name', 'student');
    })->count();

    $totalTeachers = \App\Models\User::whereHas('roles', function ($query) {
        $query->where('name', 'teacher');
    })->count();

    $totalAdmins = \App\Models\User::whereHas('roles', function ($query) {
        $query->where('name', 'admin');
    })->count();

    $totalCourses = \App\Models\Course::count();

    $totalEnrollments = \App\Models\Enrollment::count();

    $totalAssignments = \App\Models\Assignment::count();

    $totalCertificates = \App\Models\Certificate::count();

    $pendingTransactions = \App\Models\Transaction::where('status', 'pending')->count();

    $usersThisMonth = \App\Models\User::where('created_at', '>=', now()->startOfMonth())->count();

    // Newest accounts, read-only, for the Recent Users table.
    $recentUsers = \App\Models\User::with('roles')->latest()->take(5)->get();

    // User growth: total accounts at the end of each of the last six months.
    $growth = collect(range(5, 0))->map(function ($monthsAgo) {
        $month = now()->subMonthsNoOverflow($monthsAgo);

        return [
            'label' => $month->format('M'),
            'total' => \App\Models\User::where('created_at', '<=', $month->copy()->endOfMonth())->count(),
        ];
    });

    // Chart geometry (viewBox 600 x 200). The y-axis tops out at the next
    // multiple of 4 at least 20% above the largest total, so the line never
    // touches the top and the four tick steps stay whole numbers.
    $growthMax = max(4, (int) ceil(($growth->max('total') * 1.2) / 4) * 4);
    $growthPoints = $growth->values()->map(fn ($point, $i) => [
        'x' => round($i * (600 / 5), 1),
        'y' => round(200 - ($point['total'] / $growthMax) * 200, 1),
    ] + $point);
    $growthLine = $growthPoints->map(fn ($p) => $p['x'].','.$p['y'])->implode(' ');
    $growthArea = '0,200 '.$growthLine.' 600,200';
    $growthTicks = collect(range(4, 0))->map(fn ($step) => (int) round($growthMax * $step / 4));

    // Greeting in the same local time the Admin dashboard uses.
    $hour = now('Asia/Manila')->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

    /*
    | System Overview pop-ups. Each row opens a pop-up listing only that group,
    | newest first, read-only and capped at $listLimit items. "Open page" goes
    | to User Management for people and to the matching Reports section for the
    | rest, the Super Admin's own pages for that data.
    */
    $listLimit = 50;

    $initialsOf = fn ($name) => collect(preg_split('/\s+/', trim($name ?? '')))
        ->filter()->take(2)
        ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
        ->implode('') ?: '?';

    $peopleItems = function ($role = null) use ($listLimit, $initialsOf) {
        $query = \App\Models\User::with('roles')->latest();

        if ($role) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        return $query->take($listLimit)->get()->map(function ($user) use ($initialsOf) {
            [$label, $class] = match (true) {
                ! $user->is_active => ['Deactivated', 'bg-red-50 text-red-600'],
                ! $user->hasVerifiedEmail() => ['Unverified', 'bg-amber-50 text-amber-700'],
                default => ['Active', 'bg-emerald-50 text-emerald-700'],
            };

            return [
                'initials' => $initialsOf($user->name),
                'title' => $user->name,
                'subtitle' => $user->email,
                'meta' => $user->roles->pluck('name')->map(fn ($r) => ucwords(str_replace('_', ' ', $r)))->implode(', ') ?: 'No role yet',
                'badge' => $label,
                'badgeClass' => $class,
            ];
        });
    };

    $courseItems = \App\Models\Course::with(['teacher', 'category'])->latest()->take($listLimit)->get()
        ->map(fn ($course) => [
            'initials' => $initialsOf($course->title),
            'title' => $course->title,
            'subtitle' => collect([$course->teacher?->name, $course->category?->name])->filter()->implode(' · ') ?: 'No teacher',
            'meta' => (float) $course->price > 0 ? '₱'.number_format((float) $course->price, 2) : 'Free',
            'badge' => ucfirst((string) $course->status),
            'badgeClass' => match ($course->status) {
                'published' => 'bg-emerald-50 text-emerald-700',
                'pending' => 'bg-amber-50 text-amber-700',
                'rejected' => 'bg-red-50 text-red-600',
                default => 'bg-slate-100 text-slate-600',
            },
        ]);

    $enrollmentItems = \App\Models\Enrollment::with(['student', 'course'])->latest()->take($listLimit)->get()
        ->map(fn ($enrollment) => [
            'initials' => $initialsOf($enrollment->student?->name),
            'title' => $enrollment->student?->name ?? 'Student',
            'subtitle' => $enrollment->course?->title ?? 'Course',
            'meta' => round((float) $enrollment->progress_percentage).'% progress',
            'badge' => ucfirst((string) $enrollment->status),
            'badgeClass' => match ($enrollment->status) {
                'active' => 'bg-emerald-50 text-emerald-700',
                'completed' => 'bg-violet-50 text-violet-700',
                default => 'bg-slate-100 text-slate-600',
            },
        ]);

    $assignmentItems = \App\Models\Assignment::with('course')->latest()->take($listLimit)->get()
        ->map(fn ($assignment) => [
            'initials' => $initialsOf($assignment->title),
            'title' => $assignment->title,
            'subtitle' => $assignment->course?->title ?? 'Course',
            'meta' => $assignment->due_date ? 'Due '.\Illuminate\Support\Carbon::parse($assignment->due_date)->format('M d, Y') : 'No due date',
            'badge' => $assignment->is_published ? 'Published' : 'Draft',
            'badgeClass' => $assignment->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600',
        ]);

    $certificateItems = \App\Models\Certificate::with(['student', 'course'])->latest()->take($listLimit)->get()
        ->map(fn ($certificate) => [
            'initials' => $initialsOf($certificate->student?->name),
            'title' => $certificate->student?->name ?? 'Student',
            'subtitle' => $certificate->course?->title ?? 'Course',
            'meta' => $certificate->certificate_number.($certificate->issued_date ? ' · '.\Illuminate\Support\Carbon::parse($certificate->issued_date)->format('M d, Y') : ''),
            'badge' => ucfirst((string) $certificate->status),
            'badgeClass' => $certificate->status === 'issued' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600',
        ]);

    // Icon shapes for the System Overview rows and their pop-ups.
    $overviewIcons = [
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>',
        'student' => '<circle cx="12" cy="8" r="4"></circle><path d="M5 21a7 7 0 0 1 14 0"></path>',
        'teacher' => '<path d="M12 14l9-5-9-5-9 5 9 5z"></path><path d="M5 12.5V17c0 1.66 3.13 3 7 3s7-1.34 7-3v-4.5"></path>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M9 12l2 2 4-4"></path>',
        'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>',
        'enroll' => '<path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6M8 13h8M8 17h6"></path>',
        'award' => '<circle cx="12" cy="8" r="5"></circle><path d="M8.5 12.5 7 21l5-2 5 2-1.5-8.5"></path>',
    ];

    $overviewRows = [
        ['key' => 'users', 'label' => 'Total Users', 'value' => $totalUsers, 'tone' => 'sad-violet', 'icon' => 'users',
            'items' => $peopleItems(), 'page' => route('users.index'), 'pageLabel' => 'Open User Management'],
        ['key' => 'students', 'label' => 'Students', 'value' => $totalStudents, 'tone' => 'sad-sky', 'icon' => 'student',
            'items' => $peopleItems('student'), 'page' => route('users.index'), 'pageLabel' => 'Open User Management'],
        ['key' => 'teachers', 'label' => 'Teachers', 'value' => $totalTeachers, 'tone' => 'sad-emerald', 'icon' => 'teacher',
            'items' => $peopleItems('teacher'), 'page' => route('users.index'), 'pageLabel' => 'Open User Management'],
        ['key' => 'admins', 'label' => 'Administrators', 'value' => $totalAdmins, 'tone' => 'sad-rose', 'icon' => 'shield',
            'items' => $peopleItems('admin'), 'page' => route('users.index'), 'pageLabel' => 'Open User Management'],
        ['key' => 'courses', 'label' => 'Total Courses', 'value' => $totalCourses, 'tone' => 'sad-sky', 'icon' => 'book',
            'items' => $courseItems, 'page' => route('reports.index').'#platform-overview', 'pageLabel' => 'Open Reports'],
        ['key' => 'enrollments', 'label' => 'Total Enrollments', 'value' => $totalEnrollments, 'tone' => 'sad-violet', 'icon' => 'enroll',
            'items' => $enrollmentItems, 'page' => route('reports.index').'#enrollments', 'pageLabel' => 'Open Reports'],
        ['key' => 'assignments', 'label' => 'Total Assignments', 'value' => $totalAssignments, 'tone' => 'sad-emerald', 'icon' => 'file',
            'items' => $assignmentItems, 'page' => route('reports.index').'#assignments', 'pageLabel' => 'Open Reports'],
        ['key' => 'certificates', 'label' => 'Total Certificates', 'value' => $totalCertificates, 'tone' => 'sad-amber', 'icon' => 'award',
            'items' => $certificateItems, 'page' => route('reports.index').'#certificates', 'pageLabel' => 'Open Reports'],
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

                    <div class="relative px-6 py-7 sm:px-8 md:pr-[34%]">

                        <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-[26px]">
                            {{ $greeting }}, {{ auth()->user()->name }}!&nbsp;<span aria-hidden="true">👋</span>
                        </h1>

                        <p class="mt-2 max-w-xl text-sm leading-6 text-slate-600">
                            Here's what's happening with PathWise today.
                        </p>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="{{ route('users.index') }}" class="sad-btn sad-btn-primary">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                Manage Users
                            </a>

                            <a href="{{ route('reports.index') }}" class="sad-btn sad-btn-light">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 19V5M4 19h16M8 16v-4M12 16V8M16 16v-7M20 16v-3"></path>
                                </svg>
                                View Reports
                            </a>

                            <a href="{{ route('super_admin.transactions.index') }}" class="sad-btn sad-btn-light">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                    <path d="M3 10h18"></path>
                                </svg>
                                Verify Payments
                            </a>
                        </div>

                    </div>

                    {{-- Soft-3D study-desk artwork, drawn inline (no image files). --}}
                    <svg class="sad-banner-art" viewBox="0 0 360 220" aria-hidden="true">
                        <defs>
                            <linearGradient id="sadScreen" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#e9e5f5" />
                                <stop offset="1" stop-color="#c9c3dc" />
                            </linearGradient>
                            <linearGradient id="sadBase" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#d9d4e8" />
                                <stop offset="1" stop-color="#a9a2c2" />
                            </linearGradient>
                            <linearGradient id="sadBookA" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#a78bfa" />
                                <stop offset="1" stop-color="#7c3aed" />
                            </linearGradient>
                            <linearGradient id="sadBookB" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#fcd34d" />
                                <stop offset="1" stop-color="#f59e0b" />
                            </linearGradient>
                            <linearGradient id="sadBookC" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#c4b5fd" />
                                <stop offset="1" stop-color="#8b5cf6" />
                            </linearGradient>
                            <linearGradient id="sadPot" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#ffffff" />
                                <stop offset="1" stop-color="#ddd6fe" />
                            </linearGradient>
                            <linearGradient id="sadLeaf" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#6ee7b7" />
                                <stop offset="1" stop-color="#059669" />
                            </linearGradient>
                            <filter id="sadSoft" x="-20%" y="-20%" width="140%" height="160%">
                                <feDropShadow dx="0" dy="8" stdDeviation="7" flood-color="#4c1d95" flood-opacity="0.18" />
                            </filter>
                        </defs>

                        {{-- Soft background blobs --}}
                        <circle cx="250" cy="120" r="96" fill="#ffffff" opacity="0.35" />
                        <circle cx="120" cy="170" r="60" fill="#ffffff" opacity="0.3" />

                        {{-- Shelf with books --}}
                        <g filter="url(#sadSoft)">
                            <rect x="250" y="34" width="96" height="8" rx="4" fill="#ffffff" />
                            <rect x="262" y="8" width="12" height="26" rx="3" fill="url(#sadBookC)" />
                            <rect x="277" y="4" width="12" height="30" rx="3" fill="url(#sadBookA)" />
                            <rect x="292" y="12" width="12" height="22" rx="3" fill="#ddd6fe" />
                        </g>

                        {{-- Desk --}}
                        <rect x="40" y="196" width="320" height="24" rx="10" fill="#ffffff" opacity="0.75" />

                        {{-- Laptop --}}
                        <g filter="url(#sadSoft)">
                            <rect x="150" y="100" width="130" height="86" rx="10" fill="url(#sadScreen)" />
                            <rect x="158" y="108" width="114" height="70" rx="6" fill="#f8f7fd" />
                            {{-- Graduation cap on the screen --}}
                            <path d="M195 136 L215 128 L235 136 L215 144 Z" fill="#7c3aed" />
                            <path d="M204 139 L204 146 Q215 152 226 146 L226 139 L215 143 Z" fill="#6d28d9" />
                            <path d="M132 186 L298 186 L288 198 L142 198 Z" fill="url(#sadBase)" />
                        </g>

                        {{-- Books on the desk --}}
                        <g filter="url(#sadSoft)">
                            <rect x="66" y="178" width="76" height="18" rx="5" fill="url(#sadBookA)" />
                            <rect x="72" y="182" width="64" height="4" rx="2" fill="#ffffff" opacity="0.55" />
                            <rect x="74" y="161" width="64" height="18" rx="5" fill="url(#sadBookB)" />
                            <rect x="80" y="165" width="52" height="4" rx="2" fill="#ffffff" opacity="0.55" />
                        </g>

                        {{-- Plant --}}
                        <g filter="url(#sadSoft)">
                            <path d="M312 150 C300 130 302 112 314 104 C318 120 318 136 312 150 Z" fill="url(#sadLeaf)" />
                            <path d="M318 150 C324 126 338 118 350 120 C344 134 334 146 318 150 Z" fill="url(#sadLeaf)" />
                            <path d="M310 152 C292 142 286 128 290 118 C302 126 308 138 310 152 Z" fill="url(#sadLeaf)" />
                            <path d="M300 150 L332 150 L326 196 L306 196 Z" fill="url(#sadPot)" />
                        </g>
                    </svg>

                </section>


                {{-- SUMMARY (Manuscript: Total Users, Pending Role
                     Assignments, Payments to Verify, System Status) --}}
                <section data-dashboard-section="summary">

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 2xl:grid-cols-4">

                        <a href="{{ route('users.index') }}" class="sad-card sad-lift block p-5">
                            <span class="sad-chip sad-violet">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                            </span>
                            <p class="mt-4 text-sm font-medium text-slate-500">Total Users</p>
                            <p class="mt-1 text-3xl font-bold tracking-tight text-slate-950">{{ number_format($totalUsers) }}</p>
                            <p class="mt-2 flex items-center gap-1 text-xs font-medium {{ $usersThisMonth > 0 ? 'text-emerald-600' : 'text-slate-400' }}">
                                @if($usersThisMonth > 0)
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 19V5M5 12l7-7 7 7"></path></svg>
                                @endif
                                {{ $usersThisMonth }} joined this month
                            </p>
                        </a>

                        <a href="{{ route('users.index') }}" class="sad-card sad-lift block p-5">
                            <span class="sad-chip sad-sky">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M3 21v-2a4 4 0 0 1 4-4h4"></path>
                                    <path d="M17 14v4"></path>
                                    <path d="M17 21h.01"></path>
                                </svg>
                            </span>
                            <p class="mt-4 text-sm font-medium text-slate-500">Pending Role Assignments</p>
                            <p class="mt-1 text-3xl font-bold tracking-tight {{ ($pendingRoleAssignments ?? 0) > 0 ? 'text-amber-600' : 'text-slate-950' }}">
                                {{ number_format($pendingRoleAssignments ?? 0) }}
                            </p>
                            <p class="mt-2 text-xs font-medium text-slate-400">Accounts without a role</p>
                        </a>

                        <a href="{{ route('super_admin.transactions.index') }}" class="sad-card sad-lift block p-5">
                            <span class="sad-chip sad-amber">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                    <path d="M3 10h18"></path>
                                    <path d="M7 15h3"></path>
                                </svg>
                            </span>
                            <p class="mt-4 text-sm font-medium text-slate-500">Payments to Verify</p>
                            <p class="mt-1 text-3xl font-bold tracking-tight {{ ($paymentsToVerify ?? 0) > 0 ? 'text-amber-600' : 'text-slate-950' }}">
                                {{ number_format($paymentsToVerify ?? 0) }}
                            </p>
                            <p class="mt-2 text-xs font-medium text-slate-400">Pending transactions</p>
                        </a>

                        <div class="sad-card p-5" data-system-status>
                            <span class="sad-chip {{ empty($systemIssues ?? []) ? 'sad-emerald' : 'sad-amber' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                    <path d="M9 12l2 2 4-4"></path>
                                </svg>
                            </span>
                            <p class="mt-4 text-sm font-medium text-slate-500">System Status</p>

                            @if(empty($systemIssues ?? []))
                                <p class="mt-1 text-2xl font-bold tracking-tight text-emerald-600">Operational</p>
                                <p class="mt-2 text-xs font-medium text-slate-400">All services configured</p>
                            @else
                                <p class="mt-1 text-2xl font-bold tracking-tight text-amber-600">Needs attention</p>
                                <p class="mt-2 text-xs font-medium text-slate-500">
                                    Not configured: {{ implode(', ', $systemIssues) }}
                                </p>
                            @endif
                        </div>

                    </div>

                </section>


                {{-- USER GROWTH + RECENT USERS | RECENT ACTIVITY --}}
                <div class="grid grid-cols-1 gap-6 2xl:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]">

                    <div class="min-w-0 space-y-6">

                        {{-- User growth (accounts at the end of each month) --}}
                        <section class="sad-card p-5 sm:p-6">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                                    <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                    </svg>
                                    User Growth
                                </h2>
                                <span class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-500">Last 6 months</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Total accounts at the end of each month.</p>

                            <div class="mt-5 grid grid-cols-[32px_minmax(0,1fr)] gap-2">
                                <div class="flex h-48 flex-col justify-between pb-0.5 text-right text-[11px] text-slate-400">
                                    @foreach($growthTicks as $tick)
                                        <span>{{ $tick }}</span>
                                    @endforeach
                                </div>

                                <div>
                                    <div class="relative h-48">
                                        {{-- Grid lines --}}
                                        <div class="absolute inset-0 flex flex-col justify-between">
                                            @foreach($growthTicks as $tick)
                                                <div class="border-t border-dashed border-slate-200"></div>
                                            @endforeach
                                        </div>

                                        <svg class="absolute inset-0 h-full w-full overflow-visible" viewBox="0 0 600 200" preserveAspectRatio="none" role="img" aria-label="User growth over the last six months">
                                            <defs>
                                                <linearGradient id="sadGrowthFill" x1="0" y1="0" x2="0" y2="1">
                                                    <stop offset="0" stop-color="#7c3aed" stop-opacity="0.22" />
                                                    <stop offset="1" stop-color="#7c3aed" stop-opacity="0" />
                                                </linearGradient>
                                            </defs>
                                            <polygon points="{{ $growthArea }}" fill="url(#sadGrowthFill)" />
                                            <polyline points="{{ $growthLine }}" fill="none" stroke="#7c3aed" stroke-width="2.5"
                                                      stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                                            {{-- Round dots that stay round although the SVG stretches. --}}
                                            @foreach($growthPoints as $p)
                                                <line x1="{{ $p['x'] }}" y1="{{ $p['y'] }}" x2="{{ $p['x'] }}" y2="{{ $p['y'] }}"
                                                      stroke="#7c3aed" stroke-width="9" stroke-linecap="round" vector-effect="non-scaling-stroke">
                                                    <title>{{ $p['label'] }}: {{ $p['total'] }} {{ \Illuminate\Support\Str::plural('user', $p['total']) }}</title>
                                                </line>
                                            @endforeach
                                        </svg>
                                    </div>

                                    <div class="mt-2 flex justify-between text-[11px] text-slate-400">
                                        @foreach($growthPoints as $p)
                                            <span>{{ $p['label'] }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </section>

                        {{-- Recent users (newest five accounts) --}}
                        <section class="sad-card overflow-hidden">
                            <div class="flex items-center justify-between gap-3 px-5 py-4 sm:px-6">
                                <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                                    <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="8" r="4"></circle>
                                        <path d="M5 21a7 7 0 0 1 14 0"></path>
                                    </svg>
                                    Recent Users
                                </h2>
                                <a href="{{ route('users.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-violet-600 hover:text-violet-700">
                                    View All
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                                </a>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[400px] text-left text-sm">
                                    <thead>
                                        <tr class="border-y border-slate-100 bg-slate-50/70 text-xs text-slate-500">
                                            <th class="px-5 py-2.5 font-medium sm:px-6">Name</th>
                                            <th class="px-2 py-2.5 font-medium">Role</th>
                                            <th class="px-2 py-2.5 font-medium">Status</th>
                                            <th class="px-2 py-2.5 font-medium">Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse($recentUsers as $recentUser)
                                            @php
                                                $initials = collect(preg_split('/\s+/', trim($recentUser->name ?? '')))
                                                    ->filter()->take(2)
                                                    ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
                                                    ->implode('') ?: 'U';

                                                $roleNames = $recentUser->roles->pluck('name')
                                                    ->map(fn ($role) => ucwords(str_replace('_', ' ', $role)))
                                                    ->implode(', ');

                                                [$statusLabel, $statusClass] = match (true) {
                                                    ! $recentUser->is_active => ['Deactivated', 'bg-red-50 text-red-600'],
                                                    ! $recentUser->hasVerifiedEmail() => ['Unverified', 'bg-amber-50 text-amber-700'],
                                                    default => ['Active', 'bg-emerald-50 text-emerald-700'],
                                                };
                                            @endphp

                                            <tr class="transition-colors hover:bg-violet-50/40">
                                                <td class="max-w-[260px] px-5 py-3 sm:px-6">
                                                    <div class="flex items-center gap-3">
                                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-violet-100 to-violet-200 text-[11px] font-bold text-violet-700">
                                                            {{ $initials }}
                                                        </span>
                                                        <span class="min-w-0">
                                                            <span class="block truncate font-medium text-slate-800">{{ $recentUser->name }}</span>
                                                            <span class="block truncate text-xs text-slate-500">{{ $recentUser->email }}</span>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="whitespace-nowrap px-2 py-3 text-slate-600">{{ $roleNames ?: 'No role yet' }}</td>
                                                <td class="px-2 py-3">
                                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                                                </td>
                                                <td class="whitespace-nowrap py-3 pl-2 pr-5 text-slate-500 sm:pr-6">{{ $recentUser->created_at?->format('M d, Y') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500">No users yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>

                    </div>

                    {{-- Recent activity --}}
                    <section class="sad-card self-start overflow-hidden" data-dashboard-section="recent-activity">

                        <div class="px-5 py-4 sm:px-6">
                            <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                                <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M12 7v5l3 2"></path>
                                </svg>
                                Recent Activity
                            </h2>
                            <p class="mt-1 text-xs text-slate-500">
                                Latest registrations, role assignments, enrollments and payments.
                            </p>
                        </div>

                        <ul class="divide-y divide-slate-100 border-t border-slate-100">
                            @forelse(($recentActivity ?? collect()) as $activity)
                                @php
                                    $tagClass = match ($activity['status']) {
                                        'Verified', 'Active', 'Completed' => 'bg-emerald-50 text-emerald-700',
                                        'Pending' => 'bg-amber-50 text-amber-700',
                                        default => 'bg-red-50 text-red-600',
                                    };

                                    $chipTone = match ($activity['type']) {
                                        'Payment' => 'sad-amber',
                                        'Enrollment' => 'sad-sky',
                                        'Role assignment' => 'sad-emerald',
                                        default => 'sad-violet',
                                    };
                                @endphp

                                <li class="flex gap-3 px-5 py-3.5 sm:px-6">
                                    <span class="sad-chip sad-chip-sm {{ $chipTone }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            @if($activity['type'] === 'Payment')
                                                <rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M3 10h18"></path>
                                            @elseif($activity['type'] === 'Enrollment')
                                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                            @elseif($activity['type'] === 'Role assignment')
                                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M9 12l2 2 4-4"></path>
                                            @else
                                                <circle cx="12" cy="8" r="4"></circle><path d="M5 21a7 7 0 0 1 14 0"></path>
                                            @endif
                                        </svg>
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <p class="text-[11px] font-bold uppercase tracking-wide text-violet-600">
                                            {{ $activity['type'] }}
                                        </p>
                                        <p class="mt-0.5 truncate text-sm font-semibold text-slate-800">
                                            {{ $activity['title'] }}
                                        </p>
                                        <p class="mt-0.5 truncate text-xs text-slate-500">
                                            {{ $activity['detail'] }}
                                        </p>
                                        <div class="mt-1.5 flex items-center gap-2">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold {{ $tagClass }}">
                                                {{ $activity['status'] }}
                                            </span>
                                            <span class="text-[11px] text-slate-400">
                                                {{ $activity['at']->diffForHumans() }}
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="px-5 py-10 text-center text-sm text-slate-500 sm:px-6">
                                    No activity yet.
                                </li>
                            @endforelse
                        </ul>

                    </section>

                </div>

            </div>


            {{-- =====================================================
                RIGHT RAIL
            ====================================================== --}}

            <aside class="min-w-0 space-y-6">

                {{-- System overview (all real counts) --}}
                <section class="sad-card p-5">
                    <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                        <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7" rx="2"></rect>
                            <rect x="14" y="3" width="7" height="7" rx="2"></rect>
                            <rect x="3" y="14" width="7" height="7" rx="2"></rect>
                            <rect x="14" y="14" width="7" height="7" rx="2"></rect>
                        </svg>
                        System Overview
                    </h2>

                    <ul class="mt-4 space-y-2.5">
                        @foreach($overviewRows as $row)
                            <li>
                                {{-- Opens this group's pop-up (sad-list-{key}) below. --}}
                                <button type="button"
                                        onclick="document.getElementById('sad-list-{{ $row['key'] }}').showModal()"
                                        aria-haspopup="dialog"
                                        class="sad-lift flex w-full items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/60 p-2.5 text-left">
                                    <span class="sad-chip sad-chip-sm {{ $row['tone'] }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $overviewIcons[$row['icon']] !!}</svg>
                                    </span>
                                    <span class="flex-1 text-sm font-medium text-slate-600">{{ $row['label'] }}</span>
                                    <span class="text-base font-bold text-slate-950">{{ number_format($row['value']) }}</span>
                                    <svg class="h-4 w-4 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"></path></svg>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </section>

                {{-- System health (the controller's configuration checks) --}}
                <section class="sad-card p-5">
                    <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                        <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                        </svg>
                        System Health
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">Configuration checks only; no keys are shown.</p>

                    <ul class="mt-4 space-y-2">
                        @foreach(($systemChecks ?? []) as $service => $ok)
                            <li class="flex items-center justify-between gap-3 text-sm">
                                <span class="text-slate-600">{{ $service }}</span>
                                @if($ok)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Configured
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                        Not configured
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>

                {{-- Quick actions (existing Super Admin pages only) --}}
                <section class="sad-card p-5">
                    <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                        <svg class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"></path></svg>
                        Quick Actions
                    </h2>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        @php
                            $quickActions = [
                                ['label' => 'Manage Users', 'href' => route('users.index'), 'tone' => 'sad-violet', 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M19 8v6M22 11h-6'],
                                ['label' => 'Verify Payments', 'href' => route('super_admin.transactions.index'), 'tone' => 'sad-amber', 'icon' => 'M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zM3 10h18M7 15h3', 'badge' => $pendingTransactions > 0 ? $pendingTransactions.' pending' : null],
                                ['label' => 'Create Department', 'href' => route('departments.create'), 'tone' => 'sad-sky', 'icon' => 'M3 21h18M5 21V8l7-5 7 5v13M12 10v6M9 13h6'],
                                ['label' => 'Assign Department', 'href' => route('departments.assign'), 'tone' => 'sad-emerald', 'icon' => 'M17 20h5v-2a3 3 0 0 0-5.36-1.86M9 20H2v-2a3 3 0 0 1 5.36-1.86M12 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6M7.36 16.14a5 5 0 0 1 9.28 0'],
                                ['label' => 'View Reports', 'href' => route('reports.index'), 'tone' => 'sad-violet', 'icon' => 'M4 19V5M4 19h16M8 16v-4M12 16V8M16 16v-7M20 16v-3'],
                                ['label' => 'Export PDF', 'href' => route('reports.export.pdf'), 'tone' => 'sad-rose', 'icon' => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M12 12v6M9 15l3 3 3-3'],
                            ];
                        @endphp

                        @foreach($quickActions as $action)
                            <a href="{{ $action['href'] }}" class="sad-lift flex flex-col items-center gap-2 rounded-2xl border border-slate-100 bg-slate-50/60 px-2 py-4 text-center">
                                <span class="sad-chip sad-chip-sm {{ $action['tone'] }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="{{ $action['icon'] }}"></path></svg>
                                </span>
                                <span class="text-xs font-semibold text-slate-700">{{ $action['label'] }}</span>
                                @if(! empty($action['badge']))
                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">{{ $action['badge'] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </section>

            </aside>

        </div>

    </main>


    {{-- =====================================================
        SYSTEM OVERVIEW POP-UPS
        One native <dialog> per row: Esc, the close button or a
        click on the dimmed background closes it.
    ====================================================== --}}

    @foreach($overviewRows as $row)
        <dialog id="sad-list-{{ $row['key'] }}" class="sad-dialog"
                aria-labelledby="sad-list-{{ $row['key'] }}-title"
                onclick="if (event.target === this) this.close()">

            <div class="flex max-h-[85vh] flex-col">

                <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <span class="sad-chip sad-chip-sm {{ $row['tone'] }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $overviewIcons[$row['icon']] !!}</svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 id="sad-list-{{ $row['key'] }}-title" class="text-base font-bold text-slate-900">{{ $row['label'] }}</h2>
                        <p class="text-xs text-slate-500">
                            @if($row['value'] > $row['items']->count())
                                Showing the newest {{ $row['items']->count() }} of {{ number_format($row['value']) }}
                            @else
                                {{ number_format($row['value']) }} {{ $row['value'] === 1 ? 'item' : 'items' }}
                            @endif
                        </p>
                    </div>
                    <button type="button" onclick="this.closest('dialog').close()"
                            class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"></path></svg>
                        <span class="sr-only">Close</span>
                    </button>
                </div>

                <ul class="flex-1 divide-y divide-slate-100 overflow-y-auto">
                    @forelse($row['items'] as $item)
                        <li class="flex items-center gap-3 px-5 py-3 sm:px-6">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-violet-100 to-violet-200 text-[11px] font-bold text-violet-700">
                                {{ $item['initials'] }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ $item['title'] }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $item['subtitle'] }}</p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $item['badgeClass'] }}">{{ $item['badge'] }}</span>
                                <span class="text-[11px] text-slate-400">{{ $item['meta'] }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="px-6 py-12 text-center text-sm text-slate-500">Nothing here yet.</li>
                    @endforelse
                </ul>

                <div class="flex justify-end border-t border-slate-100 px-5 py-3 sm:px-6">
                    <a href="{{ $row['page'] }}" class="inline-flex items-center gap-1 text-sm font-semibold text-violet-600 hover:text-violet-700">
                        {{ $row['pageLabel'] }}
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                    </a>
                </div>

            </div>

        </dialog>
    @endforeach

</div>

</x-layouts::app>
