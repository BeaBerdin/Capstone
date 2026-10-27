{{--
    Dashboard statistic: label, value and caption, with the icon top right.
    The value stays dark; only a status (warning, success, danger) colours it.
    - href alone makes the whole card a link.
    - href with link adds a "{link} →" link at the bottom instead.
    The default slot is for extras under the caption, such as a progress bar.
--}}
@props([
    'label',
    'value',
    'caption' => null,
    'href' => null,
    'link' => null,
    'status' => null,
    'valueSize' => 'text-3xl',
])

@php
    [$valueColor, $iconColor] = match ($status) {
        'warning' => ['text-amber-600', 'bg-amber-50 text-amber-600'],
        'success' => ['text-emerald-600', 'bg-emerald-50 text-emerald-600'],
        'danger' => ['text-red-600', 'bg-red-50 text-red-600'],
        default => ['text-slate-900', 'bg-violet-50 text-violet-600'],
    };

    $cardIsLink = $href && ! $link;
    $tag = $cardIsLink ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($cardIsLink) href="{{ $href }}" @endif
    {{ $attributes->class([
        'flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm',
        'transition hover:border-violet-200 hover:shadow-md' => $cardIsLink,
    ]) }}
>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm font-medium text-slate-600">
                {{ $label }}
            </p>

            <p class="mt-2 {{ $valueSize }} font-bold tracking-tight {{ $valueColor }}">
                {{ $value }}
            </p>

            @if (filled($caption))
                <p class="mt-1 text-sm text-slate-500">
                    {{ $caption }}
                </p>
            @endif
        </div>

        @isset($icon)
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $iconColor }} [&>svg]:h-5 [&>svg]:w-5">
                {{ $icon }}
            </div>
        @endisset
    </div>

    {{ $slot }}

    @if ($href && $link)
        <a href="{{ $href }}" class="mt-auto inline-flex pt-4 text-sm font-semibold text-violet-600 hover:text-violet-700">
            {{ $link }} →
        </a>
    @endif
</{{ $tag }}>
