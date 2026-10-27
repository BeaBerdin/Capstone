<x-layouts::app :title="__('Edit Assignment')">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Assignments' => route('assignments.index'), 'Edit Assignment' => null]"
    title="Edit Assignment"
    :subtitle="$assignment->title . '.'"
    :back-url="route('assignments.index')"
    back-label="Back to Assignments"
>
    <form method="POST" action="{{ route('assignments.update', $assignment) }}" class="sad-card overflow-hidden">
        @csrf
        @method('PUT')

        <x-pw.form-errors />

        @include('assignments._form', ['assignment' => $assignment])

        <x-pw.form-footer submit="Update Assignment" :cancel-url="route('assignments.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Assignment Guidelines"
            :tips="[
                'Pick a lesson from the same course, or leave it course-level.',
                'Choose the submission type. For code, pick the language students will use.',
                'AI evaluation only suggests a score; the teacher saves the grade.',
                'Publishing a draft notifies the enrolled students.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
