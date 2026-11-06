<x-layouts::app :title="__('Course Categories')">
@include('partials.soft3d')

    <div class="space-y-8 px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        {{-- Page Header --}}
        {{-- Header card, as on My Certificates. --}}
        <section class="sad-banner overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-sm">
            <div class="relative px-6 py-6 sm:px-7">
                <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full bg-violet-100/70 blur-3xl"></div>

        <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <p class="text-sm font-medium text-violet-600">
                    Course Management
                </p>

                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                    Course Categories
                </h1>

                <p class="mt-2 text-sm text-slate-500">
                    Organize courses into categories for easier browsing and management.
                </p>
            </div>

            <a href="{{ route('course-categories.create') }}"
               class="sad-btn-primary inline-flex items-center justify-center h-11 rounded-xl bg-violet-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">

                <svg class="mr-2 h-4 w-4"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M12 4v16m8-8H4"/>

                </svg>

                Add Category

            </a>

        </div>
            </div>
        </section>


        {{-- Success Message --}}
        @if (session('success'))

            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">

                {{ session('success') }}

            </div>

        @endif


        {{-- Search --}}
        <div class="sad-card rounded-xl border border-slate-200 bg-white p-4 shadow-sm">

            <div class="relative">

                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>

                </svg>

                <input
                    id="categorySearch"
                    type="text"
                    placeholder="Search categories..."
                    class="w-full rounded-lg border-slate-300 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:border-violet-500 focus:ring-violet-500"
                >

            </div>

        </div>


        {{-- Category Header --}}
        <div class="flex items-center justify-between">

            <div>

                <h2 class="text-xl font-semibold text-slate-900">
                    All Categories
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    <span id="categoryCount">{{ $categories->count() }}</span>
                    {{ Str::plural('category', $categories->count()) }}
                </p>

            </div>

        </div>


        {{-- Category Grid --}}
        <div id="categoryGrid"
             class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

            @forelse ($categories as $category)

                <div
                    class="sad-card category-card group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg"
                    data-name="{{ strtolower($category->name) }}"
                >

                    {{-- Icon --}}
                    <div class="flex items-start justify-between">

                        <div class="sad-chip sad-violet flex h-11 w-11 items-center justify-center rounded-xl bg-violet-100 text-violet-600">

                            <svg class="h-6 w-6"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor">

                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="1.5"
                                      d="M4 6.5A2.5 2.5 0 0 1 6.5 4H10l2 2h5.5A2.5 2.5 0 0 1 20 8.5v9a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-11Z"/>

                            </svg>

                        </div>

                    </div>


                    {{-- Category Name --}}
                    <h3 class="mt-5 line-clamp-1 text-lg font-bold text-slate-900">

                        {{ $category->name }}

                    </h3>


                    {{-- Description --}}
                    <p class="mt-2 line-clamp-3 min-h-15 text-sm leading-relaxed text-slate-500">

                        {{ $category->description ?: 'No description available.' }}

                    </p>


                    {{-- Divider --}}
                    <div class="my-5 border-t border-slate-100"></div>


                    {{-- Actions --}}
                    <div class="flex gap-2">

                        <a
                            href="{{ route('course-categories.edit', $category) }}"
                            class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-center text-xs font-semibold text-slate-700 transition hover:bg-slate-50">

                            Edit

                        </a>


                        <form
                            action="{{ route('course-categories.destroy', $category) }}"
                            method="POST"
                            class="flex-1">

                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                onclick="return confirm('Delete this category?')"
                                class="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">

                                Delete

                            </button>

                        </form>

                    </div>

                </div>

            @empty

                {{-- No Categories --}}
                <div class="sad-card col-span-full rounded-xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">

                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-violet-100 text-violet-600">

                        <svg class="h-7 w-7"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor">

                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="1.5"
                                  d="M4 6.5A2.5 2.5 0 0 1 6.5 4H10l2 2h5.5A2.5 2.5 0 0 1 20 8.5v9a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-11Z"/>

                        </svg>

                    </div>

                    <h3 class="mt-4 text-lg font-semibold text-slate-900">
                        No categories yet
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Create your first course category to organize your courses.
                    </p>

                    <a
                        href="{{ route('course-categories.create') }}"
                        class="sad-btn-primary mt-5 inline-flex h-11 items-center justify-center rounded-xl bg-violet-600 px-5 text-sm font-semibold text-white hover:bg-violet-700">

                        Add Category

                    </a>

                </div>

            @endforelse


            {{-- No Search Results --}}
            <div
                id="noResults"
                class="sad-card col-span-full hidden rounded-xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">

                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">

                    <svg class="h-7 w-7"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="1.5"
                              d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>

                    </svg>

                </div>

                <h3 class="mt-4 text-lg font-semibold text-slate-900">
                    No categories found
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Try searching for another category.
                </p>

            </div>

        </div>

    </div>


   <script>
    function initializeCategorySearch() {
        const searchInput = document.getElementById('categorySearch');
        const cards = document.querySelectorAll('.category-card');
        const noResults = document.getElementById('noResults');
        const categoryCount = document.getElementById('categoryCount');

        // Page is not the Categories page
        if (!searchInput || !categoryCount) {
            return;
        }

        function filterCategories() {
            const searchValue = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            cards.forEach(function (card) {
                const name = (card.dataset.name || '').toLowerCase();

                if (name.includes(searchValue)) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });

            categoryCount.textContent = visibleCount;

            if (noResults) {
                if (visibleCount === 0 && cards.length > 0) {
                    noResults.classList.remove('hidden');
                } else {
                    noResults.classList.add('hidden');
                }
            }
        }

        // Prevent duplicate event listeners
        searchInput.removeEventListener('input', searchInput._categorySearchHandler);

        searchInput._categorySearchHandler = filterCategories;

        searchInput.addEventListener('input', filterCategories);

        // Run once when the page is loaded/navigated to
        filterCategories();
    }

    // Normal page load
    document.addEventListener('DOMContentLoaded', initializeCategorySearch);

    // Livewire / Flux navigation
    document.addEventListener('livewire:navigated', initializeCategorySearch);
</script>

</x-layouts::app>