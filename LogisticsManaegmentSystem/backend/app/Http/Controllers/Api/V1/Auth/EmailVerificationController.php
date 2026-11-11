<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The two halves of email ownership proof.
 *
 * verify() is hit by clicking the link in the mail. It lives on the web routes
 * because the browser arrives there directly, and finishes by redirecting to
 * the SPA rather than rendering anything: the API owns no HTML.
 *
 * store() re-sends the mail. It answers the same way whether or not the address
 * exists and whether or not it is already verified, so it cannot be used to
 * enumerate registered accounts.
 */
class EmailVerificationController extends Controller
{
    /**
     * GET /email/verify/{id}/{hash}
     *
     * `signed` on the route is the authentication here. The hash proves the
     * link was minted for this address, so no session is involved and the link
     * can be opened on a phone that has never signed in.
     */
    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $frontend = (string) config('logistics.auth.frontend_url');
        $user = User::query()->find($id);

        if (! $user || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return redirect()->away($frontend.'/login?verified=invalid');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->away($frontend.'/login?verified=already');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->away($frontend.'/login?verified=1');
    }

    /**
     * POST /api/v1/auth/email/verification-notification
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $user = User::query()
            ->where('email', strtolower(trim((string) $validated['email'])))
            ->first();

        if ($user !== null && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return ApiResponse::success(
            null,
            'If that address needs verification, a new link is on its way.',
        );
    }
}
