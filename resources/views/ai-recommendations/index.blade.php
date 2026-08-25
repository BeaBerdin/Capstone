<x-layouts::app :title="__('AI Recommendations')">
    <div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black">AI Recommendations</h1>
                <p class="mt-1 text-sm text-gray-400">
                    Review personalized course recommendations generated from student quiz performance.
                </p>
            </div>

            <a href="{{ route('ai-recommendations.create') }}"
               class="rounded-xl bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700">
                Generate Recommendation
            </a>
        </div>

        @php
            $totalRecommendations = $recommendations->count();
            $viewedRecommendations = $recommendations->where('is_viewed', true)->count();
            $unviewedRecommendations = $recommendations->where('is_viewed', false)->count();
            $averageScore = $totalRecommendations > 0
                ? round($recommendations->avg('recommendation_score'), 2)
                : 0;
        @endphp

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
                <p class="text-sm text-gray-400">Total Recommendations</p>
                <h2 class="mt-2 text-3xl font-bold text-white">{{ $totalRecommendations }}</h2>
                <p class="mt-1 text-xs text-gray-400">AI-generated suggestions</p>
            </div>

            <div class="rounded-2xl border border-purple-500/40 bg-neutral-900 p-5">
                <p class="text-sm text-purple-400">Average AI Score</p>
                <h2 class="mt-2 text-3xl font-bold text-purple-400">{{ $averageScore }}%</h2>
                <p class="mt-1 text-xs text-gray-400">Confidence level</p>
            </div>

            <div class="rounded-2xl border border-green-500/40 bg-neutral-900 p-5">
                <p class="text-sm text-green-400">Viewed</p>
                <h2 class="mt-2 text-3xl font-bold text-green-400">{{ $viewedRecommendations }}</h2>
                <p class="mt-1 text-xs text-gray-400">Seen by students</p>
            </div>

            <div class="rounded-2xl border border-yellow-500/40 bg-neutral-900 p-5">
                <p class="text-sm text-yellow-400">Not Viewed</p>
                <h2 class="mt-2 text-3xl font-bold text-yellow-400">{{ $unviewedRecommendations }}</h2>
                <p class="mt-1 text-xs text-gray-400">Pending review</p>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-green-700/40 bg-green-950/40 px-4 py-3 text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-xl border border-red-700/40 bg-red-950/40 px-4 py-3 text-red-300">
                {{ session('error') }}
            </div>
        @endif

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 shadow-lg shadow-purple-950/10">
            <div class="flex flex-col gap-3 border-b border-neutral-700 px-6 py-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-white">Recommendation Records</h2>
                    <p class="text-sm text-gray-400">
                        AI-generated learning suggestions for students.
                    </p>
                </div>

                <div class="rounded-xl border border-purple-500/30 bg-neutral-800 px-4 py-2 text-sm font-semibold text-purple-300">
                    AI Engine Active
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-neutral-800 text-xs uppercase tracking-wider text-white">
                        <tr>
                            <th class="px-6 py-4">Student</th>
                            <th class="px-6 py-4">Recommended Course</th>
                            <th class="px-6 py-4">AI Score</th>
                            <th class="px-6 py-4">Reason</th>
                            <th class="px-6 py-4">Viewed</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-neutral-800">
                        @forelse($recommendations as $recommendation)
                            @php
                                $score = $recommendation->recommendation_score ?? 0;

                                $scoreClass = $score >= 85
                                    ? 'bg-green-500'
                                    : ($score >= 70 ? 'bg-yellow-500' : 'bg-red-500');
                            @endphp

                            <tr class="hover:bg-white/5">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-600/20 text-sm font-bold text-purple-300">
                                            {{ strtoupper(substr($recommendation->student->name ?? 'S', 0, 1)) }}
                                        </div>

                                        <div>
                                            <p class="font-semibold text-white">
                                                {{ $recommendation->student->name ?? 'N/A' }}
                                            </p>
                                            <p class="text-xs text-gray-500">Learner</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <p class="font-semibold text-white">
                                        {{ $recommendation->course->title ?? 'N/A' }}
                                    </p>
                                    <p class="text-xs text-gray-500">Recommended Course</p>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 w-28 rounded-full bg-neutral-700">
                                            <div class="h-2 rounded-full {{ $scoreClass }}"
                                                 style="width: {{ min($score, 100) }}%">
                                            </div>
                                        </div>

                                        <span class="font-semibold text-white">
                                            {{ number_format($score, 2) }}%
                                        </span>
                                    </div>
                                </td>

                                <td class="max-w-xl px-6 py-4 text-gray-400">
                                    {{ $recommendation->reason }}
                                </td>

                                <td class="px-6 py-4">
                                    @if($recommendation->is_viewed)
                                        <span class="rounded-full bg-green-500/15 px-3 py-1 text-xs font-semibold text-green-400">
                                            Viewed
                                        </span>
                                    @else
                                        <span class="rounded-full bg-yellow-500/15 px-3 py-1 text-xs font-semibold text-yellow-400">
                                            Not Viewed
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <form action="{{ route('ai-recommendations.destroy', $recommendation) }}"
                                          method="POST"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')

                                        <button class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700"
                                                onclick="return confirm('Delete recommendation?')">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-purple-600/20 text-2xl">
                                            ✨
                                        </div>

                                        <h3 class="text-lg font-semibold text-white">
                                            No recommendations found
                                        </h3>

                                        <p class="mt-1 text-sm text-gray-400">
                                            Generate recommendations after students have quiz results.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-layouts::app>