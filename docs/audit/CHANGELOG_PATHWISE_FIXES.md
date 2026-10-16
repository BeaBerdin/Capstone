# PathWise — Change Log (Audit Round 1: P0/P1 fixes + Course Templates)

Date prepared: October 16, 2026
Status: **Code written and checked by reading only. NOT yet executed.** PHP was not
available in the environment where these changes were prepared, so no test, migration,
or page has actually been run. Run the steps in `HOW_TO_INSTALL_THE_FIXES.md` and the
test suite on your computer before relying on any of these changes.

Legend: **P0** critical · **P1** high · **P2** medium · NEW = new file

---

## 1. Security and payment (P0)

| File | Change | Why | Manuscript |
|---|---|---|---|
| `app/Http/Controllers/CourseController.php` → `enroll()` | Refuses paid courses and redirects to the course page with an error. | Any student could join a paid course for free by sending a request to the free "enroll" address. | Fig. 23 Enroll in Course; FR (m) Payment Processing |
| `app/Services/GeminiRecommendationService.php` | API key moved from the URL (`?key=`) to the `x-goog-api-key` header; error log no longer stores the full response body. | Keys in URLs end up in error messages and log files. | NFR "sensitive data is safe"; Scope (Gemini API) |
| `bootstrap/app.php` | `paymongo/webhook` excluded from CSRF/request-forgery protection; `EnsureUserIsActive` added to every web request. | PayMongo cannot send a CSRF token, so its webhook was always rejected. The webhook stays protected by its signature check. | FR (m); FR (n) |
| `.env.example` | Added `GEMINI_API_KEY`, `GEMINI_MODEL`, `PAYMONGO_SECRET_KEY`, `PAYMONGO_PUBLIC_KEY`, `PAYMONGO_WEBHOOK_SECRET`, `PAYMONGO_WEBHOOK_TOLERANCE` (empty values). | These settings were used by the code but not documented. | Tech Stack; Software Development Specification |

## 2. Database integrity (P0)

| File | Change | Why |
|---|---|---|
| `database/migrations/2026_09_19_133820_add_course_invitation_id_to_transactions_table.php` | Rewritten to do what its name says: add `transactions.course_invitation_id` (guarded). | It wrongly created the `course_invitations` table a second time, so `php artisan migrate` on a new database crashed, and the column used by payments was never created. |
| `database/migrations/2026_09_19_105146_create_course_invitations_table.php` | Skips creation if the table already exists. | Protects databases that were repaired by hand. |
| NEW `database/migrations/2026_10_16_000001_ensure_course_invitation_id_on_transactions_table.php` | Adds the column only if it is missing. | Repairs existing databases that already recorded the broken migration as "ran". |
| `database/migrations/2026_06_19_060210_update_learning_paths_table.php`, `2026_06_19_060934_update_learning_path_courses_table.php` | On **SQLite only**, the added columns are optional. MySQL is unchanged. | SQLite (used by automated tests) cannot add a required column without a default, so the test database could never be built. |

## 3. Linux hosting compatibility (P0)

| File(s) | Change | Why | Manuscript |
|---|---|---|---|
| `AIRecommendationController`, `CertificateController`, `CourseController`, `DashboardController`, `LearningPathController`, `LessonController`, `QuizController`, `StudentAssignmentController`, `StudentRemovalRequestController`, `TransactionController` | Every `view('...')` name now matches the folder's exact letter case (e.g. `Student.dashboard`, `Teacher.my-courses`, `Lessons.index`). | Windows ignores upper/lower case in folder names, Linux does not. On Hostinger (Linux) most student and teacher pages would have crashed. Folders were not renamed because case-only renames are unreliable in Git on Windows. | Network Design (Hostinger) |

## 4. User account management (P1)

