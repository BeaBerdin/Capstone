/*
| Draws the charts rendered by resources/views/components/chart.blade.php.
|
| Chart.js is only downloaded on pages that have a chart. If it fails to
| load or a chart fails to draw, the server-rendered fallback stays visible.
*/

const BAR_COLOR = '#7c3aed';
const GRID_COLOR = '#eef0f4';
const TICK_COLOR = '#64748b';

// Elements already handed to renderCharts. Kept in memory, not as a DOM
// attribute, because Livewire saves the page HTML for the Back button and a
// restored page must be drawn again.
const claimed = new WeakSet();

// Bumped when wire:navigate leaves a page, so a Chart.js download that
// finishes after that does not draw on the page that is gone.
let pageVisit = 0;

let drawn = [];

function chartConfig(data) {
    const horizontal = data.type === 'hbar';

    const valueAxis = {
        beginAtZero: true,
        grid: { color: GRID_COLOR },
        border: { display: false },
        ticks: { color: TICK_COLOR, precision: 0 },
    };

    const categoryAxis = {
        grid: { display: false },
        border: { display: false },
        ticks: { color: TICK_COLOR },
    };

    return {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: data.label,
                data: data.values,
                backgroundColor: BAR_COLOR,
                hoverBackgroundColor: '#6d28d9',
                borderRadius: 4,
                borderSkipped: 'start',
                maxBarThickness: 48,
            }],
        },
        options: {
            indexAxis: horizontal ? 'y' : 'x',
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: { display: false },
                tooltip: { displayColors: false },
            },
            scales: horizontal
                ? { x: valueAxis, y: categoryAxis }
                : { x: categoryAxis, y: valueAxis },
        },
    };
}

async function renderCharts() {
    const elements = [...document.querySelectorAll('[data-chart]')]
        .filter((element) => ! claimed.has(element));

    if (elements.length === 0) {
        return;
    }

    // Claim the elements before the import so a second call does not draw them again.
    elements.forEach((element) => claimed.add(element));

    const visit = pageVisit;

    let Chart;

    try {
        ({ default: Chart } = await import('chart.js/auto'));
    } catch (error) {
        console.error('Chart.js did not load; showing the fallback instead.', error);

        return;
    }

    if (visit !== pageVisit) {
        return;
    }

    for (const element of elements) {
        if (! element.isConnected) {
            continue;
        }

        const wrapper = element.querySelector('[data-chart-canvas]');
        const fallback = element.querySelector('[data-chart-fallback]');
        const canvas = wrapper.querySelector('canvas');

        try {
            // Never two charts on one canvas ("Canvas is already in use").
            Chart.getChart(canvas)?.destroy();

            // Chart.js sizes the chart from its container, so show it first.
            wrapper.hidden = false;

            const chart = new Chart(canvas, chartConfig(JSON.parse(element.dataset.chart)));

            drawn.push({ chart, wrapper, fallback });

            fallback.classList.add('sr-only');
        } catch (error) {
            wrapper.hidden = true;
            fallback.classList.remove('sr-only');

            console.error('A chart could not be drawn; showing the fallback instead.', error);
        }
    }
}

document.addEventListener('DOMContentLoaded', renderCharts);
document.addEventListener('livewire:navigated', renderCharts);

// Put each chart back to its first state before wire:navigate leaves the
// page, so the copy Livewire keeps for the Back button is drawn again.
document.addEventListener('livewire:navigating', () => {
    pageVisit++;

    drawn.forEach(({ chart, wrapper, fallback }) => {
        chart.destroy();
        wrapper.hidden = true;
        fallback.classList.remove('sr-only');
    });

    drawn = [];
});
