<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Sign-up.
 *
 * Registration deliberately does not sign the user in. The address is
 * unverified until the link in the outgoing mail is opened, and LoginController
 * refuses an unverified account, so issuing a token here would only hand the
 * SPA a session it cannot keep.
 */
class RegisterController extends Controller
{
    public function store(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];

        $existingUser = User::query()->where('email', $email)->first();

        if ($existingUser) {
            if ($existingUser->hasVerifiedEmail()) {
                return ApiResponse::error('Validation failed.', 422, [
                    'email' => ['The email has already been taken.'],
                ]);
            }

            // Unverified account already exists: update details and resend verification
            $existingUser->update([
                'name' => $validated['name'],
                'password' => $validated['password'],
            ]);
            $user = $existingUser;
        } else {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $email,
                'password' => $validated['password'],
                'is_active' => true,
            ]);

            $user->assignRole('viewer');
        }

        // Sent synchronously so a mailing failure is visible now rather than
        // discovered when the user says the mail never arrived. The account
        // already exists, so the resend endpoint is the recovery path.
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);
        }

        return ApiResponse::success([
            'email' => $user->email,
            'verification_required' => true,
        ], 'Account created. Please check your email to verify your address.', 201);
    }
}
