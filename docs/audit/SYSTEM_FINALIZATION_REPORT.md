# PATHWISE — System Finalization Report

Inspection date: November 6, 2026 · Branch `finalization` (see section F for the current HEAD)
(local commits only, not pushed) · `Berdin` = `origin/Berdin` = `277310b`.

Scope: the Laravel system only. The manuscript was not edited. **No code was
changed for this report**; it records the inspection of the uncommitted B1–B5
work and of the whole application, and proposes the next steps. Nothing was
staged, committed, pushed, merged, deployed or reset.

> **Read section F first (system freeze, current).** Section 0 records the
> earlier approved fixes; sections 1–8 are the original inspection (kept for
> traceability).

## F. System freeze status (November 6, third pass)

### F.1 Approved and done in this pass

| Item | Commit | Result |
|---|---|---|
| **P1** type documentation and parameter types | `415d268` | PHPDoc only (validated request arrays, Gemini/PayMongo responses, notification channels, query scopes and builders, template structure, two untyped parameters). No runtime change |
| **P2** behaviour-neutral fixes | `1a5efb9` | Explicit null checks; integer ids for `findOrFail()` (ids from requests also validated as `integer`, so malformed input such as an array is rejected instead of causing a server error); `associate()` for relations; `getTimestamp()`; removal of null checks the foreign keys already guarantee; `new self()`; explicit `default` arm on an already-validated `match`; seeder GD colour failure handled and enrollment rows destructured in the loop; Dashboard activity types documented. **Kept on purpose:** the `is_array()` guard on notification data (protects against malformed stored data) |
| **L-1** unique transaction numbers | `95c5479` | Same format `TRX-YYYYMMDD-00001`; the next number continues from today's highest number and the insert is retried (up to 5 times) if another purchase took that number first. Existing records unchanged. New `TransactionNumberTest` (4 tests): the simultaneous-purchase and gap tests **failed with HTTP 500 on the old code** and pass now |
| **P3** controller return types (194) | — | **Deferred until after the defense** (approved). Outstanding CI work |
| Branded error pages, lock-rule consolidation | — | Deferred |

### F.2 Verification after the last code commit (`95c5479`)

| Check | Result |
|---|---|
| Full test suite | **598 passed, 0 failed, 2,536 assertions** (594 + 4 L-1 tests) |
| PHPStan level 7 (`composer types:check`) | **195 errors**: 194 `missingType.return` (deferred P3) + 1 `function.alreadyNarrowedType` (the notification guard kept on purpose). History: 553 → 297 → 255 (P1) → 196 (P2) → 195 (L-1). **CI's type-analysis step still fails** until P3 is done |
| `composer audit` / `npm audit --omit=dev` | No advisories / 0 vulnerabilities |
| Production build | Success |
| **Browser regression** (separate database `pathwise_audit`, created and dropped; `APP_NAME=PathWise`, `APP_TIMEZONE=Asia/Manila`, log mailer, PayMongo test keys, **no Gemini key**) | **Crawl:** 203 pages (Super Admin 36, Administrator 82, Teacher 52, Student 33): 0 server errors, 0 JavaScript errors, 0 overflow at 390/1280 px; only the 3 PDF downloads are reported by the crawler. The Administrator removal-requests page now loads (was 403). **Workflows:** registration and email verification, enrollment, quiz scoring, recommendation (fallback text without Gemini), course completion, certificate issue and PDF, review, teacher course building, upload limit, submit for approval, locked-page redirect (B3), administrator approval and notification, return for revision with remarks, essay submission and teacher grading, invitations, removal requests and departments (B2), account creation/deactivation, report and certificate PDFs, PayMongo test checkout with the new number `TRX-20261106-00001`, unpaid-session and bad-signature webhooks, cancel link, review hiding, password reset, published-course lock: **all pass**. Without a Gemini key, AI quiz generation and AI evaluation fail **gracefully** (no server error; the evaluation is marked `failed` and the teacher grades manually) |
| Script-only differences (not system defects) | "BUG CHECK: admin without department … 403" now gets 200, which confirms the B2 fix; lesson count measured before the quiz lesson completes; the verification check used the wrong query parameter (`number` is correct; verified separately on November 6); four scripts had course ids from the previous database and were rerun with the correct ids (all pass) |

### F.3 Remaining

| Item | Type | Owner | Blocks |
|---|---|---|---|
| Exposed database export (F-1): visibility, affected person, demo passwords, history decision | Security | Group (+ your approval for any history rewrite) | **Deployment** |
| P3: 194 controller return types | CI | You (approval after the defense) | Green CI only |
| Teacher self-registration (F-5) | Decision | Adviser | No |
| Branded error pages (F-6), lock-rule consolidation (F-9) | Polish | You | No |
| Local `APP_NAME=PathWise` (F-8) | Configuration | You | No |
| Hostinger prerequisites and live checks V-1 to V-18 (section 5, deployment guide) | Deployment | Team + separate approval | **Deployment** |

