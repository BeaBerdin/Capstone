# Reading and Course Templates browser regressions

Use Node.js 22+, the project's compatible PHP CLI, installed locked dependencies,
and a locally installed Chromium-family browser. No browser or package is downloaded.
Build the frontend before running:

```sh
npm run build
node --test tests/JavaScript/reading-editor.test.mjs
node tests/Browser/reading-editor-browser.mjs
node tests/Browser/course-under-review-browser.mjs
```

Set `PATHWISE_BROWSER_EXECUTABLE` to the installed browser executable. Optionally
set `PATHWISE_PHP_BINARY` when `php` on PATH is not the desired PHP CLI. These are
machine-specific environment values; never put executable paths or credentials
into the test source. On PowerShell use `$env:VARIABLE = 'your configured value'`.

The browser suite runs the actual Laravel routes, including login, course template
selection, draft lesson generation, rich-text editing, approval, and Student rendering.
`reading-plain-multipart-browser.mjs` is the reusable multipart case helper invoked
by that suite, not a separate server or an external preview-script dependency.

Each invocation creates a new directory under the operating system temporary
folder, an exclusively created SQLite file, generated account passwords and an
application key. It runs forward migrations only against that empty file. It
rejects cached application configuration and verifies the active database before
handling requests. Environment loading and storage are redirected outside the
checkout. No existing database, `.env`, upload, browser profile or preview server
is reused.

The app and a dedicated deny proxy bind to random loopback ports. PHP outbound
transports and real mail are disabled, Laravel stray HTTP requests are prevented,
and the isolated browser uses the deny proxy plus a same-origin CSP. AI/payment
credentials are empty. No queue worker is started.

Server/browser processes are closed when the suite finishes. Its temporary
SQLite database, generated fixtures, wire-format captures, screenshots and logs
remain outside the Git checkout for diagnosis; the output prints that location.
Do not commit these artifacts or use the generated runtime for real data.

Run the Laravel regressions separately with the repository's guarded in-memory
SQLite configuration. No production or existing-database migration command is
part of this browser test setup.
