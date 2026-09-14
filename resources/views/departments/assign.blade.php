<x-layouts::app :title="'Assign Department'">

<div class="min-h-screen bg-gray-50 dark:bg-neutral-950">

    {{-- Header --}}
    <div class="border-b border-gray-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="mx-auto max-w-7xl px-6 py-8">

            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <div class="mb-2 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <span>Superadmin</span>
                        <span>/</span>
                        <a href="{{ route('departments.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">Departments</a>
                        <span>/</span>
                        <span>Assign</span>
                    </div>

                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Assign Department
                    </h1>

                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Assign an admin, teacher, or instructor to a department.
                    </p>
                </div>

                <a href="{{ route('departments.index') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-200 dark:hover:bg-neutral-700">

                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>

                    Back to Departments
                </a>

            </div>
        </div>
    </div>


    {{-- Main Content --}}
    <div class="mx-auto max-w-3xl px-6 py-8">

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


        {{-- Form Card --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">

            <div class="border-b border-gray-200 px-6 py-5 dark:border-neutral-800">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                    Assignment Details
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Select a user and the department they belong to.
                </p>
            </div>


            <form action="{{ route('departments.assign.store') }}" method="POST" class="px-6 py-6">
                @csrf

                {{-- Validation Errors --}}
                @if($errors->any())
                    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif


                {{-- User --}}
                <div class="mb-6">
                    <label for="user_id" class="mb-2 block text-sm font-semibold text-gray-900 dark:text-white">
                        User
                    </label>

                    <select name="user_id"
                            id="user_id"
                            required
                            class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white dark:focus:border-purple-500">

                        <option value="" disabled @selected(!old('user_id'))>Select a user</option>

                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                                {{ $user->name }} — {{ $user->email }}
                            </option>
                        @endforeach

                    </select>

                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Only users with admin, teacher, or instructor roles are listed.
                    </p>
                </div>


                {{-- Department --}}
                <div class="mb-8">
                    <label for="department_id" class="mb-2 block text-sm font-semibold text-gray-900 dark:text-white">
                        Department
                    </label>

                    <select name="department_id"
                            id="department_id"
                            required
                            class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white dark:focus:border-purple-500">

                        <option value="" disabled @selected(!old('department_id'))>Select a department</option>

                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>
                                {{ $department->name }}
                            </option>
                        @endforeach

                    </select>

                    @if($departments->isEmpty())
                        <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                            No departments exist yet.
                            <a href="{{ route('departments.create') }}" class="font-semibold underline">Create one first</a>.
                        </p>
                    @endif
                </div>


                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-6 dark:border-neutral-800">

                    <a href="{{ route('departments.index') }}"
                       class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 dark:border-neutral-700 dark:text-gray-300 dark:hover:bg-neutral-800">
                        Cancel
                    </a>

                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-700">
                        Assign Department
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

</x-layouts::app>