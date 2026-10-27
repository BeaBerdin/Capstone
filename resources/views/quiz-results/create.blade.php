<x-layouts::app :title="__('Create Quiz Result')">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Quiz Results' => route('teacher.quiz-results.index'), 'Create Quiz Result' => null]"
    title="Create Quiz Result"
    subtitle="Manually record a student's quiz score."
    :back-url="route('teacher.quiz-results.index')"
    back-label="Back to Quiz Results"
>
    <form method="POST" action="{{ route('teacher.quiz-results.store') }}" class="sad-card overflow-hidden">
        @csrf

        <x-pw.form-errors />

        @include('quiz-results._form')

        <x-pw.form-footer submit="Save Result" :cancel-url="route('teacher.quiz-results.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Quiz Result Guidelines"
            :tips="[
                'Use this for quizzes taken outside PathWise, such as on paper.',
                'The score cannot be greater than the total items.',
                'Record each retake as a new attempt number.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
