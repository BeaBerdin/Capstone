<x-layouts::app :title="$course->title">

    @php
        $studentId = auth()->id();

        $enrollment = \App\Models\Enrollment::where('student_id', $studentId)
            ->where('course_id', $course->id)
            ->first();

        $quiz = \App\Models\Quiz::where('course_id', $course->id)
            ->where('is_published', true)
            ->first();

        $quizResult = null;

        if ($quiz) {
            $quizResult = \App\Models\QuizResult::where('student_id', $studentId)
                ->where('quiz_id', $quiz->id)
                ->latest()
                ->first();
        }

        $totalLessons = $lessons->count();

        $completedLessonIds = \App\Models\LessonProgress::where('student_id', $studentId)
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->where('status', 'completed')
            ->pluck('lesson_id');

        $completedLessons = $completedLessonIds->count();

        $lessonProgress = $totalLessons > 0
            ? round(($completedLessons / $totalLessons) * 100)
            : 0;

        $allLessonsCompleted = $totalLessons > 0 && $completedLessons >= $totalLessons;
        $passedFinalQuiz = $quizResult && $quizResult->remarks === 'passed';
        $failedFinalQuiz = $quizResult && $quizResult->remarks === 'failed';
        $courseCompleted = $allLessonsCompleted && $passedFinalQuiz;

        $latestRecommendation = \App\Models\AIRecommendation::with('course')
            ->where('student_id', $studentId)
            ->latest()
            ->first();
    @endphp

    <div class="min-h-screen bg-neutral-950 p-6 space-y-6">

        {{-- Hero Banner --}}
        <div class="rounded-2xl border border-purple-500/30 bg-linear-to-r from-purple-700 via-purple-900 to-neutral-900 p-8 shadow-xl">

            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">

                <div>
                    <span class="inline-flex rounded-full bg-purple-500/20 px-4 py-1 text-sm font-semibold text-purple-300">
                        Learning Course
                    </span>

                    <h1 class="mt-4 text-4xl font-bold text-white">
                        {{ $course->title }}
                    </h1>

                    <p class="mt-3 max-w-3xl text-gray-300 leading-relaxed">
                        Complete every lesson, pass the final assessment, unlock your certificate, and receive personalized AI learning recommendations to guide your next course.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-xl border border-purple-500/20 bg-black/20 p-4 text-center">
                        <p class="text-xs uppercase tracking-widest text-gray-400">Lessons</p>
                        <p class="mt-2 text-3xl font-bold text-white">{{ $totalLessons }}</p>
                    </div>
                    <div class="rounded-xl border border-purple-500/20 bg-black/20 p-4 text-center">
                        <p class="text-xs uppercase tracking-widest text-gray-400">Progress</p>
                        <p class="mt-2 text-3xl font-bold text-purple-400">{{ $lessonProgress }}%</p>
                    </div>
                </div>

            </div>
        </div>

        {{-- Lessons Section --}}
        <div class="rounded-2xl border border-purple-500/20 bg-neutral-900 p-6 shadow-xl">

            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white">Course Lessons</h2>
                    <p class="mt-1 text-sm text-gray-400">{{ $completedLessons }} of {{ $totalLessons }} lessons completed</p>
                </div>
                <div class="rounded-xl bg-purple-500/10 px-4 py-2">
                    <span class="text-lg font-bold text-purple-400">{{ $lessonProgress }}%</span>
                </div>
            </div>

            <div class="mb-5 h-2 overflow-hidden rounded-full bg-neutral-800">
                <div class="h-2 rounded-full bg-linear-to-r from-purple-500 to-indigo-500 transition-all duration-700" style="width: {{ $lessonProgress }}%"></div>
            </div>

            @forelse($lessons as $lesson)
                @php
                    $isCompleted = $completedLessonIds->contains($lesson->id);
                @endphp

                <a href="{{ route('student.lesson.view', $lesson) }}"
                   class="mb-3 block rounded-xl border border-neutral-700/50 bg-neutral-800 p-4 transition hover:border-purple-500/30 hover:bg-neutral-750">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $isCompleted ? 'bg-purple-500/20 text-purple-400' : 'bg-neutral-700 text-gray-400' }}">
                                @if($isCompleted)
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                @else
                                    <span class="text-sm font-bold">{{ $lesson->lesson_order }}</span>
                                @endif
                            </div>
                            <div>
                                <p class="font-semibold text-white">{{ $lesson->title }}</p>
                                <p class="text-sm text-gray-400">{{ ucfirst($lesson->lesson_type) }} &bull; {{ $lesson->duration_minutes ?? 0 }} mins</p>
                            </div>
                        </div>

                        @if($isCompleted)
                            <span class="rounded-full bg-purple-500/15 px-3 py-1 text-sm font-semibold text-purple-400 border border-purple-500/20">
                                Completed
                            </span>
                        @else
                            <span class="rounded-full bg-neutral-700 px-3 py-1 text-sm font-semibold text-gray-400 border border-neutral-600">
                                In Progress
                            </span>
                        @endif
                    </div>
                </a>

            @empty
                <div class="rounded-xl border border-dashed border-neutral-700 bg-neutral-800/50 p-8 text-center">
                    <p class="text-sm text-gray-500">No lessons available for this course yet.</p>
                </div>
            @endforelse
        </div>

        {{-- Final Quiz Section --}}
        <div class="rounded-2xl border border-purple-500/20 bg-neutral-900 p-6 shadow-xl">

            <div class="mb-4 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-500/20">
                    <svg class="h-5 w-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white">Final Quiz</h2>
                    @if($quiz)
                        <p class="text-sm text-gray-400">{{ $quiz->title }} &mdash; Passing Score: {{ $quiz->passing_score }}%</p>
                    @endif
                </div>
            </div>

            @if($quiz)

                @if($quizResult)
                    <div class="mt-4 rounded-xl border p-4 {{ $passedFinalQuiz ? 'border-green-500/30 bg-green-500/10 text-green-400' : 'border-red-500/30 bg-red-500/10 text-red-400' }}">
                        <div class="flex items-center gap-2">
                            @if($passedFinalQuiz)
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            @else
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            @endif
                            <span>Latest Score: <strong>{{ $quizResult->percentage }}%</strong> &mdash; <strong>{{ ucfirst($quizResult->remarks) }}</strong></span>
                        </div>
                    </div>
                @endif

                @if($allLessonsCompleted)

                    @if(!$quizResult)
                        <a href="{{ route('student.quiz.take', $quiz) }}"
                           class="mt-5 inline-flex items-center gap-2 rounded-xl bg-purple-600 px-6 py-3 font-semibold text-white shadow-lg transition hover:bg-purple-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Take Quiz
                        </a>

                    @elseif($failedFinalQuiz)
                        <a href="{{ route('student.quiz.take', $quiz) }}"
                           class="mt-4 inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 font-semibold text-white transition hover:bg-red-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            Retake Quiz
                        </a>

                    @else
                        <div class="mt-4 flex items-center gap-2 text-sm text-gray-400">
                            <svg class="h-4 w-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            <span>Want to improve your score?</span>
                            <a href="{{ route('student.quiz.take', $quiz) }}" class="font-semibold text-purple-400 hover:text-purple-300 underline underline-offset-2">
                                Retake Quiz
                            </a>
                        </div>
                    @endif

                @else
                    <div class="mt-4 flex items-center gap-3 rounded-xl border border-yellow-500/30 bg-yellow-500/10 p-4 text-yellow-400">
                        <svg class="h-5 w-5 shrink 0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span>Complete all lessons first before taking the quiz.</span>
                    </div>
                @endif

            @else
                <div class="rounded-xl border border-dashed border-neutral-700 bg-neutral-800/50 p-6 text-center">
                    <p class="text-sm text-gray-500">No quiz available for this course yet.</p>
                </div>
            @endif
        </div>

        {{-- AI Recommendation Section --}}
        @if(!$course->is_free)
            @if($latestRecommendation && $latestRecommendation->course)
                <div class="rounded-2xl border border-purple-500/20 bg-linear-to-br from-purple-900/40 via-neutral-900 to-indigo-900/30 p-6 shadow-xl">

                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-500/20">
                            <span class="text-xl">&#127919;</span>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-purple-400">Next Learning Path</h2>
                            <p class="text-sm text-gray-400">Personalized AI recommendation based on your latest quiz performance.</p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-purple-500/10 bg-black/20 p-5">
                        <h3 class="text-xl font-bold text-white">{{ $latestRecommendation->course->title }}</h3>
                        <p class="mt-1 text-sm text-gray-400">Recommended next course in your learning path.</p>

                        <div class="mt-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-purple-400">Reason</p>
                            <p class="mt-2 leading-relaxed text-gray-300">{{ $latestRecommendation->reason }}</p>
                        </div>

                        <div class="mt-5">
                            <div class="mb-2 flex justify-between">
                                <span class="text-sm text-gray-400">Confidence Score</span>
                                <span class="font-bold text-purple-400">{{ $latestRecommendation->recommendation_score }}%</span>
                            </div>
                            <div class="h-3 w-full overflow-hidden rounded-full bg-neutral-800">
                                <div class="h-3 rounded-full bg-linear-to-r from-purple-500 to-indigo-500" style="width: {{ min($latestRecommendation->recommendation_score, 100) }}%"></div>
                            </div>
                        </div>

                        <a href="{{ route('student.course.show', $latestRecommendation->course) }}"
                           class="mt-5 inline-flex items-center gap-2 rounded-xl bg-purple-600 px-5 py-3 font-semibold text-white transition hover:bg-purple-700">
                            Continue Learning
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                        </a>
                    </div>
                </div>

            @elseif($failedFinalQuiz)
                <div class="rounded-2xl border border-purple-500/20 bg-neutral-900 p-6 shadow-xl">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-500/20">
                            <svg class="h-5 w-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-purple-400">AI Learning Insight</h2>
                            <p class="mt-2 text-sm text-gray-300">Your latest score shows that you may need more foundational review before moving forward. Retake the quiz after reviewing the lessons.</p>
                        </div>
                    </div>
                </div>
            @endif
        @endif

        {{-- Certificate Section --}}
        @if(!$course->is_free && $courseCompleted)
            <div class="rounded-2xl border border-green-500/20 bg-linear-to-br from-green-900/30 to-neutral-900 p-6 shadow-xl">
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-500/20">
                            <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-green-400">Course Completed!</h2>
                            <p class="text-sm text-gray-400">Congratulations! You've completed all lessons and passed the final quiz.</p>
                        </div>
                    </div>
                    <a href="{{ route('student.certificates') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-6 py-3 font-semibold text-white shadow-lg transition hover:bg-green-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        View Certificate
                    </a>
                </div>
            </div>
        @endif

    </div>

</x-layouts::app>