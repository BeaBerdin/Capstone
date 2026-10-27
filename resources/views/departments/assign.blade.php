<x-layouts::app :title="'Assign Department'">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Departments' => route('departments.index'), 'Assign Department' => null]"
    title="Assign Department"
    subtitle="Assign an admin, teacher, or instructor to a department."
    :back-url="route('departments.index')"
    back-label="Back to Departments"
>
    @if(session('success') || session('error'))
        <x-slot:intro>
            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700" role="alert">
                    {{ session('error') }}
                </div>
            @endif
        </x-slot:intro>
    @endif

    <form method="POST" action="{{ route('departments.assign.store') }}" class="sad-card overflow-hidden">
        @csrf

        <x-pw.form-errors />

        <x-pw.form-section title="Assignment Details" first>
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="4"></circle><path d="M2 21a7 7 0 0 1 14 0M16 11h6M19 8l3 3-3 3"></path></svg>
            </x-slot:icon>

            <x-pw.field label="User" for="user_id" required hint="Only users with admin, teacher, or instructor roles are listed.">
                <select id="user_id" name="user_id" required class="sad-field">
                    <option value="" disabled @selected(! old('user_id'))>Select a user</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                            {{ $user->name }} — {{ $user->email }}
                        </option>
                    @endforeach
                </select>
            </x-pw.field>

            <x-pw.field label="Department" for="department_id" required>
                <select id="department_id" name="department_id" required class="sad-field">
                    <option value="" disabled @selected(! old('department_id'))>Select a department</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>

                @if($departments->isEmpty())
                    <p class="mt-1.5 text-xs font-medium text-amber-600">
                        No departments exist yet.
                        <a href="{{ route('departments.create') }}" class="font-semibold underline">Create one first</a>.
                    </p>
                @endif
            </x-pw.field>
        </x-pw.form-section>

        <x-pw.form-footer submit="Assign Department" :cancel-url="route('departments.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Assignment Guidelines"
            :tips="[
                'Each user belongs to one department.',
                'Assigning a new department replaces the user\'s current one.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
