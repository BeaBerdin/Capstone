"""Selenium fixtures for the PathWise unit tests.

Every run starts its OWN PathWise instance:
  * a new temporary folder (prefix ``pathwise-selenium-``) with an empty SQLite file,
  * forward migrations + synthetic seed data (tests/Selenium/support/runtime.php),
  * generated passwords and application key,
  * Gemini / PayMongo / all outbound HTTP faked, mail written to a log file,
  * ``php -S`` on a random 127.0.0.1 port, Chrome behind a deny-all proxy.

It never touches learnpathwise.site, the production database or the normal
local database. The temporary folder is deleted when the run ends.
"""

from __future__ import annotations

import base64
import http.server
import json
import os
import secrets
import shutil
import socket
import subprocess
import tempfile
import threading
import time
import urllib.request
from pathlib import Path

import pytest
from selenium import webdriver
from selenium.common.exceptions import StaleElementReferenceException
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

SELENIUM_DIR = Path(__file__).resolve().parent
REPO_ROOT = SELENIUM_DIR.parent.parent
HARNESS = SELENIUM_DIR / "support" / "runtime.php"
ARTIFACTS = Path(os.environ.get("PATHWISE_SELENIUM_ARTIFACTS", SELENIUM_DIR / "artifacts"))

_state: dict = {}


def url(path: str = "/") -> str:
    """Absolute URL on the disposable PathWise instance."""
    return _state["base"] + path


def account(key: str) -> dict:
    return _state["fixtures"]["users"][key]


def fixture(*keys):
    value = _state["fixtures"]
    for key in keys:
        value = value[key]
    return value


def login(driver, wait, key: str) -> None:
    """Sign in through the real log-in form as the fixture account ``key``."""
    driver.delete_all_cookies()
    driver.get(url("/login"))
    wait.until(EC.visibility_of_element_located((By.NAME, "email"))).send_keys(account(key)["email"])
    driver.find_element(By.NAME, "password").send_keys(_state["password"])
    driver.find_element(By.CSS_SELECTOR, "form[action$='/login'] button[type=submit]").click()
    wait.until(lambda d: "/login" not in d.current_url)


def page_text(driver) -> str:
    return driver.find_element(By.TAG_NAME, "body").text


def _free_port() -> int:
    with socket.socket() as s:
        s.bind(("127.0.0.1", 0))
        return s.getsockname()[1]


class _DenyProxy(http.server.BaseHTTPRequestHandler):
    """Refuses every request the browser tries to send outside 127.0.0.1."""

    def _deny(self):
        self.send_response(403)
        self.send_header("Content-Length", "0")
        self.end_headers()

    do_GET = do_POST = do_PUT = do_DELETE = do_HEAD = do_CONNECT = _deny

    def log_message(self, *args):
        pass


def _php_binary() -> str:
    return os.environ.get("PATHWISE_PHP_BINARY") or shutil.which("php") or "php"


