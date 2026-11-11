<?php

namespace App\Http\Resources;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Driver
 */
class DriverResource extends JsonResource
{
    /**
     * Expiry dates are emitted as plain Y-m-d. A full ISO-8601 timestamp on a
     * date-only column is noise, and it invites a caller to compare it against
     * a clock it should not be considering.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'license_number' => $this->license_number,
            'phone' => $this->phone,
            'email' => $this->email,
            'nationality' => $this->nationality,
            'carrier' => $this->whenLoaded('carrier', fn () => $this->carrier === null ? null : [
                'id' => $this->carrier->id,
                'name' => $this->carrier->name,
                'code' => $this->carrier->code,
            ]),
            'truck' => $this->whenLoaded('truck', fn () => $this->truck === null ? null : [
                'id' => $this->truck->id,
                'plate_number' => $this->truck->plate_number,
            ]),
            'license_expiry' => $this->license_expiry?->toDateString(),
            'medical_expiry' => $this->medical_expiry?->toDateString(),
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
