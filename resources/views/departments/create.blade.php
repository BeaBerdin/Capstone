<x-layouts::app :title="'Create Department'">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Departments' => route('departments.index'), 'Create Department' => null]"
    title="Create Department"
    subtitle="Add a new department to your organization."
    :back-url="route('departments.index')"
    back-label="Back to Departments"
>
    <form method="POST" action="{{ route('departments.store') }}" class="sad-card overflow-hidden">
        @csrf

        <x-pw.form-errors />

        @include('departments._form')

        <x-pw.form-footer submit="Create Department" :cancel-url="route('departments.index')">
            <x-slot:icon>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"></path></svg>
            </x-slot:icon>
        </x-pw.form-footer>
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Department Guidelines"
            :tips="[
                'Use the official department name.',
                'After creating it, add admins and teachers from Assign Department.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
