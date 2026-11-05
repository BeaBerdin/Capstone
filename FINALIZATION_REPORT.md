# PATHWISE — Finalization Report

Date: November 4, 2026 · Branch `Berdin` · Commit `277310b` · Prepared from
`PATHWISE_FINALIZATION_PROMPT.md`.

**Source of truth:** `docs/audit/PATHWISE REVISED.pdf` (131 pages, cover date
December 2026). It is byte-identical to `Downloads/PATHWISE REVISED.pdf`
(MD5 `2d14210870dd8bf67c0cbb53b848f85c`).

**`AUDIT_REPORT.md` does not exist.** The starting point was the earlier audit
files in `docs/audit/` (`AUDIT_STATUS.md`, `MANUSCRIPT_REVISIONS.md`,
`DEPLOYMENT_NOTES.md`). Their IDs (P0-1 … P2-25, MR-1 … MR-36) are reused below.

> **Read from the top. The November 5 update is current; then Final
> Acceptance; Round 2 has the detailed evidence; Round 1 (sections 1–11) is
> kept for history.**

---

# Update (November 5, 2026)

This update supersedes the items below wherever they differ.

- **New manuscript source:** `docs/audit/PATHWISE-NEW-COMPILATION.docx`
  replaces `PATHWISE REVISED.pdf`, which is no longer in `docs/audit/`. The
  manuscript findings are now in
  **`docs/audit/MANUSCRIPT_REVISIONS_NEW_COMPILATION.md`** (35 items plus
  5 optional additions). It **replaces `MANUSCRIPT_REVISIONS_DECEMBER.md`**,
  which is not kept in the repository. FA.5 and R2.11 below describe the
  December check for history only.
- **PHP 8.4 is the minimum.** 21 locked Symfony 8 packages require
  `php >=8.4.1`, so PHP 8.3 cannot install the project. Updated:
  `composer.json` (`"php": "^8.4.1"`, lock file hash and platform refreshed
  with no package changes), `docs/DEPLOYMENT_HOSTINGER.md`, `README.md`, and
  the CI test matrix (`.github/workflows/tests.yml`: 8.4 and 8.5, no 8.3).
  Every "PHP 8.3" below is superseded.
- **Second security update:** 10 Symfony advisories published on
  November 4 were fixed by updating only `symfony/http-foundation`,
  `http-kernel`, `routing`, `serializer` and `yaml` to 8.1.11.
- **Results on the final working tree:** `composer audit` 0 advisories;
  full suite 575 passed, 0 failed, 2,451 assertions; `npm run build` success;
  `composer check-platform-reqs --lock` success on PHP 8.4.22.
- **Review and commit plan:** `docs/audit/PENDING_CHANGES_REVIEW.md`.
  Nothing is committed yet.

---

# Final Acceptance Report (November 4, 2026)

## Verdict

**Locally finalized and verified. NOT yet deployment-ready.** PathWise
cannot be called deployment-ready until the hosting configuration and live
integrations (`docs/DEPLOYMENT_HOSTINGER.md`, checklist V-1 to V-17) pass on
the real Hostinger account.

## FA.1 Automated results (`finalization` branch, final run)

| Check | Result |
|---|---|
| Pest, full suite | **575 passed, 0 failed, 2451 assertions** (556 pre-existing + 19 new; no existing test edited or deleted) |
| `composer audit` | **No security vulnerability advisories found** (was 38 advisories across 10 packages) |
| `npm audit` | **0 vulnerabilities** |
| `npm run build` | Success |
| `config:cache` / `route:cache` / `view:cache` / `event:cache` | All succeed (then cleared locally) |
| Migrations from scratch + seeders | 52/52 on isolated SQLite |

## FA.2 Bug fixes (all with tests or screenshot evidence; details in R2.3)

1. Teacher → Course Students → **View progress returned HTTP 500**. Fixed.
2. Administrator **Student Progress Reports was always empty**. Fixed (teachers still see only their own courses).
3. **Certificate page cut off** on phone, tablet and desktop, and it printed the local address "pathwise.test". Fixed; it now prints `/verify-certificate` on the live domain.
4. **Review Submission page overflowed** 120 px on phones (long code lines). Fixed.
5. **Stray Markdown line** on the Departments page, and a **6 px overflow** of an Analytics chart on desktop. Fixed.

## FA.3 Responsive testing

345 page loads (every GET page of all 4 roles plus guest pages) at 390 / 768 /
1280 px in headless Microsoft Edge: **0 server errors, 0 horizontal overflow,
0 JavaScript errors** after the fixes. The remaining 403s are intentional
(R2.4). Only Edge was tested; Chrome, Firefox and Safari are on the live
checklist.

## FA.4 Database backup and restore

`backup:run --only-db` on the real database → restored into a separate
throwaway database → **34 tables, 391 rows, 0 mismatches** → throwaway database
dropped. The server cron and a restore on the host are live checklist items
V-14 and V-15.

## FA.5 Manuscript (`docs/audit/MANUSCRIPT_REVISIONS_DECEMBER.md`)

