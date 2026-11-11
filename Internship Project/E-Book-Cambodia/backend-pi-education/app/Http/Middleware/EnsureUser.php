<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUser
{
    /**
     * Only a member (reader) or admin account may continue. Author tokens
     * are valid Sanctum authenticatables but must never reach member routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof User) {
            return response()->json([
                'message' => 'Forbidden. Author accounts cannot access this resource.',
            ], 403);
        }

        return $next($request);
    }
}
