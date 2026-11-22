<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\VerificationLogin;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Handles the link in the verification email.
 *
 * Fortify's own route requires the user to be logged in, so opening the link
 * in another browser or the Gmail app sent the user to the login page first.
 * This route only needs a valid link:
 *
 *  - the `signed` middleware checks Laravel's signature and the expiry time;
 *  - the hash must match the account's email address, as in Laravel;
 *  - the route is throttled like Fortify's.
 *
 * The link itself never logs anyone in. The browser where the student
 * registered notices the verification (its pages poll) and continues to the
 * dashboard on its own. On another browser, an eligible student is signed in
 * through a one-time, session-bound grant redeemed by a CSRF-protected POST
 * that the student starts by clicking Continue (see App\Support\VerificationLogin
 * and continue()); a browser signed in to another account is only switched
 * when the user explicitly chooses to.
 */
class VerifyEmailLinkController extends Controller
{
    public function __invoke(Request $request, string $id, string $hash)
    {
        $user = User::find($id);

        abort_if(
            ! $user || ! hash_equals(sha1($user->getEmailForVerification()), $hash),
            403
        );

        // A student or teacher whose application is pending or rejected
        // cannot verify, even with a valid signed link; only the EDP's
        // approval allows it.
        abort_if(
            $user->awaitingApproval(),
            403,
            $user->hasRole('teacher')
                ? 'This account cannot be verified yet: the teacher registration has not been approved by the EDP office.'
                : 'This account cannot be verified yet: the student application has not been approved by the EDP office.'
        );

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Same browser as the account: continue straight to the dashboard,
        // exactly as Fortify did.
        if ($request->user()?->is($user)) {
            return redirect()->intended(config('fortify.home').'?verified=1');
        }

        // Another browser: the link itself never logs in. An eligible
        // student gets a one-time grant in THIS browser's session, redeemed
        // by a CSRF-protected POST (see VerificationLogin and continue()).
        $linkHash = VerificationLogin::linkHash($request->query('signature'));
        $canContinue = VerificationLogin::eligible($user) && ! VerificationLogin::alreadyUsed($linkHash);

        if ($canContinue) {
            VerificationLogin::grant($request, $user, $linkHash);
        } else {
            $request->session()->forget(VerificationLogin::SESSION_KEY);
        }

        return response()
            ->view('pages::auth.email-verified', [
                'signedInAsSomeoneElse' => $request->user() !== null,
                'currentUserName' => $request->user()?->name,
                'canContinue' => $canContinue,
                'studentName' => $user->name,
                // Shown so the student knows which account Continue opens.
                'studentEmailMasked' => VerificationLogin::maskEmail((string) $user->email),
            ])
            // Keep the signed link out of Referer headers and caches.
            ->header('Referrer-Policy', 'no-referrer')
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * Redeem the one-time grant: sign the student in on this browser.
     *
     * - The grant must exist in this session and be unexpired; it is removed
     *   on the first attempt, whatever the outcome.
     * - Everything is checked again (approved, active, verified recently,
     *   no two-factor).
     * - The link is marked as used atomically in the database: one sign-in
     *   per link, kept even when the cache is cleared.
     * - A browser signed in to another account is switched only when the
     *   user explicitly chose "switch" (otherwise nothing changes).
     * - The session id and CSRF token are regenerated.
     */
    public function continue(Request $request)
    {
        $grant = $request->session()->pull(VerificationLogin::SESSION_KEY);
        // grant() always stores an integer id; anything else is not a grant.
        $grantUserId = is_array($grant) ? ($grant['user_id'] ?? null) : null;
        $student = is_int($grantUserId) ? User::find($grantUserId) : null;
        $current = $request->user();

        $usable = $student
            && ($grant['expires_at'] ?? 0) >= now()->getTimestamp()
            && VerificationLogin::eligible($student);

        if (! $usable) {
            return $current
                ? redirect()->route('dashboard')
                : redirect()->route('login')->with('status', 'Your email is verified. Please log in to continue.');
        }

        if ($current?->is($student)) {
            return redirect()->route('dashboard');
        }

        // Never replace another account's session without an explicit choice.
        if ($current && ! $request->boolean('switch')) {
            return redirect()->route('dashboard');
        }

        if (! VerificationLogin::consume($student, (string) ($grant['link'] ?? ''))) {
            return $current
                ? redirect()->route('dashboard')
                : redirect()->route('login')->with('status', 'This verification link was already used to sign in. Please log in.');
        }

        if ($current) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
        }

        Auth::guard('web')->login($student);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('dashboard');
    }
}
