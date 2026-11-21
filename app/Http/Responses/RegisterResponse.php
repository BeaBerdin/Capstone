<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;

/**
 * After registration (Fortify has already logged the new user in):
 *  - a student applicant goes to the application status page;
 *  - everyone else keeps Fortify's usual redirect.
 */
class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 201);
        }

        if ($request->user()?->awaitingApproval()) {
            return redirect()->route('student-application.status');
        }

        return redirect()->intended(Fortify::redirects('register'));
    }
}
