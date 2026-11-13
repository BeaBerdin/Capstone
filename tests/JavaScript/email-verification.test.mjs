import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/views/pages/auth/partials/verification-poll.blade.php', import.meta.url), 'utf8')
    .replace(/^<script>\s*/, '')
    .replace(/\s*<\/script>\s*$/, '');

class Events {
    listeners = new Map();

    addEventListener(name, callback) {
        if (!this.listeners.has(name)) this.listeners.set(name, new Set());
        this.listeners.get(name).add(callback);
    }

    removeEventListener(name, callback) {
        this.listeners.get(name)?.delete(callback);
    }

    emit(name) {
        for (const callback of [...(this.listeners.get(name) ?? [])]) callback();
    }
}

const response = (verified, status = 200, redirected = false) => ({
    ok: status >= 200 && status < 300,
    status,
    redirected,
    json: async () => ({ verified }),
});
const settle = () => new Promise(setImmediate);

function browser(fetchResponse, hidden = false) {
    const calls = [];
    const redirects = [];
    const timers = new Map();
    const document = new Events();
    document.hidden = hidden;
    document.querySelector = () => ({ dataset: {
        verificationStatusUrl: '/email/verification-status',
        verificationDashboardUrl: '/dashboard',
        verificationLoginUrl: '/login',
    } });
    const window = new Events();
    window.location = { replace: (url) => redirects.push(url) };
    let nextTimer = 0;
    window.setInterval = (callback, delay) => {
        timers.set(++nextTimer, { callback, delay });
        return nextTimer;
    };
    window.clearInterval = (id) => timers.delete(id);

    vm.runInNewContext(source, {
        window, document, AbortController,
        fetch: async (url, options) => {
            calls.push({ url, options });
            return fetchResponse(calls.length);
        },
    });

    return { window, document, calls, redirects, timers };
}

test('polling redirects the original tab once verification completes elsewhere', async () => {
    const page = browser((number) => response(number > 1));
    await settle();
    assert.equal(page.calls.length, 1);
    assert.equal(page.calls[0].url, '/email/verification-status');
    assert.equal(page.calls[0].options.credentials, 'same-origin');
    assert.equal(page.calls[0].options.cache, 'no-store');
    assert.equal([...page.timers.values()][0].delay, 5000);
    assert.deepEqual(page.redirects, []);

    [...page.timers.values()][0].callback();
    await settle();

    assert.deepEqual(page.redirects, ['/dashboard']);
    assert.equal(page.timers.size, 0);
    page.window.emit('focus');
    assert.equal(page.calls.length, 2);
});

test('a hidden tab checks immediately when it becomes visible', async () => {
    const page = browser(() => response(true), true);
    await settle();
    assert.equal(page.calls.length, 0);

    page.document.hidden = false;
    page.document.emit('visibilitychange');
    await settle();

    assert.deepEqual(page.redirects, ['/dashboard']);
});

test('focus retries after an offline check without overlapping pending requests', async () => {
    const page = browser((number) => {
        if (number === 1) throw new Error('Offline');
        return response(true);
    });
    await settle();
    assert.deepEqual(page.redirects, []);

    page.window.emit('focus');
    page.window.emit('focus');
    await settle();

    assert.equal(page.calls.length, 2);
    assert.deepEqual(page.redirects, ['/dashboard']);
});

test('expired authentication returns to login rather than opening a dashboard', async () => {
    for (const result of [response(false, 401), response(false, 419), response(false, 200, true)]) {
        const page = browser(() => result);
        await settle();
        assert.deepEqual(page.redirects, ['/login']);
        assert.equal(page.timers.size, 0);
    }
});

test('page hide aborts an in-flight check and page show resumes it', async () => {
    let finish;
    const page = browser((number) => number === 1
        ? new Promise((resolve) => { finish = resolve; })
        : response(true));
    page.window.emit('pagehide');
    assert.equal(page.calls[0].options.signal.aborted, true);
    assert.equal(page.timers.size, 0);
    finish(response(true));
    await settle();
    assert.deepEqual(page.redirects, []);

    page.window.emit('pageshow');
    await settle();
    assert.deepEqual(page.redirects, ['/dashboard']);
});

test('leaving the verification view cancels its polling and event listeners', async () => {
    const page = browser(() => response(false));
    await settle();
    page.document.emit('livewire:navigating');
    page.window.emit('focus');
    page.window.emit('pageshow');
    page.document.emit('visibilitychange');
    await settle();

    assert.equal(page.timers.size, 0);
    assert.equal(page.calls.length, 1);
    assert.deepEqual(page.redirects, []);
});
