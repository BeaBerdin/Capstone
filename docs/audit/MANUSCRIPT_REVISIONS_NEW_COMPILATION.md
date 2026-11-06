# PATHWISE — Manuscript Revisions for `PATHWISE-NEW-COMPILATION.docx`

Checked on November 5, 2026: `docs/audit/PATHWISE-NEW-COMPILATION.docx`
against the `finalization` branch (commit `277310b` plus the uncommitted
changes in `docs/audit/PENDING_CHANGES_REVIEW.md`). The full test suite
passed on that working tree: **575 passed, 0 failed, 2,451 assertions**.

**The original manuscript was not edited.** This file only lists the changes
for the group to make.

## How to read this file

- **Page numbers** are the printed page numbers you get when the `.docx` is
  opened in Microsoft Word on this machine and saved as PDF (Chapter I starts
  on printed page 1; front matter uses roman numerals). If your final PDF uses
  different fonts or margins, a passage may move by a page. Use the
  **Find text** column to locate each passage.
- **Confirm?** tells you who must decide before you edit:
  - **No**: a factual fix; the system is the evidence.
  - **Group**: a wording or presentation choice the group should agree on.
  - **Adviser**: changes what the study claims (scope, objectives,
    requirements, methodology). Check with Mr. Labrador first.
- **Evidence** points to code, migrations and tests. Line numbers are those of
  the current working tree.
- Earlier audit IDs (MR-x in `MANUSCRIPT_REVISIONS.md`) are given when an
  item repeats one. This file **replaces** `MANUSCRIPT_REVISIONS_DECEMBER.md`,
  which was written against the older `PATHWISE REVISED.pdf`; everything in it
  that still applies to this compilation is included here (section G).

## What the new compilation already fixed (no action)

| Earlier item | Now in the compilation |
|---|---|
| AI System Architecture (Random Forest, Isolation Forest) | pp. 47–49 now describe rule-based logic plus the Google Gemini API, no model training, drafts reviewed by the teacher, and AI grading suggestions approved by the teacher |
| Scope: AI quiz generation, AI grading, code languages | Scope (pp. 5–6) covers AI quiz generation, optional AI grading of essays and code, the code languages (with Python), no code execution, and Gemini's limits |
| Create Account use case and email verification | The Create Account use case (p. 51) sends a verification link and activates the account after verification |
| Who publishes a course (use cases) | Submit Course for Approval and Manage Course Categories and Approvals (pp. 58–59) say the administrator approves the course for publication or returns it for revision |
| Deactivate instead of delete (use case) | Manage User Accounts and Roles (p. 61) says create, deactivate and assign roles |
| Missing data dictionary tables and fields (partly) | Data dictionary adds Departments, Course_Invitations, Student_Removal_Requests, Notifications, Certificate_Settings and Passkeys (Tables 25–30) and the missing Users/Courses/Transactions/Assignments/Submissions fields |
| Program Workflow | p. 41 text: email verification, PayMongo, timed quizzes, retakes, AI grading approved by the teacher |
| Technology stack | PayMongo (GCash, Maya, card), Fortify with 2FA and passkeys, Laravel 13, Livewire 4 |

---

## A. Chapter I — Definition of Terms

### 1. "AI-Powered Recommendation System" says machine learning

| | |
|---|---|
| **Where** | p. 8, Definition of Terms, "AI-Powered Recommendation System" |
| **Find text** | "uses machine learning to predict" |
| **Current** | "An AI-powered recommender system is broadly defined as a software module that uses machine learning to predict and suggest content relevant to a specific user based on behavioral and historical data." |
| **Replacement** | "AI-Powered Recommendation System - An AI-powered recommender system is broadly defined as a software module that suggests content relevant to a specific user based on behavioral and historical data. In PATHWISE, it refers to the module that selects a student's next course using rules based on the student's quiz result, the course category and the difficulty level, and uses the Google Gemini API to write a personalized explanation of the suggestion." |
| **Reason** | The system does not use machine learning. The panel will ask how the model was trained, and there is no model. The Scope (p. 6) and AI System Architecture (p. 48) already say "The system does not train its own models", so this definition contradicts the paper itself. |
| **Evidence** | `app/Services/RecommendationService.php`: difficulty chosen with `if ($percentage >= 85)` / `elseif ($percentage >= $quiz->passing_score)` ("This is ordinary application logic, not machine learning"); course picked by category + difficulty; `GeminiRecommendationService` only writes the `reason`, with a fixed fallback text. Test: `tests/Feature/Pathwise/RecommendationSelectionTest.php`. |
| **Confirm?** | **No** (factual). MR-1. |

### 2. "Machine Learning" definition

| | |
|---|---|
| **Where** | p. 9, Definition of Terms, "Machine Learning" |
| **Find text** | "Machine Learning - Machine learning, in general terms" |
| **Current** | "Machine learning, in general terms, is a subset of AI that enables systems to learn from data and make predictions with minimal human intervention." |
| **Replacement** | Preferred: **remove the term.** If the group wants to keep it, add an operational sentence: "…with minimal human intervention. PATHWISE does not train or use its own machine learning model; its AI features rely on the Google Gemini API." |
| **Reason** | Every other term has an operational meaning "in this study". This one has none, and it suggests the system uses machine learning. |
| **Evidence** | Same as item 1. No ML library in `composer.json`; no model files in the repository. |
| **Confirm?** | **Group** (remove or keep with the sentence). MR-2. |

### 3. "Adaptive Learning" says "in real time"

| | |
|---|---|
| **Where** | p. 8, Definition of Terms, "Adaptive Learning" |
| **Find text** | "course suggestions in real time" |
| **Current** | "It refers to how PATHWISE adjusts course suggestions in real time based on each student's progress." |
| **Replacement** | "It refers to how PATHWISE refreshes each student's course suggestions every time the student submits a quiz, based on the quiz result." |
| **Reason** | Suggestions are recalculated only when a quiz is submitted, not continuously. |
| **Evidence** | `app/Http/Controllers/QuizController.php` around line 1485: `RecommendationService::generate()` is called after a quiz is scored; `RecommendationService::generate()` first deletes the student's old recommendations. |
| **Confirm?** | **No**. MR-21. |

### 4. "Payment Gateway" says revenue is paid to instructors

| | |
|---|---|
| **Where** | p. 9, Definition of Terms, "Payment Gateway" |
| **Find text** | "distributes revenue to instructors" |
| **Current** | "In PATHWISE, this refers to the module that processes enrollment fees and distributes revenue to instructors." |
| **Replacement** | "In PATHWISE, this refers to PayMongo, which processes enrollment fees for paid courses through GCash, Maya or card, and whose payment records the Super Admin can review in the system." |
| **Reason** | The system collects payments and records them; it has no payout or revenue-sharing feature. Revenue appears only as a total in the Super Admin reports. |
| **Evidence** | `app/Http/Controllers/TransactionController.php` (checkout, webhook, approve/reject); `ReportsController.php` line ~193 `$totalRevenue` (report only). No payout code in `app/`. Tests: `EnrollmentAndPaymentTest`, `PayMongoWebhookSecurityTest`, `PaymentMethodsTest`. |
| **Confirm?** | **Adviser.** The Rationale (p. 3), Significance (p. 7) and the survey introduction also say teachers "monetize" courses. Teachers can set a price, which is true; but if the adviser expects teacher payouts, that is a missing feature, not a wording issue. MR-20. |

## B. Chapter II — Review of Related Literature and Conceptual Framework

### 5. Relevance of AI: "machine learning techniques"

| | |
|---|---|
| **Where** | p. 11, Artificial Intelligence in Education, relevance paragraph |
| **Find text** | "directly applying the machine learning techniques" |
| **Current** | "…to generate personalized next-course suggestions— directly applying the machine learning techniques validated in the literature to support continuous and adaptive learning pathways." |
| **Replacement** | "…to generate personalized next-course suggestions. Instead of training its own model, PATHWISE applies the principle validated in the literature (matching learner performance to suitable content) through rules based on quiz results, course category and difficulty, and uses the Google Gemini API to explain each suggestion to the student." |
| **Reason** | Same as item 1. |
| **Evidence** | Same as item 1. |
| **Confirm?** | **No**. MR-3. |

### 6. Relevance of adaptive learning: "continuously learning"

