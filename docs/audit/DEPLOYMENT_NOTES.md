# PATHWISE deployment notes

Notes for running PATHWISE on the live server (Hostinger). This file grows as
deployment items are confirmed; it does not claim that a deployment has been
done or tested.

## Never delete user rows directly in the database

- **Never delete rows from the `users` table in phpMyAdmin, MySQL Workbench or
  any other database tool.**
- To remove someone's access, always use **Super Admin → Users → Deactivate**.
- Reason: the database is set to delete a user's linked records together with
  the user (cascade). Deleting a student row erases their enrollments,
  payments (transactions), submissions, grades, quiz results and
  certificates. Deleting a teacher row also erases their courses and every
  student's enrollments, payments and certificates in those courses.
- The application itself refuses to delete users (`App\Models\User`), and
  Settings only offers "Deactivate account". The database tools bypass that
  protection, so this rule matters.
- Requests for permanent deletion of an account are handled by the EDP office.
  For now the EDP keeps the account deactivated; see `FUTURE_ITEMS.md` for the
  possible anonymization option.

## Moving existing files to private storage (A11)

Payment receipts, assignment submissions and lesson files are now kept in
`storage/app/private` and opened only through the `/files/...` links, which
check who may see them. Files uploaded before A11 are still in
`storage/app/public` until they are moved. Until then they still open
through the new links, but their old `/storage/...` addresses are public too.
Course thumbnails and profile photos stay public and are not moved.

Do this once on Hostinger, over SSH, from the PATHWISE folder, **after** the
A11 code is deployed:

1. **Back up the public files first.**

   ```
   tar -czf ~/pathwise-public-files-backup-$(date +%Y%m%d).tar.gz storage/app/public
   ```

   Check that the file was created (`ls -lh ~/pathwise-public-files-backup-*`)
   and keep it until step 4 has passed.

2. **Refresh the cached configuration** (A11 changes `config/filesystems.php`):

   ```
   php artisan config:clear
   php artisan config:cache
   ```

   Skip `config:cache` if the server does not normally cache configuration.

3. **Dry run** (changes nothing):

   ```
   php artisan files:make-private --dry-run
   ```

   The summary shows how many files would be moved. **conflicts** and
   **failed** must both be 0. If not, stop and check the files named above
   the summary.

4. **Real run:**

   ```
   php artisan files:make-private
   ```

   Each file is copied, checked (size and SHA-256), and only then deleted
   from `storage/app/public`. A conflict is never overwritten, and both
   copies are kept. Running the command again is safe.

5. **Check the result in the browser:**
   - Open an **old** link to a moved file, for example
     `https://<site>/storage/payment-proofs/<file>`. It must return **404**.
   - Open the same receipt from the student's Transactions page and the
     Super Admin's Transactions page. It must open.
   - Open a `/files/...` link while logged out. It must go to the login page.

If something is wrong, restore the files from the backup in step 1
(`tar -xzf ~/pathwise-public-files-backup-<date>.tar.gz`). The old paths
work again because the stored paths in the database never changed.

## Existing accounts and email verification

After pulling, run `php artisan users:verify-existing --dry-run`, then
without `--dry-run`, so existing accounts aren't blocked by email
verification.

- The command only sets `email_verified_at` for accounts where it is empty
  and that were created before the command started. Passwords, roles and
  all other columns are not changed.
- Accounts that are already verified keep their original date. Running it
  again is safe.
- Users who register afterwards must still verify their email.
- It is not a migration, so it never runs automatically on the live server.

## Testing a backup restore on your laptop

A backup only helps if it can be restored. Practise on your laptop with a
**separate test database**. Never restore into your real `pathwise`
database: the backup file starts each table with `DROP TABLE`, so it would
replace your data.

Use PowerShell in the PATHWISE folder.

1. **Make sure the MySQL tools can be found.**

   ```
   mysqldump --version
   ```

   If PowerShell says it is not recognized, add MySQL to this window's path
   (adjust the folder if your MySQL version is different), then try again:

   ```
   $env:Path += ";C:\Program Files\MySQL\MySQL Server 8.0\bin"
   ```

