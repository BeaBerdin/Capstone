{{--
    Assignment form sections, shared by Create and Edit. $assignment is null
    when creating. Field names and validation are unchanged.
--}}
@php
    $assignment = $assignment ?? null;
@endphp

{{-- ---------- Assignment details ---------- --}}
<x-pw.form-section title="Assignment Details" first>
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6M8 13h8M8 17h6"></path></svg>
    </x-slot:icon>

    {{-- Assignment Title --}}
    <div>
        <label for="title" class="text-sm font-semibold text-slate-700">
            Assignment Title
            <span class="text-rose-500">*</span>
        </label>

        <input
            id="title"
            name="title"
            type="text"
            value="{{ old('title', $assignment?->title) }}"
            required
            maxlength="255"
            class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-4 text-sm
                   outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
            placeholder="e.g. Reflection Paper"
        >

        @error('title')
            <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Description --}}
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
        >{{ old('description', $assignment?->description) }}</textarea>

        <p class="mt-1.5 text-xs text-slate-500">Students see these instructions on the assignment page.</p>
    </div>
</x-pw.form-section>


{{-- ---------- Submission & evaluation ---------- --}}
<x-pw.form-section title="Submission & Evaluation">
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z"></path><path d="m8.5 12.5 2.5 2.5 4.5-5"></path></svg>
    </x-slot:icon>

    @include('Teacher.assignments._type-fields', ['assignment' => $assignment])
</x-pw.form-section>


{{-- ---------- Schedule & scoring ---------- --}}
<x-pw.form-section title="Schedule & Scoring">
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"></path></svg>
    </x-slot:icon>

    {{-- Lesson + Max Score --}}
    <div class="grid gap-5 sm:grid-cols-2">

        <div>
            <label for="lesson_id" class="text-sm font-semibold text-slate-700">
                Related Lesson
            </label>

            <select
                id="lesson_id"
                name="lesson_id"
                class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm
                       outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
            >
                <option value="">Course-level assignment</option>

                @foreach($lessons as $lesson)
                    <option
                        value="{{ $lesson->id }}"
                        @selected((string) old('lesson_id', $assignment?->lesson_id) === (string) $lesson->id)
                    >
                        {{ $lesson->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="max_score" class="text-sm font-semibold text-slate-700">
                Maximum Score
                <span class="text-rose-500">*</span>
            </label>

            <input
                id="max_score"
                name="max_score"
                type="number"
                min="1"
                max="10000"
                value="{{ old('max_score', $assignment?->max_score ?? 100) }}"
                required
                class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-4 text-sm
                       outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
            >

            @error('max_score')
                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
            @else
                <p class="mt-1.5 text-xs text-slate-500">Total points for this assignment (1–10,000).</p>
            @enderror
        </div>

    </div>

    {{-- Publish --}}
    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-violet-100 bg-violet-50/50 px-4 py-3">
        <input
            type="checkbox"
            name="is_published"
            value="1"
            @checked((bool) old('is_published', $assignment?->is_published ?? true))
            class="mt-1 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
        >

        <span>
            <span class="block text-sm font-semibold text-slate-700">Publish assignment</span>
            <span class="mt-0.5 block text-xs text-slate-500">
                Students will see this once the course itself is published and they are enrolled.
            </span>
        </span>
    </label>
</x-pw.form-section>
