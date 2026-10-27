<x-layouts::app :title="__('Create Category')">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Categories' => route('course-categories.index'), 'Create Category' => null]"
    title="Create Course Category"
    subtitle="Organize courses by category to improve structure and navigation."
    :back-url="route('course-categories.index')"
    back-label="Back to Categories"
>
    <form method="POST" action="{{ route('course-categories.store') }}" class="sad-card overflow-hidden">
        @csrf

        <x-pw.form-errors />

        @include('course-categories._form')

        <x-pw.form-footer submit="Save Category" :cancel-url="route('course-categories.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Category Guidelines"
            :tips="[
                'Use a short, clear name; each category name must be unique.',
                'Students filter the marketplace by category.',
                'Teachers pick a category when they create a course.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
