<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('customer')?->id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('customers', 'phone')->ignore($customerId)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('customers', 'email')->ignore($customerId)],
            'address' => ['nullable', 'string', 'max:1000'],
            'loyalty_points' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
