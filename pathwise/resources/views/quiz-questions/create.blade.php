<x-layouts::app :title="__('Create Question')">

<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold">
            Create Quiz Question
        </h1>

        <p class="text-sm text-gray-500">
            Add a multiple-choice question for a quiz. Students will answer this during the assessment.
        </p>
    </div>

    <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-neutral-900 dark:border-neutral-700">

        <form action="{{ route('quiz-questions.store') }}"
              method="POST"
              class="space-y-5">

            @csrf

            <div>
                <label class="block mb-1 text-sm font-medium">
                    Quiz
                </label>

                <select name="quiz_id"
                        class="w-full rounded border border-neutral-700 bg-neutral-900 text-white p-3"
                        required>
                    <option value="">
                        Select quiz
                    </option>

                    @foreach($quizzes as $quiz)
                        <option value="{{ $quiz->id }}" @selected(old('quiz_id') == $quiz->id)>
                            {{ $quiz->course->title ?? 'No Course' }} — {{ $quiz->title }}
                        </option>
                    @endforeach
                </select>

                <p class="mt-1 text-xs text-gray-500">
                    Choose the quiz where this question will be included.
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
                          class="w-full rounded border p-3 bg-transparent"
                          required>{{ old('question') }}</textarea>

                <p class="mt-1 text-xs text-gray-500">
                    Write the question clearly and avoid confusing wording.
                </p>

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
                           value="{{ old('option_a') }}"
                           placeholder="Example: Assets = Liabilities + Equity"
                           class="w-full rounded border p-3 bg-transparent"
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
                           value="{{ old('option_b') }}"
                           placeholder="Example: Assets = Revenue - Expenses"
                           class="w-full rounded border p-3 bg-transparent"
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
                           value="{{ old('option_c') }}"
                           placeholder="Example: Cash = Income"
                           class="w-full rounded border p-3 bg-transparent">

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
                           value="{{ old('option_d') }}"
                           placeholder="Example: Revenue = Equity"
                           class="w-full rounded border p-3 bg-transparent">

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
                        <option value="">
                            Select correct answer
                        </option>
                        <option value="A" @selected(old('correct_answer') == 'A')>A</option>
                        <option value="B" @selected(old('correct_answer') == 'B')>B</option>
                        <option value="C" @selected(old('correct_answer') == 'C')>C</option>
                        <option value="D" @selected(old('correct_answer') == 'D')>D</option>
                    </select>

                    <p class="mt-1 text-xs text-gray-500">
                        Select the option letter that contains the correct answer.
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
                           value="{{ old('points', 1) }}"
                           min="1"
                           class="w-full rounded border p-3 bg-transparent"
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
                    Save Question
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