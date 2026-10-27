<x-layouts::app :title="'Edit Learning Path'">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Learning Paths' => route('learning-paths.index'), 'Edit Learning Path' => null]"
    title="Edit Learning Path"
    subtitle="Update the path details and its courses."
    :back-url="route('learning-paths.index')"
    back-label="Back to Learning Paths"
>
    <form method="POST" action="{{ route('learning-paths.update', $learningPath) }}" class="sad-card overflow-hidden">
        @csrf
        @method('PUT')

        <x-pw.form-errors />

        @include('learning-paths._form', ['learningPath' => $learningPath, 'selectedCourses' => $selectedCourses])

        <x-pw.form-footer submit="Update Learning Path" :cancel-url="route('learning-paths.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Learning Path Guidelines"
            :tips="[
                'Give the path a name that says what students will learn.',
                'Untick a course to remove it from this path.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
