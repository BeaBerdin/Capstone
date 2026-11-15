{{--
    One course card on My Courses.

    $enrollment:  the student's enrollment, with course.category loaded
    $certificate: the issued certificate for this course, or null
--}}

@php
    $course = $enrollment->course;

    $progress = min(
        max((float) ($enrollment->progress_percentage ?? 0), 0),
        100
    );

    $status = strtolower($enrollment->status ?? 'active');

    $isCompleted = $status === 'completed';
    $isCancelled = $status === 'cancelled';

    if ($progress <= 0) {
        $progressGroup = 'not-started';
    } elseif ($progress < 50) {
        $progressGroup = 'early';
    } elseif ($progress < 100) {
        $progressGroup = 'advanced';
    } else {
        $progressGroup = 'complete';
    }

    $thumbnail = $course->thumbnail ?? null;
    $thumbnailUrl = \App\Models\Course::thumbnailUrlFor($thumbnail);

    $searchText = strtolower(
        ($course->title ?? '') . ' ' .
        ($course->description ?? '') . ' ' .
        ($course->category?->name ?? '')
    );

    $formatDate = fn ($date) => $date
        ? \Carbon\Carbon::parse($date)->format('M d, Y')
        : '—';
@endphp


<article
    class="sad-card course-card pw-card pw-course-card overflow-hidden {{ $isCancelled ? 'pw-course-card-cancelled' : '' }}"
    data-search="{{ $searchText }}"
    data-category="{{ $course->category?->id ?? '' }}"
    data-progress="{{ $progressGroup }}"
    data-enrollment-status="{{ $status }}"
