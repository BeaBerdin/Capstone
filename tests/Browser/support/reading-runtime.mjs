import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import http from 'node:http';
import net from 'node:net';
import { randomBytes } from 'node:crypto';
import { spawn, execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

export const repositoryRoot = fileURLToPath(new URL('../../../', import.meta.url));
const phpHarness = fileURLToPath(new URL('./reading-runtime.php', import.meta.url));
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

export async function createReadingBrowser() {
    assert.ok(Number(process.versions.node.split('.')[0]) >= 22, 'Portable browser tests require Node.js 22 or newer.');
    const executable = process.env.PATHWISE_BROWSER_EXECUTABLE;
    if (!executable || !fs.statSync(executable, { throwIfNoEntry: false })?.isFile()) {
        throw new Error('Set PATHWISE_BROWSER_EXECUTABLE to an installed Chromium-family browser; no browser is downloaded.');
    }
    assert.ok(fs.existsSync(path.join(repositoryRoot, 'public/build/manifest.json')), 'Build the frontend before browser tests.');
    const runtime = fs.mkdtempSync(path.join(os.tmpdir(), 'pathwise-reading-browser-'));
    const database = path.join(runtime, 'browser.sqlite');
    fs.writeFileSync(database, '', { flag: 'wx' });
    fs.writeFileSync(path.join(runtime, 'created-new-runtime'), '', { flag: 'wx' });
    for (const directory of ['storage/app/private', 'storage/app/public', 'storage/framework/views', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/logs', 'uploads', 'profile']) fs.mkdirSync(path.join(runtime, directory), { recursive: true });
    const id = randomBytes(8).toString('hex');
    const password = randomBytes(24).toString('base64url') + '!7aA';
    const php = process.env.PATHWISE_PHP_BINARY || 'php';
    const proxy = http.createServer((req, res) => { res.writeHead(403); res.end('Outbound blocked in browser tests'); });
    proxy.on('connect', (req, socket) => socket.end('HTTP/1.1 403 Forbidden\r\nContent-Length: 0\r\n\r\n'));
    await new Promise((resolve, reject) => { proxy.once('error', reject); proxy.listen(0, '127.0.0.1', resolve); });
    const proxyUrl = `http://127.0.0.1:${proxy.address().port}`;
    assert.equal((await fetch(proxyUrl + '/local-safety-check')).status, 403);
    const reservation = net.createServer();
    await new Promise(resolve => reservation.listen(0, '127.0.0.1', resolve));
    const port = reservation.address().port;
    await new Promise(resolve => reservation.close(resolve));
    const base = `http://127.0.0.1:${port}`;
    const env = {
        ...process.env, APP_ENV: 'local', APP_DEBUG: 'false', APP_KEY: 'base64:' + randomBytes(32).toString('base64'), APP_URL: base,
        DB_CONNECTION: 'sqlite', DB_DATABASE: database, DB_URL: '', SESSION_DRIVER: 'file', SESSION_COOKIE: 'pw_browser_' + id,
        CACHE_STORE: 'file', MAIL_MAILER: 'log', QUEUE_CONNECTION: 'sync', BROADCAST_CONNECTION: 'null',
        GEMINI_API_KEY: '', PAYMONGO_SECRET_KEY: '', PAYMONGO_PUBLIC_KEY: '', PAYMONGO_WEBHOOK_SECRET: '', AWS_ACCESS_KEY_ID: '', AWS_SECRET_ACCESS_KEY: '', POSTMARK_API_KEY: '', RESEND_API_KEY: '',
        NIGHTWATCH_ENABLED: 'false', PULSE_ENABLED: 'false', TELESCOPE_ENABLED: 'false', BCRYPT_ROUNDS: '4',
        PATHWISE_REPO_ROOT: repositoryRoot, PATHWISE_BROWSER_RUNTIME: runtime, PATHWISE_BROWSER_ID: id, PATHWISE_BROWSER_PASSWORD: password,
        TEMP: path.join(runtime, 'uploads'), TMP: path.join(runtime, 'uploads'), HTTP_PROXY: proxyUrl, HTTPS_PROXY: proxyUrl, ALL_PROXY: proxyUrl, NO_PROXY: '127.0.0.1,localhost',
    };
    const flags = [
        '-d', 'memory_limit=512M', '-d', 'upload_max_filesize=25M', '-d', 'post_max_size=32M', '-d', `upload_tmp_dir=${env.TEMP}`,
        '-d', 'allow_url_fopen=0', '-d', 'allow_url_include=0',
        '-d', 'disable_functions=curl_exec,curl_multi_exec,fsockopen,pfsockopen,stream_socket_client,socket_create,mail,exec,shell_exec,system,passthru,proc_open,popen',
    ];
    const command = (action, ...args) => JSON.parse(execFileSync(php, [...flags, phpHarness, action, ...args.map(String)], { cwd: repositoryRoot, env, encoding: 'utf8', maxBuffer: 8 * 1024 * 1024 }));
    let server, browser, ws;
    const exceptions = [], responses = [];
    const close = async () => {
        ws?.close(); browser?.kill(); server?.kill(); proxy.closeAllConnections(); proxy.close();
    };
    try {
        const fixtures = command('init');
        const log = fs.openSync(path.join(runtime, 'server.log'), 'a');
        server = spawn(php, [...flags, '-S', `127.0.0.1:${port}`, '-t', path.join(repositoryRoot, 'public'), phpHarness], { cwd: repositoryRoot, env, windowsHide: true, stdio: ['ignore', log, log] });
        fs.closeSync(log);
        server.on('error', error => fs.appendFileSync(path.join(runtime, 'server.log'), String(error)));
        let ready;
        for (let i = 0; i < 150; i++) {
            if (server.exitCode !== null) throw new Error('Disposable PHP server exited: ' + fs.readFileSync(path.join(runtime, 'server.log'), 'utf8'));
            try {
                ready = await (await fetch(base + '/__browser-safety')).json();
                if (ready.runtimeId === id) break;
            } catch {}
            await sleep(100);
        }
        assert.equal(ready?.runtimeId, id, 'Could not verify the disposable server identity.');
        assert.ok(ready.sqlite && ready.curlDisabled && ready.streamsDisabled && ready.mail === 'log');
        assert.equal(ready.proxy, proxyUrl);
        assert.equal(path.resolve(ready.database), path.resolve(database));
        const browserLog = fs.openSync(path.join(runtime, 'browser.log'), 'a');
        browser = spawn(executable, [
            '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--disable-background-networking', '--disable-component-update', '--disable-sync', '--disable-client-side-phishing-detection', '--safebrowsing-disable-auto-update', '--disable-features=MediaRouter,OptimizationHints,Translate', '--password-store=basic',
            `--user-data-dir=${path.join(runtime, 'profile')}`, '--remote-debugging-port=0', '--remote-debugging-address=127.0.0.1', `--proxy-server=${proxyUrl}`, '--proxy-bypass-list=127.0.0.1', 'about:blank',
        ], { cwd: runtime, windowsHide: true, stdio: ['ignore', 'ignore', browserLog] });
        fs.closeSync(browserLog);
        browser.on('error', error => fs.appendFileSync(path.join(runtime, 'browser.log'), String(error)));
        const portFile = path.join(runtime, 'profile', 'DevToolsActivePort');
        for (let i = 0; i < 150 && !fs.existsSync(portFile); i++) await sleep(100);
        assert.ok(fs.existsSync(portFile), 'Installed browser did not start; inspect the isolated browser.log.');
        const debugPort = fs.readFileSync(portFile, 'utf8').split('\n')[0];
        let target;
        for (let i = 0; i < 100 && !target; i++) {
            try {
                const targets = await (await fetch(`http://127.0.0.1:${debugPort}/json/list`)).json();
                target = targets.find(candidate => candidate.type === 'page' && candidate.webSocketDebuggerUrl);
            } catch {}
            if (!target) await sleep(100);
        }
        assert.ok(target, 'The browser did not expose a page target.');
        ws = new WebSocket(target.webSocketDebuggerUrl);
        await new Promise((resolve, reject) => { ws.onopen = resolve; ws.onerror = reject; });
        let sequence = 0;
        const pending = new Map();
        const send = (method, params = {}) => new Promise((resolve, reject) => {
            const requestId = ++sequence;
            const timer = setTimeout(() => { pending.delete(requestId); reject(new Error('Browser command timed out: ' + method)); }, 20000);
            pending.set(requestId, { resolve, reject, timer }); ws.send(JSON.stringify({ id: requestId, method, params }));
        });
        ws.onmessage = ({ data }) => {
            const message = JSON.parse(data);
            if (message.id) {
                const waiting = pending.get(message.id); pending.delete(message.id);
                if (waiting) { clearTimeout(waiting.timer); if (message.error) waiting.reject(new Error(JSON.stringify(message.error))); else waiting.resolve(message.result); }
            }
            if (message.method === 'Runtime.exceptionThrown') exceptions.push(message.params.exceptionDetails);
            if (message.method === 'Network.responseReceived') responses.push({ url: message.params.response.url, status: message.params.response.status });
            if (message.method === 'Page.javascriptDialogOpening') send('Page.handleJavaScriptDialog', { accept: true }).catch(() => {});
        };
        const ev = async expression => {
            const result = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
            if (result.exceptionDetails) throw new Error(JSON.stringify(result.exceptionDetails));
            return result.result.value;
        };
        const waitFor = async (expression, timeout = 15000) => {
            const start = Date.now();
            while (Date.now() - start < timeout) {
                try { if (await ev(`Boolean(${expression})`)) return; } catch {}
                await sleep(70);
            }
            throw new Error('Browser condition timed out: ' + expression + '; URL: ' + await ev('location.href'));
        };
        const goto = async url => {
            await send('Page.navigate', { url: base + url });
            await waitFor('document.readyState === "complete"'); await sleep(120);
        };
        const click = async selector => { await ev(`document.querySelector(${JSON.stringify(selector)}).click()`); await sleep(80); };
        const fill = async (selector, value) => ev(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});e.value=${JSON.stringify(value)};e.dispatchEvent(new Event('input',{bubbles:true}));e.dispatchEvent(new Event('change',{bubbles:true}));})()`);
        const login = async role => {
            await send('Network.clearBrowserCookies'); await goto('/login');
            await fill('#email', fixtures.users[role].email); await fill('#password', password);
            await click('form[action$="/login"] button[type=submit]');
            await waitFor('location.pathname !== "/login"');
            assert.ok(!await ev('location.pathname.includes("application-status") || location.pathname.includes("email/verify")'));
        };
        const selectAll = async selector => {
            await ev(`document.querySelector(${JSON.stringify(selector)}).focus()`);
            await send('Input.dispatchKeyEvent', { type: 'keyDown', modifiers: process.platform === 'darwin' ? 4 : 2, key: 'a', code: 'KeyA', windowsVirtualKeyCode: 65 });
            await send('Input.dispatchKeyEvent', { type: 'keyUp', modifiers: process.platform === 'darwin' ? 4 : 2, key: 'a', code: 'KeyA', windowsVirtualKeyCode: 65 });
        };
        const paste = async (selector, html) => {
            await ev(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});e.focus();const d=new DataTransfer();d.setData('text/html',${JSON.stringify(html)});e.dispatchEvent(new ClipboardEvent('paste',{clipboardData:d,bubbles:true,cancelable:true}));})()`); await sleep(80);
        };
        const upload = async (selector, filename) => {
            const document = await send('DOM.getDocument'); const node = await send('DOM.querySelector', { nodeId: document.root.nodeId, selector });
            assert.ok(node.nodeId); await send('DOM.setFileInputFiles', { nodeId: node.nodeId, files: [filename] }); await sleep(100);
        };
        const screenshot = async name => {
            const result = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
            fs.writeFileSync(path.join(runtime, name + '.png'), Buffer.from(result.data, 'base64'));
        };
        await send('Page.enable'); await send('Runtime.enable'); await send('Network.enable');
        await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1100, deviceScaleFactor: 1, mobile: false });
        return { root: repositoryRoot, runtime, out: runtime, fixtures, base, command, state: () => command('snapshot'), send, ev, waitFor, goto, click, fill, login, selectAll, paste, upload, screenshot, exceptions, responses, close };
    } catch (error) { await close(); throw error; }
}
