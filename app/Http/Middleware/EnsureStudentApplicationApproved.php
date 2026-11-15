<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps students whose application is pending or rejected out of the
 * protected pages until the Super Admin (EDP) approves them.
 *
 * Runs before `verified`, so an applicant goes to the application status
 * page instead of the "verify your email" page. It only ever acts on
 * Student accounts with a pending or rejected newest application; teachers,
 * admins, the Super Admin and grandfathered accounts pass straight through.
 * It is also re-applied to Livewire requests made from protected pages.
 */
class EnsureStudentApplicationApproved
{
    public const MESSAGE = 'Your student application is still being reviewed by the EDP office. You can use PathWise once it is approved and your email is verified.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->awaitingStudentApproval()) {
            return $next($request);
        }

        // Livewire / AJAX actions get a plain refusal.
        if ($request->expectsJson() || $request->hasHeader('X-Livewire')) {
            abort(403, self::MESSAGE);
        }

        $redirect = redirect()->route('student-application.status');

        return $request->isMethod('GET') || $request->isMethod('HEAD')
            ? $redirect
            : $redirect->with('error', self::MESSAGE);
    }
}
