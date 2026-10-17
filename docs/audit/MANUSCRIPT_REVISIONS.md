# PATHWISE — Manuscript Revisions (Round 3)

Suggested wording changes so the manuscript describes the system as it is
built. Source: `PATHWISE-UPDATES.docx` (October 6, 2026), compared with the
`Berdin` branch on October 17, 2026.

- Each item quotes the **current text** exactly, then gives **replacement
  text**. Locations use the manuscript's section, figure and table names;
  table numbers are given only where the manuscript's own label is clear.
- Nothing here changes the system. Where the system and the manuscript can be
  aligned either way, the item is marked **Team decision** and lists the
  options.
- "Why" points to the code that shows the current behaviour, so the claim can
  be checked.

## Summary

| # | Location | Topic | Status |
|---|---|---|---|
| MR-1 | Definition of Terms, "AI-Powered Recommendation System" | How the next course is chosen | Revise |
| MR-2 | Definition of Terms, "Machine Learning" | PATHWISE does not use ML | Revise |
| MR-3 | Review of Related Literature, relevance of AI | "machine learning techniques" claim | Revise |
| MR-4 | Review of Related Literature, relevance of payment gateways | Payment methods | Revise |
| MR-5 | Network Design (Figure 41), Network Topology (Figure 43) | Payment gateway and email | Revise |
| MR-6 | Data dictionary, AI_Recommendations table | `recommendation_score` | Revise |
| MR-7 | Data dictionary, Lesson_Progress table | `time_spent_minutes` | Revise |
| MR-8 | Data dictionary, Quiz_Questions table (Table 14) | `points` | Revise (decided: document 1 point per correct answer) |
| MR-9 | Data dictionary, several tables | Fields missing from the manuscript | Add rows |
| MR-10 | Data dictionary, several tables | "Allow Nulls" that are wrong | Correct |
| MR-11 | Data dictionary | Tables missing from the manuscript | Add tables |
| MR-12 | Data dictionary, Payments table | Unused placeholder table | Revise (decided: keep, "reserved for future use") |
| MR-13 | Functional Decomposition, Administrator role | Department Head scope | Optional |
| MR-14 | Fig. 19, Definition of Terms, FR (r) | AI quiz generation and the Draft course | Revise |
| MR-15 | Super Admin Dashboard, Logic | Accounts are deactivated, never deleted | Revise |
| MR-16 | Scope and Limitations | Upload and AI evaluation limits | Add |
| MR-17 | Scope and Limitations, Fig. 19 | Add Python to the code languages | Revise (decided) |
| MR-18 | FR (h), View and Download Certificate | Current template, public verification | Revise |
| MR-19 | Enroll in Course use case | Marketplace behaviour | Revise |
| MR-20 | Definition of Terms, Payment Gateway; Transactions table | Revenue distribution; payment method shown | Revise |

Already accurate (no change needed) is listed near the end, followed by the
main differences between the September 27 PDF and the October 6 DOCX.

---

## MR-1 — Definition of Terms: AI-Powered Recommendation System

**Current text**

> AI-Powered Recommendation System - An AI-powered recommender system is
> broadly defined as a software module that uses machine learning to predict
> and suggest content relevant to a specific user based on behavioral and
> historical data. In PATHWISE, this refers to the module that combines
> rule-based analysis of student performance with the Google Gemini API to
> suggest the next appropriate course.

**Replacement text**

> AI-Powered Recommendation System - An AI-powered recommender system is
> broadly defined as a software module that predicts and suggests content
> relevant to a specific user based on behavioral and historical data. In
> PATHWISE, this refers to the module that selects the next appropriate
> course with rule-based logic, using the student's latest quiz result, the
> course category and difficulty level, and the courses the student has
> already taken, and then uses the Google Gemini API to write the explanation
> shown with the recommendation.

