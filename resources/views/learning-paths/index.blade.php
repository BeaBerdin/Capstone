<x-layouts::app :title="'Learning Paths'">

<div class="space-y-6">

    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold">Learning Paths</h1>
            <p class="text-gray-500">
                Manage structured course paths for personalized learning.
            </p>
        </div>

        <a href="{{ route('learning-paths.create') }}"
           class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
            Add Learning Path
        </a>
    </div>

    @if(session('success'))
        <div class="rounded bg-green-100 p-4 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-neutral-900 dark:border-neutral-700">

        <table class="w-full">
            <thead>
                <tr class="border-b">
                    <th class="p-3 text-left">Path Name</th>
                    <th class="p-3 text-left">Description</th>
                    <th class="p-3 text-left">Courses</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($learningPaths as $path)
                    <tr class="border-b">
                        <td class="p-3 font-semibold">
                            {{ $path->name }}
                        </td>

                        <td class="p-3">
                            {{ $path->description ?? '-' }}
                        </td>

                        <td class="p-3">
                            {{ $path->courses->count() }}
                        </td>

                        <td class="p-3 flex gap-2">
                            <a href="{{ route('learning-paths.show', $path) }}"
                               class="text-green-600">
                                View
                            </a>

                            <a href="{{ route('learning-paths.edit', $path) }}"
                               class="text-blue-600">
                                Edit
                            </a>

                            <form action="{{ route('learning-paths.destroy', $path) }}"
                                  method="POST"
                                  class="inline">
                                @csrf
                                @method('DELETE')

                                <button class="text-red-600"
                                        onclick="return confirm('Delete this learning path?')">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-4 text-center">
                            No learning paths found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>

</div>

</x-layouts::app><x-layouts::app :title="'Learning Paths'">

