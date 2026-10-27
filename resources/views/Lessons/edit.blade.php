<x-layouts::app :title="'Edit Lesson'">
@include('partials.soft3d')

<x-pw.form-layout
    :crumbs="['Lessons' => route('lessons.index'), 'Edit Lesson' => null]"
    title="Edit Lesson"
    subtitle="Update lesson details, content, order and visibility."
    :back-url="route('lessons.index')"
    back-label="Back to Lessons"
>
    <form action="{{ route('lessons.update', $lesson) }}" method="POST" class="sad-card overflow-hidden">
        @csrf
        @method('PUT')

        <x-pw.form-errors />

        @include('Lessons._form', ['lesson' => $lesson])

        <x-pw.form-footer submit="Update Lesson" :cancel-url="route('lessons.index')">
            <x-slot:icon>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"></path></svg>
            </x-slot:icon>
        </x-pw.form-footer>
    </form>

    <x-slot:aside>
        <x-pw.form-aside
            title="Lesson Guidelines"
            :tips="[
                'Lesson Order sets where the lesson appears in the course.',
                'Untick Published to hide the lesson from students.',
                'Free Preview lets students open the lesson before enrolling.',
            ]"
        />
    </x-slot:aside>
</x-pw.form-layout>

</x-layouts::app>
