<x-layouts::app :title="__('My Courses')">

    @php
        $totalCourses = $courses->count();
        $publishedCourses = $courses->where('status', 'published')->count();
        $pendingCourses = $courses->where('status', 'pending')->count();
        $draftCourses = $courses->where('status', 'draft')->count();
        $totalStudents = $courses->sum('enrollments_count');

        $courseCategories = $courses
            ->map(fn ($c) => $c->category->name ?? 'Uncategorized')
            ->unique()
            ->sort()
            ->values();
    @endphp

    <style>
        .pw-card {
            background: #ffffff;
            border: 1px solid #e7e9ef;
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.035);
            transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
        }
        .pw-card:hover {
            transform: translateY(-3px);
            border-color: #ddd6fe;
            box-shadow: 0 18px 45px rgba(76, 29, 149, 0.08);
        }
        .pw-line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>

    <div class="min-h-screen bg-[#f8f9fc]">

        <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

            <div class="mx-auto max-w-[1500px]">


                {{-- HEADER --}}
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">

                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-violet-600">
                            Course Management
                        </p>
                        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                            My Courses
                        </h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                            Create, organize, and manage your learning content.
                        </p>
                    </div>

                    <a href="{{ route('teacher.courses.create') }}"
                       class="inline-flex h-11 items-center justify-center gap-2 self-start rounded-xl bg-violet-600 px-5 text-sm font-semibold text-white shadow-md transition hover:bg-violet-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        Create Course
                    </a>

                </div>


                {{-- SUCCESS --}}
                @if(session('success'))
                    <div class="mt-6 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                        <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold">Success</p>
                            <p class="mt-0.5 text-emerald-700">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif


                {{-- STATS --}}
                <section class="mt-7 grid grid-cols-2 gap-4 lg:grid-cols-4">

                    <div class="pw-card p-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-semibold text-slate-500">Total Courses</p>
                                <p class="mt-2 text-3xl font-bold text-slate-950">{{ $totalCourses }}</p>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="pw-card p-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-semibold text-slate-500">Published</p>
                                <p class="mt-2 text-3xl font-bold text-emerald-600">{{ $publishedCourses }}</p>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="pw-card p-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-semibold text-slate-500">Pending</p>
                                <p class="mt-2 text-3xl font-bold text-orange-500">{{ $pendingCourses }}</p>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-orange-500">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="pw-card p-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-semibold text-slate-500">Total Students</p>
                                <p class="mt-2 text-3xl font-bold text-blue-600">{{ $totalStudents }}</p>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm13 10v-2a4 4 0 00-3-3.87"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                </section>


                {{-- FILTERS --}}
                <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-center">

                        <div class="relative min-w-0 flex-1">
                            <svg class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                            </svg>
                            <input id="courseSearch" type="text" placeholder="Search your courses..." autocomplete="off"
                                   class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                        </div>

                        <select id="courseStatusFilter" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-600 outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                            <option value="all">All Status</option>
                            <option value="published">Published</option>
                            <option value="pending">Pending</option>
                            <option value="draft">Draft</option>
                            <option value="rejected">Rejected</option>
                        </select>

                        <select id="courseCategoryFilter" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-600 outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                            <option value="all">All Categories</option>
                            @foreach($courseCategories as $category)
                                <option value="{{ strtolower($category) }}">{{ $category }}</option>
                            @endforeach
                        </select>

                    </div>
                </section>


                {{-- COURSES GRID --}}
                <section class="mt-6">
                    <div id="courseGrid" class="grid grid-cols-1 gap-5 md:grid-cols-2 2xl:grid-cols-3">

                        @forelse($courses as $course)

                            @php
                                $status = strtolower($course->status ?? 'draft');
                                $categoryName = $course->category->name ?? 'Uncategorized';
                                $lessonCount = $course->lessons_count ?? $course->lessons->count();
                                $studentCount = $course->enrollments_count ?? $course->enrollments->count();

                                $thumbnail = $course->thumbnail ?? null;
                                $thumbnailUrl = null;
                                if ($thumbnail) {
                                    if (\Illuminate\Support\Str::startsWith($thumbnail, ['http://', 'https://'])) {
                                        $thumbnailUrl = $thumbnail;
                                    } else {
                                        $thumbnailUrl = asset('storage/' . ltrim($thumbnail, '/'));
                                    }
                                }

                                $statusStyle = match($status) {
                                    'published' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'pending'   => 'bg-orange-50 text-orange-600 border-orange-200',
                                    'rejected'  => 'bg-red-50 text-red-600 border-red-200',
                                    'draft'     => 'bg-slate-100 text-slate-600 border-slate-200',
                                    default     => 'bg-blue-50 text-blue-600 border-blue-200',
                                };

                                $statusDot = match($status) {
                                    'published' => 'bg-emerald-500',
                                    'pending'   => 'bg-orange-500',
                                    'rejected'  => 'bg-red-500',
                                    default     => 'bg-slate-400',
                                };

                                $gradients = [
                                    'from-violet-600 via-purple-600 to-indigo-700',
                                    'from-emerald-500 via-teal-500 to-cyan-600',
                                    'from-blue-500 via-indigo-500 to-violet-600',
                                    'from-fuchsia-500 via-purple-600 to-violet-700',
                                    'from-cyan-500 via-blue-600 to-indigo-700',
                                    'from-orange-400 via-rose-500 to-purple-600',
                                ];
                                $fallbackGradient = $gradients[$loop->index % 6];
                            @endphp

                            <article class="pw-card overflow-hidden" data-course-card
                                     data-title="{{ strtolower($course->title) }}"
                                     data-description="{{ strtolower($course->description ?? '') }}"
                                     data-category="{{ strtolower($categoryName) }}"
                                     data-status="{{ $status }}">


                                {{-- COVER --}}
                                <div class="relative h-[190px] overflow-hidden bg-slate-100">

                                    @if($thumbnailUrl)
                                        <img src="{{ $thumbnailUrl }}" alt="{{ $course->title }}"
                                             class="h-full w-full object-cover transition duration-300 hover:scale-[1.03]">
                                    @else
                                        <div class="relative flex h-full w-full items-center justify-center overflow-hidden bg-gradient-to-br {{ $fallbackGradient }}">
                                            <div class="absolute -right-12 -top-12 h-40 w-40 rounded-full border-[30px] border-white/10"></div>
                                            <div class="absolute -bottom-16 -left-10 h-44 w-44 rounded-full bg-white/10"></div>
                                            <div class="absolute right-10 bottom-7 h-16 w-16 rotate-12 rounded-2xl border border-white/15 bg-white/10"></div>

                                            <div class="relative z-10 text-center text-white">
                                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-white/20 bg-white/15 shadow-xl backdrop-blur-sm">
                                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                                        <path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                                                    </svg>
                                                </div>
                                                <p class="mt-3 text-xs font-semibold uppercase tracking-[0.14em] text-white/75">
                                                    {{ strtoupper($categoryName) }}
                                                </p>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Published Badge --}}
                                    <div class="absolute left-3 top-3">
                                        <span class="inline-flex items-center gap-1.5 rounded-full border bg-white/95 px-3 py-1.5 text-[10px] font-bold shadow-sm backdrop-blur {{ $statusStyle }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                                            {{ ucfirst($status) }}
                                        </span>
                                    </div>

                                    {{-- Menu --}}
                                    <div class="absolute right-3 top-3">
                                        <button type="button"
                                                onclick="document.getElementById('menu{{ $course->id }}').classList.toggle('hidden')"
                                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/95 text-slate-600 shadow-sm backdrop-blur transition hover:text-violet-600">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                                <circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/>
                                            </svg>
                                        </button>
                                        <div id="menu{{ $course->id }}" class="absolute right-0 top-11 z-30 hidden w-44 overflow-hidden rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl">
                                            <a href="{{ route('teacher.lessons', $course) }}" class="block rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">Manage Lessons</a>
                                            <a href="{{ route('teacher.course.students', $course) }}" class="block rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">View Students</a>
                                            @if(in_array($course->status, ['draft', 'rejected']))
                                                <form action="{{ route('teacher.courses.submit', $course) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-xs font-medium text-emerald-600 hover:bg-emerald-50">
                                                        Submit for Approval
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>

                                </div>


                                {{-- CONTENT --}}
                                <div class="p-5">

                                    {{-- Category & Difficulty --}}
                                    <div class="flex flex-wrap items-center gap-2 text-xs">
                                        <span class="font-semibold text-violet-600">{{ $categoryName }}</span>
                                        @if(!empty($course->difficulty_level))
                                            <span class="text-slate-300">•</span>
                                            <span class="font-medium text-slate-500">{{ ucfirst($course->difficulty_level) }}</span>
                                        @endif
                                    </div>

                                    {{-- Title --}}
                                    <h2 class="pw-line-clamp-2 mt-2 text-[17px] font-bold leading-6 text-slate-900">
                                        {{ $course->title }}
                                    </h2>

                                    {{-- Description --}}
                                    <p class="pw-line-clamp-2 mt-2 min-h-[44px] text-sm leading-[22px] text-slate-500">
                                        {{ $course->description ?: 'Add a course description to help students understand what they will learn.' }}
                                    </p>

                                    {{-- Metrics --}}
                                    <div class="mt-5 grid grid-cols-3 divide-x divide-slate-100 rounded-xl border border-slate-100 bg-slate-50">
                                        <div class="px-2 py-3 text-center">
                                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Lessons</p>
                                            <p class="mt-1 text-base font-bold text-slate-800">{{ $lessonCount }}</p>
                                        </div>
                                        <div class="px-2 py-3 text-center">
                                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Students</p>
                                            <p class="mt-1 text-base font-bold text-slate-800">{{ $studentCount }}</p>
                                        </div>
                                        <div class="px-2 py-3 text-center">
                                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Price</p>
                                            <p class="mt-1 text-base font-bold text-slate-800">₱{{ number_format($course->price ?? 0, 2) }}</p>
                                        </div>
                                    </div>

                                    {{-- Extra Info --}}
                                    @if(!empty($course->estimated_hours) || $course->certificate_available)
                                        <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-500">
                                            @if(!empty($course->estimated_hours))
                                                <span class="inline-flex items-center gap-1.5">
                                                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                                                    </svg>
                                                    {{ $course->estimated_hours }} {{ \Illuminate\Support\Str::plural('hour', $course->estimated_hours) }}
                                                </span>
                                            @endif
                                            @if($course->certificate_available)
                                                <span class="inline-flex items-center gap-1.5">
                                                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M12 15l-3 2 1-3.5L7 11h4l1-3 1 3h4l-3 2.5 1 3.5z"/><circle cx="12" cy="12" r="9"/>
                                                    </svg>
                                                    Certificate
                                                </span>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Actions --}}
                                    <div class="mt-5 grid grid-cols-2 gap-2">
                                        <a href="{{ route('teacher.course.students', $course) }}"
                                           class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-violet-200 bg-white px-3 text-xs font-semibold text-violet-700 transition hover:bg-violet-50">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm13 10v-2a4 4 0 00-3-3.87"/>
                                            </svg>
                                            View Students
                                        </a>
                                        <a href="{{ route('teacher.lessons', $course) }}"
                                           class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-violet-600 px-3 text-xs font-semibold text-white transition hover:bg-violet-700">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M4 19.5A2.5 2.5 0 016.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                                            </svg>
                                            Manage Lessons
                                        </a>
                                    </div>

                                    @if(in_array($course->status, ['draft', 'rejected']))
                                        <form action="{{ route('teacher.courses.submit', $course) }}" method="POST" class="mt-2">
                                            @csrf
                                            <button type="submit" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-emerald-50 px-3 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Submit for Approval
                                            </button>
                                        </form>
                                    @endif

                                </div>

                            </article>

                        @empty

                            <div class="col-span-full rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-16 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                                    </svg>
                                </div>
                                <h3 class="mt-5 text-lg font-bold text-slate-900">You haven't created a course yet</h3>
                                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                                    Create your first PathWise course and start adding lessons, videos, activities, and quizzes.
                                </p>
                                <a href="{{ route('teacher.courses.create') }}" class="mt-6 inline-flex h-11 items-center gap-2 rounded-xl bg-violet-600 px-5 text-sm font-semibold text-white transition hover:bg-violet-700">
                                    <span class="text-lg">+</span> Create Your First Course
                                </a>
                            </div>

                        @endforelse

                    </div>

                    {{-- No Search Results --}}
                    <div id="noCourseResults" class="hidden rounded-2xl border-2 border-dashed border-slate-200 bg-white px-6 py-14 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                            </svg>
                        </div>
                        <p class="mt-4 text-sm font-semibold text-slate-700">No courses found</p>
                        <p class="mt-1 text-xs text-slate-400">Try changing your search or filters.</p>
                    </div>

                </section>


                {{-- Footer Count --}}
                @if($totalCourses > 0)
                    <div class="mt-7 flex items-center justify-between border-t border-slate-200 pt-5">
                        <p class="text-xs text-slate-400">
                            Showing <span id="visibleCourseCount" class="font-semibold text-slate-600">{{ $totalCourses }}</span>
                            of <span class="font-semibold text-slate-600">{{ $totalCourses }}</span> courses
                        </p>
                        @if($draftCourses > 0)
                            <p class="text-xs text-slate-400">{{ $draftCourses }} {{ \Illuminate\Support\Str::plural('course', $draftCourses) }} still in draft</p>
                        @endif
                    </div>
                @endif

            </div>

        </main>

    </div>


    {{-- SEARCH / FILTER SCRIPT --}}
    <script>
        function initCoursePage() {
            const searchInput = document.getElementById('courseSearch');
            const statusFilter = document.getElementById('courseStatusFilter');
            const categoryFilter = document.getElementById('courseCategoryFilter');
            const courseGrid = document.getElementById('courseGrid');
            const emptyState = document.getElementById('noCourseResults');
            const countLabel = document.getElementById('visibleCourseCount');

            if (!courseGrid) return;

            function filterCourses() {
                const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
                const selectedStatus = statusFilter ? statusFilter.value.toLowerCase() : 'all';
                const selectedCategory = categoryFilter ? categoryFilter.value.toLowerCase() : 'all';

                const cards = courseGrid.querySelectorAll('[data-course-card]');
                let visible = 0;

                cards.forEach(function(card) {
                    const title = card.dataset.title || '';
                    const description = card.dataset.description || '';
                    const status = card.dataset.status || '';
                    const category = card.dataset.category || '';

                    const matchesSearch = !query || title.includes(query) || description.includes(query) || category.includes(query);
                    const matchesStatus = selectedStatus === 'all' || status === selectedStatus;
                    const matchesCategory = selectedCategory === 'all' || category === selectedCategory;

                    const shouldShow = matchesSearch && matchesStatus && matchesCategory;
                    card.style.display = shouldShow ? '' : 'none';
                    if (shouldShow) visible++;
                });

                if (emptyState) emptyState.classList.toggle('hidden', visible !== 0);
                if (countLabel) countLabel.textContent = visible;
            }

            if (searchInput) searchInput.addEventListener('input', filterCourses);
            if (statusFilter) statusFilter.addEventListener('change', filterCourses);
            if (categoryFilter) categoryFilter.addEventListener('change', filterCourses);

            filterCourses();
        }

        document.addEventListener('DOMContentLoaded', initCoursePage);
        document.addEventListener('livewire:navigated', initCoursePage);
    </script>

</x-layouts::app>