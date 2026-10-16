<x-layouts::app :title="$quiz->title">

{{--
    Student - Take Quiz
    Controller: QuizController@take (shows the quiz) and @submit (grades it).

    Time limit (Manuscript Table 13: time_limit_minutes):
    - $secondsRemaining comes from the server, so refreshing the page does
      not reset the timer.
    - When the countdown reaches zero the answers are submitted automatically.
    - The server checks the time again on submit, so the limit cannot be
      bypassed in the browser.
--}}

@php
    $secondsRemaining = $secondsRemaining ?? null;
    $hasTimeLimit = $secondsRemaining !== null;
    $questionCount = $quiz->questions->count();
@endphp

<div class="mx-auto w-full max-w-3xl space-y-6 px-4 py-6 sm:px-6">

    {{-- HEADER --}}
    <div>
        <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">
            Quiz
        </p>

        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl dark:text-white">
            {{ $quiz->title }}
        </h1>

        <p class="mt-1 text-sm text-slate-500 dark:text-neutral-400">
            {{ $quiz->course->title }}
        </p>

        @if($quiz->description)
            <p class="mt-3 whitespace-pre-line text-sm text-slate-600 dark:text-neutral-300">{{ $quiz->description }}</p>
        @endif
    </div>

    {{-- MESSAGES --}}
    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300" role="status">
            {{ session('success') }}
        </div>
    @endif

    {{-- QUIZ DETAILS --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900">
            <p class="text-xs font-semibold text-slate-500 dark:text-neutral-400">Passing score</p>
            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-white">{{ $quiz->passing_score }}%</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900">
            <p class="text-xs font-semibold text-slate-500 dark:text-neutral-400">Questions</p>
            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-white">{{ $questionCount }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900">
            <p class="text-xs font-semibold text-slate-500 dark:text-neutral-400">Time limit</p>
            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-white">
                {{ $hasTimeLimit ? $quiz->time_limit_minutes . ' min' : 'None' }}
            </p>
        </div>
    </div>

    {{-- COUNTDOWN (stays visible while scrolling) --}}
    @if($hasTimeLimit)
        <div
            id="quizTimer"
            class="sticky top-2 z-20 flex items-center justify-between gap-3 rounded-xl border border-violet-200 bg-violet-50/95 px-4 py-3 shadow-sm backdrop-blur dark:border-violet-900/60 dark:bg-violet-950/80"
            aria-live="polite"
        >
            <span class="text-sm font-semibold text-violet-800 dark:text-violet-200">
                Time remaining
            </span>

            <span id="quizTimerValue" class="font-mono text-lg font-bold text-violet-800 dark:text-violet-100">
                {{ sprintf('%02d:%02d', intdiv($secondsRemaining, 60), $secondsRemaining % 60) }}
            </span>
        </div>
    @endif

    {{-- QUESTIONS --}}
    @if($questionCount === 0)

        <div class="rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500 dark:border-neutral-700 dark:bg-neutral-900">
            This quiz has no questions yet.
        </div>

    @else

        <form
            id="quizForm"
            action="{{ route('student.quiz.submit', $quiz) }}"
            method="POST"
            class="space-y-4"
        >
            @csrf

            @foreach($quiz->questions as $question)

                <fieldset class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">

                    <legend class="sr-only">Question {{ $loop->iteration }}</legend>

                    <p class="font-semibold text-slate-900 dark:text-white">
                        <span class="text-violet-600">{{ $loop->iteration }}.</span>
                        {{ $question->question }}
                    </p>

                    <div class="mt-4 space-y-2">

                        @foreach(['A' => $question->option_a, 'B' => $question->option_b, 'C' => $question->option_c, 'D' => $question->option_d] as $letter => $optionText)

                            {{-- Options C and D are optional in the database, so empty ones are hidden. --}}
                            @if(filled($optionText))

                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50 has-[:checked]:border-violet-400 has-[:checked]:bg-violet-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800 dark:has-[:checked]:bg-violet-950/40">
                                    <input
                                        type="radio"
                                        name="question_{{ $question->id }}"
                                        value="{{ $letter }}"
                                        class="mt-0.5"
                                        @checked(old('question_' . $question->id) === $letter)
                                    >
                                    <span>
                                        <span class="font-semibold">{{ $letter }}.</span>
                                        {{ $optionText }}
                                    </span>
                                </label>

                            @endif

                        @endforeach

                    </div>

                </fieldset>

            @endforeach

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a
                    href="{{ route('student.learn.course', $quiz->course) }}"
                    class="text-center text-sm font-semibold text-slate-500 hover:text-slate-700 dark:text-neutral-400"
                >
                    Back to course
                </a>

                <button
                    id="quizSubmitButton"
                    type="submit"
                    class="rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    Submit Quiz
                </button>
            </div>
        </form>

    @endif

</div>

<script>
    (function () {
        const form = document.getElementById('quizForm');
        const button = document.getElementById('quizSubmitButton');

        if (!form) {
            return;
        }

        let submitting = false;

        function lockForm() {
            submitting = true;

            if (button) {
                button.disabled = true;
                button.textContent = 'Submitting...';
            }
        }

        // Manual submit: warn about unanswered questions, prevent double submit.
        form.addEventListener('submit', function (event) {
            if (submitting) {
                event.preventDefault();
                return;
            }

            const names = new Set(
                Array.from(form.querySelectorAll('input[type=radio]')).map(function (input) {
                    return input.name;
                })
            );

            let unanswered = 0;

            names.forEach(function (name) {
                if (!form.querySelector('input[name="' + name + '"]:checked')) {
                    unanswered++;
                }
            });

            if (unanswered > 0 && !confirm('You have ' + unanswered + ' unanswered question(s). Submit anyway?')) {
                event.preventDefault();
                return;
            }

            lockForm();
        });

        // Countdown and automatic submission.
        const secondsRemaining = @json($secondsRemaining);
        const timerValue = document.getElementById('quizTimerValue');
        const timerBox = document.getElementById('quizTimer');

        if (secondsRemaining === null || !timerValue) {
            return;
        }

        const endTime = Date.now() + secondsRemaining * 1000;

        function pad(number) {
            return String(number).padStart(2, '0');
        }

        function tick() {
            const left = Math.max(0, Math.round((endTime - Date.now()) / 1000));

            timerValue.textContent = pad(Math.floor(left / 60)) + ':' + pad(left % 60);

            if (left <= 60 && timerBox) {
                timerBox.classList.add('border-red-300', 'bg-red-50/95');
                timerValue.classList.add('text-red-700');
            }

            if (left <= 0) {
                clearInterval(timer);

                if (!submitting) {
                    lockForm();
                    form.submit(); // skips the "unanswered" confirmation
                }
            }
        }

        const timer = setInterval(tick, 1000);

        tick();
    })();
</script>

</x-layouts::app>
