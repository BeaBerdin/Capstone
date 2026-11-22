@php
    $sectionBuilderDefaults = old('sections', old('reading_builder') ? [] : ($initialSections ?? []));
    $sectionBuilderDefaults = is_array($sectionBuilderDefaults) ? array_slice($sectionBuilderDefaults, 0, 50) : [];
    $storedSectionMap = collect($initialSections ?? [])->filter(fn ($section) => !empty($section['id']))->keyBy('id');
    $sectionBuilderDefaults = collect($sectionBuilderDefaults)->filter(fn ($section) => is_array($section))->map(function ($section) use ($storedSectionMap) {
        $inputs = array_filter(\Illuminate\Support\Arr::only($section, ['id', 'type', 'body', 'body_format', 'caption']), 'is_scalar');
        $stored = $storedSectionMap->get($inputs['id'] ?? null);
        return array_merge($stored ?? [], $inputs);
    })->values();
@endphp

<div class="mt-6" data-section-builder data-validation-retry="{{ old('reading_builder') ? 'true' : 'false' }}">
    <input type="hidden" name="reading_builder" value="1" data-reading-builder-marker>
    <div class="flex flex-col gap-4 rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-50 to-white p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <div>
            <p class="text-sm font-bold text-slate-900">Build your reading lesson</p>
            <p class="mt-1 text-xs leading-5 text-slate-600">Arrange headings, text, images, and downloadable files in the order students should see them.</p>
        </div>
        <div class="flex flex-wrap gap-2" data-section-add-buttons>
            <button type="button" data-add-section="text" class="rounded-xl bg-violet-600 px-3.5 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-violet-700">＋ Text</button>
            <button type="button" data-add-section="image" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-700 transition hover:border-violet-200 hover:bg-violet-50">＋ Image</button>
            <button type="button" data-add-section="file" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-700 transition hover:border-violet-200 hover:bg-violet-50">＋ File</button>
        </div>
    </div>

    <p class="mt-3 text-xs text-slate-500">Use the arrows on each section to set its position. Format text with the toolbar; images and files stay private and are shown only to people who can access this lesson.</p>

    <p data-reading-unsaved class="mt-3 text-xs font-semibold text-violet-800" role="status" hidden>You have unsaved lesson changes.</p>
    <p data-upload-reselect class="mt-3 text-xs text-amber-800" role="status" @if (!old('reading_builder')) hidden @endif>After a validation error or returning to a cached page, reselect any new uploads. Saved images and attachments remain unchanged.</p>
    <div class="mt-4 space-y-3" data-section-list>
        @foreach ($sectionBuilderDefaults as $index => $section)
            @php
                $sectionType = in_array($section['type'] ?? '', ['text', 'image', 'file'], true) ? $section['type'] : 'text';
                $sectionId = $section['id'] ?? null;
                $storedFile = $storedSectionMap->get($sectionId);
                $hasSavedFile = !empty($storedFile['file_path']);
                $sectionFileName = $section['original_name'] ?? null;
                $editorHtml = '';
                $editorError = false;
                if (($section['body_format'] ?? 'plain') === 'html') {
                    try {
                        $editorHtml = app(\App\Support\ReadingContent::class)->sanitize($section['body'] ?? '');
                    } catch (\InvalidArgumentException $exception) {
                        // Preserve rejected input verbatim in an escaped textarea; never truncate it.
                        $editorError = true;
                    }
                }
            @endphp
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" data-section-card>
                <input type="hidden" data-section-body_format value="{{ $section['body_format'] ?? 'plain' }}">
                <input type="hidden" data-section-id name="sections[{{ $index }}][id]" value="{{ $sectionId }}" @if (!$sectionId) disabled @endif>
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-sm font-bold text-violet-700" data-section-icon>{{ $sectionType === 'text' ? 'T' : ($sectionType === 'image' ? '▧' : '↧') }}</span>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-900" data-section-heading>{{ ucfirst($sectionType) }} section</p>
                            <p class="text-xs text-slate-500" data-section-order>Section {{ $index + 1 }}</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <button type="button" data-move-section="up" class="rounded-lg border border-slate-200 px-2.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50" aria-label="Move section up">↑</button>
                        <button type="button" data-move-section="down" class="rounded-lg border border-slate-200 px-2.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50" aria-label="Move section down">↓</button>
                        <button type="button" data-remove-section class="ml-1 rounded-lg px-2.5 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">Delete</button>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-[minmax(0,1fr)_220px]">
                    <div>
                        <label class="text-xs font-semibold text-slate-700">Section type</label>
                        <select data-section-type class="pw-field mt-1.5 h-11 px-3 text-sm">
                            <option value="text" @selected($sectionType === 'text')>Text</option>
                            <option value="image" @selected($sectionType === 'image')>Image</option>
                            <option value="file" @selected($sectionType === 'file')>File</option>
                        </select>
                    </div>
                    <div data-section-upload-wrap @if ($sectionType === 'text') class="hidden" @endif>
                        <label class="text-xs font-semibold text-slate-700" data-upload-label>{{ $sectionType === 'image' ? 'Choose image' : 'Choose learning file' }}</label>
                        @if ($sectionType === 'image' && $hasSavedFile && $storedFile['type'] === 'image')
                            <img src="{{ route('files.lesson-section', $sectionId) }}" alt="{{ $section['caption'] ?? 'Current lesson image' }}" data-image-preview class="mb-2 mt-2 max-h-36 w-full rounded-lg border border-slate-200 bg-slate-50 object-contain">
                        @else
                            <img alt="Selected lesson image preview" data-image-preview class="mb-2 mt-2 hidden max-h-36 w-full rounded-lg border border-slate-200 bg-slate-50 object-contain">
                        @endif
                        <input data-section-file type="file" class="pw-field mt-1.5 block min-h-11 px-2 py-2 text-xs" accept="{{ $sectionType === 'image' ? 'image/jpeg,image/png,image/gif,image/webp' : '.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt' }}">
                        <p class="mt-1.5 text-[11px] leading-4 text-slate-500" data-file-help>{{ $sectionType === 'image' ? 'JPG, PNG, GIF or WebP · up to 10 MB' : 'PDF, Office or TXT · up to 20 MB' }}</p>
                        @if ($hasSavedFile)
                            <a href="{{ route('files.lesson-section', $sectionId) }}" class="mt-2 inline-flex text-xs font-semibold text-violet-700 hover:text-violet-900" data-current-file data-saved-type="{{ $storedFile['type'] }}">{{ $sectionFileName ?: 'Open current attachment' }}</a>
                        @else
                            <a href="#" class="mt-2 hidden text-xs font-semibold text-violet-700 hover:text-violet-900" data-current-file></a>
                        @endif
                    </div>
                    <div data-section-caption-wrap class="sm:col-span-2">
                        <label class="text-xs font-semibold text-slate-700" data-caption-label>{{ $sectionType === 'text' ? 'Section heading (optional)' : ($sectionType === 'image' ? 'Image caption (optional)' : 'Link label (optional)') }}</label>
                        <input data-section-caption type="text" maxlength="500" value="{{ $section['caption'] ?? '' }}" placeholder="Add a short caption or helpful label" class="pw-field mt-1.5 h-11 px-3 text-sm">
                    </div>
                    <div data-section-text-wrap class="sm:col-span-2 @if ($sectionType !== 'text') hidden @endif">
                        <label class="text-xs font-semibold text-slate-700">Lesson text</label>
                        <textarea data-section-body data-editor-html="{{ $editorHtml }}" data-editor-error="{{ $editorError ? 'true' : 'false' }}" rows="6" maxlength="{{ ($section['body_format'] ?? 'plain') === 'html' ? 120000 : 30000 }}" placeholder="Write an explanation, concept, or learning note…" class="pw-field mt-1.5 resize-y px-3.5 py-3 text-sm leading-6">{{-- Browsers strip one initial textarea newline; this prefix preserves the body. --}}
{{ $section['body'] ?? '' }}</textarea>
                        <div data-reading-editor hidden></div>
                        <p data-editor-status class="reading-editor-status" role="status" aria-live="polite">{{ $editorError ? 'This formatted input exceeds the editor limits. Your original input is preserved above; correct it before saving.' : 'Formatting is available when JavaScript loads. Plain text is preserved until you edit it.' }}</p>
                    </div>
                </div>
            </section>
        @endforeach
    </div>

    <p class="mt-3 hidden rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700" data-section-limit role="status">A Reading lesson can contain up to 50 sections.</p>

    <textarea id="readingContent" data-content-input class="hidden" aria-hidden="true" tabindex="-1">{{ old('content', $lesson->content ?? '') }}</textarea>

    <template data-section-template>
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" data-section-card>
            <input type="hidden" data-section-body_format value="plain">
            <input type="hidden" data-section-id disabled>
            <div class="flex items-start justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-sm font-bold text-violet-700" data-section-icon>T</span>
                    <div class="min-w-0"><p class="text-sm font-bold text-slate-900" data-section-heading>Text section</p><p class="text-xs text-slate-500" data-section-order>Section</p></div>
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    <button type="button" data-move-section="up" class="rounded-lg border border-slate-200 px-2.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50" aria-label="Move section up">↑</button>
                    <button type="button" data-move-section="down" class="rounded-lg border border-slate-200 px-2.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50" aria-label="Move section down">↓</button>
                    <button type="button" data-remove-section class="ml-1 rounded-lg px-2.5 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">Delete</button>
                </div>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-[minmax(0,1fr)_220px]">
                <div><label class="text-xs font-semibold text-slate-700">Section type</label><select data-section-type class="pw-field mt-1.5 h-11 px-3 text-sm"><option value="text">Text</option><option value="image">Image</option><option value="file">File</option></select></div>
                <div data-section-upload-wrap class="hidden"><label class="text-xs font-semibold text-slate-700" data-upload-label>Choose file</label><img alt="Selected lesson image preview" data-image-preview class="mb-2 mt-2 hidden max-h-36 w-full rounded-lg border border-slate-200 bg-slate-50 object-contain"><input data-section-file type="file" class="pw-field mt-1.5 block min-h-11 px-2 py-2 text-xs"><p class="mt-1.5 text-[11px] leading-4 text-slate-500" data-file-help></p><a href="#" class="mt-2 hidden text-xs font-semibold text-violet-700 hover:text-violet-900" data-current-file></a></div>
                <div data-section-caption-wrap class="sm:col-span-2"><label class="text-xs font-semibold text-slate-700" data-caption-label></label><input data-section-caption type="text" maxlength="500" placeholder="Add a short heading, caption, or label" class="pw-field mt-1.5 h-11 px-3 text-sm"></div>
                <div data-section-text-wrap class="sm:col-span-2"><label class="text-xs font-semibold text-slate-700">Lesson text</label><textarea data-section-body rows="6" maxlength="30000" placeholder="Write an explanation, concept, or learning note…" class="pw-field mt-1.5 resize-y px-3.5 py-3 text-sm leading-6"></textarea><div data-reading-editor hidden></div><p data-editor-status class="reading-editor-status" role="status" aria-live="polite">Plain text is preserved until you edit it.</p></div>
            </div>
        </section>
    </template>
</div>
