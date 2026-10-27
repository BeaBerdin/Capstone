{{--
    Quiz question fields, shared by Create and Edit. $quiz_question is null
    when creating. Field names and options are unchanged.
--}}
@php
    $question = $quiz_question ?? null;
@endphp

<x-pw.form-section title="Question" first>
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6V14M12 17h.01"></path></svg>
    </x-slot:icon>

    <x-pw.field label="Quiz" for="quiz_id" required hint="The quiz this question belongs to.">
        <select id="quiz_id" name="quiz_id" required class="sad-field">
            <option value="">Select quiz</option>
            @foreach($quizzes as $quiz)
                <option value="{{ $quiz->id }}" @selected(old('quiz_id', $question?->quiz_id) == $quiz->id)>
                    {{ $quiz->course->title ?? 'No Course' }} — {{ $quiz->title }}
                </option>
            @endforeach
        </select>
    </x-pw.field>

    <x-pw.field label="Question" for="question" required hint="Write the question clearly and avoid confusing wording.">
        <textarea id="question" name="question" rows="4" required
                  placeholder="Example: What is the basic accounting equation?"
                  class="sad-field">{{ old('question', $question?->question) }}</textarea>
    </x-pw.field>
</x-pw.form-section>

<x-pw.form-section title="Answer Choices">
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6h11M9 12h11M9 18h11"></path><circle cx="4.5" cy="6" r="1.5"></circle><circle cx="4.5" cy="12" r="1.5"></circle><circle cx="4.5" cy="18" r="1.5"></circle></svg>
    </x-slot:icon>

    <div class="grid gap-5 sm:grid-cols-2">
        @foreach([
            'a' => ['A', true, 'Example: Assets = Liabilities + Equity'],
            'b' => ['B', true, 'Example: Assets = Revenue - Expenses'],
            'c' => ['C', false, 'Example: Cash = Income'],
            'd' => ['D', false, 'Example: Revenue = Equity'],
        ] as $key => [$letter, $required, $placeholder])
            <x-pw.field :label="'Option ' . $letter" :for="'option_' . $key" :required="$required" :hint="$required ? null : 'Optional.'">
                <input id="option_{{ $key }}" type="text" name="option_{{ $key }}" maxlength="255" @required($required)
                       value="{{ old('option_' . $key, $question?->{'option_' . $key}) }}"
                       placeholder="{{ $placeholder }}"
                       class="sad-field">
            </x-pw.field>
        @endforeach
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-pw.field label="Correct Answer" for="correct_answer" required hint="The letter of the option with the correct answer.">
            <select id="correct_answer" name="correct_answer" required class="sad-field">
                <option value="">Select correct answer</option>
                @foreach(['A', 'B', 'C', 'D'] as $letter)
                    <option value="{{ $letter }}" @selected(old('correct_answer', $question?->correct_answer) == $letter)>Option {{ $letter }}</option>
                @endforeach
            </select>
        </x-pw.field>

        <x-pw.field label="Points" for="points" required hint="Usually 1 point per question.">
            <input id="points" type="number" name="points" min="1" required
                   value="{{ old('points', $question?->points ?? 1) }}"
                   class="sad-field">
        </x-pw.field>
    </div>
</x-pw.form-section>
