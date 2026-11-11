<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit-expenses');
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => 'required|exists:expense_categories,id',
            'branch_id' => 'required|exists:branches,id',
            'description' => 'required|string|max:1000',
            'amount' => 'required|numeric|min:0.01',
            'tax_amount' => 'nullable|numeric|min:0',
            'expense_date' => 'required|date',
            'payment_method' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'receipt_path' => 'nullable|file|max:10240',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'expense_category_id.required' => 'សូមជ្រើសរើសប្រភេទចំណាយ',
            'branch_id.required' => 'សូមជ្រើសរើសសាខា',
            'description.required' => 'សូមបញ្ចូលការពិពណ៌នា',
            'amount.required' => 'សូមបញ្ចូលចំនួនទឹកប្រាក់',
            'expense_date.required' => 'សូមបញ្ចូលថ្ងៃចំណាយ',
        ];
    }
}
