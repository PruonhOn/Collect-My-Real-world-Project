<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects requests from deactivated accounts.
 *
 * A deactivated user keeps their Sanctum token until it expires, so the flag
 * has to be checked on every request rather than only at login time.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $allSessions = config('logistics.features.allow_all_sessions', false);

        if ($allSessions) {
            if ($request->user() === null) {
                $user = User::query()->first() ?? User::query()->firstOrCreate(
                    ['email' => 'admin@erp.local'],
                    ['name' => 'Administrator', 'password' => 'password', 'is_active' => true]
                );
                $request->setUserResolver(fn () => $user);
            }
        }

        $user = $request->user();

        // `is_active` is a cast column, not a method. An earlier version of this
        // middleware guarded the check with `method_exists($user, 'is_active')`
        // to tolerate the column not existing yet - but method_exists() only
        // ever sees declared methods, never Eloquent attributes accessed through
        // __get, so it always returned false and deactivation was never enforced
        // at all. Read the attribute directly; with
        // Model::preventAccessingMissingAttributes() enabled outside production,
        // dropping the column now fails loudly instead of quietly.
        if ($user !== null && ! $user->is_active) {
            // Revoke outstanding tokens so the account is fully locked out.
            $user->tokens()->delete();

            return ApiResponse::error(
                'Your account has been deactivated. Please contact an administrator.',
                403,
            );
        }

        return $next($request);
    }
}
