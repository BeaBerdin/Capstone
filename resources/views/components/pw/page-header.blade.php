{{--
    Dashboard page header: eyebrow, title, subtitle and date, with optional
    actions on the right (stacked on small screens). No card around it.
--}}
@props([
    'eyebrow' => null,
    'title',
    'subtitle' => null,
    'date' => null,
])

<header {{ $attributes->merge(['class' => 'flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between']) }}>
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-violet-600">
                {{ $eyebrow }}
            </p>
        @endif

        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
            {{ $title }}
        </h1>

        @if ($subtitle)
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                {{ $subtitle }}
            </p>
        @endif

        @if ($date)
            <p class="mt-1 text-sm text-slate-500">
                {{ $date }}
            </p>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-col gap-3 sm:flex-row">
            {{ $actions }}
        </div>
    @endisset
</header>
