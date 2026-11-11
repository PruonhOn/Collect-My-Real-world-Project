<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Single source of truth for the API response envelope.
 *
 * Success   { "success": true,  "message": "...", "data": ... }
 * Error     { "success": false, "message": "...", "errors": { ... } }
 * Paginated { "success": true,  "data": [...], "meta": { ... }, "links": { ... } }
 *
 * Controllers must never hand-roll these shapes.
 */
final class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'OK',
        int $status = 200,
        array $meta = [],
        array $headers = [],
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status, $headers);
    }

    /**
     * 201 with the created record and a Location header.
     */
    public static function created(mixed $data = null, string $message = 'Created successfully.', ?string $location = null): JsonResponse
    {
        $headers = $location !== null ? ['Location' => $location] : [];

        return self::success($data, $message, 201, [], $headers);
    }

    public static function error(string $message, int $status = 400, array $errors = [], array $headers = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => (object) $errors,
        ], $status, $headers);
    }

    public static function notFound(string $message = 'The requested resource was not found.'): JsonResponse
    {
        return self::error($message, 404);
    }

    public static function forbidden(string $message = 'You are not authorized to perform this action.'): JsonResponse
    {
        return self::error($message, 403);
    }

    public static function conflict(string $message, array $errors = []): JsonResponse
    {
        return self::error($message, 409, $errors);
    }

    /**
     * Wraps a paginator in the paginated envelope.
     *
     * $items exists so a controller can hand over records it has already shaped
     * through a Resource, while the counts still come from the real paginator.
     * Passing a ResourceCollection instead used to be allowed, but a
     * ResourceCollection carries no counts, so `meta` silently came back empty.
     *
     * @param  array<int, mixed>|null  $items
     * @param  array<string, mixed>  $extra
     */
    public static function paginated(
        LengthAwarePaginator|CursorPaginator $paginator,
        string $message = 'OK',
        ?array $items = null,
        array $extra = [],
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $items ?? $paginator->items(),
            'meta' => self::paginationMeta($paginator),
        ];

        if ($extra !== []) {
            $payload = array_merge($payload, $extra);
        }

        return response()->json($payload);
    }

    /**
     * Normalised pagination metadata, identical for every listing endpoint.
     */
    public static function paginationMeta(mixed $paginator): array
    {
        if ($paginator instanceof LengthAwarePaginator) {
            return [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ];
        }

        if ($paginator instanceof CursorPaginator) {
            return [
                'per_page' => $paginator->perPage(),
                'has_more_pages' => $paginator->hasMorePages(),
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'previous_cursor' => $paginator->previousCursor()?->encode(),
            ];
        }

        return [];
    }
}
