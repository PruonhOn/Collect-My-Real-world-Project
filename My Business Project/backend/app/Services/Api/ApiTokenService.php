<?php

namespace App\Services\Api;

use App\Models\ApiToken;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Issues and validates business scoped API tokens for external clients.
 *
 * The plaintext token is only ever returned once, at creation time. Only a
 * hash is stored, so a database leak cannot be replayed against the API.
 */
class ApiTokenService
{
    /**
     * Abilities that may be granted to an external token.
     */
    public const ABILITIES = [
        'products:read',
        'products:write',
        'customers:read',
        'customers:write',
        'orders:read',
        'orders:write',
        'invoices:read',
        'reports:read',
    ];

    /**
     * Create a token for a business and return the plaintext value once.
     *
     * @param  array<int, string>  $abilities
     */
    public function issue(Business $business, User $user, string $name, array $abilities, ?int $expiresInDays = null): array
    {
        $plainText = Str::random(64);

        $token = ApiToken::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'name' => $name,
            'token_hash' => hash('sha256', $plainText),
            'abilities' => $abilities,
            'expires_at' => $expiresInDays ? now()->addDays($expiresInDays) : null,
        ]);

        return [
            'token' => $token,
            'plain_text' => $plainText,
        ];
    }

    /**
     * Resolve a plaintext token to its stored record.
     */
    public function resolve(string $plainText): ?ApiToken
    {
        $token = ApiToken::withoutGlobalScopes()
            ->where('token_hash', hash('sha256', $plainText))
            ->first();

        if (! $token || ! $token->isUsable()) {
            return null;
        }

        return $token;
    }

    /**
     * Revoke a token that belongs to the given business.
     */
    public function revoke(Business $business, ApiToken $token): void
    {
        abort_unless($token->business_id === $business->id, 404, 'Token not found.');

        $token->update(['revoked_at' => now()]);
    }
}
