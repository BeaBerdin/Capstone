<x-layouts::app :title="'Create Course'">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Courses' => route('courses.index'), 'Create Course' => null]"
    title="Create Course"
    subtitle="Create a new course by providing its title, description, category, pricing, difficulty level and certificate availability."
    :back-url="route('courses.index')"
    back-label="Back to Courses"
>
    <form action="{{ route('courses.store') }}" method="POST" class="sad-card overflow-hidden">
        @csrf

        <x-pw.form-errors />

        @include('courses._form')

        <x-pw.form-footer submit="Save Course" :cancel-url="route('courses.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Course Guidelines"
            :tips="[
                'Courses created here skip the teacher approval flow; pick the status directly.',
                'Only Published courses appear in the marketplace.',
                'Enter a price of 0 for a free course.',
                'Lessons and quizzes are added from the Lessons and Quizzes pages.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
