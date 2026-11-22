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
        // Typing an invite code on the Course Invitations page.
        'invite-code' => 10,
        // Viewing an invitation preview; preserve the Local-Test limiter.
        'invite-preview' => 30,
    ];

    protected function configureRateLimiting(): void
    {
        foreach (self::RATE_LIMITS as $name => $perMinute) {
            // AI evaluation gets its own limiter below (friendly message).
            if ($name === 'ai-evaluation') {
                continue;
            }

            RateLimiter::for($name, fn (Request $request) => Limit::perMinute($perMinute)
                ->by($name.'|'.($request->user()?->getAuthIdentifier() ?: $request->ip())));
        }

        // PathWise's own AI evaluation limit: the teacher sees the same
        // friendly message as for any other AI outage (details are logged).
        RateLimiter::for('ai-evaluation', fn (Request $request) => Limit::perMinute(self::RATE_LIMITS['ai-evaluation'])
            ->by('ai-evaluation|'.($request->user()?->getAuthIdentifier() ?: $request->ip()))
            ->response(function (Request $request) {
                \App\Support\AiAvailability::logUnavailable('AI evaluation', null, [
                    'limit' => 'PathWise per-minute AI evaluation limit',
                    'teacher_id' => $request->user()?->getAuthIdentifier(),
                ]);

                $submission = $request->route('submission');

                return ($submission
                    ? redirect()->route('teacher.submissions.show', $submission)
                    : back())
                    ->with('error', \App\Support\AiAvailability::MESSAGE);
            }));

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

        $this->configureVerificationRateLimiting();
    }

    /**
     * Email verification and the application status page.
     *
     * Laravel's plain `throttle:6,1` keeps ONE counter per signed-in user, or
     * per IP address for guests, shared by every route that uses it. On a
     * campus network many students share one public IP, so a handful of
     * students opening their links used up everyone's limit, and the status
     * page's polling used up the student's own "resend" limit.
     *
     * Each route below has its own named limiter (its own counters). The
     * tight limit belongs to one account, link session or user; the per-IP
     * limit is a wide ceiling against a single machine flooding the server.
     * $request->ip() is the connecting address: no proxy is trusted
     * (bootstrap/app.php sets none), so X-Forwarded-For cannot change it.
     */
    public const VERIFICATION_LIMITS = [
        // GET /email/verify/{id}/{hash}: per account, and per IP.
        'link_per_account_per_minute' => 10,
        'link_per_ip_per_minute' => 300,
        // POST /email/verified/continue: per browser session, and per IP.
        'continue_per_session_per_minute' => 6,
        'continue_per_ip_per_minute' => 300,
        // POST /email/verification-notification: per user (sends an email).
        'resend_per_user_per_minute' => 6,
        'resend_per_user_per_hour' => 20,
        // GET /application-status/check (polled every 5 seconds): per user.
        'check_per_user_per_minute' => 60,
    ];

    protected function configureVerificationRateLimiting(): void
    {
        $limits = self::VERIFICATION_LIMITS;

        // Laravel runs the throttle BEFORE `signed` (ThrottleRequests is in
        // the middleware priority list, ValidateSignature is not). So the
        // per-account counter is used only for a genuinely signed link:
        // otherwise anyone could send forged links with a student's id and
        // lock that student out. Every request, forged or not, still counts
        // towards the per-IP ceiling; `signed` then refuses forged ones (403).
        RateLimiter::for('verification-link', function (Request $request) use ($limits) {
            $perIp = Limit::perMinute($limits['link_per_ip_per_minute'])
                ->by('verification-link-ip|'.$request->ip());

            if (! $request->hasValidSignature()) {
                return [$perIp];
            }

            return [
                Limit::perMinute($limits['link_per_account_per_minute'])
                    ->by('verification-link-account|'.(string) $request->route('id')),
                $perIp,
            ];
        });

        // The one-time grant lives in the browser's session, so the session
        // is the natural owner of this counter.
        RateLimiter::for('verification-continue', fn (Request $request) => [
            Limit::perMinute($limits['continue_per_session_per_minute'])
                ->by('verification-continue-session|'.($request->hasSession() ? $request->session()->getId() : $request->ip())),
            Limit::perMinute($limits['continue_per_ip_per_minute'])
                ->by('verification-continue-ip|'.$request->ip()),
        ]);

        RateLimiter::for('verification-resend', fn (Request $request) => [
            Limit::perMinute($limits['resend_per_user_per_minute'])
                ->by('verification-resend-minute|'.($request->user()?->getAuthIdentifier() ?: $request->ip())),
            Limit::perHour($limits['resend_per_user_per_hour'])
                ->by('verification-resend-hour|'.($request->user()?->getAuthIdentifier() ?: $request->ip())),
        ]);

        // Separate from every other counter: polling can never use up the
        // resend limit (or anything else).
        RateLimiter::for('application-check', fn (Request $request) => Limit::perMinute($limits['check_per_user_per_minute'])
            ->by('application-check|'.($request->user()?->getAuthIdentifier() ?: $request->ip())));
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
