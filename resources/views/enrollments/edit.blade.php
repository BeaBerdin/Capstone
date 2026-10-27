<x-layouts::app title="Edit Enrollment">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Enrollments' => route('enrollments.index'), 'Edit Enrollment' => null]"
    title="Edit Enrollment"
    subtitle="Update the enrollment status or progress."
    :back-url="route('enrollments.index')"
    back-label="Back to Enrollments"
>
    <form method="POST" action="{{ route('enrollments.update', $enrollment) }}" class="sad-card overflow-hidden">
        @csrf
        @method('PUT')

        <x-pw.form-errors />

        @include('enrollments._form', ['enrollment' => $enrollment])

        <x-pw.form-footer submit="Update Enrollment" :cancel-url="route('enrollments.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Enrollment Guidelines"
            :tips="[
                'Setting the status to Completed records today as the completion date.',
                'Cancelled enrollments are kept for the record.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
