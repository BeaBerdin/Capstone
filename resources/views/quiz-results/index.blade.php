<x-layouts::app :title="__('Quiz Results')">
    <div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black">Quiz Results</h1>
                <p class="mt-1 text-sm text-gray-400">
                    Monitor student quiz performance and assessment outcomes.
                </p>
            </div>

            <a href="{{ route('teacher.quiz-results.create') }}"
               class="rounded-xl bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700">
                Add Result
            </a>
        </div>

        @php
            $totalResults = $results->count();
            $passedResults = $results->where('remarks', 'passed')->count();
            $failedResults = $results->where('remarks', 'failed')->count();
            $averagePercentage = $totalResults > 0 ? $results->avg('percentage') : 0;
        @endphp

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
                <p class="text-sm text-gray-400">Total Results</p>
                <h2 class="mt-2 text-3xl font-bold text-white">{{ $totalResults }}</h2>
                <p class="mt-1 text-xs text-gray-400">All submissions</p>
            </div>

            <div class="rounded-2xl border border-green-500/40 bg-neutral-900 p-5">
                <p class="text-sm text-green-400">Passed</p>
                <h2 class="mt-2 text-3xl font-bold text-green-400">{{ $passedResults }}</h2>
                <p class="mt-1 text-xs text-gray-400">Successful attempts</p>
            </div>

            <div class="rounded-2xl border border-red-500/40 bg-neutral-900 p-5">
                <p class="text-sm text-red-400">Failed</p>
                <h2 class="mt-2 text-3xl font-bold text-red-400">{{ $failedResults }}</h2>
                <p class="mt-1 text-xs text-gray-400">Needs improvement</p>
            </div>

            <div class="rounded-2xl border border-purple-500/40 bg-neutral-900 p-5">
                <p class="text-sm text-purple-400">Average Score</p>
                <h2 class="mt-2 text-3xl font-bold text-purple-400">
                    {{ number_format($averagePercentage, 2) }}%
                </h2>
                <p class="mt-1 text-xs text-gray-400">Across all students</p>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-green-700/40 bg-green-950/40 px-4 py-3 text-green-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 shadow-lg shadow-purple-950/10">
            <div class="flex items-center justify-between border-b border-neutral-700 px-6 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-white">Assessment Records</h2>
                    <p class="text-sm text-gray-400">Latest quiz attempts submitted by students.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-neutral-800 text-xs uppercase tracking-wider text-white">
                        <tr>
                            <th class="px-6 py-4">Student</th>
                            <th class="px-6 py-4">Quiz</th>
                            <th class="px-6 py-4">Score</th>
                            <th class="px-6 py-4">Performance</th>
                            <th class="px-6 py-4">Remarks</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-neutral-800">
                        @forelse($results as $result)
                            @php
                                $percentage = $result->percentage ?? 0;
                                $isPassed = strtolower($result->remarks) === 'passed';
                            @endphp

                            <tr class="hover:bg-white/5">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-purple-600/20 text-sm font-bold text-purple-300">
                                            {{ strtoupper(substr($result->student->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-white">{{ $result->student->name }}</p>
                                            <p class="text-xs text-gray-500">Student</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-gray-400">
                                    {{ $result->quiz->title }}
                                </td>

                                <td class="px-6 py-4">
                                    <span class="font-semibold text-white">
                                        {{ $result->score }}/{{ $result->total_items }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 w-32 rounded-full bg-neutral-700">
                                            <div class="h-2 rounded-full {{ $isPassed ? 'bg-green-500' : 'bg-red-500' }}"
                                                 style="width: {{ min($percentage, 100) }}%">
                                            </div>
                                        </div>
                                        <span class="text-sm font-semibold text-white">
                                            {{ number_format($percentage, 2) }}%
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    @if($isPassed)
                                        <span class="rounded-full bg-green-500/15 px-3 py-1 text-xs font-semibold text-green-400">
                                            Passed
                                        </span>
                                    @else
                                        <span class="rounded-full bg-red-500/15 px-3 py-1 text-xs font-semibold text-red-400">
                                            Failed
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('teacher.quiz-results.edit', $result) }}"
                                           class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                                            Edit
                                        </a>

                                        <form action="{{ route('teacher.quiz-results.destroy', $result) }}"
                                              method="POST"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')

                                            <button onclick="return confirm('Delete this result?')"
                                                    class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-purple-600/20 text-2xl">
                                            📊
                                        </div>
                                        <h3 class="text-lg font-semibold text-white">No quiz results yet</h3>
                                        <p class="mt-1 text-sm text-gray-400">
                                            Quiz results will appear here once students submit their quizzes.
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