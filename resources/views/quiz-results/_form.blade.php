{{-- Quiz result fields, shared by Create and Edit. $quiz_result is null when creating. --}}
@php
    $result = $quiz_result ?? null;
@endphp

<x-pw.form-section title="Student & Quiz" first>
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
    </x-slot:icon>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-pw.field label="Student" for="student_id" required hint="Students enrolled in your courses.">
            <select id="student_id" name="student_id" required class="sad-field">
                <option value="">Select student</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}" @selected((string) old('student_id', $result?->student_id) === (string) $student->id)>
                        {{ $student->name }}
                    </option>
                @endforeach
            </select>
        </x-pw.field>

        <x-pw.field label="Quiz" for="quiz_id" required>
            <select id="quiz_id" name="quiz_id" required class="sad-field">
                <option value="">Select quiz</option>
                @foreach($quizzes as $quiz)
                    <option value="{{ $quiz->id }}" @selected((string) old('quiz_id', $result?->quiz_id) === (string) $quiz->id)>
                        {{ $quiz->title }}
                    </option>
                @endforeach
            </select>
        </x-pw.field>
    </div>
</x-pw.form-section>

<x-pw.form-section title="Score">
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z"></path><path d="m8.5 12.5 2.5 2.5 4.5-5"></path></svg>
    </x-slot:icon>

    <div class="grid gap-5 sm:grid-cols-3">
        <x-pw.field label="Score" for="score" required hint="Correct items.">
            <input id="score" type="number" name="score" min="0" required
                   value="{{ old('score', $result?->score) }}"
                   placeholder="e.g. 8"
                   class="sad-field">
        </x-pw.field>

        <x-pw.field label="Total Items" for="total_items" required>
            <input id="total_items" type="number" name="total_items" min="1" required
                   value="{{ old('total_items', $result?->total_items) }}"
                   placeholder="e.g. 10"
                   class="sad-field">
        </x-pw.field>

        <x-pw.field label="Attempt Number" for="attempt_number" required>
            <input id="attempt_number" type="number" name="attempt_number" min="1" required
                   value="{{ old('attempt_number', $result?->attempt_number ?? 1) }}"
                   class="sad-field">
        </x-pw.field>
    </div>

    <div class="rounded-2xl border border-violet-100 bg-violet-50/60 px-4 py-3 text-sm text-violet-800">
        The percentage is computed automatically. The result is marked
        <span class="font-semibold text-emerald-600">Passed</span>
        when it reaches the quiz's passing score (75% if the quiz has none set).
    </div>
</x-pw.form-section>