No known critical or high **application** defect remains.

## 0. Status after the approved fixes (November 6, second pass)

### 0.1 What was approved and done

| # | Approved item | Done | Files |
|---|---|---|---|
| 1 | **Timezone (F-2)** configurable, `Asia/Manila` in production; certificate dates and scheduled tasks follow the Laravel timezone; stored timestamps untouched; tests around midnight | Done. `config/app.php`: `'timezone' => env('APP_TIMEZONE', 'UTC')` (default unchanged, so no existing local timestamp is reinterpreted until `APP_TIMEZONE` is set). Certificates already use `now()`, and the scheduler uses `config('app.timezone')`, so no other code change was needed | `config/app.php`, `.env.example` (`APP_TIMEZONE=UTC` + comment), `tests/Feature/Pathwise/ApplicationTimezoneTest.php` (new, 6 tests) |
| 2 | **PHPStan / CI (F-3)** memory; the 5 new errors; model relation return types; no suppression | Done. `types:check` now runs `phpstan analyse --memory-limit=1G`. Relation return types and `@return Relation<Model, $this>` generics added to every relation in 22 models (bodies unchanged; imports sorted). `LessonController::redirectIfLessonsLocked(): ?RedirectResponse`; generic `Builder<StudentRemovalRequest>` on the scope. No baseline, no ignore comments, level 7 kept | `composer.json`, `app/Models/*.php` (22), `app/Http/Controllers/LessonController.php` |
| 3 | **Off-site backups (F-4)** weekly procedure | Done: section 9.1.1 of the guide (who/when, find, download, SHA-256 check, encrypted storage in two places, retention 4 weekly + 3 monthly, monthly test restore, log table, failure steps). Also `APP_TIMEZONE` in the production `.env`, a corrected scheduler note, and checklist item V-18 | `docs/DEPLOYMENT_HOSTINGER.md` |
| 4 | **Git protection (F-7)** | Done after checking: no `.docx` and no root `.png` is tracked (the 5 tracked PNGs are `public/` logos/icons). Added `*.docx` and `/*.png` (root only). `git check-ignore` confirms the manuscript and the 5 screenshots are ignored and the `public/` images are **not**. After the decision not to commit the evidence, `/docs/audit/evidence/` and the superseded `/docs/audit/MANUSCRIPT_REVISIONS_DECEMBER.md` were also added, so both stay local | `.gitignore` |
| 5 | **Security (F-1)** remains a blocker; exact steps for the group; no history rewrite | Done: ready-to-send message with numbered steps (section 5a) | `docs/audit/SECURITY_DUMP_EXPOSURE.md` |
| 6 | Branded error pages, lock-rule consolidation | **Deferred** as instructed | — |

### 0.2 Verification (run one at a time, after all changes)

| Check | Result |
|---|---|
| Full test suite | **594 passed, 0 failed, 2,520 assertions** (588 + 6 timezone tests) |
| Targeted: timezone, backup schedule, certificate numbers | 11 passed |
| `composer audit` | No advisories |
| `npm audit --omit=dev` | 0 vulnerabilities |
| `composer validate` | Valid (no lock warning; scripts are not part of the lock hash) |
| `npm run build` | Success |
| `composer types:check` (exactly as CI) | Runs without crashing; **297 errors** (was 553 and a memory crash); **0 errors on any line changed today** |
| Pint on changed files | Same pre-existing warnings as the committed versions; none added (User.php lost one) |
| Timezone behaviour | `APP_TIMEZONE` unset (local now): `UTC`. Next database backup with `UTC` = 01:00 UTC = **09:00 Manila**; with `APP_TIMEZONE=Asia/Manila` = **01:00 Manila**. Tests: 00:30 PH → that PH day; 23:59 PH → same day; 00:30 PH on Jan 1 → new year in date and number (`PATH-2027-…`); UTC default keeps UTC dating |

### 0.2a Commits created (November 6)

