<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')

    <style>
        html.dark [data-flux-sidebar] [data-flux-sidebar-item],
        html.dark ui-sidebar [data-flux-sidebar-item] {
            border-radius: 12px !important;
            transition: all 180ms ease !important;
        }

        html.dark [data-flux-sidebar] [data-flux-sidebar-item]:hover,
        html.dark ui-sidebar [data-flux-sidebar-item]:hover {
            background-color: rgb(168 85 247 / 0.10) !important;
            color: rgb(216 180 254) !important;
        }

        html.dark [data-flux-sidebar] [data-flux-sidebar-item][data-current],
        html.dark ui-sidebar [data-flux-sidebar-item][data-current] {
            background-image: linear-gradient(
                90deg,
                rgb(168 85 247 / 0.35),
                rgb(99 102 241 / 0.18)
            ) !important;
            color: #ffffff !important;
            box-shadow:
                inset 4px 0 0 rgb(168 85 247),
                0 0 18px rgb(168 85 247 / 0.15) !important;
        }

        html.dark [data-flux-sidebar] [data-flux-sidebar-item][data-current] svg,
        html.dark ui-sidebar [data-flux-sidebar-item][data-current] svg {
            color: rgb(216 180 254) !important;
        }
    </style>
</head>

<body class="min-h-screen bg-white dark:bg-neutral-950">

    <div class="pointer-events-none fixed inset-0 z-0 hidden overflow-hidden dark:block">
        <div class="absolute -top-40 left-1/4 h-96 w-96 rounded-full bg-purple-600/10 blur-3xl"></div>
        <div class="absolute bottom-0 right-1/4 h-96 w-96 rounded-full bg-indigo-600/10 blur-3xl"></div>
    </div>

    <flux:sidebar
        sticky
        collapsible="mobile"
        class="relative z-10 w-72.5 border-e border-zinc-200 bg-zinc-50 dark:border-neutral-800 dark:bg-neutral-950/90 dark:backdrop-blur"
    >

        <flux:sidebar.header>

            <a
                href="{{ route('dashboard') }}"
                class="flex items-center gap-4 px-5 py-6 transition hover:opacity-95"
            >

                <div
                    class="flex h-14 w-14 items-center justify-center rounded-2xl
                           bg-linear-to-br from-purple-600/20 to-indigo-600/20
                           ring-1 ring-purple-500/20"
                >
                    <img
                        src="{{ asset('images/pathwise-icon.png') }}"
                        alt="PATHWISE"
                        class="h-11 w-11 shrink-0
                               drop-shadow-[0_0_18px_rgba(168,85,247,0.55)]"
                    >
                </div>

                <div class="min-w-0">
                    <div
                        class="text-xl font-black tracking-[0.18em]
                               text-white"
                    >
                        PATHWISE
                    </div>

                    <div
                        class="mt-0.5 text-[10px]
                               font-semibold uppercase
                               tracking-[0.35em]
                               text-purple-400"
                    >
                        AI LEARNING PLATFORM
                    </div>
                </div>

            </a>

            <flux:sidebar.collapse class="lg:hidden" />

        </flux:sidebar.header>

        <div class="mx-4 border-b border-zinc-800/80"></div>

        <flux:sidebar.nav>

            <flux:sidebar.group
                :heading="__('Main')"
                class="mt-2 grid gap-1 px-2"
            >

                {{-- DASHBOARD --}}
                <flux:sidebar.item
                    icon="home"
                    :href="route('dashboard')"
                    :current="request()->routeIs('dashboard', '*.dashboard')"
                    wire:navigate
                >
                    {{ __('Dashboard') }}
                </flux:sidebar.item>


                {{-- =========================================================
                     SUPER ADMIN
                     ========================================================= --}}
                @if(auth()->user()->hasRole('super_admin'))

                    <flux:sidebar.item
                        icon="users"
                        :href="route('users.index')"
                        :current="request()->routeIs('users.*')"
                        wire:navigate
                    >
                        {{ __('User Management') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="credit-card"
                        :href="route('super_admin.transactions.index')"
                        :current="request()->routeIs('super_admin.transactions.*')"
                        wire:navigate
                    >
                        {{ __('Transactions') }}
                    </flux:sidebar.item>

                    {{-- Reports route will be added later --}}
                    {{-- Do not use route('reports.index') until the route exists. --}}


                {{-- =========================================================
                     ADMIN
                     ========================================================= --}}
                @elseif(auth()->user()->hasRole('admin'))

                    <flux:sidebar.item
                        icon="book-open"
                        :href="route('courses.index')"
                        :current="request()->routeIs('courses.*')"
                        wire:navigate
                    >
                        {{ __('Courses') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="folder"
                        :href="route('course-categories.index')"
                        :current="request()->routeIs('course-categories.*')"
                        wire:navigate
                    >
                        {{ __('Categories') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="document"
                        :href="route('lessons.index')"
                        :current="request()->routeIs('lessons.*')"
                        wire:navigate
                    >
                        {{ __('Lessons') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="clipboard"
                        :href="route('quizzes.index')"
                        :current="request()->routeIs('quizzes.*')"
                        wire:navigate
                    >
                        {{ __('Quizzes') }}
                    </flux:sidebar.item>

                @if(auth()->user()->hasRole('super_admin'))
    <flux:sidebar.item
        icon="credit-card"
        :href="route('super_admin.transactions.index')"
        :current="request()->routeIs('super_admin.transactions.*')"
        wire:navigate
    >
        {{ __('Transactions') }}
    </flux:sidebar.item>
@endif

                    <flux:sidebar.item
                        icon="academic-cap"
                        :href="route('certificate-management.index')"
                        :current="request()->routeIs('certificate-management.*')"
                        wire:navigate
                    >
                        {{ __('Certificates') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="sparkles"
                        :href="route('ai-recommendations.index')"
                        :current="request()->routeIs('ai-recommendations.*')"
                        wire:navigate
                    >
                        {{ __('AI Recommendations') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="chart-bar"
                        :href="route('student-progress.index')"
                        :current="request()->routeIs('student-progress.*')"
                        wire:navigate
                    >
                        {{ __('Student Progress') }}
                    </flux:sidebar.item>

                    {{-- Reports route will be added later --}}
                    {{-- Do not use route('reports.index') until the route exists. --}}


                {{-- =========================================================
                     TEACHER
                     ========================================================= --}}
                @elseif(auth()->user()->hasRole('teacher'))



                   

                    <flux:sidebar.item
                        icon="book-open"
                        :href="route('teacher.my-courses')"
                        :current="request()->routeIs('teacher.my-courses', 'teacher.courses.*', 'teacher.course.students')"
                        wire:navigate
                    >
                        {{ __('My Courses') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="document"
                        :href="route('teacher.lessons.index')"
                        :current="request()->routeIs('teacher.lessons.*')"
                        wire:navigate
                    >
                        {{ __('Lessons') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="clipboard"
                        :href="route('teacher.quiz-results.index')"
                        :current="request()->routeIs('teacher.quiz-results.*')"
                        wire:navigate
                    >
                        {{ __('Quiz Results') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="users"
                        :href="route('teacher.student-progress.index')"
                        :current="request()->routeIs('teacher.student-progress.*', 'teacher.student.progress')"
                        wire:navigate
                    >
                        {{ __('Student Progress') }}
                    </flux:sidebar.item>


                {{-- =========================================================
                     STUDENT
                     ========================================================= --}}
                @elseif(auth()->user()->hasRole('student'))

                    <flux:sidebar.item
                        icon="shopping-bag"
                        :href="route('student.marketplace')"
                        :current="request()->routeIs('student.marketplace')"
                        wire:navigate
                    >
                        {{ __('Marketplace') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="book-open"
                        :href="route('student.my-courses')"
                        :current="request()->routeIs('student.my-courses')"
                        wire:navigate
                    >
                        {{ __('My Courses') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="map"
                        :href="route('student.learning-paths')"
                        :current="request()->routeIs('student.learning-paths*')"
                        wire:navigate
                    >
                        {{ __('Learning Paths') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="sparkles"
                        :href="route('student.recommendations')"
                        :current="request()->routeIs('student.recommendations')"
                        wire:navigate
                    >
                        {{ __('AI Recommendations') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="credit-card"
                        :href="route('student.transactions')"
                        :current="request()->routeIs('student.transactions*')"
                        wire:navigate
                    >
                        {{ __('Transactions') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="academic-cap"
                        :href="route('student.certificates')"
                        :current="request()->routeIs('student.certificates')"
                        wire:navigate
                    >
                        {{ __('Certificates') }}
                    </flux:sidebar.item>

                @endif

            </flux:sidebar.group>

        </flux:sidebar.nav>

        <flux:spacer />

        <div class="mx-3 mb-3 border-t border-zinc-800"></div>

        <flux:sidebar.nav class="pt-3">

            <flux:sidebar.group
                :heading="__('Account')"
                class="grid gap-1 px-2"
            >

                <flux:sidebar.item
                    icon="cog"
                    :href="route('profile.edit')"
                    :current="request()->routeIs('profile.edit')"
                    wire:navigate
                >
                    {{ __('Settings') }}
                </flux:sidebar.item>

            </flux:sidebar.group>

        </flux:sidebar.nav>

        <x-desktop-user-menu
            class="hidden lg:block"
            :name="auth()->user()->name"
        />

    </flux:sidebar>

    {{ $slot }}

    @fluxScripts

</body>
</html>