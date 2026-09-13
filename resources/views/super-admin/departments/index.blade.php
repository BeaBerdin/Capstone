<x-layouts::app :title="'Manage Departments'">

    <div class="space-y-6">

        {{-- Header — Coursera style: light banner, blue accents --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-[#0056D2]">
                        Super Admin
                    </p>

                    <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-900">
                        Manage Departments
                    </h1>

                    <p class="mt-2 text-sm text-slate-500">
                        Create and manage departments for your organization.
                    </p>
                </div>

                <button
                    type="button"
                    onclick="document.getElementById('createDepartmentModal').classList.remove('hidden')"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0056D2] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#00419E]"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4v16m8-8H4" />
                    </svg>

                    Add Department
                </button>
            </div>
        </div>

        {{-- Success Message --}}
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- Validation Errors --}}
        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4">
                <ul class="list-disc space-y-1 pl-5 text-sm text-red-600">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Department Cards --}}
        @if($departments->count())

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">

                @foreach($departments as $department)

                    {{-- Coursera course-card look: white, soft shadow, top blue accent on hover --}}
                    <div class="group flex flex-col rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-[#0056D2]/40 hover:shadow-md">

                        {{-- Icon + Users count --}}
                        <div class="flex items-start justify-between gap-4 p-5 pb-0">

                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#E8F0FE] text-[#0056D2]">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M7 21H3m4-4h6m-6-4h6m-6-4h6" />
                                </svg>
                            </div>

                            <span class="rounded-full bg-[#E8F0FE] px-3 py-1 text-xs font-semibold text-[#0056D2]">
                                {{ $department->users_count }} users
                            </span>

                        </div>

                        <div class="flex flex-1 flex-col p-5 pt-4">

                            <h2 class="text-lg font-bold tracking-tight text-slate-900">
                                {{ $department->name }}
                            </h2>

                            <p class="mt-2 min-h-[48px] text-sm leading-6 text-slate-500">
                                {{ $department->description ?: 'No description provided.' }}
                            </p>

                            {{-- Footer actions --}}
                            <div class="mt-5 flex gap-2 border-t border-slate-100 pt-4">

                                {{-- Edit — Coursera "outline" button --}}
                                <button
                                    type="button"
                                    onclick="openEditModal(
                                        {{ $department->id }},
                                        @js($department->name),
                                        @js($department->description)
                                    )"
                                    class="flex-1 rounded-lg border border-[#0056D2] px-4 py-2 text-sm font-semibold text-[#0056D2] transition hover:bg-[#E8F0FE]"
                                >
                                    Edit
                                </button>

                                {{-- Delete --}}
                                <form
                                    action="{{ route('super-admin.departments.destroy', $department) }}"
                                    method="POST"
                                    class="flex-1"
                                    onsubmit="return confirm('Are you sure you want to delete this department?')"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="w-full rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50"
                                    >
                                        Delete
                                    </button>
                                </form>

                            </div>

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            {{-- Empty State — Coursera style: light dashed card on subtle gray --}}
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">

                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[#E8F0FE] text-[#0056D2]">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M7 21H3m4-4h6m-6-4h6m-6-4h6" />
                    </svg>
                </div>

                <h2 class="mt-5 text-xl font-bold tracking-tight text-slate-900">
                    No departments yet
                </h2>

                <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                    Create your first department to start assigning administrators and instructors.
                </p>

                <button
                    type="button"
                    onclick="document.getElementById('createDepartmentModal').classList.remove('hidden')"
                    class="mt-6 rounded-lg bg-[#0056D2] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#00419E]"
                >
                    Create Department
                </button>

            </div>

        @endif

    </div>


    {{-- CREATE DEPARTMENT MODAL --}}
    <div
        id="createDepartmentModal"
        class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/40 p-4 backdrop-blur-sm"
    >
        <div class="flex min-h-full items-center justify-center">

            <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">

                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-slate-900">
                            Create Department
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Add a new department to PathWise.
                        </p>
                    </div>

                    <button
                        type="button"
                        onclick="document.getElementById('createDepartmentModal').classList.add('hidden')"
                        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                    >
                        ✕
                    </button>
                </div>

                <form
                    action="{{ route('super-admin.departments.store') }}"
                    method="POST"
                    class="mt-6 space-y-5"
                >
                    @csrf

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Department Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            required
                            placeholder="e.g. Information Technology"
                            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 placeholder-slate-400 outline-none transition focus:border-[#0056D2] focus:ring-2 focus:ring-[#0056D2]/20"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Description
                        </label>

                        <textarea
                            name="description"
                            rows="4"
                            placeholder="Describe this department..."
                            class="w-full resize-none rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 placeholder-slate-400 outline-none transition focus:border-[#0056D2] focus:ring-2 focus:ring-[#0056D2]/20"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">

                        <button
                            type="button"
                            onclick="document.getElementById('createDepartmentModal').classList.add('hidden')"
                            class="rounded-lg border border-[#0056D2] px-5 py-3 text-sm font-semibold text-[#0056D2] transition hover:bg-[#E8F0FE]"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="rounded-lg bg-[#0056D2] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#00419E]"
                        >
                            Create Department
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>


    {{-- EDIT DEPARTMENT MODAL --}}
    <div
        id="editDepartmentModal"
        class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/40 p-4 backdrop-blur-sm"
    >
        <div class="flex min-h-full items-center justify-center">

            <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">

                <div class="flex items-center justify-between">

                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-slate-900">
                            Edit Department
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Update department information.
                        </p>
                    </div>

                    <button
                        type="button"
                        onclick="document.getElementById('editDepartmentModal').classList.add('hidden')"
                        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                    >
                        ✕
                    </button>

                </div>

                <form
                    id="editDepartmentForm"
                    method="POST"
                    class="mt-6 space-y-5"
                >
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Department Name
                        </label>

                        <input
                            id="editDepartmentName"
                            type="text"
                            name="name"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#0056D2] focus:ring-2 focus:ring-[#0056D2]/20"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Description
                        </label>

                        <textarea
                            id="editDepartmentDescription"
                            name="description"
                            rows="4"
                            class="w-full resize-none rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#0056D2] focus:ring-2 focus:ring-[#0056D2]/20"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">

                        <button
                            type="button"
                            onclick="document.getElementById('editDepartmentModal').classList.add('hidden')"
                            class="rounded-lg border border-[#0056D2] px-5 py-3 text-sm font-semibold text-[#0056D2] transition hover:bg-[#E8F0FE]"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="rounded-lg bg-[#0056D2] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#00419E]"
                        >
                            Save Changes
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>


    {{-- JAVASCRIPT --}}
    <script>
        function openEditModal(id, name, description) {
            const modal = document.getElementById('editDepartmentModal');
            const form = document.getElementById('editDepartmentForm');

            form.action = `/super-admin/departments/${id}`;

            document.getElementById('editDepartmentName').value = name;
            document.getElementById('editDepartmentDescription').value = description ?? '';

            modal.classList.remove('hidden');
        }
    </script>

</x-layouts::app>