| | |
|---|---|
| **Where** | p. 12, Adaptive Learning Systems, relevance paragraph |
| **Find text** | "continuously learning from student performance data" |
| **Current** | "…the proposed AI recommender module is designed to operate adaptively—continuously learning from student performance data and adjusting course recommendations accordingly. By providing learners with a dynamically evolving set of course suggestions…" |
| **Replacement** | "…the proposed AI recommender module is designed to operate adaptively: each time a student submits a quiz, the system re-evaluates the result and updates the student's course recommendation, suggesting a harder, similar or more basic course. By refreshing the suggestions as the student progresses…" |
| **Reason** | Nothing in the system "learns". The recommendation is recalculated from the latest quiz result. |
| **Evidence** | Items 1 and 3. |
| **Confirm?** | **No**. MR-21. |

### 7. Relevance of payment gateway: GCash and Maya directly

| | |
|---|---|
| **Where** | p. 18, Payment Gateway, relevance paragraph |
| **Find text** | "designed to support course enrollment fees through GCash and Maya" |
| **Current** | "…the platform includes a built-in local payment module designed to support course enrollment fees through GCash and Maya." |
| **Replacement** | "…the platform integrates PayMongo, a local payment gateway, to collect course enrollment fees through GCash, Maya and card payments." |
| **Reason** | The system connects to PayMongo, not to GCash or Maya directly. Naming PayMongo also matches the Technology Stack (p. 104) and Table 19. |
| **Evidence** | `config/services.php` (PayMongo keys); `TransactionController` (checkout session, webhook `checkout_session.payment.paid`); migration `2026_10_16_000008_add_paymongo_method_to_transactions_table.php` (gcash, paymaya, card). Tests: `PayMongoMethodUsedTest`, `PaymentMethodsTest`. |
| **Confirm?** | **No**. MR-4. |

### 8. Conceptual Framework: "user training module"

| | |
|---|---|
| **Where** | p. 20 (first paragraph after Figure 1) and p. 21 (second paragraph) |
| **Find text** | "a user training module" and "within its training module" |
| **Current** | p. 20: "The PATHWISE Conceptual Framework consists of a learning portal, an AI-driven course delivery engine, a user training module, and a set of reference concepts." p. 21: "Access to these features is mediated through User Training, … which is why PATHWISE separates Instructor Access from Student Access within its training module." |
| **Replacement** | p. 20: "…consists of a learning portal, an AI-driven course delivery engine, role-based user access, and a set of reference concepts." p. 21: "Access to these features is mediated through role-based User Access, since Xue et al. (2024) and Bayaga and du Plessis (2024) found that performance expectancy, effort expectancy, and facilitating conditions shape whether instructors and students adopt a new educational platform, which is why PATHWISE gives instructors and students separate, role-specific dashboards." |
| **Reason** | There is no training module in the system. Role-based access (separate Student, Teacher, Administrator and Super Admin dashboards) is what exists. |
| **Evidence** | `routes/web.php`: `role:admin`, `role:teacher`, `role:student`, `role:super_admin` route groups; `DashboardController`. Tests: `DashboardTest`, `CrossAccountAccessTest`. |
| **Confirm?** | **Group + Figure 1 must be redrawn** (the "User Training / Instructor Access / Student Access" box must be renamed in the image). MR-23. |

## C. Chapter III — Use cases and storyboards

### 9. Use case diagram text: teachers "grading quizzes"

| | |
|---|---|
| **Where** | p. 50, text below Figure 20 |
| **Find text** | "monitoring student progress while grading quizzes" |
| **Current** | "Teachers, meanwhile, are responsible for managing courses and lessons, submitting courses for administrative approval, and monitoring student progress while grading quizzes." |
| **Replacement** | "Teachers, meanwhile, are responsible for managing courses, lessons, quizzes and assignments, submitting courses for administrative approval, inviting students, monitoring student progress, and grading assignment submissions, with optional AI-suggested scores that they approve or override." |
| **Reason** | Quizzes are scored automatically (FR g). Teachers grade assignment submissions. |
| **Evidence** | `QuizController.php` line ~1420 (`$score++`, automatic scoring); `SubmissionController::teacherGrade`, `SubmissionController::aiEvaluate`. Tests: `QuizScoringTest`, `TeacherAiReviewDashboardTest`. |
| **Confirm?** | **No**. If the group adds new use cases (reviews, assignments), Figure 20 must also be redrawn. |

### 10. Enroll in Course use case

| | |
|---|---|
| **Where** | p. 53, Figure 23, System Use Case Model — Enroll in Course |
| **Find text** | "Use Case Name: Enroll in Course" |
| **Current** | Brief Description: "…enroll in a selected course, including processing the payment for paid courses." Steps: "2. Click the enroll button and submit the payment." / "2.1 Get the payment details and process the transaction. 2.2 Record the enrollment and grant access to the course content." |
| **Replacement** | Brief Description: "This lets the student browse available courses in the marketplace, or open an invitation link from a teacher, and enroll in the course. Free courses are enrolled immediately; paid courses are enrolled after the PayMongo payment is confirmed." Steps (User): "1. Browse the course marketplace and select a course, or open an invitation link. 2. Click Enroll (free course) or Purchase Course (paid course). 3. For a paid course, complete the payment on the PayMongo checkout page." Steps (System): "1.1 Display the course details and enrollment option. 2.1 For a free course, record the enrollment and grant access. 3.1 For a paid course, create a pending transaction and redirect to PayMongo. 3.2 When PayMongo confirms the payment, approve the transaction, record the enrollment and grant access to the course content." |
| **Reason** | The current text says every enrollment includes a payment. Free courses skip payment; the system never takes card or e-wallet details itself; invitations are missing. |
| **Evidence** | `CourseController::enroll` lines ~325–335 ("this route may only enroll students in FREE courses"); `TransactionController::store` / `webhook`; `CourseInvitationController::accept`. Tests: `MarketplaceEnrollmentTest`, `EnrollmentAndPaymentTest`, `CourseInvitationTest`, `PayMongoWebhookSecurityTest`. |
| **Confirm?** | **No**. MR-19. |

### 11. Get AI-Recommended Courses: "AI recommendation model"

| | |
|---|---|
| **Where** | p. 56, Figure 26, System Use Case Model — Get AI-Recommended Courses |
| **Find text** | "using the AI recommendation model" |
| **Current** | Brief Description: "…analyze the student's learning behavior (e.g., enrolled courses, quiz performance, engagement)…". Precondition: "…sufficient learning activity data…". System step 1.1: "Analyze the student's learning behavior and performance data." 2.1: "Generate course suggestions using the AI recommendation model." |
| **Replacement** | Brief Description: "This lets the system use the student's latest quiz result, enrolled and completed courses, and course categories and difficulty levels to select a suitable next course, with an explanation written by the Google Gemini API, displayed on the student's dashboard." Precondition: "The student must be logged in and have submitted at least one quiz." System: "1.1 After each quiz submission, select the next course using the quiz result, category and difficulty rules. 1.2 Request a personalized explanation from the Gemini API (a fixed explanation is used if Gemini is unavailable). 2.1 Display the recommended course and its explanation on the dashboard." |
| **Reason** | There is no "model". "Engagement" data is not used. Recommendations are created when a quiz is submitted, not when the dashboard is opened. |
| **Evidence** | Items 1 and 3. `AIRecommendationController::studentRecommendations` only reads saved records. |
| **Confirm?** | **No**. |

### 12. Manage Courses and Lessons: "save/publish"

| | |
|---|---|
| **Where** | p. 55, Figure 25, System Use Case Model — Manage Courses and Lessons |
| **Find text** | "Click save/publish" |
| **Current** | User step: "3. Click save/publish." |
| **Replacement** | "3. Click Save. (To publish, the teacher submits the course for approval; see Submit Course for Approval.)" |
| **Reason** | Teachers cannot publish. Only the administrator's approval publishes a course. Decision already recorded on November 4. |
| **Evidence** | `CourseController::approve` line ~1089–1099: only a `pending` course can be approved, and approval sets `status => 'published'`; `CourseController::submitForApproval` (line ~854). Test: `CourseApprovalWorkflowTest`. |
| **Confirm?** | **No** (decided Nov. 4). MR-24. |

### 13. Registration Page logic: no email verification

| | |
|---|---|
| **Where** | p. 66, Registration Page (Screen No. 3), Logic step 5 |
| **Find text** | "automatically redirect the user to their dashboard" |
| **Current** | "5 Clicking Create Account with valid inputs will create the account and automatically redirect the user to their dashboard based on their selected role (Student or Teacher) 6. Clicking the Log In link redirects the user to the Log-in Page." |
| **Replacement** | "5. Clicking Create Account with valid inputs will create the account and send a verification link to the user's email address. Until the email is verified, the user is shown a page asking them to check their email, with an option to resend the link. 6. Clicking the verification link verifies the account and redirects the user to their dashboard based on their selected role (Student or Teacher). 7. Clicking the Log In link redirects the user to the Log-in Page." |
| **Reason** | It contradicts the Create Account use case (p. 51) and the Program Workflow (p. 41), which both correctly say the email is verified first. |
| **Evidence** | `routes/web.php` line 33: every dashboard is inside `Route::middleware(['auth', 'verified'])`. Tests: `tests/Feature/Auth/EmailVerificationTest.php`, `tests/Feature/Auth/RegistrationTest.php`. |
| **Confirm?** | **No**. |

