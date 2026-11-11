<?php

namespace App\Http\Requests;

use App\Enums\InventoryTransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'type' => ['required', Rule::enum(InventoryTransactionType::class)],
            'quantity' => ['required', 'integer', 'not_in:0', 'min:-999999', 'max:999999'],
            'reason' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
