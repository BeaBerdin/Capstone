<x-layouts::app :title="__('Edit Certificate')">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Certificates' => route('certificates.index'), 'Edit Certificate' => null]"
    title="Edit Certificate"
    :subtitle="$certificate->certificate_number . '.'"
    :back-url="route('certificates.index')"
    back-label="Back to Certificates"
>
    <form method="POST" action="{{ route('certificates.update', $certificate) }}" class="sad-card overflow-hidden">
        @csrf
        @method('PUT')

        <x-pw.form-errors />

        @include('certificates.partials.form', ['certificate' => $certificate])

        <x-pw.form-footer submit="Update Certificate" :cancel-url="route('certificates.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Certificate Guidelines"
            :tips="[
                'Each certificate number must be unique.',
                'Set the status to Revoked to withdraw a certificate without deleting it.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
