@php
    $sectionBuilderDefaults = old('sections', old('reading_builder') ? [] : ($initialSections ?? []));
    $sectionBuilderDefaults = is_array($sectionBuilderDefaults) ? array_slice($sectionBuilderDefaults, 0, 50) : [];
    $storedSectionMap = collect($initialSections ?? [])->filter(fn ($section) => !empty($section['id']))->keyBy('id');
    $sectionBuilderDefaults = collect($sectionBuilderDefaults)->filter(fn ($section) => is_array($section))->map(function ($section) use ($storedSectionMap) {
        $inputs = array_filter(\Illuminate\Support\Arr::only($section, ['id', 'type', 'body', 'caption']), 'is_scalar');
        $stored = $storedSectionMap->get($inputs['id'] ?? null);
        return array_merge($stored ?? [], $inputs);
    })->values();
@endphp

<div class="mt-6" data-section-builder>
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

    <p class="mt-3 text-xs text-slate-500">Use the arrows on each section to set its position. Text is saved as readable paragraphs; images and files stay private and are shown only to people who can access this lesson.</p>

    <div class="mt-4 space-y-3" data-section-list>
        @foreach ($sectionBuilderDefaults as $index => $section)
            @php
                $sectionType = in_array($section['type'] ?? '', ['text', 'image', 'file'], true) ? $section['type'] : 'text';
                $sectionId = $section['id'] ?? null;
                $storedFile = $storedSectionMap->get($sectionId);
                $hasSavedFile = !empty($storedFile['file_path']);
                $sectionFileName = $section['original_name'] ?? null;
            @endphp
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" data-section-card>
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
                        <textarea data-section-body rows="6" maxlength="30000" placeholder="Write an explanation, concept, or learning note…" class="pw-field mt-1.5 resize-y px-3.5 py-3 text-sm leading-6">{{ $section['body'] ?? '' }}</textarea>
                        <p class="mt-1.5 text-[11px] text-slate-500">Separate paragraphs with a blank line for easier reading.</p>
                    </div>
                </div>
            </section>
        @endforeach
    </div>

    <p class="mt-3 hidden rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700" data-section-limit role="status">A Reading lesson can contain up to 50 sections.</p>

    <textarea id="readingContent" data-content-input class="hidden" aria-hidden="true" tabindex="-1">{{ old('content', $lesson->content ?? '') }}</textarea>

    <template data-section-template>
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" data-section-card>
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
                <div data-section-text-wrap class="sm:col-span-2"><label class="text-xs font-semibold text-slate-700">Lesson text</label><textarea data-section-body rows="6" maxlength="30000" placeholder="Write an explanation, concept, or learning note…" class="pw-field mt-1.5 resize-y px-3.5 py-3 text-sm leading-6"></textarea><p class="mt-1.5 text-[11px] text-slate-500">Separate paragraphs with a blank line for easier reading.</p></div>
            </div>
        </section>
    </template>
</div>

