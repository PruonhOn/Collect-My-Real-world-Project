<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Email verification for the first-party SPA.
 *
 * The verification link is a signed, temporary URL. Following it marks the
 * account as verified. The frontend is redirected back with the outcome so
 * that the user never has to leave the application flow.
 */
class VerificationController extends Controller
{
    /**
     * Send a fresh verification link to the authenticated user.
     */
    public function send(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'The email address is already verified.'], 422);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'A verification link has been sent to your email address.',
            'verification_url' => $this->verificationUrl($user),
        ]);
    }

    /**
     * Verify an email address from a signed URL.
     *
     * The signature is validated before anything is changed: without it the
     * link could be forged by anyone who knows (or guesses) an email
     * address.
     */
    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return $this->respond($request, 'invalid');
        }

        $user = User::find($id);

        if (! $user) {
            return $this->respond($request, 'invalid');
        }

        if (! hash_equals(sha1((string) $user->getEmailForVerification()), (string) $hash)) {
            return $this->respond($request, 'invalid');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->respond($request, 'already-verified');
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return $this->respond($request, 'verified');
    }

    /**
     * Build the signed verification URL that is emailed to the user.
     */
    protected function verificationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $user->id,
                'hash' => sha1((string) $user->email),
            ]
        );
    }

    /**
     * Redirect a browser to the SPA, but answer API clients with JSON.
     */
    protected function respond(Request $request, string $status): JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status' => $status,
                'message' => $status === 'verified'
                    ? 'Your email address has been verified.'
                    : 'This verification link is invalid or has expired.',
            ], $status === 'verified' || $status === 'already-verified' ? 200 : 400);
        }

        return redirect()->away(config('app.frontend_url').'/verify-email?status='.$status);
    }
}
