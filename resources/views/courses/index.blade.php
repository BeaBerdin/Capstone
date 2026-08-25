<x-layouts::app :title="__('Courses')">
    <div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black">Courses</h1>
                <p class="mt-1 text-sm text-gray-400">
                    Manage course content, pricing, teachers, and publishing status.
                </p>
            </div>

            <a href="{{ route('courses.create') }}"
               class="rounded-xl bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700">
                Add Course
            </a>
        </div>

        @php
            $totalCourses = $courses->count();
            $publishedCourses = $courses->where('status', 'published')->count();
            $pendingCourses = $courses->where('status', 'pending')->count();
            $draftCourses = $courses->where('status', 'draft')->count();
        @endphp

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
                <p class="text-sm text-gray-400">Total Courses</p>
                <h2 class="mt-2 text-3xl font-bold text-white">{{ $totalCourses }}</h2>
                <p class="mt-1 text-xs text-gray-400">All created courses</p>
            </div>

            <div class="rounded-2xl border border-green-500/40 bg-neutral-900 p-5">
                <p class="text-sm text-green-400">Published</p>
                <h2 class="mt-2 text-3xl font-bold text-green-400">{{ $publishedCourses }}</h2>
                <p class="mt-1 text-xs text-gray-400">Live on marketplace</p>
            </div>

            <div class="rounded-2xl border border-yellow-500/40 bg-neutral-900 p-5">
                <p class="text-sm text-yellow-400">Pending</p>
                <h2 class="mt-2 text-3xl font-bold text-yellow-400">{{ $pendingCourses }}</h2>
                <p class="mt-1 text-xs text-gray-400">Awaiting approval</p>
            </div>

            <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
                <p class="text-sm text-gray-400">Drafts</p>
                <h2 class="mt-2 text-3xl font-bold text-white">{{ $draftCourses }}</h2>
                <p class="mt-1 text-xs text-gray-400">Unpublished work</p>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-green-700/40 bg-green-950/40 px-4 py-3 text-green-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 shadow-lg shadow-purple-950/10">
            <div class="border-b border-neutral-700 px-6 py-4">
                <h2 class="text-lg font-semibold text-white">Course Directory</h2>
                <p class="text-sm text-gray-400">All courses created in PathWise.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-neutral-800 text-xs uppercase tracking-wider text-white">
                        <tr>
                            <th class="px-6 py-4">Course</th>
                            <th class="px-6 py-4">Category</th>
                            <th class="px-6 py-4">Teacher</th>
                            <th class="px-6 py-4">Price</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-neutral-800">
                        @forelse($courses as $course)
                            @php
                                $status = strtolower($course->status);

                                $statusClass = match ($status) {
                                    'published' => 'bg-green-500/15 text-green-400',
                                    'pending' => 'bg-yellow-500/15 text-yellow-400',
                                    'rejected' => 'bg-red-500/15 text-red-400',
                                    'draft' => 'bg-gray-500/15 text-gray-400',
                                    default => 'bg-blue-500/15 text-blue-400',
                                };
                            @endphp

                            <tr class="hover:bg-white/5">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-600/20 text-lg">
                                            📚
                                        </div>

                                        <div>
                                            <p class="font-semibold text-white">{{ $course->title }}</p>
                                            <p class="text-xs text-gray-500">Course</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-gray-400">
                                    {{ $course->category->name ?? 'No Category' }}
                                </td>

                                <td class="px-6 py-4 text-gray-400">
                                    {{ $course->teacher->name ?? 'No Teacher' }}
                                </td>

                                <td class="px-6 py-4 font-semibold text-white">
                                    ₱{{ number_format($course->price, 2) }}
                                </td>

                                <td class="px-6 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                        {{ ucfirst($course->status) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if($course->status === 'pending')
                                            <form action="{{ route('courses.approve', $course) }}" method="POST">
                                                @csrf
                                                <button type="submit"
                                                    class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700">
                                                    Approve
                                                </button>
                                            </form>

                                            <form action="{{ route('courses.reject', $course) }}" method="POST">
                                                @csrf
                                                <button type="submit"
                                                    onclick="return confirm('Reject this course?')"
                                                    class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">
                                                    Reject
                                                </button>
                                            </form>
                                        @endif

                                        <a href="{{ route('courses.edit', $course) }}"
                                           class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                                            Edit
                                        </a>

                                        <form action="{{ route('courses.destroy', $course) }}" method="POST">
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                onclick="return confirm('Delete this course?')"
                                                class="rounded-lg bg-red-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-800">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <h3 class="text-lg font-semibold text-white">No courses found</h3>
                                    <p class="mt-1 text-sm text-gray-400">
                                        Courses will appear here once created.
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