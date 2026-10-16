<?php

use Illuminate\Console\Scheduling\Schedule;

/*
| Manuscript Non-Functional Requirement: data backup and recovery.
| Checks that the backups are registered with Laravel's scheduler.
| (It does not run a real backup.)
*/

test('database, full, and cleanup backups are scheduled', function () {
    $commands = collect(app(Schedule::class)->events())
        ->map(fn ($event) => (string) $event->command);

    expect($commands->contains(fn ($command) => str_contains($command, 'backup:run --only-db')))->toBeTrue()
        ->and($commands->contains(fn ($command) => str_contains($command, 'backup:run') && ! str_contains($command, '--only-db')))->toBeTrue()
        ->and($commands->contains(fn ($command) => str_contains($command, 'backup:clean')))->toBeTrue();
});

test('backup notification emails do not go to the placeholder address', function () {
    expect(config('backup.notifications.mail.to'))->not->toBe('your@example.com');
});
