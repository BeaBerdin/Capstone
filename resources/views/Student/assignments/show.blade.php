<x-layouts::app :title="$assignment->title">
@include('partials.soft3d')

<div class="sad-page min-h-screen">
    <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <div class="mx-auto max-w-5xl space-y-6">

            <x-pw.form-header
                :crumbs="$assignment->is_activity && $assignment->lesson ? [$assignment->course->title => route('student.learn.course', $assignment->course), 'Activity' => $assignment->lesson->title] : ['Assignments' => route('student.assignments.index'), $assignment->title => null]"
                :title="$assignment->title"
                :subtitle="$assignment->course?->title"
                :back-url="route('student.assignments.index')"
                back-label="Back to Assignments"
                :required="false"
            />

            {{-- SUCCESS / ERROR MESSAGES --}}
            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700" role="alert">
                    {{ session('error') }}
                </div>
            @endif


            {{-- ASSIGNMENT DETAILS AND INSTRUCTIONS --}}
            <section class="sad-card overflow-hidden">
                <x-pw.form-section title="Assignment Details" first>
                    <x-slot:icon>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6M8 13h8M8 17h6"></path></svg>
                    </x-slot:icon>

                    <div class="flex flex-wrap gap-2">
                        @if($assignment->is_activity)
                            <span class="rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-700">Activity</span>
                        @elseif($assignment->submission_type === 'code')
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

                    <dl class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 px-4 py-3">
                            <dt class="text-xs font-bold uppercase tracking-[.08em] text-slate-500">Maximum Score</dt>
                            <dd class="mt-1 text-lg font-bold text-slate-800">{{ $assignment->max_score }}</dd>
                        </div>

                        <div class="rounded-xl bg-slate-50 px-4 py-3">
                            <dt class="text-xs font-bold uppercase tracking-[.08em] text-slate-500">AI Evaluation</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">
                                {{ $assignment->ai_evaluation_enabled ? 'Enabled' : 'Not enabled' }}
                            </dd>
                        </div>
                    </dl>
                </x-pw.form-section>

                <x-pw.form-section title="Instructions">
                    <x-slot:icon>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v5M12 8h.01"></path></svg>
                    </x-slot:icon>

                    <div class="whitespace-pre-line text-sm leading-7 text-slate-600">{{ $assignment->description }}</div>

                    @if($assignment->evaluation_criteria)
                        <div class="rounded-2xl border border-violet-100 bg-violet-50/60 p-4">
                            <p class="text-sm font-bold text-violet-900">Evaluation Criteria</p>
                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-violet-800">{{ $assignment->evaluation_criteria }}</p>
                        </div>
                    @endif
                </x-pw.form-section>
            </section>


            {{-- EXISTING SUBMISSION --}}
            @if($submission)
                <section class="sad-card overflow-hidden">
                    <x-pw.form-section title="Your Submission" first>
                        <x-slot:icon>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z"></path><path d="m8.5 12.5 2.5 2.5 4.5-5"></path></svg>
                        </x-slot:icon>

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-xs text-slate-500">
                                Submitted
                                {{ $submission->submitted_at?->format('M d, Y h:i A') ?? 'recently' }}
                            </p>

                            @if($submission->status === 'graded')
                                <span class="rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-700">Graded</span>
                            @else
                                <span class="rounded-full bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-700">Submitted</span>
                            @endif
                        </div>

                        @if($assignment->submission_type === 'code')

                            {{-- EXISTING SOURCE FILE --}}
                            @if($submission->file_path)
                                <p class="text-sm text-slate-600">
                                    Submitted source file:
                                    <strong class="font-mono">{{ basename($submission->file_path) }}</strong>
                                </p>
                            @endif

                            {{-- EXISTING CODE --}}
                            @if($submission->answer_text)
                                <div class="overflow-hidden rounded-xl border border-slate-700 bg-slate-950">
                                    <div class="flex items-center justify-between border-b border-slate-800 bg-slate-900 px-4 py-2.5">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full bg-red-400"></span>
                                            <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                                            <span class="h-2.5 w-2.5 rounded-full bg-green-400"></span>
                                        </div>

                                        <span class="text-xs font-medium text-slate-500">
                                            {{ $assignment->programmingLanguageLabel() ?? 'Code' }}
                                        </span>
                                    </div>

                                    <pre class="overflow-x-auto p-5 font-mono text-sm leading-6 text-slate-100">{{ $submission->answer_text }}</pre>
                                </div>
                            @endif

                        @elseif($submission->answer_text)

                            {{-- EXISTING WRITTEN ANSWER --}}
                            <div class="rounded-xl bg-slate-50 p-5">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Your Answer</p>
                                <div class="mt-3 whitespace-pre-wrap wrap-break-word text-sm leading-7 text-slate-700">{{ $submission->answer_text }}</div>
                            </div>

                        @endif

                        {{-- SCORE --}}
                        @if($submission->score !== null)
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                                <p class="text-xs font-bold uppercase tracking-wide text-emerald-600">Final Score</p>
                                <p class="mt-1 text-3xl font-bold text-emerald-800">
                                    {{ $submission->score }}
                                    <span class="text-base font-medium">/ {{ $assignment->max_score }}</span>
                                </p>
                            </div>
                        @endif

                        {{-- TEACHER FEEDBACK --}}
                        @if($submission->feedback)
                            <div class="rounded-xl border border-slate-200 bg-white p-5">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Teacher Feedback</p>
                                <p class="mt-3 whitespace-pre-wrap wrap-break-word text-sm leading-6 text-slate-700">{{ $submission->feedback }}</p>
                            </div>
                        @endif
                    </x-pw.form-section>
                </section>
            @endif


            {{-- SUBMISSION FORM (also after a final grade below passing: resubmit to improve) --}}
            @php
                $pwFailedGrade = $submission && \App\Support\CertificateEligibility::failedFinalGrade($submission, $assignment);
            @endphp
            @if ($pwFailedGrade)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" data-test="resubmit-to-improve">
                    Your score is below the passing score ({{ (int) round(\App\Support\CertificateEligibility::PASSING_RATIO * 100) }}% of the maximum). You can resubmit to improve it.
                </div>
            @endif
            @if(! $submission || $submission->status !== 'graded' || $pwFailedGrade)
                <form
                    method="POST"
                    action="{{ route('student.assignments.submit', $assignment) }}"
                    enctype="multipart/form-data"
                    class="sad-card overflow-hidden"
                >
                    @csrf

                    <x-pw.form-errors />

                    <x-pw.form-section :title="$submission ? 'Resubmit Assignment' : 'Submit Assignment'" first>
                        <x-slot:icon>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 16V4m0 0-4 4m4-4 4 4"></path><path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"></path></svg>
                        </x-slot:icon>

                        <p class="-mt-2 text-sm text-slate-500">
                            Make sure your answer is complete before submitting.
                        </p>

                        {{-- HOW TO ANSWER (same wording as the teacher's choice) --}}
                        @if($assignment->submission_type !== 'multiple_choice')
                            <p class="rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-500">
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
                                            <label for="answer_text" class="text-sm font-semibold text-slate-700">Code Submission</label>
                                            <p class="mt-1 text-xs text-slate-500">Write your solution below and submit it for evaluation.</p>
                                        </div>

                                        <span class="rounded-full bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white">
                                            {{ strtoupper($assignment->programmingLanguageLabel() ?? 'Code') }}
                                        </span>
                                    </div>

                                    <div class="mt-3 overflow-hidden rounded-xl border border-slate-700 bg-slate-950">
                                        <div class="flex items-center justify-between border-b border-slate-800 bg-slate-900 px-4 py-2.5">
                                            <div class="flex items-center gap-2">
                                                <span class="h-2.5 w-2.5 rounded-full bg-red-400"></span>
                                                <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                                                <span class="h-2.5 w-2.5 rounded-full bg-green-400"></span>
                                            </div>

                                            <span class="text-xs font-medium text-slate-500">
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

                                    <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
                                        <span>
                                            Language:
                                            <strong class="text-slate-600">{{ $assignment->programmingLanguageLabel() ?? 'Not specified' }}</strong>
                                        </span>
                                        <span>Maximum 20,000 characters</span>
                                    </div>
                                </div>
                            @endif

                            {{-- OR ONE SOURCE FILE --}}
                            @if($assignment->acceptsFileUpload())
                                <x-pw.field
                                    :label="$assignment->acceptsTypedAnswer() ? 'Or upload one source file' : 'Upload one source file'"
                                    for="submission_file"
                                >
                                    <input
                                        id="submission_file"
                                        type="file"
                                        name="submission_file"
                                        accept="{{ implode(',', array_map(fn ($extension) => '.'.$extension, $assignment->sourceFileExtensions())) }}"
                                        class="sad-field"
                                    />

                                    <p class="mt-1.5 text-xs text-slate-500">
                                        Accepted: {{ implode(', ', array_map(fn ($extension) => '.'.$extension, $assignment->sourceFileExtensions())) }}.
                                        Maximum 25 MB.
                                        @if($assignment->acceptsTypedAnswer())
                                            Typing code without a file replaces an earlier uploaded file.
                                        @endif
                                    </p>
                                </x-pw.field>
                            @endif

                        {{-- WRITTEN / ESSAY --}}
                        @else

                            @if($assignment->acceptsTypedAnswer())
                                <x-pw.field label="Written Answer" for="answer_text">
                                    <textarea
                                        id="answer_text"
                                        name="answer_text"
                                        rows="9"
                                        maxlength="20000"
                                        class="sad-field"
                                        placeholder="Write your answer here..."
                                    >{{-- The newline below is required: browsers drop the first line break inside a textarea. --}}
{{ old('answer_text', $submission?->answer_text) }}</textarea>
                                </x-pw.field>
                            @endif

                            {{-- FILE UPLOAD --}}
                            @if($assignment->acceptsFileUpload())
                                <x-pw.field label="Attachment" for="submission_file">
                                    <input
                                        id="submission_file"
                                        type="file"
                                        name="submission_file"
                                        accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.jpg,.jpeg,.png"
                                        class="sad-field"
                                    />

                                    <p class="mt-1.5 text-xs text-slate-500">
                                        Accepted: PDF, DOC, DOCX, PPT, PPTX, TXT, JPG, JPEG, PNG.
                                        Maximum 25 MB.
                                        @if($submission?->file_path)
                                            A previous attachment is already on your submission.
                                            Uploading a new file will replace it.
                                        @endif
                                    </p>
                                </x-pw.field>
                            @endif

                        @endif
                    </x-pw.form-section>

                    <x-pw.form-footer
                        :submit="$submission ? 'Resubmit Assignment' : 'Submit Assignment'"
                        :cancel-url="route('student.assignments.index')"
                    />
                </form>
            @endif

            {{-- Previous / next lesson (also shown right after a successful submission). --}}
            @isset($lessonNav)
                @if ($lessonNav)
                    @include('Student.partials.lesson-nav', ['nav' => $lessonNav])
                @endif
            @endisset

        </div>
    </main>
</div>

</x-layouts::app>
