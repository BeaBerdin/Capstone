# PATHWISE — Final System and Manuscript Validation Audit (Phases 1–3)

Audit date: November 6, 2026 · Branch `finalization` at `5257be3` (8 local
commits, not pushed) · `Berdin` unchanged at `277310b`.
Manuscript reference: `docs/audit/PATHWISE-NEW-COMPILATION.docx` (printed page
numbers as rendered by Microsoft Word on this machine; see
`MANUSCRIPT_REVISIONS_NEW_COMPILATION.md` for how pages were derived).

**This phase changed nothing in the system or the manuscript.** It only
created this document. Proposed fixes are in section 7 and need approval
before Phase 4.

Status words used throughout:

| Status | Meaning |
|---|---|
| **Implemented** | Exists and was seen working (browser run, real database) or is covered by passing tests and was read in the code |
| **Partial** | Exists, but differs from what the manuscript says, or works only under a condition |
| **Missing** | Claimed by the manuscript; not in the system |
| **Not verified** | Could not be checked in this environment (live host, real payment, real SMTP, other browsers) |

---

## 1. Summary

**The system works.** Every important workflow of all four roles ran
successfully in a real browser against a real database, including Gemini
(3 real calls) and PayMongo (test mode). 191 pages were crawled with **no
server errors, no JavaScript errors and no horizontal overflow** at phone and
desktop widths, except one page blocked by a real bug (SYS-1).

**Findings:** 1 critical security item, 1 high-priority functional bug,
3 medium items that need a decision, 5 low items, and **26 new manuscript
findings** in addition to the 35 items and 5 optional additions already in
`MANUSCRIPT_REVISIONS_NEW_COMPILATION.md`.

**The largest remaining gap is the manuscript, not the code.** Chapter II and
several figures still claim gamification, adaptive assessment, machine
learning, teacher earnings, administrator report exports and AI-generated
learning paths, none of which exist.

| Priority | ID | Finding | Type |
|---|---|---|---|
| **Critical** | SEC-1 | Database dump (users with password hashes, sessions) is in the GitHub history of `main` and all team branches | Security |
| **High** | SYS-1 | Student removal requests depend on departments: a teacher without a department can file a request nobody can see; an admin without a department gets 403. Demo data has no departments | Bug |
| Medium | SYS-2 | Add Lesson / Edit Lesson on a course that is pending approval or published showed a bare 403 error page instead of an explanation (**corrected description, see 4.4; fixed in B3**) | Usability |
| Medium (decision) | SYS-3 | Teachers cannot change a **published** course at all (no way to fix a typo) | Design decision |
| Medium (decision) | SYS-4 | Anyone can self-register as a **Teacher**; Chapter II claims a "restricted, department-integrated account creation process" | Policy / manuscript |
| Low | SYS-5 | `APP_NAME=Laravel` in the local `.env`: every browser tab reads "… - Laravel" (also visible in the storyboard screenshots) | Configuration |
| Low | SYS-6 | An unquoted `#` in `.env` silently cuts a value (a password `Abc#123` becomes `Abc`) | Deployment docs |
| Low | SYS-7 | Production password rules need outbound HTTPS (breach check) and registration needs DNS (deliverable email domain); not in the deployment guide | Deployment docs |
| Low | SYS-8 | Administrator has no reports page or PDF export (only Super Admin; teachers have analytics). Fig. 14 claims one | Manuscript or feature |
| Low | SYS-9 | Storyboard screenshots use an older dark design and show a password-manager popup with a demo email | Manuscript images |

---

## 2. Method and safety log

### 2.1 Isolation

| Item | What was done |
|---|---|
| Code | A clone of commit `5257be3` in the session scratchpad, git remote removed (nothing could be pushed). Project folder only read |
| Database | `pathwise_audit`, created and dropped by a script that refuses any other name and checks the clone's `.env` first. Every query checked `DB::connection()->getDatabaseName() === 'pathwise_audit'`. `pathwise` and `pathwise_demo` were never opened |
| Email | `MAIL_MAILER=log` in the clone: verification, reset, enrollment and certificate emails were written to the clone's log. **No email was sent** |
| Server | `php artisan serve` on `127.0.0.1:8123` from the clone; stopped after testing |
| Browser | `puppeteer-core 24.10.0` in the scratchpad driving installed Microsoft Edge (headless) |
| Seed data | `db:seed` + `DemoDataSeeder` (22 users, 6 courses, 16 enrollments, 7 transactions) |

### 2.2 External calls made

| Service | Calls | Purpose |
|---|---|---|
| Google Gemini (`gemini-2.5-flash`) | **3** | 1 course-recommendation explanation (quiz submit), 1 AI quiz generation (3 questions), 1 AI essay evaluation |
| PayMongo **test mode** (`sk_test_…`, verified before running) | 1 checkout session created + verification look-ups | Paid enrollment up to the hosted test checkout page; no payment was completed; no live keys exist in the clone |
| SMTP | 0 | Log mailer only |

The AI quiz generation was refused once before Gemini was called (no published
reading lesson yet; correct behaviour) and did not use a call.

### 2.3 Evidence

Kept **locally, outside Git** (decision of November 6; the folder is in
`.gitignore`) in **`docs/audit/evidence/2026-11-06-final-validation/`**
(see its `README.md`): 22 screenshots, every crawl and workflow result, the
data dictionary comparison and the audit scripts. Passwords, tokens and the
synthetic test email address were redacted; the manuscript images (members'
photos and signatures), the database, and the mail and server logs were not
kept.

---

## 3. Security

### SEC-1 (Critical). Database dump in Git history

