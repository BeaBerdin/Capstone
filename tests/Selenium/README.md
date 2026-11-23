# Berdin Selenium unit tests

Selenium WebDriver (Python + pytest + Chrome) tests for the Learning and
Assessment Modules and the Administrator Modules (Case-001 to Case-063 in
`test_berdin_appendix.py`).

## What a run does (isolation)

Each run starts its **own** PathWise instance. It never uses
learnpathwise.site, the production database or your normal local database.

1. Creates a new temporary folder (`pathwise-selenium-*` in the OS temp
   folder) containing an **empty SQLite file**.
2. Runs the forward migrations on that file and seeds synthetic data
   (`support/runtime.php`): accounts with a **generated password**, a
   generated `APP_KEY`, departments, courses, lessons, quizzes and so on.
3. Starts `php -S` on a random `127.0.0.1` port. Environment loading and
   storage are redirected into the temporary folder, and the harness refuses
   to run if the active database is not that file.
4. Fakes external services: **Gemini** (fixed explanation text), **PayMongo**
   and all other outbound HTTP (`Http::fake` plus `preventStrayRequests`, with
   empty API keys). **Email** goes to the log file in the temporary folder.
   Chrome runs behind a local proxy that refuses everything except `127.0.0.1`.
5. Lifts only Fortify's log-in limit (5 per minute per account) for the
   disposable instance. The suite signs in through the real form several
   times a minute as the same account; no other rate limit is changed.
6. Removes the temporary folder when the run ends. Set
   `PATHWISE_SELENIUM_KEEP_RUNTIME=1` to keep it for diagnosis.

## Requirements

- Python 3.10+ and Google Chrome. Selenium Manager downloads the matching
  ChromeDriver automatically.
- The project's PHP 8.4 CLI with `pdo_sqlite`, installed Composer
  dependencies, and a built frontend (`npm run build`).
- No `bootstrap/cache/config.php`. The harness refuses cached configuration.

## Running

```sh
python -m venv .venv
.venv/Scripts/python -m pip install -r tests/Selenium/requirements.txt   # Windows
# .venv/bin/python -m pip install -r tests/Selenium/requirements.txt     # macOS/Linux

# If `php` on PATH is not PHP 8.4, point to the right binary:
#   PowerShell: $env:PATHWISE_PHP_BINARY = 'C:\path\to\php.exe'
.venv/Scripts/python -m pytest tests/Selenium/test_berdin_appendix.py -v
```

- Headless Chrome at 1366x768 by default. Set `PATHWISE_SELENIUM_HEADED=1`
  to watch the browser.
- Each case saves a screenshot of the PathWise result to
  `tests/Selenium/artifacts/screenshots/<test name>.png` (override the
  folder with `PATHWISE_SELENIUM_ARTIFACTS`). `artifacts/` is git-ignored.
- One run takes about 10 minutes, because every case starts a fresh browser
  and signs in through the real log-in form.

## Fixtures

`conftest.py` provides:

- `driver`: a new Chrome session per test.
- `wait`: `WebDriverWait`, 40 s, ignoring stale elements.
- `snap(label, element=None)`: saves the result screenshot, scrolled to `element`.
- `url(path)`: absolute URL on the disposable instance.
- `login(driver, wait, key)`: signs in as a fixture account (keys are listed in `support/runtime.php`).
