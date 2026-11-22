import test from 'node:test';
import assert from 'node:assert/strict';
import { getSchema } from '@tiptap/core';
import { plainDocument, allowedReadingLink, readingExtensions } from '../../resources/js/reading-editor.js';

test('legacy text becomes literal JSON text nodes, not HTML', () => {
    const original = '  <script>alert(1)</script>\r\n\r\n<h2>Literal</h2> & value  ';
    const doc = plainDocument(original);
    const schema = getSchema(readingExtensions());
    const parsed = schema.nodeFromJSON(doc);
    assert.equal(parsed.textBetween(0, parsed.content.size, '\n\n', '\n'), original.replaceAll('\r\n', '\n'));
    assert.equal(doc.content[0].content[0].text, '  <script>alert(1)</script>');
    assert.deepEqual(plainDocument(''), { type: 'doc', content: [{ type: 'paragraph' }] });
});

test('schema exposes essential formatting without embeds or unsupported formatting', () => {
    const schema = getSchema(readingExtensions());
    assert.deepEqual(Object.keys(schema.marks).sort(), ['bold', 'code', 'italic', 'link', 'underline']);
    for (const name of ['image', 'iframe', 'table', 'blockquote', 'horizontalRule']) assert.equal(schema.nodes[name], undefined);
    assert.deepEqual(schema.nodes.heading.spec.parseDOM.map(rule => rule.tag), ['h2', 'h3']);
    for (const name of ['paragraph', 'bulletList', 'orderedList', 'listItem', 'codeBlock', 'hardBreak']) assert.ok(schema.nodes[name]);
});

test('editor link policy permits only explicit safe links, excluding protected files', () => {
    for (const link of ['https://example.test/topic', 'http://example.test/topic', 'mailto:teacher@example.test']) assert.equal(allowedReadingLink(link), true, link);
    for (const link of ['javascript:alert(1)', 'data:image/png,AAAA', '/storage/a.pdf', '//example.test', 'https://example.test/%73torage/a.pdf', 'https://example.test/lesson-files/a.pdf', 'https://example.test/assignment-submissions/a.pdf', 'https://example.test/payment-proofs/a.pdf', 'https://example.test/\nattack', 'file:///a', '']) assert.equal(allowedReadingLink(link), false, link);
});
