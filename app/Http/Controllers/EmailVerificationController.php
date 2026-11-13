<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class EmailVerificationController extends Controller
{
    public function verify(string $id, string $hash): Response
    {
        // The signed middleware validates the URL and expiry before this runs.
        // Lock the row so repeated clicks only dispatch Verified once.
        DB::transaction(function () use ($id, $hash): void {
            $user = User::query()->lockForUpdate()->findOrFail($id);

            abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);
            abort_if($user->getAttribute('is_active') === false, 403);

            if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        });

        // Verification does not authenticate this browser or change its user.
        return response()->view('pages.auth.email-verified')
            ->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function status(Request $request): JsonResponse
    {
        $user = User::query()->whereKey($request->user()->getAuthIdentifier())->first();

        return response()->json([
            'verified' => $user?->hasVerifiedEmail() ?? false,
        ])->header('Cache-Control', 'no-store, private');
    }
}
