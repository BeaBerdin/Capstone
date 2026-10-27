<x-layouts::app :title="__('Create Assignment')">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Assignments' => route('assignments.index'), 'Create Assignment' => null]"
    title="Create Assignment"
    subtitle="Add a graded activity to any course."
    :back-url="route('assignments.index')"
    back-label="Back to Assignments"
>
    <form method="POST" action="{{ route('assignments.store') }}" class="sad-card overflow-hidden">
        @csrf

        <x-pw.form-errors />

        @include('assignments._form')

        <x-pw.form-footer submit="Save Assignment" :cancel-url="route('assignments.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Assignment Guidelines"
            :tips="[
                'Pick a lesson from the same course, or leave it course-level.',
                'Choose the submission type. For code, pick the language students will use.',
                'AI evaluation only suggests a score; the teacher saves the grade.',
                'Enrolled students are notified when a published assignment is saved.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