**Why.** `app/Services/RecommendationService.php` picks the course with fixed
rules (quiz percentage → target difficulty, same category first, courses
already taken excluded; the code comment says "ordinary application logic,
not machine learning"). Gemini (`GeminiRecommendationService::generateReason`)
only writes the reason text, with a fixed fallback when Gemini is unavailable.

## MR-2 — Definition of Terms: Machine Learning

**Current text**

> Machine Learning - Machine learning, in general terms, is a subset of AI
> that enables systems  to learn from data and make predictions with minimal
> human intervention.

**Replacement text** (adds one sentence; also fixes the double space)

> Machine Learning - Machine learning, in general terms, is a subset of AI
> that enables systems to learn from data and make predictions with minimal
> human intervention. PATHWISE does not train or run its own machine learning
> models; its AI-assisted features use the Google Gemini API.

**Why.** Matches the Scope and Limitations ("The system does not train or
maintain its own AI model") and Figure 19 ("The system does not train its own
models"). Alternatively, remove the term if no other section needs it.

## MR-3 — Review of Related Literature: relevance of artificial intelligence

**Current text**

> Artificial intelligence is relevant to PATHWISE as it constitutes the core
> intelligence layer of the proposed platform. The AI recommender module in
> PATHWISE is designed to analyze student learning history, course completion
> data, and performance metrics to generate personalized next-course
> suggestions— directly applying the machine learning techniques validated in
> the literature to support continuous and adaptive learning pathways.

**Replacement text**

> Artificial intelligence is relevant to PATHWISE as it constitutes the core
> intelligence layer of the proposed platform. The AI recommender module in
> PATHWISE analyzes a student's quiz performance, completed and enrolled
> courses, course categories, and difficulty levels to suggest a suitable
> next course, and uses the Google Gemini API to explain each suggestion.
> Rather than training its own models, PATHWISE applies the personalization
> principles validated in the literature through rule-based logic combined
> with a large language model, to support continuous and adaptive learning
> pathways.

**Why.** Same as MR-1. The new wording also matches Figure 19, which already
says the module "analyzes a student's quiz performance, completed courses,
course categories, and difficulty levels".

## MR-4 — Review of Related Literature: relevance of payment gateway integration

**Current text**

> Payment gateway integration is directly relevant to PATHWISE, as the
> platform includes a built-in local payment module designed to support
> course enrollment fees through GCash and Maya.

**Replacement text** (first sentence only; the rest of the paragraph stays)

> Payment gateway integration is directly relevant to PATHWISE, as the
> platform includes a built-in payment module that collects course enrollment
> fees through PayMongo, which supports GCash, Maya, and card payments, with
> an alternative manual option in which students upload a payment receipt
> (including bank transfer) for verification by the Super Admin.

**Why.** `app/Support/PaymentMethods.php` and `config/services.php`
(`PAYMONGO_PAYMENT_METHODS`, default `gcash,card,paymaya`). The manual
receipt form (`Student/transactions/upload-proof`) offers the enabled methods
plus Bank Transfer, and the Super Admin approves or rejects it
(`super_admin.transactions.*`). If the team turns card payments off on the
live server, drop "and card" here and in MR-5.

## MR-5 — Network Design (Figure 41) and Network Topology (Figure 43)

**Current text** (last sentence of the paragraph)

> The application server communicates with three external components: a
> MySQL database for data storage and retrieval, a Payment Gateway API
> (GCash/Maya) for processing course enrollment fees, and an SMTP email
> service for sending system notifications such as registration
> confirmations, enrollment updates, and certificate issuance alerts.

**Replacement text**

> The application server communicates with three external components: a
> MySQL database for data storage and retrieval, the PayMongo payment gateway
> API (GCash, Maya, and card) for processing course enrollment fees, and an
> SMTP email service for sending email verification and password reset links,
> enrollment confirmations, and certificate issuance alerts. All other
> notifications are shown inside the platform.

**Network Topology (Figure 43) — current text**

> The web server also connects externally to the GCash/Maya payment gateway,
> which functions as an external node in the topology for processing
> financial transactions.

**Replacement text**

> The web server also connects externally to the PayMongo payment gateway
> (GCash, Maya, and card), which functions as an external node in the
> topology for processing financial transactions.

Also relabel the payment gateway in **Figures 41 and 43** from "GCash/Maya"
to "PayMongo (GCash, Maya, Card)", and, if Figure 41 or Figure 43 shows the
Google Gemini API, keep it; if not, consider adding it, since the
recommendation, quiz generation and output evaluation features call it.

**Why.** Payments: see MR-4. Email: `app/Notifications/PathwiseNotification.php`
emails only the enrollment (`enrollment_activated`, `payment_approved`) and
certificate (`certificate_issued`) types, and only when real mail is
configured; verification and password reset emails come from Laravel Fortify
(`config/fortify.php`). Every other notification is in-app only, which
matches Functional Requirement p.

## MR-6 — Data dictionary: AI_Recommendations table, `recommendation_score`

**Current text** (Description column)

> Recommendation score generated by the AI model

**Replacement text**

> The student's quiz percentage that led to the recommendation (0–100)

**Why.** `RecommendationService` stores `round($percentage, 2)`;
`AIRecommendationController` stores the student's rounded average score. No
AI model produces this value.

## MR-7 — Data dictionary: Lesson_Progress table, `time_spent_minutes`

**Current text** (Description column)

> Total time spent on the lesson

**Replacement text**

> Reserved for time spent on the lesson; not recorded in this version (always 0)

**Why.** Nothing writes this field; `app/Services/EngagementReport.php` notes
"Time spent is not reported: the platform does not record it". Removing the
row is also acceptable if the team prefers.

## MR-8 — Data dictionary: Quiz_Questions table (Table 14), `points`

**Decided (October 17, 2026):** document the current behaviour; no code
change. This closes audit item P2-15.

**Current text** (Description column)

> Points assigned to the question

**Replacement text**

> Points entered for the question; stored for reference. Each correct answer
> counts as one point when the quiz score is computed.

**Why.** Teachers enter points per question and they are saved, but the quiz
score counts one point per correct answer (`QuizController`, `$score++`), and
the percentage is correct answers ÷ number of questions.

## MR-9 — Data dictionary: fields missing from the manuscript

Add these rows (Field Name · Constraints · Data Type · Allow Nulls ·
Description). Types follow the manuscript's style (`int`, `varchar`, `text`,
`enum`, `tinyint`, `timestamp`).

**Users table (Table 5)**

| Field Name | Constraints | Data Type | Allow Nulls | Description |
|---|---|---|---|---|
| department_id | FK | int | Yes | Department of the user (used for Department Head scope) |
| is_active | Not Null | tinyint | No | Whether the account is active (deactivated accounts cannot log in) |
| profile_photo_path | Null | varchar | Yes | Profile photo file path |

The table also has the framework fields `remember_token`, `two_factor_secret`,
`two_factor_recovery_codes` and `two_factor_confirmed_at`, plus `created_at`
and `updated_at`. Add them if the manuscript lists every column; the
`created_at`/`updated_at` rows are used for every other table.

**Course_Templates table (Table 8)**: the manuscript lists only `id`,
`created_at` and `updated_at`.

| Field Name | Constraints | Data Type | Allow Nulls | Description |
|---|---|---|---|---|
| name | Not Null | varchar | No | Template name |
| description | Null | text | Yes | What the template is for |
| structure | Null | text | Yes | Suggested sections and lessons, stored as JSON |
| is_active | Not Null | tinyint | No | Whether teachers can choose the template |

**Courses table (Table 9)**

| Field Name | Constraints | Data Type | Allow Nulls | Description |
|---|---|---|---|---|
| review_remarks | Null | text | Yes | Administrator's feedback when a course is returned for revision |

**Assignments table (Table 16)**

| Field Name | Constraints | Data Type | Allow Nulls | Description |
|---|---|---|---|---|
| submission_type | Not Null | varchar | No | Essay or Code |
| answer_format | Not Null | varchar | No | How students answer: typed text, file upload, or either |
| programming_language | Null | varchar | Yes | For code assignments: HTML/CSS/JavaScript, Java, C, PHP, or Python |
| evaluation_criteria | Null | text | Yes | Criteria the AI evaluator and the teacher grade against |
| ai_evaluation_enabled | Not Null | tinyint | No | Whether AI output evaluation is turned on for this assignment |

**Submissions table (Table 17)**

| Field Name | Constraints | Data Type | Allow Nulls | Description |
|---|---|---|---|---|
| ai_evaluation_status | Not Null | varchar | No | Not requested, Processing, Completed, or Failed |
| ai_score | Null | int | Yes | AI-suggested score (hidden from the student; the teacher approves or overrides it) |
| ai_feedback | Null | text | Yes | AI-suggested feedback (hidden from the student) |
| ai_strengths | Null | text | Yes | AI-identified strengths (hidden from the student) |
| ai_improvements | Null | text | Yes | AI-suggested improvements (hidden from the student) |
| ai_evaluated_at | Null | timestamp | Yes | Date and time of the AI evaluation |

**Transactions table**

| Field Name | Constraints | Data Type | Allow Nulls | Description |
|---|---|---|---|---|
| course_invitation_id | FK | int | Yes | Invitation the payment came from, if the student was invited |
| checkout_url | Null | text | Yes | PayMongo checkout page address |
| paymongo_method | Null | varchar | Yes | Method the student used in PayMongo (e.g. GCash, Maya, Card) |

**Course_Categories table**: add the `created_at` and `updated_at` rows used
for every other table.

## MR-10 — Data dictionary: "Allow Nulls" that are wrong

These fields are listed with Allow Nulls **No**, but the system allows them
to be empty. Change each to **Yes**:

| Table | Field | Why it can be empty |
|---|---|---|
| Courses | estimated_hours | Optional when creating a course |
| Lessons | duration_minutes | Optional per lesson |
| Quizzes | lesson_id | A quiz can belong to the whole course rather than one lesson |
| Quizzes | time_limit_minutes | Empty means no time limit |
| Quiz_Questions | option_c, option_d | Only options A and B are required; C and D are optional |
| Assignments | lesson_id | An assignment can belong to the whole course |
| Assignments | due_date | Optional |
| Transactions | payment_method | Not known until the student chooses how to pay |
| Transactions | approved_by | Empty until the Super Admin reviews a manual payment; PayMongo payments are confirmed automatically |
| Learning_Paths | student_id, name, difficulty_level | Optional in the database |
| Learning_Path_Courses | learning_path_id, course_id | Optional in the database |

For Learning_Paths and Learning_Path_Courses, the alternative is to make the
columns required in the database instead; ask before choosing that, because
existing rows would need checking first.

## MR-11 — Data dictionary: tables missing from the manuscript

Add a data dictionary table (and a short "illustrates the data dictionary
of…" paragraph) for each, and add them to Figure 39 (Database Design).

**Departments**: departments for the Department Head scope.

| Field Name | Constraints | Data Type | Allow Nulls | Description |
|---|---|---|---|---|
| id | PK | int | No | Department identification |
| name | Not Null | varchar | No | Department name |
| description | Null | text | Yes | Department description |
| created_at | Null | timestamp | Yes | Record creation date and time |
| updated_at | Null | timestamp | Yes | Record last updated date and time |

**Course_Invitations**: invitation codes teachers send to students.

| Field Name | Constraints | Data Type | Allow Nulls | Description |
|---|---|---|---|---|
| id | PK | int | No | Invitation identification |
| course_id | FK | int | No | Course the invitation is for |
| created_by | FK | int | No | Teacher who created the invitation |
| student_id | FK | int | Yes | Invited student, if the invitation is for one student |
| code | Not Null | varchar | No | Invitation code |
| expires_at | Null | timestamp | Yes | When the invitation stops working |
| accepted_at | Null | timestamp | Yes | When the invitation was accepted |
| accepted_by | FK | int | Yes | Student who accepted it |
| created_at | Null | timestamp | Yes | Record creation date and time |
| updated_at | Null | timestamp | Yes | Record last updated date and time |

**Student_Removal_Requests**: a teacher's request to remove a student, decided by the Department Head.

| Field Name | Constraints | Data Type | Allow Nulls | Description |
|---|---|---|---|---|
| id | PK | int | No | Removal request identification |
| instructor_id | FK | int | No | Teacher who made the request |
| student_id | FK | int | No | Student to be removed |
| course_id | FK | int | No | Course the student would be removed from |
| reason | Not Null | text | No | Teacher's reason |
| status | Not Null | varchar | No | Pending, Approved, or Rejected |
| reviewed_by | FK | int | Yes | Department Head who decided |
| review_note | Null | text | Yes | Department Head's note |
| reviewed_at | Null | timestamp | Yes | When it was decided |
| created_at | Null | timestamp | Yes | Record creation date and time |
| updated_at | Null | timestamp | Yes | Record last updated date and time |

An approved request sets the enrollment to Cancelled; the student account and
records are kept.

**Certificate_Settings**: the certificate template the Administrator edits (Figure 30).

| Field Name | Constraints | Data Type | Allow Nulls | Description |
|---|---|---|---|---|
| id | PK | int | No | Certificate settings identification |
| title | Not Null | varchar | No | Certificate title |
| body_text | Not Null | text | No | Certificate wording |
| signatory_name | Not Null | varchar | No | Name of the signatory |
| signatory_position | Not Null | varchar | No | Position of the signatory |
| updated_by | FK | int | Yes | Administrator who last changed the template |
| created_at | Null | timestamp | Yes | Record creation date and time |
| updated_at | Null | timestamp | Yes | Record last updated date and time |

Laravel's own tables (sessions, cache, jobs, failed_jobs, migrations,
password_reset_tokens, notifications, passkeys) are normally left out of a
data dictionary. If the team wants Functional Requirement p (notifications)
backed by a table, `notifications` is the one to add.

## MR-12 — Data dictionary: Payments table

**Decided (October 17, 2026):** keep the table, marked reserved for future
use; no schema change.

The Payments table exists in the database with only `id`, `created_at` and
`updated_at`, which is also all the manuscript lists. No part of the system
reads or writes it; payments are stored in the Transactions table.

**Current text** (the paragraph after the Payments table)

> illustrates the data dictionary of the Payments table, which stores the
> payment records maintained by the PATHWISE platform. It serves as a
> reference for managing payment-related information within the system.

**Replacement text**

> illustrates the data dictionary of the Payments table, which is reserved
> for future use. In this version, all payment records, including PayMongo
> payments and uploaded receipts, are stored in the Transactions table.

## MR-13 — Functional Decomposition: Administrator (Department Head) scope — Optional

**Current text** (from the paragraph after the functional decomposition)

> The Administrator role, representing the Department Head, oversees course
> categories and approvals, manages certificates and AI recommendations, and
> views student progress reports.

**Replacement text**

> The Administrator role, representing the Department Head, oversees course
> categories and approvals, manages certificates and AI recommendations,
> views student progress reports, and decides teachers' requests to remove a
> student from a course. Course approvals cover all departments, while removal
> requests are limited to the teachers in the Department Head's own
> department.

**Why.** Audit item P2-25 kept course approval campus-wide;
`StudentRemovalRequestController::departmentHeadIndex` only shows requests
from the Department Head's department.

## MR-14 — AI quiz generation and the Draft course

The DOCX does not use the word "drafts" in Functional Requirement r; it is
in the Figure 19 paragraph and the Quiz Generation definition. All three are
revised so they say the same thing.

**Figure 19 paragraph (AI Processing Layer) — current text** (last sentence)

> Gemini generates them from that context and returns them to PATHWISE as
> drafts, which the teacher reviews, edits, or removes before students see
> any of them.

**Replacement text**

> Gemini generates them from that context, and PATHWISE adds them to the
> quiz while the course is still a Draft (or has been returned for
> revision). The teacher reviews, edits, or removes them, and the
> administrator can review them when the course is submitted for approval,
> so students see them only after the course is published.

**Definition of Terms, Quiz Generation — current text**

> Quiz Generation - Quiz generation generally refers to producing assessment
> questions from learning content. In PATHWISE, it refers to the AI-assisted
> creation of draft quiz questions that teachers review before publishing

**Replacement text**

> Quiz Generation - Quiz generation generally refers to producing assessment
> questions from learning content. In PATHWISE, it refers to the AI-assisted
> creation of quiz questions while a course is still a Draft; the teacher
> reviews and edits them, and the administrator can review them during
> course approval, before the course is published.

**Functional Requirement r — current text**

> r. AI Quiz Generation Process. Teachers can request AI-generated quiz
> questions and review them before publishing.

**Replacement text**

> r. AI Quiz Generation Process. Teachers can request AI-generated quiz
> questions while a course is a Draft; the questions are added to the quiz
> and reviewed by the teacher, and by the administrator during course
> approval, before the course is published.

**Why.** `QuizController::teacherGenerateQuestions` saves the generated
questions straight into the quiz (`QuizQuestion::create`); there is no
separate draft state for a question. Quizzes can only be changed while the
course is Draft or returned for revision (`ensureTeacherCanModifyCourse`
refuses Pending and Published courses), so nothing generated reaches
students before approval. The approval screen does not list quiz questions;
the administrator reviews them on the Quizzes and Quiz Questions pages.

## MR-15 — Super Admin Dashboard: Logic, step 3

**Current text**

> 3. The Super Admin will click Manage Users to add, edit or remove user
> accounts across all roles.

**Replacement text**

> 3. The Super Admin will click Manage Users to add, edit, activate or
> deactivate user accounts across all roles; requests for permanent deletion
> are handled by the EDP office.

Add as a note under the Logic list (or under Functional Requirement n):

> Accounts are never deleted. A deactivated account cannot log in, but its
> enrollments, payments, submissions, grades and certificates are kept.

**Why.** `App\Models\User` refuses deletion; Settings offers only "Deactivate
account"; `EnsureUserIsActive` logs out deactivated users. See
`DEPLOYMENT_NOTES.md` (never delete user rows) and `FUTURE_ITEMS.md` (EDP
anonymization, not built).

## MR-16 — Scope and Limitations: upload and AI evaluation limits

The DOCX states no file-size or file-type limits. Add this paragraph after
the paragraph that begins "The output evaluation feature supports essay and
code submissions."

**New text**

> Uploads are limited in size: assignment submission files to 25 MB, lesson
> files to 20 MB (PDF, Word, PowerPoint, Excel, or text), and course
> thumbnails and payment receipts to 5 MB (images only). For AI output
> evaluation, the system reads typed answers and plain-text or source-code
> files (.txt, .html, .htm, .css, .js, .java, .c, .h, .php, and .py), and sends at
> most 256 KB of file text to the Gemini API. PDF and Word submissions, and
> files over that limit, are not read by the AI and are graded manually by
> the teacher.

**Why.** `Assignment::UPLOAD_MAX_KB = 25600` and `AI_TEXT_MAX_KB = 256`;
`LessonController` (`max:20480`, `mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt`);
`CourseController` thumbnails and `TransactionController` receipts
(`max:5120`, images); `SubmissionController::readSubmissionFileText` reads
only `.txt` and the code-language extensions and refuses larger files with a
message to grade manually.

## MR-17 — Code languages: add Python

**Decided (October 17, 2026):** add Python to the manuscript's supported
languages; no code change.

The system offers HTML/CSS/JavaScript, Java, C, PHP and **Python**
(`Assignment::PROGRAMMING_LANGUAGES`), and the teacher's assignment form
shows all five. `.py` files are read for AI evaluation like the others
(already included in MR-16's file list).

**Scope and Limitations — current text**

> Code submissions are limited to HTML/CSS/JavaScript, Java, C, and PHP, and
> are submitted as pasted code or a single file.

**Replacement text**

> Code submissions are limited to HTML/CSS/JavaScript, Java, C, PHP, and
> Python, and are submitted as pasted code or a single file.

**Figure 19 paragraph (Output Evaluation module) — current text**

> For code written in HTML/CSS/JavaScript, Java, C, or PHP, the evaluator
> reads the source code and comments on correctness, basic code quality,
> possible efficiency concerns, and improvements.

**Replacement text**

> For code written in HTML/CSS/JavaScript, Java, C, PHP, or Python, the
> evaluator reads the source code and comments on correctness, basic code
> quality, possible efficiency concerns, and improvements.

The comment in `Assignment::PROGRAMMING_LANGUAGES` and the header of
`tests/Feature/Pathwise/CodeAssignmentLanguageTest.php` still describe
Python as kept only for older assignments; update that wording the next
time those files change.

## MR-18 — Certificates: current template and public verification

**Functional Requirement h — current text**

> h. Certificate Generation Process. The system generates a downloadable
> certificate upon course completion.

**Replacement text**

> h. Certificate Generation Process. The system generates a downloadable
> certificate upon completion of a course that offers one, using the
> certificate template set by the Administrator, and provides a public page
> where anyone can verify a certificate by its number.

**View and Download Certificate use case — Post-Condition, current text**

> Post-Condition: The certificate is generated and made available for the
> student to download.

**Replacement text**

> Post-Condition: The certificate is generated and made available for the
> student to download. Each download uses the Administrator's current
> certificate template, while the certificate number, issue date, student
> and course stay as issued. The certificate shows a "Verify at" address,
> where anyone can enter the certificate number to see whether it is valid
> or revoked.

**Why.** `CertificateController::download` loads `CertificateSetting::current()`
("the certificate's own number, date, student and course are never
changed"). The public, rate-limited page is `/verify-certificate`
(`CertificateVerificationController`); it shows only the status, student
name, course and issue date. Certificates are issued only for courses with
"certificate available" turned on.

## MR-19 — Enroll in Course use case: marketplace behaviour

**Current text** (Steps, System column)

> 1.1 Display the course details and enrollment option. 2.1 Get the payment
> details and process the transaction. 2.2 Record the enrollment and grant
> access to the course content.

**Replacement text**

> 1.1 Display the published courses the student does not already have, with
> the course details and enrollment option. 2.1 For a free course, enroll the
> student immediately; for a paid course, process the payment through
> PayMongo or record the uploaded receipt for Super Admin verification. A
> payment that was started but not finished can be continued. 2.2 Record the
> enrollment and grant access once the payment is confirmed. A cancelled
> enrollment shows a message instead of an enroll button; only an
> administrator can restore it.

Optionally add to the student dashboard Logic, after "The student will click
My Courses to open an enrolled course, view its lessons, and take quizzes.":

> My Courses groups the student's courses into In Progress, Completed (with
> the completion date and certificate) and, when there are any, Cancelled
> enrollments, which can no longer be opened.

**Why.** `CourseController` (marketplace) leaves out courses the student
already has active or completed access to, lists pending payments to
continue, and shows cancelled enrollments with "Your enrollment was
cancelled. Contact the course administrator." My Courses: commit `147bd72`.
The View and Download Certificate steps ("Go to the My Courses page and
select a completed course. Click View Certificate.") already match.

## MR-20 — Payment Gateway definition and the payment method shown

**Definition of Terms, Payment Gateway — current text**

> Payment Gateway - A payment gateway generally refers to a technology
> service that authorizes  and processes online financial transactions
> securely. In PATHWISE, this refers to the module that processes
> enrollment  fees and distributes revenue to instructors.

**Replacement text** (also fixes two double spaces)

> Payment Gateway - A payment gateway generally refers to a technology
> service that authorizes and processes online financial transactions
> securely. In PATHWISE, this refers to the PayMongo integration that
> processes course enrollment fees.

**Why.** No part of the system calculates or pays out instructor earnings.
If the team wants to keep "distributes revenue to instructors", it has to be
described as done outside PATHWISE.

**Transactions table, `payment_method` — current Description**

> Payment method used

**Replacement Description**

> How the student paid: PayMongo, or the method entered with an uploaded
> receipt (GCash, Maya, Card, or Bank Transfer). For PayMongo payments,
> `paymongo_method` records the method used, and receipts show it as, for
> example, "Maya via PayMongo".

**Transactions table, `approved_by` — current Description**

> Super Admin (EDP) who approved the transaction

**Replacement Description** (Allow Nulls also becomes Yes, see MR-10)

> Super Admin (EDP) who approved or rejected an uploaded receipt; empty for
> PayMongo payments, which are confirmed automatically

**Why.** `PaymentMethods::forTransaction` builds the "Maya via PayMongo"
label shown to students and the Super Admin; the Super Admin's approve and
reject actions both set `approved_by`; the PayMongo confirmation does not.
The DOCX describes no receipt or transaction screen, so only the data
dictionary needs this change.

---

## Already accurate — no change needed

Checked against the system; leave as written, apart from the items noted.

- **Scope and Limitations**: Gemini API, no own model training, output
  evaluation limited to essays and code (add Python, see MR-17),
  code never executed, teacher approval required.
- **Figure 19, AI System Architecture** (Data Processing, AI Processing,
  Analytics and Decision, Presentation layers): matches the system, including
  "AI evaluations work as suggestions only" (only the quiz generation and
  code language sentences change, see MR-14 and MR-17) and the Teacher Dashboard showing
  "AI evaluations awaiting approval".
- **Definition of Terms, "Output Evaluation"** and **Functional Requirement
  s**: students see AI results only after the teacher approves or overrides
  them (covered by `tests/Feature/Pathwise/StudentAiVisibilityTest.php`).
- **Functional Requirement n, User Management**: the Super Admin verifies
  (email), activates, deactivates and edits accounts and assigns roles.
- **Functional Requirement o, Report Generation**: system reports include user
  engagement and course performance, with PDF export and charts.
- **Figure 30, Manage Certificates**: the Administrator edits the certificate
  template (title, wording, signatory) and can preview it.
- **Quizzes table, `time_limit_minutes`**: the time limit is enforced (only
  the Allow Nulls value changes, see MR-10).
- **Enrollments, Lesson_Progress, Quiz_Results, Certificates, Submissions
  status values**: the listed values match the database.
- **Technology Stack**: lists the PayMongo API for payment processing, and
  the Gemini API for recommendations, quiz generation and assignment
  evaluation.
- **View and Download Certificate steps**: "Go to the My Courses page and
  select a completed course. Click View Certificate." matches My Courses.

---

## The September 27 PDF is outdated

`docs/audit/pathwise_revised (1).pdf` is the September 27, 2026 version
(the same file as `Downloads/pathwise_revised.pdf`; identical SHA-256). Use
the October 6 DOCX. The main differences, found by comparing the text of
both (not a line-by-line diff):

| Topic | September 27 PDF | October 6 DOCX |
|---|---|---|
| Figure 19, AI System Architecture | An "AI Layer (Machine Learning Models)": content-based and collaborative filtering, a hybrid learning-path engine, Random Forest regression and classification, and Isolation Forest at-risk detection; data cleaning and feature engineering into a "Learning Analytics Database" | Rule-based logic plus the Google Gemini API; "The system does not train its own models"; data kept in the central MySQL database; AI evaluations are suggestions the teacher approves |
| Scope and Limitations | Gemini powers "the AI recommendation and quiz generation features" only | Adds AI output evaluation: essays and HTML/CSS/JavaScript, Java, C and PHP code, never executed, teacher approval required |
| Functional Requirements | a to p (16 items) | a to s: adds q Assignment Process, r AI Quiz Generation Process, s AI Output Evaluation Process |
| Definition of Terms | No "Output Evaluation" or "Quiz Generation" entries | Both added; "AI-Powered Recommendation System" now names the Gemini API |
| Technology Stack | "Machine Learning / API Integration: Google Gemini API … for AI-powered recommendations" | "AI / API Integration": Gemini for recommendations, quiz generation, and assignment evaluation |
| Dashboards (Fig. 19 Presentation Layer) | Learning analytics reports, recommendation widgets, at-risk alerts | Student dashboard shows approved assignment feedback; Teacher dashboard shows AI evaluations awaiting approval |

Unchanged between the two, so still to revise: the "add, edit or remove user
accounts" step (MR-15), "GCash and Maya" (MR-4, MR-5), and the machine
learning wording in the Definition of Terms and the literature review
(MR-1 to MR-3). Both versions have the same objectives, the same 45 figure
captions, and the same data dictionary tables.
