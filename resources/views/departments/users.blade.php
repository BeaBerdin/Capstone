<x-layouts::app :title="$department->name.' Users'">

@include('partials.soft3d')
<div class="sad-page min-h-screen bg-slate-50">

    <div class="mx-auto max-w-7xl px-6 py-8">

        <section class="sad-banner overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">
            <div class="relative flex flex-col gap-5 px-6 py-6 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                <div>
                    <nav class="mb-2 flex items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
                        <a href="{{ route('departments.index') }}" class="hover:text-violet-700">Departments</a>
                        <span>/</span>
                        <span>View users</span>
                    </nav>

                    <h1 class="text-3xl font-bold tracking-tight text-slate-900">
                        {{ $department->name }}
                        @if ($department->code)
                            <span class="ml-1 rounded-full bg-violet-50 px-2 py-0.5 align-middle text-xs font-bold text-violet-700">{{ $department->code }}</span>
                        @endif
                    </h1>

                    <p class="mt-2 text-sm text-slate-500">
                        {{ $users->count() }} assigned {{ Str::plural('user', $users->count()) }}.
                        Removing a user only clears their department; their courses, enrollments, progress, quiz results and certificates stay.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('departments.index') }}"
                       class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                        Back to Departments
                    </a>
                    <a href="{{ route('departments.assign') }}"
                       class="rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-violet-700">
                        Assign Department
                    </a>
                </div>
            </div>
        </section>

        <div class="mt-6 space-y-3">
            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700" role="status">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="alert" data-test="only-admin-warning">
                    {{ session('warning') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                    {{ session('error') }}
                </div>
            @endif
        </div>

        <section class="sad-card mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            @if ($users->isEmpty())
                <div class="px-6 py-14 text-center">
                    <h2 class="text-sm font-bold text-slate-800">No users in this department</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Assign an Administrator or Teacher from Assign Department. An empty department can be deleted.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[44rem] text-left">
                        <thead class="border-b border-slate-100 bg-slate-50/80">
                            <tr class="text-xs font-bold uppercase tracking-[.08em] text-slate-500">
                                <th class="px-5 py-4">Name</th>
                                <th class="px-5 py-4">Email</th>
                                <th class="px-5 py-4">Role</th>
                                <th class="px-5 py-4">Status</th>
                                <th class="px-5 py-4 text-right"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($users as $user)
                                @php
                                    $role = $user->roles->pluck('name')->first();
                                    $roleLabel = match ($role) {
                                        'super_admin' => 'Super Admin',
                                        'admin' => 'Administrator',
                                        'teacher' => 'Teacher',
                                        'student' => 'Student',
                                        default => 'No role',
                                    };
                                    $onlyAdmin = $role === 'admin' && $adminCount === 1;
                                    $confirm = 'Remove '.$user->name.' from '.$department->name.'?'
                                        .($onlyAdmin ? ' '.$user->name.' is the only Administrator of '.$department->name.', so no one will be able to review its courses until another Administrator is assigned.' : '');
                                @endphp
                                <tr class="transition hover:bg-slate-50/70" data-test="department-user-row">
                                    <td class="px-5 py-4 text-sm font-semibold text-slate-800">
                                        {{ $user->name }}
                                        @if ($onlyAdmin)
                                            <span class="ml-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-amber-200">Only Administrator</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-600">{{ $user->email }}</td>
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ $roleLabel }}</td>
                                    <td class="px-5 py-4 text-sm">
                                        @if ($user->is_active)
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Active</span>
                                        @else
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <form method="POST" action="{{ route('departments.users.remove', [$department, $user]) }}"
                                              data-confirm="{{ $confirm }}" data-remove-user-form>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50"
                                                    aria-label="Remove {{ $user->name }} from {{ $department->name }}">
                                                Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</div>

<script>
    // Runs on every visit (also after wire:navigate); each form is bound once.
    (function () {
        document.querySelectorAll('form[data-remove-user-form]').forEach(function (form) {
            if (form.dataset.confirmBound) { return; }
            form.dataset.confirmBound = 'true';
            form.addEventListener('submit', function (event) {
                if (!window.confirm(form.dataset.confirm)) { event.preventDefault(); }
            });
        });
    })();
</script>

</x-layouts::app>
