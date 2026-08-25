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

<div class="space-y-6">

    {{-- Hero Banner --}}
    <div class="rounded-2xl border border-blue-500/30 bg-linear-to-r from-blue-900 via-neutral-900 to-neutral-900 p-6">
        <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">

            <div>
                <p class="text-sm font-medium text-blue-300">
                    PathWise System Administration
                </p>

                <h1 class="mt-2 text-4xl font-bold text-white">
                    Welcome, {{ auth()->user()->name }} 👋
                </h1>

                <p class="mt-2 text-sm text-blue-200">
                    Electronic Data Processing (EDP) — Super Admin
                </p>

                <p class="mt-4 max-w-2xl text-gray-400">
                    Manage user accounts and roles, verify transactions,
                    and monitor system-wide information.
                </p>
            </div>

        </div>
    </div>


    {{-- System Statistics --}}
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
            <p class="text-sm text-gray-400">Total Users</p>
            <h2 class="mt-2 text-3xl font-bold text-white">
                {{ $totalUsers }}
            </h2>
            <p class="mt-1 text-xs text-gray-400">
                All registered platform users
            </p>
        </div>

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
            <p class="text-sm text-gray-400">Students</p>
            <h2 class="mt-2 text-3xl font-bold text-white">
                {{ $totalStudents }}
            </h2>
            <p class="mt-1 text-xs text-gray-400">
                Registered learners
            </p>
        </div>

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
            <p class="text-sm text-gray-400">Teachers</p>
            <h2 class="mt-2 text-3xl font-bold text-white">
                {{ $totalTeachers }}
            </h2>
            <p class="mt-1 text-xs text-gray-400">
                Course instructors
            </p>
        </div>

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
            <p class="text-sm text-gray-400">Administrators</p>
            <h2 class="mt-2 text-3xl font-bold text-white">
                {{ $totalAdmins }}
            </h2>
            <p class="mt-1 text-xs text-gray-400">
                Department administrators
            </p>
        </div>

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
            <p class="text-sm text-gray-400">Total Courses</p>
            <h2 class="mt-2 text-3xl font-bold text-white">
                {{ $totalCourses }}
            </h2>
            <p class="mt-1 text-xs text-gray-400">
                Courses in the system
            </p>
        </div>

        <div class="rounded-2xl border border-yellow-500/40 bg-neutral-900 p-5">
            <p class="text-sm text-yellow-400">Pending Transactions</p>
            <h2 class="mt-2 text-3xl font-bold text-yellow-400">
                {{ $pendingTransactions }}
            </h2>
            <p class="mt-1 text-xs text-gray-400">
                Payments awaiting verification
            </p>
        </div>

    </div>


    {{-- Super Admin Functions --}}
    <div class="grid gap-4 lg:grid-cols-3">

        {{-- User Management --}}
        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">

            <h3 class="text-lg font-semibold text-white">
                User Management
            </h3>

            <p class="mt-1 text-sm text-gray-400">
                Manage platform accounts and user roles.
            </p>

            <a href="{{ route('users.index') }}"
               class="mt-4 block rounded-xl border border-blue-500/40 bg-blue-500/10 p-4 transition hover:bg-blue-500/20">

                <p class="font-semibold text-blue-300">
                    Manage Users
                </p>

                <p class="mt-1 text-xs text-gray-400">
                    View users and manage their assigned roles.
                </p>

            </a>

        </div>


        {{-- Transaction Verification --}}
        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">

            <h3 class="text-lg font-semibold text-white">
                Payment Verification
            </h3>

            <p class="mt-1 text-sm text-gray-400">
                Review and verify submitted payment transactions.
            </p>

            <a href="{{ route('super_admin.transactions.index') }}"
               class="mt-4 block rounded-xl border border-yellow-500/40 bg-yellow-500/10 p-4 transition hover:bg-yellow-500/20">

                <p class="font-semibold text-yellow-300">
                    Verify Transactions
                </p>

                <p class="mt-1 text-xs text-gray-400">
                    Review pending payment proofs.
                </p>

            </a>

        </div>


        {{-- Reports --}}
        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">

            <h3 class="text-lg font-semibold text-white">
                System Reports
            </h3>

            <p class="mt-1 text-sm text-gray-400">
                View system-wide reports and information.
            </p>

            <a href="{{ route('reports.index') }}"
               class="mt-4 block rounded-xl border border-green-500/40 bg-green-500/10 p-4 transition hover:bg-green-500/20">

                <p class="font-semibold text-green-300">
                    View Reports
                </p>

                <p class="mt-1 text-xs text-gray-400">
                    Generate and view platform reports.
                </p>

            </a>

        </div>

    </div>


    {{-- Role Responsibilities --}}
    <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">

        <h3 class="text-lg font-semibold text-white">
            Super Admin Responsibilities
        </h3>

        <p class="mt-1 text-sm text-gray-400">
            System-level administrative functions assigned to the EDP.
        </p>

        <div class="mt-5 grid gap-4 md:grid-cols-3">

            <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4">
                <p class="font-semibold text-white">
                    User Accounts & Roles
                </p>

                <p class="mt-1 text-sm text-gray-400">
                    Manage platform users and their system roles.
                </p>
            </div>

            <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4">
                <p class="font-semibold text-white">
                    Payment Verification
                </p>

                <p class="mt-1 text-sm text-gray-400">
                    Verify student payment transactions before access.
                </p>
            </div>

            <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4">
                <p class="font-semibold text-white">
                    System-wide Reports
                </p>

                <p class="mt-1 text-sm text-gray-400">
                    Monitor and generate reports across the platform.
                </p>
            </div>

        </div>

    </div>

</div>

</x-layouts::app>