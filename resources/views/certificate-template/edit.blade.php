<x-layouts::app :title="'Certificate Template'">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Certificates' => route('certificates.index'), 'Certificate Template' => null]"
    title="Certificate Template"
    subtitle="Choose the wording printed on every certificate PDF."
>
    @if(session('success'))
        <x-slot:intro>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        </x-slot:intro>
    @endif

    <form method="POST" action="{{ route('certificate-template.update') }}" class="sad-card overflow-hidden">
        @csrf
        @method('PUT')

        <x-pw.form-errors />

        <x-pw.form-section title="Wording" first>
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3M9 20h6M12 4v16"></path></svg>
            </x-slot:icon>

            <x-pw.field label="Title" for="title" required>
                <input id="title" type="text" name="title" required maxlength="120"
                       value="{{ old('title', $template->title) }}"
                       class="sad-field">
            </x-pw.field>

            <x-pw.field label="Body Text" for="body_text" required hint="Shown under the course title. Up to 1,000 characters.">
                <textarea id="body_text" name="body_text" rows="5" required maxlength="1000"
                          class="sad-field">{{ old('body_text', $template->body_text) }}</textarea>
            </x-pw.field>
        </x-pw.form-section>

        <x-pw.form-section title="Signatory">
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"></path></svg>
            </x-slot:icon>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-pw.field label="Signatory Name" for="signatory_name" required>
                    <input id="signatory_name" type="text" name="signatory_name" required maxlength="120"
                           value="{{ old('signatory_name', $template->signatory_name) }}"
                           class="sad-field">
                </x-pw.field>

                <x-pw.field label="Signatory Position" for="signatory_position" required>
                    <input id="signatory_position" type="text" name="signatory_position" required maxlength="120"
                           value="{{ old('signatory_position', $template->signatory_position) }}"
                           class="sad-field">
                </x-pw.field>
            </div>
        </x-pw.form-section>

        <x-pw.form-footer submit="Save Template">
            <x-slot:icon>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"></path></svg>
            </x-slot:icon>

            <a href="{{ route('certificate-template.preview') }}" target="_blank" rel="noopener"
               class="sad-btn sad-btn-light border border-slate-200">
                Preview Saved Template
            </a>
        </x-pw.form-footer>
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Template Notes"
            :tips="[
                'The student name, course, certificate number and issue date are filled in automatically.',
                'Issued certificates keep their number and date and stay valid.',
                'Downloading an issued certificate uses the current template.',
                'Save first, then use Preview to check the PDF.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
