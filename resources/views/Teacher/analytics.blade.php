<x-layouts::app :title="'Performance Analytics'">

<style>
    .pw-analytics-page {
        min-height: 100vh;
        background: #f7f8fc;
        color: #0f172a;
    }

    .pw-analytics-shell {
        width: 100%;
        max-width: 1600px;
        margin: 0;
        padding: 22px 24px 38px;
    }

    .pw-a-card {
        background: #fff;
        border: 1px solid #e6e9ef;
        border-radius: 14px;
        box-shadow:
            0 1px 2px rgba(15, 23, 42, .025),
            0 5px 18px rgba(15, 23, 42, .018);
    }

    .pw-a-title {
        font-size: 15px;
        font-weight: 800;
        letter-spacing: -.02em;
        color: #111827;
    }

    .pw-a-sub {
        margin-top: 4px;
        font-size: 10.5px;
        color: #94a3b8;
    }

    .pw-a-stat {
        min-height: 126px;
        padding: 18px 18px 16px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition:
            transform .16s ease,
            border-color .16s ease,
            box-shadow .16s ease;
    }

    .pw-a-stat:hover {
        transform: translateY(-1px);
        border-color: #ddd6fe;
        box-shadow: 0 8px 22px rgba(76, 29, 149, .055);
    }

    .pw-a-stat-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }

    .pw-a-icon {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        flex: 0 0 38px;
        border-radius: 50%;
    }

    .pw-a-label {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
    }

    .pw-a-value {
        margin-top: 5px;
        font-size: 27px;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -.03em;
        color: #0f172a;
    }

    .pw-a-hint {
        margin-top: 8px;
        font-size: 9.5px;
        color: #94a3b8;
    }

    .pw-a-panel-header {
        padding: 17px 18px;
        border-bottom: 1px solid #eef1f5;
    }

    .pw-a-progress-track {
        height: 7px;
        overflow: hidden;
        border-radius: 999px;
        background: #f1f5f9;
    }

    .pw-a-progress-bar {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #7c3aed, #6366f1);
    }

    .pw-a-table {
        width: 100%;
        border-collapse: collapse;
    }

    .pw-a-table th {
        padding: 11px 16px;
        border-bottom: 1px solid #eef1f5;
        color: #94a3b8;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: .06em;
        text-align: left;
        text-transform: uppercase;
    }

    .pw-a-table td {
        padding: 13px 16px;
        border-bottom: 1px solid #f1f3f7;
        color: #475569;
        font-size: 10.5px;
    }

    .pw-a-table tr:last-child td {
        border-bottom: 0;
    }

    .pw-a-select {
        height: 39px;
        min-width: 175px;
        padding: 0 34px 0 12px;
        border: 1px solid #dfe4ec;
        border-radius: 9px;
        outline: none;
        background: #fff;
        color: #475569;
        font-size: 11px;
        font-weight: 600;
    }

    .pw-a-filter-button {
        height: 39px;
        padding: 0 15px;
        border: 0;
        border-radius: 9px;
        background: #6d28d9;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
    }


    .pw-a-empty-chart {
        min-height: 210px;
        display: grid;
        place-items: center;
        padding: 24px;
        text-align: center;
    }

    .pw-a-empty-icon {
        width: 42px;
        height: 42px;
        margin: 0 auto;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: #f5f3ff;
        color: #7c3aed;
    }

    .pw-a-empty-title {
        margin-top: 12px;
        font-size: 11px;
        font-weight: 800;
        color: #1f2937;
    }

    .pw-a-empty-copy {
        margin-top: 4px;
        max-width: 240px;
        font-size: 9.5px;
        line-height: 1.5;
        color: #94a3b8;
    }

    .pw-a-table tbody tr {
        transition: background-color .14s ease;
    }

    .pw-a-table tbody tr:hover {
        background: #fafbff;
    }

    .pw-a-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border-radius: 999px;
        padding: 4px 8px;
        background: #f1f5f9;
        color: #64748b;
        font-size: 8.5px;
        font-weight: 800;
        text-transform: capitalize;
    }

    .pw-a-status-dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: #22c55e;
    }

    .pw-a-kpi-foot {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
        font-size: 9.5px;
        color: #94a3b8;
    }

    .pw-a-kpi-foot strong {
        color: #64748b;
        font-weight: 700;
    }

    @media (max-width: 900px) {
        .pw-analytics-shell {
            padding: 20px 16px 32px;
        }
    }
