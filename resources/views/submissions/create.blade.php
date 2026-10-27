<x-layouts::app :title="__('Create Submission')">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Submissions' => route('submissions.index'), 'Create Submission' => null]"
    title="Create Submission"
    subtitle="Record a submission for a student, for example one handed in outside PathWise."
    :back-url="route('submissions.index')"
    back-label="Back to Submissions"
>
    <form method="POST" action="{{ route('submissions.store') }}" class="sad-card overflow-hidden">
        @csrf

        <x-pw.form-errors />

        @include('submissions.partials.form', ['submission' => null])

        <x-pw.form-footer submit="Save Submission" :cancel-url="route('submissions.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Submission Guidelines"
            :tips="[
                'Enter the typed answer, a file path, or both.',
                'Leave the score blank until the work is graded.',
                'Set the status to Graded once a score and feedback are saved.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
