<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out any user whose account was deactivated by the Super Admin.
 *
 * It runs on every web request, so a deactivated user is logged out on
 * their next click, even if they were already logged in.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only an explicit "false" blocks the user. A missing value (for
        // example, before the migration has run) is treated as active.
        if ($user && $user->getAttribute('is_active') === false) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Your account has been deactivated. Please contact the EDP office.',
                ]);
        }

        return $next($request);
    }
}