</style>


<div class="pw-analytics-page">

    <main class="pw-analytics-shell">

        {{-- HEADER --}}
        <div
            class="mb-5 flex flex-col gap-4
                   lg:flex-row lg:items-end lg:justify-between"
        >

            <div>

                <div
                    class="text-[10px] font-bold uppercase
                           tracking-[.14em] text-violet-600"
                >
                    Analytics
                </div>

                <h1
                    class="mt-1 text-[26px] font-extrabold
                           tracking-[-.03em] text-slate-950"
                >
                    Performance Analytics
                </h1>

                <p class="mt-1.5 text-xs text-slate-500">
                    Measure learner progress, course performance,
                    assessment results, and enrollment activity.
                </p>

            </div>


            <form
                method="GET"
                action="{{ route('teacher.analytics') }}"
                class="flex flex-wrap items-center gap-2"
            >

                <select
                    name="course"
                    class="pw-a-select"
                >
                    <option value="">
                        All Courses
                    </option>

                    @foreach($teacherCourses as $course)

                        <option
                            value="{{ $course->id }}"
                            @selected(
                                (string) $selectedCourseId
                                === (string) $course->id
                            )
                        >
                            {{ $course->title }}
                        </option>

                    @endforeach
                </select>


                <select
                    name="range"
                    class="pw-a-select"
                >
                    <option value="all" @selected($range === 'all')>
                        All Time
                    </option>

                    <option value="30" @selected($range === '30')>
                        Last 30 Days
                    </option>

                    <option value="90" @selected($range === '90')>
                        Last 90 Days
                    </option>

                    <option value="365" @selected($range === '365')>
                        Last 12 Months
                    </option>
                </select>


                <button
                    type="submit"
                    class="pw-a-filter-button"
                >
                    Apply
                </button>

            </form>

        </div>



        {{-- KPI CARDS --}}
        <section
            class="grid grid-cols-1 gap-4
                   sm:grid-cols-2 xl:grid-cols-4"
        >

            <div class="pw-a-card pw-a-stat">

                <div class="pw-a-stat-top">

                    <div>
                        <div class="pw-a-label">
                            Total Learners
                        </div>

                        <div class="pw-a-value">
                            {{ $totalStudents }}
                        </div>
                    </div>

                    <div
                        class="pw-a-icon
                               bg-blue-50 text-blue-600"
                    >
                        <svg
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"
                            />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M19 8v6M22 11h-6" />
                        </svg>
                    </div>

                </div>

                <div class="pw-a-kpi-foot">
                    <span>Unique enrolled students across</span>
                    <strong>
                        {{ $totalCourses }}
                        {{ \Illuminate\Support\Str::plural('course', $totalCourses) }}
                    </strong>
                </div>

            </div>


            <div class="pw-a-card pw-a-stat">

                <div class="pw-a-stat-top">

                    <div>
                        <div class="pw-a-label">
                            Avg. Class Progress
                        </div>

                        <div class="pw-a-value">
                            {{ number_format($averageProgress, 1) }}%
                        </div>
                    </div>

                    <div
                        class="pw-a-icon
                               bg-emerald-50 text-emerald-600"
                    >
                        <svg
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M4 19V9M9 19V5M14 19v-7M19 19V3"
                            />
                        </svg>
                    </div>

                </div>

                <div class="pw-a-kpi-foot">
                    <span>Average enrollment progress</span>
                </div>

            </div>


            <div class="pw-a-card pw-a-stat">

                <div class="pw-a-stat-top">

                    <div>
                        <div class="pw-a-label">
                            Avg. Quiz Score
                        </div>

                        <div class="pw-a-value">
                            {{ number_format($averageQuizScore, 1) }}%
                        </div>
                    </div>

                    <div
                        class="pw-a-icon
                               bg-violet-50 text-violet-600"
                    >
                        <svg
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <circle cx="12" cy="12" r="9" />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m8.5 12.5 2.5 2.5 4.5-5"
                            />
                        </svg>
                    </div>

                </div>

                <div class="pw-a-kpi-foot">
                    <strong>{{ number_format($quizPassRate, 1) }}%</strong>
                    <span>quiz pass rate</span>
                </div>

            </div>


            <div class="pw-a-card pw-a-stat">

                <div class="pw-a-stat-top">

                    <div>
                        <div class="pw-a-label">
                            Completion Rate
                        </div>

                        <div class="pw-a-value">
                            {{ number_format($completionRate, 1) }}%
                        </div>
                    </div>

                    <div
                        class="pw-a-icon
                               bg-orange-50 text-orange-600"
                    >
                        <svg
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 11l3 3L22 4"
                            />
                            <path
                                d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"
                            />
                        </svg>
                    </div>

                </div>

                <div class="pw-a-kpi-foot">
                    <span>Completed enrollments</span>
                </div>

            </div>

        </section>



        {{-- TREND + DISTRIBUTION --}}
        <section
            class="mt-4 grid grid-cols-1 gap-4
                   xl:grid-cols-[1.35fr_.65fr]"
        >

            {{-- Enrollment Trend --}}
            <div class="pw-a-card">

                <div class="pw-a-panel-header">

                    <div class="pw-a-title">
                        Enrollment Trend
                    </div>

                    <div class="pw-a-sub">
                        New enrollments during the last six months
                    </div>

                </div>


                @if($enrollmentTrend->sum('count') > 0)

                    <div class="px-5 pb-5 pt-6">

                        <div
                            class="flex h-[200px]
                                   items-end gap-4"
                        >

                            @foreach($enrollmentTrend as $month)

                                @php
                                    $height = max(
                                        5,
                                        ($month['count'] / $maxTrend) * 100
                                    );
                                @endphp


                                <div
                                    class="flex h-full min-w-0
                                           flex-1 flex-col
                                           items-center justify-end"
                                >

                                    <div
                                        class="mb-2 text-[9px]
                                               font-bold text-slate-500"
                                    >
                                        {{ $month['count'] }}
                                    </div>


                                    <div
                                        class="w-full max-w-[54px]
                                               rounded-t-lg
                                               bg-gradient-to-t
                                               from-violet-600
                                               to-violet-400"
                                        style="height: {{ $height }}%;"
                                    ></div>


                                    <div
                                        class="mt-2 text-[9px]
                                               font-semibold text-slate-400"
                                    >
                                        {{ $month['label'] }}
                                    </div>

                                </div>

                            @endforeach

                        </div>

                    </div>

                @else

                    <div class="pw-a-empty-chart">

                        <div>

                            <div class="pw-a-empty-icon">
                                <svg
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4 19V9M9 19V5M14 19v-7M19 19V3"
                                    />
                                </svg>
                            </div>

                            <div class="pw-a-empty-title">
                                No enrollment activity yet
                            </div>

                            <div class="pw-a-empty-copy">
                                Enrollment trends will appear here once students begin joining your courses.
                            </div>

                        </div>

                    </div>

                @endif

            </div>



            {{-- Quiz Distribution --}}
            <div class="pw-a-card">

                <div class="pw-a-panel-header">

                    <div class="pw-a-title">
                        Quiz Score Distribution
                    </div>

                    <div class="pw-a-sub">
                        How student quiz scores are distributed
                    </div>

                </div>


                @if(array_sum($scoreDistribution) > 0)

                    <div class="space-y-5 p-5">

                        @foreach($scoreDistribution as $label => $count)

                            @php
                                $width = ($count / $maxDistribution) * 100;
                            @endphp


                            <div>

                                <div
                                    class="mb-2 flex
                                           items-center justify-between"
                                >
                                    <span
                                        class="text-[10px]
                                               font-semibold text-slate-600"
                                    >
                                        {{ $label }}
                                    </span>

                                    <span
                                        class="text-[10px]
                                               font-bold text-slate-900"
                                    >
                                        {{ $count }}
                                    </span>
                                </div>


                                <div class="pw-a-progress-track">

                                    <div
                                        class="pw-a-progress-bar"
                                        style="width: {{ $width }}%;"
                                    ></div>

                                </div>

                            </div>

                        @endforeach

                    </div>

                @else

                    <div class="pw-a-empty-chart">

                        <div>

                            <div class="pw-a-empty-icon">
                                <svg
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m8.5 12.5 2.5 2.5 4.5-5"
                                    />
                                </svg>
                            </div>

                            <div class="pw-a-empty-title">
                                No quiz results yet
                            </div>

                            <div class="pw-a-empty-copy">
                                Score distribution will appear after learners complete quizzes.
                            </div>

                        </div>

                    </div>

                @endif

            </div>

        </section>



        {{-- COURSE PERFORMANCE --}}
        <section class="pw-a-card mt-4 overflow-hidden">

            <div
                class="flex items-center justify-between
                       gap-4 border-b border-slate-100
                       px-5 py-4"
            >

                <div>

                    <div class="pw-a-title">
                        Course Performance
                    </div>

                    <div class="pw-a-sub">
                        Compare learner activity and results across your courses
                    </div>

                </div>

            </div>


            <div class="overflow-x-auto">

                <table class="pw-a-table">

                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Status</th>
                            <th>Students</th>
                            <th>Avg. Progress</th>
                            <th>Avg. Quiz</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($coursePerformance as $course)

                            <tr>

                                <td>
                                    <div
                                        class="font-bold
                                               text-slate-800"
                                    >
                                        {{ $course['title'] }}
                                    </div>
                                </td>

                                <td>
                                    <span class="pw-a-status">
                                        <span class="pw-a-status-dot"></span>
                                        {{ $course['status'] }}
                                    </span>
                                </td>

                                <td>
                                    @if($course['students'] > 0)
                                        {{ $course['students'] }}
                                    @else
                                        <span class="text-slate-400">0</span>
                                    @endif
                                </td>

                                <td>
                                    {{ number_format($course['avg_progress'], 1) }}%
                                </td>

                                <td>
                                    {{ number_format($course['avg_quiz'], 1) }}%
                                </td>

                            </tr>


                        @empty

                            <tr>
                                <td colspan="5">
                                    No course performance data yet.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </section>



        {{-- LEARNERS + ASSESSMENT --}}
        <section
            class="mt-4 grid grid-cols-1 gap-4
                   xl:grid-cols-[1.35fr_.65fr]"
        >

            {{-- Learner Snapshot --}}
            <div class="pw-a-card overflow-hidden">

                <div class="pw-a-panel-header">

                    <div class="pw-a-title">
                        Learner Snapshot
                    </div>

                    <div class="pw-a-sub">
                        Top learners based on progress and quiz performance
                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="pw-a-table">

                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Courses</th>
                                <th>Avg. Progress</th>
                                <th>Avg. Quiz</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($learnerSnapshot as $student)

                                <tr>

                                    <td>
                                        <div
                                            class="font-bold
                                                   text-slate-800"
                                        >
                                            {{ $student['name'] }}
                                        </div>

                                        <div
                                            class="mt-1
                                                   text-[9px]
                                                   text-slate-400"
                                        >
                                            {{ $student['email'] }}
                                        </div>
                                    </td>

                                    <td>
                                        {{ $student['courses'] }}
                                    </td>

                                    <td>
                                        {{ number_format($student['avg_progress'], 1) }}%
                                    </td>

                                    <td>
                                        {{ number_format($student['avg_quiz'], 1) }}%
                                    </td>

                                </tr>


                            @empty

                                <tr>
                                    <td colspan="4">
                                        No learner data yet.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>



            {{-- Assessment Snapshot --}}
            <div class="pw-a-card">

                <div class="pw-a-panel-header">

                    <div class="pw-a-title">
                        Assessment Snapshot
                    </div>

                    <div class="pw-a-sub">
                        Assignment and quiz workload
                    </div>

                </div>


                <div class="space-y-4 p-5">

                    <div
                        class="flex items-center
                               justify-between
                               rounded-xl bg-slate-50
                               px-4 py-3"
                    >
                        <span
                            class="text-[10px]
                                   font-semibold text-slate-500"
                        >
                            Assignments
                        </span>

                        <span
                            class="text-lg font-extrabold
                                   text-slate-900"
                        >
                            {{ $totalAssignments }}
                        </span>
                    </div>


                    <div
                        class="flex items-center
                               justify-between
                               rounded-xl bg-amber-50
                               px-4 py-3"
                    >
                        <span
                            class="text-[10px]
                                   font-semibold text-amber-700"
                        >
                            Need grading
                        </span>

                        <span
                            class="text-lg font-extrabold
                                   text-amber-700"
                        >
                            {{ $pendingGrading }}
                        </span>
                    </div>


                    <div
                        class="flex items-center
                               justify-between
                               rounded-xl bg-violet-50
                               px-4 py-3"
                    >
                        <span
                            class="text-[10px]
                                   font-semibold text-violet-700"
                        >
                            Quiz pass rate
                        </span>

                        <span
                            class="text-lg font-extrabold
                                   text-violet-700"
                        >
                            {{ number_format($quizPassRate, 1) }}%
                        </span>
                    </div>

                </div>

            </div>

        </section>

    </main>

</div>

</x-layouts::app>
