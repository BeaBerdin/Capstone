<x-layouts::app :title="'Learning Paths'">
@include('partials.soft3d')

<div class="space-y-6 px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

    {{-- Header card, as on My Certificates. --}}
    <section class="sad-banner overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">
        <div class="relative px-6 py-6 sm:px-7">
            <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full bg-violet-100/70 blur-3xl"></div>

    <x-pw.page-header
        title="Learning Paths"
        subtitle="Manage structured course paths for personalized learning."
        class="relative"
    >
        <x-slot:actions>
            <a href="{{ route('learning-paths.create') }}"
               class="sad-btn-primary inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">
                Add Learning Path
            </a>
        </x-slot:actions>
    </x-pw.page-header>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="sad-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="pw-table-head px-5 py-3">Path Name</th>
                        <th class="pw-table-head px-5 py-3">Description</th>
                        <th class="pw-table-head px-5 py-3">Courses</th>
                        <th class="pw-table-head px-5 py-3">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($learningPaths as $path)
                        <tr class="align-top transition hover:bg-slate-50/70">
                            <td class="px-5 py-4 font-semibold text-slate-900">
                                {{ $path->name }}
                            </td>

                            <td class="px-5 py-4 text-slate-600">
                                {{ $path->description ?? '-' }}
                            </td>

                            <td class="px-5 py-4 text-slate-700">
                                {{ $path->courses->count() }}
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex items-center gap-4 whitespace-nowrap">
                                    <a href="{{ route('learning-paths.show', $path) }}"
                                       class="text-sm font-semibold text-violet-600 hover:text-violet-700">
                                        View
                                    </a>

                                    <a href="{{ route('learning-paths.edit', $path) }}"
                                       class="text-sm font-semibold text-violet-600 hover:text-violet-700">
                                        Edit
                                    </a>

                                    <form action="{{ route('learning-paths.destroy', $path) }}"
                                          method="POST"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')

                                        <button class="text-sm font-semibold text-red-600 hover:text-red-700"
                                                onclick="return confirm('Delete this learning path?')">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <x-pw.empty-state title="No learning paths found." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

</div>

</x-layouts::app>
