<x-layouts::app :title="__('Quiz Questions')">
@include('partials.soft3d')
<div class="space-y-6 px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

<x-pw.page-header title="Quiz Questions">
    <x-slot:actions>
        <a href="{{ route('quiz-questions.create') }}"
           class="sad-btn-primary inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">
            Add Question
        </a>
    </x-slot:actions>
</x-pw.page-header>

@if(session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        {{ session('success') }}
    </div>
@endif

<div class="sad-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
<div class="overflow-x-auto">

<table class="w-full min-w-[720px] text-left text-sm">

    <thead class="border-b border-slate-200 bg-slate-50">
        <tr>
            <th class="pw-table-head px-5 py-3">Quiz</th>
            <th class="pw-table-head px-5 py-3">Question</th>
            <th class="pw-table-head px-5 py-3">Answer</th>
            <th class="pw-table-head px-5 py-3">Points</th>
            <th class="pw-table-head px-5 py-3">Actions</th>
        </tr>
    </thead>

    <tbody class="divide-y divide-slate-100">

        @forelse($questions as $question)

        <tr class="align-top transition hover:bg-slate-50/70">
            <td class="px-5 py-4 text-slate-700">
                {{ $question->quiz->title }}
            </td>

            <td class="px-5 py-4 font-medium text-slate-900">
                {{ $question->question }}
            </td>

            <td class="px-5 py-4 text-slate-700">
                {{ $question->correct_answer }}
            </td>

            <td class="px-5 py-4 text-slate-700">
                {{ $question->points }}
            </td>

            <td class="px-5 py-4">
                <div class="flex items-center gap-4 whitespace-nowrap">

                    <a href="{{ route('quiz-questions.edit',$question) }}"
                       class="text-sm font-semibold text-violet-600 hover:text-violet-700">
                        Edit
                    </a>

                    <form action="{{ route('quiz-questions.destroy',$question) }}"
                          method="POST"
                          class="inline">

                        @csrf
                        @method('DELETE')

                        <button class="text-sm font-semibold text-red-600 hover:text-red-700">
                            Delete
                        </button>

                    </form>

                </div>
            </td>
        </tr>

        @empty

        <tr>
            <td colspan="5">
                <x-pw.empty-state title="No quiz questions yet." />
            </td>
        </tr>

        @endforelse

    </tbody>

</table>

</div>
</div>

</div>

</x-layouts::app>