| | |
|---|---|
| **What** | A MySQL export of a local development database (September 2026) was committed and later removed from tracking; it remains in the Git history. Commit and file details are held privately by the project lead (sanitized here) |
| **Where it is** | The team's GitHub repository (`main` and all team branches) and every clone; visibility **not confirmed** (treated as public) |
| **Contents** | User accounts (4 demo accounts and 1 real person's student account) with names, emails and bcrypt password hashes; old sessions; test-mode transactions; demo learning data. No API keys, webhook secret or `APP_KEY` |
| **Risk** | Bcrypt hashes are slow to crack, but demo passwords must be treated as known. The real person's account is the main concern |
| **Already true** | The production seeder never creates the demo accounts (`DatabaseSeeder` skips them outside local/testing), and the Super Admin password is generated or set from `.env` |
| **Actions (no code)** | See `SECURITY_DUMP_EXPOSURE.md` (procedure, message to the group, tracking table). History rewrite only with separate approval |

No other secret was found: `.env` was never committed (only `.env.example`),
the `.gitignore` covers `*.sql`, `.env` and test result files.

### Static checks (no issue found)

| Check | Result |
|---|---|
| Unescaped Blade output `{!! !!}` | Only framework/Flux components, the 2FA QR code SVG, a date string, and lesson content passed through `e()` first. Safe |
| Raw SQL | One `whereRaw` with a bound parameter (`EnrollmentController`). Safe |
| Mass assignment | No model uses `$guarded = []` |
| State-changing GET routes | Only `transactions/{id}/cancel` (PayMongo's required cancel return URL; owner-checked; a later paid webhook still approves the payment) |
| Public routes | Home, invitation preview, certificate verification (rate-limited 10/min), PayMongo webhook (signature-checked). All others require login + verified email + role |
| Authorization | 249 routes: Admin 86, Teacher 50, signed-in 41, Student 30, Super Admin 22, public 20 (4 app + framework). Cross-account access is covered by `CrossAccountAccessTest` |

---

## 4. System verification results

### 4.1 Page crawl (all roles)

Each page reachable from the role's dashboard was opened at 1280 px and 390 px
and checked for HTTP errors, JavaScript errors, failed requests and
horizontal overflow.

| Role | Pages | Server errors | JS errors | Overflow | Notes |
|---|---|---|---|---|---|
| Super Admin | 36 | 0 | 0 | 0 | 2 report PDFs are downloads (checked separately: 200, `application/pdf`) |
| Administrator | 82 | 0 | 0 | 0 | `/admin/student-removal-requests` **403** (SYS-1). Certificate preview is a PDF (200) |
| Teacher | 41 | 0 | 0 | 0 | — |
| Student | 32 | 0 | 0 | 0 | — |
| **Total** | **191** | **0** | **0** | **0** | Edge only |

### 4.2 Workflows run in the browser

| # | Workflow | Result | Evidence |
|---|---|---|---|
| 1 | Student registers (Student role) | **Pass**: lands on "verify your email" | `w_student.json`, shot `student_01` |
| 2 | Dashboard blocked until verified | **Pass** | redirected to `/email/verify` |
| 3 | Verification email + link → role dashboard | **Pass** | link read from mail log |
| 4 | Registration with an undeliverable domain (`@example.com`) | Rejected ("must be a valid email address"); correct, see SYS-7 | — |
| 5 | Course listed in marketplace; free enrollment | **Pass** (status `active`) | — |
| 6 | Complete lessons | **Pass** (4/4, quiz lesson completes on submit) | — |
| 7 | Take quiz, automatic scoring | **Pass** (5/5, 100 %, passed) | shot `student_04` |
| 8 | **[Gemini]** Recommendation after quiz | **Pass**: next course + Gemini explanation ("Fantastic job on passing … we recommend Introduction to …"), score = quiz % | shot `student_06` |
| 9 | Course completed + certificate issued | **Pass** (`PATH-2026-00006`) | shot `student_07` |
| 10 | Certificate PDF download | **Pass** (200, PDF, 0.9 MB) | — |
| 11 | Public verification, logged out (real and fake number) | **Pass** ("Valid certificate" / "No certificate found") | shot `student_08` |
| 12 | Rate and review course | **Pass** | — |
| 13 | Teacher creates course (draft) | **Pass** | shot `teacher_01` |
| 14 | Text lesson, PDF lesson, quiz lesson | **Pass** | — |
| 15 | 21 MB lesson file | **Rejected** (limit 20 MB): correct | — |
| 16 | Quiz saved, manual question | **Pass** | shot `teacher_02` |
| 17 | AI generation refused without a published reading lesson | Correct guard ("Add published Reading lessons with content…") | — |
| 18 | **[Gemini]** AI quiz generation on a draft course | **Pass** (1 → 4 questions; "Review and edit them before publishing") | shot `teacher_03` |
| 19 | Essay assignment with AI evaluation enabled | **Pass** | — |
| 20 | Submit for approval → `pending` | **Pass** | — |
| 21 | Lock while pending | **Pass with a usability issue**: saving is refused (403, code + `CourseApprovalWorkflowTest`, `AssignmentLockTest`); course Edit already redirects with a message; Add/Edit Lesson showed a 403 error page (SYS-2, fixed in B3) | — |
| 22 | Administrator approves → `published` | **Pass** | shot `admin_01` |
| 23 | Teacher notified of approval | **Pass** ("Your course … has been approved and published.") | — |
| 24 | Administrator returns a course with remarks → `rejected`; teacher sees remarks; can edit again | **Pass** | shot `teacher_05` |
| 25 | Student enrolls in the new course, submits essay | **Pass** | — |
| 26 | **[Gemini]** AI evaluation: suggested score 95, final score still empty | **Pass** | shot `teacher_04` |
| 27 | Student cannot see AI score/feedback before approval | **Pass** | — |
| 28 | Teacher overrides and grades (88) | **Pass** | — |
| 29 | Teacher invitation → preview while logged out → student accepts → enrolled | **Pass** | shot `student_09` |
| 30 | Teacher files removal request | Accepted, but **teacher has no department** | `w_more_removal.json` |
| 31 | Admin without department opens removal requests | **403** (SYS-1) | — |
| 32 | Super Admin creates department, assigns admin + teacher; request becomes visible | **Pass** | shot `admin_02` |
| 33 | Super Admin creates a teacher account (verified, active) | **Pass** | — |
| 34 | Deactivate account → login refused ("deactivated. Please contact the EDP office.") | **Pass** | — |
| 35 | Super Admin report PDF (range and all-time) | **Pass** | — |
| 36 | System Status card | **Pass** ("Needs attention: Email delivery" because the audit used the log mailer) | — |
| 37 | Certificate template preview PDF | **Pass** | — |
| 38 | **[PayMongo test]** Purchase paid course → pending transaction + hosted test checkout | **Pass** (redirected to `checkout.paymongo.com`) | shot `student_10` |
| 39 | No enrollment before payment | **Pass** | — |
| 40 | Success page with an unpaid session | Stays `pending`: correct | — |
| 41 | Correctly signed webhook for an unpaid session | **202**, not approved (PayMongo re-queried): correct | — |
| 42 | Webhook with wrong signature | **401**: correct | — |
| 43 | Cancel link | Transaction `rejected` | — |
| 44 | Admin hides a review with a reason | **Pass** | shot `admin_03` |
| 45 | Admin generates a recommendation for a student with no quiz results | Refused with a clear message: correct guard | — |
| 46 | Forgot password → reset link (logged) | **Pass** | — |
| 47 | Teacher adds a lesson to a **published** course | **403** (SYS-3; current design) | — |

### 4.3 Not verified in this environment

| Item | Why | Covered elsewhere |
|---|---|---|
| A completed PayMongo payment through a **real** webhook | Needs a public HTTPS URL and a completed test payment | `PayMongoWebhookSecurityTest` (8), `EnrollmentAndPaymentTest`; live checklist V-items |
| Real SMTP delivery | Log mailer by decision | `EmailNotificationsTest` (14); live checklist |
| Chrome, Firefox, Safari | Only Edge installed | Live checklist |
| Two-factor login and passkeys in a browser | Need an authenticator/device | Fortify tests (`tests/Feature/Settings`, `Auth`) |
| Hostinger, MariaDB, cron, backups on the host | Not deployed | `docs/DEPLOYMENT_HOSTINGER.md`, PB-1 to PB-6 |
| Concurrent users / performance | Out of scope | — |

### 4.4 System findings in detail

**SYS-1 (High). Removal requests and departments**
- *Observed:* `StudentRemovalRequestController::store` accepts a request from a
  teacher with `department_id = null`. `departmentHeadIndex()` shows only
  requests from teachers in the admin's own department and aborts with 403
  when the admin has no department. Self-registered teachers and the seeded
  admin have no department, so the request is invisible and the page fails.
- *Inconsistency:* course approval is campus-wide (any admin approves any
  course); removal requests are per department.
- *Defense risk:* with the demo data, the removal-request workflow fails.
- *Proposed fix (Phase 4, needs approval):* (a) when a teacher has no
  department, show a clear message instead of filing the request, **or** route
  it to all administrators; (b) the admin page shows a helpful message instead
  of 403 when no department is assigned; (c) `DemoDataSeeder` creates a demo
  department and assigns the demo admin and teachers; (d) tests for each.
  The choice between "block" and "route to all admins" is a **group decision**.

**SYS-2 (Medium). Locked lesson pages showed an error page**
- *Correction (Phase 4, November 6):* the first description ("the pages open
  and typing is lost") was wrong. The browser check recorded the page URL,
  not the HTTP status, and `fetch` followed the course-edit redirect. Re-reading
  the code showed: course **Edit** already redirects to My Courses with "Course
  details cannot be edited while the course is pending approval or
  published" (`CourseController::redirectIfCourseLocked`); **Add Lesson** and
  **Edit Lesson** returned a bare **403 error page** to the course's own
  teacher. No typed text could be lost, because the form never opened.
  The quiz builder already shows a read-only "Quiz content is locked" view;
  assignment pages hide their edit links while pending (existing tests).
- *Fix (B3):* Add/Edit Lesson now redirect the owner to the course's lesson
  list with an explanation (pending: "waiting for administrator approval …
  You can edit it again if the administrator returns it for revision";
  published: "lessons cannot be added or changed by the teacher. Contact an
  administrator"). The lesson list now shows error messages. Saving stays
  403; other teachers still get 403. Tests: `LockedLessonPagesTest`.

**SYS-3 (Medium, decision). Published courses cannot be changed by the teacher**
- *Observed:* `ensureTeacherCanModifyCourse` allows only `draft` and `rejected`.
  Once published, the teacher cannot fix a typo or add a lesson.
- *Options:* (a) keep it and say so in the manuscript ("published courses are
  changed by the administrator"; admins can edit courses and lessons);
  (b) add a "request revision" action that returns the course to draft
  (hidden from the marketplace until re-approved). **Group/adviser decision.**
  Recommendation: (a) for this release.

**SYS-4 (Medium, decision). Open teacher self-registration**
- *Observed:* `CreateNewUser` lets anyone choose Teacher. Courses still need
  administrator approval, so nothing is published without review.
- *Manuscript:* p. 15 claims "restricted, department-integrated account
  creation process"; FR b says "create a student or teacher account".
- *Options:* (a) keep open registration and correct p. 15 (recommended; no
  code change); (b) require Super Admin activation for new teachers (new
  behaviour, needs tests and manuscript text). **Group/adviser decision.**

**SYS-5 (Low). Application name**
- `.env` has `APP_NAME=Laravel`; titles, emails and PDFs use it. Production
  already has `APP_NAME=PathWise` on its checklist (PB-2). For the defense,
  set it in the local `.env` too (configuration only, done by you).

**SYS-6 (Low). `#` in `.env` values**
- A test value of the form `Abc#123` was read as `Abc` (confirmed with the
  audit copy's own throwaway password). Proposed: one line in
  `docs/DEPLOYMENT_HOSTINGER.md`: "Put any value that contains `#`, spaces or
  quotes in double quotes".

**SYS-7 (Low). Outbound requirements**
- Production passwords: at least 12 characters, upper- and lowercase, a
  number, a symbol, and not in a known breach (`AppServiceProvider`;
  the breach check calls the Have I Been Pwned API). Registration and profile
  emails need a domain that can receive mail (`email:rfc,dns`). Proposed: add
  both to the deployment guide's requirements.

**SYS-8 (Low). Administrator reports**
- Only `super-admin/reports` (+ PDF) and `teacher/analytics` exist. The
  Administrator has Student Progress Reports (view only). Fig. 14 says the
  Administrator "Generate[s] reports (engagement, course performance) with PDF
  export". Recommendation: correct Fig. 14 (manuscript, N-5); building an
  admin report is a feature, not a fix.

**SYS-9 (Low). Storyboard images**: see N-16 and N-17.

---

## 5. Manuscript–system alignment

### 5.1 Specific objectives (p. 4)

| Objective | Status | Evidence |
|---|---|---|
| 1. Existing system: process, problems | Not a system feature (survey, Chapter III) | — |
| 2.1 Course creation | **Implemented** | Workflow 13–14, 20; `CourseTemplateTest` (4 built-in templates) |
| 2.2 AI-powered course recommendation | **Implemented** (rules + Gemini explanation) | Workflow 8; `RecommendationSelectionTest`; wording items 1–3, 5–6, 11, 26 |
| 2.3 Quiz generation | **Implemented** (teacher-reviewed drafts, from reading-lesson text) | Workflow 17–18; `AiQuizGenerationTest` |
| 2.4 Output evaluation | **Implemented** (optional, teacher approval) | Workflow 26–28; `StudentAiVisibilityTest` |
| 2.5 Certificate generation | **Implemented** (for courses that offer one) | Workflow 9–11 |
| 2.6 Payment tracking | **Implemented**; live payment **not verified** | Workflow 38–43; `PayMongoWebhookSecurityTest` |
| 2.7 Performance tracking and analytics reports | **Implemented** for teacher analytics and Super Admin reports; **Partial** for Administrator (view-only progress reports, no export) | Crawl; workflow 35; SYS-8 |
| 3. Best features | Survey (not a system feature) | — |
| 4. Acceptability (ISO/IEC 25010) | **Not verified**: results not yet in the manuscript | — |

### 5.2 Functional requirements a–p (p. 107)

| FR | Status | Evidence / note |
|---|---|---|
| a Log-in | Implemented | Workflows (all roles) |
| b Register (student/teacher) | Implemented | Workflow 1–3; SYS-4 |
| c Course management | Implemented, **wording wrong** ("publish") | Item 32c |
| d Enrollment | Implemented (+ invitations) | Workflows 5, 29; item 32d |
| e Lessons | Implemented | Workflow 6 |
| f Quizzes | Implemented (timed, retake) | Workflow 7; `QuizTimeLimitTest`, `QuizRetakeEnrollmentTest` |
| g Score computation | Implemented (1 point per correct answer) | Workflow 7; item 21 |
| h Certificate | Implemented (conditional) | Workflow 9–11; item 32h |
| i AI recommendation | Implemented, **wording** | Workflow 8; item 32i |
| j Progress tracking | Implemented | Crawl; `StudentProgressPagesTest` |
| k Course approval | Implemented | Workflows 20–24 |
| l Category management | Implemented | Crawl; `AdminPagesTest` |
| m Payment processing | Implemented (test mode); live **not verified** | Workflows 38–43 |
| n User management | Implemented | Workflows 33–34 |
| o Report generation | Implemented (Super Admin PDF, teacher analytics) | Workflow 35; SYS-8 |
| p Notifications | Implemented (in-app; enrollment and certificate also by email) | Workflow 23; `NotificationsTest` |
| q–t (proposed in item 32) | Implemented in the system; **missing from the manuscript** | Workflows 18, 25–28, 12, 44 |

### 5.3 Use cases (Figures 21–31, pp. 51–61)

| Fig. | Use case | Status | Note |
|---|---|---|---|
| 21 | Create Account | Implemented | Matches (email verification) |
| 22 | Log-in | Implemented | Figure says "username" → email (N-12) |
| 23 | Enroll in Course | Implemented; **text Partial** | Item 10; "Process payment" is always included in the figure (N-13) |
| 24 | Learn Lessons and Take Quiz | Implemented | — |
| 25 | Manage Courses and Lessons | Implemented; **text Partial** | "save/publish" (item 12) |
| 26 | Get AI-Recommended Courses | Implemented; **text Partial** | Item 11, N-14 |
| 27 | View and Download Certificate | Implemented | Conditional on the course offering one |
| 28 | Submit Course for Approval | Implemented | Lock: SYS-2 |
| 29 | Manage Course Categories and Approvals | Implemented | "Reject" = return for revision |
| 30 | Manage Certificates and AI Recommendations | Implemented; **figure/text Partial** | No "settings"; one template (item 15, N-15) |
| 31 | Manage User Accounts and Roles | Implemented | — |
| — | Assignments, AI quiz generation, AI evaluation, invitations, reviews, removal requests, departments | Implemented; **no use case** | Optional G-4; N-11 |

### 5.4 List of Modules (Table 24, pp. 108–109)

| Module | Status |
|---|---|
| Login, Registration, Forgot Password, Dashboards | Implemented |
| Create Course, Edit Course, Upload Lessons | Implemented (edit only while draft/returned: SYS-3) |
| **Publish/Unpublish Course (Teacher)** | **Missing**: the administrator's approval publishes; teachers cannot unpublish (item 34) |
| Submit Course for Approval | Implemented |
| Browse Marketplace, Enroll in Course, Process Payment (PayMongo) | Implemented (payment live not verified) |
| Study Lessons, Create Quiz, Take Quiz, Compute Quiz Score, Retake Quiz, View Progress | Implemented |
| Generate Certificate, AI Recommendation Engine, View Learning Path | Implemented (learning path is rule-based: N-4) |
| View Student Scores | Implemented |
| **View AI Learning Analytics (Teacher)** | **Partial**: Teacher Analytics exists but is not AI (N-24) |
| Manage Course Categories, Review/Approve/Reject Courses, Manage Certificates, Manage AI Recommendations, View Student Progress Reports | Implemented |
| Manage Users, Assign User Roles, Verify Payments, Generate System-wide Reports | Implemented |
| Not listed but implemented | Assignments/grading, AI quiz generation, AI evaluation, invitations, reviews and moderation, learning paths (admin), removal requests, departments, certificate verification, account settings/2FA/passkeys (item 34) |

### 5.5 Chapter II claims

| p. | Claim | Status | Action |
|---|---|---|---|
| 11 | AI applies "machine learning techniques" | **Missing** (no ML) | Item 5 |
| 12 | Recommender "continuously learning" | **Partial** (recalculated after each quiz) | Item 6 |
| 13 | Certificates issued automatically on completion | Implemented (courses that offer one) | — |
| 14 | Certificates and dashboards have "achievement-oriented elements" (gamification) | **Missing** (no points, badges, leaderboards or streaks) | **N-1** |
| 15 | "Restricted, department-integrated account creation process" | **Partial/contradicted** (open registration; departments used only for removal requests) | **N-3**, SYS-4 |
| 15 | Secure payment gateway | Implemented (signature + re-query) | — |
| 16 | Responsive, desktop and mobile | Implemented (191 pages at 390 px, 0 overflow; Edge only) | — |
| 16 | Verifiable certificates | Implemented (public verification) | — |
| 17 | Faculty set their own course pricing | Implemented | — |
| 18 | "Visible, real-time progress tracking with intelligent, performance-based course sequencing" | **Partial**: progress updates on lesson/quiz completion; sequencing = next-course recommendation and rule-based learning paths | N-23 (optional wording) |
| 18 | Payment through GCash and Maya | **Partial** (through PayMongo) | Item 7 |
| 19 | Gemini generates quiz questions "based on course materials uploaded by educators" | **Partial**: only the text of published Reading lessons, not uploaded PDFs or videos | **N-23** |
| 19 | Educators review questions before students see them | Implemented | — |
| 20–21 | Conceptual Framework: User Training module; Gamification; Adaptive Learning adjusts "content sequencing and pacing" | **Missing / Partial** | Item 8, **N-1**, **N-6** |
| 26 | "PATHWISE extends this concept by embedding a similar adaptive, gamified assessment approach" | **Missing** (fixed quizzes; no gamification) | **N-2** |
| 27 | "PATHWISE builds on this gamified learner-creator model…" | **Missing** (gamified part) | **N-2** |
| 28 | Comparative Matrix (Table 1): PATHWISE features | Implemented for all 11 rows listed | — |
| 10–11 | Platform as a Service | Framing: PathWise is a hosted web application; the paper calls the system a PaaS | **N-26** (adviser, low) |

### 5.6 Figures (all 51 images in the DOCX)

The DOCX holds 51 images: 45 numbered figures, the Gantt chart (Table 3),
4 CV photos and the signed transmittal letter. "Viewed" = image opened and
read during this audit.

| Img | Figure (page) | Viewed | Verdict |
|---|---|---|---|
| 1 | Fig. 1 Conceptual Framework (20) | Yes | **Issues**: User Training box; Gamification; "View/Verify earnings" (no earnings page); Student Module labelled "Instructor's Access"; typos "Learnig", "Recomendation"; cites "Walker et al., 2024", which is **not in the References** (N-6) |
| 2 | Fig. 2 CHED-LMS (22) | Yes | Third-party system; no claim about PathWise |
| 3 | Fig. 3 TEL LMS (22) | Yes | Third-party |
| 4–6 | Figs. 4–6 Idika, QuizMe, Kulkarni (23–24) | No | Third-party screenshots; the claims are in the text (N-2) |
| 7 | Fig. 7 Flow of the Study (31) | Yes | Input says "CCS students and faculty"; Respondents section says all departments (N-25) |
| 8 | Fig. 8 Local map (32) | No | Map; no system claim |
| 9 | Fig. 9 Agile (37) | Yes | Generic diagram; fine |
| 10 | Fig. 10 Lean Model Canvas (38) | Yes | **Issues**: "machine learning"; revenue streams subscription fees, commission, certificate fees, transaction fees; "time spent" metric (N-7) |
| 11 | Fig. 11 Program Workflow (39) | Yes | Accurate (registration, email verification, 2FA/passkey, role routing) |
| 12 | Fig. 12 Student (39) | Yes | Accurate; "Submit code activity" should also mention essays (minor) |
| 13 | Fig. 13 Teacher (40) | Yes | Accurate; "code activity" should also mention essays (minor) |
| 14 | Fig. 14 Administrator (40) | Yes | **Issues**: "Review submitted courses (own department)" (approval is campus-wide); "Generate reports … with PDF export" (no admin reports) (N-5) |
| 15 | Fig. 15 Super Admin (41) | Yes | Accurate |
| 16 | Fig. 16 Validation Board (42) | Yes | Fine (hypotheses) |
| 17 | Fig. 17 Business Roadmap (43) | Yes | Figure runs 2025–2031 with different milestones; text (p. 42) ends in 2030 (N-8) |
| 18 | Fig. 18 Functional Decomposition (44) | Yes | Accurate but incomplete (no assignments, AI quiz, invitations, reviews, removal requests, departments) (N-9, optional) |
| 19 | Table 3 Gantt chart (46) | Yes | Plan; no system claim |
| 20 | Fig. 19 AI System Architecture (48) | Yes | Accurate except code languages omit **Python** (N-10) |
| 21 | Fig. 20 Use Case Diagram (50) | Yes | **Issues**: no Create Account; "grade quizzes" (item 9); no assignments/reviews/invitations (N-11) |
| 22 | Fig. 21 Create Account (51) | Yes | Accurate |
| 23 | Fig. 22 Log-in (52) | Yes | "Check username and password" → email (N-12) |
| 24 | Fig. 23 Enroll (53) | Yes | "Process payment" shown as always «include»; should be «extend» (paid only); no invitation path (N-13) |
| 25 | Fig. 24 Take Quiz (54) | Yes | Accurate |
| 26 | Fig. 25 Manage Courses (55) | Yes | Accurate |
| 27 | Fig. 26 AI-Recommended (56) | Yes | "Analyze learning behavior" overstated (N-14) |
| 28 | Fig. 27 Certificate (57) | Yes | Accurate |
| 29 | Fig. 28 Submit for Approval (58) | Yes | Accurate |
| 30 | Fig. 29 Categories and Approvals (59) | Yes | Accurate |
| 31 | Fig. 30 Certificates and AI (60) | Yes | "Configure certificate templates", "Adjust recommendation settings" do not exist (N-15, item 15) |
| 32 | Fig. 31 Users and Roles (61) | Yes | Accurate |
| 33 | Fig. 32 Login Page (62) | Yes | Screenshot shows a password-manager popup with a demo email (N-16) |
| 34 | Fig. 33 Forgot Password (63) | Yes | Accurate |
| 35 | Fig. 34 Registration (65) | Yes | Accurate |
| 36–39 | Figs. 35–38 Dashboards (67–73) | Yes | Older dark design; tab title "Laravel"; admin quick action "Manage suggestion settings" (N-17). Card names match the system (`DashboardSummaryCardsTest`) |
| 40 | Fig. 39 Database Design (75) | Yes | Accurate and complete **except** the `payments` table (dropped) (item 24) |
| 41 | Fig. 40 ERD (76) | Yes | Accurate except the `Payment` entity (item 24) |
| 42 | Fig. 41 Network Design (102) | Yes | Shows Gemini (good); unlabeled "Service" node → "Email (SMTP) Service" (N-19) |
| 43 | Fig. 42 Network Model (103) | Yes | Accurate |
| 44 | Fig. 43 Network Topology (104) | Yes | Accurate |
| 45 | Fig. 44 Technology Stack (105) | Yes | "PHP 8.3" → 8.4; "User's Laptop / Desktop" → any device (N-20) |
| 46–49 | CV photos (110–113) | Yes (46, 48) | No system claim |
| 50 | Appendix A Transmittal Letter (119) | Yes | **Present and signed** (resolves item 35h). Says surveys target "students in programming courses and computer studies" (N-25) |
| 51 | Fig. 45 Map (120) | No | Map; no system claim |

### 5.7 Data dictionary: all tables, column by column

Compared automatically (`dd_compare.py`) against the real schema created by
the migrations.

| Table | Columns match? | Differences (beyond the 35 items) |
|---|---|---|
| 4 Roles | Missing from the body | Item 18 |
| 5 Users | Yes | Not listed: `remember_token`, `created_at`, `updated_at` (N-22) |
| 6 User_Roles | Yes | Unique (`user_id`, `role_id`) not mentioned (optional) |
| 7 Course_Categories | Yes | Not listed: timestamps (N-22) |
| 8 Course_Templates | Yes | No header row (item 27); not listed: `is_active`, timestamps (N-22) |
| 9 Courses | `is_free` not in DB | Item 19; also `description` row says constraint "Null" but Allow Nulls "No"; the column is NOT NULL → constraint "Not Null" (N-22) |
| 10 Lessons | Yes | — |
| 11 Enrollments | Yes | Unique (`student_id`, `course_id`) (optional) |
| 12 Lesson_Progress | Yes | Item 20 (time spent never recorded) |
| 13 Quizzes | Yes | — |
| 14 Quiz_Questions | Yes | Item 21 |
| 15 Quiz_Results | Yes | — |
| 16 Assignments | Yes | Missing `due_date`, `max_score`, `is_published`, timestamps (item 22) |
| 17 Submissions | Yes | Missing `status`, `submitted_at`, `ai_evaluation_status`, `ai_evaluated_at`, timestamps (item 23) |
| 18 Payments | **Table does not exist** | Item 24 |
| 19 Transactions | Yes | Not listed: `approved_at`, timestamps; `transaction_no` is **Unique** (N-22) |
| 20 Certificates | Yes | — |
| 21 Course_Reviews | Only 3 of 10 columns | Item 25 |
| 22 Learning_Paths | Yes | `is_generated` "AI-generated" → rule-based (N-4) |
| 23 Learning_Path_Courses | Yes | — |
| 24 AI_Recommendations | Yes | Item 26 |
| 25–30 Departments, Course_Invitations, Student_Removal_Requests, Notifications, Certificate_Settings, Passkeys | **Yes, exact** | — |
| Status/enum lists | All match the database (difficulty, lesson type, enrollment, progress, correct answer, remarks, transaction, certificate) except Courses status | Item 19 |

Framework tables not documented (acceptable): `cache`, `cache_locks`, `jobs`,
`job_batches`, `failed_jobs`, `migrations`, `password_reset_tokens`,
`sessions`.

### 5.8 Storyboard field tables (pp. 62–73)

| Screen | Field | Manuscript | System | Status |
|---|---|---|---|---|
| Login | Email, Password, Remember Me, Forgot, Sign Up | VARCHAR 255 | Present; login by email | OK |
| Forgot Password | Email, Send Reset Link, Back | — | Present; reset link verified (workflow 46) | OK |
| Registration | Name | VARCHAR 255 | `required, max:255` | OK |
| Registration | Email | VARCHAR/Email 255 | `email:rfc,dns`, `max:255`, unique; domain must accept mail | OK; add the rule (N-21) |
| Registration | Role | Toggle (Student/Teacher) | `in:student,teacher` | OK |
| Registration | Password / Confirm | **VARCHAR 50** | No 50-character limit; stored as a hash; production rule: **≥ 12 characters, upper/lowercase, number, symbol, not breached**; must match confirmation | **Wrong** (N-21) |
| Dashboards | Summary cards and links | As listed | Match (`DashboardSummaryCardsTest`, `SuperAdminAccountsAndDashboardTest`) | OK; screen numbering item 16; admin step 8 item 15 |

### 5.9 New manuscript findings (in addition to items 1–35 and G-1 to G-5)

Each item names the page, the current content, the proposed correction, the
evidence and who must approve it. "Recommend: correct manuscript" follows your
instruction not to build features for unsupported claims.

| ID | Page | Current | Proposed correction | Evidence | Confirm |
|---|---|---|---|---|---|
| **N-1** | 14 (Gamification relevance), 20 (Fig. 1), 20–21 (framework text) | "PATHWISE's digital certificates and progress dashboards are designed with achievement-oriented elements that reinforce learner motivation…"; Gamification box in Fig. 1; framework text: "Engagement within the system is reinforced by Gamification…" | p. 14: "Gamification is relevant to PATHWISE as its findings inform the motivational design of the platform: visible progress percentages, quiz results and digital certificates give learners clear milestones. PATHWISE does not implement game elements such as points, badges or leaderboards in this version." Remove "Gamification" from Fig. 1 or relabel it "Progress milestones (certificates, progress tracking)"; reword the framework sentence the same way | No points/badges/leaderboard/streak code in `app/` or `resources/views` | **Adviser** (removing a reviewed concept) |
| **N-2** | 26 (Idika and Saihi), 27 (Kulkarni) | "PATHWISE extends this concept by embedding a similar adaptive, gamified assessment approach within a complete PaaS-style platform…"; "PATHWISE builds on this gamified learner-creator model by adding an AI Recommendation Engine, Adaptive Learning…" | p. 26: "PATHWISE applies the adaptive principle at the course level instead: after each quiz it recommends an easier, similar or harder next course; its quizzes themselves have fixed questions." p. 27: "PATHWISE builds on this learner-creator model and its verification-and-publishing workflow by adding an AI Recommendation Engine, a monetized course marketplace and integrated payment." | `RecommendationService` (course-level difficulty); `QuizController` (fixed questions) | Adviser |
| **N-3** | 15 (Cybersecurity relevance) | "…PATHWISE's restricted, department-integrated account creation process…" | Option (a), recommended: "…PATHWISE's role-based access control, email verification, optional two-factor authentication and passkeys, Super Admin account deactivation, and its reliance on a secure payment gateway…". Option (b) requires SYS-4 (b) | `CreateNewUser`; `routes/web.php` line 33 (`verified`); Fortify 2FA/passkeys; `AccountDeactivationTest` | Adviser (+ SYS-4) |
| **N-4** | 95, Table 22 `is_generated` | "Indicates whether the learning path was AI-generated" | "Indicates whether the learning path was generated automatically by the system from the student's quiz results (rule-based: category and difficulty)" | `LearningPathController::generateForStudent` (no Gemini call); `LearningPathGenerationTest` | No |
| **N-5** | 40, Fig. 14 | "Review submitted courses (own department)"; "Generate reports (engagement, course performance) with PDF export" | "Review submitted courses"; replace the reports box with "View student progress reports". (Alternative: build an admin report = new feature) | `CourseController::approve` (no department filter); routes: reports only for `super_admin` and teacher analytics | Group (redraw) |
| **N-6** | 20, Fig. 1 | User Training box; "View/Verify earnings"; Student Module labelled "Instructor's Access"; "Learnig Portal", "AI Recomendation Engine Learning"; "Walker et al., 2024" | Redraw: "Role-based User Access" (item 8); remove "View/Verify earnings" (no earnings feature; or "View course enrollments and payments" if the adviser wants a teacher-side item, which also does not exist); "Student's Access"; fix typos; add Walker et al. (2024) to the References **or** replace it with a cited source (the text uses Ramaswami, Tzeng) | No earnings route/view (`grep earnings`); References list (pp. 110–114) has no Walker | **Group** (find the Walker source) |
| **N-7** | 38, Fig. 10 Lean Canvas | "Continuous improvement through data analytics and machine learning"; revenue: subscription fees, commission from course sales, certificate generation fees, transaction fees; key metric "time spent" | Unfair advantage: "…through data analytics and AI-assisted features (Google Gemini)"; label revenue streams as the **business model**, not implemented features, or keep only "Course enrollment fees" (implemented) and mark the rest "planned"; drop "time spent" or mark it planned | No subscription/commission/certificate-fee code; item 20 | Group + Adviser |
| **N-8** | 42 (text) vs 43 (Fig. 17) | Text: 2025 → 2030 ("concludes in 2030"); figure: 2025 → 2031 with different milestones | Make them agree: redraw the figure to the text's six stages, or rewrite the text to the figure | Manuscript only | Group |
| **N-9** | 44, Fig. 18 FDD | Missing implemented functions | Optional: add Teacher: assignments and grading, AI quiz generation, invitations, removal requests, course reviews; Student: submit outputs, accept invitation, rate courses; Admin: moderate reviews, removal requests; Super Admin: manage departments | Routes | Group (optional) |
| **N-10** | 48, Fig. 19 | Output Evaluation box lists HTML/CSS/JavaScript, Java, C, PHP | Add Python (same fix as item 17 in the text) | `Assignment.php` languages | No |
| **N-11** | 50, Fig. 20 | No "Create account" use case; "Monitor progress and grade quizzes" | Add "Create account" (Student, Teacher); "Monitor progress and grade submissions"; optional review/assignment/invitation use cases (G-4) | Fig. 21 itself; item 9 | No / Group for optional |
| **N-12** | 52, Fig. 22 | "Check username and password" | "Check email and password" | Login form uses email | No |
| **N-13** | 53, Fig. 23 | "Process payment" «include» | Change to «extend» ("paid courses only"); optionally add "Accept invitation" «extend» | Workflows 5, 29, 38 | No |
| **N-14** | 56, Fig. 26 | "Analyze learning behavior" | "Evaluate latest quiz result" (and "Generate explanation (Gemini)") | Item 11 | No |
| **N-15** | 60, Fig. 30 | "Configure certificate templates"; "Adjust recommendation settings" | "Update certificate template"; "Generate or remove recommendation records" | Item 15 | No |
| **N-16** | 62, Fig. 32 | Screenshot shows a browser password-manager popup with a demo account | Retake the screenshot without the popup | — | No |
| **N-17** | 67–73, Figs. 35–38 | Older dark design; tab title "… - Laravel"; admin quick action "Manage suggestion settings" | Retake screenshots from the current light design after SYS-5 (`APP_NAME=PathWise`); optional | `LightModeOnlyTest` | Group (optional) |
| **N-19** | 102, Fig. 41 | Node labelled "Service" | "Email (SMTP) Service" | `config/mail.php` | No |
| **N-20** | 105, Fig. 44 | "Laravel 13 · PHP 8.3"; "User's Laptop / Desktop" | "PHP 8.4"; "Any device with a web browser" (also p. 104 text, item 31) | `composer.json` `^8.4.1` | No |
| **N-21** | 65, Registration storyboard table | Password and Confirm Password: VARCHAR, size 50 | Size "—"; add a Logic line: "Passwords must be at least 12 characters with upper- and lowercase letters, a number and a symbol, and must not appear in a known data breach. The email address must belong to a domain that can receive mail." | `AppServiceProvider` `Password::defaults`; `ProfileValidationRules` | No |
| **N-22** | 77–92, Tables 5, 7, 8, 9, 19 | Missing columns / wrong constraint (see 5.7) | Add the listed columns; Courses `description` constraint "Not Null"; Transactions `transaction_no` constraint "Unique" | `dd_report.json` | No |
| **N-23** | 18–19 (Self-paced; Quiz Generation relevance) | "…generate quiz questions based on course materials uploaded by educators" | "…based on the text of the published reading lessons that educators write in the course (uploaded PDF and video lessons are not read by the AI)" | `GeminiQuizService` (text lessons only); refusal message in workflow 17 | No |
| **N-24** | 109, Table 24 | "View AI Learning Analytics" (Teacher) | "View Course Analytics" | `teacher/analytics` (charts of enrollments and scores; no AI) | No |
| **N-25** | 31 (Fig. 7), 119 (transmittal), 30/33 (respondents) | Fig. 7 and the letter say CCS / programming students; Respondents say all departments | Make the population consistent with what was actually surveyed | Manuscript | **Group** (facts only the group knows) |
| **N-26** | 1, 4, 10–11 | The system is described as a "Platform as a Service" | Consider "a web-based platform delivered as a cloud-hosted service (Software as a Service), hosted on a PaaS-style web host"; keep only if the adviser accepts the PaaS framing | Hosting: Hostinger web hosting | Adviser (low) |

(The 35 earlier items remain valid; items 8, 15, 24 and 31 now also cover
their figures, as noted above. N-18 was merged into item 24.)

---

## 6. Decisions needed

### Group

| # | Decision | Recommendation |
|---|---|---|
| D-1 | SEC-1: rewrite Git history or rely on password rotation | Confirm visibility first; rotate in any case; rewrite only if public |
| D-2 | SYS-1: teacher without department → block the request, or route to all admins | Block with a clear message + seed a demo department |
| D-3 | SYS-3: teachers editing published courses | Keep as is; document it |
| D-4 | N-6: source for "Walker et al., 2024" | Find it or replace it |
| D-5 | N-8: roadmap years | Choose one version |
| D-6 | N-25 and item 35d/e: who was actually surveyed and how many | Use the Google Forms data |
| D-7 | Optional figure updates (N-9, N-17, G-4, G-5) | Do N-17 if time allows |

### Adviser

| # | Decision | Recommendation |
|---|---|---|
| A-1 | Gamification claims (N-1) | Correct the manuscript |
| A-2 | Adaptive assessment claims (N-2) | Correct the manuscript |
| A-3 | Account creation claim vs open teacher registration (N-3, SYS-4) | Correct the manuscript (option a) |
| A-4 | Teacher payouts / "earnings" (item 4, N-6) | Correct the manuscript |
| A-5 | Requirements q–t and new modules (items 32, 34) | Add them |
| A-6 | Lean Canvas revenue/ML (N-7) | Mark as business model / planned |
| A-7 | PaaS framing (N-26) | Adviser's call |
| A-8 | Department Head scope (campus-wide approval) | Keep campus-wide; say so (Fig. 14, N-5) |

---

## 7. Proposed Phase 4 batches (nothing done yet)

| Batch | Content | Files (expected) | Tests |
|---|---|---|---|
| **B1 Security docs** | SEC-1 guidance in the deployment guide and finalization report (rotation steps; no history rewrite) | Docs only | — |
| **B2 Removal requests (SYS-1)** | Clear message when the teacher has no department (or route to all admins, per D-2); helpful page instead of 403 for an admin without a department; demo seeder creates a department and assigns the demo admin and teachers | `StudentRemovalRequestController`, its views, `DemoDataSeeder` | New tests in `StudentRemovalRequestTest`, `DemoDataSeederTest`; full suite |
| **B3 Locked course pages (SYS-2)** | Lesson create/edit and course edit pages redirect with a message when the course is pending or published | `LessonController`, `CourseController` | New tests; full suite |
| **B4 Deployment guide (SYS-6, SYS-7)** | Quote `.env` values with `#`; outbound HTTPS for the breach check; DNS for email validation; set `APP_NAME=PathWise` | `docs/DEPLOYMENT_HOSTINGER.md` | — |
| **B5 Manuscript revisions file** | Add N-1 to N-26 and the figure verdicts to `MANUSCRIPT_REVISIONS_NEW_COMPILATION.md` (or keep them here and cross-reference) | Docs only | — |
| **B6 Optional, after decisions** | SYS-3 (b), SYS-4 (b), admin reports (SYS-8) | Only if approved | Tests per feature |

After each batch: targeted tests, then the full suite (575 today), `composer
audit`, `npm run build`, and an update of this document.

---

## 8. Phase 4 results (November 6, 2026)

Approved batches B1–B5. Decisions: removal requests from teachers without a
department go to all administrators; administrators without a department see
all requests; the published-course editing restriction stays and is
documented; unsupported gamification and adaptive-quiz claims are removed
from the manuscript (revision document only; the DOCX is not edited).

| Batch | Result | Files |
|---|---|---|
| B1 Security | Done. Affected accounts listed by role only (sanitized); remediation procedure, message to the group and tracking table; no history rewrite | `docs/audit/SECURITY_DUMP_EXPOSURE.md` (new) |
| B2 Removal requests (SYS-1) | **Fixed.** Rule in one place (`StudentRemovalRequest::scopeReviewableBy` / `isReviewableBy`): an administrator without a department reviews all requests; an administrator with a department reviews their department plus requests from teachers without a department and from departments that have no administrator; other departments stay forbidden. No 403 for an administrator without a department; the teacher is told when a request goes to all administrators. The demo seeder was **not** changed: with the fix the demo administrator (no department) sees every request, and adding a department would break the seeder's rule of never changing existing accounts | `app/Models/StudentRemovalRequest.php`, `app/Http/Controllers/StudentRemovalRequestController.php`, `resources/views/student-removal-requests/index.blade.php`, `tests/Feature/Pathwise/RemovalRequestRoutingTest.php` (new, 6 tests) |
| B3 Locked lesson pages (SYS-2) | **Fixed** (description corrected in 4.4). The course owner is redirected from Add/Edit Lesson of a pending or published course to the lesson list with an explanation; the lesson list now shows error messages; saving stays 403; other teachers still get 403. Assignment pages unchanged (they already hide their links while pending; existing tests expect 403 on direct access) | `app/Http/Controllers/LessonController.php`, `resources/views/Teacher/lessons.blade.php`, `tests/Feature/Pathwise/LockedLessonPagesTest.php` (new, 7 tests) |
| B4 Deployment guide | Done. Outbound HTTPS hosts (Gemini, PayMongo, Have I Been Pwned, SMTP) with a check command; DNS for email validation; production password rules; `APP_NAME=PathWise`; quoting `.env` values that contain `#`; pointer to the security document | `docs/DEPLOYMENT_HOSTINGER.md` |
| B5 Manuscript revisions | Done. Section H added (N-1 to N-27; N-18 noted under item 24) with the November 6 decisions and the unresolved items marked; items 1–35 and G-1 to G-5 unchanged except a status note (35h resolved) | `docs/audit/MANUSCRIPT_REVISIONS_NEW_COMPILATION.md` |
| Evidence | Preserved locally with redactions; **not committed** (ignored by Git, decision of November 6) | `docs/audit/evidence/2026-11-06-final-validation/` |

Still open (outside the approved batches): SYS-4 (open teacher registration;
adviser), SYS-5 (set `APP_NAME=PathWise` in the local `.env`: your action),
SEC-1 password resets and the history decision (group), and the unresolved
manuscript items N-3, N-6 (Walker reference), N-7 (label planned or remove),
N-8, N-25, N-26 and 35a–g. SYS-3 is decided (keep the restriction;
documented as N-27). SYS-8 is handled by the manuscript correction N-5.
