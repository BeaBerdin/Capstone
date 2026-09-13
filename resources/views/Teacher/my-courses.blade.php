<x-layouts::app :title="__('My Courses')">

@php
    $totalCourses = $courses->count();

    $publishedCourses = $courses
        ->where('status', 'published')
        ->count();

    $pendingCourses = $courses
        ->where('status', 'pending')
        ->count();

    $draftCourses = $courses
        ->where('status', 'draft')
        ->count();

    $totalStudents = $courses
        ->sum('enrollments_count');

    $courseCategories = $courses
        ->map(fn ($course) => $course->category->name ?? 'Uncategorized')
        ->unique()
        ->sort()
        ->values();
@endphp


<style>
    .pw-courses-page {
        min-height: 100vh;
        background: #f7f8fc;
        color: #0f172a;
    }

    .pw-courses-shell {
        width: 100%;
        max-width: 1600px;
        margin: 0;
        padding: 22px 24px 40px;
    }

    .pw-course-card,
    .pw-course-panel {
        background: #ffffff;
        border: 1px solid #e6e9ef;
        border-radius: 14px;
        box-shadow:
            0 1px 2px rgba(15, 23, 42, .025),
            0 5px 18px rgba(15, 23, 42, .018);
    }

    .pw-course-card {
        overflow: hidden;
        transition:
            transform .16s ease,
            border-color .16s ease,
            box-shadow .16s ease;
    }

    .pw-course-card:hover {
        transform: translateY(-2px);
        border-color: #ddd6fe;
        box-shadow: 0 10px 26px rgba(76, 29, 149, .065);
    }

    .pw-course-stat {
        min-height: 116px;
        padding: 17px 18px;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
    }

    .pw-course-stat-label {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
    }

    .pw-course-stat-value {
        margin-top: 6px;
        font-size: 27px;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -.03em;
        color: #0f172a;
    }

    .pw-course-stat-hint {
        margin-top: 8px;
        font-size: 9.5px;
        color: #94a3b8;
    }

    .pw-course-stat-icon {
        width: 39px;
        height: 39px;
        flex: 0 0 39px;
        display: grid;
        place-items: center;
        border-radius: 50%;
    }

    .pw-course-search,
    .pw-course-select {
        height: 40px;
        border: 1px solid #dfe4ec;
        border-radius: 9px;
        outline: none;
        background: #ffffff;
        color: #475569;
        font-size: 11px;
        font-weight: 500;
        transition:
            border-color .15s ease,
            box-shadow .15s ease;
    }

    .pw-course-search {
        width: 100%;
        padding: 0 14px 0 40px;
    }

    .pw-course-select {
        min-width: 160px;
        padding: 0 34px 0 12px;
    }

    .pw-course-search:focus,
    .pw-course-select:focus {
        border-color: #c4b5fd;
        box-shadow: 0 0 0 4px rgba(124, 58, 237, .07);
    }

    .pw-course-cover {
    position: relative;
    height: 158px;
    overflow: visible;
    background: #f1f5f9;
    z-index: 5;
    }

    .pw-course-cover img {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
        transition: transform .22s ease;
    }

    .pw-course-card:hover .pw-course-cover img {
        transform: scale(1.025);
    }

    .pw-course-fallback {
        position: relative;
        width: 100%;
        height: 100%;
        display: grid;
        place-items: center;
        overflow: hidden;
        background:
            radial-gradient(
                circle at 82% 20%,
                rgba(255,255,255,.16),
                transparent 23%
            ),
            linear-gradient(
                135deg,
                #6d28d9,
                #7c3aed 48%,
                #4f46e5
            );
        color: #ffffff;
    }

    .pw-course-fallback::before {
        content: "";
        position: absolute;
        width: 120px;
        height: 120px;
        right: -42px;
        top: -44px;
        border: 24px solid rgba(255,255,255,.07);
        border-radius: 50%;
    }

    .pw-course-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid transparent;
        border-radius: 999px;
        padding: 5px 9px;
        background: rgba(255,255,255,.95);
        box-shadow: 0 2px 7px rgba(15, 23, 42, .07);
        backdrop-filter: blur(8px);
        font-size: 8.5px;
        font-weight: 800;
        text-transform: capitalize;
    }

    .pw-course-status-dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
    }

    .pw-course-status-published {
        color: #047857;
    }

    .pw-course-status-published .pw-course-status-dot {
        background: #10b981;
    }

    .pw-course-status-pending {
        color: #c2410c;
    }

    .pw-course-status-pending .pw-course-status-dot {
        background: #f97316;
    }

    .pw-course-status-draft {
        color: #64748b;
    }

    .pw-course-status-draft .pw-course-status-dot {
        background: #94a3b8;
    }

    .pw-course-status-rejected {
        color: #dc2626;
    }

    .pw-course-status-rejected .pw-course-status-dot {
        background: #ef4444;
    }

    .pw-course-menu {
        position: absolute;
        top: 44px;
        right: 0;
        z-index: 50;
        width: 165px;
        padding: 6px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 11px;
        background: #ffffff;
        box-shadow: 0 14px 36px rgba(15, 23, 42, .14);
    }

    .pw-course-menu-link,
    .pw-course-menu-button {
        width: 100%;
        display: block;
        border: 0;
        border-radius: 8px;
        background: transparent;
        padding: 9px 10px;
        color: #475569;
        font-size: 10px;
        font-weight: 600;
        text-align: left;
        text-decoration: none;
        cursor: pointer;
    }

    .pw-course-menu-link:hover,
    .pw-course-menu-button:hover {
        background: #f8fafc;
        color: #6d28d9;
    }

    .pw-course-menu-button.is-submit {
        color: #059669;
    }

    .pw-course-menu-button.is-submit:hover {
        background: #ecfdf5;
        color: #047857;
    }


    .pw-course-menu-button.is-delete {
        color: #dc2626;
    }

    .pw-course-menu-button.is-delete:hover {
        background: #fef2f2;
        color: #b91c1c;
    }

    .pw-course-line-clamp-2 {
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        overflow: hidden;
    }

    .pw-course-metrics {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        overflow: hidden;
        border: 1px solid #eef1f5;
        border-radius: 10px;
        background: #f8fafc;
    }

    .pw-course-metric {
        padding: 9px 7px;
        text-align: center;
    }

    .pw-course-metric + .pw-course-metric {
        border-left: 1px solid #eef1f5;
    }

    .pw-course-metric-label {
        font-size: 8px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #94a3b8;
    }

    .pw-course-metric-value {
        margin-top: 3px;
        font-size: 12px;
        font-weight: 800;
        color: #334155;
    }

    .pw-course-action-secondary,
    .pw-course-action-primary {
        height: 37px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border-radius: 9px;
        padding: 0 10px;
        font-size: 9.5px;
        font-weight: 700;
        transition: .15s ease;
    }

    .pw-course-action-secondary {
        border: 1px solid #ddd6fe;
        background: #fff;
        color: #6d28d9;
    }

    .pw-course-action-secondary:hover {
        background: #faf8ff;
        border-color: #c4b5fd;
    }

    .pw-course-action-primary {
        border: 1px solid #6d28d9;
        background: #6d28d9;
        color: #fff;
    }

    .pw-course-action-primary:hover {
        background: #5b21b6;
        border-color: #5b21b6;
    }

    .pw-course-empty {
        padding: 52px 24px;
        border: 1.5px dashed #dbe1e9;
        border-radius: 14px;
        background: #fff;
        text-align: center;
    }

    @media (max-width: 900px) {
        .pw-courses-shell {
            padding: 20px 16px 34px;
        }

        .pw-course-select {
            width: 100%;
            min-width: 0;
        }
    }
