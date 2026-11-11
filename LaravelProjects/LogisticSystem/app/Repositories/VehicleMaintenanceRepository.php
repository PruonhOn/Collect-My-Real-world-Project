<?php

namespace App\Repositories;

use App\Models\VehicleMaintenance;
use Illuminate\Support\Collection;

class VehicleMaintenanceRepository extends BaseRepository
{
    public function __construct(VehicleMaintenance $vehicleMaintenance)
    {
        parent::__construct($vehicleMaintenance);
    }

    public function model(): VehicleMaintenance
    {
        return new VehicleMaintenance();
    }

    public function findByReferenceNo(string $referenceNo): ?VehicleMaintenance
    {
        return $this->findBy('reference_no', $referenceNo);
    }

    public function findByVehicle(int $vehicleId): Collection
    {
        return $this->findAllBy('vehicle_id', $vehicleId);
    }

    public function findByStatus(string $status): Collection
    {
        return $this->findAllBy('status', $status);
    }
}