| File | Change | Manuscript |
|---|---|---|
| NEW `database/migrations/2026_10_16_000002_add_is_active_to_users_table.php` | Adds `users.is_active` (default **true**, so nobody is locked out). | FR (n); Fig. 31 |
| NEW `app/Http/Middleware/EnsureUserIsActive.php` | Logs out deactivated users on their next request with the message "Your account has been deactivated…". | FR (n) |
| `app/Models/User.php` | `is_active` cast to boolean (kept out of `$fillable` on purpose); new `department()` relation. | — |
| `app/Http/Controllers/UserManagementController.php` | New `store()` (create account with role and optional department; account is pre-verified) and `updateStatus()` (activate/deactivate; cannot deactivate yourself). | Fig. 31 "Add User", "deactivate" |
| `routes/web.php` | `POST /super-admin/users` (`users.store`), `PATCH /super-admin/users/{user}/status` (`users.update-status`). | — |
| `resources/views/users/index.blade.php` | "Create Account" panel, Status column with Activate/Deactivate button, "Email not verified" hint. | Fig. 35 Super Admin dashboard ("Manage Users") |

## 5. Course approval and revision workflow (P1)

| File | Change | Manuscript |
|---|---|---|
| NEW `database/migrations/2026_10_16_000003_add_review_remarks_to_courses_table.php` | Adds `courses.review_remarks`. | Fig. 28/29; Program Workflow "revising submissions based on feedback" |
| `app/Models/Course.php` | `review_remarks`, `template_id` fillable; `template()` relation. | — |
| `app/Http/Controllers/CourseController.php` | `reject()` now **requires** feedback (5–2000 characters) and saves it; `approve()` clears it; teacher edit/update is **blocked while pending or published**; submission is blocked if any lesson is unpublished. | Fig. 28 "Finalize and lock the course details for review"; Fig. 29 |
| `app/Notifications/CourseStatusNotification.php` | Includes the administrator's feedback in the teacher's notification. | Fig. 28 "Notify the teacher" |
| `resources/views/courses/index.blade.php` | "Reject" renamed **"Return for Revision"** and asks for feedback; error messages are now shown. | Fig. 29 |
| NEW `resources/views/Teacher/courses/edit.blade.php` | The missing teacher "Edit Course Details" page (it was linked to a route whose page did not exist). Shows administrator feedback and the template used. | Fig. 25 "edit existing course details" |
| `resources/views/Teacher/my-courses.blade.php` | "Edit Course Details" menu link; feedback box with a "Revise course details" link on returned courses. | Fig. 28 |

## 6. Course templates (P1 — NEW FEATURE)

| File | Change | Manuscript |
|---|---|---|
| NEW `database/migrations/2026_10_16_000004_add_template_fields_to_course_templates_table.php` | Adds `name`, `description`, `structure` (JSON lesson outline), `is_active`. | Table 8 (Course_Templates); Definition of Terms "Course Template" |
| NEW `database/migrations/2026_10_16_000005_add_template_id_to_courses_table.php` | Adds `courses.template_id` (nullable; deleting a template never deletes courses). | Table 9 `template_id` |
| NEW `app/Models/CourseTemplate.php` | Model, `active()` scope, `lessonOutline()` (skips invalid entries). | — |
| NEW `database/seeders/CourseTemplateSeeder.php` (+ `DatabaseSeeder.php`) | Three built-in templates: *Standard Course (3 Modules)* (5 lessons), *Hands-on Skills Workshop* (5), *Short Course (Micro-credential)* (3). Safe to run repeatedly. | Rationale/Scope "pre-designed curriculum templates" |
| `CourseController@teacherCreateCourse / teacherStoreCourse` | Teacher can pick a template; its outline is copied into the new course as **unpublished text lessons** containing writing tips. Done in one database transaction. | Scope "creation of structured courses using pre-designed curriculum templates" |
| `resources/views/Teacher/courses/create.blade.php` | "Start from a template" picker showing each template's lesson list. | Comparative Matrix "Course Templates for Teachers" |

