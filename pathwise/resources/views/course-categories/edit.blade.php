<x-layouts::app :title="'Edit Category'">

<div class="space-y-6">

    {{-- Header — Coursera v1 style: light card, blue accents --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">
            Edit Course Category
        </h1>

        <p class="mt-2 text-sm text-slate-500">
            Update the category name and description used to organize courses.
        </p>
    </div>

    {{-- Form Card --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

        <form method="POST"
              action="{{ route('course-categories.update', $courseCategory->id) }}"
              class="space-y-5">

            @csrf
            @method('PUT')

            {{-- Category Name --}}
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">
                    Category Name
                </label>

                <input type="text"
                       name="name"
                       value="{{ old('name', $courseCategory->name) }}"
                       placeholder="Enter category name"
                       class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 placeholder-slate-400 outline-none transition focus:border-[#0056D2] focus:ring-2 focus:ring-[#0056D2]/20">

                @error('name')
                    <p class="mt-2 text-sm font-medium text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">
                    Description
                </label>

                <textarea
                    name="description"
                    rows="5"
                    placeholder="Write a short description for this category..."
                    class="w-full resize-none rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 placeholder-slate-400 outline-none transition focus:border-[#0056D2] focus:ring-2 focus:ring-[#0056D2]/20"
                >{{ old('description', $courseCategory->description) }}</textarea>

                @error('description')
                    <p class="mt-2 text-sm font-medium text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Buttons --}}
            <div class="flex gap-3 border-t border-slate-100 pt-5">

                <button type="submit"
                        class="rounded-lg bg-[#0056D2] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#00419E]">
                    Update Category
                </button>

                <a href="{{ route('course-categories.index') }}"
                   class="rounded-lg border border-[#0056D2] px-6 py-3 text-sm font-semibold text-[#0056D2] transition hover:bg-[#E8F0FE]">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</x-layouts::app>