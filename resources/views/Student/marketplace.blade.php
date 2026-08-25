<x-layouts::app :title="'Course Marketplace'">

<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-black">
            Course Marketplace
        </h1>
        <p class="text-sm text-gray-400">
            Browse available courses and start learning with PathWise.
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        @forelse($courses as $course)
            <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5 shadow-lg shadow-purple-950/10 transition hover:border-purple-500/30">
                <p class="text-xs uppercase tracking-wide text-purple-400">
                    {{ $course->category->name ?? 'No Category' }}
                </p>

                <h2 class="mt-2 text-lg font-bold text-white">
                    {{ $course->title }}
                </h2>

                <p class="mt-2 text-sm text-gray-400">
                    {{ Str::limit($course->description, 100) }}
                </p>

                <div class="mt-4 space-y-1 text-sm text-gray-400">
                    <p>Level: <span class="text-gray-300">{{ ucfirst($course->difficulty_level) }}</span></p>
                    <p>Hours: <span class="text-gray-300">{{ $course->estimated_hours ?? 'N/A' }}</span></p>
                    <p>Price: <span class="font-semibold text-white">₱{{ number_format($course->price, 2) }}</span></p>
                </div>

                <a href="{{ route('student.course.show', $course) }}"
                   class="mt-4 inline-block rounded-xl bg-purple-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-purple-700">
                    View Course →
                </a>
            </div>
        @empty
            <div class="col-span-3 rounded-2xl border border-neutral-700 bg-neutral-900 p-8 text-center">
                <p class="text-sm text-gray-400">
                    No published courses available.
                </p>
                <p class="mt-1 text-xs text-gray-500">
                    Check back later for new courses.
                </p>
            </div>
        @endforelse
    </div>
</div>

</x-layouts::app>