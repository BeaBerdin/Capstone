<x-layouts::app :title="'My Courses'">

<div class="space-y-6">

    <div>
        <h1 class="text-3xl font-bold text-black">
            My Courses
        </h1>
        <p class="text-sm text-gray-400">
            Continue your learning journey with PathWise.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-700/40 bg-green-950/40 px-4 py-3 text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @forelse($enrollments as $enrollment)

        @php
            $progress = $enrollment->progress_percentage;
            $isCompleted = $progress >= 100;
        @endphp

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-6 shadow-lg shadow-purple-950/10">

            <div class="flex justify-between items-start">

                <div>
                    <h2 class="text-xl font-bold text-white">
                        {{ $enrollment->course->title }}
                    </h2>

                    <p class="text-sm text-gray-400 mt-1">
                        Status:
                        <span class="font-semibold text-gray-300">
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
                <div class="w-full bg-neutral-700 rounded-full h-3">
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
                       class="inline-block rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-700">
                        Review Course
                    </a>
                @else
                    <a href="{{ route('student.learn.course', $enrollment->course) }}"
                       class="inline-block rounded-xl bg-linear-to-r from-purple-500 to-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                        Continue Learning →
                    </a>
                @endif
            </div>

        </div>

    @empty

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-8 text-center">

            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-purple-600/20 text-2xl mx-auto">
                📚
            </div>

            <h2 class="text-xl font-semibold text-white">
                No Enrolled Courses Yet
            </h2>

            <p class="text-gray-400 mt-2">
                Browse the marketplace and enroll in your first course.
            </p>

            <a href="{{ route('student.marketplace') }}"
               class="inline-block mt-4 rounded-xl bg-linear-to-r from-purple-500 to-indigo-600 px-5 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                Browse Courses →
            </a>

        </div>

    @endforelse

</div>

</x-layouts::app>