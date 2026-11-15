<?php

namespace App\Providers;

use App\Http\Middleware\EnsureStudentApplicationApproved;
use Carbon\CarbonImmutable;
use Livewire\Livewire;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();

        // Livewire requests made from a protected page re-check that the
        // student's application is approved (same as the page itself).
        Livewire::addPersistentMiddleware([
            EnsureStudentApplicationApproved::class,
        ]);
    }

    /**
     * Requests allowed per minute, per signed-in user, for actions that
     * call a paid outside service or create records. Each action has its
     * own counter, so reaching one limit does not block the others.
     */
    public const RATE_LIMITS = [
        // One Gemini request per evaluation.
        'ai-evaluation' => 10,
        // Rebuilds the student's generated learning path.
        'learning-path' => 5,
        // Creates an enrollment in a free course.
        'enrollment' => 10,
        // Creates a transaction and a PayMongo checkout session.
        'purchase' => 5,
    ];

    protected function configureRateLimiting(): void
    {
        foreach (self::RATE_LIMITS as $name => $perMinute) {
            RateLimiter::for($name, fn (Request $request) => Limit::perMinute($perMinute)
                ->by($name.'|'.($request->user()?->getAuthIdentifier() ?: $request->ip())));
        }

        /*
         * Registration (layered). Students often register from one school
         * network or computer laboratory that shares a public IP, so the
         * tight limit is per email address on that IP, and the IP as a whole
         * gets a wider limit that changing the email address cannot avoid.
         */
        RateLimiter::for('registration', function (Request $request) {
            $email = self::normalizedEmail($request->input('email'));
            $ip = (string) $request->ip();

            return [
                Limit::perMinutes(10, 5)->by('registration-email|'.$email.'|'.$ip),
                Limit::perMinutes(10, 30)->by('registration-ip|'.$ip),
            ];
        });

        // A rejected applicant uploading a corrected Study Load.
        RateLimiter::for('study-load-resubmission', fn (Request $request) => Limit::perMinutes(10, 5)
            ->by('study-load-resubmission|'.($request->user()?->getAuthIdentifier() ?: $request->ip())));
    }

    /**
     * The same email always gives the same key: trimmed and lowercased.
     */
    public static function normalizedEmail(mixed $email): string
    {
        return mb_strtolower(trim(is_string($email) ? $email : ''));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
