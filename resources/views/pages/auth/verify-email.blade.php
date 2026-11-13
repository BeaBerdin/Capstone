<x-layouts::auth :title="__('Email verification')">
    <div
        class="mt-4 flex flex-col gap-6"
        data-verification-status-url="{{ route('verification.status') }}"
        data-verification-dashboard-url="{{ route('dashboard') }}"
        data-verification-login-url="{{ route('login') }}"
    >
        @if (! \App\Support\MailSettings::isConfigured())
            <flux:text role="alert" class="text-center text-amber-700">
                {{ __('Email delivery is not configured yet. Please contact the administrator to enable verification emails. Your account has been saved; you do not need to register again.') }}
            </flux:text>
        @else
        <flux:text class="text-center">
            {{ __('Please verify your email address by clicking on the link we just emailed to you.') }}
        </flux:text>
        @endif

        <flux:text class="text-center text-sm" role="status" aria-live="polite">
            {{ __('Keep this tab open. After you verify your email, it will open your dashboard automatically.') }}
        </flux:text>

        @if (session('status') == 'verification-link-sent' && \App\Support\MailSettings::isConfigured())
            <flux:text class="text-center font-medium text-green-600!">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </flux:text>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Resend verification email') }}
                </flux:button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button variant="ghost" type="submit" class="text-sm cursor-pointer" data-test="logout-button">
                    {{ __('Log out') }}
                </flux:button>
            </form>
        </div>
    </div>

    @include('pages.auth.partials.verification-poll')
</x-layouts::auth>
