<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentStatus;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ShipmentService extends BaseService
{
    private const VALID_TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['picked_up', 'cancelled'],
        'picked_up' => ['in_transit', 'cancelled'],
        'in_transit' => ['arrived_at_branch', 'delayed', 'cancelled'],
        'arrived_at_branch' => ['out_for_delivery', 'cancelled'],
        'out_for_delivery' => ['delivered', 'failed_attempt', 'cancelled'],
        'delivered' => [],
        'failed_attempt' => ['out_for_delivery', 'cancelled'],
        'delayed' => ['in_transit', 'cancelled'],
        'cancelled' => [],
    ];

    public function __construct(BaseRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getStatuses(): array
    {
        return array_keys(self::VALID_TRANSITIONS);
    }

    public function generateTrackingNumber(): string
    {
        $prefix = 'KHM';
        $timestamp = now()->format('ymd');
        $random = strtoupper(Str::random(6));

        return "{$prefix}-{$timestamp}-{$random}";
    }

    public function createShipment(array $data): Shipment
    {
        if (!isset($data['tracking_number'])) {
            $data['tracking_number'] = $this->generateTrackingNumber();
        }

        $data['status'] = $data['status'] ?? 'pending';

        /** @var Shipment $shipment */
        $shipment = $this->repository->create($data);

        $this->logStatusChange($shipment, $data['status'], 'Shipment created');

        if (isset($data['items']) && is_array($data['items'])) {
            $shipment->shipmentItems()->createMany($data['items']);
        }

        return $shipment->load(['customer', 'originBranch', 'destinationBranch', 'shipmentItems']);
    }

    public function updateShipment(int $id, array $data): ?Shipment
    {
        /** @var Shipment|null $shipment */
        $shipment = $this->repository->find($id);

        if (!$shipment) {
            return null;
        }

        if (isset($data['items']) && is_array($data['items'])) {
            $shipment->shipmentItems()->delete();
            $shipment->shipmentItems()->createMany($data['items']);
            unset($data['items']);
        }

        return $this->repository->update($id, $data)?->load([
            'customer', 'originBranch', 'destinationBranch',
            'shipmentItems', 'shipmentStatuses',
        ]);
    }

    public function updateStatus(int $id, string $newStatus, ?string $description = null, ?int $updatedBy = null): ?Shipment
    {
        /** @var Shipment|null $shipment */
        $shipment = $this->repository->find($id);

        if (!$shipment) {
            return null;
        }

        if (!$this->isValidTransition($shipment->status, $newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from '{$shipment->status}' to '{$newStatus}'."
            );
        }

        $shipment->update(['status' => $newStatus]);

        $this->logStatusChange($shipment, $newStatus, $description, $updatedBy);

        if ($newStatus === 'delivered') {
            $shipment->update(['actual_delivery' => now()]);
        }

        return $shipment->fresh()->load('shipmentStatuses');
    }

    public function getShipmentByTracking(string $trackingNumber): ?Shipment
    {
        /** @var Shipment|null */
        return $this->repository->findBy('tracking_number', $trackingNumber);
    }

    public function getShipmentsByCustomer(int $customerId): Collection
    {
        return $this->repository->findAllBy('customer_id', $customerId);
    }

    public function getShipmentsByStatus(string $status): Collection
    {
        return $this->repository->findAllBy('status', $status);
    }

    public function assignDriver(int $shipmentId, int $driverId): ?Shipment
    {
        /** @var Shipment|null $shipment */
        $shipment = $this->repository->find($shipmentId);

        if (!$shipment) {
            return null;
        }

        $shipment->update(['assigned_driver_id' => $driverId]);

        return $shipment->fresh()->load('assignedDriver');
    }

    public function assignVehicle(int $shipmentId, int $vehicleId): ?Shipment
    {
        /** @var Shipment|null $shipment */
        $shipment = $this->repository->find($shipmentId);

        if (!$shipment) {
            return null;
        }

        $shipment->update(['assigned_vehicle_id' => $vehicleId]);

        return $shipment->fresh()->load('assignedVehicle');
    }

    public function getStatusHistory(int $id): Collection
    {
        /** @var Shipment|null $shipment */
        $shipment = $this->repository->find($id);

        if (!$shipment) {
            return collect();
        }

        return $shipment->shipmentStatuses()->with('updatedBy')->orderByDesc('created_at')->get();
    }

    public function searchShipments(?array $filters = null): Collection
    {
        $query = Shipment::query();

        if (isset($filters['tracking_number'])) {
            $query->where('tracking_number', 'like', "%{$filters['tracking_number']}%");
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['origin_branch_id'])) {
            $query->where('origin_branch_id', $filters['origin_branch_id']);
        }

        if (isset($filters['destination_branch_id'])) {
            $query->where('destination_branch_id', $filters['destination_branch_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('shipment_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('shipment_date', '<=', $filters['date_to']);
        }

        if (isset($filters['is_express'])) {
            $query->where('is_express', $filters['is_express']);
        }

        return $query->with(['customer', 'originBranch', 'destinationBranch'])
            ->orderByDesc('created_at')
            ->get();
    }

    private function isValidTransition(string $currentStatus, string $newStatus): bool
    {
        if (!isset(self::VALID_TRANSITIONS[$currentStatus])) {
            return false;
        }

        return in_array($newStatus, self::VALID_TRANSITIONS[$currentStatus], true);
    }

    private function logStatusChange(Shipment $shipment, string $status, ?string $description = null, ?int $updatedBy = null): void
    {
        $shipment->shipmentStatuses()->create([
            'status' => $status,
            'description' => $description,
            'updated_by' => $updatedBy,
        ]);
    }

    public static function getValidTransitions(): array
    {
        return self::VALID_TRANSITIONS;
    }
}
