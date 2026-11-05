# PathWise — Hostinger Deployment Guide

Prepared on November 4, 2026 from the `finalization` branch. **Nothing has been
deployed.** The Hostinger plan is not confirmed yet, so this guide has two
paths:

- **Path A: Web/Cloud hosting** (Premium, Business, or Cloud plans, managed
  through hPanel). Most likely for a capstone.
- **Path B: VPS** (your own Ubuntu server with root access).

Steps marked **[hPanel]** are done in the Hostinger control panel, **[SSH]** in a
terminal on the server, and **[Local]** on your laptop. Lines marked
**Check:** depend on the plan. Confirm them on the real account; do not assume
them.

Never paste real keys or passwords into this file, a commit, a chat or a
screenshot. They go **only** into the server's `.env`.

---

## 1. Requirements (from the code)

| Item | Requirement | Source |
|---|---|---|
| PHP | **8.4 minimum** (8.4.1 or later; tested on 8.4.22). **PHP 8.3 will not work** | `composer.json` `"php": "^8.4.1"`; 21 locked Symfony 8 packages require `php >=8.4.1` (see `composer why-not php 8.3.99`) |
| Database | MySQL 8.0 (tested) or a MariaDB that Laravel 13 supports. **Check:** Hostinger web hosting usually provides MariaDB; see the version in phpMyAdmin | `config/database.php` |
| PHP extensions | `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `iconv`, `json`, `libxml`, `mbstring`, `openssl`, `pcre`, `pdo_mysql`, `session`, `tokenizer`, `zip`, **`gd`** (certificate PDF logo), `bcmath`, `intl` (recommended) | `composer check-platform-reqs`; DomPDF needs GD for the PNG logo |
| Upload limits | `upload_max_filesize = 25M`, `post_max_size = 30M`, `memory_limit = 256M`, `max_execution_time = 120` | Code submissions up to 25 MB, lessons 20 MB. 15 MB upload tested in Round 2 |
| Composer | 2.x on the server | — |
| Node.js | Only to build assets. Build **locally** if the plan has no Node | `package.json` |
| `mysqldump` | Needed by the backups (`spatie/laravel-backup`). **Check:** `which mysqldump` over SSH | `config/backup.php` |
| HTTPS | Required (secure cookies, passkeys, PayMongo redirects) | — |
| Queue worker | **Not needed.** Nothing is queued; mail is sent immediately | No `ShouldQueue` in `app/` |
| Cron | Needed for the scheduled backups | `routes/console.php` |

Verified on November 4: `config:cache`, `route:cache`, `view:cache` and
`event:cache` all succeed with this code.

---

## 2. Production `.env` (names only, never commit this file)

Start from `.env.example` and set:

```
APP_NAME=PathWise
APP_ENV=production
APP_DEBUG=false
APP_KEY=                      # filled by: php artisan key:generate
APP_URL=https://your-domain   # exact, with https, no trailing slash

LOG_CHANNEL=stack
LOG_STACK=daily               # one file per day, old files removed
LOG_DAILY_DAYS=14
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=                      # hPanel → Databases (often localhost on web hosting)
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync         # nothing is queued; sync avoids needing a worker
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=                # full mailbox address, e.g. no-reply@your-domain
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=            # same mailbox
MAIL_FROM_NAME="${APP_NAME}"

GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.5-flash

PAYMONGO_SECRET_KEY=          # sk_test_… until live payments are authorized
PAYMONGO_PUBLIC_KEY=          # pk_test_…
PAYMONGO_WEBHOOK_SECRET=      # whsk_… from the webhook you create (section 6)
PAYMONGO_PAYMENT_METHODS=gcash,card,paymaya
PAYMONGO_WEBHOOK_TOLERANCE=300

BACKUP_ARCHIVE_PASSWORD=      # backups contain personal data: set this
BACKUP_NOTIFICATION_EMAIL=    # who is told when a backup fails

