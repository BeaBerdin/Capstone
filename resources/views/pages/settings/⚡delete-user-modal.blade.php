<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Deactivate the currently authenticated user.
     *
     * PATHWISE never deletes accounts: deleting a user would also delete
     * their enrollments, payments, submissions and certificates (and, for
     * a teacher, every student's records in their courses). The account is
     * switched off instead and can be reactivated by the Super Admin.
     */
    public function deactivateAccount(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        $user = Auth::user();

        // The Super Admin could otherwise lock the EDP out of the system.
        if ($user->hasRole('super_admin')) {
            $this->addError('password', __('A Super Admin account cannot be deactivated from here.'));

            return;
        }

        $user->forceFill(['is_active' => false])->save();

        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<flux:modal name="confirm-user-deactivation" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
    <form method="POST" wire:submit="deactivateAccount" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Deactivate your account?') }}</flux:heading>

            <flux:subheading>
                {{ __('You will be signed out and will not be able to sign in until the EDP office reactivates your account. Nothing is deleted. Please enter your password to confirm.') }}
            </flux:subheading>
        </div>

        <flux:input wire:model="password" :label="__('Password')" type="password" viewable />

        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
            <flux:modal.close>
                <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>

            <flux:button variant="danger" type="submit" data-test="confirm-deactivate-user-button">
                {{ __('Deactivate account') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
