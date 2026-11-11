<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-income');
    }

    public function rules(): array
    {
        return [
            'income_category_id' => 'required|exists:income_categories,id',
            'customer_id' => 'nullable|exists:customers,id',
            'branch_id' => 'nullable|exists:branches,id',
            'description' => 'required|string|max:1000',
            'amount' => 'required|numeric|min:0.01',
            'tax_amount' => 'nullable|numeric|min:0',
            'income_date' => 'required|date',
            'reference_number' => 'nullable|string|max:255',
            'receipt_path' => 'nullable|file|max:10240',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'income_category_id.required' => 'សូមជ្រើសរើសប្រភេទចំណូល',
            'income_category_id.exists' => 'ប្រភេទចំណូលនេះមិនមានទេ',
            'description.required' => 'សូមបញ្ចូលការពិពណ៌នា',
            'amount.required' => 'សូមបញ្ចូលចំនួនទឹកប្រាក់',
            'amount.numeric' => 'ចំនួនទឹកប្រាក់ត្រូវតែជាលេខ',
            'amount.min' => 'ចំនួនទឹកប្រាក់មិនអាចតូចជាង 0.01 បានទេ',
            'income_date.required' => 'សូមបញ្ចូលថ្ងៃចំណូល',
            'receipt_path.file' => 'ឯកសារបង្កាន់ដៃត្រូវតែជាឯកសារ',
            'receipt_path.max' => 'ឯកសារបង្កាន់ដៃមិនអាចលើសពី 10MB បានទេ',
        ];
    }
}
