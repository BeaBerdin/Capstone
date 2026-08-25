<x-layouts::app :title="__('Edit Quiz')">

<div class="min-h-screen bg-black p-6">

    <div class="mx-auto max-w-4xl space-y-6">

        {{-- Header --}}
        <div class="rounded-2xl border border-purple-500/20 bg-linear-to-r from-purple-900/40 via-purple-900/20 to-black p-6 shadow-xl">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-purple-500/20 px-3 py-1 text-xs font-semibold text-purple-300">
                        Quiz Management
                    </span>
                    <h1 class="mt-3 text-3xl font-bold text-white">
                        Edit Quiz
                    </h1>
                    <p class="mt-2 text-sm text-gray-400">
                        Update quiz information, passing score, time limit, and visibility.
                    </p>
                </div>
            </div>
        </div>

        {{-- Form Card --}}
        <div class="rounded-2xl border border-purple-500/20 bg-zinc-950 p-6 shadow-xl">

            <form action="{{ route('quizzes.update', $quiz) }}"
                  method="POST"
                  class="space-y-6">

                @csrf
                @method('PUT')

                {{-- Course Select --}}
                <div>
                    <label class="mb-2 block text-sm font-semibold text-white">
                        Course
                    </label>
                    <select name="course_id"
                            class="w-full rounded-xl border border-purple-500/20 bg-[#111111] p-3 text-white focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500"
                            required>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}"
                                @selected(old('course_id', $quiz->course_id) == $course->id)>
                                {{ $course->title }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Select the course where this quiz belongs.
                    </p>
                    @error('course_id')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Related Lesson Select --}}
                <div>
                    <label class="mb-2 block text-sm font-semibold text-white">
                        Related Lesson
                    </label>
                    <select name="lesson_id"
                            class="w-full rounded-xl border border-purple-500/20 bg-[#111111] p-3 text-white focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500">
                        <option value="">
                            No Lesson — this is a course final quiz
                        </option>
                        @foreach($lessons as $lesson)
                            <option value="{{ $lesson->id }}"
                                @selected(old('lesson_id', $quiz->lesson_id) == $lesson->id)>
                                {{ $lesson->course->title ?? 'No Course' }} — {{ $lesson->title }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Leave this blank if the quiz is for the whole course.
                    </p>
                    @error('lesson_id')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Quiz Title --}}
                <div>
                    <label class="mb-2 block text-sm font-semibold text-white">
                        Quiz Title
                    </label>
                    <input type="text"
                           name="title"
                           value="{{ old('title', $quiz->title) }}"
                           placeholder="Example: Financial Accounting Final Quiz"
                           class="w-full rounded-xl border border-purple-500/20 bg-[#111111] p-3 text-white placeholder-gray-600 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500"
                           required>
                    <p class="mt-1 text-xs text-gray-500">
                        Use a clear title that students can easily understand.
                    </p>
                    @error('title')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Quiz Description --}}
                <div>
                    <label class="mb-2 block text-sm font-semibold text-white">
                        Quiz Description
                    </label>
                    <textarea name="description"
                              rows="5"
                              placeholder="Briefly explain what this quiz covers."
                              class="w-full rounded-xl border border-purple-500/20 bg-[#111111] p-3 text-white placeholder-gray-600 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500">{{ old('description', $quiz->description) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">
                        Briefly explain what the quiz covers.
                    </p>
                    @error('description')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Passing Score & Time Limit --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-white">
                            Passing Score (%)
                        </label>
                        <input type="number"
                               name="passing_score"
                               value="{{ old('passing_score', $quiz->passing_score) }}"
                               min="1"
                               max="100"
                               placeholder="Example: 75"
                               class="w-full rounded-xl border border-purple-500/20 bg-[#111111] p-3 text-white placeholder-gray-600 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500"
                               required>
                        <p class="mt-1 text-xs text-gray-500">
                            Students must reach this percentage to pass the quiz.
                        </p>
                        @error('passing_score')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-white">
                            Time Limit in Minutes
                        </label>
                        <input type="number"
                               name="time_limit_minutes"
                               value="{{ old('time_limit_minutes', $quiz->time_limit_minutes) }}"
                               min="1"
                               placeholder="Example: 30"
                               class="w-full rounded-xl border border-purple-500/20 bg-[#111111] p-3 text-white placeholder-gray-600 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500">
                        <p class="mt-1 text-xs text-gray-500">
                            Optional. Leave blank if there is no time limit.
                        </p>
                        @error('time_limit_minutes')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Published Checkbox --}}
                <div class="rounded-xl border border-purple-500/20 bg-[#111111] p-4">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox"
                               name="is_published"
                               class="mt-1 h-4 w-4 rounded border-purple-500/30 bg-zinc-950 text-purple-600 focus:ring-purple-500 focus:ring-offset-0"
                               @checked(old('is_published', $quiz->is_published))>
                        <div>
                            <span class="block font-semibold text-white">Published</span>
                            <span class="text-sm text-gray-500">
                                Make this quiz available to students after completing the course lessons.
                            </span>
                        </div>
                    </label>
                </div>

                {{-- Buttons --}}
                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-purple-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Update Quiz
                    </button>

                    <a href="{{ route('quizzes.index') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-purple-500/20 bg-[#111111] px-6 py-3 text-sm font-semibold text-gray-300 transition hover:bg-purple-500/10">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Cancel
                    </a>
                </div>

            </form>

        </div>

    </div>
</div>

</x-layouts::app>