- **(q)–(t) verified against the code and tests. All four are implemented;
  restoring all four is recommended.** Each is mapped to Objectives 2.3/2.4/3,
  the responsible roles and named tests. The invitations feature is covered by
  extending FR d, not by a new requirement.
- **Registration contradiction resolved in favour of the system and p. 52:**
  the verification email comes first, then the dashboard. Replacement for
  p. 66 steps 5–7 is provided.
- **"Windows 10 and above" (p. 101) and "laptop or desktop" (p. 103) replaced**
  with Linux server hosting plus any desktop, laptop, tablet or smartphone with
  an up-to-date browser. It states honestly that only Edge was tested.
- **Remaining discrepancies the team must still edit:** 25 items in section A
  (top risk: the machine-learning AI architecture, pp. 48–50; FR c and
  Table 24 "Publish/Unpublish" must say the Administrator's approval
  publishes; stub Tables 18 and 21), section B (restore q–t, Table 24
  modules), and section C (registration, OS/devices, certificate and AI
  "settings" wording, list numbering).
- A recheck must be run on the corrected PDF.

## FA.6 Deployment documentation

`docs/DEPLOYMENT_HOSTINGER.md`: requirements taken from the code (PHP 8.4 minimum, updated Nov. 5;
extensions including GD, MySQL 8 / MariaDB to be checked), upload limits,
storage permissions, SMTP (`smtp.hostinger.com:465`, `smtps`), the PayMongo
webhook (`checkout_session.payment.paid`, test mode first), cron, asset
build, update, backup, restore and rollback. Path A covers web/cloud hosting
and Path B covers a VPS. Plan-specific values are marked **Check:**.

## FA.7 Outstanding production blockers

| # | Blocker | Owner |
|---|---|---|
| PB-1 | Hostinger plan confirmed, and every **Check:** in the guide filled in | Team |
| PB-2 | Production `.env` on the server (SMTP mailbox, Gemini key, PayMongo **test** keys and webhook secret, `BACKUP_ARCHIVE_PASSWORD`, `APP_NAME=PathWise`, `SESSION_SECURE_COOKIE=true`) | Team |
| PB-3 | Live checklist V-1 to V-17 passed on the real URL (email, password reset, PayMongo test payment, webhook signature, enrollment, duplicate event, backup and restore) | Team + me |
| PB-4 | MariaDB compatibility, if the plan uses MariaDB (covered by running V-1 to V-17 after `migrate`) | Team + me |
| PB-5 | Approval of the commits, then of push/merge | You |
| PB-6 | Live PayMongo keys: **only after explicit authorization** | You |
| — | Manuscript edits: not a deployment blocker, but a defense risk | Team |

---

# Round 2 — Authorized local finalization (November 4, 2026)

Branch **`finalization`**, created from `Berdin` at `277310b` and carrying the
uncommitted Create Account work. **Nothing is committed, merged or pushed.**
No deployment was done. No secrets were printed, written or committed.

## R2.1 Status at a glance

| Requirement from your approval | Result |
|---|---|
| Separate branch | Done: `finalization` (`Berdin` is unchanged at `277310b`) |
| Fix production dependency vulnerabilities within constraints | **Done.** `composer audit`: 0 advisories (was 38). `composer.json` unchanged |
| Full regression suite, including the new security tests | **575 passed, 0 failed, 2451 assertions** (556 existing + 19 new). `npm audit`: 0. `composer audit`: 0 |
| `npm run build` | Passes |
| Responsive testing (desktop, tablet, mobile) | **Done:** 345 page loads across all roles. **5 defects found and fixed** (R2.4) |
| PHP upload limits vs 20 MB | **Verified failing locally** (2 MB / 8 MB). Required values found (R2.5) |
| Email and password reset with production-type settings | **Verified** over SMTP with authentication (R2.6) |
| PayMongo webhook signature and payment handling | **Verified**, 8 new tests (R2.7) |
| `public/hot` excluded; production assets | **Verified** (R2.8) |
| Backup and rollback | Backup → restore → compare **verified** (R2.9). Rollback steps in R2.10 |
| Manuscript recheck against the December PDF | **Done:** `docs/audit/MANUSCRIPT_REVISIONS_DECEMBER.md` (R2.11) |
| Hostinger procedure | **Waiting for the plan type.** Not assumed |
| Teacher publish/unpublish | Not added, as decided. The paper is to be corrected (A-17, A-18) |

## R2.2 Dependency update (reviewed)

Command: `composer update` restricted to the vulnerable packages and their
dependencies. The new versions are all within the existing `composer.json`
constraints, and **no major-version upgrades** remain. The first resolution
moved Guzzle 7→8, psr7 2→3, promises 2→3, uri-template 1→2 and brick/math
0.14→0.17, so I pinned those back to their current major versions with
`--with`. 7.16.1 already fixes the Guzzle advisory.