**How it works:** template lessons start unpublished, so students never see the writing
tips. The teacher edits each lesson, replaces the tips with real content, and publishes
it. The course cannot be submitted for approval until every lesson is published.
Templates are built-in (seeded); there is no screen for administrators to edit them yet.

## 7. Crash fixes and small corrections

| File | Change |
|---|---|
| `StudentRemovalRequestController@instructorIndex` | Pointed to a page that does not exist (`instructor.…`); now uses `Teacher.student-removal-requests.index`. |
| NEW `resources/views/assignments/show.blade.php`, NEW `resources/views/submissions/show.blade.php` | The administrator "view" pages for assignments and submissions were missing. |
| `app/Models/Quiz.php` | `lesson_id` added to `$fillable`; the administrator quiz form was silently losing the lesson link. |

## 8. Automated tests

| File | Purpose |
|---|---|
| `tests/TestCase.php` | **Safety guard:** tests stop immediately unless they use the temporary in-memory SQLite database, so your real MySQL data cannot be erased. |
| `tests/Pest.php` | Turns on a fresh database for every test (it was switched off, so database tests could not pass); helper functions `pathwiseUser()` and `pathwiseCourse()`. |
| `tests/Feature/Auth/RegistrationTest.php` | Sends the required role; new test: nobody can self-register as an administrator. |
| `tests/Feature/DashboardTest.php` | Checks each role reaches its own dashboard; a user without a role is refused. |
| NEW `tests/Feature/Pathwise/EnrollmentAndPaymentTest.php` | Paid-course bypass blocked, free enrollment works, webhook security, CSRF exclusion. |
| NEW `tests/Feature/Pathwise/UserManagementTest.php` | Create account, role validation, only Super Admin, activate/deactivate, deactivated users logged out. |
| NEW `tests/Feature/Pathwise/CourseApprovalWorkflowTest.php` | Submit, lock while pending, edit page, ownership, feedback required/saved/notified, approve. |
| NEW `tests/Feature/Pathwise/CourseTemplateTest.php` | Seeder, picker, lessons created unpublished, blank course, inactive template rejected, submission rule. |
| NEW `tests/Feature/Pathwise/DatabaseSchemaTest.php` | All migrations run on a fresh database; new columns exist. |
| NEW `tests/Feature/Pathwise/ViewReferencesTest.php` | Every page name in the code matches a file with the **exact** letter case (catches the Linux problem on any computer). |
| NEW `tests/Feature/Pathwise/GeminiSecurityTest.php` | Key sent in header not URL; graceful failure; no call without a key (Gemini is faked; no real API call). |
| NEW `tests/Feature/Pathwise/PagesRenderTest.php` | Pages that used to crash now open. |

---

# Round 2 — Quiz time limit, cancelled enrollments, backups

Status: **Code written and checked by reading only. NOT yet executed.** No database
migrations are needed for Round 2.

## 9. Cancelled enrollments no longer give access (P2-20)

| File | Change |
|---|---|
| `app/Models/Enrollment.php` | New `ACCESS_STATUSES` (`active`, `completed`), `withAccess()` scope, `grantsAccess()` method. |
| `app/Http/Controllers/LessonController.php` (`ensureStudentCanAccessCourse`) | Lessons and the course learning page require an active or completed enrollment. |
| `app/Http/Controllers/QuizController.php` (`ensureStudentCanAccessQuiz`) | Same rule for quizzes. |
| `app/Http/Controllers/AssignmentController.php`, `SubmissionController.php`, `StudentAssignmentController.php` | Same rule for viewing and submitting assignments. |
| `app/Http/Controllers/CourseController.php` (`enroll`), `TransactionController.php` (`store`), `CourseInvitationController.php` (`accept`) | A student whose enrollment was cancelled sees "Your enrollment in this course was cancelled. Please contact the course administrator…" instead of being told they are enrolled (or being asked to pay again). The Administrator restores access under *Enrollments*. |

