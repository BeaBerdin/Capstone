import { mountReadingEditor } from './reading-editor';
import '../css/reading-editor.css';

const builders = new Map();
const dirtyMessage = 'Leave this lesson and discard unsaved changes? New file selections will need to be selected again.';

function formSignature(form) {
    return JSON.stringify([...new FormData(form)].filter(([key]) => key !== '_token').map(([key, value]) => [
        key, value instanceof File ? (value.name ? [value.name, value.size, value.lastModified] : null) : value,
    ]));
}

function preserveFormForHistory(form) {
    for (const input of form.querySelectorAll('input:not([type="file"])')) {
        input.setAttribute('value', input.value);
        if (['checkbox', 'radio'].includes(input.type)) input.toggleAttribute('checked', input.checked);
    }
    for (const textarea of form.querySelectorAll('textarea')) textarea.textContent = textarea.value;
    for (const option of form.querySelectorAll('option')) option.toggleAttribute('selected', option.selected);
}

export function initializeSectionBuilder(root) {
    if (builders.has(root)) return;
    const list = root.querySelector('[data-section-list]');
    const template = root.querySelector('[data-section-template]');
    const form = root.closest('form');
    if (!form || !list || !template) return;
    const controller = new AbortController();
    const listen = (node, type, callback, options = {}) => node.addEventListener(type, callback, { ...options, signal: controller.signal });
    const editors = new Map();
    const isReading = () => form.querySelector('input[name="lesson_type"]:checked')?.value === 'text';
    const mirror = root.querySelector('[data-content-input]');
    const limitMessage = root.querySelector('[data-section-limit]');
    let baseline;
    const forceDirty = root.dataset.validationRetry === 'true' || root.dataset.readingRestoredDirty === 'true';
    const state = { dirty: forceDirty, submitting: false };
    builders.set(root, state);
    root.dataset.initialized = 'true';
    const updateDirty = () => {
        state.dirty = forceDirty || (baseline !== undefined && formSignature(form) !== baseline);
        root.dataset.readingDirty = String(state.dirty);
        root.querySelector('[data-reading-unsaved]').hidden = !state.dirty;
    };
    const renumber = () => {
        const cards = [...list.querySelectorAll('[data-section-card]')];
        const enabled = isReading();
        root.querySelector('[data-reading-builder-marker]').disabled = !enabled;
        cards.forEach((card, index) => {
            editors.get(card)?.sync();
            card.querySelector('[data-section-order]').textContent = `Section ${index + 1}`;
            const sectionId = card.querySelector('[data-section-id]');
            sectionId.name = `sections[${index}][id]`;
            sectionId.disabled = !enabled || !sectionId.value;
            const type = card.querySelector('[data-section-type]').value;
            const format = card.querySelector('[data-section-body_format]');
            format.name = `sections[${index}][body_format]`;
            format.disabled = !enabled || type !== 'text';
            for (const field of ['type', 'body', 'caption', 'file']) {
                const input = card.querySelector(`[data-section-${field}]`);
                input.name = `sections[${index}][${field}]`;
                input.id = `reading-section-${index}-${field}`;
                const label = input.parentElement.querySelector('label');
                if (label) label.htmlFor = input.id;
                input.disabled = !enabled || (field === 'body' && type !== 'text') || (field === 'file' && type === 'text');
            }
            editors.get(card)?.label(`Lesson text, section ${index + 1}`);
            editors.get(card)?.setEnabled(enabled && type === 'text');
            card.querySelector('[data-move-section="up"]').disabled = index === 0;
            card.querySelector('[data-move-section="down"]').disabled = index === cards.length - 1;
        });
        mirror.value = cards.filter(card => card.querySelector('[data-section-type]').value === 'text').map(card => {
            const heading = card.querySelector('[data-section-caption]').value.trim();
            const body = card.querySelector('[data-section-body]').value.trim();
            const rich = card.querySelector('[data-section-body_format]').value === 'html';
            return [heading, rich ? (card.dataset.plainText || '') : body].filter(Boolean).join('\n');
        }).filter(Boolean).join('\n\n');
        mirror.dispatchEvent(new Event('input', { bubbles: true }));
        // This capture-time update precedes the create/edit page's existing submit listeners.
        const hiddenContent = form.querySelector('input[name="content"]');
        if (enabled && hiddenContent) hiddenContent.value = mirror.value;
        limitMessage.classList.toggle('hidden', cards.length < 50);
        root.querySelectorAll('[data-add-section]').forEach(button => { button.disabled = cards.length >= 50; });
        updateDirty();
    };
    const revokePreview = card => {
        const preview = card.querySelector('[data-image-preview]');
        if (preview.src.startsWith('blob:')) { URL.revokeObjectURL(preview.src); preview.removeAttribute('src'); }
    };
    const configureCard = (card, type) => {
        const select = card.querySelector('[data-section-type]');
        if (type) select.value = type;
        const selected = select.value;
        const isText = selected === 'text';
        const isImage = selected === 'image';
        const savedRich = card.querySelector('[data-section-id]').value && card.querySelector('[data-section-body_format]').value === 'html';
        if (savedRich) for (const option of select.options) option.disabled = option.value !== 'text';
        card.querySelector('[data-section-icon]').textContent = isText ? 'T' : (isImage ? '▧' : '⇩');
        card.querySelector('[data-section-heading]').textContent = `${selected[0].toUpperCase()}${selected.slice(1)} section`;
        card.querySelector('[data-section-text-wrap]').classList.toggle('hidden', !isText);
        card.querySelector('[data-section-upload-wrap]').classList.toggle('hidden', isText);
        const file = card.querySelector('[data-section-file]');
        const imagePreview = card.querySelector('[data-image-preview]');
        const currentLink = card.querySelector('[data-current-file]');
        const hasSavedFile = currentLink.dataset.savedType === selected;
        const selectedFile = file.files[0];
        revokePreview(card);
        if (isImage && selectedFile?.type.startsWith('image/')) imagePreview.src = URL.createObjectURL(selectedFile);
        else if (isImage && hasSavedFile) imagePreview.src = currentLink.href;
        else imagePreview.removeAttribute('src');
        imagePreview.classList.toggle('hidden', !imagePreview.getAttribute('src'));
        currentLink.classList.toggle('hidden', !hasSavedFile || !!selectedFile);
        file.accept = isImage ? 'image/jpeg,image/png,image/gif,image/webp' : '.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt';
        card.querySelector('[data-upload-label]').textContent = isImage ? 'Choose image' : 'Choose learning file';
        card.querySelector('[data-file-help]').textContent = isImage ? 'JPG, PNG, GIF or WebP up to 10 MB' : 'PDF, Office or TXT up to 20 MB';
        card.querySelector('[data-caption-label]').textContent = isText ? 'Section heading (optional)' : (isImage ? 'Image caption (optional)' : 'Link label (optional)');
        file.required = !isText && !hasSavedFile;
        if (isText && !editors.has(card)) {
            const editor = mountReadingEditor(card, renumber);
            if (editor) editors.set(card, editor);
        }
        renumber();
    };
    const addCard = type => {
        if (list.querySelectorAll('[data-section-card]').length >= 50) return;
        const card = template.content.firstElementChild.cloneNode(true);
        list.append(card);
        configureCard(card, type);
        card.querySelector('[data-section-type]').focus();
    };
    listen(list, 'click', event => {
        const card = event.target.closest('[data-section-card]');
        if (!card) return;
        if (event.target.closest('[data-remove-section]')) {
            editors.get(card)?.destroy(); editors.delete(card); revokePreview(card); card.remove(); renumber();
        } else if (event.target.closest('[data-move-section="up"]') && card.previousElementSibling) {
            list.insertBefore(card, card.previousElementSibling); renumber();
        } else if (event.target.closest('[data-move-section="down"]') && card.nextElementSibling) {
            list.insertBefore(card.nextElementSibling, card); renumber();
        }
    });
    listen(list, 'input', event => {
        if (event.target.matches('[data-section-body], [data-section-caption]')) renumber();
    });
    listen(list, 'change', event => {
        const card = event.target.closest('[data-section-card]');
        if (!card) return;
        if (event.target.matches('[data-section-type]')) {
            card.querySelector('[data-section-file]').value = ''; configureCard(card);
        } else if (event.target.matches('[data-section-file]')) configureCard(card);
    });
    root.querySelectorAll('[data-add-section]').forEach(button => listen(button, 'click', () => addCard(button.dataset.addSection)));
    form.querySelectorAll('input[name="lesson_type"]').forEach(radio => listen(radio, 'change', renumber));
    listen(form, 'input', updateDirty);
    listen(form, 'change', updateDirty);
    listen(form, 'submit', event => {
        renumber();
        state.submitting = true;
        queueMicrotask(() => { if (event.defaultPrevented) state.submitting = false; });
    }, { capture: true });
    state.destroy = () => {
        renumber();
        root.dataset.readingRestoredDirty = String(state.dirty);
        if ([...form.querySelectorAll('input[type="file"]')].some(input => input.files.length)) root.querySelector('[data-upload-reselect]').hidden = false;
        for (const editor of editors.values()) editor.destroy();
        list.querySelectorAll('[data-section-card]').forEach(revokePreview);
        preserveFormForHistory(form);
        controller.abort(); editors.clear(); builders.delete(root);
        delete root.dataset.initialized;
    };
    list.querySelectorAll('[data-section-card]').forEach(card => configureCard(card));
    renumber();
    baseline = formSignature(form);
    updateDirty();
}

function start() {
    for (const [root, state] of builders) if (!root.isConnected) state.destroy();
    document.querySelectorAll('[data-section-builder]').forEach(initializeSectionBuilder);
}
document.addEventListener('livewire:navigate', event => {
    if ([...builders.values()].some(state => state.dirty && !state.submitting) && !window.confirm(dirtyMessage)) event.preventDefault();
});
// Cleanup only after navigation is accepted. Cancellation must leave DOM, files, and undo history intact.
document.addEventListener('livewire:navigating', () => [...builders.values()].forEach(state => state.destroy()));
document.addEventListener('livewire:navigated', start);
window.addEventListener('pageshow', start);
window.addEventListener('beforeunload', event => {
    if ([...builders.values()].some(state => state.dirty && !state.submitting)) {
        event.preventDefault(); event.returnValue = '';
    }
});
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
else start();
