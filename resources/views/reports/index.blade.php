<x-layouts::app :title="__('Reports & Analytics')">

    <div class="min-h-screen bg-gray-50">

        <div class="mx-auto max-w-7xl space-y-8 px-4 py-6 sm:px-6 lg:px-8">

            {{-- =========================================================
                 HEADER
            ========================================================== --}}
            <div class="flex flex-col gap-5 border-b border-gray-200 pb-6 md:flex-row md:items-end md:justify-between">

                <div>
                    <div class="mb-2 flex items-center gap-2">
                    

                        <span class="text-sm font-semibold text-purple-600">
                             Super Admin
                        </span>
                    </div>

                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 md:text-4xl">
                        Reports & Analytics
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500">
                        Monitor platform activity, learner engagement, assessments,
                        assignments, certificates, and transactions.
                    </p>
                </div>


                {{-- Report Period --}}
                <form
                    method="GET"
                    action="{{ route('reports.index') }}"
                    class="flex items-center gap-3"
                >

                    <label
                        for="range"
                        class="whitespace-nowrap text-sm font-medium text-gray-600"
                    >
                        Report period
                    </label>

                    <select
                        id="range"
                        name="range"
                        onchange="this.form.submit()"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm outline-none transition focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
                    >
                        <option value="all" @selected($range === 'all')}>
                            All Time
                        </option>

                        <option value="30" @selected($range === '30')}>
                            Last 30 Days
                        </option>

                        <option value="90" @selected($range === '90')}>
                            Last 90 Days
                        </option>

                        <option value="365" @selected($range === '365')}>
                            Last 365 Days
                        </option>
                    </select>

                </form>

            </div>


            {{-- =========================================================
                 PLATFORM OVERVIEW
            ========================================================== --}}
            <section>

                <div class="mb-4">
                    <h2 class="text-lg font-bold text-gray-900">
                        Platform Overview
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Current users, courses, and learner completion activity.
                    </p>
                </div>


                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                    {{-- Students --}}
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                        <div class="flex items-start justify-between">

                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Total Students
                                </p>

                                <p class="mt-2 text-3xl font-bold text-gray-900">
                                    {{ number_format($totalStudents) }}
                                </p>
                            </div>

                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-10a4 4 0 110 8 4 4 0 010-8zM17 8a3 3 0 100-6"/>
                                </svg>
                            </div>

                        </div>

                        <p class="mt-3 text-xs text-gray-400">
                            Registered learners
                        </p>

                    </div>


                    {{-- Teachers --}}
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                        <div class="flex items-start justify-between">

                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Total Teachers
                                </p>

                                <p class="mt-2 text-3xl font-bold text-gray-900">
                                    {{ number_format($totalTeachers) }}
                                </p>
                            </div>

                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 14l9-5-9-5-9 5 9 5z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 12v5c3.5 2.5 10.5 2.5 14 0v-5"/>
                                </svg>
                            </div>

                        </div>

                        <p class="mt-3 text-xs text-gray-400">
                            Active instructors
                        </p>

                    </div>


                    {{-- Courses --}}
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                        <div class="flex items-start justify-between">

                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Total Courses
                                </p>

                                <p class="mt-2 text-3xl font-bold text-gray-900">
                                    {{ number_format($totalCourses) }}
                                </p>
                            </div>

                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5s3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>

                        </div>

                        <p class="mt-3 text-xs text-gray-400">
                            Learning programs
                        </p>

                    </div>


                    {{-- Completion --}}
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                        <div class="flex items-start justify-between">

                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Completion Rate
                                </p>

                                <p class="mt-2 text-3xl font-bold text-gray-900">
                                    {{ number_format($completionRate, 1) }}%
                                </p>
                            </div>

                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50 text-green-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>

                        </div>

                        <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full bg-purple-600"
                                style="width: {{ min($completionRate, 100) }}%"
                            ></div>
                        </div>

                        <p class="mt-2 text-xs text-gray-400">
                            Completed enrollments
                        </p>

                    </div>

                </div>

            </section>


            {{-- =========================================================
                 ENROLLMENT STATISTICS
            ========================================================== --}}
            <section>

                <div class="mb-4">
                    <h2 class="text-lg font-bold text-gray-900">
                        Enrollment Activity
                    </h2>
                </div>


                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                        <p class="text-sm text-gray-500">
                            Total Enrollments
                        </p>

                        <p class="mt-2 text-2xl font-bold text-gray-900">
                            {{ number_format($totalEnrollments) }}
                        </p>

                        <p class="mt-2 text-xs text-gray-400">
                            Selected report period
                        </p>
                    </div>


                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                        <p class="text-sm text-gray-500">
                            Active Enrollments
                        </p>

                        <p class="mt-2 text-2xl font-bold text-gray-900">
                            {{ number_format($activeEnrollments) }}
                        </p>

                        <p class="mt-2 text-xs text-gray-400">
                            Currently learning
                        </p>
                    </div>


                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                        <p class="text-sm text-gray-500">
                            Completed
                        </p>

                        <p class="mt-2 text-2xl font-bold text-gray-900">
                            {{ number_format($completedEnrollments) }}
                        </p>

                        <p class="mt-2 text-xs text-gray-400">
                            Finished enrollments
                        </p>
                    </div>


                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                        <p class="text-sm text-gray-500">
                            Total Admins
                        </p>

                        <p class="mt-2 text-2xl font-bold text-gray-900">
                            {{ number_format($totalAdmins) }}
                        </p>

                        <p class="mt-2 text-xs text-gray-400">
                            System administrators
                        </p>
                    </div>

                </div>

            </section>


            {{-- =========================================================
                 LEARNING & ASSESSMENT
            ========================================================== --}}
            <section>

                <div class="mb-4">
                    <h2 class="text-lg font-bold text-gray-900">
                        Learning & Assessment
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Learner performance and assessment activity.
                    </p>
                </div>


                <div class="grid gap-6 lg:grid-cols-3">

                    {{-- Assessment Summary --}}
                    <div class="rounded-xl border border-gray-200 bg-white p-6 lg:col-span-2">

                        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">

                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Quiz Attempts
                                </p>

                                <p class="mt-1 text-3xl font-bold text-gray-900">
                                    {{ number_format($quizAttempts) }}
                                </p>

                                <p class="mt-1 text-sm text-gray-400">
                                    Total student assessment attempts
                                </p>
                            </div>


                            <div class="rounded-lg bg-purple-50 px-5 py-4 text-center">

                                <p class="text-xs font-semibold uppercase tracking-wide text-purple-600">
                                    Average Score
                                </p>

                                <p class="mt-1 text-2xl font-bold text-purple-700">
                                    {{ number_format($averageQuizScore, 2) }}%
                                </p>

                            </div>

                        </div>


                        <div class="mt-8 grid gap-4 sm:grid-cols-2">

                            {{-- Passed --}}
                            <div class="rounded-lg border border-green-100 bg-green-50 p-4">

                                <div class="flex items-center justify-between">

                                    <div>
                                        <p class="text-sm font-medium text-green-700">
                                            Passed Quizzes
                                        </p>

                                        <p class="mt-1 text-2xl font-bold text-green-800">
                                            {{ number_format($passedQuizAttempts) }}
                                        </p>
                                    </div>

                                    <div class="text-green-600">
                                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>

                                </div>

                            </div>


                            {{-- Failed --}}
                            <div class="rounded-lg border border-red-100 bg-red-50 p-4">

                                <div class="flex items-center justify-between">

                                    <div>
                                        <p class="text-sm font-medium text-red-700">
                                            Failed Quizzes
                                        </p>

                                        <p class="mt-1 text-2xl font-bold text-red-800">
                                            {{ number_format($failedQuizAttempts) }}
                                        </p>
                                    </div>

                                    <div class="text-red-600">
                                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- Certificates --}}
                    <div class="rounded-xl border border-gray-200 bg-white p-6">

                        <div class="flex h-full flex-col justify-between">

                            <div>

                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-50 text-purple-600">

                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15l-3.5 2 1-4-3-2.5h4l1.5-4 1.5 4h4l-3 2.5 1 4z"/>
                                    </svg>

                                </div>

                                <p class="mt-5 text-sm font-medium text-gray-500">
                                    Certificates Issued
                                </p>

                                <p class="mt-2 text-3xl font-bold text-gray-900">
                                    {{ number_format($certificatesIssued) }}
                                </p>

                            </div>

                            <p class="mt-6 text-xs text-gray-400">
                                Course completion certificates issued to learners.
                            </p>

                        </div>

                    </div>

                </div>

            </section>


            {{-- =========================================================
                 ASSIGNMENTS
            ========================================================== --}}
            <section>

                <div class="rounded-xl border border-gray-200 bg-white p-6">

                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-900">
                            Assignment Activity
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Track assignment creation, submissions, and grading.
                        </p>
                    </div>


                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                        <div class="rounded-lg bg-gray-50 p-5">
                            <p class="text-sm text-gray-500">
                                Total Assignments
                            </p>

                            <p class="mt-2 text-2xl font-bold text-gray-900">
                                {{ number_format($totalAssignments) }}
                            </p>
                        </div>


                        <div class="rounded-lg bg-gray-50 p-5">
                            <p class="text-sm text-gray-500">
                                Total Submissions
                            </p>

                            <p class="mt-2 text-2xl font-bold text-gray-900">
                                {{ number_format($totalSubmissions) }}
                            </p>
                        </div>


                        <div class="rounded-lg bg-green-50 p-5">
                            <p class="text-sm text-green-700">
                                Graded Submissions
                            </p>

                            <p class="mt-2 text-2xl font-bold text-green-800">
                                {{ number_format($gradedSubmissions) }}
                            </p>
                        </div>


                        <div class="rounded-lg bg-yellow-50 p-5">
                            <p class="text-sm text-yellow-700">
                                Pending Grading
                            </p>

                            <p class="mt-2 text-2xl font-bold text-yellow-800">
                                {{ number_format($pendingSubmissions) }}
                            </p>
                        </div>

                    </div>

                </div>

            </section>


            {{-- =========================================================
                 TRANSACTIONS
            ========================================================== --}}
            <section>

                <div class="rounded-xl border border-gray-200 bg-white p-6">

                    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">

                        <div>
                            <h2 class="text-lg font-bold text-gray-900">
                                Transactions & Revenue
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                Overview of course purchases and transaction status.
                            </p>
                        </div>


                        <div class="text-left sm:text-right">

                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                                Approved Revenue
                            </p>

                            <p class="mt-1 text-2xl font-bold text-gray-900">
                                ₱{{ number_format($totalRevenue, 2) }}
                            </p>

                        </div>

                    </div>


                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                        <div class="rounded-lg border border-gray-200 p-5">

                            <p class="text-sm text-gray-500">
                                Total Transactions
                            </p>

                            <p class="mt-2 text-2xl font-bold text-gray-900">
                                {{ number_format($totalTransactions) }}
                            </p>

                        </div>


                        <div class="rounded-lg border border-green-200 bg-green-50 p-5">

                            <p class="text-sm font-medium text-green-700">
                                Approved
                            </p>

                            <p class="mt-2 text-2xl font-bold text-green-800">
                                {{ number_format($approvedTransactions) }}
                            </p>

                        </div>


                        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-5">

                            <p class="text-sm font-medium text-yellow-700">
                                Pending
                            </p>

                            <p class="mt-2 text-2xl font-bold text-yellow-800">
                                {{ number_format($pendingTransactions) }}
                            </p>

                        </div>


                        <div class="rounded-lg border border-red-200 bg-red-50 p-5">

                            <p class="text-sm font-medium text-red-700">
                                Rejected
                            </p>

                            <p class="mt-2 text-2xl font-bold text-red-800">
                                {{ number_format($rejectedTransactions) }}
                            </p>

                        </div>

                    </div>

                </div>

            </section>


            {{-- =========================================================
                 COURSES + QUIZ RESULTS
            ========================================================== --}}
            <section class="grid gap-6 lg:grid-cols-2">

                {{-- Popular Courses --}}
                <div class="rounded-xl border border-gray-200 bg-white">

                    <div class="border-b border-gray-100 p-6">

                        <h2 class="text-lg font-bold text-gray-900">
                            Popular Courses
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Courses with the highest number of enrollments.
                        </p>

                    </div>


                    <div class="divide-y divide-gray-100">

                        @forelse($popularCourses as $index => $course)

                            <div class="flex items-center gap-4 px-6 py-4">

                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-sm font-bold text-purple-600">
                                    {{ $index + 1 }}
                                </div>


                                <div class="min-w-0 flex-1">

                                    <p class="truncate text-sm font-semibold text-gray-900">
                                        {{ $course->title }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-400">
                                        Learning program
                                    </p>

                                </div>


                                <div class="text-right">

                                    <p class="text-sm font-bold text-gray-900">
                                        {{ number_format($course->enrollments_count) }}
                                    </p>

                                    <p class="text-xs text-gray-400">
                                        enrollments
                                    </p>

                                </div>

                            </div>

                        @empty

                            <div class="p-10 text-center">

                                <p class="text-sm text-gray-500">
                                    No courses found.
                                </p>

                            </div>

                        @endforelse

                    </div>

                </div>


                {{-- Recent Quiz Results --}}
                <div class="rounded-xl border border-gray-200 bg-white">

                    <div class="border-b border-gray-100 p-6">

                        <h2 class="text-lg font-bold text-gray-900">
                            Recent Quiz Results
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Latest assessment results from students.
                        </p>

                    </div>


                    <div class="divide-y divide-gray-100">

                        @forelse($recentQuizResults as $result)

                            @php
                                $isPassed = strtolower($result->remarks ?? '') === 'passed';
                            @endphp


                            <div class="flex items-center justify-between gap-4 px-6 py-4">

                                <div class="min-w-0">

                                    <p class="truncate text-sm font-semibold text-gray-900">
                                        {{ $result->student->name ?? 'N/A' }}
                                    </p>

                                    <p class="mt-1 truncate text-xs text-gray-500">
                                        {{ $result->quiz->title ?? 'N/A' }}
                                    </p>

                                </div>


                                <div class="shrink-0 text-right">

                                    <p class="text-sm font-bold text-gray-900">
                                        {{ number_format($result->percentage, 2) }}%
                                    </p>


                                    @if($isPassed)

                                        <span class="text-xs font-semibold text-green-600">
                                            Passed
                                        </span>

                                    @else

                                        <span class="text-xs font-semibold text-red-600">
                                            Failed
                                        </span>

                                    @endif

                                </div>

                            </div>

                        @empty

                            <div class="p-10 text-center">

                                <p class="text-sm text-gray-500">
                                    No quiz results found.
                                </p>

                            </div>

                        @endforelse

                    </div>

                </div>

            </section>


            {{-- =========================================================
                 RECENT CERTIFICATES
            ========================================================== --}}
            <section>

                <div class="rounded-xl border border-gray-200 bg-white">

                    <div class="border-b border-gray-100 p-6">

                        <h2 class="text-lg font-bold text-gray-900">
                            Recent Certificates
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Recently issued course completion certificates.
                        </p>

                    </div>


                    <div class="overflow-x-auto">

                        <table class="w-full text-left">

                            <thead class="border-b border-gray-100 bg-gray-50">

                                <tr>

                                    <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Certificate No.
                                    </th>

                                    <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Student
                                    </th>

                                    <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Course
                                    </th>

                                    <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Issued Date
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-gray-100">

                                @forelse($recentCertificates as $certificate)

                                    <tr class="transition hover:bg-gray-50">

                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                            {{ $certificate->certificate_number }}
                                        </td>

                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ $certificate->student->name ?? 'N/A' }}
                                        </td>

                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ $certificate->course->title ?? 'N/A' }}
                                        </td>

                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ $certificate->issued_date }}
                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td
                                            colspan="4"
                                            class="px-6 py-12 text-center"
                                        >

                                            <p class="text-sm font-medium text-gray-600">
                                                No certificates found
                                            </p>

                                            <p class="mt-1 text-xs text-gray-400">
                                                Certificates will appear here once issued.
                                            </p>

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </section>


            {{-- =========================================================
                 REPORT FOOTER
            ========================================================== --}}
            <div class="border-t border-gray-200 pt-5">

                <p class="text-center text-xs text-gray-400">
                    PathWise System Reports • Data generated from the current platform records
                </p>

            </div>

        </div>

    </div>

</x-layouts::app>