Why: the Administrator can set an enrollment to "cancelled", but the access checks
ignored the status, so cancelled students kept full access.

## 10. Quiz time limit enforced (P2-14) — Manuscript Table 13 `time_limit_minutes`

| File | Change |
|---|---|
| `app/Http/Controllers/QuizController.php` | `take()` records the attempt start time in the student's session (refreshing does not reset it). `submit()` rejects submissions that are missing a start time or arrive later than the limit + 30 seconds grace. The attempt is cleared after grading. New private helpers: `startOrResumeTimedAttempt`, `rejectIfTimeLimitExceeded`, `forgetTimedAttempt`. |
| `resources/views/Student/take-quiz.blade.php` (rewritten) | Countdown timer that stays visible while scrolling and turns red in the last minute; **automatic submission at 0:00**; empty options C/D are hidden; warning before submitting with unanswered questions; double-submit prevention; error/success messages; responsive layout and dark mode. |

Quizzes without a time limit work exactly as before.
Known limitation: the start time is stored in the browser session. A student who opens
the quiz in a different browser starts a new attempt, which counts as a retake (retakes
were already allowed).

## 11. Scheduled backups (P2-21) — Non-Functional Requirement: backup and recovery

| File | Change |
|---|---|
| `routes/console.php` | Daily database backup (1:00 AM), weekly full backup (Sunday 2:00 AM), daily cleanup (3:00 AM), using the already installed `spatie/laravel-backup`. |
| `config/backup.php` | Report emails go to `BACKUP_NOTIFICATION_EMAIL` (fallback: `MAIL_FROM_ADDRESS`) instead of the placeholder `your@example.com`; `.git` and `storage/logs` excluded from full backups. |
| `.env.example` | Added `BACKUP_NOTIFICATION_EMAIL=`. |

Backups only run when the Laravel scheduler runs (`php artisan schedule:work` locally;
a cron job on Hostinger).

## 12. Portability and test safety

| File | Change |
|---|---|
| `app/Services/RecommendationService.php` | SQL text values use standard single quotes (`'beginner'`), so the query also works with MySQL's ANSI_QUOTES mode and with SQLite. Behavior is unchanged. |
| `phpunit.xml` | Tests use empty Gemini/PayMongo keys. |
| `tests/TestCase.php` | `Http::preventStrayRequests()`: tests can never contact Gemini or PayMongo for real. |

## 13. New tests

| File | Purpose |
|---|---|
| `tests/Feature/Pathwise/QuizTimeLimitTest.php` | Countdown shown, refresh keeps the timer, on-time submission graded, late or unstarted submission rejected, untimed quizzes unchanged, empty options hidden. |
| `tests/Feature/Pathwise/CancelledEnrollmentTest.php` | Cancelled students blocked from lessons and quizzes and cannot silently re-enroll; active students still have access. |
| `tests/Feature/Pathwise/BackupScheduleTest.php` | Backups are scheduled; placeholder email removed. |

---

# Round 2.1 — Test fixes after the first test run (October 16, 2026)

First real test run on the student's computer: 16 passed, 32 failed.

| File | Problem | Fix |
|---|---|---|
| `tests/Pest.php` | 31 failures "Call to undefined function pathwiseUser()". The helpers were never added: the automatic edit looked for an empty placeholder function, but the file's placeholder contained a `// ..` comment, so nothing matched. | Complete file rewritten with `pathwiseUser()` and `pathwiseCourse()`. Every other Round 1/2 edit was re-verified as present. |
| `tests/Feature/Pathwise/ViewReferencesTest.php` | Reported both student-removal-request pages as missing, although they exist in the uploaded project. | The test now explains exactly which folder or file is missing, or which one has different letter case, and what is on disk. The cause must be confirmed on the student's computer. |

Confirmed by that run: all migrations build a fresh database (DatabaseSchemaTest passed),
the Gemini key is sent in a header (GeminiSecurityTest passed), and the PayMongo webhook
security checks pass.
