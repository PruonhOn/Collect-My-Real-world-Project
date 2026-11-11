<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\DeliveryStop;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class DeliveryService extends BaseService
{
    public function __construct(BaseRepository $repository)
    {
        parent::__construct($repository);
    }

    public function assignDriver(int $id, int $driverId, int $userId): ?Delivery
    {
        /** @var Delivery|null $delivery */
        $delivery = $this->repository->find($id);
        if (!$delivery) {
            return null;
        }
        $delivery->update([
            'driver_id' => $driverId,
            'status' => 'assigned',
        ]);
        return $delivery->fresh()->load(['driver', 'vehicle', 'route', 'shipment']);
    }

    public function markDelivered(int $id, array $data): ?Delivery
    {
        /** @var Delivery|null $delivery */
        $delivery = $this->repository->find($id);
        if (!$delivery) {
            return null;
        }
        $updateData = [
            'status' => 'delivered',
            'delivered_at' => now(),
        ];
        if (isset($data['signature'])) {
            $updateData['signature'] = $data['signature'];
        }
        if (isset($data['notes'])) {
            $updateData['delivery_notes'] = $data['notes'];
        }
        $delivery->update($updateData);
        if ($delivery->shipment) {
            $delivery->shipment->update(['status' => 'delivered', 'actual_delivery' => now()]);
        }
        return $delivery->fresh()->load(['driver', 'vehicle', 'route', 'shipment']);
    }

    public function uploadProof(int $id, array $data): ?Delivery
    {
        /** @var Delivery|null $delivery */
        $delivery = $this->repository->find($id);
        if (!$delivery) {
            return null;
        }
        $delivery->update($data);
        return $delivery->fresh();
    }

    public function assignDelivery(array $data): Delivery
    {
        $data['status'] = 'assigned';

        /** @var Delivery $delivery */
        $delivery = $this->repository->create($data);

        if (isset($data['shipment_id'])) {
            $shipment = $delivery->shipment;
            if ($shipment) {
                $shipment->update([
                    'assigned_driver_id' => $data['driver_id'] ?? null,
                    'assigned_vehicle_id' => $data['vehicle_id'] ?? null,
                ]);
            }
        }

        return $delivery->load(['driver', 'vehicle', 'route', 'shipment']);
    }

    public function updateStatus(int $id, string $status, ?array $extra = null): ?Delivery
    {
        /** @var Delivery|null $delivery */
        $delivery = $this->repository->find($id);

        if (!$delivery) {
            return null;
        }

        $updateData = ['status' => $status];

        if ($status === 'in_transit' && !$delivery->departed_at) {
            $updateData['departed_at'] = now();
        }

        if ($status === 'delivered') {
            $updateData['delivered_at'] = now();
        }

        if ($extra) {
            $updateData = array_merge($updateData, $extra);
        }

        $delivery->update($updateData);

        if ($status === 'delivered' && $delivery->shipment) {
            $delivery->shipment->update(['status' => 'delivered', 'actual_delivery' => now()]);
        }

        return $delivery->fresh()->load(['driver', 'vehicle', 'route', 'shipment', 'deliveryStops']);
    }

    public function getDeliveriesByDriver(int $driverId): Collection
    {
        return $this->repository->findAllBy('driver_id', $driverId);
    }

    public function getDeliveriesByShipment(int $shipmentId): Collection
    {
        return $this->repository->findAllBy('shipment_id', $shipmentId);
    }

    public function getDeliveriesByStatus(string $status): Collection
    {
        return $this->repository->findAllBy('status', $status);
    }

    public function getPendingDeliveries(): Collection
    {
        return Delivery::whereIn('status', ['assigned', 'in_transit'])
            ->with(['driver', 'vehicle', 'shipment'])
            ->orderBy('scheduled_date')
            ->get();
    }

    public function addDeliveryStop(int $deliveryId, array $data): ?DeliveryStop
    {
        /** @var Delivery|null $delivery */
        $delivery = $this->repository->find($deliveryId);

        if (!$delivery) {
            return null;
        }

        $data['delivery_id'] = $deliveryId;

        if (!isset($data['sequence_order'])) {
            $maxOrder = $delivery->deliveryStops()->max('sequence_order');
            $data['sequence_order'] = ($maxOrder ?? 0) + 1;
        }

        return $delivery->deliveryStops()->create($data);
    }

    public function updateDeliveryStop(int $stopId, array $data): ?DeliveryStop
    {
        $stop = DeliveryStop::find($stopId);

        if (!$stop) {
            return null;
        }

        $stop->update($data);

        return $stop->fresh();
    }

    public function getStopsForDelivery(int $deliveryId): Collection
    {
        return DeliveryStop::where('delivery_id', $deliveryId)
            ->orderBy('sequence_order')
            ->get();
    }
}
