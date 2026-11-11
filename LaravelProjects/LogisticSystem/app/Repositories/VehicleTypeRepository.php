<?php

namespace App\Repositories;

use App\Models\VehicleType;

class VehicleTypeRepository extends BaseRepository
{
    public function __construct(VehicleType $vehicleType)
    {
        parent::__construct($vehicleType);
    }

    public function model(): VehicleType
    {
        return new VehicleType();
    }

    public function findBySlug(string $slug): ?VehicleType
    {
        return $this->findBy('slug', $slug);
    }
}
