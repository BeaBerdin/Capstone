<x-layouts::app :title="__('Edit Question')">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Quiz Questions' => route('quiz-questions.index'), 'Edit Question' => null]"
    title="Edit Quiz Question"
    subtitle="Update the question, its answer choices or its points."
    :back-url="route('quiz-questions.index')"
    back-label="Back to Questions"
>
    <form method="POST" action="{{ route('quiz-questions.update', $quiz_question) }}" class="sad-card overflow-hidden">
        @csrf
        @method('PUT')

        <x-pw.form-errors />

        @include('quiz-questions._form', ['quiz_question' => $quiz_question])

        <x-pw.form-footer submit="Update Question" :cancel-url="route('quiz-questions.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Question Guidelines"
            :tips="[
                'Options A and B are required; C and D are optional.',
                'The correct answer must point to an option you filled in.',
                'Keep each option short so students can compare them quickly.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
