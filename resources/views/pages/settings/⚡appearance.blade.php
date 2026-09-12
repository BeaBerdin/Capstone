<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Appearance settings')] class extends Component {
    //
}; ?>

<section class="min-h-screen w-full bg-[#f7f8fc] px-6 py-7 text-zinc-900 transition-colors dark:bg-neutral-950 dark:text-zinc-100 lg:px-8">

    @include('partials.settings-heading')

    <flux:heading class="sr-only">
        {{ __('Appearance settings') }}
    </flux:heading>

    <x-pages::settings.layout
        :heading="__('Appearance')"
        :subheading="__('Choose how PathWise looks on this device')"
    >

        <div class="space-y-6">

            {{-- Appearance Selector --}}
            <div
                class="rounded-2xl border border-zinc-200 bg-zinc-50/70 p-5
                       dark:border-neutral-700 dark:bg-neutral-800/70"
            >

                <div class="mb-5">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                        Theme
                    </h3>

                    <p class="mt-1 text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                        Select your preferred appearance for the PathWise interface.
                    </p>
                </div>


                <flux:radio.group
                    x-data
                    variant="segmented"
                    x-model="$flux.appearance"
                    class="w-full"
                >

                    <flux:radio
                        value="light"
                        icon="sun"
                    >
                        {{ __('Light') }}
                    </flux:radio>


                    <flux:radio
                        value="dark"
                        icon="moon"
                    >
                        {{ __('Dark') }}
                    </flux:radio>


                    <flux:radio
                        value="system"
                        icon="computer-desktop"
                    >
                        {{ __('System') }}
                    </flux:radio>

                </flux:radio.group>

            </div>


            {{-- Explanation --}}
            <div
                class="rounded-2xl border border-violet-100 bg-violet-50/70 p-4
                       dark:border-violet-900/40 dark:bg-violet-950/20"
            >

                <div class="flex gap-3">

                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center
                               rounded-xl bg-violet-100 text-violet-700
                               dark:bg-violet-900/40 dark:text-violet-300"
                    >
                        <svg
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <circle cx="12" cy="12" r="9" />
                            <path d="M12 11v5M12 8h.01" />
                        </svg>
                    </div>


                    <div>
                        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            About System mode
                        </p>

                        <p class="mt-1 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                            System automatically follows the light or dark appearance
                            selected in your computer or browser.
                        </p>
                    </div>

                </div>

            </div>

        </div>

    </x-pages::settings.layout>

</section>