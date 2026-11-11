<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class VehicleService extends BaseService
{
    public function __construct(BaseRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createVehicle(array $data): Vehicle
    {
        /** @var Vehicle */
        return $this->repository->create($data);
    }

    public function updateVehicle(int $id, array $data): ?Vehicle
    {
        /** @var Vehicle|null */
        return $this->repository->update($id, $data);
    }

    public function findByPlateNumber(string $plateNumber): ?Vehicle
    {
        /** @var Vehicle|null */
        return $this->repository->findBy('plate_number', $plateNumber);
    }

    public function getAvailableVehicles(): Collection
    {
        return Vehicle::where('status', 'available')
            ->where('is_active', true)
            ->get();
    }

    public function getActiveVehicles(): Collection
    {
        return $this->repository->findAllBy('is_active', true);
    }

    public function getVehiclesByBranch(int $branchId): Collection
    {
        return $this->repository->findAllBy('branch_id', $branchId);
    }

    public function updateStatus(int $id, string $status): ?Vehicle
    {
        /** @var Vehicle|null $vehicle */
        $vehicle = $this->repository->find($id);

        if (!$vehicle) {
            return null;
        }

        $vehicle->update(['status' => $status]);

        return $vehicle->fresh();
    }

    public function getVehiclesByType(int $vehicleTypeId): Collection
    {
        return $this->repository->findAllBy('vehicle_type_id', $vehicleTypeId);
    }

    public function toggleActive(int $id): ?Vehicle
    {
        /** @var Vehicle|null $vehicle */
        $vehicle = $this->repository->find($id);

        if (!$vehicle) {
            return null;
        }

        $vehicle->update(['is_active' => !$vehicle->is_active]);

        return $vehicle->fresh();
    }
}
