{{--
    Submission type, AI evaluation, rubric and programming language fields,
    shared by the create and edit forms. Pass $assignment when editing so the
    current values are pre-filled.
--}}
@php
    $assignment = $assignment ?? null;
    $currentSubmissionType = old('submission_type', $assignment?->submission_type ?? 'essay');
@endphp

                        {{-- Submission Type --}}
                        <div>
                            <label
                                for="submission_type"
                                class="text-sm font-semibold text-slate-700"
                            >
                                Submission Type
                                <span class="text-rose-500">*</span>
                            </label>

                            <select
                                id="submission_type"
                                name="submission_type"
                                required
                                class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm
                                       outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                            >
                                <option
                                    value="essay"
                                    @selected($currentSubmissionType === 'essay')
                                >
                                    Essay / Written Response
                                </option>

                                <option
                                    value="code"
                                    @selected($currentSubmissionType === 'code')
                                >
                                    Code Submission
                                </option>

                                <option
                                    value="multiple_choice"
                                    @selected($currentSubmissionType === 'multiple_choice')
                                >
                                    Multiple Choice
                                </option>
                            </select>

                            <p class="mt-1.5 text-xs text-slate-400">
                                Choose what type of output students will submit.
                            </p>
                        </div>

                        {{-- How students answer --}}
                        <div id="answer-format-section">
                            <label
                                for="answer_format"
                                class="text-sm font-semibold text-slate-700"
                            >
                                How Students Answer
                            </label>

                            {{-- Labels follow the submission type (essay or code); the stored values do not change. --}}
                            <select
                                id="answer_format"
                                name="answer_format"
                                data-labels='@json(\App\Models\Assignment::ANSWER_FORMAT_LABELS)'
                                class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm
                                       outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                            >
                                @foreach (\App\Models\Assignment::answerFormatLabels($currentSubmissionType) as $formatKey => $formatLabel)
                                    <option
                                        value="{{ $formatKey }}"
                                        @selected(old('answer_format', $assignment?->answer_format ?? 'either') === $formatKey)
                                    >
                                        {{ $formatLabel }}
                                    </option>
                                @endforeach
                            </select>

                            <p class="mt-1.5 text-xs text-slate-400">
                                Typed answers and pasted code keep their exact formatting. Uploaded files may be up to 25 MB.
                            </p>
                        </div>

                        {{-- AI Evaluation --}}
                        <div
                            id="ai-evaluation-section"
                            class="rounded-2xl border border-violet-200 bg-violet-50/70 p-5"
                        >
                            <div class="flex items-start gap-4">

                                <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path d="M12 3v18"/>
                                        <path d="M3 12h18"/>
                                        <path d="M5.5 5.5l13 13"/>
                                        <path d="M18.5 5.5l-13 13"/>
                                    </svg>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <label class="flex cursor-pointer items-start gap-3">

                                        <input
                                            id="ai_evaluation_enabled"
                                            type="checkbox"
                                            name="ai_evaluation_enabled"
                                            value="1"
                                            @checked((bool) old('ai_evaluation_enabled', $assignment?->ai_evaluation_enabled))
                                            class="mt-1 rounded border-violet-300 text-violet-600
                                                   focus:ring-violet-500"
                                        >

                                        <span>
                                            <span class="block text-sm font-bold text-violet-950">
                                                Enable AI Evaluation
                                            </span>

                                            <span class="mt-1 block text-xs leading-5 text-violet-700">
                                                Gemini will provide a suggested score and feedback.
                                                The teacher will still review and determine the final grade.
                                            </span>
                                        </span>

                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Evaluation Criteria --}}
                        <div id="evaluation-criteria-section">

                            <label
                                for="evaluation_criteria"
                                class="text-sm font-semibold text-slate-700"
                            >
                                Evaluation Criteria / Rubric
                            </label>

                            <textarea
                                id="evaluation_criteria"
                                name="evaluation_criteria"
                                rows="5"
                                class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm
                                       outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                                placeholder="Example:
- Understanding of the topic
- Accuracy of information
- Organization of ideas
- Relevance to the lesson
- Clarity of explanation"
                            >{{ old('evaluation_criteria', $assignment?->evaluation_criteria) }}</textarea>

                            <p class="mt-1.5 text-xs text-slate-400">
                                These criteria will help Gemini evaluate the student's work.
                            </p>
                        </div>

                        {{-- Programming Language --}}
                        <div
                            id="programming-language-section"
                            class="hidden"
                        >
                            <label
                                for="programming_language"
                                class="text-sm font-semibold text-slate-700"
                            >
                                Programming Language
                                <span class="text-rose-500">*</span>
                            </label>

                            <select
                                id="programming_language"
                                name="programming_language"
                                class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm
                                       outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                            >
                                <option value="">Select language</option>

                                @foreach (\App\Models\Assignment::PROGRAMMING_LANGUAGES as $languageKey => $language)
                                    <option
                                        value="{{ $languageKey }}"
                                        @selected(old('programming_language', $assignment?->programming_language) === $languageKey)
                                    >
                                        {{ $language['label'] }}
                                    </option>
                                @endforeach
                            </select>

                            <p class="mt-1.5 text-xs text-slate-400">
                                Students can type their code or upload one source file in this language. The language is also used for AI evaluation.
                            </p>
                        </div>

    {{-- Dynamic Form Behavior --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const submissionType = document.getElementById('submission_type');

            const aiSection = document.getElementById('ai-evaluation-section');

            const aiCheckbox = document.getElementById('ai_evaluation_enabled');

            const criteriaSection = document.getElementById('evaluation-criteria-section');

            const programmingSection = document.getElementById('programming-language-section');

            const programmingLanguage = document.getElementById('programming_language');

            const answerFormatSection = document.getElementById('answer-format-section');

            const answerFormat = document.getElementById('answer_format');

            const answerFormatLabels = JSON.parse(answerFormat.dataset.labels);

            function updateAssignmentFields() {

                const type = submissionType.value;

                // Multiple choice has no typed answer or file upload.
                answerFormatSection.classList.toggle('hidden', type === 'multiple_choice');

                // Word the options for the selected type; the values stay the same.
                const labels = answerFormatLabels[type === 'code' ? 'code' : 'essay'];

                Array.from(answerFormat.options).forEach(function (option) {
                    option.textContent = labels[option.value];
                });

                /*
                |--------------------------------------------------------------------------
                | Multiple Choice
                |--------------------------------------------------------------------------
                */

                if (type === 'multiple_choice') {

                    aiSection.classList.add('hidden');

                    criteriaSection.classList.add('hidden');

                    programmingSection.classList.add('hidden');

                    aiCheckbox.checked = false;

                    programmingLanguage.value = '';

                    programmingLanguage.removeAttribute('required');

                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | Code
                |--------------------------------------------------------------------------
                */

                if (type === 'code') {

                    aiSection.classList.remove('hidden');

                    criteriaSection.classList.remove('hidden');

                    programmingSection.classList.remove('hidden');

                    programmingLanguage.setAttribute('required', 'required');

                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | Essay
                |--------------------------------------------------------------------------
                */

                aiSection.classList.remove('hidden');

                criteriaSection.classList.remove('hidden');

                programmingSection.classList.add('hidden');

                programmingLanguage.value = '';

                programmingLanguage.removeAttribute('required');
            }

            submissionType.addEventListener('change', updateAssignmentFields);

            updateAssignmentFields();
        });
    </script>
