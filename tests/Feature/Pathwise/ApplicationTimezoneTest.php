<?php

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Lesson;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Carbon;

/*
| System finalization F-2 (approved November 6, 2026): the application
| timezone is configurable (APP_TIMEZONE, Asia/Manila in production).
| Certificate issue dates and numbers, and the backup schedule, follow the
| configured Laravel timezone instead of UTC.
*/

/**
 * Run the callback as if the app had booted with the given timezone, the
 * same way Laravel applies config('app.timezone') at boot.
 */
function inAppTimezone(string $timezone, callable $callback): mixed
{
    $previousConfig = config('app.timezone');
    $previousPhp = date_default_timezone_get();

    config(['app.timezone' => $timezone]);
    date_default_timezone_set($timezone);

    try {
        return $callback();
    } finally {
        Carbon::setTestNow();
        config(['app.timezone' => $previousConfig]);
        date_default_timezone_set($previousPhp);
    }
}

function completeOnlyLessonAt(string $utcMoment): Certificate
{
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 0]);

    $lesson = Lesson::create([
        'course_id' => $course->id,
        'title' => 'Only lesson',
        'content' => 'Content',
        'lesson_type' => 'text',
        'lesson_order' => 1,
        'is_preview' => false,
        'is_published' => true,
    ]);

    Carbon::setTestNow(Carbon::parse($utcMoment, 'UTC'));

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    test()->actingAs($student)
        ->post(route('student.lesson.complete', $lesson))
        ->assertSessionHas('success');

    return Certificate::where('student_id', $student->id)
        ->where('course_id', $course->id)
        ->sole();
}

test('the application timezone comes from APP_TIMEZONE and defaults to UTC', function () {
    $file = base_path('config/app.php');

    $previous = getenv('APP_TIMEZONE');

    putenv('APP_TIMEZONE');
    unset($_ENV['APP_TIMEZONE'], $_SERVER['APP_TIMEZONE']);
    expect((require $file)['timezone'])->toBe('UTC');

    putenv('APP_TIMEZONE=Asia/Manila');
    $_ENV['APP_TIMEZONE'] = $_SERVER['APP_TIMEZONE'] = 'Asia/Manila';
    expect((require $file)['timezone'])->toBe('Asia/Manila');

    putenv($previous === false ? 'APP_TIMEZONE' : "APP_TIMEZONE={$previous}");
    unset($_ENV['APP_TIMEZONE'], $_SERVER['APP_TIMEZONE']);
});

test('a certificate earned at 00:30 Philippine time is dated that Philippine day', function () {
    // 2026-11-05 16:30 UTC is 2026-11-06 00:30 in Asia/Manila.
    $certificate = inAppTimezone('Asia/Manila', fn () => completeOnlyLessonAt('2026-11-05 16:30:00'));

    expect(substr((string) $certificate->issued_date, 0, 10))->toBe('2026-11-06');
});

test('a certificate earned at 23:59 Philippine time keeps that day', function () {
    // 2026-11-05 15:59 UTC is 2026-11-05 23:59 in Asia/Manila.
    $certificate = inAppTimezone('Asia/Manila', fn () => completeOnlyLessonAt('2026-11-05 15:59:00'));

    expect(substr((string) $certificate->issued_date, 0, 10))->toBe('2026-11-05');
});

test('a certificate earned just after New Year in the Philippines gets the new year in its date and number', function () {
    // 2026-12-31 16:30 UTC is 2027-01-01 00:30 in Asia/Manila.
    $certificate = inAppTimezone('Asia/Manila', fn () => completeOnlyLessonAt('2026-12-31 16:30:00'));

    expect(substr((string) $certificate->issued_date, 0, 10))->toBe('2027-01-01')
        ->and($certificate->certificate_number)->toStartWith('PATH-2027-');
});

test('with the default UTC timezone the same moment is still dated by UTC', function () {
    $certificate = inAppTimezone('UTC', fn () => completeOnlyLessonAt('2026-11-05 16:30:00'));

    expect(substr((string) $certificate->issued_date, 0, 10))->toBe('2026-11-05');
});

test('the scheduler uses the configured application timezone for the backups', function () {
    $kernel = app(ConsoleKernel::class);
    $scheduleTimezone = fn () => $this->scheduleTimezone();

    inAppTimezone('Asia/Manila', function () use ($kernel, $scheduleTimezone) {
        expect($scheduleTimezone->call($kernel))->toBe('Asia/Manila');

        // A schedule built in Asia/Manila runs the 01:00 database backup at
        // 01:00 Philippine time (17:00 UTC the day before).
        $schedule = new Schedule('Asia/Manila');
        $event = $schedule->command('backup:run --only-db')->dailyAt('01:00');

        Carbon::setTestNow(Carbon::parse('2026-11-06 12:00:00', 'Asia/Manila'));

        expect($event->nextRunDate()->setTimezone('Asia/Manila')->format('Y-m-d H:i'))->toBe('2026-11-07 01:00')
            ->and($event->nextRunDate()->setTimezone('UTC')->format('Y-m-d H:i'))->toBe('2026-11-06 17:00');
    });

    // The registered backup events follow config('app.timezone').
    $backup = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'backup:run --only-db'));

    expect($backup)->not->toBeNull()
        ->and((string) $backup->timezone)->toBe((string) config('app.timezone'));
});
