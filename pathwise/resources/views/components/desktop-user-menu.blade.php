@props([
    'name' => auth()->user()->name,
])

<div {{ $attributes->merge(['class' => 'px-3 pb-4']) }}>
    <flux:dropdown position="top" align="start">

        <button
            type="button"
            class="flex w-full items-center gap-3 rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4 text-left transition hover:border-purple-700/50 hover:bg-purple-950/30"
        >
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-purple-600 to-indigo-600 text-sm font-bold text-white">
                {{ auth()->user()->initials() }}
            </div>

            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-white">
                    {{ $name }}
                </p>

                <p class="truncate text-[11px] text-zinc-500">
                    {{ auth()->user()->email }}
                </p>
            </div>

            <svg
                class="h-4 w-4 text-zinc-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                />
            </svg>
        </button>

        <flux:menu>

            <div class="px-3 py-2">
                <p class="text-sm font-semibold text-white">
                    {{ auth()->user()->name }}
                </p>

                <p class="text-xs text-zinc-500">
                    {{ auth()->user()->email }}
                </p>
            </div>

            <flux:menu.separator />

            <flux:menu.item
                :href="route('profile.edit')"
                icon="cog"
                wire:navigate
            >
                Settings
            </flux:menu.item>

            <flux:menu.separator />

            <form
                method="POST"
                action="{{ route('logout') }}"
                class="w-full"
            >
                @csrf

                <flux:menu.item
                    as="button"
                    type="submit"
                    icon="arrow-right-start-on-rectangle"
                    class="w-full cursor-pointer text-red-400"
                >
                    Log out
                </flux:menu.item>
            </form>

        </flux:menu>

    </flux:dropdown>
</div>