<x-layouts::app :title="__('Manage Users')">

    @php
        // Counts over every account (the directory below is searched and paginated).
        $superAdminCount = (int) ($roleCounts['super_admin'] ?? 0);
        $adminCount = (int) ($roleCounts['admin'] ?? 0);
        $teacherCount = (int) ($roleCounts['teacher'] ?? 0);
        $studentCount = (int) ($roleCounts['student'] ?? 0);
    @endphp

    <style>
        .pw-card {
            background: #ffffff;
            border: 1px solid #e7e9ef;
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.035);
        }

        .pw-hover {
            transition:
                transform 160ms ease,
                border-color 160ms ease,
                box-shadow 160ms ease;
        }

        .pw-hover:hover {
            transform: translateY(-2px);
            border-color: #ddd6fe;
            box-shadow: 0 12px 30px rgba(76, 29, 149, 0.06);
        }
    </style>


@include('partials.soft3d')
    <div class="sad-page min-h-screen bg-[#f8f9fc]">

        <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

            <div class="mx-auto max-w-375">


                {{-- =====================================================
                    HEADER
                ====================================================== --}}

                {{-- Header card, as on My Certificates. --}}
                <section class="sad-banner overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">
                    <div class="relative px-6 py-6 sm:px-7">
                        <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full bg-violet-100/70 blur-3xl"></div>

                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">

                    <div>

                        <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                            Super Admin
                        </p>

                        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                            User Management
                        </h1>

                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                            Manage registered users, assigned roles, and account information
                            across the PathWise platform.
                        </p>

                    </div>

                </div>
                    </div>
                </section>


                {{-- =====================================================
                    SUCCESS / ERROR MESSAGES
                ====================================================== --}}

                @if (session('success'))

                    <div class="mt-6 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4">

                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                            ✓
                        </div>

                        <p class="text-sm font-medium text-emerald-700">
                            {{ session('success') }}
                        </p>

                    </div>

                @endif


                @if ($errors->any())
                    <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-700" data-test="form-errors">
                        @foreach ($errors->all() as $message)
                            <p>{{ $message }}</p>
                        @endforeach
                    </div>
                @endif


                @if (session('error'))

                    <div class="mt-6 flex items-center gap-3 rounded-2xl border border-red-200 bg-red-50 px-5 py-4">

                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">
                            !
                        </div>

                        <p class="text-sm font-medium text-red-700">
                            {{ session('error') }}
                        </p>

                    </div>

                @endif



                {{-- =====================================================
                    CREATE ACCOUNT (Manuscript Figure 31: "Add User")
                ====================================================== --}}

                <section class="sad-card mt-6 flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">

                    <div class="flex items-start gap-3">
                        <span class="sad-section-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="4"></circle><path d="M2 21a7 7 0 0 1 14 0M19 8v6M16 11h6"></path></svg>
                        </span>

                        <div>
                            <h2 class="text-sm font-bold text-slate-900">
                                Create Account
                            </h2>

                            <p class="mt-0.5 text-xs text-slate-500">
                                Add an Administrator, Teacher, Student, or Super Admin account.
                                Teacher accounts require EDP approval and email confirmation.
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('users.create') }}" class="sad-btn sad-btn-primary shrink-0 self-start sm:self-auto">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
                        Add User
                    </a>

                </section>

                {{-- =====================================================
                    ROLE SUMMARY
                ====================================================== --}}

                <section class="mt-7 grid grid-cols-2 gap-4 xl:grid-cols-4">


                    {{-- ADMIN --}}

                    <div class="sad-card pw-card pw-hover p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div>

                                <p class="text-xs font-semibold text-slate-500">
                                    Administrators
                                </p>

                                <p class="mt-2 text-3xl font-bold tracking-tight text-violet-600">
                                    {{ $adminCount }}
                                </p>

                            </div>

                            <div class="sad-chip sad-chip-sm sad-violet flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">

                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"></path>
                                    <path d="M9 12l2 2 4-4"></path>
                                </svg>

                            </div>

                        </div>

                        <p class="mt-2 text-xs text-slate-500">
                            Department administrators
                        </p>

                    </div>


                    {{-- TEACHERS --}}

                    <div class="sad-card pw-card pw-hover p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div>

                                <p class="text-xs font-semibold text-slate-500">
                                    Teachers
                                </p>

                                <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-600">
                                    {{ $teacherCount }}
                                </p>

                            </div>

                            <div class="sad-chip sad-chip-sm sad-emerald flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">

                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>

                            </div>

                        </div>

                        <p class="mt-2 text-xs text-slate-500">
                            Course instructors
                        </p>

                    </div>


                    {{-- STUDENTS --}}

                    <div class="sad-card pw-card pw-hover p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div>

                                <p class="text-xs font-semibold text-slate-500">
                                    Students
                                </p>

                                <p class="mt-2 text-3xl font-bold tracking-tight text-blue-600">
                                    {{ $studentCount }}
                                </p>

                            </div>

                            <div class="sad-chip sad-chip-sm sad-sky flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">

                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 00-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 010 7.75"></path>
                                </svg>

                            </div>

                        </div>

                        <p class="mt-2 text-xs text-slate-500">
                            Registered learners
                        </p>

                    </div>


                    {{-- SUPER ADMIN --}}

                    <div class="sad-card pw-card pw-hover p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div>

                                <p class="text-xs font-semibold text-slate-500">
                                    Super Admin
                                </p>

                                <p class="mt-2 text-3xl font-bold tracking-tight text-orange-500">
                                    {{ $superAdminCount }}
                                </p>

                            </div>

                            <div class="sad-chip sad-chip-sm sad-amber flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-orange-500">

                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M12 2l3 6 6 .9-4.5 4.4 1.1 6.2L12 16.5 6.4 19.5l1.1-6.2L3 8.9 9 8l3-6z"></path>
                                </svg>

                            </div>

                        </div>

                        <p class="mt-2 text-xs text-slate-500">
                            System-level administrators
                        </p>

                    </div>

                </section>


                {{-- =====================================================
                    USER DIRECTORY
                ====================================================== --}}

                <section class="sad-card pw-card mt-6 overflow-hidden">


                    {{-- TABLE HEADER --}}

                    <div class="border-b border-slate-100 px-5 py-5 sm:px-6">

                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                            <div>

                                <h2 class="text-lg font-bold text-slate-900">
                                    User Directory
                                </h2>

                                <p class="mt-1 text-xs text-slate-500">
                                    View and manage all registered PathWise accounts.
                                </p>

                            </div>


                            {{-- SEARCH --}}

                            <form method="GET" action="{{ route('users.index') }}" role="search" class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto" data-user-search-form>

                                <label for="userDepartmentFilter" class="sr-only">Filter by department</label>

                                <select
                                    id="userDepartmentFilter"
                                    name="department"
                                    class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs text-slate-700 outline-none transition focus:border-violet-300 focus:bg-white focus:ring-2 focus:ring-violet-100 sm:w-56"
                                >
                                    <option value="" @selected($departmentFilter === '')>All departments</option>
                                    <option value="none" @selected($departmentFilter === 'none')>No department</option>
                                    @foreach ($departments as $filterDepartment)
                                        <option value="{{ $filterDepartment->id }}" @selected($departmentFilter === (string) $filterDepartment->id)>
                                            {{ $filterDepartment->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <div class="relative w-full lg:w-72">

                                <label for="userSearch" class="sr-only">Search users by name or email</label>

                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">

                                    <svg
                                        class="h-4 w-4 text-slate-500"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <path d="m21 21-4.35-4.35"></path>
                                    </svg>

                                </div>

                                <input
                                    type="search"
                                    id="userSearch"
                                    name="search"
                                    value="{{ $search }}"
                                    maxlength="100"
                                    autocomplete="off"
                                    placeholder="Search users..."
                                    class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-4 text-xs text-slate-700 outline-none transition placeholder:text-slate-500 focus:border-violet-300 focus:bg-white focus:ring-2 focus:ring-violet-100"
                                >

                                </div>

                                <noscript>
                                    <button type="submit" class="h-10 rounded-xl border border-slate-200 px-4 text-xs font-semibold text-slate-700">Search</button>
                                </noscript>

                            </form>

                        </div>

                    </div>


                    {{-- TABLE (searched and paginated on the server; live search swaps this block) --}}

                    <div id="userDirectoryResults" aria-live="polite" data-user-results>

                    @if ($search !== '' || $departmentFilter !== '')
                        @php
                            $filterLabel = match (true) {
                                $departmentFilter === 'none' => 'with no department',
                                $departmentFilter !== '' => 'in '.($departments->firstWhere('id', (int) $departmentFilter)?->name ?? ''),
                                default => '',
                            };
                        @endphp
                        <p class="border-b border-slate-100 px-5 py-3 text-xs text-slate-500 sm:px-6" data-test="user-search-summary">
                            {{ $users->total() }} {{ \Illuminate\Support\Str::plural('user', $users->total()) }} found{!! $search !== '' ? ' for &ldquo;'.e($search).'&rdquo;' : '' !!}{{ $filterLabel !== '' ? ' '.$filterLabel : '' }}
                        </p>
                    @endif

                    <div class="overflow-x-auto">

                        <table class="w-full min-w-237.5 text-left">

                            <thead class="border-b border-slate-100 bg-slate-50/80">

                                <tr class="text-xs font-bold uppercase tracking-[.08em] text-slate-500">

                                    <th class="px-4 py-4">
                                        User
                                    </th>

                                    <th class="px-4 py-4">
                                        Email
                                    </th>

                                    <th class="px-4 py-4">
                                        Department
                                    </th>

                                    <th class="px-4 py-4">
                                        Current Role
                                    </th>

                                    <th class="px-4 py-4">
                                        Change Role
                                    </th>

                                    <th class="px-4 py-4">
                                        Status
                                    </th>

                                    <th class="px-4 py-4">
                                        Registered
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="userTableBody" class="divide-y divide-slate-100">


                                @forelse($users as $user)

                                    @php

                                        $roles = $user->roles->pluck('name');

                                        $currentRole = $roles->first();

                                        $roleClass = match ($currentRole) {

                                            'super_admin' => 'bg-orange-50 text-orange-600',

                                            'admin' => 'bg-violet-50 text-violet-700',

                                            'teacher' => 'bg-emerald-50 text-emerald-700',

                                            'student' => 'bg-blue-50 text-blue-700',

                                            default => 'bg-slate-100 text-slate-600',

                                        };

                                        $roleLabel = match ($currentRole) {

                                            'super_admin' => 'Super Admin',

                                            'admin' => 'Admin',

                                            'teacher' => 'Teacher',

                                            'student' => 'Student',

                                            default => 'No Role',

                                        };

                                    @endphp


                                    <tr
                                        class="user-row transition hover:bg-slate-50/70"
                                    >


                                        {{-- USER --}}

                                        <td class="px-4 py-5">

                                            <div class="flex items-center gap-3">

                                                <div class="sad-chip sad-chip-sm sad-violet flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-sm font-bold text-violet-600">

                                                    {{ strtoupper(substr($user->name, 0, 1)) }}

                                                </div>

                                                <div class="min-w-0">

                                                    <p class="truncate text-sm font-semibold text-slate-800">
                                                        {{ $user->name }}
                                                    </p>

                                                    <p class="mt-0.5 text-xs text-slate-500">
                                                        PathWise User
                                                    </p>

                                                </div>

                                            </div>

                                        </td>


                                        {{-- EMAIL --}}

                                        <td class="px-4 py-5">

                                            <p class="text-xs font-medium text-slate-600">
                                                {{ $user->email }}
                                            </p>

                                        </td>


                                        {{-- DEPARTMENT --}}

                                        <td class="px-4 py-5" data-test="user-department">

                                            @if ($user->department)
                                                <p class="text-xs font-medium text-slate-700" title="{{ $user->department->name }}">
                                                    {{ $user->department->shortLabel() }}
                                                </p>
                                            @else
                                                <p class="text-xs text-slate-400">None</p>
                                            @endif

                                        </td>


                                        {{-- CURRENT ROLE --}}

                                        <td class="px-4 py-5">

                                            @if($currentRole)

                                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold {{ $roleClass }}">

                                                    <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>

                                                    {{ $roleLabel }}

                                                </span>

                                                {{-- Teachers and admins need a department for course review. --}}
                                                @if (in_array($currentRole, ['teacher', 'admin'], true) && ! $user->department_id)
                                                    <span class="mt-1 inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-amber-200" data-test="no-department-badge">
                                                        No department
                                                    </span>
                                                @endif

                                            @else

                                                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-500">
                                                    No Role
                                                </span>

                                            @endif

                                        </td>


                                        {{-- CHANGE ROLE --}}

                                        <td class="px-4 py-5">

                                            <form
                                                action="{{ route('users.update-role', $user) }}"
                                                method="POST"
                                                class="flex items-center gap-2"
                                                data-role-form
                                                data-user-name="{{ $user->name }}"
                                                data-current-role="{{ $currentRole }}"
                                            >

                                                @csrf

                                                @method('PUT')


                                                <select
                                                    name="role"
                                                    class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 outline-none transition focus:border-violet-300 focus:ring-2 focus:ring-violet-100"
                                                >

                                                    <option
                                                        value="super_admin"
                                                        {{ $currentRole === 'super_admin' ? 'selected' : '' }}
                                                    >
                                                        Super Admin
                                                    </option>

                                                    <option
                                                        value="admin"
                                                        {{ $currentRole === 'admin' ? 'selected' : '' }}
                                                    >
                                                        Admin
                                                    </option>

                                                    <option
                                                        value="teacher"
                                                        {{ $currentRole === 'teacher' ? 'selected' : '' }}
                                                    >
                                                        Teacher
                                                    </option>

                                                    <option
                                                        value="student"
                                                        {{ $currentRole === 'student' ? 'selected' : '' }}
                                                    >
                                                        Student
                                                    </option>

                                                </select>


                                                <input type="hidden" name="reason" value="">
                                                <input type="hidden" name="current_password" value="">

                                                <button
                                                    type="button"
                                                    data-open-role-dialog
                                                    class="sad-btn-primary h-9 rounded-lg bg-violet-600 px-3 text-xs font-semibold text-white transition hover:bg-violet-700"
                                                >
                                                    Update
                                                </button>

                                            </form>

                                        </td>


                                        {{-- STATUS (activate / deactivate) --}}

                                        <td class="px-4 py-5">

                                            @php
                                                $isActive = $user->is_active !== false;
                                            @endphp

                                            <div class="flex items-center gap-2">

                                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $isActive ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' }}">
                                                    {{ $isActive ? 'Active' : 'Deactivated' }}
                                                </span>

                                                @if($user->id !== auth()->id())

                                                    <form
                                                        action="{{ route('users.update-status', $user) }}"
                                                        method="POST"
                                                        onsubmit="return confirm('{{ $isActive ? 'Deactivate' : 'Activate' }} this account?')"
                                                    >
                                                        @csrf
                                                        @method('PATCH')

                                                        <input type="hidden" name="is_active" value="{{ $isActive ? 0 : 1 }}">

                                                        <button
                                                            type="submit"
                                                            class="h-8 rounded-lg border px-2.5 text-xs font-semibold transition {{ $isActive ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }}"
                                                        >
                                                            {{ $isActive ? 'Deactivate' : 'Activate' }}
                                                        </button>
                                                    </form>

                                                @endif

                                            </div>

                                            @if($currentRole === 'teacher')
                                                <form action="{{ route('users.teacher-review', $user) }}" method="POST" class="mt-1.5">
                                                    @csrf
                                                    <button type="submit" class="h-7 rounded-lg border border-violet-200 px-2 text-xs font-semibold text-violet-700 hover:bg-violet-50">
                                                        Open teacher review
                                                    </button>
                                                </form>
                                                @unless($user->hasVerifiedEmail())
                                                    <p class="mt-1 text-xs text-amber-600">Email not verified</p>
                                                @endunless
                                            @elseif(! $user->hasVerifiedEmail())
                                                <div class="mt-1.5 flex items-center gap-2">
                                                    <p class="text-xs text-amber-600">Email not verified</p>

                                                    <form
                                                        action="{{ route('users.verify-email', $user) }}"
                                                        method="POST"
                                                        onsubmit="return confirm('Mark this email address as verified?')"
                                                    >
                                                        @csrf
                                                        @method('PATCH')

                                                        <button
                                                            type="submit"
                                                            class="h-7 rounded-lg border border-amber-200 px-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-50"
                                                        >
                                                            Mark verified
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif

                                            <a
                                                href="{{ route('users.edit', $user) }}"
                                                class="mt-1.5 inline-flex h-7 items-center rounded-lg border border-violet-200 px-2.5 text-xs font-semibold text-violet-700 transition hover:bg-violet-50"
                                            >
                                                Edit name / email
                                            </a>

                                        </td>


                                        {{-- DATE --}}

                                        <td class="px-4 py-5">

                                            <p class="text-xs font-semibold text-slate-600">
                                                {{ $user->created_at->format('M d, Y') }}
                                            </p>

                                            <p class="mt-0.5 text-xs text-slate-500">
                                                {{ $user->created_at->format('h:i A') }}
                                            </p>

                                        </td>

                                    </tr>


                                @empty

                                    <tr>

                                        <td colspan="7" class="px-6 py-16 text-center">

                                            <div class="sad-chip sad-violet mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">

                                                <svg
                                                    class="h-6 w-6"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"></path>
                                                    <circle cx="9" cy="7" r="4"></circle>
                                                    <path d="M22 21v-2a4 4 0 00-3-3.87"></path>
                                                    <path d="M16 3.13a4 4 0 010 7.75"></path>
                                                </svg>

                                            </div>

                                            <h3 class="mt-4 text-sm font-bold text-slate-800">
                                                No users found
                                            </h3>

                                            <p class="mt-1 text-xs text-slate-500">
                                                @if ($search !== '')
                                                    No users match &ldquo;{{ $search }}&rdquo;. Try a different name or email.
                                                @elseif ($departmentFilter !== '')
                                                    No users match this department filter.
                                                @else
                                                    Registered users will appear here.
                                                @endif
                                            </p>

                                        </td>

                                    </tr>

                                @endforelse



                            </tbody>

                        </table>

                    </div>

                    @if ($users->hasPages())
                        <div class="border-t border-slate-100 px-5 py-3 sm:px-6" data-test="user-pagination">
                            {{ $users->links() }}
                        </div>
                    @endif

                    </div>

                </section>


            </div>

        </main>

    </div>


    {{-- =====================================================
        SEARCH SCRIPT
        The server does the search (name in any order, middle
        initial optional, email) and the paging. As you type, the
        results block is fetched and swapped in; Enter submits the
        form normally. It runs on every visit, including wire:navigate
        page changes (no DOMContentLoaded).
    ====================================================== --}}

    <script>
        (function () {
            var form = document.querySelector('[data-user-search-form]');
            var input = document.getElementById('userSearch');
            if (!form || !input || form.dataset.searchBound) { return; }
            form.dataset.searchBound = 'true';

            var timer = null;
            var controller = null;

            // Search text + department filter; a new search starts at page 1.
            function searchUrl() {
                var url = new URL(form.action, window.location.origin);
                var params = new URLSearchParams();
                new FormData(form).forEach(function (value, key) {
                    value = String(value).trim();
                    if (value !== '') { params.set(key, value); }
                });
                url.search = params.toString();
                return url;
            }

            function load(url) {
                if (controller) { controller.abort(); }
                controller = new AbortController();
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal, credentials: 'same-origin' })
                    .then(function (response) {
                        if (!response.ok) { throw new Error('search failed'); }
                        return response.text();
                    })
                    .then(function (html) {
                        var next = new DOMParser().parseFromString(html, 'text/html').querySelector('[data-user-results]');
                        var current = document.querySelector('[data-user-results]');
                        if (!next || !current) { window.location.assign(url); return; }
                        current.replaceWith(next);
                        window.history.replaceState(window.history.state, '', url);
                    })
                    .catch(function (error) {
                        if (error.name !== 'AbortError') { window.location.assign(url); }
                    });
            }

            input.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () { load(searchUrl()); }, 300);
            });

            var departmentFilter = document.getElementById('userDepartmentFilter');
            if (departmentFilter) {
                departmentFilter.addEventListener('change', function () {
                    clearTimeout(timer);
                    load(searchUrl());
                });
            }

            form.addEventListener('submit', function () {
                clearTimeout(timer);
                if (controller) { controller.abort(); }
            });
        })();
    </script>

    {{-- =====================================================
        ROLE CHANGE CONFIRMATION (the server checks everything again)
    ====================================================== --}}

    <dialog id="role-dialog" class="w-[min(28rem,calc(100vw-2rem))] rounded-2xl p-0 shadow-2xl backdrop:bg-slate-900/40" data-test="role-dialog">
        <form id="role-dialog-form" class="space-y-4 p-6">
            <h2 class="text-lg font-bold text-slate-900">Change role</h2>
            <p class="text-sm text-slate-600" data-role-dialog-text></p>

            <div data-role-dialog-warning hidden class="rounded-lg bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800">
                A Super Admin can manage every account, role and payment. Re-enter <strong>your own</strong> password to confirm.
            </div>

            <label class="block text-sm font-semibold text-slate-700">
                Reason
                <textarea id="role-dialog-reason" rows="3" required maxlength="500" placeholder="e.g. Appointed as department head" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800"></textarea>
            </label>

            <label class="block text-sm font-semibold text-slate-700" data-role-password-field hidden>
                Your password
                <input id="role-dialog-password" type="password" autocomplete="current-password" class="mt-1 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-800">
            </label>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" data-dialog-cancel class="h-10 rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="h-10 rounded-lg bg-violet-600 px-4 text-sm font-semibold text-white hover:bg-violet-700">Confirm change</button>
            </div>
        </form>
    </dialog>

    <script>
        (function () {
            var roleLabels = { super_admin: 'Super Admin', admin: 'Admin', teacher: 'Teacher', student: 'Student' };
            var dialog = document.getElementById('role-dialog');
            if (!dialog || typeof dialog.showModal !== 'function') { return; }

            var activeForm = null;
            var reason = document.getElementById('role-dialog-reason');
            var password = document.getElementById('role-dialog-password');

            dialog.querySelector('[data-dialog-cancel]').addEventListener('click', function () {
                password.value = '';
                dialog.close();
            });

            // Delegated: the rows are replaced by the live search.
            if (dialog.dataset.roleBound) { return; }
            dialog.dataset.roleBound = 'true';

            document.addEventListener('click', function (event) {
                var button = event.target.closest('[data-open-role-dialog]');
                if (!button || !document.contains(dialog)) { return; }
                {
                    activeForm = button.closest('[data-role-form]');
                    var role = activeForm.querySelector('select[name="role"]').value;
                    var promoting = role === 'super_admin' && activeForm.dataset.currentRole !== 'super_admin';

                    dialog.querySelector('[data-role-dialog-text]').textContent =
                        'Change ' + activeForm.dataset.userName + ' to ' + (roleLabels[role] || role) + '?'
                        + (role === 'teacher' ? ' The account goes to EDP review before teacher access.' : '');
                    dialog.querySelector('[data-role-dialog-warning]').hidden = !promoting;
                    dialog.querySelector('[data-role-password-field]').hidden = !promoting;

                    reason.value = '';
                    password.value = '';
                    password.required = promoting;

                    dialog.showModal();
                }
            });

            document.getElementById('role-dialog-form').addEventListener('submit', function (event) {
                event.preventDefault();
                if (!activeForm) { return; }
                activeForm.querySelector('input[name="reason"]').value = reason.value;
                activeForm.querySelector('input[name="current_password"]').value = password.value;
                password.value = '';
                dialog.close();
                activeForm.submit();
            });
        })();
    </script>

</x-layouts::app>
