<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>

    <body class="min-h-screen bg-white text-gray-900 antialiased">
        <div class="relative z-10 flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-6">
                {{-- PATHWISE logo --}}
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <img src="{{ asset('images/pathwise-logo-full.png') }}" alt="PATHWISE" class="h-12 w-auto" />
                    <span class="sr-only">{{ config('app.name', 'PATHWISE') }}</span>
                </a>

                {{-- Auth card --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-8 text-gray-900 shadow-xl">
                    <div class="flex flex-col gap-6 text-gray-900
                        [&_h1]:text-gray-900
                        [&_h2]:text-gray-900
                        [&_p]:text-gray-500
                        [&_label]:text-gray-700
                        [&_span]:text-gray-500
                        [&_input]:text-gray-900
                        [&_input]:placeholder:text-gray-400
                        [&_input]:bg-white
                        [&_input]:border-gray-200
                        [&_a]:text-purple-600
                        [&_a:hover]:text-purple-700">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>