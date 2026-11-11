<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-shipments');
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'nullable|exists:customers,id',
            'branch_id' => 'required|exists:branches,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'type' => 'required|string|in:standard,express,economy,freight',
            'priority' => 'required|string|in:low,normal,high,urgent',
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_address' => 'required|string|max:500',
            'sender_city' => 'required|string|max:255',
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:20',
            'recipient_address' => 'required|string|max:500',
            'recipient_city' => 'required|string|max:255',
            'total_weight' => 'required|numeric|min:0',
            'total_volume' => 'nullable|numeric|min:0',
            'total_packages' => 'required|integer|min:1',
            'total_freight_charge' => 'required|numeric|min:0',
            'total_insurance' => 'nullable|numeric|min:0',
            'total_tax' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'pickup_date' => 'nullable|date|after_or_equal:today',
            'pickup_time' => 'nullable|string|max:10',
            'delivery_date' => 'nullable|date|after_or_equal:pickup_date',
            'delivery_time' => 'nullable|string|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'សូមជ្រើសរើសសាខា',
            'branch_id.exists' => 'សាខានេះមិនមានទេ',
            'type.required' => 'សូមជ្រើសរើសប្រភេទការដឹកជញ្ជូន',
            'priority.required' => 'សូមជ្រើសរើសអាទិភាព',
            'sender_name.required' => 'សូមបញ្ចូលឈ្មោះអ្នកផ្ញើ',
            'sender_phone.required' => 'សូមបញ្ចូលលេខទូរស័ព្ទអ្នកផ្ញើ',
            'sender_address.required' => 'សូមបញ្ចូលអាសយដ្ឋានអ្នកផ្ញើ',
            'sender_city.required' => 'សូមបញ្ចូលទីក្រុងអ្នកផ្ញើ',
            'recipient_name.required' => 'សូមបញ្ចូលឈ្មោះអ្នកទទួល',
            'recipient_phone.required' => 'សូមបញ្ចូលលេខទូរស័ព្ទអ្នកទទួល',
            'recipient_address.required' => 'សូមបញ្ចូលអាសយដ្ឋានអ្នកទទួល',
            'recipient_city.required' => 'សូមបញ្ចូលទីក្រុងអ្នកទទួល',
            'total_weight.required' => 'សូមបញ្ចូលទម្ងន់សរុប',
            'total_weight.numeric' => 'ទម្ងន់សរុបត្រូវតែជាលេខ',
            'total_weight.min' => 'ទម្ងន់សរុបមិនអាចតូចជាង 0 បានទេ',
            'total_packages.required' => 'សូមបញ្ចូលចំនួនកញ្ចប់',
            'total_packages.integer' => 'ចំនួនកញ្ចប់ត្រូវតែជាលេខគត់',
            'total_packages.min' => 'ចំនួនកញ្ចប់មិនអាចតូចជាង 1 បានទេ',
            'total_freight_charge.required' => 'សូមបញ្ចូលថ្លៃដឹកជញ្ជូន',
            'total_freight_charge.numeric' => 'ថ្លៃដឹកជញ្ជូនត្រូវតែជាលេខ',
            'grand_total.required' => 'សូមបញ្ចូលចំនួនសរុប',
            'grand_total.numeric' => 'ចំនួនសរុបត្រូវតែជាលេខ',
            'pickup_date.after_or_equal' => 'ថ្ងៃយកត្រូវតែជាថ្ងៃនេះ ឬ នាពេលអនាគត',
            'delivery_date.after_or_equal' => 'ថ្ងៃដឹកត្រូវតែនាពេលអនាគត',
        ];
    }
}
