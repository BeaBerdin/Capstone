<x-layouts::app :title="'Edit Course'">
<div class="min-h-screen bg-slate-50/80 pb-12 text-slate-900">
    <div class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <a href="{{ route('teacher.my-courses') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-violet-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                Back to My Courses
            </a>
            <p class="mt-5 text-xs font-bold uppercase tracking-[0.2em] text-violet-600">Course Builder</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950">Edit course</h1>
            <p class="mt-2 text-sm text-slate-500">Update the course details without changing its approval status.</p>
        </div>
    </div>

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                <p class="font-bold">Please review the highlighted fields.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('teacher.courses.update', $course) }}" method="POST" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-[1fr_340px]">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-extrabold text-slate-950">Course information</h2>
                    <div class="mt-6 space-y-5">
                        <div>
                            <label for="title" class="text-sm font-bold text-slate-800">Course title</label>
                            <input id="title" name="title" value="{{ old('title', $course->title) }}" required class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100">
                        </div>
                        <div>
                            <label for="description" class="text-sm font-bold text-slate-800">Description</label>
                            <textarea id="description" name="description" rows="6" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100">{{ old('description', $course->description) }}</textarea>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="category_id" class="text-sm font-bold text-slate-800">Category</label>
                                <select id="category_id" name="category_id" required class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100">
                                    @foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $course->category_id) === (string) $category->id)>{{ $category->name }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label for="difficulty_level" class="text-sm font-bold text-slate-800">Difficulty</label>
                                <select id="difficulty_level" name="difficulty_level" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100">
                                    @foreach(['beginner'=>'Beginner','intermediate'=>'Intermediate','advanced'=>'Advanced'] as $value => $label)<option value="{{ $value }}" @selected(old('difficulty_level', $course->difficulty_level) === $value)>{{ $label }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-extrabold text-slate-950">Learning details</h2>
                    <div class="mt-6 grid gap-5 md:grid-cols-2">
                        <div><label class="text-sm font-bold text-slate-800">Estimated hours</label><input type="number" min="1" name="estimated_hours" value="{{ old('estimated_hours', $course->estimated_hours) }}" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100"></div>
                        <div><label class="text-sm font-bold text-slate-800">Price (₱)</label><input type="number" min="0" step="0.01" name="price" value="{{ old('price', $course->price) }}" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100"></div>
                    </div>
                    <div class="mt-5"><label class="text-sm font-bold text-slate-800">Intro video URL</label><input type="url" name="intro_video" value="{{ old('intro_video', $course->intro_video) }}" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100"></div>
                    <label class="mt-5 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4"><input type="checkbox" name="certificate_available" value="1" @checked(old('certificate_available', $course->certificate_available)) class="mt-1 h-4 w-4 rounded text-violet-600"><span><span class="block text-sm font-bold text-slate-800">Certificate available</span><span class="mt-1 block text-xs text-slate-500">Enable completion certificates for eligible students.</span></span></label>
                </section>
            </div>

            <aside>
                <section class="sticky top-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3"><div><h2 class="text-base font-extrabold text-slate-950">Course cover</h2><p class="mt-1 text-xs text-slate-500">Upload a new image only if you want to replace the current cover.</p></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold uppercase text-slate-500">{{ $course->status }}</span></div>
                    <label for="thumbnail" class="mt-4 block cursor-pointer overflow-hidden rounded-2xl border border-dashed border-violet-300 bg-violet-50">
                        @if($course->thumbnail)
                            <img id="thumbnail-preview" src="{{ asset('storage/' . $course->thumbnail) }}" class="aspect-video w-full object-cover" alt="Current course cover">
                            <div id="thumbnail-placeholder" class="hidden"></div>
                        @else
                            <div id="thumbnail-placeholder" class="flex aspect-video flex-col items-center justify-center p-6 text-center"><div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-violet-600 shadow-sm"><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V6.75A2.25 2.25 0 015.25 4.5h13.5A2.25 2.25 0 0121 6.75v9.75M3 16.5l4.72-4.72a2.25 2.25 0 013.182 0L12.25 13.13l2.098-2.098a2.25 2.25 0 013.182 0L21 14.5"/></svg></div><p class="mt-3 text-sm font-bold text-violet-700">Add a course image</p></div>
                            <img id="thumbnail-preview" class="hidden aspect-video w-full object-cover" alt="Course cover preview">
                        @endif
                    </label>
                    <input id="thumbnail" type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp" class="sr-only">
                    @error('thumbnail')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror

                    <button class="mt-6 h-12 w-full rounded-xl bg-violet-600 text-sm font-bold text-white hover:bg-violet-700">Save Changes</button>
                    <a href="{{ route('teacher.lessons', $course) }}" class="mt-2 inline-flex h-11 w-full items-center justify-center rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50">Manage Lessons</a>
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
        preview.src = URL.createObjectURL(file); preview.classList.remove('hidden'); placeholder?.classList.add('hidden');
    });
</script>
</x-layouts::app>