| # | Hash | Commit |
|---|---|---|
| 9 | `a43f6d6` | Route student removal requests so none is left without a reviewer |
| 10 | `9cf56bc` | Redirect teachers from locked lesson pages with an explanation |
| 11 | `14aaec6` | Make the application timezone configurable for Philippine time |
| 12 | `83ac58c` | Add relation return types and give PHPStan enough memory |
| 13 | `d51a605` | Keep the manuscript, root screenshots and local audit files out of Git (adds `/docs/audit/evidence/` and the superseded December notes to the approved `*.docx` and `/*.png`) |
| 14 | *(this document's commit)* | Add final validation, security and finalization documents, after a sensitive-content review: the security documents were sanitized (no account emails, ids, commit hashes, file names or repository address of the exposed data; no password strings) |

### 0.3 Remaining PHPStan errors (pre-existing, reported separately)

297 errors in code not changed today. By type: 194 `missingType.return`
(controllers without return types), 28 `missingType.iterableValue`, 19
`argument.type`, 9 `property.notFound`, 8 `missingType.generics`, 7
`nullsafe.neverNull`, 6 `argument.templateType`, 5 `method.notFound`, 5
`assign.propertyType`, 3 `binaryOp.invalid`, 2 `missingType.parameter`, 2
`identical.alwaysFalse`. Most in `CourseController` (26),
`AssignmentController` (23), `QuizController` (22), `SubmissionController`
(18), `LearningPathController` (17), `LessonController` (16, none on changed
lines), `TransactionController` (16).

The ones that could indicate logic problems were read:
`TransactionController.php` ~1516 (`progress_percentage === null`) and ~1664,
`QuizController.php` ~1353/~1586, `DemoDataSeeder.php` ~192. **None is a
confirmed runtime bug**: the values are cast at runtime, and the seeder's
"never true" branch demonstrably runs (seeded certificates exist). The
`property.notFound`/`method.notFound` items come from `findOrFail($request->id)`
being typed as "model or collection". All need a later typing batch (controller
return types first). **CI's test job will still fail at type analysis until
these are fixed**; the application is not affected.

### 0.4 Status of findings

| ID | Severity | Status |
|---|---|---|
| F-1 Exposed dump | Critical | **Open: group action** (message ready). Blocks deployment |
| F-2 Timezone | Medium | **Resolved in code**; production must set `APP_TIMEZONE=Asia/Manila` before real data (guide + V-18) |
| F-3 PHPStan/CI | Medium | **Partly resolved**: memory fixed, 0 new errors, 553 → 297. Remaining are pre-existing (0.3). CI red until fixed |
| F-4 Backups | Medium | **Resolved (procedure)**; performing it is an operational task after deployment |
| F-5 Teacher self-registration | Decision | Open (adviser) |
| F-6 Branded error pages | Low | Deferred |
| F-7 Accidental commits | Low | **Resolved** (`.gitignore`) |
| F-8 Local `APP_NAME` | Low | Open (your `.env`) |
| F-9 Lock-rule duplication | Low | Deferred |
| F-10 Pint style | Low | Unchanged (CI auto-fixes) |
| F-11 Proxies | Info | Check at deployment |

No critical or high **code** defect is known. Deployment remains blocked by
F-1 (group), the Hostinger prerequisites (section 5: D-3 to D-16) and the live
checklist (V-1 to V-18).

### 0.5 Proposed commit plan (not created; exact paths only)

The commits touch separate files and should pass on their own, but only the
combined working tree has been tested (594/594); the full suite is run again
after the last commit (per-commit runs on request). `StudentRemovalRequest.php`
contains both the B2 rule and its relation types, and `LessonController.php`
both the B3 redirect and its return type, so each goes whole into its
feature commit (no partial staging).

| # | Message | Files | Content |
|---|---|---|---|
| 9 | Route student removal requests so none is left without a reviewer | `app/Models/StudentRemovalRequest.php`, `app/Http/Controllers/StudentRemovalRequestController.php`, `resources/views/student-removal-requests/index.blade.php`, `tests/Feature/Pathwise/RemovalRequestRoutingTest.php` | Reviewer rule (scope + check), campus-wide admins, teacher message, page heading; relation types of that model; 6 tests |
| 10 | Redirect teachers from locked lesson pages with an explanation | `app/Http/Controllers/LessonController.php`, `resources/views/Teacher/lessons.blade.php`, `tests/Feature/Pathwise/LockedLessonPagesTest.php` | Owner redirect with message (pending/published), error flash on the lesson list, `?RedirectResponse` type; 7 tests |
| 11 | Make the application timezone configurable for Philippine time | `config/app.php`, `.env.example`, `tests/Feature/Pathwise/ApplicationTimezoneTest.php` | `APP_TIMEZONE` (default UTC); example value and warning; 6 tests (midnight, New Year, scheduler) |
| 12 | Add relation return types and give PHPStan enough memory | `composer.json`, the other 21 files in `app/Models/` (`AIRecommendation`, `Assignment`, `Certificate`, `CertificateSetting`, `Course`, `CourseCategory`, `CourseInvitation`, `CourseReview`, `CourseTemplate`, `Department`, `Enrollment`, `LearningPath`, `Lesson`, `LessonProgress`, `Quiz`, `QuizQuestion`, `QuizResult`, `Role`, `Submission`, `Transaction`, `User`) | Return types and generic `@return` docs on relations; sorted imports; `--memory-limit=1G` |
| 13 | Keep the manuscript and root screenshots out of Git | `.gitignore` | `*.docx`, `/*.png` |
| 14 | Add final validation, security and finalization documents | `docs/audit/FINAL_VALIDATION_AUDIT.md`, `docs/audit/SECURITY_DUMP_EXPOSURE.md`, `docs/audit/SYSTEM_FINALIZATION_REPORT.md`, `docs/DEPLOYMENT_HOSTINGER.md`, `docs/audit/MANUSCRIPT_REVISIONS_NEW_COMPILATION.md` | Audit, security steps and message, this report, guide (outbound, quoting, timezone, off-site backups, V-18), manuscript revision section H |
| ~~15~~ | **Not approved.** The evidence stays local and is ignored by Git | — | — |

**Never committed:** `.env`, `*.sql` exports, the manuscript `.docx`, the
5 root screenshots (all now ignored), `docs/audit/MANUSCRIPT_REVISIONS_DECEMBER.md`
(superseded; left untracked). Every commit ends with the project's
attribution line; nothing is pushed. Rollback: `git reset --soft 5257be3`
keeps all changes uncommitted; `git revert <hash>` undoes one commit.

---

**Original verdict (before the fixes above):** the application is functionally complete and stable locally
(588/588 tests, 0 dependency advisories, clean production build, all
workflows of the four roles verified in a browser on November 6). It is
**not yet deployment-ready**: one critical security action (exposed database
dump) is open, one medium defect affects certificate dates (timezone), CI's
type analysis fails, backups have no off-site copy, and the live Hostinger
checks have not been run.

---

## 1. Completed tasks

| Task | Result | Evidence |
|---|---|---|
| Phases 1–3 audit | 191 pages crawled (0 errors/overflow), 47 browser workflow checks, 51 manuscript images, 30 data dictionary tables | `FINAL_VALIDATION_AUDIT.md`, `docs/audit/evidence/2026-11-06-final-validation/` |
| B1 security documentation | Affected accounts by role only (sanitized), remediation procedure, message to the group, tracking table | `docs/audit/SECURITY_DUMP_EXPOSURE.md` |
| B2 removal-request routing (SYS-1) | No request without a reviewer; admins without a department are campus-wide; department boundaries kept | `app/Models/StudentRemovalRequest.php`, `StudentRemovalRequestController.php`, view, `RemovalRequestRoutingTest` (6) |
| B3 locked lesson pages (SYS-2) | Owner redirected with an explanation; saving still 403; other teachers 403 | `LessonController.php`, `Teacher/lessons.blade.php`, `LockedLessonPagesTest` (7) |
| B4 deployment guide | Outbound hosts, DNS, password rules, `APP_NAME`, `.env` quoting | `docs/DEPLOYMENT_HOSTINGER.md` |
| B5 manuscript revisions | Section H (N-1 to N-27) | `MANUSCRIPT_REVISIONS_NEW_COMPILATION.md` |
| This inspection | Tests, audits, build, PHPStan, Pint, caches, configuration, Git | Sections 4–6 |

### Review of the B1–B5 changes

| Change | Review result |
|---|---|
| `StudentRemovalRequest::scopeReviewableBy` / `isReviewableBy` | Correct. OR conditions are grouped inside `whereHas` (they cannot widen the instructor link). One rule serves both the list and the review action (no duplicate logic between them). `departmentsWithAdministrator()` runs one small query per page view; acceptable at this scale |
| `StudentRemovalRequestController` | The two former department checks (`abort_if(!$departmentId)` in the list and review) are replaced by the model rule. Store message tells a teacher without a department where the request goes. No other behaviour changed |
| Removal requests view | Heading and empty-state text depend on `$campusWide`; defaults safely if the variable is missing |
| `LessonController::redirectIfLessonsLocked` | Correct and limited to the owner, so the cross-account 403 behaviour is unchanged. **Duplicated logic (low):** the "draft or rejected" rule now appears in four places: `LessonController::ensureTeacherCanModifyCourse`, `LessonController::redirectIfLessonsLocked`, `CourseController::redirectIfCourseLocked`, `QuizController::ensureTeacherCanModifyCourse` (three were pre-existing). See F-9 |
| Lessons view | Adds an error flash block in the page's own style; success block untouched |
| Tests | 13 new tests; no existing test edited or deleted |
| Deployment guide (B4) | Accurate against the code (hosts confirmed in `GeminiQuizService`/`TransactionController`/Laravel's `NotPwnedVerifier`). **One inaccuracy found:** the note "The server's timezone may differ from Philippine time. Check with `date`" is misleading; Laravel schedules in the app timezone (`config/app.php`, UTC), not the server's (see F-2) |
| Security documentation (B1) | No passwords, hashes, tokens or full personal emails. Dump confirmed to contain no API keys, `APP_KEY` or webhook secret |
| Evidence folder | Passwords, tokens and the synthetic test email redacted in all text files (re-scanned). Screenshots contain only demo data and the synthetic test address. 3.1 MB |
| Unnecessary changes | None found. No feature added beyond the approved scope; branding untouched |

---

## 2. Remaining defects and findings

| ID | Severity | Feature / file | Evidence | Recommended action | Blocks finalization? |
|---|---|---|---|---|---|
| **F-1** | **Critical** | Git history: an old local database export (details held privately by the project lead) | `SECURITY_DUMP_EXPOSURE.md`: 5 accounts with bcrypt hashes, one belongs to a real person | Group completes the reset steps (section 3). History rewrite only with separate approval | **Yes** (before deployment) |
| **F-2** | **Medium** | Timezone: `config/app.php` line 68 `'timezone' => 'UTC'` (hard-coded) | Automatic certificates use `now()->toDateString()` (`QuizController.php` ~1949, `LessonController.php` ~574): a certificate earned 00:00–07:59 Philippine time gets **the previous day's date**. Backups scheduled "01:00" run at **09:00 PH time** (`routes/console.php`). 58 `->format(...)` date displays in views show UTC (8 hours behind) | Make the timezone configurable (`env('APP_TIMEZONE', 'UTC')`) and set `APP_TIMEZONE=Asia/Manila` on the server **before the first production data**; tests for the certificate date and the schedule; correct the guide's note. Decision needed for existing local data (section 7) | **Yes** (certificate accuracy) |
| **F-3** | **Medium** | CI: `.github/workflows/tests.yml` step `composer types:check` (PHPStan level 7, `phpstan.neon`) | Default run crashes at the 128 MB memory limit; with 1 GB: **553 errors** (237 missing return types, 107 `larastan.relationExistence`, 79 `property.notFound`, 30 `argument.type`, …), in 56 files, almost all pre-existing. 5 are on lines changed today (2 in `LessonController`, 3 in `StudentRemovalRequest`) | (1) Give PHPStan enough memory in the `types:check` script; (2) fix the 5 new ones; (3) add return types to model relation methods (removes most `relationExistence`/`property.notFound` errors, ~186), then controller return types in a later batch. Do **not** baseline or lower the level without your decision | No for the running system; **Yes** for a green CI on `main` |
| **F-4** | **Medium** | Backups: `config/backup.php` (`disks => ['local']`) | Daily/weekly backups are stored on the same server as the site; losing the server loses the backups | Document a weekly manual download of the latest backup to the group's storage (no code); optional second disk later (feature, needs approval) | **Yes** for deployment readiness (procedure only) |
| **F-5** | Medium (decision) | Registration: `app/Actions/Fortify/CreateNewUser.php` | Anyone can register as Teacher (courses still need admin approval) | Adviser decision (audit SYS-4); no change proposed now | No |
| **F-6** | Low | Error pages: `resources/views/errors/` does not exist | Production 403/404/419/500/503 show Laravel's plain pages (no PathWise branding, no link back). No information leak with `APP_DEBUG=false` | Optional: branded error pages using the existing layout and logo (no redesign) | No |
| **F-7** | Low | Untracked sensitive files not ignored: `docs/audit/PATHWISE-NEW-COMPILATION.docx`, 5 root `.png` files | `git status` shows them as `??` (not ignored); a `git add .` would include personal information | Add them to `.gitignore` or move them out of the project folder | No (but recommended before any commit) |
| **F-8** | Low | Local `.env`: `APP_NAME=Laravel` | Browser tabs read "… - Laravel" | Your action: set `APP_NAME=PathWise` locally | No |
| **F-9** | Low | Duplicated "course is editable" rule in 4 places (see section 1) | Code review | Optional refactor into one `Course` method; no behaviour change; covered by existing tests | No |
| **F-10** | Low | Code style: Pint reports 116 files | CI's lint job runs Pint in fix mode, so it does not fail | Optional separate formatting commit (large, cosmetic diff) | No |
| **F-11** | Info | Proxies: no `trustProxies` configuration | Only matters if the site sits behind a proxy/CDN (e.g. Cloudflare); Hostinger web hosting normally terminates HTTPS itself | Check at deployment (signed email links and PayMongo return URLs must be `https://`) | No |

### Not re-verified in a browser after B2/B3

The B2/B3 view changes are covered by feature tests (13 new tests, all
passing) but were not re-crawled. Include them in the final browser rerun
(section 8, step 7).

---

## 3. Security issues and required actions

### F-1: exposed database dump (critical)

Facts (from `SECURITY_DUMP_EXPOSURE.md`, sanitized): an old local database
export is in the history of `main` and the team branches of the team's GitHub
repository (visibility **not confirmed**); 5 accounts (4 demo, 1 real
person's student account); no API keys, `APP_KEY` or webhook secret in it.
Commit, file name and account details are held privately by the project lead.

**Safe remediation plan (no history rewrite):**

| # | Action | Owner | Status |
|---|---|---|---|
| 1 | Confirm repository visibility | Member with GitHub admin access | Open |
| 2 | Privately inform the affected person; they change their PathWise password and any reused password, and enable 2-step verification on that email account | Project lead | Open |
| 3 | Change demo passwords on shared or defense computers; never reuse them | Each member | Open |
| 4 | Production: no demo accounts (already enforced by `DatabaseSeeder`); Super Admin password from `SEED_SUPERADMIN_PASSWORD` (quoted) or generated | Deployer | Built in |
| 5 | After deployment: confirm no local demo account exists | Super Admin | After deployment |
| 6 | Decide whether to rewrite history (`git filter-repo`), only with separate approval and every member's coordination | Group + you | Open |
| 7 | Record each step in the tracking table of `SECURITY_DUMP_EXPOSURE.md` | Group | Open |

### Accidental-commit check

| Item | Ignored by Git? | Risk |
|---|---|---|
| `.env` | Yes (`.gitignore`) | None |
| Local database export (`*.sql`) | Yes (`*.sql`) | None now (history: F-1) |
| `storage/app/private/*` (payment proofs, lesson files, submissions, backups), `storage/framework/sessions` | Yes | None |
| `public/storage`, `public/build`, `node_modules`, `vendor` | Yes | None |
| `docs/audit/PATHWISE-NEW-COMPILATION.docx` (CVs: phone numbers, addresses, photos) | **No** | F-7 |
| 5 `.png` files in the project root | **No** | F-7 |
| `docs/audit/MANUSCRIPT_REVISIONS_DECEMBER.md` (superseded) | No | Low (no personal data) |
| `docs/audit/evidence/…` | No | Reviewed: redacted; commit is your decision |

No API key, password or token was found in any tracked or untracked text file
of the project.

---

## 4. Test and build results (November 6, run one at a time)

| Check | Result |
|---|---|
| `php artisan test` (full suite) | **588 passed, 0 failed, 2,504 assertions** |
| `composer audit` | No security vulnerability advisories |
| `npm audit --omit=dev` | 0 vulnerabilities |
| `composer validate` | Valid |
| `composer check-platform-reqs --lock` | 24/24 success (PHP 8.4.22) |
| `npm run build` | Success |
| `config:cache`, `route:cache`, `view:cache`, `event:cache` | All succeed (then cleared) |
| PHPStan (`composer types:check`) | **Fails**: memory crash at 128 MB; 553 errors with 1 GB (F-3) |
| Pint (`--test`) | 116 files need formatting (F-10; CI fixes, does not fail) |

Earlier in the day, one full run had 1 failure (`CourseInvitationTest`, Vite
manifest not found) because `npm run build` replaced `public/build` while the
tests were running. Rerun alone: 588/588. Not a code defect.

### Application areas inspected

| Area | Status | Evidence |
|---|---|---|
| Authentication (register, verify email, login, reset, 2FA/passkeys, deactivation) | Works (2FA/passkeys by tests only) | Browser workflows 1–4, 34, 46; Fortify tests |
| Authorization, four roles | Works; 249 routes, every non-public route behind `auth`, `verified`, `role:*`; `EnsureUserIsActive` on all web routes | `bootstrap/app.php`; `CrossAccountAccessTest`; crawl |
| Courses, approval, lock | Works | Workflows 13–24, 47; `LockedLessonPagesTest` |
| Enrollment, invitations, payments (test mode) | Works; live payment not verified | Workflows 5, 29, 38–43 |
| Lessons, quizzes, assignments, grading | Works | Workflows 6–7, 14–19, 25–28 |
| AI (recommendation, quiz generation, evaluation) | Works with real Gemini; fallback text when Gemini fails | Workflows 8, 18, 26; `GeminiFallbackModelTest` |
| Certificates and public verification | Works; **date issue F-2** | Workflows 9–11 |
| Notifications and email | In-app works; email by log mailer and tests only | Workflow 23; `EmailNotificationsTest` |
| Account management, departments, removal requests | Works (after B2) | Workflows 30–34; `RemovalRequestRoutingTest` |
| Responsive layout | 191 pages, 0 overflow at 390/1280 px (Edge) | Crawl |
| Error handling | Default Laravel error pages (F-6); validation messages shown on forms | Crawl, workflows |

---

## 5. Deployment blockers

Compared with `docs/DEPLOYMENT_HOSTINGER.md` and the project configuration.

| # | Area | Requirement (from the code) | Status |
|---|---|---|---|
| D-1 | Security | F-1 steps 1–3 done | Open |
| D-2 | Timezone | F-2 fixed and `APP_TIMEZONE=Asia/Manila` set before real data | Open (needs approval) |
| D-3 | PHP | **8.4.1 or later** (`composer.json` `^8.4.1`); extensions incl. `gd`, `intl`, `bcmath`, `zip`, `pdo_mysql` | Hostinger plan not confirmed |
| D-4 | Database | MySQL 8 tested; MariaDB must be tested (`json` columns in passkeys and course templates) | Not tested |
| D-5 | Environment | `APP_NAME=PathWise`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, `SESSION_SECURE_COOKIE=true`, `LOG_LEVEL=error`, `QUEUE_CONNECTION=sync`, secrets **quoted**; note `.env.example` has `APP_DEBUG=true`, `QUEUE_CONNECTION=database`, `MAIL_MAILER=log`, so do not copy it unchanged | Not prepared |
| D-6 | Email | Hostinger SMTP mailbox (`smtps`, port 465); verification, reset, enrollment and certificate emails | Not tested live |
| D-7 | Gemini | `GEMINI_API_KEY`, model `gemini-2.5-flash`, outbound HTTPS to `generativelanguage.googleapis.com` | Works locally |
| D-8 | PayMongo | Test keys + webhook (`checkout_session.payment.paid`) + `PAYMONGO_WEBHOOK_SECRET`; **live keys only after your explicit authorization** | Test mode works locally; webhook not tested live |
| D-9 | HTTPS / domain | SSL + force HTTPS; check signed links and PayMongo return URLs are `https://` (F-11) | Not started |
| D-10 | Storage | `storage` and `bootstrap/cache` writable; `php artisan storage:link`; private files stay in `storage/app/private` | Not started |
| D-11 | Migrations / seed | `migrate --force`; `db:seed --force` (roles, Super Admin, templates; no demo accounts) | Verified on SQLite/MySQL locally |
| D-12 | Scheduler / queue | Cron `schedule:run` every minute; no queue worker needed | Not started |
| D-13 | Backups | `mysqldump` present; `BACKUP_ARCHIVE_PASSWORD` (quoted); run once; **restore once**; off-site copy procedure (F-4) | Restore tested locally only |
| D-14 | Outbound / DNS | HTTPS to Gemini, PayMongo, `api.pwnedpasswords.com`, SMTP; DNS MX lookups | Not tested |
| D-15 | Logging / errors | Daily log, 14 days, `LOG_LEVEL=error`; no debug pages | Config ready |
| D-16 | Live checklist | V-1 to V-17 in the guide, plus Chrome/Firefox/Safari | Not started |

---

## 6. Git status and proposed commit plan

### Current status

| Category | Files |
|---|---|
| Modified, application | `app/Http/Controllers/LessonController.php`, `app/Http/Controllers/StudentRemovalRequestController.php`, `app/Models/StudentRemovalRequest.php`, `resources/views/Teacher/lessons.blade.php`, `resources/views/student-removal-requests/index.blade.php` |
| New, tests | `tests/Feature/Pathwise/RemovalRequestRoutingTest.php`, `tests/Feature/Pathwise/LockedLessonPagesTest.php` |
| Modified, documentation | `docs/DEPLOYMENT_HOSTINGER.md`, `docs/audit/MANUSCRIPT_REVISIONS_NEW_COMPILATION.md` |
| New, documentation | `docs/audit/FINAL_VALIDATION_AUDIT.md`, `docs/audit/SECURITY_DUMP_EXPOSURE.md`, `docs/audit/SYSTEM_FINALIZATION_REPORT.md` (this file) |
| New, evidence | `docs/audit/evidence/2026-11-06-final-validation/` (redacted, 3.1 MB) |
| Keep out of Git (sensitive / superseded) | `docs/audit/PATHWISE-NEW-COMPILATION.docx`, 5 root `.png` files, `docs/audit/MANUSCRIPT_REVISIONS_DECEMBER.md` |
| Ignored (correct) | `.env`, `*.sql` exports, `vendor/`, `node_modules/`, `public/build/`, `public/storage`, `storage/app/private/*`, `storage/framework/*`, `bootstrap/cache/*.php`, `database/database.sqlite` |

### Proposed commits (exact paths only; not created)

| # | Message | Files |
|---|---|---|
| 9 | Route student removal requests so none is left without a reviewer | `app/Models/StudentRemovalRequest.php`, `app/Http/Controllers/StudentRemovalRequestController.php`, `resources/views/student-removal-requests/index.blade.php`, `tests/Feature/Pathwise/RemovalRequestRoutingTest.php` |
| 10 | Redirect teachers from locked lesson pages with an explanation | `app/Http/Controllers/LessonController.php`, `resources/views/Teacher/lessons.blade.php`, `tests/Feature/Pathwise/LockedLessonPagesTest.php` |
| 11 | Add final validation, security and system finalization documents | `docs/audit/FINAL_VALIDATION_AUDIT.md`, `docs/audit/SECURITY_DUMP_EXPOSURE.md`, `docs/audit/SYSTEM_FINALIZATION_REPORT.md`, `docs/DEPLOYMENT_HOSTINGER.md`, `docs/audit/MANUSCRIPT_REVISIONS_NEW_COMPILATION.md` |
| 12 *(if approved)* | Add redacted evidence of the November 6 validation audit | `docs/audit/evidence/2026-11-06-final-validation/` |
| 13 *(if approved)* | Keep personal manuscript files out of Git | `.gitignore` (F-7) |

Each commit ends with the project's attribution line. Rollback: every commit
is local; `git revert <hash>` or `git reset --soft 5257be3` (keeps the
changes). `Berdin` and the stash are not touched.

---

## 7. Items requiring your decision

| # | Decision | Options | Recommendation |
|---|---|---|---|
| 1 | Commits 9–11 | Approve / change | Approve |
| 2 | Commit the evidence folder (12) | Yes / keep local | Yes (redacted, traceable) |
| 3 | `.gitignore` for the DOCX and PNGs (13) | Ignore / move files out | Ignore (safer) |
| 4 | Timezone fix (F-2) | (a) configurable timezone, `Asia/Manila` in production, local data left as is; (b) also switch local `.env` and accept that old local timestamps shift by 8 hours; (c) leave UTC and document it | (a) |
| 5 | PHPStan / CI (F-3) | (a) memory fix + the 5 new errors + model relation return types; (b) also controller return types (larger); (c) baseline or lower level (hides problems) | (a) now, (b) later; not (c) |
| 6 | Off-site backups (F-4) | Manual weekly download procedure / second backup disk (feature) | Manual procedure now |
| 7 | Branded error pages (F-6) | Add / skip | Add (small, uses existing layout and logo) |
| 8 | Lock-rule refactor (F-9) | Do / skip | Do together with F-2/F-3 (no behaviour change) |
| 9 | Code-style commit (F-10) | Do / skip | Skip until after the defense (large diff) |
| 10 | Teacher self-registration (F-5) | Adviser | Keep; manuscript wording |
| 11 | History rewrite (F-1) | Group | Only after visibility is confirmed |

---

## 8. Prioritized action plan to finish the system

| Step | Work | Needs | Tests |
|---|---|---|---|
| 1 | Commit the approved B2/B3 fixes and documents (commits 9–11, optional 12–13) | Your approval | Full suite before committing |
| 2 | **Group:** F-1 security steps 1–3 and 7 | Group | — |
| 3 | **Batch S1 — timezone (F-2):** configurable timezone; certificate date and backup schedule in Philippine time; guide note corrected | Approval of decision 4 | New tests: certificate date near midnight; schedule timezone; full suite |
| 4 | **Batch S2 — CI types (F-3a):** PHPStan memory, the 5 new errors, model relation return types | Approval of decision 5 | PHPStan error count before/after; full suite |
| 5 | **Batch S3 — optional polish:** branded error pages (F-6), lock-rule refactor (F-9) | Approval of decisions 7–8 | Error page tests; existing lock tests |
| 6 | **Docs:** off-site backup procedure (F-4) and timezone in the deployment guide | Approval of decision 6 | — |
| 7 | **Final local validation:** fresh `pathwise_audit` database, rerun the saved browser scripts for all four roles (includes B2/B3 pages), full suite, audits, build | Approval (same safety rules as before) | Browser + full suite |
| 8 | **Deployment (separate approval):** D-1 to D-16, live checklist V-1 to V-17, PayMongo test mode first | Your explicit approval; server access | Live checklist |

After step 7, the system meets the local acceptance criteria (workflows,
four roles, no known critical/high defects). Step 8 completes the
"production deployment and live checks pass" criterion.
