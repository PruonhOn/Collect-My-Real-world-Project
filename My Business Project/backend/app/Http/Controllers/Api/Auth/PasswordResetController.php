<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Password reset for the first-party SPA.
 *
 * The reset link is delivered by email; the token is exchanged for a new
 * password on this endpoint.
 */
class PasswordResetController extends Controller
{
    /**
     * Send a reset link. The response never discloses whether the account
     * exists, so the endpoint cannot be used to enumerate users.
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(['email' => $request->email]);

        if ($status === Password::RESET_THROTTLED) {
            return response()->json([
                'message' => 'A reset link was already sent. Please wait before requesting another one.',
            ], 429);
        }

        return response()->json([
            'message' => 'If the account exists, a password reset link has been sent.',
        ]);
    }

    /**
     * Exchange a valid reset token for a new password.
     */
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Every existing session/token is invalidated after a reset.
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'This password reset link is invalid or has expired.',
                'errors' => ['email' => [__($status)]],
            ], 422);
        }

        return response()->json(['message' => 'Your password has been reset.']);
    }
}
