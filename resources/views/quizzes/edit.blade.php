<x-layouts::app :title="'Edit Quiz'">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Quizzes' => route('quizzes.index'), 'Edit Quiz' => null]"
    title="Edit Quiz"
    subtitle="Update quiz information, passing score, time limit and visibility."
    :back-url="route('quizzes.index')"
    back-label="Back to Quizzes"
>
    <form action="{{ route('quizzes.update', $quiz) }}" method="POST" class="sad-card overflow-hidden">
        @csrf
        @method('PUT')

        <x-pw.form-errors />

        @include('quizzes._form', ['quiz' => $quiz])

        <x-pw.form-footer submit="Update Quiz" :cancel-url="route('quizzes.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Quiz Guidelines"
            :tips="[
                'A quiz with no related lesson is the course\'s final quiz.',
                'Changing the passing score does not change results already recorded.',
                'Untick Published to hide the quiz from students.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
