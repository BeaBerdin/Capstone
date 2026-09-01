<x-layouts::app :title="__('Edit Question')">

<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold">
            Edit Quiz Question
        </h1>

        <p class="text-sm text-gray-500">
            Update the question, answer choices, correct answer, and point value.
        </p>
    </div>

    <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-neutral-900 dark:border-neutral-700">

        <form action="{{ route('quiz-questions.update', $quiz_question) }}"
              method="POST"
              class="space-y-5">

            @csrf
            @method('PUT')

            <div>
                <label class="block mb-1 text-sm font-medium">
                    Quiz
                </label>

                <select name="quiz_id"
                        class="w-full rounded border border-neutral-700 bg-neutral-900 text-white p-3"
                        required>
                    @foreach($quizzes as $quiz)
                        <option value="{{ $quiz->id }}"
                            @selected(old('quiz_id', $quiz_question->quiz_id) == $quiz->id)>
                            {{ $quiz->course->title ?? 'No Course' }} — {{ $quiz->title }}
                        </option>
                    @endforeach
                </select>

                <p class="mt-1 text-xs text-gray-500">
                    Choose the quiz where this question belongs.
                </p>

                @error('quiz_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block mb-1 text-sm font-medium">
                    Question
                </label>

                <textarea name="question"
                          rows="4"
                          placeholder="Example: What is the basic accounting equation?"
                          class="w-full rounded border border-neutral-700 bg-neutral-900 text-white p-3"
                          required>{{ old('question', $quiz_question->question) }}</textarea>

                @error('question')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">

                <div>
                    <label class="block mb-1 text-sm font-medium">
                        Option A
                    </label>

                    <input type="text"
                           name="option_a"
                           value="{{ old('option_a', $quiz_question->option_a) }}"
                           placeholder="Option A"
                           class="w-full rounded border border-neutral-700 bg-neutral-900 text-white p-3"
                           required>

                    @error('option_a')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium">
                        Option B
                    </label>

                    <input type="text"
                           name="option_b"
                           value="{{ old('option_b', $quiz_question->option_b) }}"
                           placeholder="Option B"
                           class="w-full rounded border border-neutral-700 bg-neutral-900 text-white p-3"
                           required>

                    @error('option_b')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium">
                        Option C
                    </label>

                    <input type="text"
                           name="option_c"
                           value="{{ old('option_c', $quiz_question->option_c) }}"
                           placeholder="Option C"
                           class="w-full rounded border border-neutral-700 bg-neutral-900 text-white p-3">

                    @error('option_c')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium">
                        Option D
                    </label>

                    <input type="text"
                           name="option_d"
                           value="{{ old('option_d', $quiz_question->option_d) }}"
                           placeholder="Option D"
                           class="w-full rounded border border-neutral-700 bg-neutral-900 text-white p-3">

                    @error('option_d')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            <div class="grid gap-4 md:grid-cols-2">

                <div>
                    <label class="block mb-1 text-sm font-medium">
                        Correct Answer
                    </label>

                    <select name="correct_answer"
                            class="w-full rounded border border-neutral-700 bg-neutral-900 text-white p-3"
                            required>
                        <option value="A" @selected(old('correct_answer', $quiz_question->correct_answer) == 'A')>Option A</option>
                        <option value="B" @selected(old('correct_answer', $quiz_question->correct_answer) == 'B')>Option B</option>
                        <option value="C" @selected(old('correct_answer', $quiz_question->correct_answer) == 'C')>Option C</option>
                        <option value="D" @selected(old('correct_answer', $quiz_question->correct_answer) == 'D')>Option D</option>
                    </select>

                    <p class="mt-1 text-xs text-gray-500">
                        Select the option that contains the correct answer.
                    </p>

                    @error('correct_answer')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium">
                        Points
                    </label>

                    <input type="number"
                           name="points"
                           value="{{ old('points', $quiz_question->points) }}"
                           min="1"
                           class="w-full rounded border border-neutral-700 bg-neutral-900 text-white p-3"
                           required>

                    <p class="mt-1 text-xs text-gray-500">
                        Usually 1 point per question.
                    </p>

                    @error('points')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    Update Question
                </button>

                <a href="{{ route('quiz-questions.index') }}"
                   class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                    Cancel
                </a>
            </div>

        </form>

    </div>

</div>

</x-layouts::app>