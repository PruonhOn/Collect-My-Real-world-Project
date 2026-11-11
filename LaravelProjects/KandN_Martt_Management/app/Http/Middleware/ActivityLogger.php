<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        if (Auth::check() && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $route = $request->route();
            $module = $route ? $route->getName() : $request->path();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => $request->method(),
                'module' => $module,
                'reference_type' => $request->path(),
                'reference_id' => $request->route('id'),
                'description' => $request->method() . ' on ' . $request->path(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }
}
