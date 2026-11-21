<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps students and self-registered teachers whose application is pending
 * or rejected out of the protected pages (including every `role:teacher`
 * route) until the Super Admin (EDP) approves them.
 *
 * Runs before `verified`, so an applicant goes to the application status
 * page instead of the "verify your email" page. It only ever acts on
 * Student or Teacher accounts with a pending or rejected newest application;
 * admins, the Super Admin and accounts without an application (existing
 * teachers and students, accounts created by the Super Admin) pass straight
 * through. It is also re-applied to Livewire requests made from protected
 * pages.
 */
class EnsureStudentApplicationApproved
{
    public const MESSAGE = 'Your student application is still being reviewed by the EDP office. You can use PathWise once it is approved and your email is verified.';

    public const TEACHER_MESSAGE = 'Your teacher registration is still being reviewed by the EDP office. You can use the teacher tools once it is approved and your email is verified.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->awaitingApproval()) {
            return $next($request);
        }

        $message = $user->hasRole('teacher') ? self::TEACHER_MESSAGE : self::MESSAGE;

        // Livewire / AJAX actions get a plain refusal.
        if ($request->expectsJson() || $request->hasHeader('X-Livewire')) {
            abort(403, $message);
        }

        $redirect = redirect()->route('student-application.status');

        return $request->isMethod('GET') || $request->isMethod('HEAD')
            ? $redirect
            : $redirect->with('error', $message);
    }
}
