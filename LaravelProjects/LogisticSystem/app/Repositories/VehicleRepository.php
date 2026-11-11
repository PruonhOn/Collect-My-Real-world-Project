<?php

namespace App\Repositories;

use App\Models\Vehicle;
use Illuminate\Support\Collection;

class VehicleRepository extends BaseRepository
{
    public function __construct(Vehicle $vehicle)
    {
        parent::__construct($vehicle);
    }

    public function model(): Vehicle
    {
        return new Vehicle();
    }

    public function findByCode(string $code): ?Vehicle
    {
        return $this->findBy('code', $code);
    }

    public function findByPlateNumber(string $plateNumber): ?Vehicle
    {
        return $this->findBy('plate_number', $plateNumber);
    }

    public function findByStatus(string $status): Collection
    {
        return $this->findAllBy('status', $status);
    }

    public function findByBranch(int $branchId): Collection
    {
        return $this->findAllBy('branch_id', $branchId);
    }
}
