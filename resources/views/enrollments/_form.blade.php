{{-- Enrollment fields, shared by Create and Edit. $enrollment is null when creating. --}}
@php
    $enrollment = $enrollment ?? null;
@endphp

<x-pw.form-section title="Enrollment" first>
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="4"></circle><path d="M2 21a7 7 0 0 1 14 0M19 8v6M16 11h6"></path></svg>
    </x-slot:icon>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-pw.field label="Student" for="student_id" required>
            <select id="student_id" name="student_id" required class="sad-field">
                <option value="">Select student</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}" @selected((string) old('student_id', $enrollment?->student_id) === (string) $student->id)>
                        {{ $student->name }}
                    </option>
                @endforeach
            </select>
        </x-pw.field>

        <x-pw.field label="Course" for="course_id" required>
            <select id="course_id" name="course_id" required class="sad-field">
                <option value="">Select course</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected((string) old('course_id', $enrollment?->course_id) === (string) $course->id)>
                        {{ $course->title }}
                    </option>
                @endforeach
            </select>
        </x-pw.field>
    </div>
</x-pw.form-section>

<x-pw.form-section title="Progress">
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="m7 15 4-4 3 3 5-6"></path></svg>
    </x-slot:icon>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-pw.field label="Status" for="status" required hint="Completed records today as the completion date.">
            <select id="status" name="status" required class="sad-field">
                @foreach(['active' => 'Active', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $enrollment?->status ?? 'active') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-pw.field>

        <x-pw.field label="Progress (%)" for="progress_percentage" required hint="A number from 0 to 100.">
            <input id="progress_percentage" type="number" name="progress_percentage" min="0" max="100" required
                   value="{{ old('progress_percentage', $enrollment?->progress_percentage ?? 0) }}"
                   class="sad-field">
        </x-pw.field>
    </div>
</x-pw.form-section>
