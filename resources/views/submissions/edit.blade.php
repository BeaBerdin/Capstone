<x-layouts::app :title="__('Edit Submission')">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Submissions' => route('submissions.index'), 'Edit Submission' => null]"
    title="Edit Submission"
    :subtitle="($submission->assignment?->title ?? 'Submission') . '.'"
    :back-url="route('submissions.index')"
    back-label="Back to Submissions"
>
    <form method="POST" action="{{ route('submissions.update', $submission) }}" class="sad-card overflow-hidden">
        @csrf
        @method('PUT')

        <x-pw.form-errors />

        @include('submissions.partials.form', ['submission' => $submission])

        <x-pw.form-footer submit="Update Submission" :cancel-url="route('submissions.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Submission Guidelines"
            :tips="[
                'Leave the score blank until the work is graded.',
                'Set the status to Graded once a score and feedback are saved.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
