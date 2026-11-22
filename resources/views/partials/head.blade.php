<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="color-scheme" content="light" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=2">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=2">

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
