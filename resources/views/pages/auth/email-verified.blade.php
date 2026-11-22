<x-layouts::auth :title="__('Email verified')">
    <div class="mt-4 flex flex-col gap-6" data-test="email-verified-page">

        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-center" role="status">
            <flux:text class="font-medium text-green-700!">
                {{ __('Your email address has been verified.') }}
            </flux:text>
        </div>

        @php
            $canContinue = $canContinue ?? false;
            $studentEmailMasked = $studentEmailMasked ?? '';
        @endphp

        @if ($canContinue)
            {{-- Which account Continue opens: name and masked email only. --}}
            <div class="rounded-lg border border-gray-200 bg-white px-4 py-3 text-center" data-test="verification-account">
                <flux:text class="text-sm">{{ __('Account') }}</flux:text>
                <flux:text class="font-medium text-gray-900!">{{ $studentName }}</flux:text>
                <flux:text class="text-sm">{{ $studentEmailMasked }}</flux:text>
            </div>
        @endif

        @if ($canContinue && ! $signedInAsSomeoneElse)
            {{--
                Signs the student in on THIS browser: a CSRF-protected POST that
                redeems the one-time grant stored in this browser's session.
                Only the student's click submits it. Nothing submits by itself,
                so a mail scanner that opens or renders this page never signs
                in or uses the link up.
            --}}
            <form method="POST" action="{{ route('verification.continue') }}" class="flex flex-col items-center gap-3" data-test="verification-continue">
                @csrf
                <flux:text class="text-center">
                    {{ __('To open your dashboard on this device, continue as :student.', ['student' => $studentName]) }}
                </flux:text>
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Continue as :student', ['student' => $studentName]) }}
                </flux:button>
            </form>

            <flux:text class="text-center text-sm">
                {{ __('Not you, or using a shared computer? Close this page and log in normally.') }}
            </flux:text>
        @elseif ($canContinue && $signedInAsSomeoneElse)
            {{-- Never switch accounts without an explicit choice. --}}
            <flux:text class="text-center">
                {{ __('This browser is signed in as :current. To open :student\'s dashboard here, switch accounts.', ['current' => $currentUserName, 'student' => $studentName]) }}
            </flux:text>

            <div class="flex flex-col items-center gap-3">
                <form method="POST" action="{{ route('verification.continue') }}" class="w-full" data-test="verification-switch">
                    @csrf
                    <input type="hidden" name="switch" value="1">
                    <flux:button type="submit" variant="primary" class="w-full">
                        {{ __('Sign out and continue as :student', ['student' => $studentName]) }}
                    </flux:button>
                </form>

                <flux:button :href="route('dashboard')" variant="ghost" class="w-full" data-test="verification-stay">
                    {{ __('Stay signed in as :current', ['current' => $currentUserName]) }}
                </flux:button>
            </div>
        @else
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
        @endif

    </div>
</x-layouts::auth>