### 14. Super Admin Dashboard: "add, edit or remove user accounts"

| | |
|---|---|
| **Where** | p. 68, Super Admin Dashboard (Screen No. 4), Logic step 3 |
| **Find text** | "add, edit or remove user accounts" |
| **Current** | "3. The Super Admin will click Manage Users to add, edit or remove user accounts across all roles." |
| **Replacement** | "3. The Super Admin will click Manage Users to add, edit, activate or deactivate user accounts across all roles. Deactivated users cannot log in, and their records are kept." |
| **Reason** | Accounts are never deleted. This also matches the use case on p. 61 and Table 5 (`is_active`). |
| **Evidence** | `routes/web.php` lines 107–128: index, create, store, role, status, verify-email, edit, update; **no delete route**. `UserManagementController::updateStatus`. Tests: `AccountDeactivationTest`, `UserManagementTest`. |
| **Confirm?** | **No**. MR-15. |

### 15. Administrator Dashboard and Figure 30: AI "settings" and certificate "templates"

| | |
|---|---|
| **Where** | p. 71, Administrator Dashboard (Screen No. 5), Logic steps 7–8; p. 60, Figure 30, Manage Certificates and AI Recommendations |
| **Find text** | "configure the AI-based course suggestion settings"; "configure and issue completion certificates"; "configure certificate templates"; "set up and update certificate templates" |
| **Current** | p. 71 step 7: "…click Manage Certificates to configure and issue completion certificates." Step 8: "…click Manage AI Recommendations to configure the AI-based course suggestion settings." p. 60 Purpose: "To allow administrators to configure certificate templates and oversee…". Brief Description: "…set up and update certificate templates used for course completion…". Post-Condition: "Certificate templates are updated…". |
| **Replacement** | p. 71 step 7: "…click Manage Certificates to view and manage issued certificates and to update the certificate template (title, body text and signatory)." Step 8: "…click Manage AI Recommendations to review, generate and remove AI course recommendation records." p. 60: replace "certificate templates" with "the certificate template (title, body text and signatory)" in Purpose, Brief Description and Post-Condition. |
| **Reason** | There is one editable certificate template, and there are no AI settings. Administrators list, generate and delete recommendation records. |
| **Evidence** | `routes/web.php` lines 206 and 215–222 (`ai-recommendations` resource without edit/update; `certificate-template.edit/update/preview`); `AIRecommendationController` methods `index`, `create`, `store`, `destroy`; `CertificateTemplateController`; Table 29 (Certificate_Settings, one row). Test: `CertificateTemplateTest`. |
| **Confirm?** | **No**. |

### 16. Student Dashboard screen number

| | |
|---|---|
| **Where** | p. 73, Student Dashboard storyboard |
| **Find text** | "Screen No: 8" |
| **Current** | "Screen No: 8" (screens go 1, 2, 3, 4, 5, 6, **8**) |
| **Replacement** | "Screen No: 7" |
| **Reason** | Numbering skips 7. |
| **Evidence** | The manuscript itself. |
| **Confirm?** | **No** (formatting). |

### 17. AI System Architecture: code languages leave out Python

| | |
|---|---|
| **Where** | p. 48, AI System Architecture, paragraph on the Output Evaluation module |
| **Find text** | "HTML/CSS/JavaScript, Java, C, or PHP" |
| **Current** | "For code written in HTML/CSS/JavaScript, Java, C, or PHP, the evaluator reads the source code…" |
| **Replacement** | "For code written in HTML/CSS/JavaScript, Java, C, PHP, or Python, the evaluator reads the source code…" |
| **Reason** | The Scope (p. 6) lists Python, and the system supports it. The two passages must match. |
| **Evidence** | `app/Models/Assignment.php` lines ~13–36 (`'python' => ['label' => 'Python', 'extensions' => ['py']]`). Test: `CodeAssignmentLanguageTest`. |
| **Confirm?** | **No**. |

## D. Data dictionary (pp. 77–102)

### 18. Roles table missing from the body

| | |
|---|---|
| **Where** | p. viii, List of Tables, lists "DATA DICTIONARY OF ROLES TABLE" (Table 4, p. 77); p. 77, body starts at Table 5 (Users) |
| **Find text** | "DATA DICTIONARY OF ROLES TABLE" |
| **Current** | The Roles table appears in the List of Tables but not in the chapter. |
| **Replacement** | Insert Table 4 before Table 5: **Table 4. DATA DICTIONARY OF ROLES TABLE** — `id` PK, int, No, Role identification · `name` Unique, varchar, No, Name of the role (super_admin, admin, teacher, student) · `description` Null, varchar, Yes, Description of the role · `created_at` Null, timestamp, Yes, Record creation date and time · `updated_at` Null, timestamp, Yes, Record last updated date and time. Caption: "Table 4 illustrates the data dictionary of the Roles table, which stores the four user roles of PATHWISE." |
| **Reason** | Table 6 (User_Roles) refers to `role_id`, but the table it references is not documented. |
| **Evidence** | `database/migrations/2026_06_13_131743_create_roles_table.php` (id, name unique, description nullable, timestamps). |
| **Confirm?** | **No**. |

### 19. Table 9 Courses: `is_free` and status values

| | |
|---|---|
| **Where** | p. 81, Table 9, DATA DICTIONARY OF COURSES TABLE |
| **Find text** | "Indicates whether the course is free"; "Draft, Pending, Approved, or Published" |
| **Current** | Row `is_free` (Not Null, int, No). Row `status`: "Current course status (Draft, Pending, Approved, or Published)". |
| **Replacement** | **Remove the `is_free` row.** Add a sentence to the `price` row: "Course enrollment fee; a price of 0 means the course is free". Status: "Current course status (Draft, Pending, Rejected, or Published). A rejected course has been returned to the teacher for revision; Approved is reserved and not used, because approval publishes the course." Also add the missing `created_at` / `updated_at` rows, as in the other tables. |
| **Reason** | There is no `is_free` column; the system treats price 0 as free. The status enum also contains Rejected, which is how "returned for revision" is stored. |
| **Evidence** | `database/migrations/2026_06_13_130813_create_courses_table.php` (status enum: draft, pending, approved, rejected, published; no `is_free`); `resources/views/Student/course-show.blade.php` line 83 (`$isFree = $price <= 0`); `CourseController::reject` line ~1144 (`'status' => 'rejected'`); `CourseController::approve` (`'status' => 'published'`). Test: `DatabaseSchemaTest`, `CourseApprovalWorkflowTest`. |
| **Confirm?** | **No**. |

### 20. Table 12 Lesson_Progress: `time_spent_minutes`

| | |
|---|---|
| **Where** | p. 85, Table 12, DATA DICTIONARY OF LESSON_PROGRESS TABLE |
| **Find text** | "Total time spent on the lesson" |
| **Current** | "Total time spent on the lesson" |
| **Replacement** | "Reserved for the time spent on the lesson; not recorded in this version (always 0)" — or remove the row. |
| **Reason** | Nothing writes this field. A panelist who asks for time-on-task reports will not find them. |
| **Evidence** | `app/Services/EngagementReport.php` line 16: "the platform does not record it (time_spent_minutes stays 0)". Test: `EngagementReportTest`. |
| **Confirm?** | **Group** (reword or remove). MR-7. |

### 21. Table 14 Quiz_Questions: `points`

| | |
|---|---|
| **Where** | p. 87, Table 14, DATA DICTIONARY OF QUIZ_QUESTIONS TABLE |
| **Find text** | "Points assigned to the question" |
| **Current** | "Points assigned to the question" |
| **Replacement** | "Points entered for the question; stored for reference. Each correct answer counts as one point when the quiz score is computed." |
| **Reason** | The quiz score counts one point per correct answer; the `points` value is not used in scoring. |
| **Evidence** | `QuizController.php` line 1420 (`$score++`); percentage = correct answers ÷ number of questions. Test: `QuizScoringTest`. |
| **Confirm?** | **No** (decided Oct. 17, MR-8). |

### 22. Table 16 Assignments: missing fields