2. **Make a fresh database backup.**

   ```
   php artisan backup:run --only-db
   ```

   It should end with "Backup completed!".

3. **Find the backup file.** Backups are saved in
   `storage\app\private\<APP_NAME>\`, where `<APP_NAME>` is the `APP_NAME`
   value in `.env` (for example `storage\app\private\Laravel\`). The newest
   `.zip` file is the one you just made:

   ```
   Get-ChildItem storage\app\private\*\*.zip | Sort-Object LastWriteTime | Select-Object -Last 1
   ```

4. **Unzip it into a test folder.**

   ```
   Expand-Archive storage\app\private\Laravel\<file>.zip -DestinationPath $HOME\restore-test
   ```

   The database dump is `$HOME\restore-test\db-dumps\mysql-pathwise.sql`.
   If `BACKUP_ARCHIVE_PASSWORD` is set in `.env`, the zip is encrypted; open
   it with 7-Zip and that password instead.

5. **Create the separate test database.** It must have a different name
   from your real database:

   ```
   mysql -u root -p -e "CREATE DATABASE pathwise_restore_test"
   ```

6. **Restore the dump into the test database only.** Check that the
   command says `pathwise_restore_test`, not `pathwise`, before pressing
   Enter:

   ```
   cmd /c "mysql -u root -p pathwise_restore_test < %USERPROFILE%\restore-test\db-dumps\mysql-pathwise.sql"
   ```

7. **Check the restored data.** Compare a few counts between the test
   database and your real one (these commands only read):

   ```
   mysql -u root -p -e "SELECT COUNT(*) FROM pathwise_restore_test.users; SELECT COUNT(*) FROM pathwise_restore_test.courses; SELECT COUNT(*) FROM pathwise_restore_test.enrollments; SELECT COUNT(*) FROM pathwise_restore_test.transactions;"
   mysql -u root -p -e "SELECT COUNT(*) FROM pathwise.users; SELECT COUNT(*) FROM pathwise.courses; SELECT COUNT(*) FROM pathwise.enrollments; SELECT COUNT(*) FROM pathwise.transactions;"
   ```

   The numbers should match, unless records were added after the backup.
   You can also open `pathwise_restore_test` in MySQL Workbench and look
   at a few tables.

8. **Clean up.** Delete the test database and the unzipped files. Check
   the name twice: it must be `pathwise_restore_test`.

   ```
   mysql -u root -p -e "DROP DATABASE pathwise_restore_test"
   Remove-Item -Recurse $HOME\restore-test
   ```

The backup contains the database only (`--only-db`). Uploaded files such as
receipts and lesson files are in the weekly full backup (`backup:run`).

## Dropping the unused payments and course_reviews tables

The migration `2026_10_21_000001_drop_unused_payments_and_course_reviews_tables`
removes two empty placeholder tables that nothing in PATHWISE uses
(payments are stored in `transactions`). On the live server:

1. **Back up the database first:**

   ```
   php artisan backup:run --only-db
   ```

   Check that it ends with "Backup completed!".

2. **Run the migration:**

   ```
   php artisan migrate
   ```

   It should list only the new migration as run.

If it ever has to be undone, `php artisan migrate:rollback --step=1`
recreates both tables exactly as they were (empty, with `id` and
timestamps).

## Backups on Windows: mysqldump must be on the PATH

`php artisan backup:run` uses MySQL's `mysqldump` program. On Windows it is
often not on the PATH, and the backup then fails. Check it first:

```
mysqldump --version
```

If PowerShell says it is not recognized, add MySQL to the PATH for this
window (adjust the folder if your MySQL version is different), then run the
backup again:

```
$env:Path += ";C:\Program Files\MySQL\MySQL Server 8.0\bin"
```

This only lasts until the window is closed. To make it permanent, add the
same folder to the Path in Windows' Environment Variables settings.
