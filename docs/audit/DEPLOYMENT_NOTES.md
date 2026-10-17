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
