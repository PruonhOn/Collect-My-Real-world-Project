<?php

namespace App\Services;

use App\Models\VehicleMaintenance;

class VehicleMaintenanceService
{
    public function getAll(array $filters = [])
    {
        $query = VehicleMaintenance::with(['vehicle', 'createdBy']);

        if (!empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['maintenance_type'])) {
            $query->where('maintenance_type', $filters['maintenance_type']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('reference_no', 'like', "%{$filters['search']}%")
                    ->orWhere('vendor', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        return $query->latest()->paginate(15);
    }

    public function getById(int $id): VehicleMaintenance
    {
        return VehicleMaintenance::with(['vehicle', 'createdBy'])->findOrFail($id);
    }

    public function create(array $data): VehicleMaintenance
    {
        return VehicleMaintenance::create($data);
    }

    public function update(int $id, array $data): VehicleMaintenance
    {
        $maintenance = $this->getById($id);
        $maintenance->update($data);
        return $maintenance;
    }

    public function delete(int $id): bool
    {
        return $this->getById($id)->delete();
    }
}
