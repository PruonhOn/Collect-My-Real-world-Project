<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Base class for every paginated API listing.
 *
 * Returning `ModelResource::collection($paginator)` normally yields Laravel's
 * default `{data, links, meta}` shape. Extending this instead guarantees the
 * project-wide envelope:
 *
 *     { "success": true, "data": [...], "meta": { current_page, per_page, total, ... } }
 *
 * Only use this for a paginated result. For a plain array (dropdown options,
 * reference lists) return `ApiResponse::success($data)` from the controller.
 */
abstract class PaginatedResource extends ResourceCollection
{
    public function toResponse($request): JsonResponse
    {
        /** @var LengthAwarePaginator $paginator */
        $paginator = $this->resource;

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => $this->resolve(),
            'meta' => ApiResponse::paginationMeta($paginator),
        ]);
    }
}
