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
