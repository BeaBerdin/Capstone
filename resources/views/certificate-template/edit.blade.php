<x-layouts::app :title="'Certificate Template'">
<div class="min-h-screen bg-[#f8f9fc]">
    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">
        <div class="mx-auto max-w-4xl">

            <div>
                <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                    Certificates
                </p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                    Certificate Template
                </h1>
                <p class="mt-2 text-sm text-slate-500">
                    Choose the wording printed on every certificate PDF. The student's name, the course,
                    the certificate number and the issue date are filled in automatically. Certificates
                    already issued keep their number and date and stay valid; their PDF uses this template
                    when downloaded.
                </p>
            </div>

            @if(session('success'))
                <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4">
                    <p class="text-sm font-bold text-rose-700">Please correct the following:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-600">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('certificate-template.update') }}"
                class="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                @csrf
                @method('PUT')

                <div class="space-y-5 p-5 sm:p-6">
                    <div>
                        <label for="title" class="text-sm font-semibold text-slate-700">
                            Title <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="title"
                            name="title"
                            type="text"
                            required
                            maxlength="120"
                            value="{{ old('title', $template->title) }}"
                            class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-4 text-sm
                                   outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                        >
                    </div>

                    <div>
                        <label for="body_text" class="text-sm font-semibold text-slate-700">
                            Body Text <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            id="body_text"
                            name="body_text"
                            rows="5"
                            required
                            maxlength="1000"
                            class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm
                                   outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                        >{{ old('body_text', $template->body_text) }}</textarea>
                        <p class="mt-1.5 text-xs text-slate-400">
                            Shown under the course title. Up to 1,000 characters.
                        </p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="signatory_name" class="text-sm font-semibold text-slate-700">
                                Signatory Name <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="signatory_name"
                                name="signatory_name"
                                type="text"
                                required
                                maxlength="120"
                                value="{{ old('signatory_name', $template->signatory_name) }}"
                                class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-4 text-sm
                                       outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                            >
                        </div>

                        <div>
                            <label for="signatory_position" class="text-sm font-semibold text-slate-700">
                                Signatory Position <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="signatory_position"
                                name="signatory_position"
                                type="text"
                                required
                                maxlength="120"
                                value="{{ old('signatory_position', $template->signatory_position) }}"
                                class="mt-2 h-11 w-full rounded-xl border border-slate-200 px-4 text-sm
                                       outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                            >
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50
                            px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <a
                        href="{{ route('certificate-template.preview') }}"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200
                               bg-white px-5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Preview Saved Template
                    </a>
                    <button
                        type="submit"
                        class="inline-flex h-11 items-center justify-center rounded-xl bg-linear-to-r
                               from-violet-600 to-indigo-600 px-6 text-sm font-semibold text-white
                               shadow-md shadow-violet-200 transition hover:-translate-y-0.5"
                    >
                        Save Template
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>
</x-layouts::app>
