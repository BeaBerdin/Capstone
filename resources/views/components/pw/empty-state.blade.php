{{--
    Centred "nothing here yet" message with an optional icon slot and an
    optional action in the default slot.
--}}
@props([
    'title',
    'message' => null,
])

<div {{ $attributes->merge(['class' => 'px-6 py-10 text-center']) }}>
    @isset($icon)
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 [&>svg]:h-6 [&>svg]:w-6">
            {{ $icon }}
        </div>
    @endisset

    <p class="text-sm font-semibold text-slate-800">
        {{ $title }}
    </p>

    @if ($message)
        <p class="mt-1 text-sm text-slate-500">
            {{ $message }}
        </p>
    @endif

    @if ($slot->isNotEmpty())
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif
</div>
