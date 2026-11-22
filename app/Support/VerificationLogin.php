<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Signing a student in on the browser where they opened the verification
 * link (often a different browser from the one they registered in).
 *
 * The emailed link is a signed URL that stays valid until it expires and
 * can be opened again, so the link itself never logs anyone in. Instead:
 *
 *  1. Opening the link (GET) may store a one-time GRANT in that browser's
 *     own server-side session: never in a URL, a cookie value or the logs,
 *     and valid for GRANT_MINUTES. Opening it never signs in and never
 *     uses the link up.
 *  2. The page shows which account (name and masked email) and waits for
 *     the student to click Continue: a CSRF-protected POST that redeems the
 *     grant. Nothing submits by itself, so an email security scanner that
 *     opens (or even renders) the link does not sign in or use the link up.
 *  3. Redeeming marks the link as used atomically in the database
 *     (verification_link_uses: a unique hash of the link's signature), so one
 *     link signs in at most once, even with concurrent clicks and even after
 *     the cache is cleared; the session is regenerated (no session fixation).
 *     Different links are separate: each is single-use on its own.
 *
 * Only an approved, active student without two-factor authentication, whose
 * email was verified within WINDOW_MINUTES, is eligible. A browser already
 * signed in to another account is never switched without an explicit click.
 */
class VerificationLogin
{
    public const SESSION_KEY = 'verification_login_grant';

    /** How long the grant in the session may be redeemed. */
    public const GRANT_MINUTES = 5;

    /** A link signs in only shortly after the email was verified. */
    public const WINDOW_MINUTES = 30;

    /** Used links (hashes only). */
    public const TABLE = 'verification_link_uses';

    public static function eligible(?User $user): bool
    {
        return $user !== null
            && $user->getAttribute('is_active') !== false
            && $user->hasVerifiedEmail()
            && $user->email_verified_at->greaterThan(now()->subMinutes(self::WINDOW_MINUTES))
            // A link must never bypass two-factor authentication.
            && blank($user->two_factor_confirmed_at)
            && $user->hasRole('student')
            && ! $user->awaitingStudentApproval();
    }

    public static function linkHash(?string $signature): ?string
    {
        return filled($signature) ? hash('sha256', $signature) : null;
    }

    /**
     * True when the link was already used to sign in. Fails closed: a
     * missing hash, or a database problem (for example the table not
     * migrated yet), counts as used, so no sign-in is offered.
     */
    public static function alreadyUsed(?string $linkHash): bool
    {
        if ($linkHash === null) {
            return true;
        }

        try {
            return DB::table(self::TABLE)->where('link_hash', $linkHash)->exists();
        } catch (QueryException $e) {
            self::logUnavailable($e);

            return true;
        }
    }

    /**
     * Store the one-time grant in this browser's session.
     */
    public static function grant(Request $request, User $user, string $linkHash): void
    {
        $request->session()->put(self::SESSION_KEY, [
            'user_id' => $user->id,
            'link' => $linkHash,
            'expires_at' => now()->addMinutes(self::GRANT_MINUTES)->getTimestamp(),
        ]);
    }

    /**
     * Mark the link as used. True only for the first caller: the unique
     * index on link_hash lets exactly one insert succeed (INSERT IGNORE on
     * MySQL), so two simultaneous attempts cannot both sign in. Fails
     * closed on a database problem.
     */
    public static function consume(User $user, string $linkHash): bool
    {
        // Only a real SHA-256 hex hash can be consumed.
        if (preg_match('/^[0-9a-f]{64}$/', $linkHash) !== 1) {
            return false;
        }

        try {
            return DB::table(self::TABLE)->insertOrIgnore([
                'user_id' => $user->id,
                'link_hash' => $linkHash,
                'used_at' => now(),
            ]) === 1;
        } catch (QueryException $e) {
            self::logUnavailable($e);

            return false;
        }
    }

    /**
     * "juan.delacruz@gmail.com" -> "j•••z@gmail.com"; "jo@x.ph" -> "j•••@x.ph".
     * Enough for the student to recognise their account, without showing the
     * full address to anyone else who opens the link.
     */
    public static function maskEmail(string $email): string
    {
        $at = mb_strrpos($email, '@');

        if ($at === false || $at === 0) {
            return '•••';
        }

        $local = mb_substr($email, 0, $at);
        $domain = mb_substr($email, $at + 1);
        $length = mb_strlen($local);

        return mb_substr($local, 0, 1).'•••'.($length > 2 ? mb_substr($local, -1) : '').'@'.$domain;
    }

    private static function logUnavailable(QueryException $e): void
    {
        Log::warning('PATHWISE could not read or write used verification links; the sign-in from another browser was refused (run the migrations).', [
            'error' => $e->getMessage(),
        ]);
    }
}
