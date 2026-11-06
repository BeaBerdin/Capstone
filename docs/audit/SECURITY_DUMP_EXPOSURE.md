# PATHWISE — Old Database Export in Git History (SEC-1)

Prepared November 6, 2026 (Final Validation Audit, batch B1). Sanitized for
the repository: this document contains **no passwords, password hashes,
tokens, email addresses, account ids, commit hashes or file names** of the
exposed data. The project lead holds those details privately. Git history has
**not** been rewritten; that needs separate approval.

## 1. What happened

| Item | Detail |
|---|---|
| What | A MySQL export of a local development database (September 2026) was committed to the repository and later removed from tracking. It remains in the Git history |
| Now | `.gitignore` ignores `*.sql`; no export is tracked |
| Where the history is | The team's GitHub repository (all team branches and `main`) and every member's local clone |
| Repository visibility | **Not confirmed.** Treat it as public until a member with admin access checks the repository settings |
| Private details | Commit, file name and affected accounts: held by the project lead, not written in the repository |

## 2. What the export contains

| Data | Sensitive? |
|---|---|
| User accounts (5): names, email addresses, bcrypt password hashes, two-factor columns | **Yes** |
| Roles and role assignments | No |
| Old session records from one local computer (already expired; never valid elsewhere) | Low |
| Test-mode payment records and payment-proof file names (no card or e-wallet data, no checkout links) | Low |
| Demo learning data (enrollments, quiz results, certificates, lessons, learning paths) | Low |
| Password-reset tokens, API tokens | None present |

Checked: the export contains **no** API keys (Gemini, PayMongo), **no**
webhook secret and **no** `APP_KEY`. Those live only in `.env`, which was
never committed.

## 3. Affected accounts (by role only)

| Accounts | Risk |
|---|---|
| 4 local **demo** accounts (Super Admin, Administrator, Teacher, Student) | Demo passwords must be treated as known |
| 1 **real person's** Student account with a personal email address | **High**: their name, email and password hash are exposed |

## 4. Remediation procedure (coordinate with the group)

Do these steps **before** deployment and record them in section 6.

1. **Confirm repository visibility** (one member with admin access).
2. **The real person's account** (the project lead knows which one): tell the
   person privately that an old test database was published. Ask them to:
   - change their PathWise password on every PathWise copy they use (and,
     later, the live site) through *Settings → Security* or *Forgot your
     password?* on the login page;
   - change the password of **any other service where they used the same
     password**, starting with that email account;
   - turn on two-step verification for that email account.
3. **Demo accounts:**
   - Never create them on the live server. `DatabaseSeeder` already skips
     demo accounts outside `local`/`testing`, and the live Super Admin
     password comes from `SEED_SUPERADMIN_PASSWORD` or is generated (see
     `docs/DEPLOYMENT_HOSTINGER.md`).
   - On each member's computer that is shared or used for the public defense
     demo, change the demo account passwords.
   - Never reuse a demo password for any real account.
4. **Members' own passwords:** if any member used the same password for a
   demo account and for a personal account, change the personal one.
5. **Live site (after deployment):** the Super Admin checks *User Management*
   and confirms that no local demo account (the demo domain used by the local
   seeders) exists.

## 5. Removing the export from history (separate decision)

Password rotation (section 4) protects the accounts whether or not the
history is rewritten. Removing the file from history is optional and
**requires separate, explicit approval** because it:

- rewrites every branch that contains the export (all team branches);
- needs a force-push, and every member must re-clone or reset their copy;
- does not remove copies already downloaded or cached (forks, clones, GitHub
  caches); GitHub Support can purge cached views on request.

If approved later, one member runs `git filter-repo` for that single path
(the project lead has it), followed by a coordinated force-push. **Do not run
it without that approval.**

## 5a. Message to send to the group (copy as is)

> **PathWise security action needed before deployment (do not reply with passwords).**
>
> An old database export from September is still in our GitHub history. It
> contains our 4 demo accounts and one real student account with a personal
> email address, with their password hashes. It contains no API keys. We are
> **not** rewriting the Git history for now. Please do these steps and tick
> them in `docs/audit/SECURITY_DUMP_EXPOSURE.md`, section 6:
>
> 1. **[Whoever has admin access to our GitHub repository]** Check whether it
>    is Public or Private and post only "public" or "private" in our group chat.
> 2. **[Project lead, privately]** Tell the owner of the affected real account
>    to change their PathWise password on every copy they use, change any other
>    account that used the same password (starting with that email account),
>    and turn on 2-step verification.
> 3. **[Everyone]** If you ever used a demo account password for anything
>    real, change that real password now.
> 4. **[Everyone with a local copy used for demos or on a shared computer]**
>    Change the passwords of the demo accounts there (Settings → Security).
> 5. **[Deployer]** On the live server, never create demo accounts; set
>    `SEED_SUPERADMIN_PASSWORD` in quotes or let it be generated; after
>    deployment, confirm in User Management that no demo account exists.
> 6. **Never** paste `.env` values, passwords or database exports into Git,
>    chats or documents.
>
> Deployment stays blocked until steps 1 to 4 are ticked.

## 6. Tracking (fill in; no secrets, no names of affected people)

| Step | Owner | Date done | Notes |
|---|---|---|---|
| Repository visibility confirmed | | | public / private |
| Affected person informed | | | |
| Affected person's PathWise password changed (all copies) | | | |
| Affected person's reused passwords changed | | | |
| Demo passwords changed on shared/demo computers | | | |
| Live site checked: no demo accounts | | | after deployment |
| History rewrite decision | | | approved / not needed |
