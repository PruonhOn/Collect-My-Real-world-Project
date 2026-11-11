<?php

namespace App\Repositories;

use App\Models\Driver;
use Illuminate\Support\Collection;

class DriverRepository extends BaseRepository
{
    public function __construct(Driver $driver)
    {
        parent::__construct($driver);
    }

    public function model(): Driver
    {
        return new Driver();
    }

    public function findByCode(string $code): ?Driver
    {
        return $this->findBy('code', $code);
    }

    public function findByPhone(string $phone): ?Driver
    {
        return $this->findBy('phone', $phone);
    }

    public function findByLicenseNumber(string $licenseNumber): ?Driver
    {
        return $this->findBy('license_number', $licenseNumber);
    }

    public function findByStatus(string $status): Collection
    {
        return $this->findAllBy('status', $status);
    }
}