| | |
|---|---|
| **Where** | p. 88–90, Table 16, DATA DICTIONARY OF ASSIGNMENTS TABLE |
| **Find text** | "due date, maximum score, and publication status" |
| **Current** | Rows stop at `ai_evaluation_enabled`. The caption says the table "defines the assignment details, due date, maximum score, and publication status", but those fields are not listed. |
| **Replacement** | Add rows: `due_date` Null, datetime, Yes, Date and time the assignment is due · `max_score` Not Null, int, No, Highest score for the assignment (default 100) · `is_published` Not Null, int, No, Indicates whether students can see the assignment · `created_at` / `updated_at` as in the other tables. |
| **Reason** | The caption describes fields that are missing. |
| **Evidence** | `database/migrations/2026_06_13_130902_create_assignments_table.php` lines 31–35. |
| **Confirm?** | **No**. MR-9. |

### 23. Table 17 Submissions: missing fields

| | |
|---|---|
| **Where** | p. 90, Table 17, DATA DICTIONARY OF SUBMISSIONS TABLE |
| **Find text** | "Final feedback given by the teacher" |
| **Current** | Rows stop at `ai_improvements`. The caption mentions "submission status", but there is no status row. |
| **Replacement** | Add rows: `status` Not Null, enum, No, Submission status (Submitted, Graded, Returned) · `submitted_at` Null, timestamp, Yes, Date and time of submission · `ai_evaluation_status` Null, varchar, Yes, Status of the AI evaluation (Not Requested, Processing, Completed, Failed) · `ai_evaluated_at` Null, timestamp, Yes, Date and time of the AI evaluation · `created_at` / `updated_at`. |
| **Reason** | Missing fields; the "returned for revision" behaviour and the AI evaluation step depend on them. |
| **Evidence** | `database/migrations/2026_06_13_130907_create_submissions_table.php` lines 33–39; `2026_10_07_022328_add_ai_analysis_fields_to_submissions_table.php` (`ai_evaluation_status`, `ai_evaluated_at`); `SubmissionController.php` lines 349, 566, 618, 640 (`not_requested`, `processing`, `completed`, `failed`). Test: `StudentAiVisibilityTest`. |
| **Confirm?** | **No**. |

### 24. Table 18 PAYMENTS: placeholder for a dropped table

| | |
|---|---|
| **Where** | p. 91, Table 18, DATA DICTIONARY OF PAYMENTS TABLE; p. viii List of Tables; Figures 39–40 |
| **Find text** | "Payment identification" |
| **Current** | A table with only `id`, `created_at`, `updated_at`, and the caption "stores the payment records…". |
| **Replacement** | **Remove Table 18**, its caption and its List of Tables entry. Remove the Payments entity from Figure 39 (Database Design) and Figure 40 (ERD). Payment data is in Table 19 (Transactions). Then renumber all tables once (see item 27). |
| **Reason** | The table no longer exists; it was an empty placeholder and was dropped. |
| **Evidence** | `database/migrations/2026_10_21_000001_drop_unused_payments_and_course_reviews_tables.php`. Test: `DatabaseSchemaTest`. |
| **Confirm?** | **No**, but the **figures must be redrawn**. MR-12. |

### 25. Table 21 COURSE_REVIEWS: placeholder

| | |
|---|---|
| **Where** | p. 94–95, Table 21, DATA DICTIONARY OF COURSE_REVIEWS TABLE |
| **Find text** | "Course review identification" |
| **Current** | Only `id`, `created_at`, `updated_at`. |
| **Replacement** | `id` PK, int, No, Course review identification · `course_id` FK, int, No, Course identification · `student_id` FK, int, No, Student who wrote the review · `rating` Not Null, int, No, Rating from 1 to 5 stars · `comment` Null, text, Yes, Written review · `is_hidden` Not Null, int, No, Indicates whether an administrator has hidden the review · `hidden_reason` Null, varchar(500), Yes, Reason given by the administrator for hiding the review · `hidden_by` FK, int, Yes, Administrator who hid the review · `created_at` / `updated_at`. Add: "Each student can review a course only once (unique course_id and student_id)." Caption: "…stores the ratings and reviews students give to courses they are enrolled in. Hidden reviews are not shown and are not counted in the average rating." Add Course_Reviews (Courses 1–N, Users 1–N) to Figures 39–40. |
| **Reason** | The feature is built and tested; the table is still a placeholder. The survey (Appendix C, Section D, p. 126) asks about course reviews. |
| **Evidence** | `database/migrations/2026_10_21_000002_create_course_reviews_table.php`; `CourseReviewController`; routes lines 232–239, 264–268, 437–444. Test: `CourseReviewsTest` (36 tests). |
| **Confirm?** | **No** for the table. Whether to add review use cases/requirements is item 32 (**Adviser**). MR-26. |

### 26. Table 24 AI_Recommendations: `recommendation_score`

| | |
|---|---|
| **Where** | p. 96–97, Table 24, DATA DICTIONARY OF AI_RECOMMENDATIONS TABLE |
| **Find text** | "Recommendation score generated by the AI model" |
| **Current** | "Recommendation score generated by the AI model" |
| **Replacement** | "The student's quiz percentage that led to the recommendation (0–100)" |
| **Reason** | No AI model produces this value. |
| **Evidence** | `RecommendationService.php` line ~188: `$recommendationScore = round($percentage, 2);` with the comment "It is not an ML prediction or classification score." |
| **Confirm?** | **No**. MR-6. |

### 27. Table numbering and the List of Tables

| | |
|---|---|
| **Where** | p. viii List of Tables; p. 80 Table 8; p. 96 and p. 108 (two "Table 24"s); pp. 97–102 Tables 25–30 |
| **Find text** | "Table 24"; "COURSE_TEMPLATES TABLE" |
| **Current** | (a) There are two different Table 24s: AI_RECOMMENDATIONS (p. 96) and LIST OF MODULES (p. 108). (b) The List of Tables stops at Table 24 and does not list Tables 25–30. (c) Table 8 (Course_Templates) has no header row (Field Name / Constraints / Data Type / Allow Nulls / Description). (d) The List of Tables header says "Name" in the "Table No." area. (e) Captions are inconsistent: Tables 5–24 start with "illustrates…" with no table number, while Tables 25–30 start with "Table 25 illustrates…". |
| **Replacement** | After items 18 and 24: number the tables once, in order (Roles = 4 … List of Modules = last), regenerate the List of Tables with Word's *Insert Table of Figures → Table* caption list, add the header row to Table 8, and start every caption with "Table N illustrates…". |
| **Reason** | Formatting; panel members check these lists. |
| **Evidence** | The manuscript itself. |
| **Confirm?** | **No** (formatting). |

## E. Network, technology stack, specifications and modules

### 28. Network Design and Topology: "GCash/Maya" gateway

| | |
|---|---|
| **Where** | p. 102, Network Design (Figure 41) paragraph; p. 103, Network Topology (Figure 43) paragraph |
| **Find text** | "Payment Gateway API (GCash/Maya)"; "GCash/Maya payment gateway" |
| **Current** | p. 102: "…a Payment Gateway API (GCash/Maya) for processing course enrollment fees…". p. 103: "The web server also connects externally to the GCash/Maya payment gateway…". |
| **Replacement** | p. 102: "…the PayMongo payment gateway API (GCash, Maya and card) for processing course enrollment fees…". p. 103: "The web server also connects externally to the PayMongo payment gateway (GCash, Maya and card) and to the Google Gemini API, which function as external nodes…". Update the labels in Figures 41 and 43 the same way. |
| **Reason** | Same as item 7. The Gemini API is also an external node the topology leaves out. |
| **Evidence** | Item 7; `config/services.php` (`gemini`, `paymongo`). |
| **Confirm?** | **No**, but **Figures 41 and 43 must be relabelled**. MR-5. |

### 29. Network Design: "four external components" but three listed

| | |
|---|---|
| **Where** | p. 102, Network Design paragraph |
| **Find text** | "four external components" |
| **Current** | "The application server communicates with four external components: a MySQL database…, a Payment Gateway API (GCash/Maya)…, and an SMTP email service…" (three items). |
| **Replacement** | "The application server communicates with four external components: a MySQL database for data storage and retrieval, the PayMongo payment gateway API (GCash, Maya and card) for processing course enrollment fees, the Google Gemini API for AI course recommendation explanations, quiz generation and suggested evaluation of submissions, and an SMTP email service for sending email verification and password reset links, enrollment confirmations, and certificate issuance emails. Other notifications, such as course approval results and course invitations, are shown inside the system." |
| **Reason** | The count is wrong, and Gemini is missing. "Registration confirmations" are really the email verification link. Course approval results and invitations are **not** emailed; they are in-app notifications only. |
| **Evidence** | `config/services.php`, `config/mail.php`; Fortify (verification and password reset); `tests/Feature/Pathwise/EmailNotificationsTest.php`: "a free enrollment sends one confirmation email…", "completing a course sends one certificate email…", "other notification types are never emailed". `CourseInvitationController.php` line ~394 sends an in-app `PathwiseNotification`. |
| **Confirm?** | **No**. |

