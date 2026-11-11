<?php

namespace App\Http\Resources;

use App\Models\Vessel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Vessel
 */
class VesselResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'imo_number' => $this->imo_number,
            'mmsi' => $this->mmsi,
            'flag_country' => $this->flag_country,
            'vessel_type' => $this->vessel_type,
            'length_meters' => $this->length_meters,
            'beam_meters' => $this->beam_meters,
            'capacity_teu' => $this->capacity_teu,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
