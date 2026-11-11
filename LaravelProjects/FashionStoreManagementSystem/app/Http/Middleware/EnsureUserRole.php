<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $allowed = array_map(fn (string $role) => Role::from($role), $roles);

        if (! $user->hasRole(...$allowed)) {
            abort(403, 'You do not have permission to access this section.');
        }

        return $next($request);
    }
}
