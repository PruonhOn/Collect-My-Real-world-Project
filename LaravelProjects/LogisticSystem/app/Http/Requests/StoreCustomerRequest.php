<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:customers,code',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'company' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'credit_limit' => 'nullable|numeric|min:0',
            'outstanding_balance' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'The customer code is required.',
            'code.unique' => 'This customer code is already in use.',
            'name.required' => 'The customer name is required.',
            'email.email' => 'Please provide a valid email address.',
            'credit_limit.min' => 'Credit limit must be a positive value.',
        ];
    }
}
