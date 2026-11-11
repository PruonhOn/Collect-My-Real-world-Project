<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-customers');
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|exists:users,id',
            'code' => 'required|string|max:50|unique:customers,code',
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'tax_number' => 'nullable|string|max:50',
            'credit_limit' => 'nullable|numeric|min:0',
            'balance' => 'nullable|numeric',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'សូមបញ្ចូលកូដអតិថិជន',
            'code.unique' => 'កូដអតិថិជននេះមានរួចហើយ',
            'name.required' => 'សូមបញ្ចូលឈ្មោះអតិថិជន',
            'phone.required' => 'សូមបញ្ចូលលេខទូរស័ព្ទ',
            'email.email' => 'សូមបញ្ចូលអ៊ីមែលឱ្យបានត្រឹមត្រូវ',
            'credit_limit.numeric' => 'ឥណទានកំរិតត្រូវតែជាលេខ',
            'credit_limit.min' => 'ឥណទានកំរិតមិនអាចតូចជាង 0 បានទេ',
        ];
    }
}
