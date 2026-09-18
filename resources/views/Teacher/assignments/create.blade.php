<x-layouts::app :title="'Create Assignment - ' . $course->title">
<div class="min-h-screen bg-[#f8f9fc]">
    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">
        <div class="mx-auto max-w-4xl">

            <div class="mb-5 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                <a href="{{ route('teacher.assignments.index') }}" class="font-medium hover:text-violet-600">
                    Assignments
                </a>
                <span>›</span>
                <span class="font-semibold text-slate-600">Create</span>
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                    {{ $course->title }}
                </p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                    Create Assignment
                </h1>
                <p class="mt-2 text-sm text-slate-500">
                    Add an output-based activity that enrolled students can submit for evaluation.
                </p>
            </div>

            @if($errors->any())
                <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4">
                    <p class="text-sm font-bold text-rose-700">Please correct the following:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-600">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('teacher.assignments.store', $course) }}"
                class="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                @csrf

                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <h2 class="text-sm font-bold text-slate-800">Assignment Details</h2>
                </div>

                <div class="space-y-5 p-5 sm:p-6">
                    <div>
                        <label for="title" class="text-sm font-semibold text-slate-700">
                            Assignment Title <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="title"
                            name="title"
                            type="text"
                            value="{{ old('title') }}"
                            required
                            maxlength="255"
                            class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-4 text-sm
                                   outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                            placeholder="e.g. Reflection Paper"
                        >
                    </div>

                    <div>
                        <label for="description" class="text-sm font-semibold text-slate-700">
                            Instructions / Description
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="6"
                            class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm
                                   outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                            placeholder="Explain what the student should submit..."
                        >{{ old('description') }}</textarea>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="lesson_id" class="text-sm font-semibold text-slate-700">
                                Related Lesson
                            </label>
                            <select
                                id="lesson_id"
                                name="lesson_id"
                                class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-white px-4
                                       text-sm outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                            >
                                <option value="">Course-level assignment</option>
                                @foreach($lessons as $lesson)
                                    <option
                                        value="{{ $lesson->id }}"
                                        @selected((string) old('lesson_id') === (string) $lesson->id)
                                    >
                                        {{ $lesson->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="max_score" class="text-sm font-semibold text-slate-700">
                                Maximum Score <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="max_score"
                                name="max_score"
                                type="number"
                                min="1"
                                max="10000"
                                value="{{ old('max_score', 100) }}"
                                required
                                class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-4 text-sm
                                       outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                            >
                        </div>
                    </div>

                    <div>
                        <label for="due_date" class="text-sm font-semibold text-slate-700">
                            Due Date
                        </label>
                        <input
                            id="due_date"
                            name="due_date"
                            type="datetime-local"
                            value="{{ old('due_date') }}"
                            class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-4 text-sm
                                   outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                        >
                        <p class="mt-1.5 text-xs text-slate-400">
                            Leave blank if the assignment has no deadline.
                        </p>
                    </div>

                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200
                                  bg-slate-50 px-4 py-3">
                        <input
                            type="checkbox"
                            name="is_published"
                            value="1"
                            @checked(old('is_published', true))
                            class="mt-1 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                        >
                        <span>
                            <span class="block text-sm font-semibold text-slate-700">Publish assignment</span>
                            <span class="mt-0.5 block text-xs text-slate-500">
                                Students will see this once the course itself is published and they are enrolled.
                            </span>
                        </span>
                    </label>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50
                            px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <a
                        href="{{ route('teacher.assignments.index') }}"
                        class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200
                               bg-white px-5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </a>
                    <button
                        type="submit"
                        class="inline-flex h-11 items-center justify-center rounded-xl bg-gradient-to-r
                               from-violet-600 to-indigo-600 px-6 text-sm font-semibold text-white
                               shadow-md shadow-violet-200 transition hover:-translate-y-0.5"
                    >
                        Create Assignment
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>
</x-layouts::app>
