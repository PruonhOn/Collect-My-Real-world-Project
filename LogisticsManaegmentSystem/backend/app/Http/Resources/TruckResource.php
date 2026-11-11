<?php

namespace App\Http\Resources;

use App\Models\Truck;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Truck
 */
class TruckResource extends JsonResource
{
    /**
     * `carrier` is reduced to an id and name rather than a nested object. The
     * listing eager loads it, but a nested full resource would drag the
     * carrier's trucks - and their drivers - into every row of a paged list.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plate_number' => $this->plate_number,
            'code' => $this->code,
            'carrier' => $this->whenLoaded('carrier', fn () => $this->carrier === null ? null : [
                'id' => $this->carrier->id,
                'name' => $this->carrier->name,
                'code' => $this->carrier->code,
            ]),
            'truck_type' => $this->truck_type,
            'capacity_kg' => $this->capacity_kg,
            'capacity_pallets' => $this->capacity_pallets,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
