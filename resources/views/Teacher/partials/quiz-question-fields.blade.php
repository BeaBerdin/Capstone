@php
    // Inline field errors are shown only where the caller asks for them
    // (the Add a Question form after clicking Add Question).
    $inlineErrors = $inlineErrors ?? false;
    $fieldError = fn (string $field) => $inlineErrors ? $errors->first($inputPrefix.'.'.$field) : null;
    $errorId = fn (string $field) => str_replace(['.', '_'], '-', $inputPrefix.'-'.$field).'-error';
@endphp
<div class="mt-4 space-y-4">
    <label class="block text-sm font-semibold text-slate-700">
        Question
        <textarea form="quiz-edit-form" name="{{ $fieldPrefix }}[question]" rows="3"
                  class="pw-field mt-2 px-4 py-3" @required(! $newQuestion) @readonly(! $quizCanBeModified)
                  @if ($fieldError('question')) aria-invalid="true" aria-describedby="{{ $errorId('question') }}" @endif>{{ old($inputPrefix.'.question', $question?->question) }}</textarea>
        @if ($fieldError('question'))
            <span id="{{ $errorId('question') }}" class="mt-1 block text-sm font-medium text-red-700">{{ $fieldError('question') }}</span>
        @endif
    </label>

    <div class="grid gap-3 sm:grid-cols-2">
        @foreach (['A', 'B', 'C', 'D'] as $letter)
            @php $field = 'option_'.strtolower($letter); @endphp
            <label class="block text-sm font-semibold text-slate-700">
                Option {{ $letter }} @if (in_array($letter, ['C', 'D'], true)) <span class="font-normal text-slate-500">(optional)</span> @endif
                <input type="text" form="quiz-edit-form" name="{{ $fieldPrefix }}[{{ $field }}]" maxlength="255"
                       value="{{ old($inputPrefix.'.'.$field, data_get($question, $field)) }}" class="pw-field mt-2 h-11 px-4"
                       @required(! $newQuestion && in_array($letter, ['A', 'B'], true)) @readonly(! $quizCanBeModified)
                       @if ($fieldError($field)) aria-invalid="true" aria-describedby="{{ $errorId($field) }}" @endif>
                @if ($fieldError($field))
                    <span id="{{ $errorId($field) }}" class="mt-1 block text-sm font-medium text-red-700">{{ $fieldError($field) }}</span>
                @endif
            </label>
        @endforeach
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <label class="block text-sm font-semibold text-slate-700">
            Correct answer
            <select form="quiz-edit-form" name="{{ $fieldPrefix }}[correct_answer]" class="pw-field mt-2 h-11 px-4"
                    @required(! $newQuestion) @disabled(! $quizCanBeModified)
                    @if ($fieldError('correct_answer')) aria-invalid="true" aria-describedby="{{ $errorId('correct_answer') }}" @endif>
                <option value="">Select an option</option>
                @foreach (['A', 'B', 'C', 'D'] as $letter)
                    <option value="{{ $letter }}" @selected(old($inputPrefix.'.correct_answer', $question?->correct_answer) === $letter)>
                        Option {{ $letter }}
                    </option>
                @endforeach
            </select>
            @if ($fieldError('correct_answer'))
                <span id="{{ $errorId('correct_answer') }}" class="mt-1 block text-sm font-medium text-red-700">{{ $fieldError('correct_answer') }}</span>
            @endif
        </label>
        <label class="block text-sm font-semibold text-slate-700">
            Points
            <input type="number" form="quiz-edit-form" name="{{ $fieldPrefix }}[points]" min="1" max="100"
                   value="{{ old($inputPrefix.'.points', $question?->points ?? 1) }}" class="pw-field mt-2 h-11 px-4"
                   @required(! $newQuestion) @readonly(! $quizCanBeModified)
                   @if ($fieldError('points')) aria-invalid="true" aria-describedby="{{ $errorId('points') }}" @endif>
            @if ($fieldError('points'))
                <span id="{{ $errorId('points') }}" class="mt-1 block text-sm font-medium text-red-700">{{ $fieldError('points') }}</span>
            @endif
        </label>
    </div>
</div>
