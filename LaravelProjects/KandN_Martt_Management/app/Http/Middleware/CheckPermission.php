<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = Auth::user();

        if ($user->is_superadmin) {
            return $next($request);
        }

        if ($user->can($permission)) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized. Required permission: ' . $permission], 403);
    }
}
