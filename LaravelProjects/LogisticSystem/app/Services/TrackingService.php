<?php

namespace App\Services;

use App\Models\Shipment;

class TrackingService
{
    public function track(string $trackingNumber): ?Shipment
    {
        return Shipment::with([
            'customer',
            'originBranch',
            'destinationBranch',
            'shipmentStatuses' => function ($query) {
                $query->latest();
            },
            'shipmentItems',
        ])->where('tracking_number', $trackingNumber)->first();
    }

    public function getTrackingDetails(string $trackingNumber): array
    {
        $shipment = $this->track($trackingNumber);

        if (!$shipment) {
            return [];
        }

        return [
            'tracking_number' => $shipment->tracking_number,
            'status' => $shipment->status,
            'shipper' => [
                'name' => $shipment->shipper_name,
                'phone' => $shipment->shipper_phone,
                'email' => $shipment->shipper_email,
            ],
            'consignee' => [
                'name' => $shipment->consignee_name,
                'phone' => $shipment->consignee_phone,
                'email' => $shipment->consignee_email,
            ],
            'origin' => [
                'address' => $shipment->origin_address,
                'city' => $shipment->origin_city,
                'province' => $shipment->origin_province,
                'branch' => $shipment->originBranch?->name,
            ],
            'destination' => [
                'address' => $shipment->destination_address,
                'city' => $shipment->destination_city,
                'province' => $shipment->destination_province,
                'branch' => $shipment->destinationBranch?->name,
            ],
            'shipment' => [
                'type' => $shipment->shipment_type,
                'weight' => $shipment->weight,
                'volume' => $shipment->volume,
                'pieces' => $shipment->pieces,
                'description' => $shipment->description,
                'is_express' => $shipment->is_express,
                'is_insured' => $shipment->is_insured,
            ],
            'dates' => [
                'shipment_date' => $shipment->shipment_date,
                'estimated_delivery' => $shipment->estimated_delivery,
                'actual_delivery' => $shipment->actual_delivery,
            ],
            'status_history' => $shipment->shipmentStatuses->map(function ($status) {
                return [
                    'status' => $status->status,
                    'description' => $status->description,
                    'location' => $status->location,
                    'updated_at' => $status->updated_at,
                ];
            }),
        ];
    }
}
