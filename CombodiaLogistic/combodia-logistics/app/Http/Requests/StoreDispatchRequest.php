<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-dispatches');
    }

    public function rules(): array
    {
        return [
            'shipment_id' => 'required|exists:shipments,id',
            'route_id' => 'nullable|exists:routes,id',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => 'nullable|string|max:10',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'shipment_id.required' => 'សូមជ្រើសរើសការដឹកជញ្ជូន',
            'shipment_id.exists' => 'ការដឹកជញ្ជូននេះមិនមានទេ',
            'scheduled_date.required' => 'សូមបញ្ចូលថ្ងៃគ្រោងផ្ទេរ',
            'scheduled_date.after_or_equal' => 'ថ្ងៃគ្រោងផ្ទេរត្រូវតែជាថ្ងៃនេះ ឬ នាពេលអនាគត',
        ];
    }
}
