<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipment_type' => 'required|string|max:50',
            'status' => 'required|string|max:50',
            'origin_address' => 'nullable|string|max:500',
            'origin_city' => 'nullable|string|max:100',
            'origin_province' => 'nullable|string|max:100',
            'destination_address' => 'nullable|string|max:500',
            'destination_city' => 'nullable|string|max:100',
            'destination_province' => 'nullable|string|max:100',
            'shipper_name' => 'nullable|string|max:255',
            'shipper_phone' => 'nullable|string|max:20',
            'shipper_email' => 'nullable|email|max:255',
            'consignee_name' => 'required|string|max:255',
            'consignee_phone' => 'required|string|max:20',
            'consignee_email' => 'nullable|email|max:255',
            'weight' => 'nullable|numeric|min:0',
            'volume' => 'nullable|numeric|min:0',
            'pieces' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:1000',
            'declared_value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'shipment_date' => 'nullable|date',
            'estimated_delivery' => 'nullable|date',
            'is_express' => 'boolean',
            'is_insured' => 'boolean',
            'insurance_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'customer_id' => 'nullable|exists:customers,id',
            'origin_branch_id' => 'nullable|exists:branches,id',
            'destination_branch_id' => 'nullable|exists:branches,id',
            'assigned_driver_id' => 'nullable|exists:drivers,id',
            'assigned_vehicle_id' => 'nullable|exists:vehicles,id',
            'items' => 'nullable|array',
            'items.*.name' => 'required_with:items|string|max:255',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.volume' => 'nullable|numeric|min:0',
            'items.*.value' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'shipment_type.required' => 'The shipment type is required.',
            'consignee_name.required' => 'The consignee name is required.',
            'consignee_phone.required' => 'The consignee phone number is required.',
            'shipper_email.email' => 'Please provide a valid shipper email address.',
            'consignee_email.email' => 'Please provide a valid consignee email address.',
            'items.*.name.required_with' => 'Each item must have a name.',
            'items.*.quantity.required_with' => 'Each item must have a quantity.',
            'customer_id.exists' => 'The selected customer is invalid.',
            'origin_branch_id.exists' => 'The selected origin branch is invalid.',
            'destination_branch_id.exists' => 'The selected destination branch is invalid.',
            'assigned_driver_id.exists' => 'The selected driver is invalid.',
            'assigned_vehicle_id.exists' => 'The selected vehicle is invalid.',
        ];
    }
}
