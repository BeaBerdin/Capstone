<x-layouts::app :title="'My Courses'">

<div class="space-y-6">

    <div>
        <h1 class="text-3xl font-bold text-black">
            My Courses
        </h1>

        <p class="text-black">
            Continue your learning journey with PathWise.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-600 text-white p-4">
            {{ session('success') }}
        </div>
    @endif

    @forelse($enrollments as $enrollment)

        @php
            $progress = $enrollment->progress_percentage;
            $isCompleted = $progress >= 100;
        @endphp

        <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-neutral-900 dark:border-neutral-700">

            <div class="flex justify-between items-start">

                <div>
                    <h2 class="text-xl font-bold">
                        {{ $enrollment->course->title }}
                    </h2>

                    <p class="text-sm text-gray-500 mt-1">
                        Status:
                        <span class="font-semibold">
                            {{ ucfirst($enrollment->status) }}
                        </span>
                    </p>
                </div>

                <span class="text-sm px-3 py-1 rounded-full font-semibold
                    {{ $isCompleted
                        ? 'bg-green-500/15 text-green-400'
                        : 'bg-purple-500/15 text-purple-300' }}">
                    {{ number_format($progress, 0) }}%
                </span>

            </div>

            <div class="mt-4">

                <div class="w-full bg-neutral-200 dark:bg-neutral-800 rounded-full h-3">

                    <div
                        class="h-3 rounded-full transition-all
                            {{ $isCompleted
                                ? 'bg-green-500'
                                : 'bg-linear-to-r from-purple-500 to-indigo-500' }}"
                        style="width: {{ $progress }}%">
                    </div>

                </div>

            </div>

            <div class="mt-4 text-sm text-gray-400">

                Enrolled:
                {{ optional($enrollment->enrolled_at)->format('M d, Y') ?? 'N/A' }}

            </div>

            <div class="mt-5">

                @if($isCompleted)
                    <a href="{{ route('student.learn.course', $enrollment->course) }}"
                       class="inline-block rounded-lg bg-green-600 px-4 py-2 text-white transition hover:bg-green-700">
                        Review Course
                    </a>
                @else
                    <a href="{{ route('student.learn.course', $enrollment->course) }}"
                       class="inline-block rounded-lg bg-linear-to-r from-purple-500 to-indigo-600 px-4 py-2 text-white transition hover:opacity-90">
                        Continue Learning
                    </a>
                @endif

            </div>

        </div>

    @empty

        <div class="rounded-xl border p-8 text-center dark:border-neutral-700">

            <h2 class="text-xl font-semibold">
                No Enrolled Courses Yet
            </h2>

            <p class="text-gray-500 mt-2">
                Browse the marketplace and enroll in your first course.
            </p>

            <a href="{{ route('student.marketplace') }}"
               class="inline-block mt-4 rounded-lg bg-linear-to-r from-purple-500 to-indigo-600 px-5 py-2 text-white transition hover:opacity-90">
                Browse Courses
            </a>

        </div>

    @endforelse

</div>

</x-layouts::app>