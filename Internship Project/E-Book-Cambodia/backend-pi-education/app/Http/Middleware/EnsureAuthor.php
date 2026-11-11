<?php

namespace App\Http\Middleware;

use App\Models\Author;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthor
{
    /**
     * Only an active author account may continue. A suspended author's
     * existing tokens are invalidated on suspension and rejected here too.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $author = $request->user();

        if (! $author instanceof Author) {
            return response()->json([
                'message' => 'Forbidden. Only author accounts can access this resource.',
            ], 403);
        }

        if ($author->isSuspended()) {
            return response()->json([
                'message' => 'Your account has been suspended.',
            ], 403);
        }

        return $next($request);
    }
}
