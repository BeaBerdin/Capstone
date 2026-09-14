<x-layouts::app :title="'Create Department'">

<div class="min-h-screen bg-gray-50 dark:bg-neutral-950">

    {{-- Header --}}
    <div class="border-b border-gray-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="mx-auto max-w-7xl px-6 py-8">

            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <div class="mb-2 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <span>Superadmin</span>
                        <span>/</span>
                        <a href="{{ route('departments.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">Departments</a>
                        <span>/</span>
                        <span>Create</span>
                    </div>

                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Create Department
                    </h1>

                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Add a new department to your organization.
                    </p>
                </div>

                <a href="{{ route('departments.index') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-200 dark:hover:bg-neutral-700">

                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>

                    Back to Departments
                </a>

            </div>
        </div>
    </div>


    {{-- Main Content --}}
    <div class="mx-auto max-w-3xl px-6 py-8">

        {{-- Form Card --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">

            <div class="border-b border-gray-200 px-6 py-5 dark:border-neutral-800">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                    Department Details
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Give the department a name and an optional description.
                </p>
            </div>


            <form action="{{ route('departments.store') }}" method="POST" class="px-6 py-6">
                @csrf

                {{-- Validation Errors --}}
                @if($errors->any())
                    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif


                {{-- Name --}}
                <div class="mb-6">
                    <label for="name" class="mb-2 block text-sm font-semibold text-gray-900 dark:text-white">
                        Name
                    </label>

                    <input type="text"
                           name="name"
                           id="name"
                           value="{{ old('name') }}"
                           required
                           maxlength="255"
                           placeholder="e.g. Information Technology"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-purple-500 focus:ring-purple-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-purple-500">

                    @error('name')
                        <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>


                {{-- Description --}}
                <div class="mb-8">
                    <label for="description" class="mb-2 block text-sm font-semibold text-gray-900 dark:text-white">
                        Description <span class="font-normal text-gray-400 dark:text-gray-500">(optional)</span>
                    </label>

                    <textarea name="description"
                              id="description"
                              rows="4"
                              placeholder="What does this department handle?"
                              class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-purple-500 focus:ring-purple-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-purple-500">{{ old('description') }}</textarea>

                    @error('description')
                        <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>


                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-6 dark:border-neutral-800">

                    <a href="{{ route('departments.index') }}"
                       class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 dark:border-neutral-700 dark:text-gray-300 dark:hover:bg-neutral-800">
                        Cancel
                    </a>

                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-700">

                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M12 4v16m8-8H4" />
                        </svg>

                        Create Department
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

</x-layouts::app>