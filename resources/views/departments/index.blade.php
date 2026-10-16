```blade
<x-layouts::app :title="'Department Management'">

<div class="min-h-screen bg-gray-50 dark:bg-neutral-950">

    {{-- Header --}}
    <div class="border-b border-gray-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="mx-auto max-w-7xl px-6 py-8">

            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <div class="mb-2 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <span>Superadmin</span>
                        <span>/</span>
                        <span>Departments</span>
                    </div>

                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Department Management
                    </h1>

                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Create departments and manage department assignments for admins and instructors.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">

                    {{-- Assign Department --}}
                    <a href="{{ route('departments.assign') }}"
                       class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-200 dark:hover:bg-neutral-700">

                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0m-4-3a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>

                        Assign Department
                    </a>

                    {{-- Create Department --}}
                    <a href="{{ route('departments.create') }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-700">

                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M12 4v16m8-8H4" />
                        </svg>

                        Create Department
                    </a>

                </div>
            </div>
        </div>
    </div>


    {{-- Main Content --}}
    <div class="mx-auto max-w-7xl px-6 py-8">

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif


        {{-- Statistics --}}
        <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Total Departments
                        </p>

                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $departments->count() }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2m-2 0h-2M5 21H3m2 0h2M9 7h6m-6 4h6m-6 4h3" />
                        </svg>
                    </div>

                </div>
            </div>


            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Assigned Users
                        </p>

                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $departments->sum('users_count') }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m14-11a4 4 0 11-8 0 4 4 0 018 0zm6 11v-2a4 4 0 00-3-3.87m0-10a4 4 0 010 7.75" />
                        </svg>
                    </div>

                </div>
            </div>


            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Unassigned Users
                        </p>

                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ \App\Models\User::whereNull('department_id')->count() }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M12 9v4m0 4h.01M10.29 3.86l-8.18 14a2 2 0 001.73 3h16.32a2 2 0 001.73-3l-8.18-14a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>

                </div>
            </div>

        </div>


        {{-- Departments --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">

            <div class="border-b border-gray-200 px-6 py-5 dark:border-neutral-800">

                <div class="flex items-center justify-between">

                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                            Departments
                        </h2>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Manage your organization's departments.
                        </p>
                    </div>

                </div>

            </div>


            @if($departments->count())

                <div class="divide-y divide-gray-200 dark:divide-neutral-800">

                    @foreach($departments as $department)

                        <div class="px-6 py-5 transition hover:bg-gray-50 dark:hover:bg-neutral-800/40">

                            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">

                                <div class="flex items-start gap-4">

                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">

                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  stroke-width="2"
                                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2m-2 0h-2M5 21H3m2 0h2M9 7h6m-6 4h6m-6 4h3" />
                                        </svg>

                                    </div>

                                    <div>
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                            {{ $department->name }}
                                        </h3>

                                        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                                            {{ $department->description ?: 'No description provided.' }}
                                        </p>

                                        <div class="mt-3 flex flex-wrap items-center gap-2">

                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-neutral-800 dark:text-gray-300">

                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round"
                                                          stroke-linejoin="round"
                                                          stroke-width="2"
                                                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 005.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857" />
                                                </svg>

                                                {{ $department->users_count }}
                                                {{ Str::plural('user', $department->users_count) }}

                                            </span>

                                        </div>
                                    </div>

                                </div>


                                {{-- Actions --}}
                                <div class="flex shrink-0 items-center gap-2">

                                    <a href="{{ route('departments.assign') }}"
                                       class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:border-neutral-700 dark:text-gray-300 dark:hover:bg-neutral-800">
                                        Assign
                                    </a>

                                    <a href="{{ route('departments.edit', $department) }}"
                                       class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:border-neutral-700 dark:text-gray-300 dark:hover:bg-neutral-800">
                                        Edit
                                    </a>

                                    <form action="{{ route('departments.destroy', $department) }}"
                                          method="POST"
                                          onsubmit="return confirm('Are you sure you want to delete this department?');">

                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:border-red-900/50 dark:text-red-400 dark:hover:bg-red-950/30">
                                            Delete
                                        </button>

                                    </form>

                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>

            @else

                <div class="px-6 py-16 text-center">

                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">

                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2m-2 0h-2M5 21H3m2 0h2M9 7h6m-6 4h6m-6 4h3" />
                        </svg>

                    </div>

                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                        No departments yet
                    </h3>

                    <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                        Create your first department to start organizing admins and instructors.
                    </p>

                    <a href="{{ route('departments.create') }}"
                       class="mt-5 inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-700">

                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M12 4v16m8-8H4" />
                        </svg>

                        Create Department

                    </a>

                </div>

            @endif

        </div>

    </div>

</div>

</x-layouts::app>
