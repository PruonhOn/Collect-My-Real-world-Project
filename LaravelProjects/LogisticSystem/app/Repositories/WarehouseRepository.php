<?php

namespace App\Repositories;

use App\Models\Warehouse;
use Illuminate\Support\Collection;

class WarehouseRepository extends BaseRepository
{
    public function __construct(Warehouse $warehouse)
    {
        parent::__construct($warehouse);
    }

    public function model(): Warehouse
    {
        return new Warehouse();
    }

    public function findByCode(string $code): ?Warehouse
    {
        return $this->findBy('code', $code);
    }

    public function findByBranch(int $branchId): Collection
    {
        return $this->findAllBy('branch_id', $branchId);
    }
}
