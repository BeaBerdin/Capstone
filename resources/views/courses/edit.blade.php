<x-layouts::app :title="'Edit Course'">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Courses' => route('courses.index'), 'Edit Course' => null]"
    title="Edit Course"
    subtitle="Update course details, category, difficulty level, pricing and certificate availability."
    :back-url="route('courses.index')"
    back-label="Back to Courses"
>
    <form action="{{ route('courses.update', $course) }}" method="POST" class="sad-card overflow-hidden">
        @csrf
        @method('PUT')

        <x-pw.form-errors />

        @include('courses._form', ['course' => $course])

        <x-pw.form-footer submit="Update Course" :cancel-url="route('courses.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Course Guidelines"
            :tips="[
                'Only Published courses appear in the marketplace.',
                'Enter a price of 0 for a free course.',
                'Use Approve or Return for Revision on the Courses page for teacher submissions.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
