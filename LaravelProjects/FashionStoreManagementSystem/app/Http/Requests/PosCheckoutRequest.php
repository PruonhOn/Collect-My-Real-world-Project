<?php

namespace App\Http\Requests;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PosCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'discount_type' => ['required', Rule::enum(DiscountType::class)],
            'discount_value' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'amount_tendered' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
