# PATHWISE — Review of Uncommitted Changes (for approval)

Prepared November 5, 2026 on branch `finalization` (last commit `277310b`).
**Nothing has been committed, pushed, merged or deployed.**

Verification on this working tree:

| Check | Result |
|---|---|
| Full test suite (`php artisan test`) | **575 passed, 0 failed, 2,451 assertions** on the final working tree (also before and after the Symfony update; includes the 4 new test files below) |
| Targeted tests after the PHP change | **48 passed** (PagesRender, PayMongoWebhookSecurity, CrossAccountAccess, CertificateTemplate, Auth) |
| `composer audit` | Before the Symfony update: 10 advisories (published Nov. 4). **Final: 0** (section 4b) |
| `composer validate` / `check-platform-reqs --lock` | Valid / **success on PHP 8.4.22** (section 4c) |
| `npm run build` | Success |

Use `git diff <file>` to see any change line by line.

---

## 1. Bug fixes (5 files)

### 1.1 `app/Http/Controllers/CourseController.php` (+6 / −1)

**Bug:** Teacher → Course Students → **View progress** returned HTTP 500.

**Cause:** `studentProgress()` renders `Teacher.student-progress`, and that view
loops over `$enrollments`, but the method only passed one `$enrollment`.

**Change:** builds a one-item collection and passes it as `enrollments`:

```php
$enrollments = collect([
    $enrollment->load(['student', 'course.category']),
]);
// … compact(…, 'enrollment', 'enrollments')
```

**Risk:** low. The query that finds the enrollment (and its ownership check)
is unchanged. **Test:** `StudentProgressPagesTest` → "a teacher can open one
enrolled student's progress", "…student who is not enrolled is not found".

### 1.2 `app/Http/Controllers/StudentProgressController.php` (+10 / −8)

**Bug:** Administrator → **Student Progress Reports** was always empty.

**Cause:** `index()` is shared by `/student-progress` (admin, `routes/web.php`
line 208) and `/teacher/student-progress` (teacher, line 378), but it always
filtered by `teacher_id = auth()->id()`. An administrator owns no courses, so
the list was empty.

**Change:** the teacher filter now applies only when the user is not an
administrator:

```php
->when(
    ! auth()->user()->hasRole('admin'),
    fn ($query) => $query->whereHas('course', fn ($course) =>
        $course->where('teacher_id', auth()->id()))
)
```

**Risk:** low. Teachers still see only their own courses. The admin route is
already behind `role:admin`. **Tests:** `StudentProgressPagesTest` → "the
administrator progress report lists enrollments in every teacher's course",
"a teacher progress list shows only the teacher's own courses".

### 1.3 `resources/views/Student/certificate-view.blade.php` (+10 / −10)

**Bugs:** the certificate was cut off on phones, tablets and desktops, and it
printed the local development address "pathwise.test".

**Changes:**
- Removed the fixed A4 aspect ratio (`aspect-297/210`) that clipped the content.
- Smaller text and padding on small screens (`text-3xl sm:text-5xl lg:text-7xl`
  and similar), `break-words` on the student name and course title, and
  `break-all` on the certificate number.
- Footer: one column on phones, two from `sm` up.
- "Verify this certificate at: pathwise.test" →
  `{{ route('certificates.verify') }}`, which prints the real `/verify-certificate`
  address of the site.

**Risk:** low, visual only. **Note:** the printed address comes from
`APP_URL`, so production `.env` must have the real domain (already listed in
`docs/DEPLOYMENT_HOSTINGER.md`). The downloadable PDF (DomPDF) is a separate
template and is not changed. **Test:** `FinalizationUiFixesTest` → "the
certificate page prints this site's verification address".

### 1.4 `resources/views/Teacher/submissions/show.blade.php` (+1 / −1)

**Bug:** the Review Submission page overflowed by about 120 px on phones when
a submission had long code lines.

**Change:** `*:min-w-0` on the two-column grid, so grid children can shrink and
the code block scrolls inside its own box instead of widening the page.

