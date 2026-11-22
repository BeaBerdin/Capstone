<x-layouts::app :title="'Create Account'">
@include('partials.soft3d')

{{-- Create Account (Manuscript Figure 31: "Add User"), Super Admin / EDP only. --}}
<x-pw.form-layout
    :crumbs="['User Management' => route('users.index'), 'Create Account' => null]"
    title="Create Account"
    subtitle="Add an Administrator, Teacher, Student, or Super Admin account."
    :back-url="route('users.index')"
    back-label="Back to Users"
>
    <form method="POST" action="{{ route('users.store') }}" class="sad-card overflow-hidden">
        @csrf

        <x-pw.form-errors />

        <x-pw.form-section title="Account Details" first>
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="4"></circle><path d="M2 21a7 7 0 0 1 14 0M19 8v6M16 11h6"></path></svg>
            </x-slot:icon>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-pw.field label="Full Name" for="name" required>
                    <input id="name" type="text" name="name" required maxlength="255"
                           value="{{ old('name') }}"
                           placeholder="e.g. Juan Dela Cruz"
                           class="sad-field">
                </x-pw.field>

                <x-pw.field label="Email Address" for="email" required hint="Must be unique.">
                    <input id="email" type="email" name="email" required maxlength="255"
                           value="{{ old('email') }}"
                           placeholder="e.g. juan@uclm.edu.ph"
                           class="sad-field">
                </x-pw.field>
            </div>
        </x-pw.form-section>

        <x-pw.form-section title="Role & Department">
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7z"></path></svg>
            </x-slot:icon>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-pw.field label="Role" for="role" required>
                    <select id="role" name="role" required class="sad-field">
                        <option value="admin" @selected(old('role', 'admin') === 'admin')>Administrator (Department Head)</option>
                        <option value="teacher" @selected(old('role') === 'teacher')>Teacher</option>
                        <option value="student" @selected(old('role') === 'student')>Student</option>
                        <option value="super_admin" @selected(old('role') === 'super_admin')>Super Admin (EDP)</option>
                    </select>
                </x-pw.field>

                <x-pw.field label="Department" for="department_id" error="department_id" hint="Optional.">
                    <select id="department_id" name="department_id" class="sad-field">
                        <option value="">No department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </x-pw.field>
            </div>
        </x-pw.form-section>

        <x-pw.form-section title="Temporary Password">
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="11" width="16" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>
            </x-slot:icon>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-pw.field label="Temporary Password" for="password" required
                            hint="On the live site: at least 12 characters with upper and lower case letters, numbers and symbols.">
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                           class="sad-field">
                </x-pw.field>

                <x-pw.field label="Confirm Password" for="password_confirmation" required>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                           class="sad-field">
                </x-pw.field>
            </div>
        </x-pw.form-section>

        <x-pw.form-footer submit="Create Account" :cancel-url="route('users.index')">
            <x-slot:icon>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"></path></svg>
            </x-slot:icon>
        </x-pw.form-footer>
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Account Guidelines"
            :tips="[
                'Use this for teachers and administrators; students can also register themselves.',
                'Teacher accounts enter the EDP review queue and confirm their email after approval.',
                'Give the temporary password to the user privately.',
                'Ask the user to change the password after the first login.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
