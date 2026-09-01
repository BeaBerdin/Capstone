<x-layouts::app :title="'Super Admin Dashboard'">

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

    $pendingTransactions = \App\Models\Transaction::where('status', 'pending')->count();
@endphp

<style>
    .sa-card {
        background: #ffffff;
        border: 1px solid #e8eaf0;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }

    .sa-hover {
        transition: all 160ms ease;
    }

    .sa-hover:hover {
        transform: translateY(-2px);
        border-color: #c7d2fe;
        box-shadow: 0 12px 30px rgba(79, 70, 229, 0.08);
    }
</style>

<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-[1500px]">

            {{-- =====================================================
                HEADER
            ====================================================== --}}

            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">

                <div>

                    <p class="text-xs font-bold uppercase tracking-[.12em] text-indigo-600">
                        PathWise Administration
                    </p>

                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                        Welcome, {{ auth()->user()->name }} 👋
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                        Monitor your platform, manage users, verify transactions,
                        and keep track of system-wide activities.
                    </p>

                </div>

            </div>


            {{-- =====================================================
                SYSTEM OVERVIEW
            ====================================================== --}}

            <section class="mt-7">

                <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">

                    {{-- TOTAL USERS --}}
                    <div class="sa-card p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div>
                                <p class="text-xs font-semibold text-slate-500">
                                    Total Users
                                </p>

                                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                                    {{ $totalUsers }}
                                </p>

                                <p class="mt-2 text-xs text-slate-400">
                                    All registered users
                                </p>
                            </div>

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">

                                <svg class="h-5 w-5"
                                     viewBox="0 0 24 24"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="2">

                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>

                                </svg>

                            </div>

                        </div>

                    </div>


                    {{-- STUDENTS --}}
                    <div class="sa-card p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div>
                                <p class="text-xs font-semibold text-slate-500">
                                    Students
                                </p>

                                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                                    {{ $totalStudents }}
                                </p>

                                <p class="mt-2 text-xs text-slate-400">
                                    Registered learners
                                </p>
                            </div>

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">

                                <svg class="h-5 w-5"
                                     viewBox="0 0 24 24"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="2">

                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>

                                </svg>

                            </div>

                        </div>

                    </div>


                    {{-- TEACHERS --}}
                    <div class="sa-card p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div>
                                <p class="text-xs font-semibold text-slate-500">
                                    Teachers
                                </p>

                                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                                    {{ $totalTeachers }}
                                </p>

                                <p class="mt-2 text-xs text-slate-400">
                                    Course instructors
                                </p>
                            </div>

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">

                                <svg class="h-5 w-5"
                                     viewBox="0 0 24 24"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="2">

                                    <path d="M12 14l9-5-9-5-9 5 9 5z"></path>
                                    <path d="M12 14l6.16-3.42"></path>
                                    <path d="M5 12.5V17c0 1.66 3.13 3 7 3s7-1.34 7-3v-4.5"></path>

                                </svg>

                            </div>

                        </div>

                    </div>


                    {{-- ADMINISTRATORS --}}
                    <div class="sa-card p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div>
                                <p class="text-xs font-semibold text-slate-500">
                                    Administrators
                                </p>

                                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                                    {{ $totalAdmins }}
                                </p>

                                <p class="mt-2 text-xs text-slate-400">
                                    Department administrators
                                </p>
                            </div>

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-orange-500">

                                <svg class="h-5 w-5"
                                     viewBox="0 0 24 24"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="2">

                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                    <path d="M9 12l2 2 4-4"></path>

                                </svg>

                            </div>

                        </div>

                    </div>

                </div>

            </section>


            {{-- =====================================================
                SECONDARY STATISTICS
            ====================================================== --}}

            <section class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">

                {{-- COURSES --}}
                <div class="sa-card p-5">

                    <div class="flex items-center gap-4">

                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600">

                            <svg class="h-6 w-6"
                                 viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">

                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>

                            </svg>

                        </div>

                        <div>

                            <p class="text-xs font-semibold text-slate-500">
                                Total Courses
                            </p>

                            <p class="mt-1 text-2xl font-bold text-slate-950">
                                {{ $totalCourses }}
                            </p>

                        </div>

                    </div>

                </div>


                {{-- ENROLLMENTS --}}
                <div class="sa-card p-5">

                    <div class="flex items-center gap-4">

                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">

                            <svg class="h-6 w-6"
                                 viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">

                                <circle cx="12" cy="8" r="4"></circle>
                                <path d="M5 21a7 7 0 0 1 14 0"></path>

                            </svg>

                        </div>

                        <div>

                            <p class="text-xs font-semibold text-slate-500">
                                Total Enrollments
                            </p>

                            <p class="mt-1 text-2xl font-bold text-slate-950">
                                {{ $totalEnrollments }}
                            </p>

                        </div>

                    </div>

                </div>


                {{-- PENDING TRANSACTIONS --}}
                <div class="sa-card border-yellow-200 p-5">

                    <div class="flex items-center gap-4">

                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-yellow-50 text-yellow-600">

                            <svg class="h-6 w-6"
                                 viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">

                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M12 7v5l3 2"></path>

                            </svg>

                        </div>

                        <div>

                            <p class="text-xs font-semibold text-slate-500">
                                Pending Transactions
                            </p>

                            <p class="mt-1 text-2xl font-bold text-yellow-600">
                                {{ $pendingTransactions }}
                            </p>

                        </div>

                    </div>

                </div>

            </section>


            {{-- =====================================================
                ADMINISTRATION CENTER
            ====================================================== --}}

            <section class="mt-6 sa-card overflow-hidden">

                <div class="border-b border-slate-100 p-5 sm:p-6">

                    <div>

                        <h2 class="text-lg font-bold text-slate-900">
                            Administration Center
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Access the main system administration functions.
                        </p>

                    </div>

                </div>


                <div class="grid grid-cols-1 gap-4 p-5 sm:p-6 lg:grid-cols-3">


                    {{-- USER MANAGEMENT --}}
                    <a href="{{ route('users.index') }}"
                       class="sa-hover rounded-2xl border border-slate-200 bg-white p-5">

                        <div class="flex items-start justify-between">

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">

                                <svg class="h-5 w-5"
                                     viewBox="0 0 24 24"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="2">

                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>

                                </svg>

                            </div>

                            <span class="text-xl text-slate-300">
                                →
                            </span>

                        </div>

                        <h3 class="mt-5 text-sm font-bold text-slate-900">
                            User Management
                        </h3>

                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Manage platform accounts and assigned system roles.
                        </p>

                        <div class="mt-4 text-xs font-semibold text-indigo-600">
                            Manage Users
                        </div>

                    </a>


                    {{-- TRANSACTIONS --}}
                    <a href="{{ route('super_admin.transactions.index') }}"
                       class="sa-hover rounded-2xl border border-slate-200 bg-white p-5">

                        <div class="flex items-start justify-between">

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-yellow-50 text-yellow-600">

                                <svg class="h-5 w-5"
                                     viewBox="0 0 24 24"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="2">

                                    <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                    <path d="M3 10h18"></path>
                                    <path d="M7 15h3"></path>

                                </svg>

                            </div>

                            <span class="text-xl text-slate-300">
                                →
                            </span>

                        </div>

                        <h3 class="mt-5 text-sm font-bold text-slate-900">
                            Payment Verification
                        </h3>

                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Review and verify submitted payment transactions.
                        </p>

                        <div class="mt-4 flex items-center gap-2 text-xs font-semibold text-yellow-600">

                            <span>
                                Verify Transactions
                            </span>

                            @if($pendingTransactions > 0)

                                <span class="rounded-full bg-yellow-50 px-2 py-0.5 text-[10px]">
                                    {{ $pendingTransactions }} pending
                                </span>

                            @endif

                        </div>

                    </a>


                    {{-- REPORTS --}}
                    <a href="{{ route('reports.index') }}"
                       class="sa-hover rounded-2xl border border-slate-200 bg-white p-5">

                        <div class="flex items-start justify-between">

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">

                                <svg class="h-5 w-5"
                                     viewBox="0 0 24 24"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="2">

                                    <path d="M4 19V5"></path>
                                    <path d="M4 19h16"></path>
                                    <path d="M8 16v-4"></path>
                                    <path d="M12 16V8"></path>
                                    <path d="M16 16v-7"></path>
                                    <path d="M20 16v-3"></path>

                                </svg>

                            </div>

                            <span class="text-xl text-slate-300">
                                →
                            </span>

                        </div>

                        <h3 class="mt-5 text-sm font-bold text-slate-900">
                            System Reports
                        </h3>

                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            View system-wide reports and platform information.
                        </p>

                        <div class="mt-4 text-xs font-semibold text-emerald-600">
                            View Reports
                        </div>

                    </a>

                </div>

            </section>


            {{-- =====================================================
                SYSTEM RESPONSIBILITIES
            ====================================================== --}}

            <section class="mt-6 sa-card p-5 sm:p-6">

                <div>

                    <h2 class="text-lg font-bold text-slate-900">
                        System Administration
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        Key administrative responsibilities for maintaining the PathWise platform.
                    </p>

                </div>


                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">


                    <div class="rounded-2xl bg-slate-50 p-5">

                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">

                            <svg class="h-5 w-5"
                                 viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">

                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>

                            </svg>

                        </div>

                        <h3 class="mt-4 text-sm font-bold text-slate-900">
                            User Accounts & Roles
                        </h3>

                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Manage platform users and their assigned system roles.
                        </p>

                    </div>


                    <div class="rounded-2xl bg-slate-50 p-5">

                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-yellow-50 text-yellow-600">

                            <svg class="h-5 w-5"
                                 viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">

                                <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                <path d="M3 10h18"></path>

                            </svg>

                        </div>

                        <h3 class="mt-4 text-sm font-bold text-slate-900">
                            Payment Verification
                        </h3>

                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Verify student payment transactions before course access.
                        </p>

                    </div>


                    <div class="rounded-2xl bg-slate-50 p-5">

                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">

                            <svg class="h-5 w-5"
                                 viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">

                                <path d="M4 19V5"></path>
                                <path d="M4 19h16"></path>
                                <path d="M8 16v-4"></path>
                                <path d="M12 16V8"></path>
                                <path d="M16 16v-7"></path>

                            </svg>

                        </div>

                        <h3 class="mt-4 text-sm font-bold text-slate-900">
                            System-wide Reports
                        </h3>

                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Monitor and generate reports across the platform.
                        </p>

                    </div>

                </div>

            </section>

        </div>

    </main>

</div>

</x-layouts::app>