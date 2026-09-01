<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="white">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('PATHWISE — AI-Powered E-Learning Platform') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-white antialiased" style="color: #171717;">

        <!-- Background glow (lighter version for white bg) -->
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-purple-400/20 blur-3xl"></div>
            <div class="absolute top-1/3 -right-40 h-96 w-96 rounded-full bg-indigo-400/20 blur-3xl"></div>
            <div class="absolute -bottom-40 left-1/3 h-96 w-96 rounded-full bg-fuchsia-400/15 blur-3xl"></div>
        </div>

        <div class="relative z-10">
            <!-- Navbar -->
            <header class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/pathwise-logo-full.png') }}" alt="PATHWISE" class="h-14 w-auto" />
                </div>

                @if (Route::has('login'))
                    <nav class="flex items-center gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-lg border border-gray-200 px-5 py-2 text-sm font-medium text-gray-700 transition hover:border-gray-300 hover:bg-gray-50">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-lg border-2 border-gray-900 px-5 py-2 text-sm font-semibold text-gray-900 transition hover:bg-gray-900 hover:text-white">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-lg bg-linear-to-r from-purple-500 to-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-lg transition hover:opacity-90">
                                    Get Started
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </header>

            <!-- Hero -->
            <main class="mx-auto max-w-6xl px-6">
                <section class="flex flex-col items-center py-16 text-center lg:py-20">
                    <span class="mb-6 inline-flex items-center gap-2 rounded-full border border-purple-200 bg-purple-50 px-4 py-1.5 text-sm text-purple-700">
                        <span class="h-2 w-2 animate-pulse rounded-full bg-purple-500"></span>
                        Powered by AI-Driven Recommendations
                    </span>

                    <h1 class="max-w-4xl text-4xl font-bold leading-tight tracking-tight text-gray-900 sm:text-5xl lg:text-6xl">
                        Learn smarter with
                        <span class="bg-linear-to-r from-purple-600 via-fuchsia-600 to-indigo-600 bg-clip-text text-transparent">
                            personalized learning paths
                        </span>
                    </h1>

                    <p class="mt-6 max-w-2xl text-lg text-gray-600">
                        PATHWISE is an AI-powered e-learning platform that recommends the right course for you based on your performance — connecting students, teachers, and institutions in one place.
                    </p>

                    <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                        @guest
                            <a href="{{ route('register') }}" class="rounded-xl bg-linear-to-r from-purple-500 to-indigo-600 px-8 py-3 font-semibold text-white shadow-lg shadow-purple-500/25 transition hover:scale-[1.02] hover:opacity-90">
                                Start Learning Free
                            </a>
                            <a href="{{ route('login') }}" class="rounded-xl border border-gray-200 px-8 py-3 font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50">
                                I already have an account
                            </a>
                        @endguest
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-xl bg-linear-to-r from-purple-500 to-indigo-600 px-8 py-3 font-semibold text-white shadow-lg shadow-purple-500/25 transition hover:scale-[1.02] hover:opacity-90">
                                Go to Dashboard
                            </a>
                        @endauth
                    </div>

                    <div class="mt-8 flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-sm text-gray-500">
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                            For students &amp; teachers
                        </span>
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                            AI-powered recommendations
                        </span>
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                            Free to start
                        </span>
                    </div>
                </section>

                <!-- Feature cards -->
                <section class="grid gap-6 pb-24 sm:grid-cols-2 lg:grid-cols-3">
                    @php
                        $features = [
                            ['title' => 'AI Course Recommendations', 'desc' => 'Get your next course suggested automatically based on your quiz performance and learning progress.'],
                            ['title' => 'Self-Paced Learning', 'desc' => 'Learn at your own speed with structured lessons, quizzes, and progress tracking.'],
                            ['title' => 'Verified Certificates', 'desc' => 'Earn downloadable certificates automatically upon completing your courses.'],
                            ['title' => 'Course Marketplace', 'desc' => 'Browse and enroll in courses created by teachers across different categories.'],
                            ['title' => 'For Teachers', 'desc' => 'Create courses with templates, manage lessons and quizzes, and track student progress.'],
                            ['title' => 'Analytics Dashboard', 'desc' => 'Monitor performance and engagement with clear, data-driven dashboards.'],
                        ];
                    @endphp

                    @foreach ($features as $f)
                        <div class="group rounded-2xl border border-gray-200 bg-white p-6 transition duration-200 hover:-translate-y-1 hover:border-purple-300 hover:shadow-lg hover:shadow-purple-500/10">
                            <h3 class="mb-2 font-semibold text-gray-900">{{ $f['title'] }}</h3>
                            <p class="text-sm text-gray-600">{{ $f['desc'] }}</p>
                        </div>
                    @endforeach
                </section>
            </main>

            <!-- Footer -->
            <footer class="border-t border-gray-200">
                <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-6 py-8 text-sm text-gray-500 sm:flex-row">
                    <p>&copy; {{ date('Y') }} PATHWISE. An AI-Powered E-Learning Platform.</p>
                    <p>Built with Laravel & Google Gemini</p>
                </div>
            </footer>
        </div>
    </body>
</html>