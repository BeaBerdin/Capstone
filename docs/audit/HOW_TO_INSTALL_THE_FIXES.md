# How to Install the PathWise Fixes (Beginner Guide)

Read every step before you start. Commands go in a terminal opened **inside your
`pathwise` project folder** (in VS Code: *Terminal → New Terminal*).

> **Important:** these changes were written and checked by reading the code, but they
> have **not been run yet**. Step 8 (the automated tests) is how you find out whether
> everything works. Please send me the test output.

---

## Step 0 — Before anything else: protect your secrets

Your uploaded ZIP contained your real `.env` file.

1. Create a **new Gemini API key** in Google AI Studio and delete the old one.
2. Change the **email (SMTP) password** used in `.env`.
3. In the PayMongo dashboard, **roll (regenerate) your test API keys**.
4. Put the new values in your `.env`. Never send `.env` to anyone again.
5. Remove the database dump from Git (it contains user accounts):
   ```
   git rm --cached Dump20260901.sql
   git commit -m "Remove database dump from repository"
   ```
   The file stays on your computer; it just stops being shared through Git.
   (It is still in older commits. If the repository is public, tell your adviser and
   ask me how to remove it from the history.)

## Step 1 — Make a backup (do not skip)

1. **Code backup with Git:**
   ```
   git add -A
   git commit -m "Backup before audit fixes"
   ```
2. **Database backup:** in MySQL Workbench, *Server → Data Export*, choose the
   `pathwise` schema, *Export to Self-Contained File*, click *Start Export*.

If anything goes wrong you can return to this point with `git reset --hard` (code) and by
importing the export file (database).

## Step 2 — Stop the running app

Stop `php artisan serve`, `npm run dev`, or `composer run dev` (press `Ctrl + C`).

## Step 3 — Copy the changed files into your project

1. Download **`pathwise-changed-files.zip`**.
2. Extract it **directly into your `pathwise` folder** (the folder that contains
   `artisan`). The ZIP already uses the same folder structure (`app/…`,
   `resources/…`, …).
3. When Windows asks, choose **"Replace the files in the destination"**.

This ZIP does **not** contain `.env`, `vendor`, `node_modules`, or your settings pages,
so those stay exactly as they are.

> Do **not** copy files back from the ZIP you originally uploaded: the Livewire settings
> pages in it were renamed by the ZIP tool (`⚡profile.blade.php` became
> `#U26a1profile.blade.php`). Keep your own originals.

## Step 4 — Add the new setting to `.env`

Open `.env` and add this line at the bottom (leave the value empty if you have not
created a PayMongo webhook yet):

```
PAYMONGO_WEBHOOK_SECRET=
```

When you create a webhook in the PayMongo dashboard (event
`checkout_session.payment.paid`, URL `https://YOUR-DOMAIN/paymongo/webhook`), paste its
signing secret (starts with `whsk_`) here.

## Step 5 — Clear cached settings

```
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Step 6 — Update the database (safe, additive)

First **preview** what will run, without changing anything:

```
php artisan migrate --pretend
```

You should see only these new migrations (plus possibly
`2026_09_19_133820_…` if it never ran on your database):

- `2026_10_16_000001_ensure_course_invitation_id_on_transactions_table`
- `2026_10_16_000002_add_is_active_to_users_table`
- `2026_10_16_000003_add_review_remarks_to_courses_table`
- `2026_10_16_000004_add_template_fields_to_course_templates_table`
- `2026_10_16_000005_add_template_id_to_courses_table`

They only **add** columns. Then run them for real:

```
php artisan migrate
```

> **Never** run `php artisan migrate:fresh` or `migrate:refresh` on your real database —
> those commands delete all data.

## Step 7 — Load the course templates and rebuild the styles

```
php artisan db:seed --class=CourseTemplateSeeder
npm run build
```

`npm run build` is needed because the new pages use new styling classes.

## Step 8 — Run the automated tests

The tests use a temporary in-memory **SQLite** database, never your MySQL database. A
safety check in `tests/TestCase.php` stops the tests if the configuration ever points
somewhere else.

1. Make sure PHP has the SQLite driver:
   ```
   php -m
   ```
   Look for `pdo_sqlite` in the list. If it is missing, open your `php.ini` (Laragon:
   *Menu → PHP → php.ini*; XAMPP: `C:\xampp\php\php.ini`), remove the `;` in front of
   `extension=pdo_sqlite` and `extension=sqlite3`, save, and restart the terminal.
2. Run only the new PathWise tests first:
   ```
   php artisan test tests/Feature/Pathwise
   ```
3. Then run everything:
   ```
   php artisan test
   ```
4. **Copy the full output and send it to me**, including any red FAILED lines.

Notes:
- `RegistrationTest` checks email domains on the internet (`email:rfc,dns`), so it can
  fail when you are offline.
- The settings-page tests only pass if your `⚡…blade.php` settings files are intact.

## Step 9 — Check by hand in the browser

Run `composer run dev` (or `php artisan serve` + `npm run dev`) and log in with each role.

| As | Do this | You should see |
|---|---|---|
| Student | Open a **paid** course and try to enroll | Payment checkout, not instant enrollment |
| Super Admin | *Users → + Add User*, create an Administrator | New account in the list, status "Active" |
| Super Admin | Click **Deactivate** on a test account, then log in as it | "Your account has been deactivated…" |
| Teacher | *Create Course*, pick **Standard Course (3 Modules)** | Course with 5 unpublished draft lessons |
| Teacher | Try **Submit for Approval** before publishing those lessons | Error asking you to publish them |
| Teacher | Edit and publish the lessons, then submit | Status "Pending" |
| Teacher | Try to edit the pending course | Message that it is locked |
| Administrator | *Courses → Return for Revision*, type feedback | Teacher sees the feedback and a notification |
| Teacher | *My Courses → Edit Course Details* on the returned course | Edit page with the feedback, then resubmit |

## If something goes wrong

- **"Tests stopped to protect your data"** → run `php artisan config:clear` and try again.
- **A migration error** → do not try to fix it by dropping tables. Copy the full error
  and send it to me.
- **A page shows an error** → open `storage/logs/laravel.log`, copy the last error, and
  send it to me (check it does not contain passwords first).
