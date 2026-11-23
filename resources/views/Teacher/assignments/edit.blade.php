<x-layouts::app :title="'Edit Assignment - ' . $assignment->title">
@include('partials.soft3d')

{{-- Edit Assignment: same layout and sections as Create (Teacher.assignments._form). --}}

<div class="sad-page min-h-screen">
    <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <div class="mx-auto max-w-6xl">

            <x-pw.form-header
                :crumbs="['Assignments' => route('teacher.assignments.index'), 'Edit Assignment' => null]"
                title="Edit Assignment"
                :back-url="route('teacher.assignments.index')"
                back-label="Back to Assignments"
            >
                <span class="font-semibold text-violet-700">{{ $assignment->title }}</span> in {{ $course->title }}.
            </x-pw.form-header>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_300px] lg:items-start">

                <form
                    method="POST"
                    action="{{ route('teacher.assignments.update', $assignment) }}"
                    class="sad-card overflow-hidden"
                >
                    @csrf
                    @method('PUT')

                    <x-pw.form-errors />

                    @include('Teacher.assignments._form', ['assignment' => $assignment])

                    <x-pw.form-footer submit="Save Changes" :cancel-url="route('teacher.assignments.index')">
                        <x-slot:icon>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"></path></svg>
                        </x-slot:icon>
                    </x-pw.form-footer>
                </form>

                <x-pw.form-aside
                    title="Before You Save"
                    :tips="[
                        'Saving does not change work students have already submitted.',
                        'Students see updated instructions right away.',
                        'AI evaluation only suggests a score. Students never see it; they see the grade you save.',
                        'Untick Publish to hide the assignment from students.',
                    ]"
                />

            </div>

        </div>
    </main>
</div>

</x-layouts::app>
