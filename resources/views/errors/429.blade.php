<x-layouts::auth :title="'Too Many Requests'">
    {{--
        Shown when an action is repeated too quickly (for example AI
        evaluation, enrollment or payment). Replaces Laravel's plain
        "429 Too Many Requests" page. Nothing was saved or charged by the
        request that was stopped.
    --}}
    <div class="flex flex-col gap-6 text-center">

        <div>
            <h1 class="text-xl font-bold text-slate-900">Please wait a moment</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                You repeated this action too many times in a short period.
                Please wait about a minute, then go back and try again.
            </p>
        </div>

        <a href="{{ url()->previous() }}" class="pw-btn-primary w-full rounded-xl px-4 py-3 text-sm font-semibold text-white transition">
            Go back
        </a>

        <a href="{{ route('home') }}" class="text-sm font-semibold text-violet-700 hover:underline">
            Back to the home page
        </a>

    </div>
</x-layouts::auth>
