<x-layouts::app :title="__('Quizzes')">
    <div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black">Quizzes</h1>
                <p class="mt-1 text-sm text-gray-400">
                    Manage course assessments, passing scores, and question coverage.
                </p>
            </div>

            <a href="{{ route('quizzes.create') }}"
               class="rounded-xl bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700">
                Add Quiz
            </a>
        </div>

        @php
            $totalQuizzes = $quizzes->count();
            $publishedQuizzes = $quizzes->where('is_published', true)->count();
            $totalQuestions = $quizzes->sum(fn ($quiz) => $quiz->questions->count());
            $averagePassingScore = $totalQuizzes > 0 ? round($quizzes->avg('passing_score'), 2) : 0;
        @endphp

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
                <p class="text-sm text-gray-400">Total Quizzes</p>
                <h2 class="mt-2 text-3xl font-bold text-white">{{ $totalQuizzes }}</h2>
                <p class="mt-1 text-xs text-gray-400">All assessments created</p>
            </div>

            <div class="rounded-2xl border border-green-500/40 bg-neutral-900 p-5">
                <p class="text-sm text-green-400">Published</p>
                <h2 class="mt-2 text-3xl font-bold text-green-400">{{ $publishedQuizzes }}</h2>
                <p class="mt-1 text-xs text-gray-400">Live assessments</p>
            </div>

            <div class="rounded-2xl border border-purple-500/40 bg-neutral-900 p-5">
                <p class="text-sm text-purple-400">Question Bank</p>
                <h2 class="mt-2 text-3xl font-bold text-purple-400">{{ $totalQuestions }}</h2>
                <p class="mt-1 text-xs text-gray-400">Total questions</p>
            </div>

            <div class="rounded-2xl border border-blue-500/40 bg-neutral-900 p-5">
                <p class="text-sm text-blue-400">Avg. Passing Score</p>
                <h2 class="mt-2 text-3xl font-bold text-blue-400">{{ $averagePassingScore }}%</h2>
                <p class="mt-1 text-xs text-gray-400">Across all quizzes</p>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-green-700/40 bg-green-950/40 px-4 py-3 text-green-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 shadow-lg shadow-purple-950/10">
            <div class="flex flex-col gap-3 border-b border-neutral-700 px-6 py-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-white">Assessment Directory</h2>
                    <p class="text-sm text-gray-400">
                        All quizzes connected to PathWise courses.
                    </p>
                </div>

                <div class="rounded-xl border border-purple-500/30 bg-neutral-800 px-4 py-2 text-sm font-semibold text-purple-300">
                    {{ $totalQuestions }} total questions
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-neutral-800 text-xs uppercase tracking-wider text-white">
                        <tr>
                            <th class="px-6 py-4">Course</th>
                            <th class="px-6 py-4">Quiz</th>
                            <th class="px-6 py-4">Questions</th>
                            <th class="px-6 py-4">Passing Score</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-neutral-800">
                        @forelse($quizzes as $quiz)
                            @php
                                $questionCount = $quiz->questions->count();
                            @endphp

                            <tr class="hover:bg-white/5">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-linear-to-br from-purple-600 to-indigo-600 text-sm font-bold text-white">
                                            {{ strtoupper(substr($quiz->course->title ?? 'C', 0, 1)) }}
                                        </div>

                                        <div>
                                            <p class="font-semibold text-white">
                                                {{ $quiz->course->title ?? 'No Course' }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                Course assessment
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <p class="font-semibold text-white">
                                        {{ $quiz->title }}
                                    </p>

                                    @if($quiz->description)
                                        <p class="mt-1 max-w-md truncate text-xs text-gray-400">
                                            {{ $quiz->description }}
                                        </p>
                                    @else
                                        <p class="mt-1 text-xs text-gray-500">
                                            Final assessment
                                        </p>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    <span class="rounded-full bg-purple-500/15 px-3 py-1 text-xs font-semibold text-purple-400">
                                        {{ $questionCount }} question{{ $questionCount !== 1 ? 's' : '' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 w-24 rounded-full bg-neutral-700">
                                            <div class="h-2 rounded-full bg-green-500"
                                                 style="width: {{ min($quiz->passing_score, 100) }}%">
                                            </div>
                                        </div>

                                        <span class="font-semibold text-white">
                                            {{ $quiz->passing_score }}%
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    @if($quiz->is_published)
                                        <span class="rounded-full bg-green-500/15 px-3 py-1 text-xs font-semibold text-green-400">
                                            Published
                                        </span>
                                    @else
                                        <span class="rounded-full bg-gray-500/15 px-3 py-1 text-xs font-semibold text-gray-400">
                                            Draft
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('quizzes.edit', $quiz) }}"
                                           class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                                            Edit
                                        </a>

                                        <form action="{{ route('quizzes.destroy', $quiz) }}"
                                              method="POST">
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                onclick="return confirm('Delete this quiz?')"
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
                                    <h3 class="text-lg font-semibold text-white">
                                        No quizzes found
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-400">
                                        Create your first course assessment to start tracking learner performance.
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