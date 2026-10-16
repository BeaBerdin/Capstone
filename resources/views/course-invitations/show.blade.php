<x-layouts::app :title="__('Course Invitation')">

    <div class="flex min-h-[70vh] items-center justify-center px-4 py-12">

        <div class="w-full max-w-xl">

            @if (session('error'))
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                    {{ session('error') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">

                <div class="border-b border-gray-200 bg-violet-50 px-6 py-8 text-center dark:border-zinc-700 dark:bg-violet-950/20">

                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-300">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="h-7 w-7"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M13.5 6.75h3.75A2.25 2.25 0 0 1 19.5 9v6a2.25 2.25 0 0 1-2.25 2.25h-3.75m-3-10.5H6.75A2.25 2.25 0 0 0 4.5 9v6a2.25 2.25 0 0 0 2.25 2.25h3.75m-3.75-3h9m0 0-3-3m3 3-3 3"
                            />
                        </svg>
                    </div>

                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Course Invitation
                    </h1>

                    <p class="mt-2 text-sm text-gray-600 dark:text-zinc-400">
                        You have been invited to join this course.
                    </p>

                </div>

                <div class="px-6 py-8">

                    <div class="mb-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-zinc-400">
                            Course
                        </p>

                        <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">
                            {{ $invitation->course->title ?? 'Course unavailable' }}
                        </h2>
                    </div>

                    <div class="mb-6 rounded-lg bg-gray-50 p-4 dark:bg-zinc-900">

                        <div class="flex items-center justify-between gap-4">

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-zinc-400">
                                    Invitation Code
                                </p>

                                <p class="mt-1 font-mono text-lg font-bold tracking-wider text-violet-600 dark:text-violet-300">
                                    {{ $invitation->code }}
                                </p>
                            </div>

                            @if ($invitation->isAccepted())

                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                    Accepted
                                </span>

                            @elseif ($invitation->isExpired())

                                <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300">
                                    Expired
                                </span>

                            @else

                                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-green-300">
                                    Active
                                </span>

                            @endif

                        </div>

                    </div>

                    @if ($invitation->expires_at)

                        <p class="mb-6 text-sm text-gray-500 dark:text-zinc-400">
                            This invitation expires on
                            <span class="font-medium text-gray-700 dark:text-zinc-300">
                                {{ $invitation->expires_at->format('M d, Y h:i A') }}
                            </span>.
                        </p>

                    @endif

                    @if ($invitation->isValid())

                        @if ((float) ($invitation->course->price ?? 0) > 0)

                            <div class="mb-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                                <p class="font-semibold">
                                    This is a paid course.
                                </p>

                                <p class="mt-1">
                                    Payment is required before you can access this course.
                                </p>

                                <p class="mt-2 font-semibold">
                                    Course Price:
                                    ₱{{ number_format((float) $invitation->course->price, 2) }}
                                </p>
                            </div>

                            <form
    method="POST"
    action="{{ route('student.transactions.store', ['course' => $invitation->course->id]) }}"
>
    @csrf

    <input
        type="hidden"
        name="course_invitation_code"
        value="{{ $invitation->code }}"
    >

    <button
        type="submit"
        class="w-full rounded-lg bg-violet-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2"
    >
        View Course & Purchase
    </button>
</form>
                        @else

                            <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-300">
                                This is a free course. You can join immediately.
                            </div>

                            <form
                                method="POST"
                                action="{{ route('course-invitations.accept', $invitation->code) }}"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="w-full rounded-lg bg-violet-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2"
                                >
                                    Accept Invitation & Join Course
                                </button>
                            </form>

                        @endif

                    @elseif ($invitation->isAccepted())

                        <div class="rounded-lg bg-green-50 px-4 py-3 text-center text-sm text-green-700 dark:bg-green-900/20 dark:text-green-300">
                            This invitation has already been accepted.
                        </div>

                    @else

                        <div class="rounded-lg bg-red-50 px-4 py-3 text-center text-sm text-red-700 dark:bg-red-900/20 dark:text-red-300">
                            This invitation has expired and can no longer be used.
                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>

</x-layouts::app>