### 30. Technology Stack paragraph: broken sentence, Gemini missing

| | |
|---|---|
| **Where** | p. 104, Technology Stack Diagram, first paragraph |
| **Find text** | "for a responsive and modern front-end, for AI-driven course recommendations" |
| **Current** | "…Flux UI, Tailwind CSS v4, and Chart.js for a responsive and modern front-end, for AI-driven course recommendations, quiz generation, and assignment evaluation., DomPDF for certificate generation…" |
| **Replacement** | "…Flux UI, Tailwind CSS v4, and Chart.js for a responsive and modern front-end, the Google Gemini API (gemini-2.5-flash) for AI-driven course recommendation explanations, quiz generation, and suggested assignment evaluation, DomPDF for certificate generation…" |
| **Reason** | The words "the Google Gemini API" were deleted, leaving a broken sentence and a stray "., ". |
| **Evidence** | `config/services.php` line 43 (`gemini-2.5-flash`). |
| **Confirm?** | **No**. |

### 31. Software Development Specification

| | |
|---|---|
| **Where** | p. 106, Software Development Specification |
| **Find text** | "Machine Learning / API Integration"; "Windows 10 and above" |
| **Current** | "Machine Learning / API Integration: - Google Gemini API (gemini-2.5-flash) for AI-powered recommendations - PayMongo API for payment processing" … "Running in an Operating System such as: - Windows 10 and above" (also an empty bullet "-" at the end of the Front-end list). |
| **Replacement** | "AI and API Integration: - Google Gemini API (gemini-2.5-flash) for course recommendation explanations, AI quiz generation and suggested evaluation of student submissions - PayMongo API for payment processing (GCash, Maya, card)". In the Back-end list, change "PHP 8.3" to "PHP 8.4". Replace the operating-system lines with: "Deployment Environment: - Server: Linux web hosting (Hostinger) running PHP 8.4 or later and a MySQL-compatible database. Client Requirements: - Any desktop, laptop, tablet or smartphone with an up-to-date web browser (e.g., Google Chrome, Microsoft Edge, Mozilla Firefox or Safari) and an Internet connection; no installation is required." Delete the empty bullet. |
| **Reason** | No machine learning is used; Gemini does three things, not one; the server is Linux hosting and users only need a browser. **PHP 8.3 is not enough:** `composer.json` allows `^8.3`, but 21 locked Symfony 8 packages require PHP 8.4.1 or later, so the system cannot be installed on PHP 8.3. The same "PHP 8.3" appears in the Technology Stack paragraph (p. 104, "Laravel 13 with PHP 8.3"); change it to PHP 8.4 too. |
| **Evidence** | Item 1; `composer.lock` (`composer why-not php 8.3.99` lists 21 Symfony packages, e.g. `symfony/console`, `symfony/http-foundation`, `symfony/serializer`, each requiring `php >=8.4.1`); tests run on PHP 8.4.22; `composer.json` now requires `"php": "^8.4.1"` and `docs/DEPLOYMENT_HOSTINGER.md` says PHP 8.4 minimum (both updated Nov. 5, not yet committed); FINALIZATION_REPORT FA.3 (every page checked at 390/768/1280 px with 0 overflow, **in Microsoft Edge only**). |
| **Confirm?** | **Group**: name only browsers you will actually test before the defense. Chrome, Firefox and Safari have **not** been tested yet. |

### 32. Functional requirements

| | |
|---|---|
| **Where** | p. 107, Program Specification, Functional Requirements a–p |
| **Find text** | "create, edit, and publish courses"; "enroll in available courses."; "downloadable certificate upon course completion"; "hybrid rule-based and AI approach" |
| **Current** | c: "The teachers can create, edit, and publish courses with lessons and quizzes." d: "The students can browse the course marketplace and enroll in available courses." h: "The system generates a downloadable certificate upon course completion." i: "The system provides personalized learning path recommendations using a hybrid rule-based and AI approach." The list stops at p. |
| **Replacement** | c: "Course Management Process. The teachers can create and edit courses with lessons, quizzes and assignments, using a course template, and submit them for the administrator's approval; the administrator's approval publishes the course." d: "Course Enrollment Process. The students can browse the course marketplace and enroll in available courses, or join a course through an invitation link or code sent by the teacher." h: "Certificate Generation Process. The system generates a downloadable certificate with a unique certificate number when a student completes a course that offers a certificate, and anyone can verify the certificate number on the public verification page." i: "AI Recommendation Process. After each quiz, the system recommends a next course chosen by rules based on the student's quiz result, course category and difficulty, with a personalized explanation generated by the Google Gemini API, and can generate a learning path of related courses." Add after p: **q. Output Submission Process.** Teachers can create assignments for their courses, and students can submit an essay, a file, or a code answer; teachers grade each submission with a score and feedback or return it for revision. **r. AI Quiz Generation Process.** Teachers can request AI-generated quiz questions while a course is a Draft or returned for revision; the questions are added to the quiz and reviewed by the teacher, and by the administrator during course approval, before the course is published. **s. AI Output Evaluation Process.** When enabled by the teacher, the system generates a suggested score and feedback for a submission, which the teacher approves or overrides before the student can see it. **t. Course Review Process.** Students can rate (1 to 5 stars) and review the courses they are enrolled in, once per course, and can edit or delete their review; teachers can view the reviews of their own courses, and administrators can hide inappropriate reviews with a reason. |
| **Reason** | c says teachers publish (they cannot). Objectives 2.3 "quiz generation" and 2.4 "output evaluation" (p. 4) and the Scope (pp. 5–6) promise features that have no requirement. |
| **Evidence** | c: `CourseController::approve`, `CourseApprovalWorkflowTest`. d: `CourseInvitationTest` (14). h: `QuizController.php` line ~1931 and `LessonController.php` line ~560 (certificate only if `certificate_available`); `/verify-certificate` route; `CertificateVerificationTest`, `CertificateNumberTest`. i: items 1, 3; `LearningPathGenerationTest`. q: `AssignmentController`, `SubmissionController::studentStore/teacherGrade`, `CodeAssignmentRequirementsTest`, `AssignmentLockTest`. r: `QuizController::teacherGenerateQuestions`, `GeminiQuizService`, `AiQuizGenerationTest`. s: `SubmissionController::aiEvaluate`, `GeminiEvaluationService`, `StudentAiVisibilityTest`. t: `CourseReviewController`, `CourseReviewsTest`. |
| **Confirm?** | **Adviser** for adding q–t (they add requirements). c, d, h and i are factual fixes. MR-18, MR-24, MR-25. |

### 33. Non-functional requirement: "laptop or desktop"

| | |
|---|---|
| **Where** | p. 108, Non-functional Requirements |
| **Find text** | "The user must have a laptop or desktop to access the website." |
| **Current** | "-The user must have a laptop or desktop to access the website." |
| **Replacement** | "- The user must have a desktop, laptop, tablet or smartphone with an up-to-date web browser to access the website." Optional, for the backup line: "- The system must have a backup and recovery of the database (daily database backups and weekly full backups)." Also fix "computerliteracy" → "computer literacy". |
| **Reason** | The pages were tested at phone and tablet widths. The Mobile Learning relevance paragraph (p. 16) already says the site is responsive, so the NFR contradicts the paper. |
| **Evidence** | FINALIZATION_REPORT FA.3 (345 page loads, 0 horizontal overflow); `BackupScheduleTest`. |
| **Confirm?** | **No**. |

### 34. List of Modules table

| | |
|---|---|
| **Where** | pp. 108–109, Table 24 (List of Modules) |
| **Find text** | "Publish/Unpublish Course" |
| **Current** | Course Management Modules has a Teacher row "Publish/Unpublish Course". No modules for assignments, AI quiz generation, AI evaluation, invitations or reviews. |
| **Replacement** | Remove "Publish/Unpublish Course" (or rename it to "Delete or Archive Draft Course" only if the group wants to keep the row count). Add rows (proponent to be assigned by the group): Teacher: *Create Assignment*, *Grade Submission (with AI suggestion)*, *Generate Quiz with AI*, *Invite Students*, *View Course Reviews*. Student: *Submit Output*, *Accept Invitation*, *Rate and Review Course*. Administrator: *Moderate Course Reviews*, *Manage Learning Paths*, *Review Student Removal Requests*. Super Admin: *Manage Departments*. Recount the "No. of point" rows. |
| **Reason** | Teachers cannot publish. Many built features have no module, and the list is used to show each member's share of the work. |
| **Evidence** | `routes/web.php` (teacher assignments, invitations lines 390–404, reviews lines 264–268; admin course reviews 232–239; departments 172); admin learning paths line 224; tests: `CourseInvitationTest`, `CourseReviewsTest`, `AiQuizGenerationTest`, `StudentRemovalRequestTest`, `LearningPathGenerationTest`. |
| **Confirm?** | **Group + Adviser**: the group decides which member is credited with each new module; the adviser confirms adding modules. |

