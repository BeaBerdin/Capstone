<x-layouts::app :title="__('Course Categories')">

    <div class="space-y-8">

        {{-- Page Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">

            <div>
                <p class="text-sm font-medium text-purple-600 dark:text-purple-400">
                    Course Management
                </p>

                <h1 class="mt-1 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                    Course Categories
                </h1>

                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Organize courses into categories for easier browsing and management.
                </p>
            </div>

            <a href="{{ route('course-categories.create') }}"
               class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-700">

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


        {{-- Success Message --}}
        @if (session('success'))

            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-300">

                {{ session('success') }}

            </div>

        @endif


        {{-- Search --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">

            <div class="relative">

                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
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
                    class="w-full rounded-lg border-gray-300 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >

            </div>

        </div>


        {{-- Category Header --}}
        <div class="flex items-center justify-between">

            <div>

                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    All Categories
                </h2>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
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
                    class="category-card group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg dark:border-gray-700 dark:bg-gray-900"
                    data-name="{{ strtolower($category->name) }}"
                >

                    {{-- Icon --}}
                    <div class="flex items-start justify-between">

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400">

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
                    <h3 class="mt-5 line-clamp-1 text-lg font-bold text-gray-900 dark:text-white">

                        {{ $category->name }}

                    </h3>


                    {{-- Description --}}
                    <p class="mt-2 line-clamp-3 min-h-15 text-sm leading-relaxed text-gray-500 dark:text-gray-400">

                        {{ $category->description ?: 'No description available.' }}

                    </p>


                    {{-- Divider --}}
                    <div class="my-5 border-t border-gray-100 dark:border-gray-800"></div>


                    {{-- Actions --}}
                    <div class="flex gap-2">

                        <a
                            href="{{ route('course-categories.edit', $category) }}"
                            class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-center text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">

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
                                class="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50 dark:border-red-900/50 dark:text-red-400 dark:hover:bg-red-950/30">

                                Delete

                            </button>

                        </form>

                    </div>

                </div>

            @empty

                {{-- No Categories --}}
                <div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center dark:border-gray-700 dark:bg-gray-900">

                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-purple-100 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400">

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

                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                        No categories yet
                    </h3>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Create your first course category to organize your courses.
                    </p>

                    <a
                        href="{{ route('course-categories.create') }}"
                        class="mt-5 inline-flex rounded-lg bg-purple-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-purple-700">

                        Add Category

                    </a>

                </div>

            @endforelse


            {{-- No Search Results --}}
            <div
                id="noResults"
                class="col-span-full hidden rounded-xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center dark:border-gray-700 dark:bg-gray-900">

                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">

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

                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                    No categories found
                </h3>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
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