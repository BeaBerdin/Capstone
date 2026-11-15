<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

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
 * It verifies the account but never logs anyone in. The browser where the
 * user is already logged in notices the verification (the verify page polls)
 * and continues to the dashboard on its own.
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

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Same browser as the account: continue straight to the dashboard,
        // exactly as Fortify did.
        if ($request->user()?->is($user)) {
            return redirect()->intended(config('fortify.home').'?verified=1');
        }

        // Another browser, or nobody logged in here: confirm, without logging in.
        return view('pages::auth.email-verified', [
            'signedInAsSomeoneElse' => $request->user() !== null,
        ]);
    }
}
