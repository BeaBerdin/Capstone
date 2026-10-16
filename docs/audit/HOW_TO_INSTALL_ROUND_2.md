# How to Install Round 2 (Beginner Guide)

This ZIP contains **all fixes from Round 1 and Round 2** (complete files). You can copy it
whether or not you already copied Round 1.

> These changes were written and checked by reading the code, but they have **not been
> run yet**. The tests in Step 6 are how we find out whether everything works.

## Step 1 — Backup (if you did not already do it today)

```
git add -A
git commit -m "Backup before Round 2"
```

Database: MySQL Workbench → *Server → Data Export* → `pathwise` schema →
*Export to Self-Contained File* → *Start Export*.

## Step 2 — Copy the files

1. Stop the app (`Ctrl + C` in the terminal running `php artisan serve` / `npm run dev`).
2. Double-click `pathwise-changed-files.zip`, press **Ctrl + A**, then **Ctrl + C**.
3. Open your `pathwise` folder (the one that contains `artisan`), click on empty space,
   press **Ctrl + V**.
4. Choose **"Replace the files in the destination"** (tick "Do this for all current
   items" if asked).

Check: `resources/views/Student/take-quiz.blade.php` should now contain the words
`Time remaining`.

## Step 3 — Update `.env`

Add these lines at the bottom of `.env` (skip a line if it is already there):

```
PAYMONGO_WEBHOOK_SECRET=
BACKUP_NOTIFICATION_EMAIL=your-real-email@example.com
```

Replace `your-real-email@example.com` with the email that should receive backup reports.

## Step 4 — Clear caches and update the database

```
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan migrate --pretend
```

- If `migrate --pretend` says **"Nothing to migrate"**, Round 1's migrations are already
  done (Round 2 adds none). Go to Step 5.
- Otherwise it lists the Round 1 migrations (`2026_10_16_000001` … `000005`). **Send me
  that output first**, then run `php artisan migrate`.

> Never run `migrate:fresh` or `migrate:refresh` — they delete all data.

If you have not loaded the course templates yet:

```
php artisan db:seed --class=CourseTemplateSeeder
```

## Step 5 — Rebuild the styles

```
npm run build
```

## Step 6 — Run the tests

```
php artisan test tests/Feature/Pathwise
php artisan test
```

If you see `could not find driver`, enable `extension=pdo_sqlite` and
`extension=sqlite3` in your `php.ini` (see the Round 1 guide, Step 8). Copy the
**complete output** and send it to me.

## Step 7 — Test in the browser

| As | Do this | Expected |
|---|---|---|
| Teacher | Set a quiz's time limit to **1 minute** | Saved |
| Student | Open that quiz | Countdown "01:00" stays visible while scrolling |
| Student | Refresh the page | Timer continues (does not restart at 01:00) |
| Student | Wait until 00:00 | The quiz submits by itself and shows the score |
| Student | Open a quiz without a time limit | No countdown; works as before |
| Administrator | *Enrollments* → set a student's enrollment to **Cancelled** | Saved |
| That student | Open the course or its quiz | "You must have an active enrollment…" (403 page) |
| That student | Click Enroll on the same free course | "Your enrollment in this course was cancelled…" |
| Administrator | Set the enrollment back to **Active** | Student can open the course again |

## Step 8 — Check the backups

```
php artisan schedule:list
```

You should see three `backup:` lines (1:00 daily, Sunday 2:00, 3:00 daily).

Make one backup right now to confirm it works:

```
php artisan backup:run --only-db
```

Success message: "Backup completed!". The file appears in
`storage/app/private/<your APP_NAME>/`.

If it fails with **"mysqldump: not found"** (or similar), tell me where MySQL is
installed on your computer, for example
`C:\Program Files\MySQL\MySQL Server 8.0\bin`. I'll give you the exact setting.

To make the schedule run on your computer while testing, keep this running in a
separate terminal:

```
php artisan schedule:work
```

On Hostinger, create a cron job (hPanel → *Advanced → Cron Jobs*) that runs **every
minute**:

```
php /home/USERNAME/domains/YOURDOMAIN/public_html/artisan schedule:run
```

Use your real path; Hostinger shows it in the File Manager.
