<div class="mt-4 space-y-4">
    <label class="block text-sm font-semibold text-slate-700">
        Question
        <textarea form="quiz-edit-form" name="{{ $fieldPrefix }}[question]" rows="3"
                  class="pw-field mt-2 px-4 py-3" @required(! $newQuestion) @readonly(! $quizCanBeModified)>{{ old($inputPrefix.'.question', $question?->question) }}</textarea>
    </label>

    <div class="grid gap-3 sm:grid-cols-2">
        @foreach (['A', 'B', 'C', 'D'] as $letter)
            @php $field = 'option_'.strtolower($letter); @endphp
            <label class="block text-sm font-semibold text-slate-700">
                Option {{ $letter }} @if (in_array($letter, ['C', 'D'], true)) <span class="font-normal text-slate-500">(optional)</span> @endif
                <input type="text" form="quiz-edit-form" name="{{ $fieldPrefix }}[{{ $field }}]" maxlength="255"
                       value="{{ old($inputPrefix.'.'.$field, data_get($question, $field)) }}" class="pw-field mt-2 h-11 px-4"
                       @required(! $newQuestion && in_array($letter, ['A', 'B'], true)) @readonly(! $quizCanBeModified)>
            </label>
        @endforeach
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <label class="block text-sm font-semibold text-slate-700">
            Correct answer
            <select form="quiz-edit-form" name="{{ $fieldPrefix }}[correct_answer]" class="pw-field mt-2 h-11 px-4"
                    @required(! $newQuestion) @disabled(! $quizCanBeModified)>
                <option value="">Select an option</option>
                @foreach (['A', 'B', 'C', 'D'] as $letter)
                    <option value="{{ $letter }}" @selected(old($inputPrefix.'.correct_answer', $question?->correct_answer) === $letter)>
                        Option {{ $letter }}
                    </option>
                @endforeach
            </select>
        </label>
        <label class="block text-sm font-semibold text-slate-700">
            Points
            <input type="number" form="quiz-edit-form" name="{{ $fieldPrefix }}[points]" min="1" max="100"
                   value="{{ old($inputPrefix.'.points', $question?->points ?? 1) }}" class="pw-field mt-2 h-11 px-4"
                   @required(! $newQuestion) @readonly(! $quizCanBeModified)>
        </label>
    </div>
</div>
