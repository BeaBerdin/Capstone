{{--
    Admin assignment fields, shared by Create and Edit. $assignment is null
    when creating. The submission type fields are shared with the teacher form.
--}}
@php
    $assignment = $assignment ?? null;
@endphp

{{-- ---------- Assignment details ---------- --}}
<x-pw.form-section title="Assignment Details" first>
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6M8 13h8M8 17h6"></path></svg>
    </x-slot:icon>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-pw.field label="Course" for="course_id" required>
            <select id="course_id" name="course_id" required class="sad-field">
                <option value="">Select course</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected((string) old('course_id', $assignment?->course_id) === (string) $course->id)>
                        {{ $course->title }}
                    </option>
                @endforeach
            </select>
        </x-pw.field>

        <x-pw.field label="Related Lesson" for="lesson_id" hint="The lesson must belong to the selected course.">
            <select id="lesson_id" name="lesson_id" class="sad-field">
                <option value="">Course-level assignment</option>
                @foreach($lessons as $lesson)
                    <option value="{{ $lesson->id }}" @selected((string) old('lesson_id', $assignment?->lesson_id) === (string) $lesson->id)>
                        {{ $lesson->title }}
                    </option>
                @endforeach
            </select>
        </x-pw.field>
    </div>

    <x-pw.field label="Assignment Title" for="title" required>
        <input id="title" type="text" name="title" required maxlength="255"
               value="{{ old('title', $assignment?->title) }}"
               placeholder="e.g. Reflection Paper"
               class="sad-field">
    </x-pw.field>

    <x-pw.field label="Instructions / Description" for="description" hint="Students see these instructions on the assignment page.">
        <textarea id="description" name="description" rows="6"
                  placeholder="Explain what the student should submit..."
                  class="sad-field">{{ old('description', $assignment?->description) }}</textarea>
    </x-pw.field>
</x-pw.form-section>

{{-- ---------- Submission & evaluation ---------- --}}
<x-pw.form-section title="Submission & Evaluation">
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z"></path><path d="m8.5 12.5 2.5 2.5 4.5-5"></path></svg>
    </x-slot:icon>

    @include('Teacher.assignments._type-fields', ['assignment' => $assignment])
</x-pw.form-section>

{{-- ---------- Scoring ---------- --}}
<x-pw.form-section title="Scoring">
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"></path></svg>
    </x-slot:icon>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-pw.field label="Maximum Score" for="max_score" required hint="Total points for this assignment (1–10,000).">
            <input id="max_score" type="number" name="max_score" min="1" max="10000" required
                   value="{{ old('max_score', $assignment?->max_score ?? 100) }}"
                   class="sad-field">
        </x-pw.field>
    </div>

    {{-- is_published is required: the hidden 0 is sent when the box is unticked. --}}
    <input type="hidden" name="is_published" value="0">
    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-violet-100 bg-violet-50/50 px-4 py-3">
        <input type="checkbox" name="is_published" value="1"
               @checked((bool) old('is_published', $assignment?->is_published ?? true))
               class="mt-1 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
        <span>
            <span class="block text-sm font-semibold text-slate-700">Publish assignment</span>
            <span class="mt-0.5 block text-xs text-slate-500">Untick to keep it as a draft that students cannot see.</span>
        </span>
    </label>
</x-pw.form-section>
