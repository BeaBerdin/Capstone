<x-layouts::app :title="__('Manage Users')">
    <div class="space-y-6">

        <div>
            <h1 class="text-3xl font-bold text-black">Manage Users</h1>
            <p class="mt-1 text-sm text-gray-400">
                View registered users, roles, and account activity.
            </p>
        </div>

        {{-- SUCCESS / ERROR MESSAGES --}}
        @if (session('success'))
            <div class="rounded-xl border border-green-700/40 bg-green-950/40 px-4 py-3 text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-red-700/40 bg-red-950/40 px-4 py-3 text-red-300">
                {{ session('error') }}
            </div>
        @endif

        @php
            $totalUsers = $users->count();

            $superAdminCount = $users->filter(
                fn ($user) => $user->roles->pluck('name')->contains('super_admin')
            )->count();

            $adminCount = $users->filter(
                fn ($user) => $user->roles->pluck('name')->contains('admin')
            )->count();

            $teacherCount = $users->filter(
                fn ($user) => $user->roles->pluck('name')->contains('teacher')
            )->count();

            $studentCount = $users->filter(
                fn ($user) => $user->roles->pluck('name')->contains('student')
            )->count();
        @endphp

        {{-- USER SUMMARY --}}
        <div class="grid gap-4 md:grid-cols-4">

            {{-- Super Admin --}}
            <div class="rounded-2xl border border-red-500/40 bg-neutral-900 p-5">
                <p class="text-sm text-red-400">Super Admin</p>
                <h2 class="mt-2 text-3xl font-bold text-red-400">
                    {{ $superAdminCount }}
                </h2>
                <p class="mt-1 text-xs text-gray-400">
                    System-level administrators
                </p>
            </div>

            {{-- Admin --}}
            <div class="rounded-2xl border border-purple-500/40 bg-neutral-900 p-5">
                <p class="text-sm text-purple-400">Admins</p>
                <h2 class="mt-2 text-3xl font-bold text-purple-400">
                    {{ $adminCount }}
                </h2>
                <p class="mt-1 text-xs text-gray-400">
                    Department administrators
                </p>
            </div>

            {{-- Teachers --}}
            <div class="rounded-2xl border border-green-500/40 bg-neutral-900 p-5">
                <p class="text-sm text-green-400">Teachers</p>
                <h2 class="mt-2 text-3xl font-bold text-green-400">
                    {{ $teacherCount }}
                </h2>
                <p class="mt-1 text-xs text-gray-400">
                    Course instructors
                </p>
            </div>

            {{-- Students --}}
            <div class="rounded-2xl border border-blue-500/40 bg-neutral-900 p-5">
                <p class="text-sm text-blue-400">Students</p>
                <h2 class="mt-2 text-3xl font-bold text-blue-400">
                    {{ $studentCount }}
                </h2>
                <p class="mt-1 text-xs text-gray-400">
                    Registered learners
                </p>
            </div>

        </div>

        {{-- USER DIRECTORY --}}
        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 shadow-lg shadow-purple-950/10">

            <div class="flex flex-col gap-3 border-b border-neutral-700 px-6 py-4 md:flex-row md:items-center md:justify-between">

                <div>
                    <h2 class="text-lg font-semibold text-white">
                        User Directory
                    </h2>

                    <p class="text-sm text-gray-400">
                        All registered PathWise accounts.
                    </p>
                </div>

                <div class="rounded-xl border border-neutral-700 bg-neutral-800 px-4 py-2 text-sm text-white">
                    {{ $totalUsers }} registered users
                </div>

            </div>

            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead class="bg-neutral-800 text-xs uppercase tracking-wider text-white">

                        <tr>
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4">Email</th>
                            <th class="px-6 py-4">Current Role</th>
                            <th class="px-6 py-4">Change Role</th>
                            <th class="px-6 py-4">Date Registered</th>
                        </tr>

                    </thead>

                    <tbody class="divide-y divide-neutral-800">

                        @forelse($users as $user)

                            @php
                                $roles = $user->roles->pluck('name');
                                $currentRole = $roles->first();
                            @endphp

                            <tr class="hover:bg-white/5">

                                {{-- USER --}}
                                <td class="px-6 py-4">

                                    <div class="flex items-center gap-3">

                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-600/20 text-sm font-bold text-purple-300">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>

                                        <div>

                                            <p class="font-semibold text-white">
                                                {{ $user->name }}
                                            </p>

                                            <p class="text-xs text-gray-500">
                                                PathWise User
                                            </p>

                                        </div>

                                    </div>

                                </td>

                                {{-- EMAIL --}}
                                <td class="px-6 py-4 text-gray-400">
                                    {{ $user->email }}
                                </td>

                                {{-- CURRENT ROLE --}}
                                <td class="px-6 py-4">

                                    <div class="flex flex-wrap gap-2">

                                        @forelse($roles as $role)

                                            @php
                                                $roleClass = match ($role) {
                                                    'super_admin' => 'bg-red-500/15 text-red-400',
                                                    'admin' => 'bg-purple-500/15 text-purple-400',
                                                    'teacher' => 'bg-green-500/15 text-green-400',
                                                    'student' => 'bg-blue-500/15 text-blue-400',
                                                    default => 'bg-gray-500/15 text-gray-400',
                                                };

                                                $roleLabel = match ($role) {
                                                    'super_admin' => 'Super Admin',
                                                    'admin' => 'Admin',
                                                    'teacher' => 'Teacher',
                                                    'student' => 'Student',
                                                    default => ucfirst($role),
                                                };
                                            @endphp

                                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $roleClass }}">
                                                {{ $roleLabel }}
                                            </span>

                                        @empty

                                            <span class="rounded-full bg-gray-500/15 px-3 py-1 text-xs font-semibold text-gray-400">
                                                No Role
                                            </span>

                                        @endforelse

                                    </div>

                                </td>

                                {{-- CHANGE ROLE --}}
                                <td class="px-6 py-4">

                                    <form
                                        action="{{ route('users.update-role', $user) }}"
                                        method="POST"
                                        class="flex items-center gap-2"
                                    >

                                        @csrf
                                        @method('PUT')

                                        <select
                                            name="role"
                                            class="rounded-lg border border-neutral-600 bg-neutral-800 px-3 py-2 text-xs text-white focus:border-purple-500 focus:outline-none"
                                        >
                                            <option value="super_admin"
                                                {{ $currentRole === 'super_admin' ? 'selected' : '' }}>
                                                Super Admin
                                            </option>

                                            <option value="admin"
                                                {{ $currentRole === 'admin' ? 'selected' : '' }}>
                                                Admin
                                            </option>

                                            <option value="teacher"
                                                {{ $currentRole === 'teacher' ? 'selected' : '' }}>
                                                Teacher
                                            </option>

                                            <option value="student"
                                                {{ $currentRole === 'student' ? 'selected' : '' }}>
                                                Student
                                            </option>
                                        </select>

                                        <button
                                            type="submit"
                                            onclick="return confirm('Are you sure you want to change this user role?')"
                                            class="rounded-lg bg-purple-600 px-3 py-2 text-xs font-semibold text-white hover:bg-purple-700"
                                        >
                                            Update
                                        </button>

                                    </form>

                                </td>

                                {{-- DATE --}}
                                <td class="px-6 py-4 text-gray-400">

                                    {{ $user->created_at->format('M d, Y h:i A') }}

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="5" class="px-6 py-16 text-center">

                                    <h3 class="text-lg font-semibold text-white">
                                        No users found
                                    </h3>

                                    <p class="mt-1 text-sm text-gray-400">
                                        Registered users will appear here.
                                    </p>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>
</x-layouts::app>