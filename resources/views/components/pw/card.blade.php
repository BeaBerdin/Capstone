{{--
    Standard top-level container: white, rounded-2xl, slate-200 border.
    - title / subtitle (or a header slot) add a header row with a divider;
      an actions slot sits on its right (wrapping below when space runs out).
    - flush drops the body padding and clips the corners, for tables and
      lists that run edge to edge. Wrap wide tables in overflow-x-auto.
--}}
@props([
    'title' => null,
    'subtitle' => null,
    'flush' => false,
])

<section {{ $attributes->class([
    'rounded-2xl border border-slate-200 bg-white shadow-sm',
    'overflow-hidden' => $flush,
]) }}>
    @if (filled($title) || isset($header))
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b border-slate-100 px-5 py-4 sm:px-6">
            @isset($header)
                {{ $header }}
            @else
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-slate-900">
                        {{ $title }}
                    </h2>

                    @if (filled($subtitle))
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $subtitle }}
                        </p>
                    @endif
                </div>
            @endisset

            @isset($actions)
                <div class="flex shrink-0 flex-wrap items-center gap-3">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    <div @class(['p-5 sm:p-6' => ! $flush])>
        {{ $slot }}
    </div>
</section>
