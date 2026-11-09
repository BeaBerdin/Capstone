<x-layouts::auth :title="'Session Expired'">
    {{--
        Shown when a form is sent with an expired security token, usually
        because the page stayed open longer than the session lifetime
        (for example the email verification page). Replaces Laravel's
        plain "419 Page Expired" page.
    --}}
    <div class="flex flex-col gap-6 text-center">

        <div>
            <h1 class="text-xl font-bold text-slate-900">Your session has expired</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                This page was open for a long time, so your session ended for your security.
                Please log in again and repeat what you were doing.
            </p>
        </div>

        {{-- A button (like the other auth pages) so the primary style keeps white text. --}}
        <form method="GET" action="{{ route('login') }}">
            <button type="submit" class="pw-btn-primary w-full rounded-xl px-4 py-3 text-sm font-semibold text-white transition">
                Log in again
            </button>
        </form>

        <a href="{{ route('home') }}" class="text-sm font-semibold text-violet-700 hover:underline">
            Back to the home page
        </a>

    </div>
</x-layouts::auth>
