<x-layouts::app :title="__('Create Certificate')">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Certificates' => route('certificates.index'), 'Create Certificate' => null]"
    title="Create Certificate"
    subtitle="Issue a certificate to a student who has completed the course."
    :back-url="route('certificates.index')"
    back-label="Back to Certificates"
>
    <form method="POST" action="{{ route('certificates.store') }}" class="sad-card overflow-hidden">
        @csrf

        <x-pw.form-errors />

        @include('certificates.partials.form', ['certificate' => null])

        <x-pw.form-footer submit="Save Certificate" :cancel-url="route('certificates.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Certificate Guidelines"
            :tips="[
                'Each certificate number must be unique.',
                'Only issue a certificate once the student has completed the course.',
                'Set the status to Revoked to withdraw a certificate without deleting it.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