@pytest.fixture(scope="session")
def pathwise():
    assert (REPO_ROOT / "public" / "build" / "manifest.json").exists(), "Run `npm run build` first."
    runtime = Path(tempfile.mkdtemp(prefix="pathwise-selenium-"))
    database = runtime / "selenium.sqlite"
    database.touch(exist_ok=False)
    (runtime / "created-new-runtime").touch(exist_ok=False)
    for sub in ["storage/app/private", "storage/app/public", "storage/framework/views",
                "storage/framework/cache/data", "storage/framework/sessions", "storage/logs", "uploads"]:
        (runtime / sub).mkdir(parents=True, exist_ok=True)

    proxy = http.server.ThreadingHTTPServer(("127.0.0.1", 0), _DenyProxy)
    threading.Thread(target=proxy.serve_forever, daemon=True).start()
    proxy_url = f"http://127.0.0.1:{proxy.server_address[1]}"

    run_id = secrets.token_hex(8)
    password = secrets.token_urlsafe(24) + "!7aA"
    port = _free_port()
    base = f"http://127.0.0.1:{port}"
    env = {
        **os.environ,
        "APP_ENV": "local", "APP_DEBUG": "false", "APP_URL": base,
        "APP_KEY": "base64:" + base64.b64encode(secrets.token_bytes(32)).decode(),
        "DB_CONNECTION": "sqlite", "DB_DATABASE": str(database), "DB_URL": "",
        "SESSION_DRIVER": "file", "SESSION_COOKIE": "pw_selenium_" + run_id,
        "CACHE_STORE": "file", "MAIL_MAILER": "log", "LOG_CHANNEL": "single",
        "QUEUE_CONNECTION": "sync", "BROADCAST_CONNECTION": "null",
        "GEMINI_API_KEY": "", "PAYMONGO_SECRET_KEY": "", "PAYMONGO_PUBLIC_KEY": "",
        "PAYMONGO_WEBHOOK_SECRET": "", "AWS_ACCESS_KEY_ID": "", "AWS_SECRET_ACCESS_KEY": "",
        "POSTMARK_API_KEY": "", "RESEND_API_KEY": "",
        "NIGHTWATCH_ENABLED": "false", "PULSE_ENABLED": "false", "TELESCOPE_ENABLED": "false",
        "BCRYPT_ROUNDS": "4",
        "PATHWISE_REPO_ROOT": str(REPO_ROOT), "PATHWISE_SELENIUM_RUNTIME": str(runtime),
        "PATHWISE_SELENIUM_ID": run_id, "PATHWISE_SELENIUM_PASSWORD": password,
        "TEMP": str(runtime / "uploads"), "TMP": str(runtime / "uploads"),
        "HTTP_PROXY": proxy_url, "HTTPS_PROXY": proxy_url, "ALL_PROXY": proxy_url,
        "NO_PROXY": "127.0.0.1,localhost",
    }
    php = _php_binary()
    flags = ["-d", "memory_limit=512M", "-d", f"upload_tmp_dir={runtime / 'uploads'}",
             "-d", "allow_url_fopen=0", "-d", "allow_url_include=0"]
    server = None
    try:
        out = subprocess.run([php, *flags, str(HARNESS), "init"], cwd=REPO_ROOT, env=env,
                             capture_output=True, text=True, encoding="utf-8", timeout=300)
        last = out.stdout.strip().splitlines()[-1] if out.stdout.strip() else ""
        if out.returncode != 0 or not last.startswith("{"):
            raise RuntimeError("Fixture setup failed:\n" + out.stdout[-4000:] + out.stderr[-2000:])
        fixtures = json.loads(last)

        with open(runtime / "server.log", "ab") as log:
            server = subprocess.Popen([php, *flags, "-S", f"127.0.0.1:{port}", "-t", str(REPO_ROOT / "public"), str(HARNESS)],
                                      cwd=REPO_ROOT, env=env, stdout=log, stderr=log)
        ready = None
        for _ in range(150):
            if server.poll() is not None:
                raise RuntimeError("PHP server exited: " + (runtime / "server.log").read_text(errors="replace"))
            try:
                with urllib.request.urlopen(base + "/__selenium-safety", timeout=5) as response:
                    ready = json.loads(response.read())
                if ready.get("runtimeId") == run_id:
                    break
            except Exception:
                time.sleep(0.2)
        assert ready and ready["runtimeId"] == run_id, "Could not verify the disposable server."
        assert ready["sqlite"] and ready["mail"] in ("log", "array") and not ready["gemini"]
        assert Path(ready["database"]).resolve() == database.resolve()

        _state.update(base=base, fixtures=fixtures, password=password, proxy=proxy_url, runtime=runtime)
        yield _state
    finally:
        if server is not None:
            server.terminate()
            try:
                server.wait(timeout=10)
            except subprocess.TimeoutExpired:
                server.kill()
        proxy.shutdown()
        if os.environ.get("PATHWISE_SELENIUM_KEEP_RUNTIME") != "1":
            for _ in range(20):  # Windows releases the server's files shortly after exit
                shutil.rmtree(runtime, ignore_errors=True)
                if not runtime.exists():
                    break
                time.sleep(0.5)


@pytest.fixture
def driver(pathwise):
    options = webdriver.ChromeOptions()
    if os.environ.get("PATHWISE_SELENIUM_HEADED") != "1":
        options.add_argument("--headless=new")
    options.add_argument("--window-size=1366,768")
    options.add_argument(f"--proxy-server={pathwise['proxy']}")
    options.add_argument("--proxy-bypass-list=127.0.0.1")
    for arg in ["--no-first-run", "--no-default-browser-check", "--disable-background-networking",
                "--disable-component-update", "--disable-sync", "--disable-features=Translate,OptimizationHints,MediaRouter",
                "--password-store=basic", "--force-device-scale-factor=1"]:
        options.add_argument(arg)
    options.add_experimental_option("prefs", {"credentials_enable_service": False,
                                              "profile.password_manager_enabled": False,
                                              "profile.password_manager_leak_detection": False})
    browser = webdriver.Chrome(options=options)
    browser.set_window_size(1366, 768)
    yield browser
    browser.quit()


@pytest.fixture
def wait(driver):
    return WebDriverWait(driver, 40, ignored_exceptions=[StaleElementReferenceException])


@pytest.fixture
def snap(driver, request):
    """Save the current browser view as the case's PATHWISE result screenshot."""
    folder = ARTIFACTS / "screenshots"
    folder.mkdir(parents=True, exist_ok=True)

    def take(label: str = "browser-result", element=None):
        if element is not None:
            driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", element)
            time.sleep(0.3)
        driver.save_screenshot(str(folder / f"{request.node.name}.png"))

    return take


@pytest.hookimpl(hookwrapper=True)
def pytest_runtest_makereport(item, call):
    """On a failure, keep the browser state as the case's result screenshot."""
    outcome = yield
    report = outcome.get_result()
    browser = item.funcargs.get("driver") if hasattr(item, "funcargs") else None
    if report.when == "call" and report.failed and browser is not None:
        try:
            for alert_text in [browser.switch_to.alert.text]:
                browser.switch_to.alert.accept()
        except Exception:
            pass
        folder = ARTIFACTS / "screenshots"
        folder.mkdir(parents=True, exist_ok=True)
        try:
            browser.save_screenshot(str(folder / f"{item.name}.png"))
            (ARTIFACTS / "failures").mkdir(parents=True, exist_ok=True)
            (ARTIFACTS / "failures" / f"{item.name}.txt").write_text(
                browser.current_url + "\n\n" + browser.find_element(By.TAG_NAME, "body").text, encoding="utf-8")
        except Exception:
            pass