| Package | Before → After | Advisory fixed |
|---|---|---|
| laravel/framework | 13.15.0 → 13.40.0 | critical: TokenGuard bypass |
| symfony/http-foundation | 8.1.0 → 8.1.10 | high: ReDoS via `Cache-Control` |
| guzzlehttp/guzzle | 7.11.1 → 7.16.1 | high: host-check bypass |
| guzzlehttp/psr7 | 2.11.0 → 2.13.2 | medium ×2 |
| league/commonmark | 2.8.2 → 2.10.7 | high ×9 (not reachable) |
| livewire/livewire | 4.3.1 → 4.4.8 | medium ×2 |
| dompdf/dompdf | 3.1.5 → 3.1.6 | medium/low ×6 |
| symfony/mime, symfony/yaml, league/flysystem | patch updates | medium/low |

There were 64 lock entries in total: 61 patch/minor updates, 2 removed
(`thecodingmachine/safe`, no longer required by anything) and 1 added
(`symfony/polyfill-time`, now required by Carbon). The full list is in the
`composer.lock` diff.

## R2.3 Code changes made (all small, all tested)

| File | Change | Why | Test |
|---|---|---|---|
| `StudentProgressController.php` | The Administrator's report lists every course; teachers still see only their own | **Bug:** the admin's "Student Progress Reports" (Fig. 36) reused the teacher filter and was always empty | `StudentProgressPagesTest` |
| `CourseController.php` (`studentProgress`) | Pass the enrollment list the view expects | **Bug:** Course Students → View progress returned **500** | `StudentProgressPagesTest` |
| `Student/certificate-view.blade.php` | The frame grows with its content; smaller type on phones; verify address from `route('certificates.verify')` | **Bug:** the certificate was cut off on phone, tablet *and* desktop (number, date and signatory hidden), and it printed the local address "pathwise.test" | `FinalizationUiFixesTest` + screenshots |
| `Teacher/submissions/show.blade.php` | `*:min-w-0` on the grid | **Bug:** on phones, long code lines pushed the cards 120 px off screen | Responsive sweep |
| `Teacher/analytics.blade.php` | `*:min-w-0` on the two chart grids | The chart card ran 6 px past the right edge on desktop | Responsive sweep |
| `departments/index.blade.php` | Removed a stray ```` ```blade ```` first line | It printed as text at the top of the page | `FinalizationUiFixesTest` |

New tests (all passing): `CrossAccountAccessTest` (5), `PayMongoWebhookSecurityTest`
(8), `StudentProgressPagesTest` (4), `FinalizationUiFixesTest` (2). No existing
test was edited or deleted.

## R2.4 Responsive testing

The app ran against a **separate SQLite copy with demo data** (not your MySQL).
Headless Microsoft Edge opened **every GET page of every role** at 390 px
(phone), 768 px (tablet) and 1280 px (desktop): 345 page loads. For each page it
checked the HTTP status, horizontal scroll, elements running past the screen
edge, JavaScript/console errors and whether CSS loaded. I also looked at all
115 phone screenshots.

- **After the fixes:** no 500 errors, no horizontal overflow, no JavaScript
  errors, CSS loaded everywhere.
- **Remaining 403s are by design:** lesson create/edit on a *published*
  course (locked; the buttons are already hidden); the admin's Student Removal
  Requests page when the admin has no department (shows "Your account must be
  assigned to a department"; see manual action M-6); `/quiz-results/{id}/edit`
  as admin (nothing links to it).
- Not covered: interactive steps inside pages (opening modals, submitting every
  form at phone width). The 561+ feature tests cover the form logic.

## R2.5 PHP upload limits

The local PHP (Herd, used by both the CLI and the web server) has
`upload_max_filesize = 2M` and `post_max_size = 8M`. **A real 15 MB upload is
discarded by PHP before Laravel sees it.** With `upload_max_filesize=25M` and
`post_max_size=30M`, the same file arrives intact. 25M is needed because code
submissions allow 25 MB; lessons allow 20 MB.

- **Local (Herd):** Herd → PHP → set the max upload size to 25 MB (or edit
  `…\.config\herd\bin\php84\php.ini`), then restart Herd.
- **Hostinger:** hPanel → Advanced → PHP Configuration → `upload_max_filesize`
  25M, `post_max_size` 30M, `memory_limit` ≥ 256M. The exact screen depends on
  the plan.

## R2.6 Email and password reset

A local SMTP server received mail from Laravel's **SMTP mailer with
authentication**, the same transport production uses, sent against the SQLite
copy:

- "Reset your password" and "Verify your email address" were both delivered.
  Links use `APP_URL` (`https://…/reset-password/<token>`).
- The token from the email reset the password (`passwords.reset`). The new
  password works. **Reusing the token is refused** (`passwords.token`).
- No real email was sent. Your SMTP password was not used or displayed.
- Your local `.env` uses port 587 with `MAIL_ENCRYPTION=tls`. Laravel 13
  ignores `MAIL_ENCRYPTION`; on 587 it upgrades to TLS automatically. For
  **Hostinger's mail server use `MAIL_HOST=smtp.hostinger.com`, `MAIL_PORT=465`,
  `MAIL_SCHEME=smtps`**, and a mailbox created in hPanel.
