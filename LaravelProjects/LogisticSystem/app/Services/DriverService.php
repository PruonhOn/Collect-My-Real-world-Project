<?php

namespace App\Services;

use App\Models\Driver;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class DriverService extends BaseService
{
    public function __construct(BaseRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createDriver(array $data): Driver
    {
        /** @var Driver */
        return $this->repository->create($data);
    }

    public function updateDriver(int $id, array $data): ?Driver
    {
        /** @var Driver|null */
        return $this->repository->update($id, $data);
    }

    public function findByLicenseNumber(string $licenseNumber): ?Driver
    {
        /** @var Driver|null */
        return $this->repository->findBy('license_number', $licenseNumber);
    }

    public function getAvailableDrivers(): Collection
    {
        return Driver::where('status', 'available')
            ->where('is_active', true)
            ->get();
    }

    public function updateStatus(int $id, string $status): ?Driver
    {
        /** @var Driver|null $driver */
        $driver = $this->repository->find($id);

        if (!$driver) {
            return null;
        }

        $driver->update(['status' => $status]);

        return $driver->fresh();
    }

    public function getDriversByBranch(int $branchId): Collection
    {
        return $this->repository->findAllBy('branch_id', $branchId);
    }

    public function toggleActive(int $id): ?Driver
    {
        /** @var Driver|null $driver */
        $driver = $this->repository->find($id);

        if (!$driver) {
            return null;
        }

        $driver->update(['is_active' => !$driver->is_active]);

        return $driver->fresh();
    }
}
