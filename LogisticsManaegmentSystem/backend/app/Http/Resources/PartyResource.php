<?php

namespace App\Http\Resources;

use App\Models\Party;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Party
 */
class PartyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // The discriminator is included so a client can confirm what it
            // created, even though it did not choose the value.
            'type' => $this->type?->value,
            'code' => $this->code,
            'name' => $this->name,
            'contact_name' => $this->contact_name,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'tax_number' => $this->tax_number,
            'address' => [
                'line_1' => $this->address_line_1,
                'line_2' => $this->address_line_2,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country_code' => $this->country_code,
            ],
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
