# PathWise — System-to-Manuscript Alignment Status (after Round 2)

"Fixed (untested)" means the code was changed but has not been executed yet.
Run the tests (see `HOW_TO_INSTALL_THE_FIXES.md`, Step 8) to confirm.

| # | Requirement (manuscript reference) | Before | Now | Remaining action |
|---|---|---|---|---|
| P0-1 | Paid enrollment only after payment (Fig. 23, FR m) | Contradictory | Fixed (untested) | — |
| P0-2 | Fresh database installation (Database Design) | Broken | Fixed (untested) | — |
| P0-3 | Linux hosting / Hostinger (Network Design) | Broken on Linux | Fixed (untested) | Test on the real host |
| P0-4 | Sensitive data protection (NFR) | Contradictory | **Needs your action** | Rotate keys; remove the dump (Install guide, Step 0) |
| P1-5 | API key protection | Partial | Fixed (untested) | — |
| P1-6 | PayMongo webhook (FR m) | Partial | Fixed (untested) | Create the webhook and set `PAYMONGO_WEBHOOK_SECRET` |
| P1-7 | Teacher edits/revises a course (Fig. 25, 28) | Missing | Fixed (untested) | — |
| P1-8 | Course locked during review (Fig. 28) | Partial | Fixed (untested) | — |
| P1-9 | Return for revision with feedback (Fig. 29) | Partial | Fixed (untested) | — |
| P1-10 | Super Admin creates, activates, deactivates accounts (FR n, Fig. 31) | Missing | Fixed (untested) | "Verify teachers" interpreted as email verification + activation (manuscript wording) |
| P1-11 | Course templates (Rationale, Scope, Tables 8–9) | Missing | **Built** (untested) | Built-in templates only; no admin editing screen |
| P1-12 | Teacher student-removal requests page | Broken | Fixed (untested) | — |
| P1-13 | AI System Architecture (ML models claimed) | Contradictory | Open | Manuscript revision (Round 3); no ML will be fabricated |
| P2-14 | Quiz time limit (Table 13) | Missing | Fixed (untested) | — |
| P2-15 | Points per question (Table 14) | Partial | Open | **Team decision needed:** weight scores by points, or document "1 point per item" |
| P2-16 | Admin assignment/submission detail pages | Broken | Fixed (untested) | — |
| P2-17 | Email notifications (Network Design) | Partial | Open | Manuscript revision, or add queued mail |
| P2-18 | Certificate templates (Fig. 30) | Missing | Open | Manuscript revision |
| P2-19 | Private storage for payment proofs / submissions | Partial | Open | Needs approval (existing files must move) |
| P2-20 | Cancelled enrollments can still access the course | Partial | Fixed (untested) | — |
| P2-21 | Scheduled database backups (NFR) | Partial | Fixed (untested) | Scheduler/cron must run on the server |
| P2-22 | Data dictionary out of date (Tables 4–24) | Contradictory | Open | Manuscript revision (Round 3) |
| P2-23 | Payment methods (GCash/Maya/card) | Partial | Open | Manuscript revision or configuration |
| P2-24 | AI output evaluation (Objective 2.4) | Unverified | Open | Document in the manuscript |
| P2-25 | Department Head scope | Ambiguous | Kept campus-wide | Manuscript wording |
| — | Admin quiz form lost its lesson link | (found during fixes) | Fixed (untested) | — |
| — | Test suite could not build its database | (found during fixes) | Fixed (untested) | — |
| — | Tests could call the real Gemini API with your key | (found in Round 2) | Fixed (untested) | — |
| — | SQL quoting not portable (ANSI_QUOTES/SQLite) | (found in Round 2) | Fixed (untested) | — |
| UI | Pages show success/error messages inconsistently | (found during fixes) | Open | Round 2: shared components |

## Next rounds

- **Round 2 (done, untested):** quiz time limit, cancelled-enrollment check, scheduled backups.
- **Next:** shared UI components (flash messages, form fields, page header, buttons,
  status badges) applied to every page.
- **Round 3:** manuscript revision document with exact replacement wording: AI
  architecture, data dictionary, templates, user management, notifications, certificates,
  payment methods.
