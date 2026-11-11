<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        foreach ($permissions as $permission) {
            if (!Auth::user()->can($permission)) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['message' => 'Unauthorized. You do not have the required permission: ' . $permission], 403);
                }
                abort(403, 'Unauthorized. You do not have the required permission: ' . $permission);
            }
        }

        return $next($request);
    }
}
