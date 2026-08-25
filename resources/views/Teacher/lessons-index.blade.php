<x-layouts::app :title="__('Lessons')">
    <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-bold text-black">Lessons</h1>
            <p class="mt-1 text-sm text-gray-400">
                Manage lessons by selecting a course below.
            </p>
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            @forelse($courses as $course)
                <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-6 shadow-lg shadow-purple-950/10 transition hover:border-purple-500/30">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-600/20 text-lg">
                            📚
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">{{ $course->title }}</h2>
                            <p class="mt-1 text-sm text-gray-400">
                                {{ $course->lessons->count() }} lesson{{ $course->lessons->count() !== 1 ? 's' : '' }}
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('teacher.lessons', $course) }}"
                       class="mt-5 inline-block rounded-xl bg-purple-600 px-5 py-2 text-sm font-semibold text-white hover:bg-purple-700">
                        Manage Lessons →
                    </a>
                </div>
            @empty
                <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-8 text-center">
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-purple-600/20 text-2xl mx-auto">
                        📚
                    </div>
                    <h3 class="text-lg font-semibold text-white">No courses found</h3>
                    <p class="mt-1 text-sm text-gray-400">
                        Create a course first to manage its lessons.
                    </p>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts::app>