<?php

use Livewire\Component;

new class extends Component {}; ?>

{{--
    Accounts are deactivated, never deleted, so enrollments, payments,
    submissions and certificates are kept. The Super Admin (EDP) can
    reactivate a deactivated account in User Management.
--}}
<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <flux:heading>{{ __('Deactivate account') }}</flux:heading>
        <flux:subheading>
            {{ __('Your account will be switched off and you will be signed out. Your courses, payments, submissions and certificates are kept. To use PATHWISE again, ask the EDP office to reactivate your account.') }}
        </flux:subheading>
    </div>

    @if (auth()->user()->hasRole('super_admin'))
        <flux:text>
            {{ __('A Super Admin account cannot be deactivated from here. Another Super Admin can do it in User Management.') }}
        </flux:text>
    @else
        <flux:modal.trigger name="confirm-user-deactivation">
            <flux:button variant="danger" data-test="deactivate-user-button">
                {{ __('Deactivate account') }}
            </flux:button>
        </flux:modal.trigger>

        <livewire:pages::settings.delete-user-modal />
    @endif

    {{-- Accounts cannot be permanently deleted from Settings. --}}
    <flux:text data-test="permanent-deletion-guidance">
        {{ __('To request permanent deletion of your account, please contact the EDP office.') }}
    </flux:text>
</section>
