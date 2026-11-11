<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-suppliers');
    }

    public function rules(): array
    {
        $supplierId = $this->route('supplier')?->id;

        return [
            'code' => 'required|string|max:50|unique:suppliers,code,' . $supplierId,
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'សូមបញ្ចូលកូដអ្នកផ្គត់ផ្គង់',
            'name.required' => 'សូមបញ្ចូលឈ្មោះអ្នកផ្គត់ផ្គង់',
            'phone.required' => 'សូមបញ្ចូលលេខទូរស័ព្ទ',
        ];
    }
}
