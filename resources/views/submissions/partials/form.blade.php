{{--
    Submission form fields, shared by submissions.create and submissions.edit.

    $submission: the submission being edited, or null when creating
--}}

@php
    $submission = $submission ?? null;
@endphp

<x-pw.form-section title="Submission" first>
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6M12 18v-6M9 15l3-3 3 3"></path></svg>
    </x-slot:icon>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-pw.field label="Student" for="student_id" required>
            <select id="student_id" name="student_id" required class="sad-field">
                <option value="">Select student</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}" @selected((string) old('student_id', $submission?->student_id) === (string) $student->id)>
                        {{ $student->name }}
                    </option>
                @endforeach
            </select>
        </x-pw.field>

        <x-pw.field label="Assignment" for="assignment_id" required>
            <select id="assignment_id" name="assignment_id" required class="sad-field">
                <option value="">Select assignment</option>
                @foreach($assignments as $assignment)
                    <option value="{{ $assignment->id }}" @selected((string) old('assignment_id', $submission?->assignment_id) === (string) $assignment->id)>
                        {{ $assignment->title }}
                    </option>
                @endforeach
            </select>
        </x-pw.field>
    </div>

    <x-pw.field label="Answer" for="answer_text">
        <textarea id="answer_text" name="answer_text" rows="6"
                  placeholder="The student's typed answer or code..."
                  class="sad-field font-mono">{{ old('answer_text', $submission?->answer_text) }}</textarea>
    </x-pw.field>

    <x-pw.field label="File Path" for="file_path" hint="Optional. Where the uploaded file is stored.">
        <input id="file_path" type="text" name="file_path"
               value="{{ old('file_path', $submission?->file_path) }}"
               placeholder="e.g. submissions/essay.pdf"
               class="sad-field">
    </x-pw.field>
</x-pw.form-section>

<x-pw.form-section title="Grading">
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z"></path><path d="m8.5 12.5 2.5 2.5 4.5-5"></path></svg>
    </x-slot:icon>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-pw.field label="Score" for="score" hint="Optional. Leave blank until graded.">
            <input id="score" type="number" name="score" min="0"
                   value="{{ old('score', $submission?->score) }}"
                   class="sad-field">
        </x-pw.field>

        <x-pw.field label="Status" for="status" required>
            <select id="status" name="status" required class="sad-field">
                @foreach(['submitted' => 'Submitted', 'graded' => 'Graded', 'returned' => 'Returned'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $submission?->status ?? 'submitted') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </x-pw.field>
    </div>

    <x-pw.field label="Feedback" for="feedback" hint="Optional. The student sees this with their grade.">
        <textarea id="feedback" name="feedback" rows="4"
                  placeholder="Feedback for the student..."
                  class="sad-field">{{ old('feedback', $submission?->feedback) }}</textarea>
    </x-pw.field>
</x-pw.form-section>
