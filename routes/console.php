<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled backups (Manuscript: Non-Functional Requirement on data
| backup and recovery). Uses the already installed spatie/laravel-backup
| package and config/backup.php.
|--------------------------------------------------------------------------
|
| These only run when Laravel's scheduler is running:
|   - Your computer (testing):   php artisan schedule:work
|   - Hostinger (live server):   a cron job that runs every minute:
|       php /path/to/pathwise/artisan schedule:run
|
| Backups are saved privately in storage/app/private/<APP_NAME>/ .
| Run one manually any time with:  php artisan backup:run --only-db
*/

// Every day at 1:00 AM: back up the database.
Schedule::command('backup:run --only-db')
    ->dailyAt('01:00')
    ->withoutOverlapping();

// Every Sunday at 2:00 AM: full backup (database + project files and uploads).
Schedule::command('backup:run')
    ->weeklyOn(0, '02:00')
    ->withoutOverlapping();

// Every day at 3:00 AM: delete old backups according to config/backup.php.
Schedule::command('backup:clean')
    ->dailyAt('03:00')
    ->withoutOverlapping();
