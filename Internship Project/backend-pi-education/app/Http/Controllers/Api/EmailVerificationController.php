<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function sendNotification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent.']);
    }

    public function verify(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $user = User::findOrFail($id);

        if (! $request->hasValidSignature()
            || ! hash_equals((string) sha1($user->getEmailForVerification()), (string) $request->query('hash'))) {
            abort(403, 'Invalid or expired verification link.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->away(rtrim(config('frontend.url'), '/').'/login?verified=1');
    }
}
