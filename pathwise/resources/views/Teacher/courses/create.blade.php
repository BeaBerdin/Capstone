<x-layouts::app :title="'Create Course'">
<div class="min-h-screen bg-slate-50/80 pb-12 text-slate-900">
    <div class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <a href="{{ route('teacher.my-courses') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-violet-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                Back to My Courses
            </a>
            <div class="mt-5">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-violet-600">Course Builder</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950">Create a new course</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Add a recognizable cover image and clear course information before building your lessons.</p>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                <p class="font-bold">Please review the highlighted fields.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('teacher.courses.store') }}" method="POST" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-[1fr_340px]">
            @csrf

            <div class="space-y-6">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-6">
                        <h2 class="text-lg font-extrabold text-slate-950">Course information</h2>
                        <p class="mt-1 text-sm text-slate-500">Tell students what the course is and what they can expect to learn.</p>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label for="title" class="text-sm font-bold text-slate-800">Course title <span class="text-rose-500">*</span></label>
                            <input id="title" name="title" value="{{ old('title') }}" required maxlength="255" placeholder="e.g. Basic Accounting Fundamentals"
                                   class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none transition focus:border-violet-400 focus:bg-white focus:ring-4 focus:ring-violet-100">
                            @error('title')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="description" class="text-sm font-bold text-slate-800">Description <span class="text-rose-500">*</span></label>
                            <textarea id="description" name="description" rows="6" required placeholder="Explain the skills, concepts, and outcomes students will gain..."
                                      class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 outline-none transition focus:border-violet-400 focus:bg-white focus:ring-4 focus:ring-violet-100">{{ old('description') }}</textarea>
                            @error('description')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="category_id" class="text-sm font-bold text-slate-800">Category <span class="text-rose-500">*</span></label>
                                <select id="category_id" name="category_id" required class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100">
                                    <option value="">Choose a category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="difficulty_level" class="text-sm font-bold text-slate-800">Difficulty <span class="text-rose-500">*</span></label>
                                <select id="difficulty_level" name="difficulty_level" required class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100">
                                    @foreach(['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('difficulty_level', 'beginner') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('difficulty_level')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-extrabold text-slate-950">Learning details</h2>
                    <p class="mt-1 text-sm text-slate-500">These details help students judge whether the course fits their needs.</p>

                    <div class="mt-6 grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="estimated_hours" class="text-sm font-bold text-slate-800">Estimated hours</label>
                            <input type="number" min="1" id="estimated_hours" name="estimated_hours" value="{{ old('estimated_hours') }}" placeholder="e.g. 8"
                                   class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100">
                        </div>
                        <div>
                            <label for="price" class="text-sm font-bold text-slate-800">Price (₱)</label>
                            <input type="number" min="0" step="0.01" id="price" name="price" value="{{ old('price', 0) }}"
                                   class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100">
                            <p class="mt-1 text-xs text-slate-400">Use 0 for a free course.</p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <label for="intro_video" class="text-sm font-bold text-slate-800">Intro video URL</label>
                        <input type="url" id="intro_video" name="intro_video" value="{{ old('intro_video') }}" placeholder="https://youtube.com/..."
                               class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100">
                        <p class="mt-1 text-xs text-slate-400">Optional. Add a short course preview or welcome video.</p>
                    </div>

                    <label class="mt-5 flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <input type="checkbox" name="certificate_available" value="1" @checked(old('certificate_available', true)) class="mt-1 h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                        <span>
                            <span class="block text-sm font-bold text-slate-800">Certificate available</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500">Students can receive a PathWise certificate after meeting the completion requirements.</span>
                        </span>
                    </label>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="sticky top-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-extrabold text-slate-950">Course cover</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Use a clean 16:9 image. JPG, PNG, or WebP up to 4 MB.</p>

                    <label for="thumbnail" class="mt-4 block cursor-pointer overflow-hidden rounded-2xl border border-dashed border-violet-300 bg-violet-50/60">
                        <div id="thumbnail-placeholder" class="flex aspect-video flex-col items-center justify-center p-6 text-center">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-violet-600 shadow-sm">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V6.75A2.25 2.25 0 015.25 4.5h13.5A2.25 2.25 0 0121 6.75v9.75m-18 0v.75a2.25 2.25 0 002.25 2.25h13.5A2.25 2.25 0 0021 17.25v-.75M3 16.5l4.72-4.72a2.25 2.25 0 013.182 0l1.348 1.348 2.098-2.098a2.25 2.25 0 013.182 0L21 14.5M14.25 8.25h.008v.008h-.008V8.25z"/></svg>
                            </div>
                            <p class="mt-3 text-sm font-bold text-violet-700">Upload a course image</p>
                            <p class="mt-1 text-xs text-slate-500">Click to browse</p>
                        </div>
                        <img id="thumbnail-preview" class="hidden aspect-video w-full object-cover" alt="Course thumbnail preview">
                    </label>
                    <input id="thumbnail" type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp" class="sr-only">
                    @error('thumbnail')<p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror

                    <div class="mt-6 rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Publishing flow</p>
                        <ol class="mt-3 space-y-3 text-sm text-slate-600">
                            <li class="flex gap-2"><span class="font-black text-violet-600">1.</span> Create the course as a draft.</li>
                            <li class="flex gap-2"><span class="font-black text-violet-600">2.</span> Add and organize lessons.</li>
                            <li class="flex gap-2"><span class="font-black text-violet-600">3.</span> Submit it for approval.</li>
                        </ol>
                    </div>

                    <button type="submit" class="mt-6 inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 text-sm font-bold text-white shadow-sm shadow-violet-200 transition hover:bg-violet-700">
                        Create Course
                    </button>
                    <a href="{{ route('teacher.my-courses') }}" class="mt-2 inline-flex h-11 w-full items-center justify-center rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50">Cancel</a>
                </section>
            </aside>
        </form>
    </div>
</div>

<script>
    document.getElementById('thumbnail')?.addEventListener('change', function (event) {
        const file = event.target.files?.[0];
        if (!file) return;
        const preview = document.getElementById('thumbnail-preview');
        const placeholder = document.getElementById('thumbnail-placeholder');
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');
        placeholder.classList.add('hidden');
    });
</script>
</x-layouts::app>