- Nothing is queued (`ShouldQueue` is not used), so email works without a
  queue worker.

## R2.7 PayMongo

New `PayMongoWebhookSecurityTest` (8 tests) with the existing 5 webhook tests:

| Case | Result |
|---|---|
| Correct signature, paid event | Approved, student enrolled **once**, even when PayMongo resends the event |
| Signature for a different body | 401. PayMongo is not even called |
| Wrong secret | 401 |
| Timestamp older than the tolerance (300 s) | 401 |
| Test-mode signature while live keys are set | 401; the live signature is accepted |
| Other event types | Ignored |
| Signed event, but PayMongo says the session is unpaid | Not approved (202) |
| Paid amount lower than the course price | Not approved |
| No webhook secret configured | 503 (also in production mode) |

The webhook also re-queries PayMongo for the checkout session before approving,
so a forged event can't enrol anyone. **Not tested:** a live call to PayMongo's
servers. That needs your test keys and a public URL; do it on Hostinger with
the PayMongo test keys before the defense.

## R2.8 Production assets and `public/hot`

- `public/hot` was a **stale file left from `npm run dev`**. The Vite dev server
  was not running, so the local site was trying to load CSS/JS from
  `localhost:5173`. **I deleted it.** It is git-ignored and recreated
  automatically the next time you run `npm run dev`.
- `.gitignore` excludes `/public/hot`, `/public/build` and `/public/storage`.
  A git-based deployment never carries `hot`. **If you upload a ZIP through the
  File Manager, make sure `public/hot` is not inside it.**
- In production mode (`APP_ENV=production`, `APP_DEBUG=false`) the login page
  loads the hashed CSS/JS from `public/build` (HTTP 200) with no reference to
  port 5173. The 404 page and the certificate lookup page show no stack traces
  or SQL errors.
- `public/build` is not in git, so it must be built (`npm run build`) and
  uploaded, or built on the server if the plan has Node.

## R2.9 Backup and restore (verified)

1. `php artisan backup:run --only-db` on the real `pathwise` MySQL database
   (read-only `mysqldump`) produced a 26 KB zip in
   `storage/app/private/<APP_NAME>/` (git-ignored).
2. The SQL dump was imported into a **new, separate database
   `pathwise_restore_test`**.
3. All **34 tables / 391 rows matched** the live database (0 mismatches).
4. `pathwise_restore_test` was dropped, and my extracted copy of the dump was
   deleted.

On Windows, `mysqldump` must be on the PATH (`docs/audit/DEPLOYMENT_NOTES.md`).
`BACKUP_ARCHIVE_PASSWORD` is not set, so backup zips are not encrypted. Set it
on the server; backups contain personal data.

## R2.10 Rollback instructions

**Your working version is preserved.** `Berdin` still points at `277310b`.
Everything from this round exists only as uncommitted changes in the working
tree on `finalization`.

| To undo | Command |
|---|---|
| Only the dependency update | `git checkout -- composer.lock` then `composer install` |
| Only one code fix | `git checkout -- <file>` (files in R2.3) |
| Everything from this round, keeping your Create Account work | `git checkout -- composer.lock app/Http/Controllers/CourseController.php app/Http/Controllers/StudentProgressController.php resources/views/Student/certificate-view.blade.php resources/views/Teacher/analytics.blade.php resources/views/Teacher/submissions/show.blade.php resources/views/departments/index.blade.php` then `composer install` and `npm run build` |
| Go back to `Berdin` | `git switch Berdin`. Uncommitted changes come along, so commit or stash them first |
| Production (after deployment) | Before each deployment: back up the database (`backup:run --only-db`) and keep the previous release folder/ZIP. To roll back: restore the previous files, `php artisan optimize:clear`, and import the backup only if a migration changed data |

A copy of the original `composer.lock`, `composer.json` and `package-lock.json`
is in the session scratchpad.

## R2.11 Manuscript recheck

`docs/audit/MANUSCRIPT_REVISIONS_DECEMBER.md` (new; the October file is
untouched) maps every correction to the **December** PDF:

- **A. 25 items still present in the December text**, with page numbers. The
  most important: the ML architecture on pp. 48–50, FR c and Table 24
  "Publish/Unpublish" (pp. 102–103), and the stub Payments and Course_Reviews
  tables (pp. 91, 94).
- **B. December removed requirements the system still implements.** FRs stop at
  (p): AI quiz generation, output submission/AI evaluation and course reviews
  have no requirement, although Objectives 2.3/2.4 promise them. Proposed FR
  q–t wording is included. **Team decision.**
- **C. New December-only issues:** registration says "redirect to dashboard"
  (p. 66) but the use case on p. 52 says verification link; "Windows 10 and
  above" (p. 101); certificate/AI "settings" wording (pp. 61, 70); and
  numbering of the List of Tables and Figures.

The recheck **after** the team edits the PDF still has to be done; I can run
the same comparison on the corrected file.

