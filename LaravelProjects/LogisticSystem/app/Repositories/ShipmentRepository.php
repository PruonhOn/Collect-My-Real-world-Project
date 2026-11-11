<?php

namespace App\Repositories;

use App\Models\Shipment;
use Illuminate\Support\Collection;

class ShipmentRepository extends BaseRepository
{
    public function __construct(Shipment $shipment)
    {
        parent::__construct($shipment);
    }

    public function model(): Shipment
    {
        return new Shipment();
    }

    public function findByTrackingNumber(string $trackingNumber): ?Shipment
    {
        return $this->findBy('tracking_number', $trackingNumber);
    }

    public function findByStatus(string $status): Collection
    {
        return $this->findAllBy('status', $status);
    }

    public function findByCustomer(int $customerId): Collection
    {
        return $this->findAllBy('customer_id', $customerId);
    }

    public function findByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model
            ->whereBetween('shipment_date', [$startDate, $endDate])
            ->get();
    }
}
