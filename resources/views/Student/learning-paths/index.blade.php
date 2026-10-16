<x-layouts::app :title="'Learning Paths'">

<div class="min-h-screen bg-slate-50/70">

    <main class="px-4 py-6 sm:px-5 lg:px-6 lg:py-7">

        <div class="w-full max-w-none space-y-6">


            {{-- =====================================================
                HEADER
            ====================================================== --}}

            <section class="overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">

                <div class="relative px-6 py-7 sm:px-8">

                    <div class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-violet-100/70 blur-3xl"></div>
                    <div class="pointer-events-none absolute right-36 top-12 h-24 w-24 rounded-full bg-indigo-100/70 blur-2xl"></div>

                    <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">

                        <div class="max-w-2xl">

                            <div class="inline-flex items-center gap-2 rounded-full bg-violet-50 px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-violet-700">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 6h5l2 3h9"></path>
                                    <path d="M4 18h5l2-3h9"></path>
                                    <circle cx="4" cy="6" r="1"></circle>
                                    <circle cx="4" cy="18" r="1"></circle>
                                    <circle cx="20" cy="9" r="1"></circle>
                                    <circle cx="20" cy="15" r="1"></circle>
                                </svg>
                                Guided Learning
                            </div>

                            <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                                Learning Paths
                            </h1>

                            <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">
                                Follow a structured course journey or generate a personalized path based on your learning performance.
                            </p>

                        </div>


                        <form action="{{ route('student.learning-paths.generate') }}"
                              method="POST">
                            @csrf

                            <button
                                type="submit"
                                class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m12 3 1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3Z"></path>
                                </svg>

                                Generate AI Learning Path
                            </button>
                        </form>

                    </div>

                </div>

            </section>



            {{-- =====================================================
                ALERTS
            ====================================================== --}}

            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                    {{ session('error') }}
                </div>
            @endif



            {{-- =====================================================
                PATH CARDS
            ====================================================== --}}

            <section class="grid grid-cols-1 gap-5 xl:grid-cols-2">

                @forelse($learningPaths as $path)

                    @php
                        $courseCount = $path->courses->count();
                        $difficulty = $path->difficulty_level
                            ? ucfirst($path->difficulty_level)
                            : 'Flexible';
                    @endphp

                    <article class="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-violet-200 hover:shadow-md">

                        <div class="grid min-h-[250px] grid-cols-1 md:grid-cols-[150px_minmax(0,1fr)]">


                            {{-- VISUAL --}}
                            <div class="relative overflow-hidden bg-gradient-to-br from-violet-600 via-indigo-600 to-blue-600">

                                <div class="absolute -right-8 -top-8 h-28 w-28 rounded-full border border-white/20"></div>
                                <div class="absolute -bottom-10 -left-10 h-36 w-36 rounded-full border border-white/15"></div>

                                <div class="relative flex h-full min-h-[150px] items-center justify-center">
                                    <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-white/15 text-white backdrop-blur">
                                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M5 4v16"></path>
                                            <path d="M5 6h7l2 3h5"></path>
                                            <path d="M5 18h7l2-3h5"></path>
                                        </svg>
                                    </div>
                                </div>

                                @if($path->is_generated)
                                    <div class="absolute left-3 top-3">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-violet-700 shadow-sm">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path d="m12 3 1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3Z"></path>
                                            </svg>
                                            AI Generated
                                        </span>
                                    </div>
                                @endif

                            </div>



                            {{-- CONTENT --}}
                            <div class="flex flex-col p-6">

                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full bg-violet-50 px-3 py-1 text-[10px] font-semibold text-violet-700">
                                        {{ $difficulty }}
                                    </span>

                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-semibold text-slate-600">
                                        {{ $courseCount }}
                                        {{ \Illuminate\Support\Str::plural('course', $courseCount) }}
                                    </span>
                                </div>


                                <h2 class="mt-4 text-xl font-bold tracking-tight text-slate-950">
                                    {{ $path->name }}
                                </h2>


                                <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-500">
                                    {{ $path->description }}
                                </p>


                                <div class="mt-auto pt-6">

                                    <div class="mb-4 flex items-center gap-2">

                                        @for($i = 0; $i < min($courseCount, 5); $i++)
                                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-violet-50 text-[10px] font-bold text-violet-600 ring-2 ring-white">
                                                {{ $i + 1 }}
                                            </div>
                                        @endfor

                                        @if($courseCount > 5)
                                            <span class="text-[11px] font-semibold text-slate-400">
                                                +{{ $courseCount - 5 }} more
                                            </span>
                                        @endif

                                    </div>


                                    <a
                                        href="{{ route('student.learning-paths.show', $path) }}"
                                        class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 text-xs font-semibold text-white transition hover:bg-violet-700"
                                    >
                                        View Learning Path

                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="m9 18 6-6-6-6"></path>
                                        </svg>
                                    </a>

                                </div>

                            </div>

                        </div>

                    </article>


                @empty


                    <div class="xl:col-span-2">

                        <section class="overflow-hidden rounded-3xl border border-dashed border-violet-200 bg-white shadow-sm">

                            <div class="grid min-h-[360px] grid-cols-1 lg:grid-cols-[1fr_360px]">

                                <div class="flex items-center px-8 py-12 sm:px-12">

                                    <div class="max-w-xl">

                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M5 4v16"></path>
                                                <path d="M5 6h7l2 3h5"></path>
                                                <path d="M5 18h7l2-3h5"></path>
                                            </svg>
                                        </div>

                                        <p class="mt-6 text-xs font-bold uppercase tracking-[0.14em] text-violet-600">
                                            Guided Learning
                                        </p>

                                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">
                                            No learning paths yet
                                        </h2>

                                        <p class="mt-3 text-sm leading-6 text-slate-500">
                                            Generate your first AI-powered learning path to receive a structured course sequence based on your learning performance.
                                        </p>


                                        <form action="{{ route('student.learning-paths.generate') }}"
                                              method="POST"
                                              class="mt-6">
                                            @csrf

                                            <button
                                                type="submit"
                                                class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 text-sm font-semibold text-white transition hover:bg-violet-700"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path d="m12 3 1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3Z"></path>
                                                </svg>

                                                Generate AI Learning Path
                                            </button>
                                        </form>

                                    </div>

                                </div>


                                <div class="relative hidden overflow-hidden bg-gradient-to-br from-violet-600 via-indigo-600 to-blue-600 lg:block">

                                    <div class="absolute -right-12 -top-12 h-44 w-44 rounded-full border border-white/15"></div>
                                    <div class="absolute -bottom-16 -left-16 h-56 w-56 rounded-full border border-white/10"></div>

                                    <div class="relative flex h-full items-center justify-center text-center text-white">

                                        <div>
                                            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-white/15 backdrop-blur">
                                                <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <path d="M5 4v16"></path>
                                                    <path d="M5 6h7l2 3h5"></path>
                                                    <path d="M5 18h7l2-3h5"></path>
                                                </svg>
                                            </div>

                                            <p class="mt-5 text-sm font-semibold">
                                                PathWise Learning Path
                                            </p>

                                            <p class="mt-1 text-xs text-white/70">
                                                Structured. Personalized. Progressive.
                                            </p>
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </section>

                    </div>

                @endforelse

            </section>

        </div>

    </main>

</div>

</x-layouts::app>
