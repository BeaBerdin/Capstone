import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { createReadingBrowser } from './support/reading-runtime.mjs';
import { testPlainMultipartPreservation } from './reading-plain-multipart-browser.mjs';

const app = await createReadingBrowser();
const { fixtures, runtime, state, command, ev, goto, waitFor, click, fill, login, selectAll, paste, upload, send, screenshot } = app;
const results = [];
const pass = name => { results.push({ name, passed: true }); console.log('PASS ' + name); };
const escape = text => text.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
const paragraphs = text => text.split('\n\n').map(p => '<p>' + p.split('\n').map(escape).join('<br>') + '</p>').join('');
const fetchPage = url => ev(`fetch(${JSON.stringify(url)}).then(async r=>({status:r.status,url:r.url,body:await r.text(),type:r.headers.get('content-type')}))`);
try {
    await login('teacher');
    await goto('/teacher/courses/create');
    await waitFor('document.querySelectorAll("input[name=template_id]").length === 4');
    const template = fixtures.templates['Short Course (Micro-credential)'];
    await click(`label[for="course-template-${template}"]`);
    await fill('#title', 'Browser template course ' + fixtures.id);
    await fill('#description', 'Synthetic Reading integration course.');
    await fill('#category_id', String(fixtures.category));
    await fill('input[name=price]', '0');
    await fill('input[name=estimated_hours]', '1');
    const invalid = await ev('[...document.querySelectorAll("#createCourseForm :invalid")].map(e=>e.name)');
    assert.deepEqual(invalid, []);
    await click('#createCourseForm button[type=submit]');
    await waitFor('location.pathname !== "/teacher/courses/create"');
    const course = state().courses.find(c => c.title === 'Browser template course ' + fixtures.id);
    assert.ok(course); assert.equal(course.template_id, template); assert.equal(course.status, 'draft');
    const lessons = state().lessons.filter(l => l.course_id === course.id).sort((a, b) => a.lesson_order - b.lesson_order);
    assert.equal(lessons.length, 3); assert.deepEqual(lessons.map(l => l.lesson_order), [1, 2, 3]);
    assert.ok(lessons.every(l => !l.is_published && l.lesson_type === 'text'));
    const first = lessons[0];
    pass('Teacher selects the existing template and creates three ordered unpublished Reading lessons');

    await goto(`/teacher/lessons/${first.id}/edit`);
    await waitFor('document.querySelector(".tiptap")');
    assert.equal(await ev('document.querySelector("[data-section-body]").value'), first.content);
    await click('#editLessonForm button[type=submit]');
    await waitFor(`location.pathname === '/teacher/courses/${course.id}/lessons'`);
    assert.equal(state().lessons.find(l => l.id === first.id).content, first.content);
    const section = state().lesson_sections.find(s => s.lesson_id === first.id);
    assert.equal(section.body_format, 'plain'); assert.equal(section.body, first.content);
    pass('First builder conversion preserves the generated template guidance byte for byte');

    await goto(`/teacher/lessons/${first.id}/edit`); await waitFor('document.querySelector(".tiptap")');
    const original = await ev('document.querySelector("[data-section-body]").value');
    await selectAll('.tiptap'); await click('[data-editor-command=bold]');
    assert.equal(await ev('document.querySelector("[data-section-body_format]").value'), 'html');
    await click('[data-editor-command=undo]');
    assert.equal(await ev('document.querySelector("[data-section-body]").value'), original);
    assert.equal(await ev('document.querySelector("[data-section-body_format]").value'), 'plain');
    await click('[data-editor-command=redo]');
    assert.equal(await ev('document.querySelector("[data-section-body_format]").value'), 'html');
    pass('Formatting, active states, undo and redo retain plain text until an intentional edit');

    await selectAll('.tiptap');
    const html = '<h2>Template learning</h2>' + paragraphs(first.content)
        + '<p><strong>Bold</strong> <em>Italic</em> <u>Underline</u> <code>x &lt; y</code></p><h3>Practice</h3><ul><li>First bullet</li></ul><ol><li>First step</li></ol><pre><code>&lt;script&gt;literal code&lt;/script&gt;</code></pre>';
    await paste('.tiptap', html);
    const commands = await ev('[...document.querySelectorAll("[data-editor-command]")].map(e=>e.dataset.editorCommand)');
    assert.deepEqual(commands, ['bold', 'italic', 'underline', 'h2', 'h3', 'bulletList', 'orderedList', 'link', 'code', 'codeBlock', 'undo', 'redo']);
    await click('[data-add-section=image]');
    const imagePath = path.join(runtime, 'diagram.png');
    const png = await ev('(()=>{const c=document.createElement("canvas");c.width=320;c.height=160;const x=c.getContext("2d");x.fillStyle="#ede9fe";x.fillRect(0,0,320,160);x.fillStyle="#6d28d9";x.font="18px sans-serif";x.fillText("Synthetic test diagram",30,85);return c.toDataURL("image/png").split(",")[1];})()');
    fs.writeFileSync(imagePath, Buffer.from(png, 'base64'));
    await upload('[data-section-card]:nth-child(2) input[type=file]', imagePath);
    await fill('[data-section-card]:nth-child(2) [data-section-caption]', 'Synthetic diagram');
    await click('[data-add-section=file]');
    const pdfPath = path.join(runtime, 'guide.pdf'); fs.writeFileSync(pdfPath, '%PDF-1.4\n% Synthetic browser test PDF\n1 0 obj << /Type /Catalog >> endobj\n%%EOF');
    await upload('[data-section-card]:nth-child(3) input[type=file]', pdfPath);
    await fill('[data-section-card]:nth-child(3) [data-section-caption]', 'Private guide');
    await click('[data-section-card]:nth-child(3) [data-move-section=up]');
    await click('[data-section-card]:nth-child(2) [data-move-section=down]');
    await click('[data-add-section=text]'); await click('[data-section-card]:nth-child(4) [data-remove-section]');
    assert.equal(await ev('document.querySelectorAll(".tiptap").length'), 1);
    assert.equal(await ev('document.querySelectorAll("[data-section-list] > [data-section-card]").length'), 3);
    await screenshot('teacher-rich-editor');
    await click('#editLessonForm button[type=submit]'); await waitFor(`location.pathname === '/teacher/courses/${course.id}/lessons'`);
    let saved = state(); let sections = saved.lesson_sections.filter(s => s.lesson_id === first.id);
    assert.deepEqual(sections.map(s => s.type), ['text', 'image', 'file']);
    assert.equal(sections[0].id, section.id); assert.equal(sections[0].body_format, 'html');
    assert.ok(saved.lessons.find(l => l.id === first.id).content.includes(first.content));
    for (const tag of ['h2', 'h3', 'strong', 'em', 'u', 'ul', 'ol', 'code', 'pre']) assert.ok(sections[0].body.includes('<' + tag));
    pass('Rich editor saves formatting, section identity, ordering, private image/PDF and all original guidance');

    await goto(`/teacher/lessons/${first.id}/edit`); await waitFor('document.querySelector(".tiptap")');
    assert.ok(await ev('document.querySelector(".tiptap").innerHTML.includes("<strong>Bold</strong>")'));
    await selectAll('.tiptap');
    await paste('.tiptap', '<p onclick="window.pasteAttack=true">Safe paste<img src="https://example.test/tracker" onerror="window.pasteAttack=true"></p><script>window.pasteAttack=true</script>');
    assert.ok(!await ev('/onclick|onerror|<img|<script/.test(document.querySelector("[data-section-body]").value)'));
    await click('[data-editor-command=undo]');
    await selectAll('.tiptap'); await click('[data-editor-command=bold]');
    await ev('window.confirmCalls=0;window.confirm=()=>{window.confirmCalls++;return false;}');
    const leaving = await ev('(()=>{const e=new CustomEvent("livewire:navigate",{cancelable:true,detail:{history:false,cached:false,url:new URL(location.href)}});return document.dispatchEvent(e);})()');
    assert.equal(leaving, false); assert.equal(await ev('window.confirmCalls'), 1);
    for (let i = 0; i < 3; i++) await ev('document.dispatchEvent(new CustomEvent("livewire:navigated"))');
    assert.equal(await ev('document.querySelectorAll(".tiptap").length'), 1);
    assert.equal(await ev('(()=>{const e=new Event("beforeunload",{cancelable:true});window.dispatchEvent(e);return e.defaultPrevented;})()'), true);
    const unsavedBody = await ev('document.querySelector("[data-section-body]").value');
    await upload('[data-section-card]:nth-child(3) input[type=file]', pdfPath);
    await ev('(()=>{const a=document.createElement("a");a.id="browser-history-link";a.href="/teacher/courses/create";a.setAttribute("wire:navigate","");a.textContent="Browser navigation probe";document.body.append(a);})()');
    await click('#browser-history-link');
    assert.equal(await ev('location.pathname'), `/teacher/lessons/${first.id}/edit`);
    assert.equal(await ev('document.querySelector("[data-section-body]").value'), unsavedBody);
    assert.equal(await ev('document.querySelector("[data-section-card]:nth-child(3) input[type=file]").files.length'), 1);
    await ev('window.confirm=()=>true');
    await click('#browser-history-link');
    await waitFor('location.pathname === "/teacher/courses/create"');
    await ev('history.back()');
    await waitFor(`location.pathname === '/teacher/lessons/${first.id}/edit' && document.querySelectorAll('.tiptap').length === 1`);
    assert.equal(await ev('document.querySelector("[data-section-body]").value'), unsavedBody);
    assert.equal(await ev('document.querySelector("[data-section-builder]").dataset.readingDirty'), 'true');
    assert.equal(await ev('document.querySelector("[data-upload-reselect]").hidden'), false);
    assert.equal(await ev('document.querySelector("[data-section-card]:nth-child(3) input[type=file]").files.length'), 0);
    pass('Real Livewire cancellation preserves edits/uploads; browser Back restores text and requests upload reselection');
    // Discard this deliberate unsaved test edit by reloading the saved lesson.
    await goto(`/teacher/lessons/${first.id}/edit`); await waitFor('document.querySelector(".tiptap")');
    const attackResponse = await ev(`(async()=>{const f=new FormData(document.querySelector('#editLessonForm'));f.set('sections[0][body]',f.get('sections[0][body]')+'<p onclick="evil()">Sanitized marker</p><script>evil()</script><img src="https://example.test/a"><a href="javascript:evil()">bad</a>');const r=await fetch('/teacher/lessons/${first.id}',{method:'POST',body:f});return r.status;})()`);
    assert.equal(attackResponse, 200);
    assert.ok(!/<script|<img|onclick|javascript:/.test(state().lesson_sections.find(s => s.id === section.id).body));
    pass('Paste filtering, server sanitizer, lifecycle initialization, cancelled navigation and unload warnings');

    await testPlainMultipartPreservation({ cases: fixtures.cases, fixtures, goto, waitFor, ev, click, state, out: runtime, pass });

    await goto(`/teacher/lessons/${first.id}/edit`); await waitFor('document.querySelector(".tiptap")');
    await click('input[name=is_published]'); // The generated outline is still a draft.
    await click('#editLessonForm button[type=submit]'); await waitFor(`location.pathname === '/teacher/courses/${course.id}/lessons'`);
    for (const lesson of lessons.slice(1)) {
        await goto(`/teacher/lessons/${lesson.id}/edit`); await waitFor('document.querySelector(".tiptap")');
        await click('input[name=is_published]'); await click('#editLessonForm button[type=submit]');
        await waitFor(`location.pathname === '/teacher/courses/${course.id}/lessons'`);
        assert.equal(state().lessons.find(l => l.id === lesson.id).content, lesson.content);
    }
    command('enroll', course.id);
    await login('student'); assert.equal((await fetchPage(`/lesson/${first.id}`)).status, 404);
    await login('teacher'); await goto(`/teacher/courses/${course.id}/lessons`);
    const submit = await ev(`fetch('/teacher/courses/${course.id}/submit',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('form input[name=_token]').value}}).then(r=>r.status)`);
    assert.equal(submit, 200); assert.equal(state().courses.find(c => c.id === course.id).status, 'pending');
    await login('edp'); await goto('/courses');
    assert.equal(await ev(`fetch('/courses/${course.id}/approve',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('form input[name=_token]').value}}).then(r=>r.status)`), 200);
    assert.equal(state().courses.find(c => c.id === course.id).status, 'published');
    pass('Teacher submission and real Super Admin approval retain the course publication gate');

    await login('student'); await goto(`/lesson/${first.id}`); await waitFor('document.querySelector("[data-reading-material]")');
    assert.ok(await ev('document.querySelector(".pw-reading-rich strong")?.textContent === "Bold"'));
    assert.equal(await ev('document.querySelectorAll("a[data-next-lesson]").length'), 1);
    assert.ok(!await ev('/\u00e2\u20ac\u00ba|\u00e2\u0153\u201c|\u00e2\u2013/.test(document.body.textContent)'));
    const image = sections.find(s => s.type === 'image'), file = sections.find(s => s.type === 'file');
    assert.equal((await fetchPage(`/files/lesson-sections/${image.id}`)).status, 200);
    const pdf = await fetchPage(`/files/lesson-sections/${file.id}`); assert.equal(pdf.status, 200); assert.ok(pdf.body.startsWith('%PDF-1.4'));
    assert.equal(state().lesson_progress.find(p => p.lesson_id === first.id && p.student_id === fixtures.users.student.id).status, 'in_progress');
    await screenshot('student-textbook');
    await send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
    assert.ok(await ev('document.documentElement.scrollWidth <= innerWidth')); await screenshot('student-mobile');
    await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1100, deviceScaleFactor: 1, mobile: false });
    await click(`form[action$="/lesson/${first.id}/complete"] button[type=submit]`);
    await waitFor('document.body.innerText.includes("Completed")');
    assert.equal(state().lesson_progress.find(p => p.lesson_id === first.id && p.student_id === fixtures.users.student.id).status, 'completed');
    assert.equal(await ev('document.querySelectorAll("a[data-next-lesson]").length'), 1);
    pass('Student textbook display, corrected Unicode, private files, mobile layout and explicit completion');

    await goto(`/quiz/${fixtures.quiz.id}/take`); await waitFor(`document.querySelector('input[name=question_${fixtures.quiz.question}]')`);
    await click(`input[name=question_${fixtures.quiz.question}][value=A]`); await click('form[action$="/submit"] button[type=submit]');
    await waitFor('!location.pathname.endsWith("/take")');
    assert.ok(state().quiz_results.some(r => r.quiz_id === fixtures.quiz.id && r.remarks === 'passed'));
    await goto(`/student/assignments/${fixtures.activity.id}`); await waitFor('document.querySelector("textarea[name=answer_text]")');
    await fill('textarea[name=answer_text]', 'A synthetic browser Activity answer.'); await click('form[action$="/submit"] button[type=submit]');
    await waitFor('document.body.innerText.includes("submitted") || document.body.innerText.includes("Submitted")');
    assert.ok(state().submissions.some(s => s.assignment_id === fixtures.activity.id));
    assert.deepEqual(state().lesson_progress.find(p => p.id === fixtures.protectedProgress.id), fixtures.protectedProgress);
    pass('Quiz grading, Activity submission, and pre-existing disposable progress remain intact');

    await login('outsider'); assert.equal((await fetchPage(`/files/lesson-sections/${file.id}`)).status, 403);
    assert.equal((await fetchPage(`/teacher/lessons/${first.id}/edit`)).status, 403);
    await send('Network.clearBrowserCookies'); assert.ok((await fetchPage(`/files/lesson-sections/${file.id}`)).url.endsWith('/login'));
    assert.deepEqual(app.exceptions, []); assert.ok(!app.responses.some(r => r.status >= 500));
    pass('Unenrolled and guest private-file access remains denied');
} catch (error) {
    results.push({ name: 'browser regression', passed: false, error: error.stack });
    try { await screenshot('failure'); fs.writeFileSync(path.join(runtime, 'failure-page.txt'), await ev('document.body.innerText')); } catch {}
    console.error(error); process.exitCode = 1;
} finally {
    fs.writeFileSync(path.join(runtime, 'results.json'), JSON.stringify({ results, passed: results.filter(r => r.passed).length, failed: results.filter(r => !r.passed).length, runtimeExceptions: app.exceptions }, null, 2));
    console.log('Disposable browser artifacts: ' + runtime);
    await app.close();
}