**Risk:** very low (one CSS class). Checked with the responsive page test.

### 1.5 `resources/views/Teacher/analytics.blade.php` (+2 / −2)

**Bug:** an Analytics chart overflowed by 6 px at desktop width.

**Change:** the same `*:min-w-0` on the two chart grids.

**Risk:** very low.

### 1.6 `resources/views/departments/index.blade.php` (−1)

**Bug:** a stray Markdown line "```blade" was printed at the top of the
Departments page.

**Change:** deleted that line.

**Test:** `FinalizationUiFixesTest` → "the departments page does not print
stray markdown".

## 2. New Create Account page (3 files)

**Purpose:** Super Admin → User Management → **Add User** (manuscript
Figure 31, "Add User"). The form used to be a fold-out box inside the user
list, which was cramped on phones and hard to find. It is now its own page.

| File | Change |
|---|---|
| `routes/web.php` (+3) | New `GET /super-admin/users/create` → `UserManagementController@create`, named `users.create`. It sits in the existing Super Admin group (same middleware as `users.index` / `users.store`) |
| `app/Http/Controllers/UserManagementController.php` (+10) | New `create()` method: loads departments by name and shows `users.create`. **`store()` is unchanged** (validation, verified email, `is_active = true`, one role) |
| `resources/views/users/create.blade.php` (new, 106 lines) | The form: full name, email, role (Administrator default; Teacher; Student; Super Admin), department (optional), temporary password and confirmation. Posts to the existing `users.store`. Cancel/back go to the user list. Re-fills inputs after a validation error |
| `resources/views/users/index.blade.php` (+12 / −84) | The 84-line inline form was removed. In its place: a card that says "Accounts created here are already email-verified" and an **Add User** button linking to the new page |

**Behaviour to confirm before approving:**
- The Super Admin can still create **any** role, including another Super
  Admin. This was already true before; the page did not change it.
- Accounts created here skip email verification (same as before, and as the
  manuscript use case says).

**Test:** `CrossAccountAccessTest` → "the super admin create account page
opens and keeps input after a validation error", plus "teachers and students
cannot reach administrator or super admin actions" and "an administrator
cannot reach super admin pages or actions".

## 3. New test files (4 files, 19 tests; no existing test changed)

| File | Tests | What it protects |
|---|---|---|
| `tests/Feature/Pathwise/CrossAccountAccessTest.php` (335 lines) | 5 | Another teacher cannot open or change a teacher's course, lessons, quiz and questions, AI quiz generation, assignments, submissions (grade / AI evaluate), quiz results, invitations, course students, reviews or removal requests; another student cannot open a student's lessons, quizzes, certificates, transactions, learning paths or private files; teachers and students cannot reach admin/super admin actions; an admin cannot reach super admin pages; the new Create Account page |
| `tests/Feature/Pathwise/PayMongoWebhookSecurityTest.php` (199 lines) | 8 | A correctly signed "paid" event approves the payment and enrolls the student **once**; rejects a signature for a different body, the wrong secret, an old timestamp, or a test signature when live keys are set; ignores other event types; does not trust a signed event if PayMongo says the session is unpaid; does not approve a payment below the course price |
| `tests/Feature/Pathwise/StudentProgressPagesTest.php` (67 lines) | 4 | Fixes 1.1 and 1.2 |
| `tests/Feature/Pathwise/FinalizationUiFixesTest.php` (45 lines) | 2 | Fixes 1.3 (address) and 1.6 |

## 4. `composer.lock` (dependency security update)

**Purpose:** fix the 38 advisories found in the earlier audit (FINALIZATION_REPORT
F-1). `composer.json` is **unchanged**; only the lock file moved, within the
version ranges already allowed.

**64 packages changed.** The important ones:

