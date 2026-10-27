<x-layouts::app :title="__('Create Lesson')">
@include('partials.soft3d')

{{-- Back and Cancel go to the admin Lessons list (they pointed at the teacher-only My Courses page). --}}

<x-pw.form-layout
    :crumbs="['Lessons' => route('lessons.index'), 'Create Lesson' => null]"
    title="Create Lesson"
    subtitle="Add a lesson under a course. Students see it before taking the course quiz."
    :back-url="route('lessons.index')"
    back-label="Back to Lessons"
>
    <form action="{{ route('lessons.store') }}" method="POST" class="sad-card overflow-hidden">
        @csrf

        <x-pw.form-errors />

        @include('Lessons._form')

        <x-pw.form-footer submit="Save Lesson" :cancel-url="route('lessons.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Lesson Guidelines"
            :tips="[
                'Lesson Order sets where the lesson appears in the course.',
                'Students complete every lesson and pass the final quiz to finish a course.',
                'Untick Published to keep the lesson hidden from students.',
                'Free Preview lets students open the lesson before enrolling.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
