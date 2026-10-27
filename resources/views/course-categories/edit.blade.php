<x-layouts::app :title="'Edit Category'">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Categories' => route('course-categories.index'), 'Edit Category' => null]"
    title="Edit Course Category"
    subtitle="Update the category name and description used to organize courses."
    :back-url="route('course-categories.index')"
    back-label="Back to Categories"
>
    <form method="POST" action="{{ route('course-categories.update', $courseCategory->id) }}" class="sad-card overflow-hidden">
        @csrf
        @method('PUT')

        <x-pw.form-errors />

        @include('course-categories._form', ['courseCategory' => $courseCategory])

        <x-pw.form-footer submit="Update Category" :cancel-url="route('course-categories.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Category Guidelines"
            :tips="[
                'Renaming a category updates it everywhere it is shown.',
                'Students filter the marketplace by category.',
                'Courses keep their category when you edit it.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
