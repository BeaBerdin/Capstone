<x-layouts::app :title="__('Edit Quiz Result')">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Quiz Results' => route('teacher.quiz-results.index'), 'Edit Quiz Result' => null]"
    title="Edit Quiz Result"
    subtitle="Update the student's quiz score and attempt details."
    :back-url="route('teacher.quiz-results.index')"
    back-label="Back to Quiz Results"
>
    <form method="POST" action="{{ route('teacher.quiz-results.update', $quiz_result) }}" class="sad-card overflow-hidden">
        @csrf
        @method('PUT')

        <x-pw.form-errors />

        @include('quiz-results._form', ['quiz_result' => $quiz_result])

        <x-pw.form-footer submit="Update Result" :cancel-url="route('teacher.quiz-results.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Quiz Result Guidelines"
            :tips="[
                'The score cannot be greater than the total items.',
                'Passed or failed is recalculated when you save.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
