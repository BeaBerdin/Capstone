<x-layouts::app :title="'Create Assignment - ' . $course->title">
@include('partials.soft3d')

{{--
    Create Assignment, laid out after the PathWise forms reference: header
    with breadcrumb and back link, the form in one white card with titled
    sections, and on desktop a side panel with artwork and guidelines.
--}}

<div class="sad-page min-h-screen">
    <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <div class="mx-auto max-w-6xl">

            <x-pw.form-header
                :crumbs="['Assignments' => route('teacher.assignments.index'), 'Create Assignment' => null]"
                title="Create New Assignment"
                :back-url="route('teacher.assignments.index')"
                back-label="Back to Assignments"
            >
                For <span class="font-semibold text-violet-700">{{ $course->title }}</span>. Fill in the details below.
            </x-pw.form-header>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_300px] lg:items-start">

                <form
                    method="POST"
                    action="{{ route('teacher.assignments.store', $course) }}"
                    class="sad-card overflow-hidden"
                >
                    @csrf

                    <x-pw.form-errors />

                    @include('Teacher.assignments._form')

                    <x-pw.form-footer submit="Create Assignment" :cancel-url="route('teacher.assignments.index')">
                        <x-slot:icon>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"></path></svg>
                        </x-slot:icon>
                    </x-pw.form-footer>
                </form>

                <x-pw.form-aside
                    title="Assignment Guidelines"
                    :tips="[
                        'Write clear instructions: students see them on the assignment page.',
                        'Choose Essay or Code. For code, pick the language students will use.',
                        'PathWise is self-paced: assignments have no due date.',
                        'AI evaluation only suggests a score. Students never see it; they see the grade you save.',
                        'Untick Publish to keep the assignment hidden while you prepare it.',
                    ]"
                />

            </div>

        </div>
    </main>
</div>

</x-layouts::app>
