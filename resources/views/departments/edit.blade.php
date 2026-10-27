<x-layouts::app :title="'Edit Department'">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Departments' => route('departments.index'), 'Edit Department' => null]"
    title="Edit Department"
    :back-url="route('departments.index')"
    back-label="Back to Departments"
>
    <x-slot:subtitleSlot>
        Update the details of <span class="font-semibold text-slate-700">{{ $department->name }}</span>.
    </x-slot:subtitleSlot>

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

    <form method="POST" action="{{ route('departments.update', $department) }}" class="sad-card overflow-hidden">
        @csrf
        @method('PUT')

        <x-pw.form-errors />

        @include('departments._form', ['department' => $department])

        <x-pw.form-footer submit="Save Changes" :cancel-url="route('departments.index')">
            <button type="submit" form="delete-department"
                    onclick="return confirm('Are you sure you want to delete this department?');"
                    class="sad-btn border border-rose-200 bg-white text-rose-600 hover:bg-rose-50 sm:ml-auto">
                Delete
            </button>
        </x-pw.form-footer>
    </form>

    <form id="delete-department" method="POST" action="{{ route('departments.destroy', $department) }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Department Guidelines"
            :tips="[
                'Changes apply to all users assigned to this department.',
                'Use the official department name.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
