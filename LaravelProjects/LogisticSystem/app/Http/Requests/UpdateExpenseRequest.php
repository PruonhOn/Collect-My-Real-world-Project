<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_date' => 'required|date',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:1000',
            'vendor' => 'nullable|string|max:255',
            'receipt_path' => 'nullable|image|max:5120',
            'is_recurring' => 'boolean',
            'status' => 'required|string|max:50',
            'branch_id' => 'nullable|exists:branches,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'expense_date.required' => 'The expense date is required.',
            'category.required' => 'The expense category is required.',
            'amount.required' => 'The expense amount is required.',
            'amount.min' => 'The amount must be a positive value.',
            'status.required' => 'The expense status is required.',
            'receipt_path.image' => 'The receipt must be an image file.',
            'receipt_path.max' => 'The receipt must not exceed 5MB.',
            'branch_id.exists' => 'The selected branch is invalid.',
            'vehicle_id.exists' => 'The selected vehicle is invalid.',
        ];
    }
}