## F. Front matter (item 35) — information only the group can provide

Nothing below has been filled in. **Do not let anyone guess these values.**

| # | Where | What is missing or inconsistent | What the group must provide |
|---|---|---|---|
| 35a | p. ii, Approval Sheet | "…with a group verdict of on July 4, 2026." The verdict word is missing, and July 4, 2026 is before the December 2026 cover date | The actual **verdict** (e.g., "Passed") and the actual **oral defense date**, from the official defense record |
| 35b | p. ii, Approval Sheet | Chairman and both Members are "TBA" | The **full names and titles of the panel chair and two members**, spelled exactly as in the official panel assignment |
| 35c | p. ii, Approval Sheet | "Date:" under the Dean is blank | Leave blank for the Dean to date when signing, unless the college says otherwise (**ask the adviser**) |
| 35d | p. 33, Table 2, Distribution of Respondents | Every number is 0 (Students 0 / 0 %, Instructors 0 / 0 %, Total 0 / 0 %) | The **actual counts from Google Forms**, and percentages = count ÷ total × 100 |
| 35e | p. 30 (Research Design) and p. 33 (Respondents) | Both say 495 students and 5 instructors (500 total) | **Confirm** these are the real response counts. If the actual Google Forms responses differ, change pp. 30 and 33 to match Table 2. Note: p. 33 says "the instructor" (singular) in one place, while the count is five |
| 35f | p. ix, List of Figures | Figure 26 page number is "5" | Should be **56**. Regenerate the whole list after redrawing figures |
| 35g | p. ix, List of Figures | Figures 4–10 and 14–26 are printed as a column of numbers followed by a column of names, so numbers and names do not line up | Regenerate the List of Figures with Word's *Insert Table of Figures*, using the figure captions |
| 35h | p. 119, Appendix A | Only the heading "Transmittal Letter" has text; the letter itself may be an image that this check cannot read | **Check** that the signed, scanned transmittal letter (as submitted to the Dean) is on this page |
| 35i | p. v, Table of Contents | Appendix page numbers (Appendices 114, Map 115, Survey 116) do not match where they fall when the file is rendered (pp. 119–121) | Update the Table of Contents in Word (*References → Update Table → Entire table*) after all edits |

---

## G. Optional additions carried over from the December check

These are not errors. The new compilation does not mention these built
features. They were proposed against the December PDF, and they still apply to
this compilation. The full replacement wording is in the tracked
`docs/audit/MANUSCRIPT_REVISIONS.md` under the MR number shown. Each one is a
**Group** decision; G-1 and G-4 also change the scope, so check them with the
**Adviser**.

| # | Where | What to add | Evidence | Wording | Confirm? |
|---|---|---|---|---|---|
| G-1 | p. 5, Scope and Limitations, 2nd and 5th paragraphs | Course ratings and reviews in the list of core functions, and the manual hiding of inappropriate reviews in the moderation sentence | `CourseReviewController`; `CourseReviewsTest` (36); survey item on p. 126 | MR-29 | Group + Adviser |
| G-2 | p. 6, Scope, after the paragraph beginning "The output evaluation feature supports essay and code submissions" | Upload limits (submissions 25 MB, lesson files 20 MB, thumbnails and receipts 5 MB), accepted file types, and the 256 KB of file text the AI reads | `Assignment::UPLOAD_MAX_KB`; `LessonController.php` lines 764, 977 (`max:20480`); `CourseController.php` lines 84, 144 and `TransactionController.php` line 607 (`max:5120`); `SubmissionController.php` lines 183–205 | MR-16 | Group |
| G-3 | pp. 8–9, Definition of Terms | "Average Rating" (after Adaptive Learning) and "Course Review" (after Course Marketplace) | as G-1 | MR-31 | Group |
| G-4 | p. 50 (Figure 20 and its text) and after p. 61 (new use case figures) | Use cases Rate and Review Course (Student), View Course Reviews (Teacher), Moderate Course Reviews (Administrator); renumber later figures | routes lines 232–239, 264–268, 437–444; `CourseReviewsTest` | MR-32, MR-33 | Group + Adviser (goes with item 32 t) |
| G-5 | after p. 73 (Student Dashboard storyboard) | Storyboard screens for Course Marketplace, Course Page and Reviews, and teacher Course Reviews. **Note:** MR-36 numbers them 9–11 because it follows the old "Screen No: 8". After item 16 they become Screens 8–10 | `resources/views/Student/course-show.blade.php`; `CourseReviewsTest` | MR-36 | Group |

---

## H. Findings from the Final Validation Audit (November 6, 2026)

Source: `docs/audit/FINAL_VALIDATION_AUDIT.md` (browser run of all four
roles, all 51 manuscript images, column-by-column data dictionary check).
Items 1–35 and G-1 to G-5 above are unchanged; section H adds N-1 to N-26
(and N-27, which records a decision made on November 6).

**Decisions recorded on November 6, 2026 (by the group lead, for now):**
remove the unsupported gamification and adaptive-quiz claims; correct the
Lean Canvas, Figure 1, Figure 14, the learning-path description, the AI
quiz-generation description and the other inaccurate diagrams and tables;
keep the published-course editing restriction and document it. Matters that
need facts or approval the audit cannot supply are marked **Unresolved**:
do not fill them in without the group or adviser.

**Status updates for earlier items:** item 35h is **resolved** (the signed
transmittal letter is on p. 119). Items 15, 24 and 31 also apply to their
figures (N-15, N-18, N-20). Item 17 also applies to Figure 19 (N-10).
**N-18** (Figures 39 and 40, pp. 75–76, still show the dropped `payments` table and the
`Payment` entity) is handled by item 24: remove them when redrawing. Apart from that,
Figure 39 is accurate and already shows the real `course_reviews` columns.

### H.1 Chapter II and Figure 1

#### N-1. Gamification claims (decided: remove)

| | |
|---|---|
| **Where** | p. 14 (Gamification in E-Learning, relevance paragraph); p. 20 (Figure 1, "Gamification (Duterte, 2024)" box); pp. 20–21 (framework text, "Engagement within the system is reinforced by Gamification …") |
| **Find text** | "achievement-oriented elements"; "reinforced by Gamification" |
| **Current** | p. 14: "Rather than functioning as plain completion records, PATHWISE's digital certificates and progress dashboards are designed with achievement-oriented elements that reinforce learner motivation and encourage sustained engagement throughout a course, consistent with the engagement principles identified in the literature." |
| **Replacement** | p. 14: "Gamification is relevant to PATHWISE because its findings inform the motivational design of the platform: visible progress percentages, quiz results and digital certificates give learners clear milestones as they move through a course. PATHWISE does not implement game elements such as points, badges, leaderboards or streaks in this version." Framework text (pp. 20–21): replace "Engagement within the system is reinforced by Gamification, which Duterte (2024) found to produce measurable gains in motivation and academic performance, and by Digital Credentials, …" with "Engagement within the system is supported by visible progress milestones, informed by the gamification findings of Duterte (2024), and by Digital Credentials, …". Figure 1: rename the box "Progress Milestones (informed by Duterte, 2024)" or remove it. |
| **Reason** | No points, badges, leaderboards or streaks exist. The panel may ask to see them. |
| **Evidence** | No such code in `app/` or `resources/views` (searched for badge/points awarded/leaderboard/streak/achievement). Progress percentages: `Enrollment::progress_percentage`; certificates: workflow 9–11 of the audit. |
| **Confirm?** | **Decided** (remove). Inform the **adviser**, who may prefer a different wording. |

#### N-2. Adaptive / gamified assessment in Related Studies (decided: remove)