<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-[1500px]">

            {{-- =====================================================
                HEADER
            ====================================================== --}}

            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">

                <div>

                    <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                        Personalized Learning
                    </p>

                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                        Learning Paths
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                        Follow structured learning paths designed to help you
                        develop your skills step by step.
                    </p>

                </div>

                <div class="rounded-xl border border-violet-100 bg-violet-50 px-4 py-3">

                    <p class="text-[10px] font-bold uppercase tracking-[.08em] text-violet-500">
                        Available Paths
                    </p>

                    <p class="mt-1 text-xl font-bold text-violet-900">
                        {{ $learningPaths->count() }}
                    </p>

                </div>

            </div>


            {{-- =====================================================
                SUCCESS MESSAGE
            ====================================================== --}}

            @if(session('success'))

                <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif


            {{-- =====================================================
                LEARNING PATHS
            ====================================================== --}}

            <section class="mt-7">

                @if($learningPaths->isNotEmpty())

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">

                        @foreach($learningPaths as $path)

                            @php
                                $courseCount = $path->courses->count();
                            @endphp

                            <article
                                class="group overflow-hidden rounded-2xl border border-slate-200
                                       bg-white shadow-sm transition duration-200
                                       hover:-translate-y-1 hover:border-violet-200
                                       hover:shadow-xl hover:shadow-violet-950/5"
                            >

                                {{-- PATH HEADER --}}

                                <div class="relative overflow-hidden bg-gradient-to-br from-violet-600 via-indigo-600 to-blue-600 p-6">

                                    <div class="absolute -right-8 -top-8 h-28 w-28 rounded-full bg-white/10"></div>

                                    <div class="absolute -bottom-10 -left-6 h-24 w-24 rounded-full bg-white/10"></div>

                                    <div class="relative">

                                        <div class="flex items-center justify-between">

                                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 text-white backdrop-blur-sm">

                                                <svg
                                                    class="h-6 w-6"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                >
                                                    <circle cx="6" cy="6" r="2"></circle>
                                                    <circle cx="18" cy="18" r="2"></circle>
                                                    <path d="M8 6h4a4 4 0 014 4v6"></path>
                                                </svg>

                                            </div>

                                            <span class="rounded-full bg-white/15 px-3 py-1.5 text-[10px] font-bold text-white backdrop-blur-sm">
                                                {{ $courseCount }}
                                                {{ \Illuminate\Support\Str::plural('Course', $courseCount) }}
                                            </span>

                                        </div>


                                        <p class="mt-6 text-[10px] font-bold uppercase tracking-[.12em] text-violet-100">
                                            Learning Path
                                        </p>

                                        <h2 class="mt-2 line-clamp-2 text-xl font-bold leading-7 text-white">
                                            {{ $path->name }}
                                        </h2>

                                    </div>

                                </div>


                                {{-- PATH CONTENT --}}

                                <div class="p-5">

                                    @if($path->description)

                                        <p class="line-clamp-3 text-sm leading-6 text-slate-500">
                                            {{ $path->description }}
                                        </p>

                                    @else

                                        <p class="text-sm leading-6 text-slate-400">
                                            A structured collection of courses to guide your learning journey.
                                        </p>

                                    @endif


                                    {{-- COURSE PREVIEW --}}

                                    <div class="mt-5">

                                        <div class="flex items-center justify-between">

                                            <p class="text-[10px] font-bold uppercase tracking-[.08em] text-slate-400">
                                                Included Courses
                                            </p>

                                            <span class="text-[10px] font-semibold text-violet-600">
                                                {{ $courseCount }} total
                                            </span>

                                        </div>


                                        @if($path->courses->isNotEmpty())

                                            <div class="mt-3 space-y-2">

                                                @foreach($path->courses->take(3) as $course)

                                                    <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">

                                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-600">

                                                            <svg
                                                                class="h-4 w-4"
                                                                viewBox="0 0 24 24"
                                                                fill="none"
                                                                stroke="currentColor"
                                                                stroke-width="2"
                                                            >
                                                                <path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                                                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                                                            </svg>

                                                        </div>

                                                        <p class="min-w-0 flex-1 truncate text-xs font-semibold text-slate-700">
                                                            {{ $course->title }}
                                                        </p>

                                                    </div>

                                                @endforeach


                                                @if($courseCount > 3)

                                                    <p class="pt-1 text-center text-[10px] font-medium text-slate-400">
                                                        + {{ $courseCount - 3 }}
                                                        more
                                                        {{ \Illuminate\Support\Str::plural('course', $courseCount - 3) }}
                                                    </p>

                                                @endif

                                            </div>

                                        @else

                                            <div class="mt-3 rounded-xl bg-slate-50 p-4 text-center">

                                                <p class="text-xs text-slate-400">
                                                    No courses have been added yet.
                                                </p>

                                            </div>

                                        @endif

                                    </div>


                                    {{-- ACTION --}}

                                    <a
                                        href="{{ route('learning-paths.show', $path) }}"
                                        class="mt-5 inline-flex h-10 w-full items-center
                                               justify-center gap-2 rounded-xl
                                               bg-violet-600 px-4 text-xs font-semibold
                                               text-white transition
                                               hover:bg-violet-700"
                                    >

                                        Explore Learning Path

                                        <svg
                                            class="h-4 w-4 transition-transform group-hover:translate-x-0.5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path d="M5 12h14"></path>
                                            <path d="m13 6 6 6-6 6"></path>
                                        </svg>

                                    </a>

                                </div>

                            </article>

                        @endforeach

                    </div>


                @else

                    {{-- =================================================
                        EMPTY STATE
                    ================================================== --}}

                    <div class="rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm">

                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">

                            <svg
                                class="h-8 w-8"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                            >
                                <circle cx="6" cy="6" r="2"></circle>
                                <circle cx="18" cy="18" r="2"></circle>
                                <path d="M8 6h4a4 4 0 014 4v6"></path>
                            </svg>

                        </div>

                        <h2 class="mt-5 text-lg font-bold text-slate-800">
                            No Learning Paths Available
                        </h2>

                        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-400">
                            Learning paths will appear here once they are
                            available for students.
                        </p>

                        <a
                            href="{{ route('student.marketplace') }}"
                            class="mt-5 inline-flex h-10 items-center justify-center
                                   rounded-xl bg-violet-600 px-5 text-xs font-semibold
                                   text-white transition hover:bg-violet-700"
                        >
                            Browse Courses
                        </a>

                    </div>

                @endif

            </section>

        </div>

    </main>

</div>

</x-layouts::app>