<x-layouts::app :title="'Create Learning Path'">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Learning Paths' => route('learning-paths.index'), 'Create Learning Path' => null]"
    title="Create Learning Path"
    subtitle="Build a structured learning sequence from the available courses."
    :back-url="route('learning-paths.index')"
    back-label="Back to Learning Paths"
>
    <form method="POST" action="{{ route('learning-paths.store') }}" class="sad-card overflow-hidden">
        @csrf

        <x-pw.form-errors />

        @include('learning-paths._form')

        <x-pw.form-footer submit="Save Learning Path" :cancel-url="route('learning-paths.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Learning Path Guidelines"
            :tips="[
                'Give the path a name that says what students will learn.',
                'Tick every course that belongs in this path.',
                'You can add or remove courses later.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
