# PathWise

PathWise is an e-learning platform built with Laravel. Teachers create
courses with lessons, quizzes and assignments; administrators review and
publish them; students enroll (free or paid through PayMongo), learn, take
quizzes, submit assignments and earn certificates. The Google Gemini API
generates quiz questions, suggests assignment scores for the teacher to
review, and writes the explanation shown with each course recommendation
(the recommended course itself is chosen by fixed rules).

Step-by-step instructions per role are in [docs/USER_GUIDE.md](docs/USER_GUIDE.md).

## Requirements

- PHP 8.4 or newer (8.4.1 minimum), Composer
- Node.js and npm
- MySQL

## Setup

1. Install the PHP and JavaScript packages:

   ```
   composer install
   npm install
   ```

2. Create your settings file and the application key:

   ```
   copy .env.example .env
   php artisan key:generate
   ```

3. Fill in `.env`:
   - **Database:** `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
   - **Gemini:** `GEMINI_API_KEY`
   - **PayMongo:** `PAYMONGO_SECRET_KEY`, `PAYMONGO_PUBLIC_KEY`,
     `PAYMONGO_WEBHOOK_SECRET`
   - **Mail:** `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`,
     `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`

   After changing `.env`, run `php artisan config:clear`.

4. Create the database tables and the course templates:

   ```
   php artisan migrate
   php artisan db:seed --class=CourseTemplateSeeder
   ```

5. Build the front-end assets:

   ```
   npm run build
   ```

6. Mark accounts that already exist as email-verified (only new
   registrations must verify their email):

   ```
   php artisan users:verify-existing
   ```

7. Run the tests:

   ```
   php artisan test --compact
   ```

   The tests use a temporary in-memory SQLite database and never touch your
   MySQL data.

## More documentation

- [docs/USER_GUIDE.md](docs/USER_GUIDE.md): how to use PathWise, per role
- [docs/audit/DEPLOYMENT_NOTES.md](docs/audit/DEPLOYMENT_NOTES.md): live
  server notes, moving files to private storage, testing a backup restore
