<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_date' => 'required|date',
            'type' => 'required|string|max:50',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'payment_method' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'reference' => 'nullable|string|max:255',
            'status' => 'required|string|max:50',
            'branch_id' => 'nullable|exists:branches,id',
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_date.required' => 'The transaction date is required.',
            'type.required' => 'The transaction type is required.',
            'category.required' => 'The transaction category is required.',
            'amount.required' => 'The transaction amount is required.',
            'amount.min' => 'The amount must be a positive value.',
            'status.required' => 'The transaction status is required.',
            'branch_id.exists' => 'The selected branch is invalid.',
        ];
    }
}