</style>


<div class="pw-courses-page">

    <main class="pw-courses-shell">


        {{-- =====================================================
             HEADER
        ====================================================== --}}

        <section
            class="flex flex-col gap-4
                   lg:flex-row lg:items-end lg:justify-between"
        >

            <div>

                <div
                    class="text-[10px] font-bold
                           uppercase tracking-[.14em]
                           text-violet-600"
                >
                    Course Management
                </div>

                <h1
                    class="mt-1 text-[26px]
                           font-extrabold
                           tracking-[-.03em]
                           text-slate-950"
                >
                    My Courses
                </h1>

                <p
                    class="mt-1.5 max-w-2xl
                           text-xs leading-5
                           text-slate-500"
                >
                    Create, organize, and manage your learning content.
                </p>

            </div>


            <a
                href="{{ route('teacher.courses.create') }}"
                class="inline-flex h-10
                       items-center justify-center gap-2
                       self-start rounded-lg
                       bg-violet-600 px-4
                       text-xs font-bold text-white
                       shadow-sm transition
                       hover:bg-violet-700"
            >
                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        d="M12 5v14M5 12h14"
                    />
                </svg>

                Create Course
            </a>

        </section>


{{-- =====================================================
     SUCCESS MESSAGE
====================================================== --}}

@if(session('success'))

    <div
        class="mt-5 flex items-start gap-3
               rounded-xl border border-emerald-200
               bg-emerald-50 px-4 py-3
               text-xs text-emerald-800"
    >

        <div
            class="mt-0.5 flex h-7 w-7
                   shrink-0 items-center
                   justify-center rounded-full
                   bg-emerald-100 text-emerald-600"
        >
            <svg
                width="15"
                height="15"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                />
            </svg>
        </div>

        <div>
            <div class="font-bold">
                Success
            </div>

            <div class="mt-0.5 text-emerald-700">
                {{ session('success') }}
            </div>
        </div>

    </div>