## R2.12 Before deployment: still open

| # | Item | Blocks deployment? |
|---|---|---|
| D-1 | **Hostinger plan type**, needed for the exact procedure | **Yes** |
| D-2 | Production `.env` on the server: `APP_NAME=PathWise`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, `SESSION_SECURE_COOKIE=true`, DB, SMTP (R2.6), `GEMINI_*`, `PAYMONGO_*` including **`PAYMONGO_WEBHOOK_SECRET`**, `BACKUP_ARCHIVE_PASSWORD`, `BACKUP_NOTIFICATION_EMAIL` | **Yes** |
| D-3 | PHP upload limits on the host (R2.5) | No, but large uploads fail |
| D-4 | Cron for `schedule:run` (backups) | No, but there are no automatic backups without it |
| D-5 | Live PayMongo test payment and webhook on the real URL (test keys) | Yes for paid courses |
| D-6 | Team applies the manuscript corrections, then a recheck | No (defense risk) |
| D-7 | Your approval to commit this branch (suggested: three commits: Create Account page, dependency update, finalization fixes and tests) | Yes, before merge/push |

### Manual actions

- M-1 Confirm the Hostinger plan.
- M-2 Create the production mailbox and the PayMongo webhook; put the secrets in the server `.env` only.
- M-3 Set the local Herd upload limit to 25 MB if you test large uploads locally.
- M-4 Run `npm run dev` again when you develop locally; `public/hot` comes back automatically.
- M-5 Review and approve the commits (D-7).
- M-6 Assign each Administrator (Department Head) account to a department (Super Admin → Departments → Assign). Otherwise their Student Removal Requests page shows "must be assigned to a department".

---

# Round 1 — Audit (kept for history)

---

## 1. Baseline (Phase 1)

| Item | Value |
|---|---|
| Branch / commit | `Berdin` / `277310bb0e70ed8eec5a06b39f7147271c703e4a` |
| Uncommitted (kept, not touched) | `UserManagementController.php` (new `create()`), `users/index.blade.php` (form moved to its own page), `routes/web.php` (`users.create` route), new `users/create.blade.php`. 5 untracked PNG screenshots in the root. |
| WIP status | The new Create Account page works: it opens, keeps input after a validation error, and is blocked for every other role (new tests). Ready to commit. |

| Stack | Manuscript | Installed | OK? |
|---|---|---|---|
| Laravel | 13 | 13.15.0 | Yes. Update advised, see §3 |
| PHP | 8.3 | `^8.3` required at the time; 8.4.22 locally. **Superseded Nov. 5: PHP 8.4 required** | No (see Update) |
| Livewire | 4 | 4.3.1 | Yes. Update advised |
| Flux UI | 2.13 | 2.14.1 | Yes (newer minor) |
| Tailwind CSS | v4 | 4.x via `@tailwindcss/vite` | Yes |
| Chart.js | yes | 4.5.x | Yes |
| Vite | yes | 8.x | Yes |
| MySQL | yes | MySQL (`.env`) | Yes |
| Gemini | `gemini-2.5-flash` | `GEMINI_MODEL=gemini-2.5-flash` | Yes |
| DomPDF | yes | barryvdh/laravel-dompdf 3.x | Yes. Update advised |
| Fortify (2FA, passkeys) | yes | 1.37.2: registration, reset, email verification, 2FA, passkeys | Yes |
| PayMongo | yes | Direct API + signed webhook | Yes |
| Composer / Node | — | 2.10.1 / 24.16.0 | — |

## 2. Functional completeness (Phase 2 + 3)

Evidence is the automated feature test that exercises the workflow against the
database, not just a page loading.

### Functional Requirements (manuscript p. 102)

| FR | Requirement | Status | Evidence |
|---|---|---|---|
| a | Log-in | COMPLETE | `Auth/AuthenticationTest`, `TwoFactorChallengeTest`; each role goes to its own dashboard (`DashboardSummaryCardsTest`) |
| b | Register (student/teacher) | COMPLETE | `Auth/RegistrationTest`; role limited to `student`/`teacher` in `CreateNewUser` |
| c | Course management | COMPLETE (see MR-24) | Teacher create/edit/submit, `CourseTemplateTest`, `CourseApprovalWorkflowTest`. Teachers do **not** publish or unpublish. The admin's approval publishes. |
| d | Enrollment / marketplace | COMPLETE | `MarketplaceEnrollmentTest`, `EnrollmentAndPaymentTest` (free, paid, cancelled, invitation) |
| e | Lessons (text, PDF, video) | COMPLETE | `PrivateFileAccessTest` (files private, enrolled only), lesson types text/document/video |
| f | Quiz | COMPLETE | `QuizScoringTest`, `QuizTimeLimitTest` |
| g | Automatic score computation | COMPLETE | `QuizScoringTest` (1 point per correct answer, MR-8) |
| h | Certificate generation | COMPLETE | `CertificateNumberTest`, `CertificateTemplateTest`, `CertificateVerificationTest` |
| i | AI recommendation (hybrid, fallback) | COMPLETE (see MR-25) | `RecommendationSelectionTest`, `GeminiFallbackModelTest`, `GeminiSecurityTest` (rules choose the course, Gemini writes the reason, fixed text when Gemini fails) |
| j | Progress tracking | COMPLETE | `StudentMyCoursesTest`, `QuizRetakeEnrollmentTest`, teacher student-progress pages |
| k | Course approval | COMPLETE | `CourseApprovalWorkflowTest` (approve, return with feedback, locked while pending) |
| l | Category management | COMPLETE | `AdminPagesTest` |
| m | Payment processing | COMPLETE | `EnrollmentAndPaymentTest`, `PayMongoMethodUsedTest`, `PaymentMethodsTest`, `TransactionRecordTest` (checkout, webhook signature, manual approve/reject) |
| n | User management | COMPLETE | `UserManagementTest`, `AccountDeactivationTest`, `SuperAdminAccountsAndDashboardTest` |
| o | Report generation | COMPLETE | `ReportChartsTest`, `EngagementReportTest`, PDF export |
| p | Notifications | COMPLETE | `NotificationsTest`, `EmailNotificationsTest` |