| Package | Before → After |
|---|---|
| laravel/framework | 13.15.0 → 13.40.0 |
| livewire/livewire | 4.3.1 → 4.4.8 |
| guzzlehttp/guzzle | 7.11.1 → 7.16.1 (used for Gemini and PayMongo calls) |
| dompdf/dompdf | 3.1.5 → 3.1.6 (certificates) |
| league/commonmark | 2.8.2 → 2.10.7 |
| symfony/* (http-foundation, mailer, routing, console, …) | 8.1.0 → 8.1.1–8.1.10 |
| nesbot/carbon, monolog, flysystem, polyfills | minor/patch updates |
| symfony/polyfill-time | added |
| thecodingmachine/safe | removed (no longer required by any package) |

**Risk:** medium, because the framework moved 25 minor versions. It is covered
by the full suite passing (575/575) and by the earlier 345-page responsive check.

This first update is kept as its own commit (5). Its exact lock file is saved
as git blob `124058f42ead86d44531d84670d7c814a3375576` (see section 4b).

## 4b. Symfony 8.1.11 security update (separate, done on Nov. 5 with approval)

**Why:** `composer audit` on Nov. 5 reported **10 advisories published on
November 4** in `symfony/http-foundation` (4), `symfony/http-kernel` (1),
`symfony/routing` (1), `symfony/serializer` (3) and `symfony/yaml` (1). All are
fixed in Symfony **8.1.11**.

**Compatibility check (before updating):**

| Package | Required by | Constraint | 8.1.11 allowed? |
|---|---|---|---|
| symfony/http-foundation | laravel/framework 13.40, symfony/http-kernel, fruitcake/php-cors | `^7.4.0 \|\| ^8.0.0`, `^7.4\|^8.0`, `^5.4\|^6.4\|^7.3\|^8` | Yes |
| symfony/http-kernel | laravel/framework 13.40, livewire/livewire 4.4.8 | `^7.4.0 \|\| ^8.0.0`, `^6.2\|^7.0\|^8.0` | Yes |
| symfony/routing | laravel/framework 13.40 | `^7.4.0 \|\| ^8.0.0` | Yes |
| symfony/serializer | web-auth/webauthn-lib 5.3.5 (passkeys) | `^6.4\|^7.0\|^8.0` | Yes |
| symfony/yaml | laravel/sail 1.62.0 (**development only**) | `^6.0\|^7.0\|^8.0` | Yes |

`composer.json` has no direct Symfony requirement and was **not changed**.

**Command (only the five packages, no `--with-dependencies`):**

```
composer update symfony/http-foundation symfony/http-kernel symfony/routing symfony/serializer symfony/yaml
```

A dry run first showed exactly "0 installs, 5 updates, 0 removals"; the real
run did the same.

**Changed packages (5; nothing else moved):**

| Package | Type | Before → After |
|---|---|---|
| symfony/http-foundation | production | v8.1.10 → v8.1.11 |
| symfony/http-kernel | production | v8.1.1 → v8.1.11 |
| symfony/routing | production | v8.1.10 → v8.1.11 |
| symfony/serializer | production | v8.1.0 → v8.1.11 |
| symfony/yaml | development | v8.1.10 → v8.1.11 |

**Lock-file difference:** 1 file, 34 insertions, 33 deletions. For each
package: `version`, `source.reference`, `dist.url`, `dist.reference`,
`support.source` and `time` (now 2026-11-04). The only metadata changes are in
the packages' own declarations:
- `symfony/http-kernel` 8.1.11 adds a conflict with `symfony/serializer`
  `<7.4.15|>=8.0,<8.0.15|>=8.1,<8.1.2`. That is why the serializer had to move
  in the same update; leaving it at 8.1.0 would conflict.
- `symfony/http-kernel` dev requirement `twig/twig` `^3.21` → `^3.21|^4.0`
  (Twig is not installed).
- `symfony/serializer` conflict `symfony/property-info` `<7.4` → `<7.4.15` and
  its **dev** requirement `^7.4|^8.0` → `^7.4.15|~8.0.15|^8.1.2`. The installed
  property-info is 8.1.0, which does not hit the conflict; the dev requirement
  applies only to Symfony's own tests.
- `content-hash` unchanged (because `composer.json` is unchanged).

To see the diff: `git diff 124058f42e 30f432d1f0` (before / after blobs).

**Results after the update:**

| Check | Result |
|---|---|
| `composer audit` | **No security vulnerability advisories found** |
| Full test suite | **575 passed, 0 failed, 2,451 assertions** (151 s) |
| `npm run build` | **Success** (built in 4.2 s; `public/build` is git-ignored, so no tracked file changed) |

**Rollback:** `git cat-file -p 124058f42e > composer.lock` then
`composer install` returns to the state before this update.
`git checkout HEAD -- composer.lock` then `composer install` returns to the
last commit. A copy of each lock file is also in the session scratchpad.

## 4c. PHP 8.4 minimum requirement (Nov. 5, with approval)

**Why:** `composer why-not php 8.3.99` lists **21 locked Symfony 8 packages
that require `php >=8.4.1`** (console, http-foundation, http-kernel, mailer,
routing, serializer, …). This was already true in the committed lock file
(`HEAD`); it was not caused by either update. `composer.json` said `^8.3`, so
the project said it supported a PHP version it cannot install on.

**Changes:**

| File | Change |
|---|---|
| `composer.json` | `"php": "^8.3"` → `"php": "^8.4.1"` (approved Nov. 5; matches the `>=8.4.1` the locked packages need) |
| `composer.lock` | Refreshed with `composer update --lock`: **only** `content-hash` (`f99eb8d4…` → `1308d221…`) and `platform.php` (`^8.3` → `^8.4.1`). No package changed |
| `README.md` | "PHP 8.3 or newer" → "PHP 8.4 or newer (8.4.1 minimum)" |
| `.github/workflows/tests.yml` | CI matrix `['8.3', '8.4', '8.5']` → `['8.4', '8.5']`. The 8.3 job would fail at `composer install` |
| `docs/DEPLOYMENT_HOSTINGER.md` | Requirements table: "8.4 minimum (8.4.1 or later)… PHP 8.3 will not work"; hPanel step: PHP 8.4; SSH step: `php -v` must print 8.4.x, plus a new `composer check-platform-reqs --lock` step; VPS: `php8.4-*` packages, with a note on adding `ppa:ondrej/php` if the Ubuntu release only offers 8.3, and `/etc/php/8.4/fpm` |
| `FINALIZATION_REPORT.md` | New "Update (November 5, 2026)" at the top; FA.6 and the hPanel table now say 8.4; the Round 1 stack row is marked as superseded |
| `docs/audit/MANUSCRIPT_REVISIONS_NEW_COMPILATION.md` | Item 31 already said PHP 8.4. Its evidence now cites `why-not` and the updated `composer.json` |

`^8.4.1` was chosen over `^8.4` so the declared minimum is exactly what the
locked packages require (`^8.4` would have allowed 8.4.0, which they reject).

**Verification:**

| Check | Result |
|---|---|
| `composer validate` | `./composer.json is valid` |
| `composer check-platform-reqs --lock` | `php 8.4.22 success` (all extensions success) |
| `composer install --dry-run` | "Nothing to install, update or remove" (lock in sync with `composer.json`) |
| Lock diff vs. the Symfony lock | 2 lines (content-hash, platform.php); `git diff 30f432d1f0 3f415cb6ae` |

## 5. Documentation

**`docs/audit/MANUSCRIPT_REVISIONS_DECEMBER.md` is superseded and will not be
committed.** It was written against `PATHWISE REVISED.pdf`, which is no longer
in `docs/audit/`, so its page numbers point to a file the repository does not
have. Everything in it that still applies to the new compilation is now in
`MANUSCRIPT_REVISIONS_NEW_COMPILATION.md`: the 35 items, plus section G
(5 optional additions: course reviews and upload limits in the Scope, two
definitions, review use cases and storyboards). The new file no longer refers
to December item IDs; it refers only to `MANUSCRIPT_REVISIONS.md` (already
tracked). The December file stays on disk, untracked; delete it whenever you
like.

| File | Decision |
|---|---|
| `FINALIZATION_REPORT.md` | **Commit** (with the Nov. 5 update at the top) |
| `docs/DEPLOYMENT_HOSTINGER.md` | **Commit** (PHP 8.4) |
| `docs/audit/MANUSCRIPT_REVISIONS_NEW_COMPILATION.md` | **Commit** |
| `docs/audit/PENDING_CHANGES_REVIEW.md` | **Commit** |
| `docs/audit/MANUSCRIPT_REVISIONS_DECEMBER.md` | **Do not commit** (superseded) |
| `docs/audit/PATHWISE-NEW-COMPILATION.docx` (9 MB) | **Do not commit** (members' phone numbers and addresses in the CVs) |
| The five `.png` files in the project root | **Do not commit** |

## 6. Commits (approved Nov. 5: eight commits)

**This is eight commits, not seven.** The PHP requirement is a separate change
from both dependency updates, and you asked to keep code and documentation
apart, so it needs its own commit. If you want exactly seven, the cleanest
merge is commits 1 and 2 (both are page bug fixes); every other separation
stays the same.

Order matters: commit 4 tests the page from commit 3; the lock files of
commits 5 → 6 → 7 build on each other. Files are staged **by exact path only**
(never `git add .` / `-A`), so the excluded files cannot slip in. The three
lock files are already saved in git's object store:

| Lock state | Blob | Used in |
|---|---|---|
| After the first security update | `124058f42ead86d44531d84670d7c814a3375576` | Commit 5 |
| After Symfony 8.1.11 | `30f432d1f0b79489a13b9205e55c5712e4724ac5` | Commit 6 |
| After PHP ^8.4.1 (= working tree) | `3f415cb6ae34fc3b37c81e039b0d865f7ddf926c` | Commit 7 |

Commits 5 and 6 stage their lock with
`git update-index --cacheinfo 100644,<blob>,composer.lock`, which does not
touch the working-tree file. `composer.json` stays `^8.3` in the repository
until commit 7, so each commit's `composer.json` matches its lock file.

### Commit 1: Fix the teacher and administrator student progress pages

```
Fix the teacher and administrator student progress pages

Teacher > Course Students > View progress returned HTTP 500: the view
loops over $enrollments but the controller passed only $enrollment.

Administrator > Student Progress Reports was always empty: the shared
index filtered by teacher_id even for the administrator. The filter now
applies to teachers only.

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
```

- `app/Http/Controllers/CourseController.php` (modified, +6 −1)
- `app/Http/Controllers/StudentProgressController.php` (modified, +10 −8)
- `tests/Feature/Pathwise/StudentProgressPagesTest.php` (new, 67 lines)

### Commit 2: Fix certificate, submission, analytics and departments page layouts

```
Fix certificate, submission, analytics and departments page layouts

- Certificate page no longer cuts off on phone, tablet or desktop, and
  prints the real /verify-certificate address instead of pathwise.test.
- Review Submission page no longer overflows on phones with long code.
- Analytics charts no longer overflow by 6 px on desktop.
- Remove a stray Markdown line from the Departments page.

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
```

- `resources/views/Student/certificate-view.blade.php` (modified, +10 −10)
- `resources/views/Teacher/submissions/show.blade.php` (modified, +1 −1)
- `resources/views/Teacher/analytics.blade.php` (modified, +2 −2)
- `resources/views/departments/index.blade.php` (modified, −1)
- `tests/Feature/Pathwise/FinalizationUiFixesTest.php` (new, 45 lines)

### Commit 3: Move the Super Admin Create Account form to its own page

```
Move the Super Admin Create Account form to its own page

The Add User form was a fold-out box inside the user list. It is now a
separate page (GET /super-admin/users/create) that posts to the existing
users.store action, which is unchanged.

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
```

- `routes/web.php` (modified, +3)
- `app/Http/Controllers/UserManagementController.php` (modified, +10)
- `resources/views/users/create.blade.php` (new, 106 lines)
- `resources/views/users/index.blade.php` (modified, +12 −84)

### Commit 4: Add cross-account access and PayMongo webhook security tests

```
Add cross-account access and PayMongo webhook security tests

Cover owner-only access for teachers and students, role boundaries for
administrator and super admin actions, and PayMongo webhook signature,
timestamp, mode, event type, paid status and amount checks.

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
```

- `tests/Feature/Pathwise/CrossAccountAccessTest.php` (new, 335 lines)
- `tests/Feature/Pathwise/PayMongoWebhookSecurityTest.php` (new, 199 lines)

### Commit 5: Update Composer dependencies to fix security advisories

```
Update Composer dependencies to fix security advisories

composer audit reported 38 advisories in 10 packages (laravel/framework,
symfony/http-foundation, guzzlehttp/guzzle, league/commonmark, dompdf,
livewire and others). Updated within the existing composer.json
constraints (composer.json unchanged). 64 packages changed, including
laravel/framework 13.15.0 -> 13.40.0.

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
```

- `composer.lock` = blob `124058f42e` (64 packages changed vs. `HEAD`)

### Commit 6: Update Symfony to 8.1.11 to fix the November 4 security advisories

```
Update Symfony to 8.1.11 to fix the November 4 security advisories

composer audit reported 10 advisories published on 2026-11-04 in
symfony/http-foundation, http-kernel, routing, serializer and yaml.
Updated only those five packages (8.1.1/8.1.10/8.1.0 -> 8.1.11);
no other package changed and composer.json is unchanged.

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
```

- `composer.lock` = blob `30f432d1f0` (5 packages changed vs. commit 5; +34 −33 lines)

### Commit 7: Require PHP 8.4.1, which the locked dependencies need

```
Require PHP 8.4.1, which the locked dependencies need

21 locked Symfony 8 packages require php >=8.4.1, so the project cannot
be installed on PHP 8.3 even though composer.json allowed ^8.3.

- composer.json: php ^8.3 -> ^8.4.1
- composer.lock: content-hash and platform refreshed (no package changes)
- README: PHP 8.4 or newer
- CI: test on PHP 8.4 and 8.5 (the 8.3 job could not install)

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
```

- `composer.json` (modified, 1 line)
- `composer.lock` = blob `3f415cb6ae`, the working-tree file (2 lines vs. commit 6)
- `README.md` (modified, 1 line)
- `.github/workflows/tests.yml` (modified, 1 line)

### Commit 8: Add finalization, deployment and manuscript audit documents

```
Add finalization, deployment and manuscript audit documents

- FINALIZATION_REPORT.md: finalization results, with the November 5
  update (new manuscript, PHP 8.4, Symfony 8.1.11, final test results).
- docs/DEPLOYMENT_HOSTINGER.md: Hostinger deployment guide (PHP 8.4).
- MANUSCRIPT_REVISIONS_NEW_COMPILATION.md: 35 findings and 5 optional
  additions for PATHWISE-NEW-COMPILATION.docx, with evidence.
- PENDING_CHANGES_REVIEW.md: review of these finalization commits.

The December revisions file is superseded and not included. The
manuscript DOCX (personal information) and screenshots are not included.

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
```

- `FINALIZATION_REPORT.md` (new)
- `docs/DEPLOYMENT_HOSTINGER.md` (new)
- `docs/audit/MANUSCRIPT_REVISIONS_NEW_COMPILATION.md` (new)
- `docs/audit/PENDING_CHANGES_REVIEW.md` (new)

**Left untracked:** `docs/audit/MANUSCRIPT_REVISIONS_DECEMBER.md`,
`docs/audit/PATHWISE-NEW-COMPILATION.docx`, and the five `.png` files.

**After committing:** `git status` must show only those seven untracked
files, and `git diff HEAD` must be empty.

**Rollback:** every commit is local until you push. `git reset --soft 277310b`
undoes all eight and keeps the changes in the working tree. The `Berdin`
branch (`277310b`) and the existing stash are not touched.

Nothing is pushed, merged or deployed.
