{{--
    Form page header (PathWise forms reference): breadcrumb, title, a short
    line under it, and a back button on the right (below on small screens).
    - crumbs: [label => url, ...]; the last entry may have a null url.
    - The default slot replaces the subtitle when it needs markup.
--}}
@props([
    'crumbs' => [],
    'title',
    'subtitle' => null,
    'backUrl' => null,
    'backLabel' => 'Back',
    'required' => true,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between']) }}>
    <div class="min-w-0">
        @if ($crumbs)
            <nav class="flex flex-wrap items-center gap-2 text-xs text-slate-500" aria-label="Breadcrumb">
                @foreach ($crumbs as $label => $url)
                    @if (! $loop->first)
                        <svg class="h-3 w-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
                    @endif

                    @if ($url && ! $loop->last)
                        <a href="{{ $url }}" class="font-medium hover:text-violet-600">{{ $label }}</a>
                    @else
                        <span class="font-semibold text-slate-600">{{ $label }}</span>
                    @endif
                @endforeach
            </nav>
        @endif

        <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-950 sm:text-[28px]">
            {{ $title }}
        </h1>

        <p class="mt-1.5 text-sm text-slate-500">
            @if ($slot->isNotEmpty())
                {{ $slot }}
            @else
                {{ $subtitle }}
            @endif

            @if ($required)
                Fields marked <span class="font-semibold text-rose-500">*</span> are required.
            @endif
        </p>
    </div>

    @if ($backUrl)
        <a href="{{ $backUrl }}" class="sad-btn sad-btn-light shrink-0 self-start border border-violet-100">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
            {{ $backLabel }}
        </a>
    @endif
</div>