### Table 24 modules (p. 103–104)

All modules exist, including **Forgot Password** (`PasswordResetTest`) and
**Retake Quiz** (`QuizRetakeEnrollmentTest`). One exception:
**Publish/Unpublish Course (Teacher)** is INCOMPLETE as worded. Teachers submit,
the administrator publishes, and a teacher cannot take a published course
down. Following the code, this is listed as a manuscript revision (MR-24). If
the panel expects teachers to unpublish, that is a small feature and needs
your decision (F-4).

### Dashboards (Fig. 35–38)

| Dashboard | Summary cards in manuscript | Status |
|---|---|---|
| Super Admin | Total Users, Pending Role Assignments, Payments to Verify, System Status, Recent Activity | COMPLETE |
| Administrator | Course Categories, Courses for Approval, Certificates Issued, Active Learning Paths | COMPLETE |
| Teacher | My Courses, Total Lessons, Enrolled Students, Avg. Quiz Score | COMPLETE |
| Student | Enrolled Courses, Quizzes Taken, Certificates Earned, Average Score | COMPLETE |

### Data dictionary (Tables 4–24)

COURSE_TEMPLATES, ASSIGNMENTS, SUBMISSIONS, TRANSACTIONS, CERTIFICATES,
COURSE_REVIEWS, LEARNING_PATHS, LEARNING_PATH_COURSES and AI_RECOMMENDATIONS
exist and migrate cleanly. **PAYMENTS (Table 18)** was dropped on purpose
(commit `00be981`). The manuscript still shows it as a stub with only id and
timestamps. Remove it from the paper (MR-12). Course_Reviews (Table 21) is
also a stub in the paper; its real columns are in MR-26.

### Out of manuscript scope (kept, not removed)

| Feature | Recommendation |
|---|---|
| Assignments and code submissions with AI evaluation (Java/Python …) | Keep. Tables 16–17 exist in the manuscript; document the code languages (MR-17) |
| Departments management and assignment | Keep; document as department scoping (MR-13) |
| Student removal requests | Keep; mention under Teacher/Administrator |
| Course reviews | Keep; the MR-29 to MR-36 wording is ready |
| Public certificate verification page | Keep (MR-18) |
| Self-deactivation of an account (no permanent deletion) | Keep (MR-15) |

## 3. Security (Phase 4)

| Check | Result |
|---|---|
| Cross-account access (IDOR) | **PASS.** New `CrossAccountAccessTest`, 141 assertions. Another teacher gets 403/404 on 15 pages and 22 actions on someone else's course, lessons, quizzes, questions, assignments, submissions, grades, invitations and removal requests. Another student cannot open certificates, transactions, learning paths, lessons, quizzes or private files, or submit quizzes, mark lessons complete or upload proof. Teachers and students get 403 on 16 admin/super-admin pages and 8 actions. Admins get 403 on super-admin pages. Each test also checks that nothing changed in the database. |
| Role middleware | `role:` on every role group. Public routes are only home, invitation preview, `/verify-certificate` (throttled), PayMongo webhook (signature-checked, CSRF-exempt by design), `/up`, and framework assets |
| Deactivated accounts | Blocked at login and logged out on the next request (`AccountDeactivationTest`) |
| Private files | Receipts, submissions and lesson files are on the private disk, served after an access check, with `nosniff` and `no-store` |
| Upload validation | Proofs: image, jpg/png, 5 MB. Lesson files: 20 MB. Code files: 25 MB, with language checks |
| XSS | 5 `{!! !!}` uses, all safe (lesson text is `e()` before `nl2br`, a QR SVG, fixed icons, a date) |
| Secrets | Gemini and PayMongo keys are only in `.env`, never in views or built JS. `.env` and `*.sql` are git-ignored. Tests blank the keys |
| CSRF / mass assignment | CSRF on all forms except the webhook. Models use `$fillable` |
| **Dependencies** | **FINDING F-1 (P1).** `composer audit --no-dev` found 37 advisories in 9 production packages. **laravel/framework** critical (TokenGuard bypass, not reachable because no API token guard is used; fixed in ≥ 13.29). **symfony/http-foundation** high (ReDoS through the `Cache-Control` header, reachable by any request). **guzzlehttp/guzzle** high (host-check bypass; used for the Gemini and PayMongo calls). league/commonmark high (not reachable: no Markdown is rendered). dompdf, livewire, psr7, mime, flysystem medium/low. `npm audit`: 0. |

