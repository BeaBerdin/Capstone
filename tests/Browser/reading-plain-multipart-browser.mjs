// Real Chromium + PHP multipart regression. Called from the isolated authenticated harness.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

export async function testPlainMultipartPreservation({ cases, fixtures, goto, waitFor, ev, click, state, out, pass }) {
    const report = [];
    for (const item of cases) {
        await goto(`/teacher/lessons/${item.lesson}/edit`);
        await waitFor('document.querySelector(".tiptap")');
        const initial = await ev('document.querySelector("[data-section-body]").value');
        assert.equal(initial, item.original.replace(/\r\n|\r/g, '\n'), item.name+' initial textarea');
        await ev('document.querySelector(".tiptap").focus()');
        if (item.unchanged) {
            // This invokes native form encoding, not a fabricated request parameter array.
            await click('#editLessonForm button[type=submit]');
            await waitFor(`location.pathname === '/teacher/courses/${fixtures.courses.sandbox}/lessons'`);
        } else {
            // A plain-text client intentionally changes whitespace. Fetch/FormData still uses
            // the browser's real multipart encoder and the real server's PHP request parser.
            const response = await ev(`(async()=>{const f=new FormData(document.querySelector('#editLessonForm'));f.set('sections[0][body]',${JSON.stringify(item.edited)});const r=await fetch('/teacher/lessons/${item.lesson}',{method:'POST',body:f});return{status:r.status,url:r.url};})()`);
            assert.equal(response.status, 200, item.name);
            assert.ok(response.url.endsWith(`/teacher/courses/${fixtures.courses.sandbox}/lessons`), item.name);
        }
        const snapshot = state();
        const stored = snapshot.lesson_sections.find(section => section.id === item.section);
        const mirror = snapshot.lessons.find(lesson => lesson.id === item.lesson).content;
        const normalize = text => text.replace(/\r\n|\r|\n/g, '\r\n');
        const expected = item.unchanged ? item.original : normalize(item.edited);
        assert.equal(stored.body, expected, item.name+' stored bytes');
        assert.equal(stored.body_format, 'plain');
        assert.equal(mirror, expected, item.name+' AI mirror');
        const captures = fs.readFileSync(path.join(out, 'multipart-wire.jsonl'), 'utf8').trim().split('\n').map(line => JSON.parse(line));
        const capture = captures.filter(row => row.sectionId === item.section).at(-1);
        assert.ok(capture.contentType.startsWith('multipart/form-data; boundary='), item.name+' actual HTTP encoding');
        assert.equal(capture.body, normalize(item.unchanged ? item.original : item.edited), item.name+' PHP-parsed wire bytes');
        await goto(`/teacher/lessons/${item.lesson}/edit`);
        await waitFor('document.querySelector(".tiptap")');
        assert.equal(await ev('document.querySelector("[data-section-body]").value'), expected.replace(/\r\n|\r/g, '\n'));
        report.push({ name: item.name, passed: true, encoding: capture.contentType, storedOriginalBytes: item.unchanged });
    }
    fs.writeFileSync(path.join(out, 'plain-multipart-results.json'), JSON.stringify(report, null, 2));
    pass(`Real multipart preservation: ${report.length} LF/CRLF/Unicode/blank-line and intentional whitespace/text cases`);
}
