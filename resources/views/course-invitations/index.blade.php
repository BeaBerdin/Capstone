<x-layouts::app :title="__('Student Invitations')">
    <div class="min-h-screen bg-gray-50 dark:bg-zinc-900">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Student Invitations
                    </h1>

                    <p class="mt-1 text-sm text-gray-600 dark:text-zinc-400">
                        Create invitation links and allow students to join your courses.
                    </p>
                </div>
            </div>

            {{-- Success message --}}
            @if (session('success'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Error message --}}
            @if (session('error'))
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Create invitation --}}
            <div class="mb-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Create Invitation
                    </h2>

                    <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">
                        Generate a unique invitation code for one of your courses.
                    </p>
                </div>

                <form method="POST" action="{{ route('course-invitations.store') }}">
                    @csrf

                    <div class="grid gap-5 md:grid-cols-2">
                        {{-- Course --}}
                        <div>
                            <label
                                for="course_id"
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-zinc-300"
                            >
                                Course
                            </label>

                            <select
                                id="course_id"
                                name="course_id"
                                required
                                class="block w-full rounded-lg border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                            >
                                <option value="">Select a course</option>

                                @foreach ($courses as $course)
                                    <option
                                        value="{{ $course->id }}"
                                        @selected(old('course_id') == $course->id)
                                    >
                                        {{ $course->title }}
                                    </option>
                                @endforeach
                            </select>

                            @error('course_id')
                                <p class="mt-1 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Expiration --}}
                        <div>
                            <label
                                for="expires_at"
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-zinc-300"
                            >
                                Expiration
                            </label>

                            <input
                                id="expires_at"
                                type="datetime-local"
                                name="expires_at"
                                value="{{ old('expires_at') }}"
                                class="block w-full rounded-lg border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                            >

                            <p class="mt-1 text-xs text-gray-500 dark:text-zinc-400">
                                Leave blank if the invitation should not expire.
                            </p>

                            @error('expires_at')
                                <p class="mt-1 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-5">
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2"
                        >
                            Generate Invitation
                        </button>
                    </div>
                </form>
            </div>

            {{-- Invitation list --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-gray-200 px-6 py-5 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Your Invitations
                    </h2>
                </div>

                @if ($invitations->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <p class="text-sm text-gray-500 dark:text-zinc-400">
                            You have not created any course invitations yet.
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                            <thead class="bg-gray-50 dark:bg-zinc-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400">
                                        Course
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400">
                                        Invitation Code
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400">
                                        Status
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400">
                                        Expires
                                    </th>

                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400">
                                        Actions
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                @foreach ($invitations as $invitation)
                                    <tr>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $invitation->course->title ?? 'Course unavailable' }}
                                            </div>
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4">
                                            <code class="rounded bg-gray-100 px-2 py-1 text-sm font-semibold text-violet-700 dark:bg-zinc-700 dark:text-violet-300">
                                                {{ $invitation->code }}
                                            </code>
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4">
                                            @if ($invitation->isAccepted())
                                                <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                                    Accepted
                                                </span>
                                            @elseif ($invitation->isExpired())
                                                <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300">
                                                    Expired
                                                </span>
                                            @else
                                                <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                                    Active
                                                </span>
                                            @endif
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-zinc-400">
                                           {{ $invitation->expires_at?->timezone('Asia/Manila')->format('M d, Y h:i A') ?? 'Never' }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">

                                                <form
                                                    method="POST"
                                                    action="{{ route('course-invitations.send', $invitation) }}"
                                                    class="flex items-center gap-2"
                                                >
                                                    @csrf

                                                    <select
                                                        name="student_id"
                                                        required
                                                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-700 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200"
                                                    >
                                                        <option value="">Select student</option>

                                                        @foreach($students as $student)
                                                            <option value="{{ $student->id }}">
                                                                {{ $student->name }} — {{ $student->email }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                    <button
                                                        type="submit"
                                                        class="rounded-lg bg-purple-600 px-3 py-2 text-xs font-medium text-white hover:bg-purple-700"
                                                    >
                                                        Send Invitation
                                                    </button>
                                                </form>

                                                <form
                                                    method="POST"
                                                    action="{{ route('course-invitations.destroy', $invitation) }}"
                                                    onsubmit="return confirm('Delete this invitation?')"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="rounded-lg border border-red-300 px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-950/30"
                                                    >
                                                        Delete
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-layouts::app>