## 4. Database integrity (Phase 5)

- `migrate:fresh --seed` ran on an **isolated SQLite file** in the scratchpad
  directory: 52 migrations ran, all seeders succeeded. The real MySQL
  `pathwise` database was not touched. There is no config cache, so the
  SQLite override was honoured.
- The test suite runs every test on in-memory SQLite; `tests/TestCase.php`
  refuses any other database.
- No duplicate or conflicting migrations.
- Backups: `spatie/laravel-backup` is scheduled (daily DB at 01:00, weekly
  full at 02:00, cleanup at 03:00; `BackupScheduleTest`). The restore
  procedure is in `docs/audit/DEPLOYMENT_NOTES.md` ("Testing a backup restore
  on your laptop"). It needs the server cron (§8).
- **F-2 (P2) Upload limits:** the local PHP CLI has `upload_max_filesize=2M`
  and `post_max_size=8M`, while the app accepts up to 20–25 MB. On Hostinger,
  set `upload_max_filesize ≥ 25M` and `post_max_size ≥ 30M` in hPanel
  (PHP Configuration), or large lesson and code uploads will fail with a
  generic error.

## 5. Automated checks (Phase 7)

| Check | Result |
|---|---|
| Full Pest suite | **556 passed, 2269 assertions, 0 failed** (212 s), run with the uncommitted WIP in place |
| New `CrossAccountAccessTest` | **5 passed, 141 assertions** |
| `route:list` | 249 routes. No unintended public routes |
| Migrations (isolated SQLite) | 52/52 ran, with seeders |
| `npm run build` | Success (8.4 s) |
| `composer validate` | Valid |
| `composer audit` | 38 advisories / 10 packages (37 / 9 production). See F-1 |
| `npm audit` | 0 vulnerabilities |
| Coverage | Not measured |

No failures existed before this work, and none were introduced.

## 6. UI and responsive

**NOT VERIFIED.** I did not open the pages in a browser at desktop, tablet or
phone widths in this pass. `PagesRenderTest` and `LightModeOnlyTest` confirm
that every role's pages render, but not how they look. The recent commits
(`12f6ad9`, `687c68d`, `596d635`) moved the forms onto shared styles. A
visual pass at 1280 / 768 / 390 px is still to do (F-6).

## 7. Performance observations

Not measured. Points to note: the PayMongo success page waits up to about
1.2 s (3 tries) for PayMongo to confirm a payment, and Gemini quiz generation
has a time limit (`ee2a2e0`). `rubix/ml` is installed but no code uses it.
Removing it makes the upload to Hostinger smaller (F-7, P3).

## 8. Hostinger compatibility

**Blocked: I need the plan type** (shared/Business, Cloud, or VPS). SSH,
Node, cron and PHP selection differ by plan, so the step-by-step commands are
not written yet. Already known for any plan:

| Item | Requirement |
|---|---|
| PHP | **8.4** selected in hPanel (8.3 will not work). Extensions: pdo_mysql, mbstring, openssl, gd, zip, fileinfo, intl, bcmath |
| Document root | Must point to `public/` |
| `.env` | `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY`, `APP_URL=https://…`, `SESSION_SECURE_COOKIE=true`, `DB_*`, `MAIL_*` (real SMTP: needed for email verification and password reset), `GEMINI_API_KEY`, `GEMINI_MODEL`, `PAYMONGO_SECRET_KEY`, `PAYMONGO_PUBLIC_KEY`, `PAYMONGO_WEBHOOK_SECRET`, `PAYMONGO_PAYMENT_METHODS`, `BACKUP_NOTIFICATION_EMAIL` |
| PayMongo | Decide test or live keys for the defense. Create the webhook for `https://<domain>/paymongo/webhook` and copy its secret. **`PAYMONGO_WEBHOOK_SECRET` is not set in the local `.env`.** |
| Queue | Nothing is queued (no `ShouldQueue`), so mail is sent immediately. No worker needed. `QUEUE_CONNECTION=sync` is fine |
| Scheduler | Cron every minute: `php artisan schedule:run` (backups) |
| Assets | `public/build` is git-ignored. Build with `npm run build` (locally if the plan has no Node) and upload it. **Delete `public/hot`.** It exists now and would make the live site load assets from `localhost:5173` |
| Storage | `php artisan storage:link`. If `symlink()` is disabled, a fallback is needed (plan-specific) |
| Caches | `config:cache`, `route:cache`, `view:cache`, `event:cache` after every `.env` change |

## 9. Remaining deployment blockers

| ID | Item | Priority | Blocks deployment |
|---|---|---|---|
| F-1 | Update vulnerable PHP packages | P1 | Yes (security) |
| F-3 | Hostinger plan type unknown, so no deployment runbook yet | P1 | Yes |
| F-5 | Production SMTP and PayMongo webhook secret | P1 | Yes. Registration needs email verification |
| F-2 | PHP upload limits on the host | P2 | No, but large uploads fail |
| F-6 | Visual responsive pass | P2 | No |

## 10. Manual actions for the team

1. Approve the dependency update (F-1) and a working branch (`finalization`).
2. Tell me the Hostinger plan.
3. Create production SMTP credentials and a PayMongo webhook. Put them in the
   server `.env` only.
4. Commit the uncommitted Create Account page (it is tested and working).
5. Apply the manuscript revisions below before the defense.

## 11. Manuscript revisions still needed

The December PDF still contains these items from `MANUSCRIPT_REVISIONS.md`,
which has the exact replacement wording. Page numbers are the printed numbers.

| Page | Passage | Problem | MR |
|---|---|---|---|
| 48–50 | AI System Architecture (Fig. 19): Random Forest Regression/Classifier, Isolation Forest, "machine learning models", Learning Analytics Database | None of these exist. Recommendations are rule-based with a Gemini explanation. **Highest defense risk.** | MR-1, MR-2, MR-3, P1-13 |
| 102 | FR c: "teachers can … publish courses"; Table 24 (p. 103) "Publish/Unpublish Course" | The administrator's approval publishes the course | MR-24 |
| 102 | FR i: "personalized learning path recommendations using a hybrid rule-based and AI approach" | Describe the rules plus Gemini explanation, and the rule-based path | MR-25 |
| 91–92 | Table 18 PAYMENTS (stub) | Table dropped. Remove it and renumber | MR-12 |
| 94–95 | Table 21 COURSE_REVIEWS (stub with only id/timestamps) | Add the real columns | MR-26 |
| 77–96 | Data dictionary columns | Missing fields and wrong "Allow Nulls" | MR-6 to MR-11 |
| 68 | Super Admin logic step 3: "add, edit or remove user accounts" | Accounts are deactivated, never removed | MR-15 |
| 66 vs 52 | Registration step 5 "redirect to dashboard" vs Create Account use case "verification link" | Contradictory. The system sends a verification link first; fix step 5 | new |
| 61, 70 | "configure certificate templates" / "Manage AI Recommendations … configure settings" | One editable template (title, body, signatory). AI records can be listed, added and removed, but settings are not configurable | MR-18, P2-18 |
| 98 | Network Topology: "GCash/Maya payment gateway" | The gateway is PayMongo (GCash, Maya, card) | MR-5, MR-20 |
| 101 | "Running in an Operating System such as: Windows 10 and above" | Production runs on Hostinger (Linux). Say the server runs Linux and clients use any modern browser | new |
| viii–ix | List of Tables numbering (data dictionaries listed as 6–24, body uses 4–24; Table 1 named "Name"); Fig. 26 page "5" | Formatting | new |
| 5–6 | Scope: course reviews, upload limits | Not mentioned | MR-16, MR-29 |

---

## Final checklist

| # | Item | Status | Evidence | Action | Blocks? |
|---|---|---|---|---|---|
| 1 | FR a–p implemented | COMPLETE | §2, 556 tests | — | No |
| 2 | Role access and cross-account access | COMPLETE | `CrossAccountAccessTest` | — | No |
| 3 | Deactivated users blocked | COMPLETE | `AccountDeactivationTest` | — | No |
| 4 | Private files | COMPLETE | `PrivateFileAccessTest` | Run `files:make-private` on the server if old files exist | No |
| 5 | Fresh migration and seed | COMPLETE | Isolated SQLite, 52/52 | — | No |
| 6 | Frontend build | COMPLETE | `npm run build` | Delete `public/hot` before upload | No |
| 7 | PHP dependency advisories | INCOMPLETE | `composer audit` | Approve F-1 | **Yes** |
| 8 | Hostinger runbook | NOT VERIFIED | — | Tell me the plan | **Yes** |
| 9 | SMTP and PayMongo webhook in production | NOT VERIFIED | Local `.env` lacks the webhook secret | Team | **Yes** |
| 10 | Upload limits on host | NOT VERIFIED | Local CLI 2M/8M | hPanel | No |
| 11 | Backup cron on host | NOT VERIFIED | `BackupScheduleTest` | Add cron | No |
| 12 | Responsive UI pass | NOT VERIFIED | — | F-6 | No |
| 13 | Teacher unpublish (Table 24) | INCOMPLETE as worded | §2 | Decide: revise paper (recommended) or build | No |
| 14 | Manuscript matches system | INCOMPLETE | §11 | Team edits the paper | No (defense risk) |
| 15 | Uncommitted Create Account page | COMPLETE, uncommitted | New test | Commit | No |
