<?php

namespace App\Repositories;

use App\Models\Delivery;
use Illuminate\Support\Collection;

class DeliveryRepository extends BaseRepository
{
    public function __construct(Delivery $delivery)
    {
        parent::__construct($delivery);
    }

    public function model(): Delivery
    {
        return new Delivery();
    }

    public function findByDeliveryNumber(string $deliveryNumber): ?Delivery
    {
        return $this->findBy('delivery_number', $deliveryNumber);
    }

    public function findByStatus(string $status): Collection
    {
        return $this->findAllBy('status', $status);
    }

    public function findByShipment(int $shipmentId): Collection
    {
        return $this->findAllBy('shipment_id', $shipmentId);
    }

    public function findByDriver(int $driverId): Collection
    {
        return $this->findAllBy('driver_id', $driverId);
    }
}
