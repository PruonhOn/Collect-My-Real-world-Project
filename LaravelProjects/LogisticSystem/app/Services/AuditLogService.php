<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AuditLogService extends BaseService
{
    public function __construct(BaseRepository $repository)
    {
        parent::__construct($repository);
    }

    public function log(string $action, string $module, string $description, ?array $oldValues = null, ?array $newValues = null, ?int $userId = null, ?string $ipAddress = null, ?string $userAgent = null, ?int $auditableId = null, ?string $auditableType = null): AuditLog
    {
        $data = [
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ];

        if ($auditableId && $auditableType) {
            $data['auditable_id'] = $auditableId;
            $data['auditable_type'] = $auditableType;
        }

        /** @var AuditLog */
        return $this->repository->create($data);
    }

    public function getLogsByUser(int $userId): Collection
    {
        return $this->repository->findAllBy('user_id', $userId);
    }

    public function getLogsByModule(string $module): Collection
    {
        return $this->repository->findAllBy('module', $module);
    }

    public function getLogsByAction(string $action): Collection
    {
        return $this->repository->findAllBy('action', $action);
    }

    public function getLogsByDateRange(string $startDate, string $endDate): Collection
    {
        return AuditLog::whereBetween('created_at', [$startDate, $endDate])
            ->with('user')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getRecentLogs(int $limit = 50): Collection
    {
        return AuditLog::with('user')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function paginateLogs(int $perPage = 15): LengthAwarePaginator
    {
        return AuditLog::with('user')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getModules(): Collection
    {
        return AuditLog::select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');
    }

    public function getActions(): Collection
    {
        return AuditLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');
    }

    public function getModuleStats(): Collection
    {
        return AuditLog::selectRaw('module, COUNT(*) as count')
            ->groupBy('module')
            ->orderByDesc('count')
            ->get();
    }

    public function getActionStats(): Collection
    {
        return AuditLog::selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderByDesc('count')
            ->get();
    }

    public function cleanupOldLogs(int $days = 90): int
    {
        return AuditLog::where('created_at', '<', now()->subDays($days))->delete();
    }
}
