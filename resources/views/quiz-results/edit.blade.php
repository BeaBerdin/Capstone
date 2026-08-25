<x-layouts::app :title="__('Edit Quiz Result')">
    <div class="mx-auto max-w-4xl space-y-6">

        <div>
            <h1 class="text-3xl font-bold text-black">Edit Quiz Result</h1>
            <p class="mt-1 text-sm text-zinc-400">
                Update the student’s quiz score and attempt details.
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-6 shadow-lg shadow-purple-950/10">
            <form action="{{ route('teacher.quiz-results.update', $quiz_result) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-zinc-300">
                            Student
                        </label>

                        <select name="student_id"
                            class="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3 text-white outline-none focus:border-purple-500">
                            @foreach($students as $student)
                                <option value="{{ $student->id }}"
                                    {{ $quiz_result->student_id == $student->id ? 'selected' : '' }}>
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
                                <option value="{{ $quiz->id }}"
                                    {{ $quiz_result->quiz_id == $quiz->id ? 'selected' : '' }}>
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
                            value="{{ $quiz_result->score }}"
                            class="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3 text-white outline-none focus:border-purple-500">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-zinc-300">
                            Total Items
                        </label>

                        <input type="number"
                            name="total_items"
                            min="1"
                            value="{{ $quiz_result->total_items }}"
                            class="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3 text-white outline-none focus:border-purple-500">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-zinc-300">
                            Attempt Number
                        </label>

                        <input type="number"
                            name="attempt_number"
                            min="1"
                            value="{{ $quiz_result->attempt_number }}"
                            class="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3 text-white outline-none focus:border-purple-500">
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-3 border-t border-zinc-800 pt-5">
                    <a href="{{ route('teacher.quiz-results.index') }}"
                        class="rounded-xl border border-zinc-700 px-5 py-3 text-sm font-semibold text-zinc-300 hover:bg-zinc-800">
                        Cancel
                    </a>

                    <button type="submit"
                        class="rounded-xl bg-purple-600 px-5 py-3 text-sm font-semibold text-white hover:bg-purple-700">
                        Update Result
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-layouts::app>