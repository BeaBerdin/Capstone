<?php

use App\Concerns\PasswordValidationRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Title;
use Livewire\Component;
use Laravel\Passkeys\Actions\DeletePasskey;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

new #[Title('Security settings')] class extends Component {

    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $canManageTwoFactor;
    public bool $twoFactorEnabled;
    public bool $requiresConfirmation;

    #[Locked]
    public bool $canManagePasskeys;

    #[Locked]
    public array $passkeys = [];

    public bool $showDeleteModal = false;

    #[Locked]
    public ?int $deletingPasskeyId = null;

    #[Locked]
    public string $deletingPasskeyName = '';


    public function mount(
        DisableTwoFactorAuthentication $disableTwoFactorAuthentication
    ): void {

        $this->canManageTwoFactor =
            Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {

            if (
                Fortify::confirmsTwoFactorAuthentication()
                && is_null(auth()->user()->two_factor_confirmed_at)
            ) {
                $disableTwoFactorAuthentication(
                    auth()->user()
                );
            }

            $this->twoFactorEnabled =
                auth()->user()->hasEnabledTwoFactorAuthentication();

            $this->requiresConfirmation =
                Features::optionEnabled(
                    Features::twoFactorAuthentication(),
                    'confirm'
                );
        }


        $this->canManagePasskeys =
            Features::canManagePasskeys();


        if ($this->canManagePasskeys) {
            $this->loadPasskeys();
        }
    }


    public function updatePassword(): void
    {
        try {

            $validated = $this->validate([
                'current_password' =>
                    $this->currentPasswordRules(),

                'password' =>
                    $this->passwordRules(),
            ]);

        } catch (ValidationException $e) {

            $this->reset(
                'current_password',
                'password',
                'password_confirmation'
            );

            throw $e;
        }


        Auth::user()->update([
            'password' => $validated['password'],
        ]);


        $this->reset(
            'current_password',
            'password',
            'password_confirmation'
        );


        Flux::toast(
            variant: 'success',
            text: __('Password updated.')
        );
    }


    public function loadPasskeys(): void
    {
        $this->passkeys =
            auth()->user()
                ->passkeys()
                ->select([
                    'id',
                    'name',
                    'credential',
                    'created_at',
                    'last_used_at',
                ])
                ->latest()
                ->get()
                ->map(fn ($passkey) => [

                    'id' =>
                        $passkey->id,

                    'name' =>
                        $passkey->name,

                    'authenticator' =>
                        $passkey->authenticator,

                    'created_at_diff' =>
                        $passkey->created_at->diffForHumans(),

                    'last_used_at_diff' =>
                        $passkey->last_used_at?->diffForHumans(),

                ])
                ->toArray();
    }


    public function confirmDelete(int $passkeyId): void
    {
        $passkey =
            auth()->user()
                ->passkeys()
                ->findOrFail($passkeyId);


        $this->deletingPasskeyId =
            $passkey->id;

        $this->deletingPasskeyName =
            $passkey->name;

        $this->showDeleteModal = true;
    }


    public function deletePasskey(
        DeletePasskey $deletePasskey
    ): void {

        if (! $this->deletingPasskeyId) {
            return;
        }


        $passkey =
            auth()->user()
                ->passkeys()
                ->findOrFail(
                    $this->deletingPasskeyId
                );


        $deletePasskey(
            auth()->user(),
            $passkey
        );


        $this->closeDeleteModal();
        $this->loadPasskeys();
    }


    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;

        $this->deletingPasskeyId = null;

        $this->deletingPasskeyName = '';
    }


    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }


    public function disable(
        DisableTwoFactorAuthentication $disableTwoFactorAuthentication
    ): void {

        $disableTwoFactorAuthentication(
            auth()->user()
        );

        $this->twoFactorEnabled = false;
    }

}; ?>


