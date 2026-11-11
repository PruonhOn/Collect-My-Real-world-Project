<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The current session: who is signed in, and signing out.
 */
class SessionController extends Controller
{
    /**
     * GET /api/v1/auth/me
     *
     * Also the SPA's boot-time session check: a stored token is validated here
     * on page load, so a revoked or expired token is discarded before the user
     * reaches a protected screen rather than after their first failed request.
     */
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(
            UserResource::make($request->user())->resolve(),
            'Current user.',
        );
    }

    /**
     * POST /api/v1/auth/logout
     *
     * Revokes only the token that made this request. Signing out on one device
     * must not sign the user out everywhere - a shared office machine and a
     * personal phone are different sessions with different exposure.
     *
     * Endpoint is idempotent by design: the SPA calls it on the way out without
     * caring whether the token was still valid, and the local token is cleared
     * either way.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Signed out successfully.');
    }
}