@endif


{{-- =====================================================
     ERROR MESSAGE
====================================================== --}}

@if(session('error'))

    <div
        class="mt-5 flex items-start gap-3
               rounded-xl border border-red-200
               bg-red-50 px-4 py-3
               text-xs text-red-800"
    >

        <div
            class="mt-0.5 flex h-7 w-7
                   shrink-0 items-center
                   justify-center rounded-full
                   bg-red-100 text-red-600"
        >
            <svg
                width="15"
                height="15"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 9v4m0 4h.01M10.29 3.86
                       1.82 18a2 2 0 0 0 1.71 3h16.94
                       a2 2 0 0 0 1.71-3L13.71 3.86
                       a2 2 0 0 0-3.42 0Z"
                />
            </svg>
        </div>

        <div>
            <div class="font-bold">
                Unable to submit
            </div>

            <div class="mt-0.5 text-red-700">
                {{ session('error') }}
            </div>
        </div>

    </div>

@endif


        {{-- =====================================================
             COURSE SUMMARY
        ====================================================== --}}

        <section
            class="mt-5 grid grid-cols-1 gap-4
                   sm:grid-cols-2 xl:grid-cols-4"
        >

            <div class="pw-course-panel pw-course-stat">

                <div>

                    <div class="pw-course-stat-label">
                        Total Courses
                    </div>

                    <div class="pw-course-stat-value">
                        {{ $totalCourses }}
                    </div>

                    <div class="pw-course-stat-hint">
                        Courses in your account
                    </div>

                </div>


                <div
                    class="pw-course-stat-icon
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
                        <path
                            d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292"
                        />
                        <path
                            d="M12 6.042A8.966 8.966 0 0 1 18 3.75c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292"
                        />
                    </svg>
                </div>

            </div>


            <div class="pw-course-panel pw-course-stat">

                <div>

                    <div class="pw-course-stat-label">
                        Published
                    </div>

                    <div class="pw-course-stat-value">
                        {{ $publishedCourses }}
                    </div>

                    <div class="pw-course-stat-hint">
                        Visible to learners
                    </div>

                </div>


                <div
                    class="pw-course-stat-icon
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
                        <circle cx="12" cy="12" r="9"></circle>
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m8.5 12.5 2.5 2.5 4.5-5"
                        />
                    </svg>
                </div>

            </div>


            <div class="pw-course-panel pw-course-stat">

                <div>

                    <div class="pw-course-stat-label">
                        Pending
                    </div>

                    <div class="pw-course-stat-value">
                        {{ $pendingCourses }}
                    </div>

                    <div class="pw-course-stat-hint">
                        Awaiting approval
                    </div>

                </div>


                <div
                    class="pw-course-stat-icon
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
                        <circle cx="12" cy="12" r="9"></circle>
                        <path
                            stroke-linecap="round"
                            d="M12 7v5l3 2"
                        />
                    </svg>
                </div>

            </div>


            <div class="pw-course-panel pw-course-stat">

                <div>

                    <div class="pw-course-stat-label">
                        Total Students
                    </div>

                    <div class="pw-course-stat-value">
                        {{ $totalStudents }}
                    </div>

                    <div class="pw-course-stat-hint">
                        Across all enrollments
                    </div>

                </div>


                <div
                    class="pw-course-stat-icon
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
                            d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                        />
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M19 8v6M22 11h-6"></path>
                    </svg>
                </div>

            </div>

        </section>



        {{-- =====================================================
             FILTER BAR
        ====================================================== --}}

        <section class="pw-course-panel mt-4 p-4">

            <div
                class="flex flex-col gap-3
                       xl:flex-row xl:items-center"
            >

                <div class="relative min-w-0 flex-1">

                    <svg
                        class="absolute left-3.5 top-1/2
                               -translate-y-1/2
                               text-slate-400"
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <circle cx="11" cy="11" r="7"></circle>
                        <path
                            stroke-linecap="round"
                            d="m20 20-3.5-3.5"
                        />
                    </svg>


                    <input
                        id="courseSearch"
                        type="text"
                        autocomplete="off"
                        placeholder="Search your courses..."
                        class="pw-course-search"
                    >

                </div>


                <select
                    id="courseStatusFilter"
                    class="pw-course-select"
                >
                    <option value="all">
                        All Status
                    </option>

                    <option value="published">
                        Published
                    </option>

                    <option value="pending">
                        Pending
                    </option>

                    <option value="draft">
                        Draft
                    </option>

                    <option value="rejected">
                        Rejected
                    </option>
                </select>


                <select
                    id="courseCategoryFilter"
                    class="pw-course-select"
                >
                    <option value="all">
                        All Categories
                    </option>

                    @foreach($courseCategories as $category)

                        <option value="{{ strtolower($category) }}">
                            {{ $category }}
                        </option>

                    @endforeach
                </select>

            </div>

        </section>



        {{-- =====================================================
             COURSES
        ====================================================== --}}

        <section class="mt-4">

            <div
                id="courseGrid"
                class="grid grid-cols-1 gap-4
                       md:grid-cols-2 2xl:grid-cols-3"
            >

                @forelse($courses as $course)

                    @php
                        $status = strtolower(
                            $course->status ?? 'draft'
                        );

                        $categoryName =
                            $course->category->name
                            ?? 'Uncategorized';

                        $lessonCount =
                            $course->lessons_count
                            ?? $course->lessons->count();

                        $studentCount =
                            $course->enrollments_count
                            ?? $course->enrollments->count();

                        $thumbnail =
                            $course->thumbnail
                            ?? null;

                        $thumbnailUrl = null;

                        if ($thumbnail) {
                            if (
                                \Illuminate\Support\Str::startsWith(
                                    $thumbnail,
                                    ['http://', 'https://']
                                )
                            ) {
                                $thumbnailUrl = $thumbnail;
                            } else {
                                $thumbnailUrl = asset(
                                    'storage/'
                                    . ltrim($thumbnail, '/')
                                );
                            }
                        }

                        $statusClass = match($status) {
                            'published' =>
                                'pw-course-status-published',

                            'pending' =>
                                'pw-course-status-pending',

                            'rejected' =>
                                'pw-course-status-rejected',

                            default =>
                                'pw-course-status-draft',
                        };
                    @endphp


                    <article
                        class="pw-course-card"
                        data-course-card
                        data-title="{{ strtolower($course->title) }}"
                        data-description="{{ strtolower($course->description ?? '') }}"
                        data-category="{{ strtolower($categoryName) }}"
                        data-status="{{ $status }}"
                    >


                        {{-- COVER --}}
                        <div class="pw-course-cover">

                            @if($thumbnailUrl)

                                <img
                                    src="{{ $thumbnailUrl }}"
                                    alt="{{ $course->title }}"
                                >

                            @else

                                <div class="pw-course-fallback">

                                    <div
                                        class="relative z-10
                                               text-center"
                                    >

                                        <div
                                            class="mx-auto grid
                                                   h-12 w-12
                                                   place-items-center
                                                   rounded-xl
                                                   border border-white/20
                                                   bg-white/10"
                                        >
                                            <svg
                                                width="24"
                                                height="24"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.7"
                                            >
                                                <path
                                                    d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292"
                                                />
                                                <path
                                                    d="M12 6.042A8.966 8.966 0 0 1 18 3.75c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292"
                                                />
                                            </svg>
                                        </div>


                                        <div
                                            class="mt-3 text-[9px]
                                                   font-bold uppercase
                                                   tracking-[.14em]
                                                   text-white/75"
                                        >
                                            {{ $categoryName }}
                                        </div>

                                    </div>

                                </div>

                            @endif



                            {{-- STATUS --}}
                            <div class="absolute left-3 top-3">

                                <span
                                    class="pw-course-status
                                           {{ $statusClass }}"
                                >
                                    <span
                                        class="pw-course-status-dot"
                                    ></span>

                                    {{ $status }}
                                </span>

                            </div>



                            {{-- MENU --}}
                            <div class="absolute right-3 top-3">

                                <button
                                    type="button"
                                    onclick="
                                        document
                                            .getElementById(
                                                'menu{{ $course->id }}'
                                            )
                                            .classList
                                            .toggle('hidden')
                                    "
                                    class="grid h-8 w-8
                                           place-items-center
                                           rounded-lg
                                           bg-white/95
                                           text-slate-500
                                           shadow-sm
                                           backdrop-blur
                                           transition
                                           hover:text-violet-600"
                                    aria-label="Open course menu"
                                >
                                    <svg
                                        width="15"
                                        height="15"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                    >
                                        <circle cx="5" cy="12" r="1.5"></circle>
                                        <circle cx="12" cy="12" r="1.5"></circle>
                                        <circle cx="19" cy="12" r="1.5"></circle>
                                    </svg>
                                </button>


                                <div
                                    id="menu{{ $course->id }}"
                                    class="pw-course-menu hidden"
                                >

                                    <a
                                        href="{{ route('teacher.lessons', $course) }}"
                                        class="pw-course-menu-link"
                                    >
                                        Manage Lessons
                                    </a>


                                    <a
                                        href="{{ route('teacher.course.students', $course) }}"
                                        class="pw-course-menu-link"
                                    >
                                        View Students
                                    </a>


                                    @if(
                                        in_array(
                                            $course->status,
                                            ['draft', 'rejected']
                                        )
                                    )

                                        <form
                                            action="{{ route('teacher.courses.submit', $course) }}"
                                            method="POST"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                class="pw-course-menu-button is-submit"
                                            >
                                                {{ $course->status === 'rejected'
    ? 'Resubmit for Approval'
    : 'Submit for Approval'
}}
                                            </button>

                                        </form>


                                        <form
                                            action="{{ route('teacher.courses.destroy', $course) }}"
                                            method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this course? This action cannot be undone.');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="pw-course-menu-button is-delete"
                                            >
                                                Delete Course
                                            </button>

                                        </form>

                                    @endif

                                </div>

                            </div>

                        </div>



                        {{-- CONTENT --}}
                        <div class="p-4">

                            <div
                                class="flex flex-wrap
                                       items-center gap-2"
                            >

                                <span
                                    class="text-[9px]
                                           font-bold
                                           text-violet-600"
                                >
                                    {{ $categoryName }}
                                </span>


                                @if(!empty($course->difficulty_level))

                                    <span class="text-slate-300">
                                        •
                                    </span>

                                    <span
                                        class="text-[9px]
                                               font-semibold
                                               text-slate-400"
                                    >
                                        {{ ucfirst($course->difficulty_level) }}
                                    </span>

                                @endif

                            </div>


                            <h2
                                class="pw-course-line-clamp-2
                                       mt-2 min-h-[42px]
                                       text-[15px]
                                       font-extrabold
                                       leading-[21px]
                                       text-slate-900"
                            >
                                {{ $course->title }}
                            </h2>


                            <p
                                class="pw-course-line-clamp-2
                                       mt-1.5 min-h-[36px]
                                       text-[10.5px]
                                       leading-[18px]
                                       text-slate-500"
                            >
                                {{
                                    $course->description
                                    ?: 'Add a course description to help students understand what they will learn.'
                                }}
                            </p>



                            {{-- METRICS --}}
                            <div class="pw-course-metrics mt-4">

                                <div class="pw-course-metric">

                                    <div class="pw-course-metric-label">
                                        Lessons
                                    </div>

                                    <div class="pw-course-metric-value">
                                        {{ $lessonCount }}
                                    </div>

                                </div>


                                <div class="pw-course-metric">

                                    <div class="pw-course-metric-label">
                                        Students
                                    </div>

                                    <div class="pw-course-metric-value">
                                        {{ $studentCount }}
                                    </div>

                                </div>


                                <div class="pw-course-metric">

                                    <div class="pw-course-metric-label">
                                        Price
                                    </div>

                                    <div class="pw-course-metric-value">
                                        ₱{{ number_format($course->price ?? 0, 2) }}
                                    </div>

                                </div>

                            </div>



                            {{-- META --}}
                            @if(
                                !empty($course->estimated_hours)
                                || $course->certificate_available
                            )

                                <div
                                    class="mt-3 flex flex-wrap
                                           gap-x-4 gap-y-2
                                           text-[9px]
                                           font-medium
                                           text-slate-400"
                                >

                                    @if(!empty($course->estimated_hours))

                                        <span
                                            class="inline-flex
                                                   items-center gap-1.5"
                                        >
                                            <svg
                                                width="14"
                                                height="14"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.8"
                                            >
                                                <circle cx="12" cy="12" r="9"></circle>
                                                <path
                                                    stroke-linecap="round"
                                                    d="M12 7v5l3 2"
                                                />
                                            </svg>

                                            {{ $course->estimated_hours }}
                                            {{
                                                \Illuminate\Support\Str::plural(
                                                    'hour',
                                                    $course->estimated_hours
                                                )
                                            }}
                                        </span>

                                    @endif


                                    @if($course->certificate_available)

                                        <span
                                            class="inline-flex
                                                   items-center gap-1.5"
                                        >
                                            <svg
                                                width="14"
                                                height="14"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.8"
                                            >
                                                <circle cx="12" cy="12" r="9"></circle>
                                                <path
                                                    d="m12 7 1.4 2.8 3.1.5-2.2 2.2.5 3.1-2.8-1.4-2.8 1.4.5-3.1-2.2-2.2 3.1-.5Z"
                                                />
                                            </svg>

                                            Certificate
                                        </span>

                                    @endif

                                </div>

                            @endif



                            {{-- ACTIONS --}}
                            <div class="mt-4 grid grid-cols-2 gap-2">

                                <a
                                    href="{{ route('teacher.course.students', $course) }}"
                                    class="pw-course-action-secondary"
                                >
                                    <svg
                                        width="14"
                                        height="14"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                                        />
                                        <circle cx="9" cy="7" r="4"></circle>
                                    </svg>

                                    Students
                                </a>


                                <a
                                    href="{{ route('teacher.lessons', $course) }}"
                                    class="pw-course-action-primary"
                                >
                                    <svg
                                        width="14"
                                        height="14"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"
                                        />
                                        <path
                                            d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"
                                        />
                                    </svg>

                                    Lessons
                                </a>

                            </div>



                            @if(
                                in_array(
                                    $course->status,
                                    ['draft', 'rejected']
                                )
                            )

                                <form
                                    action="{{ route('teacher.courses.submit', $course) }}"
                                    method="POST"
                                    class="mt-2"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="inline-flex h-9 w-full
                                               items-center justify-center
                                               gap-2 rounded-lg
                                               bg-emerald-50
                                               px-3 text-[9.5px]
                                               font-bold text-emerald-700
                                               transition
                                               hover:bg-emerald-100"
                                    >
                                        {{ $course->status === 'rejected'
    ? 'Resubmit for Approval'
    : 'Submit for Approval'
}}
                                    </button>

                                </form>

                            @endif

                        </div>

                    </article>


                @empty

                    <div
                        class="pw-course-empty
                               col-span-full"
                    >

                        <div
                            class="mx-auto grid h-12 w-12
                                   place-items-center
                                   rounded-full
                                   bg-violet-50
                                   text-violet-600"
                        >
                            <svg
                                width="22"
                                height="22"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292"
                                />
                                <path
                                    d="M12 6.042A8.966 8.966 0 0 1 18 3.75c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292"
                                />
                            </svg>
                        </div>


                        <h3
                            class="mt-4 text-sm
                                   font-extrabold text-slate-900"
                        >
                            You haven't created a course yet
                        </h3>


                        <p
                            class="mx-auto mt-1.5
                                   max-w-md text-[10.5px]
                                   leading-5 text-slate-500"
                        >
                            Create your first PathWise course and start
                            building lessons and learning content.
                        </p>


                        <a
                            href="{{ route('teacher.courses.create') }}"
                            class="mt-5 inline-flex h-10
                                   items-center gap-2
                                   rounded-lg bg-violet-600
                                   px-4 text-xs font-bold
                                   text-white transition
                                   hover:bg-violet-700"
                        >
                            <span class="text-base">+</span>
                            Create Your First Course
                        </a>

                    </div>

                @endforelse

            </div>



            {{-- NO FILTER RESULTS --}}
            <div
                id="noCourseResults"
                class="pw-course-empty mt-4 hidden"
            >

                <div
                    class="mx-auto grid h-11 w-11
                           place-items-center
                           rounded-full bg-slate-100
                           text-slate-400"
                >
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <circle cx="11" cy="11" r="7"></circle>
                        <path
                            stroke-linecap="round"
                            d="m20 20-3.5-3.5"
                        />
                    </svg>
                </div>


                <div
                    class="mt-3 text-xs
                           font-bold text-slate-700"
                >
                    No courses found
                </div>


                <div
                    class="mt-1 text-[9.5px]
                           text-slate-400"
                >
                    Try changing your search or filters.
                </div>

            </div>

        </section>



        {{-- =====================================================
             FOOTER SUMMARY
        ====================================================== --}}

        @if($totalCourses > 0)

            <div
                class="mt-5 flex flex-wrap
                       items-center justify-between
                       gap-3 border-t
                       border-slate-200 pt-4"
            >

                <p class="text-[9.5px] text-slate-400">

                    Showing

                    <span
                        id="visibleCourseCount"
                        class="font-bold text-slate-600"
                    >
                        {{ $totalCourses }}
                    </span>

                    of

                    <span class="font-bold text-slate-600">
                        {{ $totalCourses }}
                    </span>

                    courses

                </p>


                @if($draftCourses > 0)

                    <p class="text-[9.5px] text-slate-400">

                        {{ $draftCourses }}

                        {{
                            \Illuminate\Support\Str::plural(
                                'course',
                                $draftCourses
                            )
                        }}

                        still in draft

                    </p>

                @endif

            </div>

        @endif


    </main>

