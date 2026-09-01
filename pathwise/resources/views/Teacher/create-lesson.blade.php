<x-layouts::app :title="'Add Lesson'">
<div class="min-h-screen bg-slate-50/80 pb-12 text-slate-900">
    <div class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <a href="{{ route('teacher.lessons', $course) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-violet-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                Back to Course Lessons
            </a>
            <p class="mt-5 text-xs font-bold uppercase tracking-[0.2em] text-violet-600">{{ $course->title }}</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950">Add a lesson</h1>
            <p class="mt-2 text-sm text-slate-500">Choose the content type and give students enough context before they open the lesson.</p>
        </div>
    </div>

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                <p class="font-bold">Please fix the following:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('teacher.lessons.store', $course) }}" method="POST" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-[1fr_310px]">
            @csrf
            <div class="space-y-6">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-extrabold text-slate-950">Lesson details</h2>
                    <div class="mt-6 space-y-5">
                        <div>
                            <label class="text-sm font-bold text-slate-800">Lesson title <span class="text-rose-500">*</span></label>
                            <input name="title" value="{{ old('title') }}" required placeholder="e.g. Understanding the Accounting Equation" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-violet-400 focus:bg-white focus:ring-4 focus:ring-violet-100">
                        </div>

                        <div>
                            <label class="text-sm font-bold text-slate-800">Lesson summary or content</label>
                            <textarea name="content" rows="7" placeholder="Write a short lesson overview, learning notes, instructions, or the reading content itself..." class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 outline-none focus:border-violet-400 focus:bg-white focus:ring-4 focus:ring-violet-100">{{ old('content') }}</textarea>
                            <p class="mt-1 text-xs text-slate-400">This text also becomes the lesson preview shown in the teacher and student interfaces.</p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="text-sm font-bold text-slate-800">Content type <span class="text-rose-500">*</span></label>
                                <select id="lesson_type" name="lesson_type" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100">
                                    @foreach(['video'=>'Video','text'=>'Reading / Text','document'=>'Document','quiz'=>'Quiz'] as $value => $label)<option value="{{ $value }}" @selected(old('lesson_type', 'video') === $value)>{{ $label }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-bold text-slate-800">Lesson order <span class="text-rose-500">*</span></label>
                                <input type="number" min="1" name="lesson_order" value="{{ old('lesson_order', $course->lessons()->max('lesson_order') + 1) }}" required class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100">
                            </div>
                            <div>
                                <label class="text-sm font-bold text-slate-800">Duration (minutes)</label>
                                <input type="number" min="1" name="duration_minutes" value="{{ old('duration_minutes') }}" placeholder="15" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100">
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-extrabold text-slate-950">Lesson media</h2>
                    <p class="mt-1 text-sm text-slate-500">Add the media that matches the lesson type. These are optional for text-only lessons.</p>

                    <div class="mt-6 space-y-5">
                        <div id="video-field">
                            <label class="text-sm font-bold text-slate-800">Video URL</label>
                            <input type="url" name="video_url" value="{{ old('video_url') }}" placeholder="https://youtube.com/watch?v=..." class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100">
                            <p class="mt-1 text-xs text-slate-400">YouTube links receive a visual thumbnail automatically on the lesson manager.</p>
                        </div>

                        <div id="file-field">
                            <label class="text-sm font-bold text-slate-800">Attach a document</label>
                            <label class="mt-2 flex cursor-pointer items-center gap-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 hover:border-violet-300 hover:bg-violet-50/50">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-violet-600 shadow-sm"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94a3 3 0 114.243 4.243L8.552 18.32a1.5 1.5 0 01-2.121-2.121l9.814-9.814"/></svg></div>
                                <div><p class="text-sm font-bold text-slate-700">Choose a file</p><p class="mt-1 text-xs text-slate-400">PDF, Word, PowerPoint, Excel, or text — up to 10 MB</p></div>
                                <input type="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt" class="sr-only">
                            </label>
                        </div>
                    </div>
                </section>
            </div>

            <aside>
                <section class="sticky top-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-extrabold text-slate-950">Publishing</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Control whether students can access this lesson now.</p>

                    <label class="mt-5 flex cursor-pointer items-start gap-3 rounded-xl bg-slate-50 p-4"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', true)) class="mt-1 h-4 w-4 rounded text-violet-600"><span><span class="block text-sm font-bold text-slate-800">Published</span><span class="mt-1 block text-xs text-slate-500">Show this lesson to enrolled students.</span></span></label>
                    <label class="mt-3 flex cursor-pointer items-start gap-3 rounded-xl bg-slate-50 p-4"><input type="checkbox" name="is_preview" value="1" @checked(old('is_preview')) class="mt-1 h-4 w-4 rounded text-violet-600"><span><span class="block text-sm font-bold text-slate-800">Free preview</span><span class="mt-1 block text-xs text-slate-500">Allow students to preview this lesson before enrolling.</span></span></label>

                    <button class="mt-6 h-12 w-full rounded-xl bg-violet-600 text-sm font-bold text-white shadow-sm shadow-violet-200 hover:bg-violet-700">Save Lesson</button>
                    <a href="{{ route('teacher.lessons', $course) }}" class="mt-2 inline-flex h-11 w-full items-center justify-center rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50">Cancel</a>
                </section>
            </aside>
        </form>
    </div>
</div>

<script>
    const lessonType = document.getElementById('lesson_type');
    const updateMediaHints = () => {
        const type = lessonType?.value;
        document.getElementById('video-field')?.classList.toggle('opacity-60', type !== 'video');
        document.getElementById('file-field')?.classList.toggle('opacity-60', type !== 'document');
    };
    lessonType?.addEventListener('change', updateMediaHints); updateMediaHints();
</script>
</x-layouts::app>
