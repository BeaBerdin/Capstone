@props([
    'chart',
    'type' => 'bar',
    'height' => 220,
    'canvasClass' => '',
])

{{--
    A Chart.js chart with a server-rendered fallback (the slot).

    The canvas stays hidden until resources/js/charts.js has drawn it; only
    then is the fallback moved to screen-reader-only. If JavaScript or
    Chart.js fails to load, the fallback is what the page shows.

    $chart: ['label' => string, 'labels' => string[], 'values' => number[]]
    $type:  'bar' (vertical) or 'hbar' (horizontal)
--}}
<div
    {{ $attributes }}
    data-chart="{{ json_encode(['type' => $type] + $chart) }}"
>
    <div data-chart-canvas hidden class="{{ $canvasClass }}">
        <div style="position: relative; height: {{ (int) $height }}px;">
            <canvas role="img" aria-label="{{ $chart['label'] }}"></canvas>
        </div>
    </div>

    <div data-chart-fallback>
        {{ $slot }}
    </div>
</div>
