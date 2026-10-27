<x-layouts::app :title="'Create Quiz'">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Quizzes' => route('quizzes.index'), 'Create Quiz' => null]"
    title="Create Quiz"
    subtitle="Create an assessment for a course. Students take it after completing the lessons."
    :back-url="route('quizzes.index')"
    back-label="Back to Quizzes"
>
    <form action="{{ route('quizzes.store') }}" method="POST" class="sad-card overflow-hidden">
        @csrf

        <x-pw.form-errors />

        @include('quizzes._form')

        <x-pw.form-footer submit="Save Quiz" :cancel-url="route('quizzes.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Quiz Guidelines"
            :tips="[
                'A quiz with no related lesson is the course\'s final quiz.',
                'Students must reach the passing score to complete the course.',
                'With a time limit, answers are submitted automatically when time runs out.',
                'Add the questions from the Quiz Questions page after saving.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
