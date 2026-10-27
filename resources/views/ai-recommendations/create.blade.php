<x-layouts::app :title="__('Generate Recommendation')">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['AI Recommendations' => route('ai-recommendations.index'), 'Generate Recommendation' => null]"
    title="Generate AI Recommendation"
    subtitle="Recommend a course to a student based on their quiz performance."
    :back-url="route('ai-recommendations.index')"
    back-label="Back to Recommendations"
>
    @if(session('error'))
        <x-slot:intro>
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700" role="alert">
                {{ session('error') }}
            </div>
        </x-slot:intro>
    @endif

    <form method="POST" action="{{ route('ai-recommendations.store') }}" class="sad-card overflow-hidden">
        @csrf

        <x-pw.form-errors />

        <x-pw.form-section title="Student" first>
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.9 4.6L18.5 9l-4.6 1.9L12 15.5l-1.9-4.6L5.5 9l4.6-1.4z"></path><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"></path></svg>
            </x-slot:icon>

            <x-pw.field label="Student" for="student_id" required>
                <select id="student_id" name="student_id" required class="sad-field">
                    <option value="">Select student</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" @selected((string) old('student_id') === (string) $student->id)>
                            {{ $student->name }}
                        </option>
                    @endforeach
                </select>
            </x-pw.field>

            <div class="rounded-2xl border border-violet-100 bg-violet-50/60 px-4 py-3 text-sm text-violet-800">
                The system analyzes the selected student's quiz results and recommends a course based on their performance.
            </div>
        </x-pw.form-section>

        <x-pw.form-footer submit="Generate Recommendation" :cancel-url="route('ai-recommendations.index')" />
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="How It Works"
            :tips="[
                'The student needs at least one result on a published quiz in a published course.',
                'The recommended difficulty follows their average quiz score.',
                'Generated recommendations appear on the recommendations list.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
