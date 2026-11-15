<x-layouts::auth :title="__('Email verified')">
    <div class="mt-4 flex flex-col gap-6" data-test="email-verified-page">

        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-center" role="status">
            <flux:text class="font-medium text-green-700!">
                {{ __('Your email address has been verified.') }}
            </flux:text>
        </div>

        <flux:text class="text-center">
            {{ __('If PathWise is still open in the browser where you signed up, it continues to your dashboard automatically. You do not need to log in again there.') }}
        </flux:text>

        <div class="flex flex-col items-center gap-3">
            @if ($signedInAsSomeoneElse)
                <flux:text class="text-center text-sm">
                    {{ __('This browser is logged in to a different PathWise account.') }}
                </flux:text>

                <flux:button :href="route('dashboard')" variant="primary" class="w-full">
                    {{ __('Go to dashboard') }}
                </flux:button>
            @else
                <flux:button :href="route('login')" variant="primary" class="w-full">
                    {{ __('Log in on this device') }}
                </flux:button>
            @endif
        </div>

    </div>
</x-layouts::auth>
