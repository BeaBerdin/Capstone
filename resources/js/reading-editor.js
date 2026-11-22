import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';

// JSON text nodes keep legacy markup, whitespace, and line breaks literal.
export function plainDocument(value) {
    const content = [];
    value.split(/\r\n|\r|\n/).forEach((line, index) => {
        if (index) content.push({ type: 'hardBreak' });
        if (line) content.push({ type: 'text', text: line });
    });
    return { type: 'doc', content: [{ type: 'paragraph', ...(content.length ? { content } : {}) }] };
}

export function allowedReadingLink(value) {
    if (!/^(https?:\/\/|mailto:)/i.test(value) || /[\u0000-\u0020\u007f]/.test(value)) return false;
    try {
        const url = new URL(value);
        if (!['http:', 'https:', 'mailto:'].includes(url.protocol)) return false;
        let path = url.pathname;
        for (let i = 0; i < 3; i++) path = decodeURIComponent(path);
        return !/(?:^|\/)(?:storage|lesson-files|assignment-submissions|payment-proofs)(?:\/|$)/i.test(path.replaceAll('\\', '/'));
    } catch { return false; }
}

// A convenience filter before schema parsing, never a substitute for ReadingContent.
// Template contents are inert: pasted images/scripts are removed before any live DOM insertion.
export function filterReadingPaste(html) {
    const template = document.createElement('template');
    template.innerHTML = html;
    const allowed = new Set(['P', 'BR', 'STRONG', 'B', 'EM', 'I', 'U', 'H2', 'H3', 'OL', 'UL', 'LI', 'A', 'CODE', 'PRE']);
    const discard = new Set(['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'EMBED', 'IMG', 'SVG', 'MATH', 'NOSCRIPT', 'TEMPLATE', 'VIDEO', 'AUDIO', 'SOURCE', 'LINK', 'META', 'INPUT', 'TEXTAREA', 'SELECT', 'BUTTON']);
    for (const element of [...template.content.querySelectorAll('*')]) {
        if (discard.has(element.tagName)) { element.remove(); continue; }
        if (!allowed.has(element.tagName)) { element.replaceWith(...element.childNodes); continue; }
        for (const attribute of [...element.attributes]) {
            if (!(element.tagName === 'A' && ['href', 'title'].includes(attribute.name))) element.removeAttribute(attribute.name);
        }
        if (element.tagName === 'A') {
            if (!allowedReadingLink(element.getAttribute('href') || '')) element.removeAttribute('href');
            element.setAttribute('rel', 'noopener noreferrer');
        }
    }
    return template.innerHTML;
}

export const readingExtensions = () => [StarterKit.configure({
    heading: { levels: [2, 3] },
    blockquote: false,
    strike: false,
    horizontalRule: false,
    trailingNode: false,
    link: {
        openOnClick: false, autolink: false, linkOnPaste: false,
        protocols: ['http', 'https', 'mailto'],
        isAllowedUri: allowedReadingLink,
        HTMLAttributes: { target: null, rel: 'noopener noreferrer' },
    },
    codeBlock: { languageClassPrefix: null },
})];

const controls = [
    ['bold', 'B', 'Bold (Ctrl+B)', 'toggleBold'],
    ['italic', 'I', 'Italic (Ctrl+I)', 'toggleItalic'],
    ['underline', 'U', 'Underline (Ctrl+U)', 'toggleUnderline'],
    ['h2', 'H2', 'Heading 2', 'toggleHeading', { level: 2 }],
    ['h3', 'H3', 'Heading 3', 'toggleHeading', { level: 3 }],
    ['bulletList', '• List', 'Bulleted list', 'toggleBulletList'],
    ['orderedList', '1. List', 'Numbered list', 'toggleOrderedList'],
    ['link', 'Link', 'Add or edit link'],
    ['code', '</>', 'Inline code', 'toggleCode'],
    ['codeBlock', 'Code block', 'Code block', 'toggleCodeBlock'],
    ['undo', 'Undo', 'Undo (Ctrl+Z)', 'undo'],
    ['redo', 'Redo', 'Redo (Ctrl+Shift+Z)', 'redo'],
];

function element(tag, attributes = {}, text) {
    const node = document.createElement(tag);
    for (const [key, value] of Object.entries(attributes)) node.setAttribute(key, value);
    if (text !== undefined) node.textContent = text;
    return node;
}

let serial = 0;
export function mountReadingEditor(card, onChange) {
    const textarea = card.querySelector('[data-section-body]');
    const format = card.querySelector('[data-section-body_format]');
    const host = card.querySelector('[data-reading-editor]');
    const status = card.querySelector('[data-editor-status]');
    if (textarea.dataset.editorError === 'true') return null;
    const originalBody = textarea.value;
    const originalFormat = format.value === 'html' ? 'html' : 'plain';
    const toolbar = element('div', { class: 'reading-editor-toolbar', role: 'toolbar', 'aria-label': 'Text formatting' });
    const surface = element('div', { class: 'reading-editor-surface' });
    const linkPanel = element('div', { class: 'reading-editor-link', hidden: '' });
    const linkId = `reading-link-${++serial}`;
    const linkLabel = element('label', { for: linkId }, 'Link address (https://, http:// or mailto:)');
    const linkInput = element('input', { id: linkId, type: 'text', inputmode: 'url', autocomplete: 'off' });
    const linkError = element('p', { role: 'alert', class: 'reading-editor-link-error' });
    const apply = element('button', { type: 'button' }, 'Apply link');
    const remove = element('button', { type: 'button' }, 'Remove link');
    const cancel = element('button', { type: 'button' }, 'Cancel');
    linkPanel.append(linkLabel, linkInput, apply, remove, cancel, linkError);
    host.append(toolbar, linkPanel, surface);
    host.classList.add('reading-editor');
    host.hidden = false;
    let baseline;
    let editor;
    let linkSelection;
    let currentText = originalBody;
    const sync = () => {
        if (!editor || !baseline) return;
        const changed = JSON.stringify(editor.getJSON()) !== baseline;
        textarea.value = changed ? editor.getHTML() : originalBody;
        format.value = changed ? 'html' : originalFormat;
        currentText = editor.getText({ blockSeparator: '\n\n' });
        card.dataset.plainText = currentText;
        textarea.maxLength = format.value === 'html' ? 120000 : Math.max(30000, originalBody.length);
        const message = changed ? 'Formatted text ready to save. Changes are not saved yet.'
            : (originalFormat === 'plain' ? 'Original plain text is preserved until you edit it.' : 'Formatting is preserved until you edit it.');
        if (status.textContent !== message) status.textContent = message;
    };
    try {
        editor = new Editor({
            element: surface,
            extensions: readingExtensions(),
            content: originalFormat === 'plain' ? plainDocument(originalBody)
                : filterReadingPaste(textarea.dataset.editorHtml || originalBody),
            parseOptions: { preserveWhitespace: 'full' },
            editorProps: {
                attributes: { role: 'textbox', 'aria-label': 'Lesson text', 'aria-multiline': 'true', spellcheck: 'true' },
                transformPastedHTML: (html) => {
                    if (new TextEncoder().encode(html).length > 120000) {
                        status.textContent = 'That paste exceeds 120,000 bytes. Paste a smaller selection.';
                        return '';
                    }
                    return filterReadingPaste(html);
                },
            },
            onUpdate: () => { sync(); onChange(); },
            onTransaction: () => { if (editor) updateButtons(); },
        });
        baseline = JSON.stringify(editor.getJSON());
    } catch (error) {
        host.replaceChildren(); host.hidden = true;
        status.textContent = 'The formatting editor could not start. Your text is preserved below. Reload before editing formatted content.';
        textarea.readOnly = originalFormat === 'html';
        console.error('Reading editor initialization failed', error);
        return null;
    }
    const buttons = new Map();
    function updateButtons() {
        for (const [name, button] of buttons) {
            if (name === 'undo' || name === 'redo') {
                button.disabled = !editor.can()[name]();
            } else {
                const active = name === 'h2' || name === 'h3'
                    ? editor.isActive('heading', { level: name === 'h2' ? 2 : 3 }) : editor.isActive(name);
                button.setAttribute('aria-pressed', String(active));
            }
        }
    }
    for (const [name, label, title, command, options] of controls) {
        const button = element('button', { type: 'button', 'aria-label': title, title, 'data-editor-command': name }, label);
        if (!['undo', 'redo'].includes(name)) button.setAttribute('aria-pressed', 'false');
        button.addEventListener('mousedown', event => event.preventDefault());
        button.addEventListener('click', () => {
            if (name === 'link') {
                linkSelection = { from: editor.state.selection.from, to: editor.state.selection.to };
                linkInput.value = editor.getAttributes('link').href || '';
                linkError.textContent = '';
                linkPanel.hidden = false;
                linkInput.focus();
            } else {
                editor.chain().focus()[command](options).run();
                updateButtons();
            }
        });
        buttons.set(name, button);
        toolbar.append(button);
    }
    toolbar.addEventListener('keydown', event => {
        const available = [...toolbar.querySelectorAll('button:not(:disabled)')];
        const index = available.indexOf(document.activeElement);
        if (index < 0) return;
        let next;
        if (event.key === 'ArrowRight') next = (index + 1) % available.length;
        if (event.key === 'ArrowLeft') next = (index + available.length - 1) % available.length;
        if (event.key === 'Home') next = 0;
        if (event.key === 'End') next = available.length - 1;
        if (next !== undefined) { event.preventDefault(); available[next].focus(); }
        if (event.key === 'Escape') editor.commands.focus();
    });
    function closeLink() { linkPanel.hidden = true; editor.commands.focus(); }
    function applyLink() {
        const value = linkInput.value.trim();
        if (value && !allowedReadingLink(value)) {
            linkError.textContent = 'Use an explicit http://, https:// or mailto: address. Protected uploads belong in a file section.';
            return;
        }
        const chain = editor.chain().focus().setTextSelection(linkSelection).extendMarkRange('link');
        if (value) chain.setLink({ href: value }).run(); else chain.unsetLink().run();
        closeLink(); updateButtons();
    }
    apply.addEventListener('click', applyLink);
    remove.addEventListener('click', () => { linkInput.value = ''; applyLink(); });
    cancel.addEventListener('click', closeLink);
    linkPanel.addEventListener('keydown', event => {
        if (event.key === 'Enter') { event.preventDefault(); applyLink(); }
        if (event.key === 'Escape') { event.preventDefault(); closeLink(); }
    });
    textarea.hidden = true;
    sync(); updateButtons();
    return {
        sync,
        label: label => editor.view.dom.setAttribute('aria-label', label),
        setEnabled: enabled => editor.setEditable(enabled, false),
        destroy() {
            sync(); editor.destroy(); host.replaceChildren(); host.hidden = true; textarea.hidden = false;
            // A Livewire history snapshot must use the current submitted body, not stale initial HTML.
            delete textarea.dataset.editorHtml;
        },
    };
}
