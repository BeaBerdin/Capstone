<x-layouts::app :title="'Recommended Courses'">

{{-- ✅ GI-ADD: Dark background wrapper para sa whole page --}}
<div class="min-h-screen bg-neutral-900 text-white">

    <div class="space-y-6 p-6">

        <div>
            <h1 class="text-3xl font-bold text-white">
                AI Recommended Courses
            </h1>
            <p class="text-sm text-gray-400">
                Personalized learning recommendations based on your performance.
            </p>
        </div>

        @forelse($recommendations as $recommendation)

            <div class="overflow-hidden rounded-2xl border border-purple-500/20 bg-linear-to-br from-purple-900/40 to-neutral-900 p-6 backdrop-blur shadow-lg shadow-purple-950/20">

                <div class="flex items-start justify-between gap-4">

                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-purple-500/30 to-indigo-500/20 text-purple-300">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16 2.5 6.5L22 12l-6.5 2.5L13 21l-2.5-6.5L4 12l6.5-2.5L13 3Z" />
                            </svg>
                        </div>

                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wide text-purple-400">
                                Recommended for you
                            </span>
                            <h2 class="text-xl font-bold text-white">
                                {{ $recommendation->course->title }}
                            </h2>
                        </div>
                    </div>

                    <span class="shrink-0 rounded-full border border-purple-500/30 bg-purple-500/10 px-3 py-1 text-sm font-semibold text-purple-300">
                        {{ number_format($recommendation->recommendation_score, 0) }}%
                    </span>

                </div>

                <p class="mt-4 leading-relaxed text-gray-300">
                    {{ $recommendation->reason }}
                </p>

                <a href="{{ route('student.course.show', $recommendation->course) }}"
                   class="mt-5 inline-flex items-center gap-2 rounded-xl bg-linear-to-r from-purple-500 to-indigo-600 px-5 py-2 text-sm font-semibold text-white transition hover:opacity-90 shadow-lg shadow-purple-500/20">
                    View Course →
                </a>

            </div>

        @empty

            <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-purple-500/20 bg-neutral-800 px-6 py-16 text-center">
                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-purple-500/20 text-3xl">
                    ✨
                </div>
                <h2 class="text-lg font-semibold text-white">
                    No recommendations yet
                </h2>
                <p class="mt-2 max-w-md text-sm text-gray-400">
                    Complete a quiz in one of your courses and PathWise AI will analyze your performance to recommend the perfect next course for you.
                </p>
                <a href="{{ route('student.my-courses') }}"
                   class="mt-5 inline-flex items-center gap-2 rounded-xl bg-linear-to-r from-purple-500 to-indigo-600 px-5 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                    Go to My Courses →
                </a>
            </div>

        @endforelse

    </div>

</div>

</x-layouts::app>