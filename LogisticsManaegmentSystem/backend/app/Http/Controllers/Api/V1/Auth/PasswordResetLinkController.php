<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

/**
 * Issues a password reset link.
 */
class PasswordResetLinkController extends Controller
{
    /**
     * POST /api/v1/auth/forgot-password
     *
     * Always 200 with the same message, whether or not the address has an
     * account and whether or not a mail was actually sent. The 422 case is the
     * one exception: that is the address failing to be an email address at all,
     * which says nothing about whether it is registered.
     */
    public function store(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::broker()->sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            // Worth knowing about, never worth answering differently about. A
            // hard bounce means the address on file is stale and an admin has
            // to fix it, which no user-facing message could act on anyway.
            Log::warning('Password reset link could not be sent.', [
                'email' => $request->input('email'),
                'status' => $status,
            ]);
        }

        return ApiResponse::success(
            null,
            'If an account exists for that address, a password reset link is on its way.',
        );
    }
}
