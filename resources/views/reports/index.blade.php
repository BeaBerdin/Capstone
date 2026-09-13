<x-layouts::app :title="'System Reports'">

    <div class="space-y-6">

        {{-- HEADER — Coursera style: light card, blue eyebrow --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

                <div>
                    <div class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-[#0056D2]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v14a2 2 0 01-2 2z"/>
                        </svg>
                        Super Admin
                    </div>

                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">
                        System Reports
                    </h1>

                    <p class="mt-2 text-sm text-slate-500">
                        Overview of users, courses, enrollments, certificates, and platform activity.
                    </p>
                </div>

                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#E8F0FE] text-[#0056D2]">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v14a2 2 0 01-2 2z"/>
                    </svg>
                </div>

            </div>
        </div>


        {{-- PLATFORM OVERVIEW --}}
        <div>
            <div class="mb-4">
                <h2 class="text-lg font-bold tracking-tight text-slate-900">
                    Platform Overview
                </h2>

                <p class="text-sm text-slate-500">
                    Current system statistics
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                {{-- Students --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Total Students</p>

                            <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">
                                {{ number_format($totalStudents) }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-blue-50 p-3">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>


                {{-- Instructors --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Total Instructors</p>

                            <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">
                                {{ number_format($totalTeachers) }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-indigo-50 p-3">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0121 16.5c0 1.657-4.03 3-9 3s-9-1.343-9-3c0-2.112 1.085-4.064 2.84-5.922L12 14z"/>
                            </svg>
                        </div>
                    </div>
                </div>


                {{-- Courses --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Total Courses</p>

                            <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">
                                {{ number_format($totalCourses) }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-emerald-50 p-3">
                            <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                    </div>
                </div>


                {{-- Enrollments --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Total Enrollments</p>

                            <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">
                                {{ number_format($totalEnrollments) }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-orange-50 p-3">
                            <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                    </div>
                </div>

            </div>
        </div>


        {{-- LEARNING STATISTICS --}}
        <div class="grid gap-6 lg:grid-cols-3">

            {{-- Completion --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-bold tracking-tight text-slate-900">
                            Course Completion
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Enrollment completion rate
                        </p>
                    </div>

                    <div class="rounded-xl bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-600">
                        {{ $completionRate }}%
                    </div>
                </div>

                <div class="mt-6 h-3 overflow-hidden rounded-full bg-slate-100">
                    <div
                        class="h-full rounded-full bg-[#0056D2]"
                        style="width: {{ min($completionRate, 100) }}%"
                    ></div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4">

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-medium text-slate-500">Completed</p>
                        <p class="mt-1 text-xl font-extrabold tracking-tight text-slate-900">
                            {{ number_format($completedEnrollments) }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-medium text-slate-500">Active</p>
                        <p class="mt-1 text-xl font-extrabold tracking-tight text-slate-900">
                            {{ number_format($activeEnrollments) }}
                        </p>
                    </div>

                </div>

            </div>


            {{-- Certificates --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-bold tracking-tight text-slate-900">
                            Certificates
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Certificates issued to students
                        </p>
                    </div>

                    <div class="rounded-xl bg-yellow-50 p-3">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622C17.176 19.29 21 14.591 21 9c0-1.042-.133-2.053-.382-3.016z"/>
                        </svg>
                    </div>
                </div>

                <p class="mt-6 text-4xl font-extrabold tracking-tight text-slate-900">
                    {{ number_format($certificatesIssued) }}
                </p>

                <p class="mt-2 text-sm text-slate-500">
                    Certificates issued
                </p>

            </div>


            {{-- Quiz Attempts --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-bold tracking-tight text-slate-900">
                            Quiz Activity
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Total quiz attempts recorded
                        </p>
                    </div>

                    <div class="rounded-xl bg-cyan-50 p-3">
                        <svg class="h-6 w-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a3 3 0 006 0M9 5a3 3 0 016 0"/>
                        </svg>
                    </div>
                </div>

                <p class="mt-6 text-4xl font-extrabold tracking-tight text-slate-900">
                    {{ number_format($quizAttempts) }}
                </p>

                <p class="mt-2 text-sm text-slate-500">
                    Quiz attempts recorded
                </p>

            </div>

        </div>


        {{-- POPULAR COURSES --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-100 p-6">
                <h2 class="text-lg font-bold tracking-tight text-slate-900">
                    Most Popular Courses
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Courses with the highest number of enrollments
                </p>
            </div>

            <div class="divide-y divide-slate-100">

                @forelse($popularCourses as $index => $course)

                    <div class="flex items-center gap-4 p-5 transition hover:bg-slate-50">

                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#E8F0FE] font-bold text-[#0056D2]">
                            {{ $index + 1 }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-slate-900">
                                {{ $course->title ?? $course->name ?? 'Untitled Course' }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                Course
                            </p>
                        </div>

                        <div class="text-right">
                            <p class="font-semibold text-slate-900">
                                {{ number_format($course->enrollments_count) }}
                            </p>

                            <p class="text-xs text-slate-500">
                                enrollments
                            </p>
                        </div>

                    </div>

                @empty

                    <div class="p-10 text-center">
                        <p class="text-sm text-slate-500">
                            No course enrollment data available yet.
                        </p>
                    </div>

                @endforelse

            </div>

        </div>


        {{-- RECENT CERTIFICATES --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-100 p-6">
                <h2 class="text-lg font-bold tracking-tight text-slate-900">
                    Recently Issued Certificates
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Latest certificates generated by the platform
                </p>
            </div>

            <div class="divide-y divide-slate-100">

                @forelse($recentCertificates as $certificate)

                    <div class="flex items-center gap-4 p-5 transition hover:bg-slate-50">

                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-yellow-50">
                            <svg class="h-5 w-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4"/>
                            </svg>
                        </div>

                        <div class="min-w-0 flex-1">

                            <p class="font-semibold text-slate-900">
                                {{ $certificate->student->name ?? 'Unknown Student' }}
                            </p>

                            <p class="mt-1 truncate text-sm text-slate-500">
                                {{ $certificate->course->title ?? $certificate->course->name ?? 'Unknown Course' }}
                            </p>

                        </div>

                        <div class="text-right text-xs text-slate-500">
                            {{ $certificate->created_at?->format('M d, Y') }}
                        </div>

                    </div>

                @empty

                    <div class="p-10 text-center">
                        <p class="text-sm text-slate-500">
                            No certificates have been issued yet.
                        </p>
                    </div>

                @endforelse

            </div>

        </div>


        {{-- RECENT QUIZ RESULTS --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-100 p-6">
                <h2 class="text-lg font-bold tracking-tight text-slate-900">
                    Recent Quiz Activity
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Latest quiz results recorded on the platform
                </p>
            </div>

            <div class="divide-y divide-slate-100">

                @forelse($recentQuizResults as $result)

                    <div class="flex items-center gap-4 p-5 transition hover:bg-slate-50">

                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-50">
                            <svg class="h-5 w-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                            </svg>
                        </div>

                        <div class="min-w-0 flex-1">

                            <p class="font-semibold text-slate-900">
                                {{ $result->student->name ?? 'Unknown Student' }}
                            </p>

                            <p class="mt-1 truncate text-sm text-slate-500">
                                {{ $result->quiz->title ?? $result->quiz->name ?? 'Quiz' }}
                            </p>

                        </div>

                        <div class="text-right">
                            <p class="font-semibold text-slate-900">
                                {{ $result->score ?? 0 }}
                            </p>

                            <p class="text-xs text-slate-500">
                                score
                            </p>
                        </div>

                    </div>

                @empty

                    <div class="p-10 text-center">
                        <p class="text-sm text-slate-500">
                            No quiz activity available yet.
                        </p>
                    </div>

                @endforelse

            </div>

        </div>

    </div>

</x-layouts::app>