SEED_SUPERADMIN_PASSWORD=     # optional; leave empty to have one generated and printed once
```

Notes:
- `MAIL_ENCRYPTION` is not used by Laravel 13; `MAIL_SCHEME` is.
- The PayMongo **test** and **live** webhook signatures differ. The app picks
  the right one from whether `PAYMONGO_SECRET_KEY` starts with `sk_live_`.
  Keep the key and webhook secret from the same mode.
- After any `.env` change: `php artisan config:cache`.

---

## 3. Before uploading [Local]

1. On the `finalization` branch (after the commits are approved), make sure
   `public/hot` does **not** exist: `dir public\hot` should say "File Not
   Found". Delete it if it does.
2. Build the assets: `npm ci` then `npm run build`. This creates
   `public/build/manifest.json` and `public/build/assets/*`.
3. Back up your local database if you plan to bring its data over
   (section 9).
4. **Never upload:** `.env`, `node_modules/`, `vendor/` (install it on the
   server), `public/hot`, `*.sql` dumps (for example `Dump20260901.sql`),
   `*.sqlite`, `storage/logs/*`, `storage/app/private/*` backups, the PNG
   screenshots in the project root, `test-results*.txt`.

---

## 4. Path A: Web/Cloud hosting (hPanel)

### 4.1 Panel settings [hPanel]

1. **Websites → your domain → Advanced → PHP Configuration**
   - PHP version: **8.4** (minimum; 8.3 will not work). **Check:** the plan offers 8.4
   - Extensions: enable everything in section 1 (especially `gd`, `zip`,
     `intl`, `bcmath`, `fileinfo`, `pdo_mysql`)
   - Options: `upload_max_filesize 25M`, `post_max_size 30M`,
     `memory_limit 256M`, `max_execution_time 120`
2. **Databases → Management**: create a database and user; note the host,
   name, user and password for `.env`.
3. **Security → SSL**: install the free SSL certificate and turn on **Force
   HTTPS**.
4. **Advanced → SSH Access**: enable SSH and note the host, port and user.
   **Check:** SSH is available on Premium and above.
5. **Emails**: create a mailbox (for example `no-reply@your-domain`) for SMTP.

### 4.2 Put the code on the server [SSH]

Hostinger serves a domain from `~/domains/<your-domain>/public_html`. Laravel
must be served from its own `public/` folder, and the rest of the project
(including `.env`) must stay **outside** the web root.

```bash
cd ~/domains/<your-domain>
git clone --branch <approved-branch> <your-repo-url> pathwise   # or upload a ZIP and unzip into ./pathwise
cd pathwise
php -v                      # Check: must print 8.4.x (8.4.1 or later); if not, use the full path,
                            #        e.g. /opt/alt/php84/usr/bin/php (Check the real path)
composer check-platform-reqs --lock   # every line must say "success"
composer install --no-dev --optimize-autoloader
```

If the plan has no Node, upload your locally built `public/build/` folder into
`~/domains/<your-domain>/pathwise/public/build/` (File Manager or SFTP).

### 4.3 Point the domain at `pathwise/public` [SSH]

```bash
cd ~/domains/<your-domain>
mv public_html public_html.bak          # keep Hostinger's default page, just in case
ln -s ~/domains/<your-domain>/pathwise/public public_html
ls -la                                  # public_html -> …/pathwise/public
```

**Check:** open `https://your-domain/up`. It should answer "Application up"
once `.env` is done. If symlinks are not allowed on the plan, ask Hostinger
support to set the document root to `domains/<your-domain>/pathwise/public`.
Do **not** move the whole project inside `public_html`, because that would
expose `.env`.

### 4.4 Configure and install [SSH]

```bash
cd ~/domains/<your-domain>/pathwise
cp .env.example .env
nano .env                               # fill in section 2
php artisan key:generate --force
php artisan migrate --force             # creates all tables
php artisan db:seed --force             # roles, Super Admin, course templates (no demo accounts in production)
```

`db:seed` prints the Super Admin password **once** if it generated one (account
`superadmin@pathwise.test`). Copy it to a password manager, sign in, then change
the email and password under Settings. To bring your local data instead of
starting empty, see section 9.

### 4.5 Storage and permissions [SSH]

```bash
php artisan storage:link                # public/storage -> storage/app/public (thumbnails, photos)
chmod -R 755 storage bootstrap/cache
find storage bootstrap/cache -type f -exec chmod 644 {} \;
```

If `storage:link` fails because `symlink()` is disabled, run
`ln -s ../storage/app/public public/storage` from `pathwise/`. Receipts,
submissions and lesson files stay in `storage/app/private`, which is never web
accessible; they are served only through access checks.

### 4.6 Production caches [SSH]

```bash
php artisan optimize          # config, events, routes and views
```

Run `php artisan optimize` again after every deployment or `.env` change. To
undo it: `php artisan optimize:clear`.

### 4.7 Cron for backups [hPanel]

**Advanced → Cron Jobs → Custom**, every minute (`* * * * *`):

```
/usr/bin/php /home/<user>/domains/<your-domain>/pathwise/artisan schedule:run >> /dev/null 2>&1
```

**Check:** use the same PHP binary path that `php -v` used in 4.2. The
scheduler then runs:
- daily at 01:00: a database backup
- Sundays at 02:00: a full backup (database, files and uploads)
- daily at 03:00: cleanup (keeps all backups for 7 days, daily ones for 16)

The server's timezone may differ from Philippine time. Check with `date`.

### 4.8 PayMongo webhook

See section 6.

---

## 5. Path B: VPS (Ubuntu)

Same application steps (4.2 to 4.7), with these differences:

```bash
# as root, once
# PHP 8.4 is required. Check: if `apt-cache policy php8.4-fpm` finds nothing
# (for example Ubuntu 24.04, which ships PHP 8.3), first run:
#   apt install -y software-properties-common && add-apt-repository -y ppa:ondrej/php
apt update && apt install -y nginx mysql-server unzip git \
  php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-zip \
  php8.4-gd php8.4-curl php8.4-intl php8.4-bcmath
# Composer: https://getcomposer.org/download/  ·  Node (optional, for building): nodejs 20+
```

- Project in `/var/www/pathwise`. Nginx `root /var/www/pathwise/public;` with the
  standard Laravel `try_files $uri $uri/ /index.php?$query_string;` and
  `client_max_body_size 30M;`.
- `/etc/php/8.4/fpm/php.ini`: the upload and memory values from section 1,
  then `systemctl restart php8.4-fpm`.
- Ownership: `chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache`.
- HTTPS: `certbot --nginx -d your-domain`.
- Cron: `crontab -u www-data -e` →
  `* * * * * php /var/www/pathwise/artisan schedule:run >> /dev/null 2>&1`.
- MySQL: `mysql_secure_installation`; create a dedicated user for the app.

---

## 6. PayMongo webhook (test mode first)

1. PayMongo Dashboard → **Developers** → make sure you are in **Test mode** →
   copy the `pk_test_…` and `sk_test_…` keys into `.env`.
2. **Webhooks → Create webhook**
   - URL: `https://your-domain/paymongo/webhook`
   - Event: **`checkout_session.payment.paid`** (the only event the app acts
     on; others are ignored)
3. Copy the webhook's **secret** (`whsk_…`) into `PAYMONGO_WEBHOOK_SECRET`, then
   `php artisan config:cache`.
4. Live keys only after explicit authorization: switch to Live mode, create a
   **separate** live webhook with the same URL, replace all three values, and
   run `config:cache`.

Built-in protections (tested): an HMAC signature over the exact body, a
5-minute timestamp window, a test/live signature match, a re-query of the
checkout session with PayMongo before approving, an amount check, and
idempotent handling of repeated events.

---

## 7. Production verification checklist

Do this on the real URL, with **PayMongo test keys**, before calling the
deployment done. Record the date, who did it, and the evidence (screenshot
name or log line). Never put a secret in the evidence.

| # | Check | How | Expected | Pass? | Evidence |
|---|---|---|---|---|---|
| V-1 | Site and HTTPS | Open `https://your-domain`, then `http://…` | Padlock; http redirects to https; styles load | ☐ | |
| V-2 | No debug output | Open `https://your-domain/does-not-exist` | Plain 404 page, no stack trace | ☐ | |
| V-3 | Real email delivery | Register a new student with a real inbox you own | "Verify your email address" arrives (check spam), From = your mailbox | ☐ | |
| V-4 | Verification flow | Click the link | Lands on the Student dashboard | ☐ | |
| V-5 | Password reset (production mail) | Log out → Forgot password → enter that email | "Reset your password" arrives; the link opens `https://your-domain/reset-password/…`; new password works; the same link a second time is refused | ☐ | |
| V-6 | PayMongo test checkout | As a student, buy a paid course → pay with a PayMongo **test** GCash/card | Redirect back to "Payment Confirmed"; transaction **Approved**; student sees the course under My Courses | ☐ | |
| V-7 | Webhook received and verified | PayMongo Dashboard → Webhooks → the event log for this payment | Delivery answered **200** | ☐ | |
| V-8 | Enrollment after payment | Super Admin → Transactions; Teacher → Course Students | One approved transaction; one active enrollment | ☐ | |
| V-9 | Duplicate webhook | In the PayMongo dashboard, **resend** the same event | Answers 200 "already processed"; still **one** enrollment and one enrollment email | ☐ | |
| V-10 | Bad signature rejected | `curl -X POST https://your-domain/paymongo/webhook -H "Content-Type: application/json" -H "Paymongo-Signature: t=1,te=bad,li=" -d "{}"` | **401** | ☐ | |
| V-11 | Cancelled checkout | Start a checkout and cancel it | "Payment Cancelled"; transaction Rejected; no enrollment | ☐ | |
| V-12 | Upload limit | As a teacher on a Draft course, upload a ~15 MB PDF lesson file | Saved; opens for an enrolled student | ☐ | |
| V-13 | Certificate | Complete a course as a test student; open and download the certificate | Full certificate visible; the PDF has the logo; the verify address is `https://your-domain/verify-certificate` | ☐ | |
| V-14 | Scheduled backup ran | The day after the cron is added: `ls -la storage/app/private/PathWise/` | A new `.zip` with today's date | ☐ | |
| V-15 | Backup restores | Download that zip; restore it into a **new empty** database (section 9.3); compare the table count and row counts | Same as live | ☐ | |
| V-16 | Deactivated account | Super Admin deactivates the test student; the student refreshes | Logged out; cannot log in again | ☐ | |
| V-17 | Logs | `tail storage/logs/laravel-*.log` | No errors from the steps above | ☐ | |

When V-1 to V-17 pass, record the results in `FINALIZATION_REPORT.md`. Only
then is the deployment complete.

---

## 8. Updating an existing deployment [SSH]

```bash
cd ~/domains/<your-domain>/pathwise            # or /var/www/pathwise
php artisan backup:run --only-db --disable-notifications   # 1. database backup first
git rev-parse HEAD > ../last-good-commit.txt               # 2. remember the current version
php artisan down --retry=60                                # 3. maintenance page
git pull                                                   # 4. new code (or upload the new files)
composer install --no-dev --optimize-autoloader
# upload the new public/build/ if built locally
php artisan migrate --force
php artisan optimize
php artisan up
```

---

## 9. Backups, restore and rollback

### 9.1 Backups

- Automatic: section 4.7. Stored in `storage/app/private/<APP_NAME>/` on the
  same server, which does **not** protect against losing the server. **Download
  the latest zip at least weekly** (File Manager/SFTP) and keep it off the
  server. Hostinger's own backups (hPanel → Backups) are a second layer.
  **Check:** what your plan includes.
- Manual, any time: `php artisan backup:run --only-db`.

### 9.2 Bringing local data to the server (optional, first deployment only)

Instead of the empty install in 4.4:
1. [Local] `php artisan backup:run --only-db` (with `mysqldump` on the PATH; see
   `docs/audit/DEPLOYMENT_NOTES.md`), or export the database from MySQL
   Workbench.
2. [hPanel] phpMyAdmin → the new database → **Import** the `.sql` file. Or
   [SSH] `mysql -u <user> -p <database> < dump.sql`.
3. [SSH] `php artisan migrate --force` (applies anything newer), then
   `php artisan files:make-private` if old receipts or submissions were
   uploaded before private storage, and upload `storage/app/` files from your
   laptop.
4. Demo accounts created locally (`*@example.com`, password from
   `DEMO_SEED_PASSWORD`) come along. Deactivate them before the defense if they
   should not be used.

### 9.3 Restore (tested locally on November 4: 34 tables, 391 rows matched)

```bash
unzip storage/app/private/PathWise/<date>.zip -d /tmp/restore      # asks for BACKUP_ARCHIVE_PASSWORD if set
# test first in a NEW empty database created in hPanel, e.g. <prefix>_restore_test:
mysql -u <user> -p <prefix>_restore_test < /tmp/restore/db-dumps/mysql-<database>.sql
# compare: SELECT COUNT(*) per table in both databases
# real restore (only after a confirmed loss):
php artisan down
mysql -u <user> -p <database> < /tmp/restore/db-dumps/mysql-<database>.sql
php artisan optimize && php artisan up
rm -rf /tmp/restore
```

### 9.4 Rolling back a bad deployment

```bash
php artisan down
git checkout $(cat ../last-good-commit.txt)       # or re-upload the previous ZIP
composer install --no-dev --optimize-autoloader
# re-upload the previous public/build/ if assets changed
php artisan optimize:clear && php artisan optimize
# only if the bad version changed data or ran a migration that broke data:
#   restore the backup taken in step 1 of section 8 (section 9.3)
php artisan up
```

---

## 10. What this guide does not cover yet

- The exact hPanel menu names and PHP binary path for **your** plan. Fill in
  every **Check:** once the subscription is confirmed.
- MariaDB has not been tested with this app. The migrations use no
  MySQL-only SQL, but run V-1 to V-17 after the first `migrate`.
- Live PayMongo keys: only after explicit authorization.
