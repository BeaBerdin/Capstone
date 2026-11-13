<x-layouts::auth :title="__('Email verified')">
    <div class="flex flex-col gap-5 text-center">
        <flux:heading size="lg">{{ __('Email verified') }}</flux:heading>

        <flux:text>
            {{ __('Your email address has been verified. Return to the original PathWise tab where you registered; it will open your dashboard automatically.') }}
        </flux:text>

        <flux:text class="text-sm">
            {{ __('You can close this tab. No additional login is needed here.') }}
        </flux:text>
    </div>
</x-layouts::auth>
