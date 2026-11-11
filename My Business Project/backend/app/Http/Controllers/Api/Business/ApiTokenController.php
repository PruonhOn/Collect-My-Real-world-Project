<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Api\Controller;
use App\Models\ApiToken;
use App\Services\Api\ApiTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Management of revocable API tokens for external clients.
 *
 * Tokens are a Pro entitlement (`api_access`) and are always scoped to a
 * single business. A token can never widen the access of the user that
 * created it.
 */
class ApiTokenController extends Controller
{
    public function __construct(protected ApiTokenService $tokens) {}

    public function index(): JsonResponse
    {
        $business = app('current.business');

        $tokens = $business->apiTokens()
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ApiToken $token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities ?? [],
                'is_revoked' => $token->revoked_at !== null,
                'expires_at' => $token->expires_at?->toIso8601String(),
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'created_at' => $token->created_at->toIso8601String(),
                'user' => $token->user ? [
                    'id' => $token->user->id,
                    'name' => $token->user->name,
                    'email' => $token->user->email,
                ] : null,
            ]);

        return response()->json([
            'data' => $tokens,
            'available_abilities' => ApiTokenService::ABILITIES,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $business = app('current.business');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['string', Rule::in(ApiTokenService::ABILITIES)],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $issued = $this->tokens->issue(
            $business,
            $request->user(),
            $validated['name'],
            $validated['abilities'],
            $validated['expires_in_days'] ?? null
        );

        return response()->json([
            'message' => 'The token is shown once. Store it somewhere safe.',
            'data' => $issued['token'],
            'token' => $issued['plain_text'],
        ], 201);
    }

    public function destroy(Request $request, ApiToken $token): JsonResponse
    {
        $business = app('current.business');

        $this->tokens->revoke($business, $token);

        return response()->json(['message' => 'Token revoked.']);
    }
}
