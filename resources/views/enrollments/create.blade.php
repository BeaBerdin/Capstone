<x-layouts::app title="Create Enrollment">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Enrollments' => route('enrollments.index'), 'Create Enrollment' => null]"
    title="Create Enrollment"
    subtitle="Enroll a student in a course manually."
    :back-url="route('enrollments.index')"
    back-label="Back to Enrollments"
>
    <form method="POST" action="{{ route('enrollments.store') }}" class="sad-card overflow-hidden">
        @csrf

        <x-pw.form-errors />

        @include('enrollments._form')

        <x-pw.form-footer submit="Save Enrollment" :cancel-url="route('enrollments.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Enrollment Guidelines"
            :tips="[
                'Use this for students enrolled outside the marketplace.',
                'New enrollments usually start as Active with 0% progress.',
                'Cancelled enrollments are kept for the record.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
