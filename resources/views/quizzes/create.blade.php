<x-layouts::app :title="'Create Quiz'">

<div class="space-y-6">

    {{-- Header --}}
    <div class="rounded-2xl border border-purple-500/30 bg-linear-to-r from-purple-900 via-neutral-900 to-neutral-900 p-6 shadow-lg">
        <h1 class="text-3xl font-bold text-white">
            Create Quiz
        </h1>

        <p class="mt-2 text-sm text-purple-200">
            Create an assessment for a course. Students can take this quiz after completing the lessons.
        </p>
    </div>

    {{-- Form Card --}}
    <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-6 shadow-lg">

        <form action="{{ route('quizzes.store') }}"
              method="POST"
              class="space-y-5">

            @csrf

            {{-- Course --}}
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-300">
                    Course
                </label>

                <select name="course_id"
                        class="w-full rounded-xl border border-neutral-700 bg-neutral-800 p-3 text-white outline-none transition focus:border-purple-500"
                        required>

                    <option value="">
                        Select the course for this quiz
                    </option>

                    @foreach($courses as $course)
                        <option value="{{ $course->id }}"
                                @selected(old('course_id') == $course->id)>
                            {{ $course->title }}
                        </option>
                    @endforeach

                </select>

                <p class="mt-2 text-xs text-gray-500">
                    Select the course where this quiz belongs.
                </p>

                @error('course_id')
                    <p class="mt-2 text-sm text-red-400">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Lesson --}}
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-300">
                    Related Lesson
                </label>

                <select name="lesson_id"
                        class="w-full rounded-xl border border-neutral-700 bg-neutral-800 p-3 text-white outline-none transition focus:border-purple-500">

                    <option value="">
                        No Lesson (Final Course Quiz)
                    </option>

                    @foreach($lessons as $lesson)
                        <option value="{{ $lesson->id }}"
                                @selected(old('lesson_id') == $lesson->id)>
                            {{ $lesson->course->title ?? 'No Course' }} — {{ $lesson->title }}
                        </option>
                    @endforeach

                </select>

                <p class="mt-2 text-xs text-gray-500">
                    Leave empty if this is the final assessment for the entire course.
                </p>

                @error('lesson_id')
                    <p class="mt-2 text-sm text-red-400">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Quiz Title --}}
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-300">
                    Quiz Title
                </label>

                <input type="text"
                       name="title"
                       value="{{ old('title') }}"
                       placeholder="Example: Entrepreneurship Final Quiz"
                       class="w-full rounded-xl border border-neutral-700 bg-neutral-800 p-3 text-white outline-none transition placeholder:text-gray-500 focus:border-purple-500"
                       required>

                @error('title')
                    <p class="mt-2 text-sm text-red-400">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-300">
                    Quiz Description
                </label>

                <textarea
                    name="description"
                    rows="5"
                    placeholder="Briefly describe what this quiz covers..."
                    class="w-full rounded-xl border border-neutral-700 bg-neutral-800 p-3 text-white outline-none transition placeholder:text-gray-500 focus:border-purple-500">{{ old('description') }}</textarea>

                @error('description')
                    <p class="mt-2 text-sm text-red-400">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Passing Score + Time --}}
            <div class="grid gap-4 md:grid-cols-2">

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-300">
                        Passing Score (%)
                    </label>

                    <input type="number"
                           name="passing_score"
                           value="{{ old('passing_score',75) }}"
                           min="1"
                           max="100"
                           class="w-full rounded-xl border border-neutral-700 bg-neutral-800 p-3 text-white outline-none transition focus:border-purple-500">

                    @error('passing_score')
                        <p class="mt-2 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-300">
                        Time Limit (Minutes)
                    </label>

                    <input type="number"
                           name="time_limit_minutes"
                           value="{{ old('time_limit_minutes',15) }}"
                           min="1"
                           placeholder="Optional"
                           class="w-full rounded-xl border border-neutral-700 bg-neutral-800 p-3 text-white outline-none transition focus:border-purple-500">

                    @error('time_limit_minutes')
                        <p class="mt-2 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

            {{-- Published --}}
            <div class="rounded-xl border border-neutral-700 bg-neutral-800/50 p-4">

                <label class="flex items-start gap-3">

                    <input type="checkbox"
                           name="is_published"
                           class="mt-1 accent-purple-600"
                           checked>

                    <span>

                        <span class="block font-medium text-gray-200">
                            Published
                        </span>

                        <span class="text-sm text-gray-400">
                            Make this quiz available for students after completing the required lessons.
                        </span>

                    </span>

                </label>

            </div>

            {{-- Buttons --}}
            <div class="flex gap-3 pt-2">

                <button type="submit"
                        class="rounded-xl bg-purple-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-500">
                    Save Quiz
                </button>

                <a href="{{ route('quizzes.index') }}"
                   class="rounded-xl border border-neutral-700 bg-neutral-800 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-neutral-700">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</x-layouts::app>