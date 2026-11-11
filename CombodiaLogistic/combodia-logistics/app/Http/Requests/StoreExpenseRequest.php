<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-expenses');
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
            'expense_category_id.exists' => 'ប្រភេទចំណាយនេះមិនមានទេ',
            'branch_id.required' => 'សូមជ្រើសរើសសាខា',
            'branch_id.exists' => 'សាខានេះមិនមានទេ',
            'description.required' => 'សូមបញ្ចូលការពិពណ៌នា',
            'description.max' => 'ការពិពណ៌នាមិនអាចលើសពី 1000 អក្សរទេ',
            'amount.required' => 'សូមបញ្ចូលចំនួនទឹកប្រាក់',
            'amount.numeric' => 'ចំនួនទឹកប្រាក់ត្រូវតែជាលេខ',
            'amount.min' => 'ចំនួនទឹកប្រាក់មិនអាចតូចជាង 0.01 បានទេ',
            'expense_date.required' => 'សូមបញ្ចូលថ្ងៃចំណាយ',
            'receipt_path.file' => 'ឯកសារបង្កាន់ដៃត្រូវតែជាឯកសារ',
            'receipt_path.max' => 'ឯកសារបង្កាន់ដៃមិនអាចលើសពី 10MB បានទេ',
        ];
    }
}
