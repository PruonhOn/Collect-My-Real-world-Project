<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces the JSON content negotiation headers on every API request.
 *
 * Without this, a stray `Accept: text/html` or a missing Accept header makes
 * Laravel answer with an HTML error page, which the SPA cannot parse. The API
 * contract guarantees JSON regardless of what the client asks for.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
