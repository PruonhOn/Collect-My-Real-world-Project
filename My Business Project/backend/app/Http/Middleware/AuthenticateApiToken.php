<?php

namespace App\Http\Middleware;

use App\Services\Api\ApiTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates external API clients with a business scoped API token.
 *
 * The token determines the business context: an external client can never
 * read or write data outside the business the token was issued for. The
 * abilities stored on the token are enforced per route.
 */
class AuthenticateApiToken
{
    public function __construct(protected ApiTokenService $tokens) {}

    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        $token = $request->attributes->get('current_api_token');

        if (! $token) {
            $plainText = $this->extractToken($request);

            if (! $plainText) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            $token = $this->tokens->resolve($plainText);
        }

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $business = $token->business;
        $user = $token->user;

        if (! $business || ! $user || $business->is_suspended || ! $user->is_active) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // API access is a paid entitlement: a downgraded business loses it.
        if (! app(\App\Services\Features\FeatureGate::class)->allows($business, 'api_access')) {
            return response()->json([
                'message' => 'API access is not included in your current plan.',
                'upgrade_required' => true,
            ], 403);
        }

        if ($ability !== null && ! in_array($ability, $token->abilities ?? [], true)) {
            return response()->json([
                'message' => "This token is not allowed to perform [{$ability}].",
            ], 403);
        }

        // The tenant context is derived from the token, never from the request.
        app()->instance('current.business', $business);
        app()->instance('current.api_token', $token);

        $request->attributes->set('current_api_token', $token);
        $request->attributes->set('current_business', $business);
        $request->setUserResolver(fn () => $user);

        $token->forceFill(['last_used_at' => now()])->saveQuietly();

        return $next($request);
    }

    protected function extractToken(Request $request): ?string
    {
        $header = (string) $request->header('Authorization');

        if (str_starts_with($header, 'Bearer ')) {
            return trim(substr($header, 7));
        }

        return null;
    }
}
