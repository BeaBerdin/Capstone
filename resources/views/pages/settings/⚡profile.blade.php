<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Profile settings')] class extends Component {

    use ProfileValidationRules, WithFileUploads;

    public string $name = '';
    public string $email = '';

    public $photo = null;


    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }


    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate(
            $this->profileRules($user->id)
        );

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(
            variant: 'success',
            text: __('Profile updated.')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PROFILE PHOTO
    | Selecting a file automatically saves it.
    |--------------------------------------------------------------------------
    */

    public function updatedPhoto(): void
    {
        $this->resetValidation('photo');

        $this->validateOnly('photo', [
            'photo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $this->saveProfilePhoto();
    }


    private function saveProfilePhoto(): void
    {
        if (! $this->photo) {
            return;
        }

        $user = Auth::user();

        // Store the new photo first.
        $newPhotoPath = $this->photo->store(
            'profile-photos',
            'public'
        );

        $oldPhotoPath = $user->profile_photo_path;

        // Save new photo path.
        $user->profile_photo_path = $newPhotoPath;
        $user->save();

        // Delete old photo only after the new one is saved successfully.
        if (
            $oldPhotoPath
            && $oldPhotoPath !== $newPhotoPath
            && Storage::disk('public')->exists($oldPhotoPath)
        ) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        $this->reset('photo');
        $this->resetValidation('photo');

        Auth::setUser($user->fresh());

        Session::flash(
            'profile-photo-success',
            __('Profile photo updated.')
        );

        // Full refresh so the shared topbar immediately gets the new avatar.
        $this->redirect(
            route('profile.edit'),
            navigate: false
        );
    }


    public function removeProfilePhoto(): void
    {
        $user = Auth::user();

        $oldPhotoPath = $user->profile_photo_path;

        if (! $oldPhotoPath) {
            return;
        }

        $user->profile_photo_path = null;
        $user->save();

        if (Storage::disk('public')->exists($oldPhotoPath)) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        $this->reset('photo');
        $this->resetValidation('photo');

        Auth::setUser($user->fresh());

        Session::flash(
            'profile-photo-success',
            __('Profile photo removed.')
        );

        $this->redirect(
            route('profile.edit'),
            navigate: false
        );
    }


    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(
                default: route('dashboard', absolute: false)
            );

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash(
            'status',
            'verification-link-sent'
        );
    }


    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail
            && ! Auth::user()->hasVerifiedEmail();
    }


    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (
                Auth::user() instanceof MustVerifyEmail
                && Auth::user()->hasVerifiedEmail()
            );
    }

}; ?>


