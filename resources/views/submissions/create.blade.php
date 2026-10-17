<x-layouts::app :title="__('Create Submission')">

<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-4 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-3xl">

            <a href="{{ route('submissions.index') }}" class="text-xs font-semibold text-violet-600 hover:text-violet-700">
                &larr; Back to Submissions
            </a>

            <p class="mt-5 text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                Assessments
            </p>

            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                Create Submission
            </h1>

            <p class="mt-2 text-sm leading-6 text-slate-500">
                Record a submission for a student, for example one handed in outside PathWise.
            </p>

            <form
                action="{{ route('submissions.store') }}"
                method="POST"
                class="pw-card mt-7 space-y-6 p-5 sm:p-7"
            >
                @csrf

                @include('submissions.partials.form', ['submission' => null])

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                    <a
                        href="{{ route('submissions.index') }}"
                        class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-600 transition hover:border-violet-200 hover:text-violet-700"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="inline-flex h-11 items-center justify-center rounded-xl bg-violet-600 px-5 text-sm font-semibold text-white transition hover:bg-violet-700"
                    >
                        Save Submission
                    </button>
                </div>
            </form>

        </div>

    </main>

</div>

</x-layouts::app>
