<x-layouts::app :title="'Edit User - ' . $user->name">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['User Management' => route('users.index'), 'Edit User' => null]"
    title="Edit User"
    subtitle="Correct the user's name or email address."
    :back-url="route('users.index')"
    back-label="Back to Users"
>
    <form method="POST" action="{{ route('users.update', $user) }}" class="sad-card overflow-hidden">
        @csrf
        @method('PUT')

        <x-pw.form-errors />

        <x-pw.form-section title="Account Details" first>
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
            </x-slot:icon>

            @if ($user->first_name === null)
                {{-- Account from before the name was split: show the full name until it is edited in parts. --}}
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700" data-test="legacy-full-name">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current full name</p>
                    <p class="mt-0.5 font-semibold text-slate-900">{{ $user->name }}</p>
                    <p class="mt-1 text-xs text-slate-500">To change it, enter the first name, middle initial and last name below. Leave them empty to keep the current name.</p>
                </div>
            @endif

            <div class="grid gap-5 sm:grid-cols-[minmax(0,1fr)_7rem_minmax(0,1fr)] sm:col-span-2" data-test="name-fields">
                    <x-pw.field label="First Name" for="first_name" >
                        <input id="first_name" type="text" name="first_name"  maxlength="100"
                               value="{{ old('first_name', $user->first_name) }}"
                               placeholder="e.g. Juan"
                               autocomplete="given-name"
                               class="sad-field">
                    </x-pw.field>

                    <x-pw.field label="Middle Initial" for="middle_initial" hint="Optional.">
                        <input id="middle_initial" type="text" name="middle_initial" maxlength="2"
                               value="{{ old('middle_initial', $user->middle_initial) }}"
                               placeholder="M.I."
                               autocomplete="additional-name"
                               class="sad-field uppercase">
                    </x-pw.field>

                    <x-pw.field label="Last Name" for="last_name" >
                        <input id="last_name" type="text" name="last_name"  maxlength="100"
                               value="{{ old('last_name', $user->last_name) }}"
                               placeholder="e.g. Dela Cruz"
                               autocomplete="family-name"
                               class="sad-field">
                    </x-pw.field>
                </div>

            <x-pw.field label="Email Address" for="email" required hint="Must be unique. A new address entered here is treated as verified by the EDP.">
                <input id="email" type="email" name="email" required maxlength="255"
                       value="{{ old('email', $user->email) }}"
                       class="sad-field">
            </x-pw.field>

            <div class="flex flex-wrap gap-2 text-xs">
                <span class="rounded-full px-3 py-1 font-semibold {{ $user->hasVerifiedEmail() ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                    Email {{ $user->hasVerifiedEmail() ? 'verified' : 'not verified' }}
                </span>
                <span class="rounded-full px-3 py-1 font-semibold {{ $user->is_active !== false ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600' }}">
                    Account {{ $user->is_active !== false ? 'active' : 'deactivated' }}
                </span>
            </div>
        </x-pw.form-section>

        <x-pw.form-footer submit="Save Changes" :cancel-url="route('users.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="User Notes"
            :tips="[
                'Accounts are never deleted; use Deactivate in User Management to remove access.',
                'Each email address can belong to only one account.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
