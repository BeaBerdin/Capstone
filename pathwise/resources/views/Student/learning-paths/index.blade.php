<x-layouts::app :title="'Learning Paths'">

<div class="space-y-8">

    {{-- HEADER --}}
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">

        <div>
            <p class="text-sm font-semibold text-blue-600">
                Learning Dashboard
            </p>

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                Learning Paths
            </h1>

            <p class="mt-2 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Follow a structured learning journey and build your skills
                step by step.
            </p>
        </div>

        {{-- AI GENERATE BUTTON --}}
        <form action="{{ route('student.learning-paths.generate') }}"
              method="POST">
            @csrf

            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-md
                           bg-blue-600 px-5 py-3 text-sm font-semibold
                           text-white shadow-sm transition
                           hover:bg-blue-700">

                <svg xmlns="http://www.w3.org/2000/svg"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke-width="1.8"
                     stroke="currentColor"
                     class="h-5 w-5">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.847-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.847a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.847.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.456-2.456L14.25 6l1.035-.259a3.375 3.375 0 0 0 2.456-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/>

                </svg>

                Generate AI Learning Path

            </button>
        </form>

    </div>


    {{-- SUCCESS --}}
    @if(session('success'))

        <div class="flex items-center gap-3 rounded-md border
                    border-green-200 bg-green-50 px-4 py-3
                    text-sm text-green-700">

            <svg xmlns="http://www.w3.org/2000/svg"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke-width="2"
                 stroke="currentColor"
                 class="h-5 w-5">

                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M9 12.75 11.25 15 15 9.75"/>

            </svg>

            {{ session('success') }}

        </div>

    @endif


    {{-- ERROR --}}
    @if(session('error'))

        <div class="flex items-center gap-3 rounded-md border
                    border-red-200 bg-red-50 px-4 py-3
                    text-sm text-red-700">

            <svg xmlns="http://www.w3.org/2000/svg"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke-width="2"
                 stroke="currentColor"
                 class="h-5 w-5">

                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM3.75 19.5h16.5L12 4.5 3.75 19.5Z"/>

            </svg>

            {{ session('error') }}

        </div>

    @endif


    {{-- AI INTRO BANNER --}}
    <div class="overflow-hidden rounded-lg bg-blue-700">

        <div class="relative px-6 py-7 sm:px-8">

            <div class="relative z-10 max-w-2xl">

                <p class="text-sm font-semibold text-blue-100">
                    Personalized learning
                </p>

                <h2 class="mt-1 text-2xl font-bold text-white">
                    Build your next learning journey
                </h2>

                <p class="mt-2 text-sm leading-6 text-blue-100">
                    Let Pathwise create a structured learning path based
                    on your learning progress and performance.
                </p>

            </div>

            <div class="absolute -right-10 -top-20 h-56 w-56
                        rounded-full border-[35px] border-white/10">
            </div>

            <div class="absolute -bottom-24 right-32 h-48 w-48
                        rounded-full border-[30px] border-white/10">
            </div>

        </div>

    </div>


    {{-- SECTION HEADER --}}
    <div class="flex items-end justify-between">

        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                Your Learning Paths
            </h2>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Continue learning through structured course journeys.
            </p>
        </div>

        @if($learningPaths->count())
            <span class="text-sm text-gray-500">
                {{ $learningPaths->count() }}
                {{ Str::plural('path', $learningPaths->count()) }}
            </span>
        @endif

    </div>


    {{-- LEARNING PATH CARDS --}}
    @if($learningPaths->count())

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            @foreach($learningPaths as $path)

                <div class="group overflow-hidden rounded-lg border
                            border-gray-200 bg-white shadow-sm
                            transition hover:-translate-y-1 hover:shadow-lg
                            dark:border-neutral-700 dark:bg-neutral-900">

                    {{-- CARD HEADER --}}
                    <div class="relative h-36 overflow-hidden bg-blue-700">

                        <div class="absolute inset-0">

                            <div class="absolute -right-10 -top-20 h-48 w-48
                                        rounded-full border-[25px] border-white/10">
                            </div>

                            <div class="absolute -bottom-24 left-20 h-52 w-52
                                        rounded-full border-[25px] border-white/10">
                            </div>

                        </div>

                        <div class="relative flex h-full items-center px-6">

                            <div class="flex h-14 w-14 items-center
                                        justify-center rounded-lg bg-white/15
                                        text-white backdrop-blur-sm">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.8"
                                     stroke="currentColor"
                                     class="h-7 w-7">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M4.5 6.75A2.25 2.25 0 0 1 6.75 4.5h10.5a2.25 2.25 0 0 1 2.25 2.25v12.5a.25.25 0 0 1-.38.216l-5.21-3.006a1.75 1.75 0 0 0-1.75 0l-5.21 3.006a.25.25 0 0 1-.38-.216V6.75Z"/>

                                </svg>

                            </div>

                            <div class="ml-4">

                                @if($path->is_generated)

                                    <span class="inline-flex items-center gap-1
                                                 rounded-full bg-white/15
                                                 px-2.5 py-1 text-xs
                                                 font-semibold text-white">

                                        ✨ AI Generated

                                    </span>

                                @endif

                            </div>

                        </div>

                    </div>


                    {{-- CARD BODY --}}
                    <div class="p-6">

                        <h3 class="text-xl font-bold leading-tight
                                   text-gray-900 dark:text-white">

                            {{ $path->name }}

                        </h3>


                        <p class="mt-2 line-clamp-2 text-sm leading-6
                                  text-gray-500 dark:text-gray-400">

                            {{ $path->description ?? 'A structured learning journey designed to help you develop your skills.' }}

                        </p>


                        {{-- PATH INFO --}}
                        <div class="mt-5 flex flex-wrap gap-2">

                            <span class="inline-flex items-center gap-1.5
                                         rounded-md bg-gray-100 px-3 py-1.5
                                         text-xs font-medium text-gray-700
                                         dark:bg-neutral-800
                                         dark:text-gray-300">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.8"
                                     stroke="currentColor"
                                     class="h-4 w-4">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M12 6.75v10.5m-3.75-7.5h7.5M5.25 4.5h13.5A1.5 1.5 0 0 1 20.25 6v12a1.5 1.5 0 0 1-1.5 1.5H5.25A1.5 1.5 0 0 1 3.75 18V6a1.5 1.5 0 0 1 1.5-1.5Z"/>

                                </svg>

                                {{ $path->courses->count() }}
                                {{ Str::plural('Course', $path->courses->count()) }}

                            </span>


                            @if($path->difficulty_level)

                                <span class="rounded-md bg-blue-50 px-3 py-1.5
                                             text-xs font-semibold
                                             text-blue-700
                                             dark:bg-blue-900/30
                                             dark:text-blue-300">

                                    {{ ucfirst($path->difficulty_level) }}

                                </span>

                            @endif

                        </div>


                        {{-- COURSE PROGRESSION --}}
                        @if($path->courses->count())

                            <div class="mt-6">

                                <div class="mb-2 flex items-center
                                            justify-between text-xs">

                                    <span class="font-semibold text-gray-600
                                                 dark:text-gray-400">

                                        Learning Path

                                    </span>

                                    <span class="font-semibold text-gray-500">
                                        {{ $path->courses->count() }}
                                        {{ Str::plural('course', $path->courses->count()) }}
                                    </span>

                                </div>

                                <div class="h-2 overflow-hidden rounded-full
                                            bg-gray-100 dark:bg-neutral-800">

                                    <div class="h-full rounded-full bg-blue-600"
                                         style="width: 0%">
                                    </div>

                                </div>

                            </div>

                        @endif


                        {{-- COURSES PREVIEW --}}
                        @if($path->courses->count())

                            <div class="mt-6 border-t border-gray-100 pt-5
                                        dark:border-neutral-700">

                                <p class="mb-3 text-xs font-bold uppercase
                                          tracking-wide text-gray-400">

                                    Courses in this path

                                </p>

                                <div class="space-y-3">

                                    @foreach($path->courses->take(3) as $course)

                                        <div class="flex items-center gap-3">

                                            <div class="flex h-8 w-8 shrink-0
                                                        items-center justify-center
                                                        rounded-full bg-blue-50
                                                        text-xs font-bold
                                                        text-blue-600
                                                        dark:bg-blue-900/30
                                                        dark:text-blue-300">

                                                {{ $loop->iteration }}

                                            </div>

                                            <p class="truncate text-sm
                                                      font-medium text-gray-700
                                                      dark:text-gray-300">

                                                {{ $course->title }}

                                            </p>

                                        </div>

                                    @endforeach

                                    @if($path->courses->count() > 3)

                                        <p class="pl-11 text-xs font-semibold
                                                  text-blue-600">

                                            + {{ $path->courses->count() - 3 }}
                                            more courses

                                        </p>

                                    @endif

                                </div>

                            </div>

                        @endif


                        {{-- VIEW BUTTON --}}
                        <a href="{{ route('student.learning-paths.show', $path) }}"
                           class="mt-6 flex w-full items-center justify-center
                                  gap-2 rounded-md bg-blue-600 px-5 py-3
                                  text-sm font-semibold text-white
                                  transition hover:bg-blue-700">

                            View Learning Path

                            <svg xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke-width="2"
                                 stroke="currentColor"
                                 class="h-4 w-4">

                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>

                            </svg>

                        </a>

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

            <h2 class="mt-5 text-xl font-bold text-gray-900
                       dark:text-white">

                No Learning Paths Available

            </h2>

            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-500">

                Generate an AI-powered learning path based on your
                learning progress and start building your skills.

            </p>

            <form action="{{ route('student.learning-paths.generate') }}"
                  method="POST"
                  class="mt-6">

                @csrf

                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-md
                               bg-blue-600 px-5 py-3 text-sm font-semibold
                               text-white hover:bg-blue-700">

                    ✨ Generate AI Learning Path

                </button>

            </form>

        </div>

    @endif

</div>

</x-layouts::app>