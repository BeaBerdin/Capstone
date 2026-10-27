<x-layouts::app :title="__('Create Question')">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Quiz Questions' => route('quiz-questions.index'), 'Create Question' => null]"
    title="Create Quiz Question"
    subtitle="Add a multiple-choice question that students answer during the quiz."
    :back-url="route('quiz-questions.index')"
    back-label="Back to Questions"
>
    <form method="POST" action="{{ route('quiz-questions.store') }}" class="sad-card overflow-hidden">
        @csrf

        <x-pw.form-errors />

        @include('quiz-questions._form')

        <x-pw.form-footer submit="Save Question" :cancel-url="route('quiz-questions.index')" />
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
