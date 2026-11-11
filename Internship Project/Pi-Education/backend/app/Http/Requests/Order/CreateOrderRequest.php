<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_ids' => ['required', 'array', 'min:1', 'max:50'],
            'book_ids.*' => ['integer', Rule::exists('books', 'id')],
        ];
    }
}