<section
    class="min-h-screen w-full
           bg-[#f7f8fc]
           px-6 py-7
           text-zinc-900
           transition-colors
           dark:bg-neutral-950
           dark:text-zinc-100
           lg:px-8"
>

    @include('partials.settings-heading')

    <flux:heading class="sr-only">
        {{ __('Profile settings') }}
    </flux:heading>


    <x-pages::settings.layout
        :heading="__('Profile')"
        :subheading="__('Manage your personal information and account profile')"
    >

        @if(session('profile-photo-success'))

            <div
                class="mb-5 rounded-xl
                       border border-emerald-200
                       bg-emerald-50
                       px-4 py-3
                       text-sm font-medium
                       text-emerald-700
                       dark:border-emerald-900/50
                       dark:bg-emerald-950/30
                       dark:text-emerald-300"
            >
                {{ session('profile-photo-success') }}
            </div>

        @endif


        {{-- =====================================================
             PROFILE PHOTO
        ====================================================== --}}

        <div
            class="mb-6 rounded-2xl
                   border border-zinc-200
                   bg-white
                   p-5
                   shadow-sm
                   dark:border-neutral-700
                   dark:bg-neutral-900"
        >

            <div class="mb-5">

                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                    Profile photo
                </h3>

                <p class="mt-1 text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                    Choose a new photo and PathWise will save it automatically.
                </p>

            </div>


            <div class="flex flex-col gap-5 sm:flex-row sm:items-center">

                {{-- PHOTO --}}
                <div
                    class="relative h-24 w-24 shrink-0
                           overflow-hidden rounded-full
                           border-4 border-white
                           bg-violet-100 shadow-sm
                           ring-1 ring-zinc-200
                           dark:border-neutral-900
                           dark:bg-violet-950
                           dark:ring-neutral-700"
                >

                    @if($photo)

                        <img
                            src="{{ $photo->temporaryUrl() }}"
                            alt="New profile photo preview"
                            class="h-full w-full object-cover"
                        >

                    @elseif(auth()->user()->profile_photo_path)

                        <img
                            src="{{ asset('storage/' . auth()->user()->profile_photo_path) }}?v={{ auth()->user()->updated_at?->timestamp }}"
                            alt="{{ auth()->user()->name }}"
                            class="h-full w-full object-cover"
                        >

                    @else

                        <div
                            class="flex h-full w-full items-center justify-center
                                   text-2xl font-bold text-violet-700
                                   dark:text-violet-300"
                        >
                            {{ auth()->user()->initials() }}
                        </div>

                    @endif

                </div>


                {{-- CONTROLS --}}
                <div class="min-w-0 flex-1">

                    <label
                        class="block text-sm font-semibold
                               text-zinc-800 dark:text-zinc-200"
                    >
                        Choose profile picture
                    </label>

                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        JPG, JPEG, PNG or WEBP. Maximum file size: 2 MB.
                    </p>


                    <input
                        id="profile_photo"
                        type="file"
                        wire:model="photo"
                        accept="image/jpeg,image/png,image/webp"
                        class="hidden"
                    >


                    <div class="mt-4 flex flex-wrap items-center gap-3">

                        <label
                            for="profile_photo"
                            class="inline-flex cursor-pointer items-center gap-2
                                   rounded-xl border border-zinc-200
                                   bg-white px-4 py-2.5
                                   text-sm font-semibold text-zinc-700
                                   shadow-sm transition
                                   hover:border-violet-300
                                   hover:bg-violet-50
                                   hover:text-violet-700
                                   dark:border-zinc-700
                                   dark:bg-zinc-800
                                   dark:text-zinc-200
                                   dark:hover:border-violet-700
                                   dark:hover:bg-violet-950/30
                                   dark:hover:text-violet-300"
                        >

                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 16V4m0 0-4 4m4-4 4 4M5 14v5a1 1 0 001 1h12a1 1 0 001-1v-5"
                                />
                            </svg>

                            {{ auth()->user()->profile_photo_path ? 'Change photo' : 'Choose photo' }}

                        </label>


                        @if(auth()->user()->profile_photo_path)

                            <button
                                type="button"
                                wire:click="removeProfilePhoto"
                                wire:confirm="Remove your current profile photo?"
                                class="text-sm font-semibold
                                       text-red-600 transition
                                       hover:text-red-700
                                       dark:text-red-400
                                       dark:hover:text-red-300"
                            >
                                Remove photo
                            </button>

                        @endif

                    </div>


                    <div
                        wire:loading
                        wire:target="photo"
                        class="mt-3 text-xs font-medium text-violet-600"
                    >
                        Uploading and saving your new profile photo...
                    </div>


                    @error('photo')

                        <p class="mt-3 text-xs font-medium text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>

                    @enderror

                </div>

            </div>

        </div>



        {{-- =====================================================
             PROFILE INFORMATION
        ====================================================== --}}

        <form
            wire:submit.prevent="updateProfileInformation"
            class="space-y-6"
        >

            <div
                class="rounded-2xl border border-zinc-200
                       bg-white p-5 shadow-sm
                       dark:border-neutral-700
                       dark:bg-neutral-900"
            >

                <div class="mb-5">

                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                        Personal information
                    </h3>

                    <p class="mt-1 text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                        Update the name and email address associated with your account.
                    </p>

                </div>


                <div class="space-y-5">

                    <flux:input
                        wire:model="name"
                        :label="__('Name')"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                    />


                    <div>

                        <flux:input
                            wire:model="email"
                            :label="__('Email address')"
                            type="email"
                            required
                            autocomplete="email"
                        />


                        @if ($this->hasUnverifiedEmail)

                            <div
                                class="mt-4 rounded-xl
                                       border border-amber-200
                                       bg-amber-50 p-4
                                       dark:border-amber-900/50
                                       dark:bg-amber-950/30"
                            >

                                <p class="text-sm text-amber-800 dark:text-amber-200">
                                    {{ __('Your email address is unverified.') }}
                                </p>


                                <button
                                    type="button"
                                    wire:click.prevent="resendVerificationNotification"
                                    class="mt-2 text-sm font-semibold
                                           text-violet-700 hover:text-violet-800 hover:underline
                                           dark:text-violet-300 dark:hover:text-violet-200"
                                >
                                    {{ __('Click here to re-send the verification email.') }}
                                </button>


                                @if(session('status') === 'verification-link-sent')

                                    <p class="mt-3 text-sm font-medium text-green-600 dark:text-green-400">
                                        {{ __('A new verification link has been sent to your email address.') }}
                                    </p>

                                @endif

                            </div>

                        @endif

                    </div>

                </div>

            </div>


            <div class="flex justify-end">

                <flux:button
                    variant="primary"
                    type="submit"
                    data-test="update-profile-button"
                >
                    <span
                        wire:loading.remove
                        wire:target="updateProfileInformation"
                    >
                        {{ __('Save changes') }}
                    </span>

                    <span
                        wire:loading
                        wire:target="updateProfileInformation"
                    >
                        {{ __('Saving...') }}
                    </span>
                </flux:button>

            </div>

        </form>



        {{-- =====================================================
             DELETE ACCOUNT
        ====================================================== --}}

        @if ($this->showDeleteUser)

            <div
                class="mt-10 border-t border-zinc-200 pt-8
                       dark:border-neutral-700"
            >

                <div class="mb-5">

                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        Danger zone
                    </h3>

                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Actions in this section may permanently affect your account.
                    </p>

                </div>

                <livewire:pages::settings.delete-user-form />

            </div>

        @endif


    </x-pages::settings.layout>

</section>
