<x-layouts::app :title="'Learning Paths'">

<div class="space-y-8">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600">
                    Dashboard
                </a>
                <span>/</span>
                <span>Learning Paths</span>
            </div>

            <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                Learning Paths
            </h1>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Structured course paths to help learners build skills step by step.
            </p>
        </div>

        <a href="{{ route('learning-paths.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-md
                  bg-blue-600 px-5 py-3 text-sm font-semibold text-white
                  shadow-sm transition hover:bg-blue-700">

            <svg xmlns="http://www.w3.org/2000/svg"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke-width="2"
                 stroke="currentColor"
                 class="h-5 w-5">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>

            Add Learning Path
        </a>

    </div>


    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="flex items-center gap-3 rounded-md border border-green-200
                    bg-green-50 px-4 py-3 text-sm text-green-700">

            <svg xmlns="http://www.w3.org/2000/svg"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke-width="2"
                 stroke="currentColor"
                 class="h-5 w-5">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>

            {{ session('success') }}

        </div>
    @endif


    {{-- PAGE SUMMARY --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

        <div class="rounded-lg border border-gray-200 bg-white p-5
                    dark:border-neutral-700 dark:bg-neutral-900">

            <p class="text-sm font-medium text-gray-500">
                Total Learning Paths
            </p>

            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                {{ $learningPaths->count() }}
            </p>

        </div>


        <div class="rounded-lg border border-gray-200 bg-white p-5
                    dark:border-neutral-700 dark:bg-neutral-900">

            <p class="text-sm font-medium text-gray-500">
                Courses Included
            </p>

            <p class="mt-2 text-3xl font-bold text-blue-600">
                {{ $learningPaths->sum(fn($path) => $path->courses->count()) }}
            </p>

        </div>


        <div class="rounded-lg border border-gray-200 bg-white p-5
                    dark:border-neutral-700 dark:bg-neutral-900">

            <p class="text-sm font-medium text-gray-500">
                Structured Learning
            </p>

            <p class="mt-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                Guided course progression
            </p>

        </div>

    </div>


    {{-- LEARNING PATHS --}}
    <div>

        <div class="mb-5 flex items-center justify-between">

            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    All Learning Paths
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Organize courses into structured learning experiences.
                </p>
            </div>

        </div>


        @if($learningPaths->count())

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">

                @foreach($learningPaths as $path)

                    <div class="group overflow-hidden rounded-lg border
                                border-gray-200 bg-white shadow-sm
                                transition hover:-translate-y-1 hover:shadow-lg
                                dark:border-neutral-700 dark:bg-neutral-900">

                        {{-- TOP BLUE AREA --}}
                        <div class="relative flex h-32 items-center
                                    justify-center overflow-hidden bg-blue-700">

                            <div class="absolute inset-0 opacity-20">
                                <div class="absolute -right-8 -top-12 h-40 w-40
                                            rounded-full border-[20px] border-white">
                                </div>

                                <div class="absolute -bottom-16 -left-8 h-40 w-40
                                            rounded-full border-[20px] border-white">
                                </div>
                            </div>

                            <div class="relative flex h-16 w-16 items-center
                                        justify-center rounded-full bg-white/15
                                        text-white backdrop-blur-sm">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.8"
                                     stroke="currentColor"
                                     class="h-8 w-8">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M4.5 6.75A2.25 2.25 0 0 1 6.75 4.5h10.5a2.25 2.25 0 0 1 2.25 2.25v12.5a.25.25 0 0 1-.38.216l-5.21-3.006a1.75 1.75 0 0 0-1.75 0l-5.21 3.006a.25.25 0 0 1-.38-.216V6.75Z"/>

                                </svg>

                            </div>

                        </div>


                        {{-- CARD CONTENT --}}
                        <div class="p-5">

                            <div class="mb-3 flex items-start justify-between gap-3">

                                <h3 class="text-lg font-bold leading-tight
                                           text-gray-900 dark:text-white">

                                    {{ $path->name }}

                                </h3>

                                <span class="shrink-0 rounded-full bg-blue-50
                                             px-2.5 py-1 text-xs font-semibold
                                             text-blue-700 dark:bg-blue-900/30
                                             dark:text-blue-300">

                                    {{ $path->courses->count() }}
                                    {{ Str::plural('Course', $path->courses->count()) }}

                                </span>

                            </div>


                            <p class="min-h-[48px] text-sm leading-6
                                      text-gray-600 dark:text-gray-400">

                                {{ $path->description ?? 'No description available for this learning path.' }}

                            </p>


                            {{-- COURSE PREVIEW --}}
                            @if($path->courses->count())

                                <div class="mt-5 border-t border-gray-100 pt-4
                                            dark:border-neutral-700">

                                    <p class="mb-3 text-xs font-semibold uppercase
                                              tracking-wide text-gray-400">

                                        Courses in this path

                                    </p>

                                    <div class="space-y-2">

                                        @foreach($path->courses->take(3) as $course)

                                            <div class="flex items-center gap-3">

                                                <div class="flex h-7 w-7 shrink-0
                                                            items-center justify-center
                                                            rounded-full bg-blue-50
                                                            text-xs font-bold text-blue-600">

                                                    {{ $loop->iteration }}

                                                </div>

                                                <p class="truncate text-sm font-medium
                                                          text-gray-700
                                                          dark:text-gray-300">

                                                    {{ $course->title }}

                                                </p>

                                            </div>

                                        @endforeach


                                        @if($path->courses->count() > 3)

                                            <p class="pl-10 text-xs font-medium
                                                      text-blue-600">

                                                + {{ $path->courses->count() - 3 }}
                                                more courses

                                            </p>

                                        @endif

                                    </div>

                                </div>

                            @else

                                <div class="mt-5 rounded-md bg-gray-50 px-4 py-3
                                            text-sm text-gray-500
                                            dark:bg-neutral-800">

                                    No courses added yet.

                                </div>

                            @endif


                            {{-- ACTIONS --}}
                            <div class="mt-5 flex items-center gap-2 border-t
                                        border-gray-100 pt-4
                                        dark:border-neutral-700">

                                <a href="{{ route('learning-paths.show', $path) }}"
                                   class="flex-1 rounded-md border border-gray-300
                                          px-3 py-2 text-center text-sm font-semibold
                                          text-gray-700 transition hover:bg-gray-50
                                          dark:border-neutral-600
                                          dark:text-gray-300
                                          dark:hover:bg-neutral-800">

                                    View

                                </a>


                                <a href="{{ route('learning-paths.edit', $path) }}"
                                   class="flex-1 rounded-md bg-blue-600 px-3 py-2
                                          text-center text-sm font-semibold text-white
                                          transition hover:bg-blue-700">

                                    Edit

                                </a>


                                <form action="{{ route('learning-paths.destroy', $path) }}"
                                      method="POST">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="flex h-9 w-9 items-center
                                                   justify-center rounded-md
                                                   border border-gray-300
                                                   text-gray-500 transition
                                                   hover:border-red-200
                                                   hover:bg-red-50
                                                   hover:text-red-600
                                                   dark:border-neutral-600"
                                            onclick="return confirm('Delete this learning path?')"
                                            title="Delete">

                                        <svg xmlns="http://www.w3.org/2000/svg"
                                             fill="none"
                                             viewBox="0 0 24 24"
                                             stroke-width="1.8"
                                             stroke="currentColor"
                                             class="h-5 w-5">

                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21.75H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12.53 0c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0C8.91 1.99 8 2.974 8 4.154v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>

                                        </svg>

                                    </button>

                                </form>

                            </div>

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            {{-- EMPTY STATE --}}
            <div class="rounded-lg border border-dashed border-gray-300
                        bg-white px-6 py-16 text-center
                        dark:border-neutral-700 dark:bg-neutral-900">

                <div class="mx-auto flex h-16 w-16 items-center justify-center
                            rounded-full bg-blue-50 text-blue-600">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke-width="1.8"
                         stroke="currentColor"
                         class="h-8 w-8">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M4.5 6.75A2.25 2.25 0 0 1 6.75 4.5h10.5a2.25 2.25 0 0 1 2.25 2.25v12.5a.25.25 0 0 1-.38.216l-5.21-3.006a1.75 1.75 0 0 0-1.75 0l-5.21 3.006a.25.25 0 0 1-.38-.216V6.75Z"/>

                    </svg>

                </div>

                <h3 class="mt-4 text-lg font-bold text-gray-900
                           dark:text-white">

                    No learning paths yet

                </h3>

                <p class="mx-auto mt-1 max-w-md text-sm text-gray-500">

                    Create your first learning path and organize courses
                    into a structured learning experience.

                </p>

                <a href="{{ route('learning-paths.create') }}"
                   class="mt-6 inline-flex items-center rounded-md
                          bg-blue-600 px-5 py-2.5 text-sm font-semibold
                          text-white hover:bg-blue-700">

                    Create Learning Path

                </a>

            </div>

        @endif

    </div>

</div>

</x-layouts::app>