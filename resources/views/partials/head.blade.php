<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="color-scheme" content="light" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="icon" href="/favicon.png" type="image/png">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- PathWise is light mode only. @fluxAppearance is left out on purpose: it is
     what adds the "dark" class, and without it Flux only clears the saved
     preference. This also drops a dark preference saved before the change. --}}
<script>
    document.documentElement.classList.remove('dark');

    try {
        window.localStorage.removeItem('flux.appearance');
    } catch (e) {}
</script>
