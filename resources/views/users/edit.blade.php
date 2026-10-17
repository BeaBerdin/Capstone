<x-layouts::app :title="'Edit User - ' . $user->name">
<div class="min-h-screen bg-[#f8f9fc]">
    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">
        <div class="mx-auto max-w-3xl">

            <div class="mb-5 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                <a href="{{ route('users.index') }}" class="font-medium hover:text-violet-600">
                    User Management
                </a>
                <span>›</span>
                <span class="font-semibold text-slate-600">Edit</span>
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                    PathWise Administration
                </p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                    Edit User
                </h1>
                <p class="mt-2 text-sm text-slate-500">
                    Correct the user's name or email address. Accounts are never deleted;
                    use Deactivate in User Management to remove access.
                </p>
            </div>

            @if($errors->any())
                <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4">
                    <p class="text-sm font-bold text-rose-700">Please correct the following:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-600">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('users.update', $user) }}"
                class="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                @csrf
                @method('PUT')

                <div class="space-y-5 p-5 sm:p-6">
                    <div>
                        <label for="name" class="text-sm font-semibold text-slate-700">
                            Full Name <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            required
                            maxlength="255"
                            value="{{ old('name', $user->name) }}"
                            class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-4 text-sm
                                   outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                        >
                    </div>

                    <div>
                        <label for="email" class="text-sm font-semibold text-slate-700">
                            Email Address <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            required
                            maxlength="255"
                            value="{{ old('email', $user->email) }}"
                            class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-4 text-sm
                                   outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                        >
                        <p class="mt-1.5 text-xs text-slate-400">
                            Must be unique. A new address entered here is treated as verified by the EDP.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                        Email status:
                        <strong class="{{ $user->hasVerifiedEmail() ? 'text-emerald-700' : 'text-amber-600' }}">
                            {{ $user->hasVerifiedEmail() ? 'Verified' : 'Not verified' }}
                        </strong>
                        · Account:
                        <strong class="{{ $user->is_active !== false ? 'text-emerald-700' : 'text-red-600' }}">
                            {{ $user->is_active !== false ? 'Active' : 'Deactivated' }}
                        </strong>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50
                            px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <a
                        href="{{ route('users.index') }}"
                        class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200
                               bg-white px-5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </a>
                    <button
                        type="submit"
                        class="inline-flex h-11 items-center justify-center rounded-xl bg-linear-to-r
                               from-violet-600 to-indigo-600 px-6 text-sm font-semibold text-white
                               shadow-md shadow-violet-200 transition hover:-translate-y-0.5"
                    >
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>
</x-layouts::app>
