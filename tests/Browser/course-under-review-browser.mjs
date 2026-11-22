import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { createReadingBrowser } from './support/reading-runtime.mjs';

const app = await createReadingBrowser();
const { ev, goto, waitFor, login, command, state, runtime, screenshot } = app;
const results = [];
const pass = name => { results.push({ name, passed: true }); console.log('PASS ' + name); };
const page = (url, options = {}) => ev(`fetch(${JSON.stringify(url)},${JSON.stringify(options)}).then(async r=>({status:r.status,url:r.url,body:await r.text()}))`);
const token = () => ev('document.querySelector("meta[name=csrf-token]")?.content || document.querySelector("input[name=_token]")?.value');
const write = async (url, method, data = {}) => page(url, { method, headers: { 'X-CSRF-TOKEN': await token(), 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'text/html' }, body: new URLSearchParams(data).toString() });
try {
    command('review-fixtures');
    const fixtures = JSON.parse(fs.readFileSync(path.join(runtime, 'fixtures.json'), 'utf8'));
    Object.assign(app.fixtures.users, fixtures.users);
    const f = fixtures.review;
    const courseState = () => state().courses.find(c => c.id === f.course);
    const records = () => { const s = state(); return Object.fromEntries(['enrollments', 'lesson_progress', 'quiz_results', 'submissions', 'assignments', 'lessons', 'lesson_sections'].map(k => [k, s[k]])); };
    const pending = async role => {
        await login(role); await goto(`/courses/${f.course}/edit`);
        const response = await write(`/courses/${f.course}`, 'POST', { _method: 'PUT', category_id: fixtures.category, title: courseState().title, description: courseState().description, difficulty_level: 'beginner', price: '0', status: 'pending' });
        assert.equal(response.status, 200); assert.equal(courseState().status, 'pending');
    };
    await login('teacher'); await goto(`/teacher/courses/${f.course}/lessons`);
    assert.equal((await write(`/teacher/courses/${f.course}/submit`, 'POST')).status, 200); assert.equal(courseState().status, 'pending');
    await login('admin'); await goto('/courses');
    assert.equal((await write(`/courses/${f.course}/approve`, 'POST')).status, 200); assert.equal(courseState().status, 'published');
    await login('student'); await goto(`/marketplace/${f.course}`);
    assert.equal((await write(`/marketplace/${f.course}/enroll`, 'POST')).status, 200);
    assert.ok(state().enrollments.some(e => e.student_id === fixtures.users.student.id && e.course_id === f.course && e.status === 'active'));
    await goto(`/lesson/${f.reading}`); await waitFor('document.body.innerText.includes("Confidential synthetic reading")');
    assert.equal((await write(`/lesson/${f.reading}/complete`, 'POST')).status, 200);
    assert.equal((await page(`/quiz/${f.quiz}/take`)).status, 200);
    assert.equal((await page(`/student/assignments/${f.activity}`)).status, 200);
    const before = records();
    pass('Teacher submission, scoped Admin approval, real Student enrollment and learning progress');

    await login('foreignAdmin'); await goto('/courses');
    const denied = await write(`/courses/${f.course}`, 'POST', { _method: 'PUT', status: 'pending' });
    assert.equal(denied.status, 403); assert.equal(courseState().status, 'published');
    await pending('admin');
    pass('Other-department Admin denied; assigned Department Admin can return course to Pending');

    for (const role of ['student', 'completed']) {
        await login(role);
        for (const url of ['/my-courses', '/student-dashboard']) {
            await goto(url); await waitFor('document.body.innerText.includes("Course Under Review")');
            assert.ok(await ev(`(()=>{const card=[...document.querySelectorAll('article')].find(e=>e.innerText.includes(${JSON.stringify(courseState().title)}));return !!card && card.innerText.includes('Course Under Review') && ![...card.querySelectorAll('a')].some(a=>new URL(a.href).pathname === '/learn/${f.course}');})()`));
        }
        for (const url of [`/learn/${f.course}`, `/lesson/${f.reading}`]) {
            await goto(url); await waitFor('location.pathname === "/my-courses"');
            assert.ok(await ev('document.body.innerText.includes("Course Under Review")'));
        }
        await screenshot('pending-' + role);
    }
    assert.deepEqual(records(), before);
    pass('Active/completed cards retain enrollment and progress; stale HTML links safely show exact review note');

    await login('student'); await goto('/my-courses');
    for (const url of [`/quiz/${f.quiz}/take`, `/files/lesson-sections/${f.sections.image}`, `/files/lesson-sections/${f.sections.file}`]) assert.equal((await page(url)).status, 403);
    assert.equal((await page(`/student/assignments/${f.activity}`)).status, 404);
    assert.equal((await page(`/learn/${f.course}`, {headers:{Accept:'application/json'}})).status, 404);
    assert.equal((await write(`/quiz/${f.quiz}/submit`, 'POST', {['question_' + f.question]: 'A'})).status, 403);
    assert.equal((await write(`/lesson/${f.reading}/complete`, 'POST')).status, 404);
    assert.equal((await write(`/lesson/${f.activityLesson}/complete`, 'POST')).status, 404);
    const csrf = await token();
    const rejected = await ev(`(()=>{const d=new FormData();d.set('answer_text','Stale rejected answer');d.set('submission_file',new File(['synthetic rejected upload'],'new.txt',{type:'text/plain'}));return fetch('/student/assignments/${f.activity}/submit',{method:'POST',headers:{'X-CSRF-TOKEN':${JSON.stringify(csrf)}},body:d}).then(r=>r.status);})()`);
    assert.equal(rejected, 404); assert.deepEqual(records(), before);
    await login('completed'); await goto('/my-courses'); assert.equal((await page(`/files/submissions/${f.submission}`)).status, 200);
    pass('Previously opened forms, Quiz/Activity writes and teacher media denied; own submitted attachment remains accessible');

    for (const role of ['outsider', 'cancelled']) {
        await login(role); await goto('/my-courses');
        for (const url of [`/learn/${f.course}`, `/lesson/${f.reading}`]) {
            const r = await page(url, {headers:{Accept:'text/html'}}); assert.equal(r.status, 404); assert.ok(!r.body.includes('Confidential synthetic reading'));
        }
        assert.equal((await page(`/files/lesson-sections/${f.sections.file}`)).status, 403);
        assert.equal((await page(`/files/submissions/${f.submission}`)).status, 403);
    }
    pass('Unenrolled and cancelled students receive no content or privileged review redirect');

    await login('edp'); await goto('/courses'); assert.equal((await write(`/courses/${f.course}/approve`, 'POST')).status, 200);
    assert.equal(courseState().status, 'published'); assert.deepEqual(records(), before);
    for (const role of ['student', 'completed']) {
        await login(role); await goto('/my-courses');
        assert.ok(await ev(`[...document.querySelectorAll('a')].some(a=>new URL(a.href).pathname === '/learn/${f.course}')`));
        await goto(`/lesson/${f.reading}`); await waitFor('document.body.innerText.includes("Confidential synthetic reading")');
        assert.equal((await page(`/files/lesson-sections/${f.sections.file}`)).status, 200);
    }
    await login('cancelled'); assert.equal((await page(`/learn/${f.course}`)).status, 403);
    assert.deepEqual(records(), before);
    assert.deepEqual(app.exceptions, []); assert.ok(!app.responses.some(r => r.status >= 500));
    pass('Super Admin reapproval restores prior learning; records, grades, files and cancelled restriction preserved; no HTTP 500');
} catch(error) {
    results.push({ name: 'Course Under Review browser regression', passed: false, error: error.stack });
    console.error(error); process.exitCode = 1;
    try { await screenshot('failure'); fs.writeFileSync(path.join(runtime,'failure-page.txt'), await ev('document.body.innerText')); } catch {}
} finally {
    fs.writeFileSync(path.join(runtime, 'course-under-review-results.json'), JSON.stringify({results, passed:results.filter(r=>r.passed).length, failed:results.filter(r=>!r.passed).length, runtimeExceptions:app.exceptions},null,2));
    console.log('Disposable browser artifacts: ' + runtime); await app.close();
}
