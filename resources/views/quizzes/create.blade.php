<x-layouts::app :title="'Create Quiz'">

    <style>
        .pw-card {
            background: #ffffff;
            border: 1px solid #e7e9ef;
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.035);
        }

        .pw-input {
            width: 100%;
            border: 1px solid #e1e4ea;
            border-radius: 12px;
            background: #ffffff;
            color: #334155;
            font-size: 13px;
            padding: 0.75rem 1rem;
            transition: all 160ms ease;
        }

        .pw-input:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.08);
        }

        .pw-input::placeholder {
            color: #94a3b8;
        }

        .pw-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #334155;
        }

        .pw-helper {
            margin-top: 0.5rem;
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .pw-error {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #dc2626;
        }
    </style>

    <div class="min-h-screen bg-[#f8f9fc]">

        <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

            <div class="mx-auto max-w-[900px]">


                {{-- =====================================================
                    HEADER
                ====================================================== --}}

                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">

                    <div>

                        <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                            Assessment Builder
                        </p>

                        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                            Create Quiz
                        </h1>

                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                            Create an assessment for a course. Students can take this quiz after completing the lessons.
                        </p>

                    </div>

                </div>



                {{-- =====================================================
                    FORM CARD
                ====================================================== --}}

                <div class="pw-card mt-7 p-6 sm:p-8">

                    <form action="{{ route('quizzes.store') }}" method="POST" class="space-y-6">

                        @csrf


                        {{-- COURSE --}}
                        <div>

                            <label class="pw-label">
                                Course
                            </label>

                            <select
                                name="course_id"
                                class="pw-input"
                                required
                            >

                                <option value="">
                                    Select the course for this quiz
                                </option>

                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" @selected(old('course_id') == $course->id)>
                                        {{ $course->title }}
                                    </option>
                                @endforeach

                            </select>

                            <p class="pw-helper">
                                Select the course where this quiz belongs.
                            </p>

                            @error('course_id')
                                <p class="pw-error">{{ $message }}</p>
                            @enderror

                        </div>


                        {{-- LESSON --}}
                        <div>

                            <label class="pw-label">
                                Related Lesson
                            </label>

                            <select
                                name="lesson_id"
                                class="pw-input"
                            >

                                <option value="">
                                    No Lesson (Final Course Quiz)
                                </option>

                                @foreach($lessons as $lesson)
                                    <option value="{{ $lesson->id }}" @selected(old('lesson_id') == $lesson->id)>
                                        {{ $lesson->course->title ?? 'No Course' }} — {{ $lesson->title }}
                                    </option>
                                @endforeach

                            </select>

                            <p class="pw-helper">
                                Leave empty if this is the final assessment for the entire course.
                            </p>

                            @error('lesson_id')
                                <p class="pw-error">{{ $message }}</p>
                            @enderror

                        </div>


                        {{-- QUIZ TITLE --}}
                        <div>

                            <label class="pw-label">
                                Quiz Title
                            </label>

                            <input
                                type="text"
                                name="title"
                                value="{{ old('title') }}"
                                placeholder="Example: Entrepreneurship Final Quiz"
                                class="pw-input"
                                required
                            >

                            @error('title')
                                <p class="pw-error">{{ $message }}</p>
                            @enderror

                        </div>


                        {{-- DESCRIPTION --}}
                        <div>

                            <label class="pw-label">
                                Quiz Description
                            </label>

                            <textarea
                                name="description"
                                rows="5"
                                placeholder="Briefly describe what this quiz covers..."
                                class="pw-input"
                            >{{ old('description') }}</textarea>

                            @error('description')
                                <p class="pw-error">{{ $message }}</p>
                            @enderror

                        </div>


                        {{-- PASSING SCORE + TIME --}}
                        <div class="grid gap-4 sm:grid-cols-2">

                            <div>

                                <label class="pw-label">
                                    Passing Score (%)
                                </label>

                                <input
                                    type="number"
                                    name="passing_score"
                                    value="{{ old('passing_score', 75) }}"
                                    min="1"
                                    max="100"
                                    class="pw-input"
                                >

                                @error('passing_score')
                                    <p class="pw-error">{{ $message }}</p>
                                @enderror

                            </div>

                            <div>

                                <label class="pw-label">
                                    Time Limit (Minutes)
                                </label>

                                <input
                                    type="number"
                                    name="time_limit_minutes"
                                    value="{{ old('time_limit_minutes', 15) }}"
                                    min="1"
                                    placeholder="Optional"
                                    class="pw-input"
                                >

                                @error('time_limit_minutes')
                                    <p class="pw-error">{{ $message }}</p>
                                @enderror

                            </div>

                        </div>


                        {{-- PUBLISHED --}}
                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">

                            <label class="flex items-start gap-3">

                                <input
                                    type="checkbox"
                                    name="is_published"
                                    class="mt-1 h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                                    checked
                                >

                                <span>

                                    <span class="block text-sm font-semibold text-slate-800">
                                        Published
                                    </span>

                                    <span class="text-xs text-slate-500">
                                        Make this quiz available for students after completing the required lessons.
                                    </span>

                                </span>

                            </label>

                        </div>


                        {{-- BUTTONS --}}
                        <div class="flex flex-wrap gap-3 pt-2">

                            <button
                                type="submit"
                                class="inline-flex h-11 items-center justify-center rounded-xl bg-violet-600 px-6 text-sm font-semibold text-white transition hover:bg-violet-700"
                            >
                                Save Quiz
                            </button>

                            <a
                                href="{{ route('quizzes.index') }}"
                                class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-6 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                            >
                                Cancel
                            </a>

                        </div>

                    </form>

                </div>

            </div>

        </main>

    </div>

</x-layouts::app>