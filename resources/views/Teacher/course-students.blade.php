<x-layouts::app :title="'Course Students'">

<div class="min-h-screen bg-black">

    <div class="mx-auto max-w-7xl p-6 space-y-6">

        {{-- Header --}}
        <div class="rounded-2xl border border-purple-500/20 bg-linear-to-r from-purple-900/40 via-purple-900/20 to-black p-6 shadow-xl">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

                <div>
                    <span class="inline-flex rounded-full bg-purple-500/20 px-3 py-1 text-xs font-semibold text-purple-300">
                        Course Management
                    </span>

                    <h1 class="mt-3 text-3xl font-bold text-white">
                        {{ $course->title }}
                    </h1>

                    <p class="mt-2 text-sm text-gray-400">
                        Manage enrolled students, track progress, and monitor learning activity.
                    </p>
                </div>

                <div class="flex gap-3">
                    <a href="{{ route('teacher.my-courses') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:bg-purple-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        My Courses
                    </a>

                    <a href="{{ route('courses.edit', $course) }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-purple-500/30 px-5 py-2.5 text-sm font-semibold text-purple-300 transition hover:bg-purple-500/10">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Edit Course
                    </a>
                </div>

            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

            <div class="rounded-xl border border-purple-500/20  bg-zinc-950 p-5 shadow-xl">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-500/20">
                        <svg class="h-5 w-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-400">Total Enrolled</p>
                        <p class="text-2xl font-bold text-white">{{ $enrollments->count() }}</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-500">Active students in this course</p>
            </div>

            <div class="rounded-xl border border-purple-500/20  bg-zinc-950 p-5 shadow-xl">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-500/20">
                        <svg class="h-5 w-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-400">Avg Progress</p>
                        <p class="text-2xl font-bold text-purple-400">{{ $enrollments->count() ? number_format($enrollments->avg('progress_percentage'), 0) : 0 }}%</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-500">Course completion rate</p>
            </div>

            <div class="rounded-xl border border-green-500/20 bg-zinc-950 p-5 shadow-xl">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-500/20">
                        <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-400">Completed</p>
                        <p class="text-2xl font-bold text-green-400">{{ $enrollments->where('status','completed')->count() }}</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-500">Students finished course</p>
            </div>

            <div class="rounded-xl border border-blue-500/20 bg-zinc-950 p-5 shadow-xl">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-500/20">
                        <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-400">Active Now</p>
                        <p class="text-2xl font-bold text-blue-400">{{ $enrollments->where('status','active')->count() }}</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-500">Currently learning</p>
            </div>

        </div>

        {{-- Students Table --}}
        <div class="overflow-hidden rounded-2xl border border-purple-500/20 bg-zinc-950 shadow-xl">

            <div class="border-b border-purple-500/10 p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-500/20">
                        <svg class="h-5 w-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-white">Enrolled Students</h2>
                        <p class="text-sm text-gray-400">Track student progress and engagement</p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">

                    <thead class="bg-[#111111] text-xs uppercase tracking-wider text-gray-400">
                        <tr>
                            <th class="px-6 py-4">Student</th>
                            <th class="px-6 py-4">Email</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Enrolled</th>
                            <th class="px-6 py-4">Progress</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-[#111111]">

                        @forelse($enrollments as $enrollment)
                        <tr class="transition hover:bg-white/5">

                            {{-- Student --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-purple-500/20 text-sm font-bold text-purple-400">
                                        {{ strtoupper(substr($enrollment->student->name, 0, 1)) }}
                                    </div>
                                    <span class="font-medium text-white">{{ $enrollment->student->name }}</span>
                                </div>
                            </td>

                            {{-- Email --}}
                            <td class="px-6 py-4 text-gray-400">
                                {{ $enrollment->student->email }}
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4">
                                @php
                                    $colors = [
                                        'active' => 'bg-blue-500/15 text-blue-400 border-blue-500/20',
                                        'completed' => 'bg-green-500/15 text-green-400 border-green-500/20',
                                        'dropped' => 'bg-red-500/15 text-red-400 border-red-500/20',
                                        'pending' => 'bg-yellow-500/15 text-yellow-400 border-yellow-500/20',
                                    ];
                                    $c = $colors[$enrollment->status] ?? 'bg-gray-500/15 text-gray-400 border-gray-500/20';
                                @endphp
                                <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $c }}">
                                    {{ ucfirst($enrollment->status) }}
                                </span>
                            </td>

                            {{-- Date --}}
                            <td class="px-6 py-4 text-gray-400">
                                {{ optional($enrollment->enrolled_at)->format('M d, Y') ?? 'N/A' }}
                            </td>

                            {{-- Progress --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-2 w-28 overflow-hidden rounded-full bg-[#111111]">
                                        <div class="h-full rounded-full bg-linear-to-r from-purple-500 to-indigo-500 transition-all"
                                             style="width: {{ $enrollment->progress_percentage }}%"></div>
                                    </div>
                                    <span class="w-10 text-xs font-medium text-gray-400">
                                        {{ number_format($enrollment->progress_percentage, 0) }}%
                                    </span>
                                </div>
                            </td>

                            {{-- Action --}}
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('teacher.student.progress', [$course, $enrollment->student]) }}"
                                   class="inline-flex items-center gap-1 rounded-lg bg-purple-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-purple-700">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    View
                                </a>
                            </td>

                        </tr>
                        @empty

                        <tr>
                            <td colspan="6" class="py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-purple-500/20">
                                        <svg class="h-8 w-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    </div>
                                    <p class="text-lg font-semibold text-white">No students enrolled yet</p>
                                    <p class="text-sm text-gray-400">Students will appear here once they enroll</p>
                                </div>
                            </td>
                        </tr>

                        @endforelse

                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

</x-layouts::app>