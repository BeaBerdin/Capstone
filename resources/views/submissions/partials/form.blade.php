{{--
    Submission form fields, shared by submissions.create and submissions.edit.

    $submission: the submission being edited, or null when creating
--}}

@php
    $submission = $submission ?? null;
@endphp

<style>
    .pw-card {
        background: #ffffff;
        border: 1px solid #e7e9ef;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.035);
    }

    .pw-label {
        display: block;
        margin-bottom: 6px;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
    }

    .pw-input {
        width: 100%;
        min-height: 44px;
        border: 1px solid #e1e4ea;
        border-radius: 12px;
        background: #ffffff;
        padding: 10px 14px;
        color: #334155;
        font-size: 14px;
        transition: border-color 160ms ease, box-shadow 160ms ease;
    }

    .pw-input:focus {
        outline: none;
        border-color: #8b5cf6;
        box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.08);
    }
</style>

@if($errors->any())
    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700" role="alert">
        <p class="font-semibold">Please fix the following:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

    <div>
        <label for="student_id" class="pw-label">Student</label>
        <select id="student_id" name="student_id" class="pw-input" required>
            <option value="">Select Student</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}" @selected((string) old('student_id', $submission?->student_id) === (string) $student->id)>
                    {{ $student->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="assignment_id" class="pw-label">Assignment</label>
        <select id="assignment_id" name="assignment_id" class="pw-input" required>
            <option value="">Select Assignment</option>
            @foreach($assignments as $assignment)
                <option value="{{ $assignment->id }}" @selected((string) old('assignment_id', $submission?->assignment_id) === (string) $assignment->id)>
                    {{ $assignment->title }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="sm:col-span-2">
        <label for="answer_text" class="pw-label">Answer</label>
        <textarea
            id="answer_text"
            name="answer_text"
            rows="6"
            placeholder="Answer"
            class="pw-input font-mono"
        >{{ old('answer_text', $submission?->answer_text) }}</textarea>
    </div>

    <div class="sm:col-span-2">
        <label for="file_path" class="pw-label">
            File Path <span class="font-normal text-slate-400">(optional)</span>
        </label>
        <input
            type="text"
            id="file_path"
            name="file_path"
            value="{{ old('file_path', $submission?->file_path) }}"
            placeholder="File Path"
            class="pw-input"
        >
    </div>

    <div>
        <label for="score" class="pw-label">
            Score <span class="font-normal text-slate-400">(optional)</span>
        </label>
        <input
            type="number"
            id="score"
            name="score"
            min="0"
            value="{{ old('score', $submission?->score) }}"
            placeholder="Score"
            class="pw-input"
        >
    </div>

    <div>
        <label for="status" class="pw-label">Status</label>
        <select id="status" name="status" class="pw-input" required>
            @foreach(['submitted' => 'Submitted', 'graded' => 'Graded', 'returned' => 'Returned'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $submission?->status ?? 'submitted') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="sm:col-span-2">
        <label for="feedback" class="pw-label">
            Feedback <span class="font-normal text-slate-400">(optional)</span>
        </label>
        <textarea
            id="feedback"
            name="feedback"
            rows="4"
            placeholder="Feedback"
            class="pw-input"
        >{{ old('feedback', $submission?->feedback) }}</textarea>
    </div>

</div>
