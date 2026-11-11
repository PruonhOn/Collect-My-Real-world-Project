<?php

namespace App\Repositories;

use App\Models\AuditLog;
use Illuminate\Support\Collection;

class AuditLogRepository extends BaseRepository
{
    public function __construct(AuditLog $auditLog)
    {
        parent::__construct($auditLog);
    }

    public function model(): AuditLog
    {
        return new AuditLog();
    }

    public function findByModule(string $module): Collection
    {
        return $this->findAllBy('module', $module);
    }

    public function findByAction(string $action): Collection
    {
        return $this->findAllBy('action', $action);
    }

    public function findByUser(int $userId): Collection
    {
        return $this->findAllBy('user_id', $userId);
    }
}
