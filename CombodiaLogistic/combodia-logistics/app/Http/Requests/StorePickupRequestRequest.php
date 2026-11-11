<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePickupRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-pickup-requests');
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'nullable|exists:customers,id',
            'shipment_id' => 'nullable|exists:shipments,id',
            'pickup_address' => 'required|string|max:500',
            'pickup_city' => 'required|string|max:255',
            'pickup_latitude' => 'nullable|numeric|between:-90,90',
            'pickup_longitude' => 'nullable|numeric|between:-180,180',
            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:20',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => 'nullable|string|max:10',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'pickup_address.required' => 'សូមបញ្ចូលអាសយដ្ឋានយក',
            'pickup_address.max' => 'អាសយដ្ឋានយកមិនអាចលើសពី 500 អក្សរទេ',
            'pickup_city.required' => 'សូមបញ្ចូលទីក្រុងយក',
            'contact_name.required' => 'សូមបញ្ចូលឈ្មោះទំនាក់ទំនង',
            'contact_phone.required' => 'សូមបញ្ចូលលេខទូរស័ព្ទទំនាក់ទំនង',
            'scheduled_date.required' => 'សូមបញ្ចូលថ្ងៃគ្រោងយក',
            'scheduled_date.after_or_equal' => 'ថ្ងៃគ្រោងយកត្រូវតែជាថ្ងៃនេះ ឬ នាពេលអនាគត',
            'pickup_latitude.between' => 'រយៈទទឹងត្រូវតែស្ថិតនៅចន្លោះ -90 ដល់ 90',
            'pickup_longitude.between' => 'រយៈបណ្ដោយត្រូវតែស្ថិតនៅចន្លោះ -180 ដល់ 180',
        ];
    }
}