</div>



{{-- =========================================================
     SEARCH / FILTER LOGIC
========================================================= --}}

<script>
    function initCoursePage() {
        const searchInput =
            document.getElementById('courseSearch');

        const statusFilter =
            document.getElementById('courseStatusFilter');

        const categoryFilter =
            document.getElementById('courseCategoryFilter');

        const courseGrid =
            document.getElementById('courseGrid');

        const emptyState =
            document.getElementById('noCourseResults');

        const countLabel =
            document.getElementById('visibleCourseCount');

        if (!courseGrid) {
            return;
        }

        if (courseGrid.dataset.pwInitialized === 'true') {
            return;
        }

        courseGrid.dataset.pwInitialized = 'true';


        function filterCourses() {
            const query =
                searchInput
                    ? searchInput.value
                        .trim()
                        .toLowerCase()
                    : '';

            const selectedStatus =
                statusFilter
                    ? statusFilter.value
                        .toLowerCase()
                    : 'all';

            const selectedCategory =
                categoryFilter
                    ? categoryFilter.value
                        .toLowerCase()
                    : 'all';

            const cards =
                courseGrid.querySelectorAll(
                    '[data-course-card]'
                );

            let visible = 0;


            cards.forEach(function (card) {
                const title =
                    card.dataset.title || '';

                const description =
                    card.dataset.description || '';

                const status =
                    card.dataset.status || '';

                const category =
                    card.dataset.category || '';


                const matchesSearch =
                    !query
                    || title.includes(query)
                    || description.includes(query)
                    || category.includes(query);


                const matchesStatus =
                    selectedStatus === 'all'
                    || status === selectedStatus;


                const matchesCategory =
                    selectedCategory === 'all'
                    || category === selectedCategory;


                const shouldShow =
                    matchesSearch
                    && matchesStatus
                    && matchesCategory;


                card.style.display =
                    shouldShow
                        ? ''
                        : 'none';


                if (shouldShow) {
                    visible++;
                }
            });


            if (emptyState) {
                emptyState.classList.toggle(
                    'hidden',
                    visible !== 0
                );
            }


            if (countLabel) {
                countLabel.textContent = visible;
            }
        }


        if (searchInput) {
            searchInput.addEventListener(
                'input',
                filterCourses
            );
        }


        if (statusFilter) {
            statusFilter.addEventListener(
                'change',
                filterCourses
            );
        }


        if (categoryFilter) {
            categoryFilter.addEventListener(
                'change',
                filterCourses
            );
        }


        filterCourses();
    }


    document.addEventListener(
        'DOMContentLoaded',
        initCoursePage
    );


    document.addEventListener(
        'livewire:navigated',
        initCoursePage
    );
</script>

</x-layouts::app>
