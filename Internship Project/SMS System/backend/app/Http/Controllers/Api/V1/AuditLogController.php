<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query()->with('user');

        if (! $request->user()->can('audit_logs.view')) {
            $query->where('user_id', $request->user()->getKey());
        }

        $paginator = $this->paginate($query, $request, ['action', 'resource_type', 'user.name'], [], 'created_at', 'desc');

        return response()->json([
            'data' => AuditLogResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }
}
