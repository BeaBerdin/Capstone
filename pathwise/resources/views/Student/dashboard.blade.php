<x-layouts::app :title="'Student Dashboard'">

@php
    $scoreStatus = 'No Data';
    $scoreBadgeClass = 'bg-gray-100 text-gray-700';
    $scoreTextClass = 'text-gray-400';

    if ($averageScore >= 90) {
        $scoreStatus = '🏆 Mastery';
        $scoreBadgeClass = 'bg-yellow-100 text-yellow-700';
        $scoreTextClass = 'text-yellow-400';
    } elseif ($averageScore >= 75) {
        $scoreStatus = '🟢 Proficient';
        $scoreBadgeClass = 'bg-green-100 text-green-700';
        $scoreTextClass = 'text-green-400';
    } elseif ($averageScore >= 60) {
        $scoreStatus = '🟡 Developing';
        $scoreBadgeClass = 'bg-yellow-100 text-yellow-700';
        $scoreTextClass = 'text-yellow-400';
    } elseif ($averageScore > 0) {
        $scoreStatus = '🔴 Needs Reinforcement';
        $scoreBadgeClass = 'bg-red-100 text-red-700';
        $scoreTextClass = 'text-red-400';
    }

    $latestEnrollments = $enrollments->take(3);
    $latestQuizResults = $quizResults->take(3);
@endphp

<div class="space-y-5">

    {{-- Hero Banner --}}
    <div class="rounded-2xl border border-purple-500/30 bg-linear-to-r from-purple-900 via-neutral-900 to-neutral-900 p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-purple-300">
                    PathWise Student Learning Portal
                </p>

                <h1 class="mt-2 text-3xl font-bold text-white">
                    Welcome back, {{ auth()->user()->name }}
                </h1>

                <p class="mt-2 max-w-2xl text-sm text-gray-300">
                    Track your learning progress, quiz performance, certificates, and AI-powered recommendations.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('student.marketplace') }}"
                   class="rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                    Browse Courses
                </a>

                <a href="{{ route('student.learning-paths') }}"
                   class="rounded-xl bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700">
                    Learning Paths
                </a>

                <a href="{{ route('student.my-courses') }}"
                   class="rounded-xl border border-neutral-700 bg-neutral-800 px-4 py-2 text-sm font-semibold text-white transition hover:border-neutral-500 hover:bg-neutral-700">
                    My Courses
                </a>

                <a href="{{ route('student.recommendations') }}"
                   class="rounded-xl border border-neutral-700 bg-neutral-800 px-4 py-2 text-sm font-semibold text-white transition hover:border-neutral-500 hover:bg-neutral-700">
                    AI Recommendations
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="grid gap-4 md:grid-cols-4">

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
            <p class="text-sm text-gray-400">Courses Enrolled</p>
            <h2 class="mt-2 text-3xl font-bold text-white">{{ $enrollments->count() }}</h2>
            <p class="mt-1 text-xs text-gray-400">Total courses joined</p>
        </div>

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
            <p class="text-sm text-gray-400">Quizzes Taken</p>
            <h2 class="mt-2 text-3xl font-bold text-white">{{ $quizzesTaken }}</h2>
            <p class="mt-1 text-xs text-gray-400">Assessment attempts</p>
        </div>

        <div class="rounded-2xl border border-green-500/40 bg-neutral-900 p-5">
    <p class="text-sm text-green-400">Certificates Earned</p>
    <h2 class="mt-2 text-3xl font-bold text-green-400">{{ $certificatesEarned }}</h2>
    <p class="mt-1 text-xs text-gray-300">Completed achievements</p>
