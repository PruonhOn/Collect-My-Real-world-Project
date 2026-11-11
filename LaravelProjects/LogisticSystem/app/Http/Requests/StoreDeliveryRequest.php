<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scheduled_date' => 'required|date',
            'delivery_notes' => 'nullable|string|max:1000',
            'shipment_id' => 'required|exists:shipments,id',
            'route_id' => 'nullable|exists:routes,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_date.required' => 'The scheduled delivery date is required.',
            'shipment_id.required' => 'The shipment is required.',
            'shipment_id.exists' => 'The selected shipment is invalid.',
            'route_id.exists' => 'The selected route is invalid.',
            'driver_id.exists' => 'The selected driver is invalid.',
            'vehicle_id.exists' => 'The selected vehicle is invalid.',
        ];
    }
}
