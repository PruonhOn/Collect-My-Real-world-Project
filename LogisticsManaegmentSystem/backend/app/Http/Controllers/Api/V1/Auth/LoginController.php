<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Sign-in.
 *
 * One response for a wrong password and for an address with no account. Any
 * difference between the two would turn this endpoint into a way to enumerate
 * which addresses are registered, and the address list is itself sensitive.
 */
class LoginController extends Controller
{
    /**
     * The device label stored against the token. A user may legitimately have
     * several, so this is for the activity log rather than for identification.
     */
    private const TOKEN_NAME = 'spa';

    public function store(LoginRequest $request): JsonResponse
    {
        $key = $request->throttleKey();

        if (RateLimiter::tooManyAttempts($key, $request->maxAttempts())) {
            return $this->lockedOut($key);
        }

        $user = User::query()->where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            RateLimiter::hit($key, $request->decaySeconds());

            return ApiResponse::error('These credentials do not match our records.', 401);
        }

        // Checked only after the password verified, so this cannot be used to
        // discover which addresses belong to deactivated accounts.
        if (! $user->is_active) {
            RateLimiter::hit($key, $request->decaySeconds());

            return ApiResponse::forbidden(
                'Your account has been deactivated. Please contact an administrator.'
            );
        }

        // Same ordering rule as the deactivated check above: the password was
        // correct, so this reveals nothing an attacker did not already have.
        // The field key is a contract with the SPA, which offers a resend button
        // only when it knows verification is what blocked the sign-in.
        if (! $user->hasVerifiedEmail()) {
            return ApiResponse::error(
                'Please verify your email address before signing in.',
                403,
                ['verification_required' => ['Check your inbox for the verification link.']],
            );
        }

        // A correct password clears the budget, so a user who fumbles their own
        // password a few times is not left waiting out a lockout afterwards.
        RateLimiter::clear($key);

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken(
            self::TOKEN_NAME,
            // The `remember` ability exempts the token from expiry. Without it a
            // "keep me signed in" tick box would do nothing, because the token
            // would still die at SANCTUM_TOKEN_EXPIRATION.
            $request->boolean('remember') ? ['remember'] : [],
        );

        return ApiResponse::success([
            'token' => $token->plainTextToken,
            'expires_in' => (int) config('logistics.auth.token_expiration_minutes') * 60,
            'user' => UserResource::make($user)->resolve(),
        ], 'Signed in successfully.');
    }

    /**
     * 429 with a Retry-After header.
     *
     * Returned rather than thrown so the message can name the wait in seconds.
     * The SPA shows the message verbatim, and "Too many requests. Please slow
     * down" tells a user nothing about when to try again.
     */
    private function lockedOut(string $key): JsonResponse
    {
        $seconds = RateLimiter::availableIn($key);

        return ApiResponse::error(
            "Too many sign-in attempts. Please try again in {$seconds} seconds.",
            429,
            [],
            ['Retry-After' => (string) $seconds],
        );
    }
}
