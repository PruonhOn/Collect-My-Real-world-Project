<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

final class AuditService
{
    /**
     * Record an auditable action. Safe to call from console commands and jobs
     * where no authenticated user or request context exists.
     */
    public function record(string $action, ?Model $resource = null, ?array $before = null, ?array $after = null): AuditLog
    {
        $request = request();

        return AuditLog::create([
            'user_id' => auth()->id() ?? null,
            'action' => $action,
            'resource_type' => $resource?->getMorphClass(),
            'resource_id' => $resource?->getKey(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 191) : null,
            'before' => $before,
            'after' => $after,
        ]);
    }
}
