<?php

use Livewire\Component;

/*
 * Watches the "verify your email" page for the moment the user verifies.
 *
 * The user usually clicks the link in another tab (Gmail), so this tab
 * checks the database every 5 seconds without reloading the page. It only
 * reads the status: verifying still happens through Laravel's signed link.
 */
new class extends Component {
    public bool $verified = false;

    public function checkVerification(): void
    {
        if ($this->verified) {
            return;
        }

        $this->verified = (bool) auth()->user()?->fresh()?->hasVerifiedEmail();
    }
}; ?>

<div>
    @if ($verified)
        <div
            x-data
            data-redirect="{{ route('dashboard') }}"
            x-init="setTimeout(() => window.location.assign($el.dataset.redirect), 1500)"
            role="status"
            aria-live="polite"
            data-test="email-verified-message"
            class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-center"
        >
            <flux:text class="font-medium text-green-700!">
                {{ __('Email verified successfully! Redirecting you to your dashboard...') }}
            </flux:text>
        </div>
    @else
        <div wire:poll.5s.keep-alive="checkVerification">
            <flux:text class="text-center text-xs! text-gray-400!">
                {{ __('This page will update automatically once your email is verified.') }}
            </flux:text>
        </div>
    @endif
</div>
