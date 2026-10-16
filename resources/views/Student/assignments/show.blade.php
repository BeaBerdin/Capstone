<x-layouts::app :title="$assignment->title">

    <div class="mx-auto max-w-5xl space-y-6">

        {{-- SUCCESS / ERROR MESSAGES --}}
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-semibold">Please fix the following:</p>

                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- HEADER --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">

                <div>
                    <p class="text-sm font-medium text-violet-600">
                        Assignment
                    </p>

                    <h1 class="mt-1 text-2xl font-bold text-slate-900">
                        {{ $assignment->title }}
                    </h1>

                    @if($assignment->course)
                        <p class="mt-2 text-sm text-slate-500">
                            {{ $assignment->course->title }}
                        </p>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2">

                    @if($assignment->submission_type === 'code')

                        <span class="rounded-full bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white">
                            Code Submission
                        </span>

                        @if($assignment->programming_language)
                            <span class="rounded-full bg-violet-100 px-3 py-1.5 text-xs font-semibold text-violet-700">
                                {{ $assignment->programmingLanguageLabel() }}
                            </span>
                        @endif

                    @elseif($assignment->submission_type === 'multiple_choice')

                        <span class="rounded-full bg-blue-100 px-3 py-1.5 text-xs font-semibold text-blue-700">
                            Multiple Choice
                        </span>

                    @else

                        <span class="rounded-full bg-violet-100 px-3 py-1.5 text-xs font-semibold text-violet-700">
                            Written Response
                        </span>

                    @endif

                </div>

            </div>


            {{-- ASSIGNMENT INFORMATION --}}
            <div class="mt-6 grid gap-4 sm:grid-cols-3">

                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Maximum Score
                    </p>

                    <p class="mt-1 text-lg font-bold text-slate-800">
                        {{ $assignment->max_score }}
                    </p>
                </div>

                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Due Date
                    </p>

                    <p class="mt-1 text-sm font-semibold text-slate-800">
                        {{ $assignment->due_date?->format('M d, Y h:i A') ?? 'No deadline' }}
                    </p>
                </div>

                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        AI Evaluation
                    </p>

                    <p class="mt-1 text-sm font-semibold text-slate-800">
                        @if($assignment->ai_evaluation_enabled)
                            Enabled
                        @else
                            Not enabled
                        @endif
                    </p>
                </div>

            </div>

        </div>


        {{-- INSTRUCTIONS --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex items-center gap-3">

                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-100 text-violet-700">
                    ℹ
                </div>

                <h2 class="text-lg font-bold text-slate-900">
                    Instructions
                </h2>

            </div>

            <div class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-600">
                {{ $assignment->description }}
            </div>

            @if($assignment->evaluation_criteria)

                <div class="mt-6 rounded-xl border border-violet-100 bg-violet-50 p-4">

                    <p class="text-sm font-bold text-violet-900">
                        Evaluation Criteria
                    </p>

                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-violet-800">
                        {{ $assignment->evaluation_criteria }}
                    </p>

                </div>

            @endif

        </div>


        {{-- EXISTING SUBMISSION --}}
        @if($submission)

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Your Submission
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Submitted
                            {{ $submission->submitted_at?->format('M d, Y h:i A') ?? 'recently' }}
                        </p>
                    </div>

                    <div>

                        @if($submission->status === 'graded')

                            <span class="rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                                Graded
                            </span>

                        @else

                            <span class="rounded-full bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-700">
                                Submitted
                            </span>

                        @endif

                    </div>

                </div>


                @if($assignment->submission_type === 'code')

                    {{-- EXISTING SOURCE FILE --}}
                    @if($submission->file_path)
                        <p class="mt-5 text-sm text-slate-600">
                            Submitted source file:
                            <strong class="font-mono">{{ basename($submission->file_path) }}</strong>
                        </p>
                    @endif

                    {{-- EXISTING CODE --}}
                    @if($submission->answer_text)
                    <div class="mt-5 overflow-hidden rounded-xl border border-slate-700 bg-slate-950">

                        <div class="flex items-center justify-between border-b border-slate-800 bg-slate-900 px-4 py-2.5">

                            <div class="flex items-center gap-2">

                                <span class="h-2.5 w-2.5 rounded-full bg-red-400"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-yellow-400"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-green-400"></span>

                            </div>

                            <span class="text-xs font-medium text-slate-400">
                                {{ $assignment->programmingLanguageLabel() ?? 'Code' }}
                            </span>

                        </div>

                        <pre class="overflow-x-auto p-5 font-mono text-sm leading-6 text-slate-100">{{ $submission->answer_text }}</pre>

                    </div>
                    @endif


                @else

                    {{-- EXISTING WRITTEN ANSWER --}}
                    @if($submission->answer_text)

                        <div class="mt-5 rounded-xl bg-slate-50 p-5">

                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                Your Answer
                            </p>

                            <div class="mt-3 whitespace-pre-wrap wrap-break-word text-sm leading-7 text-slate-700">{{ $submission->answer_text }}</div>

                        </div>

                    @endif

                @endif


                {{-- SCORE --}}
                @if($submission->score !== null)

                    <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-5">

                        <p class="text-xs font-bold uppercase tracking-wide text-emerald-600">
                            Final Score
                        </p>

                        <p class="mt-1 text-3xl font-bold text-emerald-800">
                            {{ $submission->score }}
                            <span class="text-base font-medium">
                                / {{ $assignment->max_score }}
                            </span>
                        </p>

                    </div>

                @endif


                {{-- TEACHER FEEDBACK --}}
                @if($submission->feedback)

                    <div class="mt-5 rounded-xl border border-slate-200 bg-white p-5">

                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                            Teacher Feedback
                        </p>

                        <p class="mt-3 whitespace-pre-wrap wrap-break-word text-sm leading-6 text-slate-700">{{ $submission->feedback }}</p>

                    </div>

                @endif

            </div>

        @endif


        {{-- SUBMISSION FORM --}}
        @if(
            ! $submission
            || $submission->status !== 'graded'
        )

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="mb-6">

                    <h2 class="text-xl font-bold text-slate-900">
                        {{ $submission ? 'Resubmit Assignment' : 'Submit Assignment' }}
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Make sure your answer is complete before submitting.
                    </p>

                </div>


                <form
                    method="POST"
                    action="{{ route('student.assignments.submit', $assignment) }}"
                    enctype="multipart/form-data"
                    class="space-y-6"
                >

                    @csrf


                    {{-- HOW TO ANSWER (same wording as the teacher's choice) --}}
                    @if($assignment->submission_type !== 'multiple_choice')
                        <p class="text-xs text-slate-500">
                            How to answer:
                            <strong class="font-semibold text-slate-700">{{ $assignment->answerFormatLabel() }}</strong>
                        </p>
                    @endif

                    {{-- CODE SUBMISSION --}}
                    @if($assignment->submission_type === 'code')

                        @if($assignment->acceptsTypedAnswer())
                        <div>

                            <div class="flex items-center justify-between gap-4">

                                <div>

                                    <label
                                        for="answer_text"
                                        class="text-sm font-semibold text-slate-700"
                                    >
                                        Code Submission
                                    </label>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Write your solution below and submit it for evaluation.
                                    </p>

                                </div>

                                <span class="rounded-full bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white">
                                    {{ strtoupper($assignment->programmingLanguageLabel() ?? 'Code') }}
                                </span>

                            </div>


                            <div class="mt-3 overflow-hidden rounded-xl border border-slate-700 bg-slate-950">

                                <div class="flex items-center justify-between border-b border-slate-800 bg-slate-900 px-4 py-2.5">

                                    <div class="flex items-center gap-2">

                                        <span class="h-2.5 w-2.5 rounded-full bg-red-400"></span>
                                        <span class="h-2.5 w-2.5 rounded-full bg-yellow-400"></span>
                                        <span class="h-2.5 w-2.5 rounded-full bg-green-400"></span>

                                    </div>

                                    <span class="text-[11px] font-medium text-slate-400">
                                        {{ $assignment->programmingLanguageLabel() ?? 'Code' }}
                                    </span>

                                </div>


                                <textarea
                                    id="answer_text"
                                    name="answer_text"
                                    rows="18"
                                    maxlength="20000"
                                    spellcheck="false"
                                    class="block w-full resize-y border-0 bg-slate-950 px-5 py-4 font-mono text-sm leading-6 text-slate-100 outline-none placeholder:text-slate-600 focus:ring-0"
                                    placeholder="Write your {{ $assignment->programmingLanguageLabel() ?? '' }} code here..."
                                >{{-- The newline below is required: browsers drop the first line break inside a textarea. --}}
{{ old('answer_text', $submission?->answer_text) }}</textarea>

                            </div>


                            <div class="mt-2 flex items-center justify-between text-xs text-slate-400">

                                <span>
                                    Language:
                                    <strong class="text-slate-600">
                                        {{ $assignment->programmingLanguageLabel() ?? 'Not specified' }}
                                    </strong>
                                </span>

                                <span>
                                    Maximum 20,000 characters
                                </span>

                            </div>

                        </div>
                        @endif


                        {{-- OR ONE SOURCE FILE --}}
                        @if($assignment->acceptsFileUpload())
                        <div>

                            <label
                                for="submission_file"
                                class="text-sm font-semibold text-slate-700"
                            >
                                {{ $assignment->acceptsTypedAnswer() ? 'Or upload one source file' : 'Upload one source file' }}
                            </label>

                            <input
                                id="submission_file"
                                type="file"
                                name="submission_file"
                                accept="{{ implode(',', array_map(fn ($extension) => '.'.$extension, $assignment->sourceFileExtensions())) }}"
                                class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600"
                            />

                            <p class="mt-2 text-xs text-slate-400">
                                Accepted: {{ implode(', ', array_map(fn ($extension) => '.'.$extension, $assignment->sourceFileExtensions())) }}.
                                Maximum 25 MB.
                                @if($assignment->acceptsTypedAnswer())
                                    Typing code without a file replaces an earlier uploaded file.
                                @endif
                            </p>

                        </div>
                        @endif


                    {{-- WRITTEN / ESSAY --}}
                    @else

                        @if($assignment->acceptsTypedAnswer())
                        <div>

                            <label
                                for="answer_text"
                                class="text-sm font-semibold text-slate-700"
                            >
                                Written Answer
                            </label>

                            <textarea
                                id="answer_text"
                                name="answer_text"
                                rows="9"
                                maxlength="20000"
                                class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                                placeholder="Write your answer here..."
                            >{{-- The newline below is required: browsers drop the first line break inside a textarea. --}}
{{ old('answer_text', $submission?->answer_text) }}</textarea>

                        </div>
                        @endif


                        {{-- FILE UPLOAD --}}
                        @if($assignment->acceptsFileUpload())
                        <div>

                            <label
                                for="submission_file"
                                class="text-sm font-semibold text-slate-700"
                            >
                                Attachment
                            </label>

                            <input
                                id="submission_file"
                                type="file"
                                name="submission_file"
                                accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.jpg,.jpeg,.png"
                                class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600"
                            />

                            <p class="mt-2 text-xs text-slate-400">
                                Accepted: PDF, DOC, DOCX, PPT, PPTX, TXT, JPG, JPEG, PNG.
                                Maximum 25 MB.
                            </p>

                            @if($submission?->file_path)

                                <p class="mt-2 text-xs text-slate-500">
                                    A previous attachment is already on your submission.
                                    Uploading a new file will replace it.
                                </p>

                            @endif

                        </div>
                        @endif

                    @endif


                    {{-- SUBMIT BUTTON --}}
                    <div class="flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-violet-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-violet-700"
                        >
                            {{ $submission ? 'Resubmit Assignment' : 'Submit Assignment' }}
                        </button>

                    </div>

                </form>

            </div>

        @endif

    </div>

</x-layouts::app>