| | |
|---|---|
| **Where** | p. 26 (Idika and Saihi, 2025); p. 27 (Kulkarni et al., 2024); p. 26 also says Idika "give[s] PATHWISE's developers a concrete point of comparison for how its own … Adaptive Learning module might be technically structured" |
| **Find text** | "embedding a similar adaptive, gamified assessment approach"; "builds on this gamified learner-creator model" |
| **Current** | p. 26: "PATHWISE extends this concept by embedding a similar adaptive, gamified assessment approach within a complete PaaS-style platform that also supports course creation, marketplace discovery, and AI-driven course recommendation." p. 27: "PATHWISE builds on this gamified learner-creator model by adding an AI Recommendation Engine, Adaptive Learning, and a monetized course marketplace, resulting in a more comprehensive, intelligence-driven platform." |
| **Replacement** | p. 26: "PATHWISE applies the adaptive principle at the course level instead: after each quiz it recommends an easier, similar or harder next course, while its quizzes keep a fixed set of questions chosen by the teacher, within a complete platform that also supports course creation, marketplace discovery, and AI-assisted course recommendation." p. 27: "PATHWISE builds on this learner-creator model and its verification-and-publishing workflow by adding an AI-assisted course recommendation, a course marketplace and integrated payment." Also on p. 26, "its own AI Recommendation Engine and Adaptive Learning module" → "its own AI-assisted course recommendation". |
| **Reason** | Quizzes do not change difficulty during the attempt, and there is no gamification. |
| **Evidence** | `RecommendationService::generate` (difficulty of the next course from the quiz percentage); `QuizController` (fixed questions; one point per correct answer). |
| **Confirm?** | **Decided** (remove). Inform the adviser. |

#### N-3. "Restricted, department-integrated account creation" (Unresolved)

| | |
|---|---|
| **Where** | p. 15, Cybersecurity and Data Privacy relevance paragraph |
| **Find text** | "restricted, department-integrated account creation process" |
| **Current** | "These findings reinforce the rationale behind PATHWISE's restricted, department-integrated account creation process and its reliance on a secure payment gateway, …" |
| **Replacement (if registration stays open, recommended)** | "These findings reinforce the rationale behind PATHWISE's role-based access control, mandatory email verification, optional two-factor authentication and passkeys, Super Admin account deactivation, and its reliance on a secure payment gateway, …" |
| **Reason** | Anyone can register as a Student or Teacher with an email address that can receive mail; departments are used only to route student removal requests. Courses still need administrator approval before students see them. |
| **Evidence** | `app/Actions/Fortify/CreateNewUser.php` (`role` in student, teacher); `routes/web.php` line 33 (`verified`); `AccountDeactivationTest`; audit workflows 1–4. |
| **Confirm?** | **Unresolved: adviser.** Keeping open registration needs only this wording change. Restricting teacher registration would be a new feature (audit SYS-4) and is not approved. |

#### N-23. AI quiz generation source material (decided: correct)

| | |
|---|---|
| **Where** | p. 19, Quiz Generation relevance paragraph; p. 18, Self-Paced Learning relevance paragraph |
| **Find text** | "based on course materials uploaded by educators"; "intelligent, performance-based course sequencing" |
| **Current** | p. 19: "…the platform integrates the Google Gemini API to automatically generate quiz questions based on course materials uploaded by educators." p. 18: "By pairing visible, real-time progress tracking with intelligent, performance-based course sequencing, …" |
| **Replacement** | p. 19: "…the platform integrates the Google Gemini API to generate draft quiz questions from the text of the published reading lessons that educators write in a course; uploaded PDF and video lessons are not read by the AI." p. 18 (optional): "By pairing visible progress tracking, updated as lessons and quizzes are completed, with performance-based recommendations of the next course, …" |
| **Reason** | The AI reads only reading-lesson text; generation is refused until such a lesson exists. Progress updates when a lesson or quiz is completed, not continuously. |
| **Evidence** | `app/Services/GeminiQuizService.php` (text lessons only); refusal message "Add published Reading lessons with content before generating AI questions" (audit workflow 17); `AiQuizGenerationTest`. |
| **Confirm?** | **Decided** (correct). |

#### N-6. Figure 1 corrections (decided: correct; one reference Unresolved)