<script>
(() => {
    const initializeSectionBuilder = (root) => {
        if (!root || root.dataset.initialized === 'true') return;
        root.dataset.initialized = 'true';
        const list = root.querySelector('[data-section-list]');
        const template = root.querySelector('[data-section-template]');
        const form = root.closest('form');
        const isReading = () => form.querySelector('input[name="lesson_type"]:checked')?.value === 'text';
        const mirror = root.querySelector('#readingContent');
        const limitMessage = root.querySelector('[data-section-limit]');

        const renumber = () => {
            const cards = [...list.querySelectorAll('[data-section-card]')];
            const enabled = isReading();
            root.querySelector('[data-reading-builder-marker]').disabled = !enabled;
            cards.forEach((card, index) => {
                card.querySelector('[data-section-order]').textContent = `Section ${index + 1}`;
                const sectionId = card.querySelector('[data-section-id]');
                sectionId.name = `sections[${index}][id]`;
                sectionId.disabled = !enabled || !sectionId.value;
                const type = card.querySelector('[data-section-type]').value;
                for (const field of ['type', 'body', 'caption', 'file']) {
                    const input = card.querySelector('[data-section-' + field + ']');
                    input.name = 'sections[' + index + '][' + field + ']';
                    input.id = 'reading-section-' + index + '-' + field;
                    input.parentElement.querySelector('label').htmlFor = input.id;
                    input.disabled = !enabled || (field === 'body' && type !== 'text') || (field === 'file' && type === 'text');
                }
                card.querySelector('[data-move-section="up"]').disabled = index === 0;
                card.querySelector('[data-move-section="down"]').disabled = index === cards.length - 1;
            });
            const text = cards
                .filter(card => card.querySelector('[data-section-type]').value === 'text')
                .map(card => {
                    const heading = card.querySelector('[data-section-caption]').value.trim();
                    const body = card.querySelector('[data-section-body]').value.trim();
                    return [heading, body].filter(Boolean).join('\n');
                })
                .filter(Boolean)
                .join('\n\n');
            mirror.value = text;
            mirror.dispatchEvent(new Event('input', { bubbles: true }));
            limitMessage.classList.toggle('hidden', cards.length < 50);
            root.querySelectorAll('[data-add-section]').forEach(button => button.disabled = cards.length >= 50);
        };

        const configureCard = (card, type) => {
            const select = card.querySelector('[data-section-type]');
            if (type) select.value = type;
            const selected = select.value;
            const isText = selected === 'text';
            const isImage = selected === 'image';
            card.querySelector('[data-section-icon]').textContent = isText ? 'T' : (isImage ? '▧' : '↧');
            card.querySelector('[data-section-heading]').textContent = `${selected[0].toUpperCase()}${selected.slice(1)} section`;
            card.querySelector('[data-section-text-wrap]').classList.toggle('hidden', !isText);
            card.querySelector('[data-section-upload-wrap]').classList.toggle('hidden', isText);
            const file = card.querySelector('[data-section-file]');
            const imagePreview = card.querySelector('[data-image-preview]');
            const currentLink = card.querySelector('[data-current-file]');
            const hasSavedFile = currentLink.dataset.savedType === selected;
            const selectedFile = file.files[0];
            if (imagePreview.src.startsWith('blob:')) URL.revokeObjectURL(imagePreview.src);
            if (isImage && selectedFile?.type.startsWith('image/')) {
                imagePreview.src = URL.createObjectURL(selectedFile);
            } else if (isImage && hasSavedFile) {
                imagePreview.src = currentLink.href;
            } else {
                imagePreview.removeAttribute('src');
            }
            imagePreview.classList.toggle('hidden', !imagePreview.getAttribute('src'));
            currentLink.classList.toggle('hidden', !hasSavedFile || !!selectedFile);
            file.accept = isImage ? 'image/jpeg,image/png,image/gif,image/webp' : '.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt';
            card.querySelector('[data-upload-label]').textContent = isImage ? 'Choose image' : 'Choose learning file';
            card.querySelector('[data-file-help]').textContent = isImage ? 'JPG, PNG, GIF or WebP up to 10 MB' : 'PDF, Office or TXT up to 20 MB';
            card.querySelector('[data-caption-label]').textContent = isText ? 'Section heading (optional)' : (isImage ? 'Image caption (optional)' : 'Link label (optional)');
            file.required = !isText && !hasSavedFile;
            renumber();
        };

        const addCard = (type) => {
            if (list.querySelectorAll('[data-section-card]').length >= 50) return;
            const card = template.content.firstElementChild.cloneNode(true);
            list.append(card);
            configureCard(card, type);
            card.querySelector('[data-section-type]').focus();
        };

        list.addEventListener('click', event => {
            const card = event.target.closest('[data-section-card]');
            if (!card) return;
            if (event.target.closest('[data-remove-section]')) {
                const preview = card.querySelector('[data-image-preview]');
                if (preview.src.startsWith('blob:')) URL.revokeObjectURL(preview.src);
                card.remove();
                renumber();
            } else if (event.target.closest('[data-move-section="up"]') && card.previousElementSibling) {
                list.insertBefore(card, card.previousElementSibling);
                renumber();
            } else if (event.target.closest('[data-move-section="down"]') && card.nextElementSibling) {
                list.insertBefore(card.nextElementSibling, card);
                renumber();
            }
        });
        list.addEventListener('input', event => {
            if (event.target.matches('[data-section-body], [data-section-caption]')) renumber();
        });
        list.addEventListener('change', event => {
            const card = event.target.closest('[data-section-card]');
            if (!card) return;
            if (event.target.matches('[data-section-type]')) {
                card.querySelector('[data-section-file]').value = '';
                configureCard(card);
            } else if (event.target.matches('[data-section-file]')) {
                configureCard(card);
            }
        });
        root.querySelectorAll('[data-add-section]').forEach(button => button.addEventListener('click', () => addCard(button.dataset.addSection)));

        form.querySelectorAll('input[name="lesson_type"]').forEach(radio => radio.addEventListener('change', renumber));
        list.querySelectorAll('[data-section-card]').forEach(card => configureCard(card));
        renumber();
    };

    const start = () => document.querySelectorAll('[data-section-builder]').forEach(initializeSectionBuilder);
    document.addEventListener('livewire:navigated', start);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
</script>
