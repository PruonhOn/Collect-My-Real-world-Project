<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Completes a password reset.
 */
class NewPasswordController extends Controller
{
    /**
     * POST /api/v1/auth/reset-password
     *
     * 422 when the token is unknown, already used, or past its expiry - all
     * three are the same situation to a user holding a dead link, and
     * distinguishing them would confirm which tokens had ever been issued.
     */
    public function store(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    // A reset is the user's statement that the old password is
                    // compromised. Any session still running on it has to die.
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return ApiResponse::error(
                'This password reset link is invalid or has expired. Please request a new one.',
                422,
                ['email' => ['The password reset link is invalid or has expired.']],
            );
        }

        // The user is not signed in here. Making them authenticate again is the
        // point of a reset: whoever prompted it should not inherit a session.
        return ApiResponse::success(null, 'Your password has been reset. You can now sign in.');
    }
}