| | |
|---|---|
| **Where** | p. 20, Figure 1 (PATHWISE's Conceptual Framework) |
| **Current (in the image)** | "User Training" box with "Instructor Module … (iv) View/Verify earnings"; the Student Module box is also labelled "Instructor's Access"; "Learnig Portal"; "AI Recomendation Engine Learning"; "Self-Paced Learning (Walker et al., 2024)" in Reference Concept; Gamification box (N-1) |
| **Correction** | Rename "User Training" to "Role-based User Access" (item 8); remove "(iv) View/Verify earnings"; label the second box "Student's Access"; fix the spelling: "Learning Portal", "AI Recommendation Engine"; handle the Gamification box as in N-1. |
| **Reason** | There is no training module and no teacher earnings page. |
| **Evidence** | No earnings route or view; routes by role in `routes/web.php`. |
| **Unresolved** | "**Walker et al., 2024**" is cited in the figure but is **not in the References** (pp. 110–114). The group must find and add the full reference, or replace it with a source already cited (for self-paced learning the text cites Ramaswami et al., 2023, and Tzeng et al., 2023). **Do not invent a reference.** |
| **Confirm?** | **Decided** (figure corrections); **Group** (Walker reference). |

#### N-26. "Platform as a Service" framing (Unresolved)

| | |
|---|---|
| **Where** | Title page subtitle and General Objective (pp. i, 4), Definition of Terms (p. 9), PaaS literature relevance (p. 11), Comparative Matrix row (p. 28) |
| **Current** | The system is "delivered as a Platform-as-a-Service" |
| **Possible wording** | "a web-based platform delivered as a cloud-hosted service and accessed through a browser, with no local installation" |
| **Reason** | PathWise is a web application that users use through a browser (closer to Software as a Service); it runs on a hosting platform. The current framing may be questioned. |
| **Confirm?** | **Unresolved: adviser.** No change unless the adviser asks for one. |

### H.2 Chapter III figures

| ID | Page / figure | Current | Correction | Evidence | Confirm? |
|---|---|---|---|---|---|
| **N-5** | p. 40, Fig. 14 (Program Workflow, Administrator) | "Review submitted courses (own department)"; "Generate reports (engagement, course performance) with PDF export" | "Review submitted courses"; replace the reports box with "View student progress reports". Also add "Review removal requests: own department, teachers without a department, and departments without a Department Head; all requests if the administrator has no department" | `CourseController::approve` (no department filter); only `super-admin/reports` and `teacher/analytics` exist; removal routing `StudentRemovalRequest::scopeReviewableBy` (Phase 4 B2) | **Decided** (correct) |
| **N-7** | p. 38, Fig. 10 (Lean Model Canvas) | Unfair Advantage: "Continuous improvement through data analytics and machine learning"; Revenue Streams: "Subscription fees (premium features)", "Transaction fees from marketplace", "Commission from course sales", "Certificate generation fees", "Partnerships and sponsorships"; Key Metrics: "User engagement (login frequency, time spent)" | Unfair Advantage: "…through data analytics and AI-assisted features (Google Gemini API)". Revenue Streams: keep "Course enrollment fees" as implemented and label the others "**Planned (not in this version)**", or remove them. Key Metrics: remove "time spent" or label it planned | No subscription, commission, certificate-fee or marketplace-fee code; time spent is never recorded (item 20) | **Decided** (correct); the choice between "label planned" and "remove" is **Group** |
| **N-8** | p. 42 (text) vs p. 43 (Fig. 17, Business Roadmap) | Text: 2025 → 2030 ("The roadmap concludes in 2030"), six stages; figure: 2025 → 2031 with different milestones (for example "2027 Beta Testing of the AI Recommendation Engine", "2031 Global Expansion") | Make the text and the figure state the same years and milestones | Manuscript only | **Unresolved: Group** (choose the real plan; do not invent dates) |
| **N-9** | p. 44, Fig. 18 (Functional Decomposition) | Lists only the original functions | Optional: add Teacher: Manage Assignments and Grading, Generate Quiz with AI, Invite Students, Request Student Removal, View Course Reviews; Student: Submit Outputs, Accept Invitation, Rate and Review Courses; Administrator: Moderate Course Reviews, Review Removal Requests; Super Admin: Manage Departments | `routes/web.php` | Group (optional) |
| **N-10** | p. 48, Fig. 19 (AI System Architecture) | Output Evaluation box: "Code (HTML/CSS/JavaScript, Java, C, PHP)" | Add **Python** | `app/Models/Assignment.php` (`python`); same fix as item 17 | No |
| **N-11** | p. 50, Fig. 20 (Use Case Diagram) | No "Create account" use case; "Monitor progress and grade quizzes" | Add "Create account" (Student, Teacher); "Monitor progress and grade submissions"; optional use cases from G-4 | Fig. 21 models Create Account; quizzes are scored automatically (item 9) | No (optional parts: Group) |
| **N-12** | p. 52, Fig. 22 (Log-in) | "Check username and password" | "Check email and password" | Login form fields `email`, `password` | No |
| **N-13** | p. 53, Fig. 23 (Enroll in Course) | "Process payment" drawn as «include» | Draw "Process payment" as «extend» (paid courses only); optionally add "Accept invitation" «extend» | Free courses enroll without payment; invitations (audit workflows 5, 29, 38) | No |
| **N-14** | p. 56, Fig. 26 (Get AI-Recommended Courses) | "Analyze learning behavior" | "Evaluate the latest quiz result" and "Generate explanation (Gemini)" | `RecommendationService`; item 11 | No |
| **N-15** | p. 60, Fig. 30 (Manage Certificates and AI Recommendations) | "Configure certificate templates"; "Adjust recommendation settings" | "Update certificate template"; "Generate or remove recommendation records" | One template (`certificate_settings`); `AIRecommendationController` (index, create, store, destroy); item 15 | No |
| **N-16** | p. 62, Fig. 32 (Login Page storyboard) | Screenshot shows a browser password-manager popup containing a demo account email | Retake the screenshot without the popup | — | No |
| **N-17** | pp. 67–73, Figs. 35–38 (dashboard storyboards) | Older dark design; browser tab "… - Laravel"; Administrator quick action "AI Recommendations — Manage suggestion settings" | Optional: retake from the current light design after setting `APP_NAME=PathWise`; at least remove "Manage suggestion settings" | `LightModeOnlyTest`; `docs/DEPLOYMENT_HOSTINGER.md` (APP_NAME) | Group (optional) |
| **N-19** | p. 102, Fig. 41 (Network Design) | Node labelled "Service" | "Email (SMTP) Service" | `config/mail.php` | No |
| **N-20** | p. 105, Fig. 44 (Technology Stack) | "Laravel 13 · PHP 8.3"; "User's Laptop / Desktop" | "PHP 8.4"; "Any device with a web browser" (same as item 31 in the text) | `composer.json` `"php": "^8.4.1"` | No |
| **N-25** | p. 31 (Fig. 7, Input: "CCS students and faculty"); p. 119 (transmittal letter: "students in programming courses and computer studies"); pp. 30, 33 (respondents "across all academic departments") | The population is described differently | State the population that was actually surveyed, consistently in all three places | Manuscript only | **Unresolved: Group** (facts only the group has; see also item 35d/e) |

### H.3 Data dictionary, storyboard table and List of Modules

| ID | Page | Current | Correction | Evidence | Confirm? |
|---|---|---|---|---|---|
| **N-4** | p. 95, Table 22 (Learning_Paths), `is_generated` | "Indicates whether the learning path was AI-generated" | "Indicates whether the learning path was generated automatically by the system from the student's quiz results (rule-based: course category and difficulty)" | `LearningPathController::generateForStudent` (no Gemini call); `LearningPathGenerationTest` | **Decided** (correct) |
| **N-21** | p. 65, Registration Page storyboard table and Logic | Password and Confirm Password: VARCHAR, size 50 | Size "—" (stored as a secure hash). Add a Logic line: "4.3 On the live site, passwords must be at least 12 characters with upper- and lowercase letters, a number and a symbol, and must not appear in a known data breach. The email address must belong to a domain that can receive email." | `AppServiceProvider` (`Password::defaults`); `app/Concerns/ProfileValidationRules.php` (`email:rfc,dns`) | No |
| **N-22** | pp. 77–92, Tables 5, 7, 8, 9, 19 | Columns missing or a constraint wrong | Table 5 Users: add `remember_token` (Null, varchar, Yes, "Token for 'Remember me' logins"), `created_at`, `updated_at`. Table 7 Course_Categories: add `created_at`, `updated_at`. Table 8 Course_Templates: add `is_active` (Not Null, int, No, "Indicates whether teachers can choose the template"), `created_at`, `updated_at`. Table 9 Courses: `description` constraint "Not Null" (the row says "Null" but Allow Nulls "No"). Table 19 Transactions: `transaction_no` constraint "Unique"; add `approved_at` (Null, timestamp, Yes, "Date and time the payment was approved"), `created_at`, `updated_at` | Column-by-column comparison with the database (`FINAL_VALIDATION_AUDIT.md` 5.7) | No |
| **N-24** | p. 109, Table 24 (List of Modules) | "View AI Learning Analytics" (Teacher) | "View Course Analytics" | `teacher/analytics` (charts of enrollments and scores; no AI) | No |

### H.4 Published-course editing (decision of November 6)

#### N-27. Document that teachers cannot change a published course

| | |
|---|---|
| **Where** | p. 55 (Manage Courses and Lessons use case); p. 107 (FR c, see item 32c); p. 72 (Teacher Dashboard logic, step 3) |
| **Add** | Use case (p. 55), Post-Condition: "…saved in the database. A course can be edited only while it is a draft or after the administrator returns it for revision; while it is pending approval or published, the teacher cannot change it." FR c (append to the item 32 wording): "Courses that are pending approval or published cannot be changed by the teacher." Teacher Dashboard step 3 (p. 72): "…to create a new course or edit a draft or returned course." |
| **Reason** | Decision of November 6: keep the approval process; teachers cannot edit published courses, and no new republishing workflow is added now. The teacher sees "This course is published, so its lessons cannot be added or changed by the teacher. Contact an administrator if a change is needed." |
| **Evidence** | `LessonController::ensureTeacherCanModifyCourse` (draft and rejected only); `CourseController::redirectIfCourseLocked`; `LockedLessonPagesTest` (Phase 4 B3); audit workflow 47. Assignments are the exception: they stay editable in a published course and are locked only while pending (`Course::ASSIGNMENT_EDITABLE_STATUSES`, `AssignmentLockTest`). |
| **Confirm?** | **Decided** (document). Any future republishing workflow needs a separate discussion. |

### H.5 Removal requests (wording after the Phase 4 fix)

Where the manuscript describes removal requests (Table 25 caption p. 98,
Table 27 caption p. 100, and any new Teacher/Administrator text), use:
"A teacher's request to remove a student goes to the Department Head of the
teacher's department. Requests from teachers without a department, or from a
department that has no Department Head, go to every administrator, and an
administrator without a department reviews all requests. The student stays
enrolled until an administrator approves the request." Evidence:
`StudentRemovalRequest::scopeReviewableBy`, `RemovalRequestRoutingTest`.
**Confirm?** No (describes the system).

### H.6 Summary of section H

| Status | Items |
|---|---|
| Decided, ready to edit | N-1, N-2, N-4, N-5, N-6 (figure), N-7 (wording), N-10, N-11, N-12, N-13, N-14, N-15, N-16, N-19, N-20, N-21, N-22, N-23, N-24, N-27, H.5 |
| Optional (group) | N-9, N-17 |
| **Unresolved: group** | N-6 (Walker et al., 2024 reference), N-7 (label planned or remove), N-8 (roadmap years), N-25 (survey population) |
| **Unresolved: adviser** | N-3 (account creation claim), N-26 (PaaS framing) |

Figures to redraw (adds to the list in the Summary below): Figures 1, 10, 14,
17 (or the text), 18 (optional), 19, 20, 22, 23, 26, 30, 41, 44; screenshots
32 and 35–38 (optional).

---

## Summary

| Section | Items | Confirm with adviser | Group decision | Factual only |
|---|---|---|---|---|
| A. Definitions | 1–4 | 4 | 2 | 1, 3 |
| B. Chapter II | 5–8 | — | 8 (and Figure 1) | 5, 6, 7 |
| C. Use cases / storyboards | 9–17 | — | — | 9–17 |
| D. Data dictionary | 18–27 | — | 20 | 18, 19, 21–27 |
| E. Specs / modules | 28–34 | 32 (q–t), 34 | 31, 34 | 28, 29, 30, 33; 32 c, d, h, i |
| F. Front matter | 35a–i | 35c | 35a, b, d, e, h | 35f, g, i |
| G. Optional additions | G-1 to G-5 | G-1, G-4 | all | — |
| H. Final validation audit (Nov. 6) | N-1 to N-27 | N-3, N-26 (unresolved) | N-6 reference, N-7, N-8, N-9, N-17, N-25 | the rest (see H.6) |

**Figures that must be redrawn or relabelled:** Figure 1 (item 8), Figure 20
(item 9, if new use cases are added), Figures 39 and 40 (items 24–25),
Figures 41 and 43 (item 28).

**Highest defense risk:** items 1, 2, 5, 6 and 26 (machine-learning claims), 12
and 32c (who publishes a course), and 32 q–t (objectives 2.3 and 2.4 have no
requirement).

## Recheck

When the corrected manuscript is ready, put it in `docs/audit/` and ask for a
recheck. Each **Find text** above should no longer be found, and each
replacement should appear on the expected page.