</div>

        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-5">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm text-gray-400">Average Score</p>
                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $scoreBadgeClass }}">
                    {{ $scoreStatus }}
                </span>
            </div>

            <h2 class="mt-2 text-3xl font-bold text-white">{{ $averageScore }}%</h2>

            <div class="mt-3 h-2 w-full rounded-full bg-neutral-800">
                <div class="h-2 rounded-full bg-linear-to-r from-purple-600 to-blue-500"
                     style="width: {{ min($averageScore, 100) }}%">
                </div>
            </div>
        </div>

    </div>

    {{-- Recommendation + AI Analysis Row --}}
    <div class="grid items-start gap-4 lg:grid-cols-3">

        {{-- Recommended Course Card — solid bg so text is always readable --}}
        <div class="rounded-2xl border border-purple-500/20 bg-neutral-900 p-6 lg:col-span-2">

            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-white">
                        🎯 Recommended Next Course
                    </h3>
                    <p class="text-sm text-gray-300">
                        Personalized recommendation based on your latest quiz performance.
                    </p>
                </div>

                <a href="{{ route('student.recommendations') }}"
                   class="text-sm font-semibold text-purple-300 hover:text-purple-200">
                    View All
                </a>
            </div>

            @if($recommendedCourse && $recommendedCourse->course)

                <div class="rounded-2xl bg-neutral-800 p-5">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">

                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-purple-400">
                                    Next Learning Path
                                </p>
                                <span class="rounded-full border border-purple-500/30 bg-purple-500/10 px-2 py-1 text-[11px] font-semibold text-purple-300">
                                    AI Generated
                                </span>
                            </div>

                            <h4 class="mt-2 text-2xl font-bold text-white">
                                {{ $recommendedCourse->course->title }}
                            </h4>

                            <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-gray-300">
                                {{ $recommendedCourse->reason }}
                            </p>

                            <a href="{{ route('student.course.show', $recommendedCourse->course) }}"
                               class="mt-4 inline-flex rounded-xl bg-linear-to-r from-purple-500 to-indigo-600 px-5 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                                Start Learning →
                            </a>
                        </div>

                        <div class="w-full rounded-xl border border-purple-500/20 bg-neutral-900 p-4 lg:w-64">
                            <p class="text-sm text-gray-400">Confidence Score</p>

                            <h5 class="mt-2 text-3xl font-bold text-purple-300">
                                {{ $recommendedCourse->recommendation_score }}%
                            </h5>

                            <div class="mt-4 h-3 w-full overflow-hidden rounded-full bg-neutral-700">
                                <div class="h-3 rounded-full bg-linear-to-r from-purple-500 to-indigo-400"
                                     style="width: {{ min($recommendedCourse->recommendation_score, 100) }}%">
                                </div>
                            </div>

                            <p class="mt-3 text-xs text-gray-400">
                                Based on your latest assessment result.
                            </p>
                        </div>

                    </div>
                </div>

            @else
                <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-purple-500/30 bg-neutral-800 px-6 py-10 text-center">
                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-purple-500/20 text-2xl">
                        🎯
                    </div>
                    <p class="text-sm font-semibold text-white">
                        No AI recommendation yet
                    </p>
                    <p class="mt-1 max-w-sm text-xs text-gray-300">
                        Complete a quiz to let PathWise AI analyze your performance and recommend your next course.
                    </p>
                    <a href="{{ route('student.my-courses') }}"
                       class="mt-4 inline-flex rounded-xl bg-linear-to-r from-purple-500 to-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                        Go to My Courses
                    </a>
                </div>
            @endif

        </div>

        {{-- AI Learning Analysis — solid bg --}}
        <div class="rounded-2xl border border-purple-500/30 bg-neutral-900 p-6">
            <h3 class="text-lg font-semibold text-white">
                🧠 AI Learning Analysis
            </h3>

            <div class="mt-4 space-y-3">

                <div class="rounded-xl bg-neutral-800 p-4">
                    <p class="text-xs uppercase tracking-wide text-gray-400">
                        Learning Status
                    </p>
                    <p class="mt-1 font-bold {{ $scoreTextClass }}">
                        {{ $scoreStatus }}
                    </p>
                </div>

                @if($strongestCategory)
                    <div class="rounded-xl border border-green-500/30 bg-green-500/10 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-green-400">
                            Strength
                        </p>
                        <p class="mt-1 font-bold text-green-300">
                            {{ $strongestCategory->category_name }}
                        </p>
                        <p class="mt-1 text-xs text-gray-300">
                            Average Score: {{ $strongestCategory->average_score }}%
                        </p>
                    </div>
                @endif

                @if($weakestCategory)
                    <div class="rounded-xl border border-red-500/30 bg-red-500/10 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-red-400">
                            Needs Improvement
                        </p>
                        <p class="mt-1 font-bold text-red-300">
                            {{ $weakestCategory->category_name }}
                        </p>
                        <p class="mt-1 text-xs text-gray-300">
                            Average Score: {{ $weakestCategory->average_score }}%
                        </p>
                    </div>
                @endif

                @if(!$strongestCategory && !$weakestCategory)
                    <p class="rounded-xl bg-neutral-800 p-4 text-sm text-gray-300">
                        Take quizzes to generate personalized learning insights.
                    </p>
                @endif

            </div>
        </div>

    </div>

    {{-- Continue Learning + Quiz Trend Row --}}
    <div class="grid gap-4 lg:grid-cols-2">

        {{-- Continue Learning — solid bg --}}
        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-white">
                        Continue Learning
                    </h3>
                    <p class="mt-1 text-sm text-gray-400">
                        Your latest active or completed courses.
                    </p>
                </div>
                <a href="{{ route('student.my-courses') }}"
                   class="text-sm font-semibold text-purple-300 hover:text-purple-200">
                    View All
                </a>
            </div>

            <div class="mt-5 space-y-3">
                @forelse($latestEnrollments as $enrollment)

                    <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4">
                        <div class="flex justify-between gap-4">
                            <h4 class="font-semibold text-white">
                                {{ $enrollment->course->title ?? 'Course unavailable' }}
                            </h4>
                            <span class="text-sm text-gray-300">
                                {{ $enrollment->progress_percentage }}%
                            </span>
                        </div>

                        <div class="mt-3 h-2 w-full rounded bg-neutral-700">
                            <div class="h-2 rounded bg-linear-to-r from-purple-500 to-indigo-500"
                                 style="width: {{ $enrollment->progress_percentage }}%">
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-3">
                            <p class="text-sm text-gray-300">
                                Status: {{ ucfirst($enrollment->status) }}
                            </p>

                            @if($enrollment->status === 'completed')
                                <a href="{{ route('student.certificates') }}"
                                   class="rounded-lg bg-green-600 px-3 py-2 text-xs font-semibold text-white hover:bg-green-700">
                                    Certificate
                                </a>
                            @else
                                <a href="{{ route('student.learn.course', $enrollment->course) }}"
                                   class="rounded-lg bg-linear-to-r from-purple-500 to-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:opacity-90">
                                    Continue
                                </a>
                            @endif
                        </div>
                    </div>

                @empty
                    <p class="rounded-xl border border-neutral-700 bg-neutral-800 p-4 text-sm text-gray-300">
                        You have not enrolled in any courses yet.
                    </p>
                @endforelse
            </div>
        </div>

        {{-- Quiz Performance Trend — solid bg --}}
        <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-6">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-white">
                        Quiz Performance Trend
                    </h3>
                    <p class="text-sm text-gray-400">
                        Latest quiz attempts summary.
                    </p>
                </div>
                <p class="text-sm text-gray-400">
                    {{ count($chartScores) }} attempts
                </p>
            </div>

            @if(count($chartScores) > 0)
                <div class="h-56">
                    <canvas id="quizTrendChart"></canvas>
                </div>
            @else
                <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-6 text-center text-sm text-gray-300">
                    No quiz attempts yet.
                </div>
            @endif
        </div>

    </div>

    {{-- Recent Quiz History — solid bg --}}
    <div class="rounded-2xl border border-neutral-700 bg-neutral-900 p-6">

        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-white">
                    Recent Quiz History
                </h3>
                <p class="mt-1 text-sm text-gray-400">
                    Latest assessment results.
                </p>
            </div>
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-3">
            @forelse($latestQuizResults as $result)

                <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4">
                    <p class="font-semibold text-white">
                        {{ $result->quiz->title ?? 'Quiz' }}
                    </p>
                    <p class="mt-1 text-sm text-gray-400">
                        {{ $result->quiz->course->title ?? 'Course unavailable' }}
                    </p>

                    <div class="mt-4 flex items-end justify-between">
                        <div>
                            <p class="text-2xl font-bold text-white">
                                {{ $result->percentage }}%
                            </p>
                            @if($result->remarks === 'passed')
                                <span class="text-sm font-semibold text-green-400">Passed</span>
                            @else
                                <span class="text-sm font-semibold text-red-400">Failed</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400">
                            @if($result->completed_at)
                                {{ \Carbon\Carbon::parse($result->completed_at)->format('M d, Y') }}
                            @endif
                        </p>
                    </div>
                </div>

            @empty
                <p class="rounded-xl border border-neutral-700 bg-neutral-800 p-4 text-sm text-gray-300 md:col-span-3">
                    No quiz attempts yet.
                </p>
            @endforelse
        </div>

    </div>

</div>

@if(count($chartScores) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    function initQuizTrendChart() {

        const canvas = document.getElementById('quizTrendChart');

        if (!canvas) return;

        // Destroy existing chart if naa
        if (window.quizTrendChart instanceof Chart) {
            window.quizTrendChart.destroy();
        }

        window.quizTrendChart = new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Quiz Score',
                    data: @json($chartScores),
                    borderColor: 'rgba(147, 51, 234, 1)',
                    backgroundColor: 'rgba(147, 51, 234, 0.25)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#d1d5db'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            color: '#9ca3af'
                        },
                        grid: {
                            color: 'rgba(156, 163, 175, 0.2)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#9ca3af'
                        },
                        grid: {
                            color: 'rgba(156, 163, 175, 0.1)'
                        }
                    }
                }
            }
        });
    }

    // Initial page load
    document.addEventListener('DOMContentLoaded', initQuizTrendChart);

    // Flux / Livewire navigation
    document.addEventListener('livewire:navigated', initQuizTrendChart);
</script>
@endif

</x-layouts::app>