<section class="min-h-screen w-full bg-[#f7f8fc] px-6 py-7 text-zinc-900 transition-colors dark:bg-neutral-950 dark:text-zinc-100 lg:px-8">

    @include('partials.settings-heading')


    <flux:heading class="sr-only">
        {{ __('Security settings') }}
    </flux:heading>


    <x-pages::settings.layout
        :heading="__('Security')"
        :subheading="__('Manage your password and account security options')"
    >


        {{-- =====================================================
             PASSWORD
        ====================================================== --}}

        <section
            class="rounded-2xl border border-zinc-200 bg-zinc-50/70 p-5
                   dark:border-neutral-700 dark:bg-neutral-800/60"
        >

            <div class="mb-6">

                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                    Update password
                </h3>

                <p class="mt-1 text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                    Use a strong and unique password to help keep your account secure.
                </p>

            </div>


            <form
                method="POST"
                wire:submit="updatePassword"
                class="space-y-5"
            >

                <flux:input
                    wire:model="current_password"
                    :label="__('Current password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    viewable
                />


                <flux:input
                    wire:model="password"
                    :label="__('New password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />


                <flux:input
                    wire:model="password_confirmation"
                    :label="__('Confirm new password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />


                <div class="flex justify-end pt-1">

                    <flux:button
                        variant="primary"
                        type="submit"
                        data-test="update-password-button"
                    >
                        {{ __('Update password') }}
                    </flux:button>

                </div>

            </form>

        </section>



        {{-- =====================================================
             TWO FACTOR AUTHENTICATION
        ====================================================== --}}

        @if ($canManageTwoFactor)

            <section
                class="mt-6 rounded-2xl border border-zinc-200 bg-zinc-50/70 p-5
                       dark:border-neutral-700 dark:bg-neutral-800/60"
            >

                <div class="mb-5">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                Two-factor authentication
                            </h3>

                            <p class="mt-1 text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                                Add an additional verification step when signing in.
                            </p>

                        </div>


                        @if ($twoFactorEnabled)

                            <span
                                class="rounded-full bg-green-100 px-3 py-1
                                       text-xs font-semibold text-green-700
                                       dark:bg-green-950 dark:text-green-300"
                            >
                                Enabled
                            </span>

                        @else

                            <span
                                class="rounded-full bg-zinc-200 px-3 py-1
                                       text-xs font-semibold text-zinc-600
                                       dark:bg-neutral-700 dark:text-zinc-300"
                            >
                                Disabled
                            </span>

                        @endif

                    </div>

                </div>


                <div
                    class="text-sm"
                    wire:cloak
                >

                    @if ($twoFactorEnabled)

                        <div class="space-y-5">

                            <p class="leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                            </p>


                            <flux:button
                                variant="danger"
                                wire:click="disable"
                            >
                                {{ __('Disable 2FA') }}
                            </flux:button>


                            <livewire:pages::settings.two-factor.recovery-codes
                                :$requiresConfirmation
                            />

                        </div>

                    @else

                        <div class="space-y-5">

                            <p class="leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                            </p>


                            <flux:modal.trigger
                                name="two-factor-setup-modal"
                            >

                                <flux:button
                                    variant="primary"
                                    wire:click="$dispatch('start-two-factor-setup')"
                                >
                                    {{ __('Enable 2FA') }}
                                </flux:button>

                            </flux:modal.trigger>


                            <livewire:pages::settings.two-factor-setup-modal
                                :requires-confirmation="$requiresConfirmation"
                            />

                        </div>

                    @endif

                </div>

            </section>

        @endif



        {{-- =====================================================
             PASSKEYS
        ====================================================== --}}

        @if ($canManagePasskeys)

            <section
                class="mt-6 rounded-2xl border border-zinc-200 bg-zinc-50/70 p-5
                       dark:border-neutral-700 dark:bg-neutral-800/60"
            >

                <div class="mb-5">

                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        Passkeys
                    </h3>

                    <p class="mt-1 text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                        Manage secure passwordless sign-in methods for your account.
                    </p>

                </div>


                <div
                    class="space-y-5"
                    wire:cloak
                >

                    <div
                        class="overflow-hidden rounded-xl border border-zinc-200
                               bg-white dark:border-neutral-700 dark:bg-neutral-900"
                    >

                        @forelse ($passkeys as $passkey)

                            <div
                                class="flex items-center justify-between gap-4 p-4
                                       {{ ! $loop->last
                                            ? 'border-b border-zinc-200 dark:border-neutral-700'
                                            : '' }}"
                            >

                                <div class="flex min-w-0 items-center gap-4">

                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center
                                               rounded-xl bg-zinc-100
                                               dark:bg-neutral-800"
                                    >

                                        <flux:icon.key
                                            class="size-5 text-zinc-500 dark:text-zinc-400"
                                        />

                                    </div>


                                    <div class="min-w-0">

                                        <div class="flex flex-wrap items-center gap-2">

                                            <p class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                                {{ $passkey['name'] }}
                                            </p>


                                            @if ($passkey['authenticator'])

                                                <flux:badge size="sm">
                                                    {{ $passkey['authenticator'] }}
                                                </flux:badge>

                                            @endif

                                        </div>


                                        <p class="mt-1 text-xs leading-5 text-zinc-500 dark:text-zinc-400">

                                            {{ __('Added :time', [
                                                'time' => $passkey['created_at_diff']
                                            ]) }}


                                            @if ($passkey['last_used_at_diff'])

                                                <span class="mx-1 opacity-50">
                                                    /
                                                </span>

                                                {{ __('Last used :time', [
                                                    'time' => $passkey['last_used_at_diff']
                                                ]) }}

                                            @endif

                                        </p>

                                    </div>

                                </div>


                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="trash"
                                    icon:variant="outline"
                                    wire:click="confirmDelete({{ $passkey['id'] }})"
                                    class="text-red-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/50"
                                />

                            </div>


                        @empty

                            <div class="px-6 py-10 text-center">

                                <div
                                    class="mx-auto mb-4 flex h-14 w-14
                                           items-center justify-center rounded-2xl
                                           bg-zinc-100 dark:bg-neutral-800"
                                >

                                    <flux:icon.key
                                        class="size-7 text-zinc-400 dark:text-zinc-500"
                                    />

                                </div>


                                <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ __('No passkeys yet') }}
                                </p>


                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('Add a passkey to sign in without entering a password.') }}
                                </p>

                            </div>

                        @endforelse

                    </div>


                    <x-passkey-registration />

                </div>

            </section>

        @endif


    </x-pages::settings.layout>



    {{-- =====================================================
         DELETE PASSKEY MODAL
    ====================================================== --}}

    <flux:modal
        name="delete-passkey-modal"
        class="max-w-md md:min-w-md"
        @close="closeDeleteModal"
        wire:model="showDeleteModal"
    >

        <div class="space-y-6">

            <div class="space-y-2">

                <flux:heading size="lg">
                    {{ __('Remove passkey') }}
                </flux:heading>


                <flux:text>
                    {{ __(
                        'Are you sure you want to remove the passkey ":name"? You will no longer be able to use it to sign in.',
                        ['name' => $deletingPasskeyName]
                    ) }}
                </flux:text>

            </div>


            <div class="flex justify-end gap-3">

                <flux:button
                    variant="outline"
                    wire:click="closeDeleteModal"
                >
                    {{ __('Cancel') }}
                </flux:button>


                <flux:button
                    variant="danger"
                    wire:click="deletePasskey"
                >
                    {{ __('Remove passkey') }}
                </flux:button>

            </div>

        </div>

    </flux:modal>

</section>