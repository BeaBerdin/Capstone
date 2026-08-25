<x-layouts::app :title="__('Create Quiz Result')">
    <div class="mx-auto max-w-4xl space-y-6">

        <div>
            <h1 class="text-3xl font-bold text-black">Create Quiz Result</h1>
            <p class="mt-1 text-sm text-zinc-400">
                Manually record a student quiz score and assessment outcome.
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-6 shadow-lg shadow-purple-950/10">
            <form action="{{ route('teacher.quiz-results.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-zinc-300">
                            Student
                        </label>

                        <select name="student_id"
                            class="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3 text-white outline-none focus:border-purple-500">
                            @foreach($students as $student)
                                <option value="{{ $student->id }}">
                                    {{ $student->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-zinc-300">
                            Quiz
                        </label>

                        <select name="quiz_id"
                            class="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3 text-white outline-none focus:border-purple-500">
                            @foreach($quizzes as $quiz)
                                <option value="{{ $quiz->id }}">
                                    {{ $quiz->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-zinc-300">
                            Score
                        </label>

                        <input type="number"
                            name="score"
                            min="0"
                            placeholder="Example: 8"
                            class="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3 text-white outline-none placeholder:text-zinc-600 focus:border-purple-500">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-zinc-300">
                            Total Items
                        </label>

                        <input type="number"
                            name="total_items"
                            min="1"
                            placeholder="Example: 10"
                            class="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3 text-white outline-none placeholder:text-zinc-600 focus:border-purple-500">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-zinc-300">
                            Attempt Number
                        </label>

                        <input type="number"
                            name="attempt_number"
                            min="1"
                            value="1"
                            class="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3 text-white outline-none focus:border-purple-500">
                    </div>
                </div>

                <div class="rounded-xl border border-purple-800/40 bg-purple-950/30 p-4 text-sm text-purple-200">
                    The system will automatically compute the percentage and mark the result as
                    <span class="font-semibold text-emerald-400">Passed</span>
                    if the score is 75% or above.
                </div>

                <div class="flex flex-wrap justify-end gap-3 border-t border-zinc-800 pt-5">
                    <a href="{{ route('teacher.quiz-results.index') }}"
                        class="rounded-xl border border-zinc-700 px-5 py-3 text-sm font-semibold text-zinc-300 hover:bg-zinc-800">
                        Cancel
                    </a>

                    <button type="submit"
                        class="rounded-xl bg-purple-600 px-5 py-3 text-sm font-semibold text-white hover:bg-purple-700">
                        Save Result
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-layouts::app>