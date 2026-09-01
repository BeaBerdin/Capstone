@props([
    'href' => null,
    'label' => 'Back',
])

{{-- Reusable back button. If :href is given it links there, otherwise it goes to the previous page. --}}

@if ($href)
    <a href="{{ $href }}" wire:navigate
       {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-sm font-medium text-gray-500 transition hover:text-purple-400']) }}>
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        {{ $label }}
    </a>
@else
    <button type="button" onclick="window.history.back()"
        {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-sm font-medium text-gray-500 transition hover:text-purple-400']) }}>
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        {{ $label }}
    </button>
@endif