>

    {{-- =========================================
        IMAGE
    ========================================== --}}

    <div class="relative h-40 overflow-hidden bg-slate-100 sm:h-48">

        @if($thumbnailUrl)

            <img
                src="{{ $thumbnailUrl }}"
                alt="{{ $course->title }}"
                class="h-full w-full object-cover transition duration-300 {{ $isCancelled ? 'grayscale' : '' }}"
            >

        @else

            <div
                class="flex h-full w-full items-center justify-center bg-gradient-to-br
                       {{ $isCancelled
                           ? 'from-slate-400 via-slate-400 to-slate-500'
                           : 'from-violet-500 via-indigo-500 to-blue-500'
                       }}"
            >
                <svg
                    class="h-12 w-12 text-white/75"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                >
                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                </svg>
            </div>

        @endif


        {{-- STATUS --}}
        <div class="absolute left-3 top-3">

            @if($isCompleted)

                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500 px-3 py-1.5 text-xs font-bold text-white shadow-sm">
                    <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                    Completed
                </span>

            @elseif($isCancelled)

                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-700 px-3 py-1.5 text-xs font-bold text-white shadow-sm">
                    <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                    Cancelled
                </span>

            @else

                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1.5 text-xs font-bold text-blue-700 shadow-sm backdrop-blur">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                    In Progress
                </span>

            @endif

        </div>


        {{-- PROGRESS --}}
        <div class="absolute bottom-3 right-3 rounded-full bg-slate-950/75 px-3 py-1.5 text-xs font-bold text-white backdrop-blur">
            {{ number_format($progress, 0) }}%
        </div>

    </div>


    {{-- =========================================
        CONTENT
    ========================================== --}}

    <div class="p-5">

        {{-- CATEGORY --}}
        <p class="text-xs font-bold uppercase tracking-[.08em] {{ $isCancelled ? 'text-slate-500' : 'text-violet-500' }}">
            {{ $course->category?->name ?? 'Course' }}
        </p>


        {{-- TITLE --}}
        <h3 class="mt-2 line-clamp-2 min-h-[48px] text-base font-bold leading-6 text-slate-900">
            {{ $course->title }}
        </h3>


        {{-- DESCRIPTION --}}
        <p class="mt-2 line-clamp-2 min-h-[40px] text-xs leading-5 text-slate-500">
            {{ \Illuminate\Support\Str::limit($course->description ?? 'Continue your learning journey in this course.', 110) }}
        </p>


        {{-- INFO --}}
        <div class="mt-4 flex flex-wrap gap-2">

            @if($course->difficulty_level)
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                    {{ ucfirst($course->difficulty_level) }}
                </span>
            @endif

            @if($course->estimated_hours)
                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-600">
                    {{ $course->estimated_hours }} hrs
                </span>
            @endif

            @if($course->certificate_available)
                <span class="rounded-full bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-600">
                    Certificate
                </span>
            @endif

        </div>


        {{-- PROGRESS --}}
        <div class="mt-5">

            <div class="flex items-center justify-between">

                <span class="text-xs font-semibold text-slate-500">
                    Course Progress
                </span>

                <span
                    class="text-xs font-bold
                           {{ $isCompleted ? 'text-emerald-600' : ($isCancelled ? 'text-slate-500' : 'text-violet-600') }}"
                >
                    {{ number_format($progress, 0) }}%
                </span>

            </div>

            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                <div
                    class="h-full rounded-full
                           {{ $isCompleted ? 'bg-emerald-500' : ($isCancelled ? 'bg-slate-400' : 'bg-violet-600') }}"
                    style="width: {{ $progress }}%;"
                ></div>
            </div>

        </div>


        {{-- DATES --}}
        <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4">

            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Enrolled
                </dt>
                <dd class="mt-1 text-xs font-semibold text-slate-600">
                    {{ $formatDate($enrollment->enrolled_at) }}
                </dd>
            </div>

            @if($isCompleted)
                <div class="text-right">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Completed
                    </dt>
                    <dd class="mt-1 text-xs font-semibold text-emerald-600" data-completed-on>
                        {{ $formatDate($enrollment->completed_at) }}
                    </dd>
                </div>
            @endif

        </dl>


        {{-- ACTION --}}
        @if($isCompleted)

            <div class="mt-5 grid grid-cols-1 gap-2 sm:grid-cols-2">

                <a
                    href="{{ route('student.learn.course', $course) }}"
                    class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition hover:border-violet-200 hover:text-violet-700"
                >
                    Review Course
                </a>

                @if($certificate)

                    <a
                        href="{{ route('student.certificate.view', $certificate) }}"
                        class="inline-flex h-10 items-center justify-center rounded-xl bg-emerald-600 px-3 text-xs font-semibold text-white transition hover:bg-emerald-700"
                        data-certificate-link
                    >
                        View Certificate
                    </a>

                @elseif($course->certificate_available)

                    <span class="inline-flex h-10 items-center justify-center rounded-xl bg-slate-100 px-3 text-center text-xs font-semibold text-slate-500">
                        Certificate pending
                    </span>

                @else

                    <a
                        href="{{ route('student.course.show', $course) }}"
                        class="sad-btn-primary inline-flex h-10 items-center justify-center rounded-xl bg-violet-600 px-3 text-xs font-semibold text-white transition hover:bg-violet-700"
                    >
                        Course Details
                    </a>

                @endif

            </div>

        @elseif($isCancelled)

            {{-- No way into the course: a cancelled enrollment has no access. --}}
            <p
                class="mt-5 rounded-xl bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-500"
                data-enrollment-cancelled
            >
                Your enrollment was cancelled, so this course is no longer available to you.
                Contact the course administrator if you think this is a mistake.
            </p>

        @else

            <a
                href="{{ route('student.learn.course', $course) }}"
                class="sad-btn-primary mt-5 inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-violet-600 text-xs font-semibold text-white transition hover:bg-violet-700"
            >
                <svg
                    class="h-3.5 w-3.5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="m10 8 6 4-6 4z"></path>
                </svg>

                Continue Learning
            </a>

        @endif

        {{-- Course reviews: only enrollments that still give access. --}}
        @if(! $isCancelled && $course->status === 'published')
            <a
                href="{{ route('student.course.show', $course) }}#reviews"
                class="mt-3 inline-flex w-full items-center justify-center gap-1.5 text-xs font-semibold text-violet-600 transition hover:text-violet-800"
                data-review-link
            >
                <svg class="h-3.5 w-3.5 text-amber-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.36 4.18a1 1 0 0 0 .95.69h4.4c.97 0 1.37 1.24.59 1.81l-3.56 2.59a1 1 0 0 0-.36 1.12l1.36 4.18c.3.92-.75 1.69-1.54 1.12l-3.56-2.59a1 1 0 0 0-1.18 0l-3.56 2.59c-.79.57-1.84-.2-1.54-1.12l1.36-4.18a1 1 0 0 0-.36-1.12L1.75 9.6c-.78-.57-.38-1.81.59-1.81h4.4a1 1 0 0 0 .95-.69l1.36-4.18Z" />
                </svg>
                {{ ($hasReviewed ?? false) ? 'Your review' : 'Rate this course' }}
            </a>
        @endif

    </div>

</article>
