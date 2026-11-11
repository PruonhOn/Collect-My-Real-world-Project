<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:150'],
            'store_tagline' => ['nullable', 'string', 'max:255'],
            'store_address' => ['nullable', 'string', 'max:1000'],
            'store_phone' => ['nullable', 'string', 'max:30'],
            'store_email' => ['nullable', 'email', 'max:150'],
            'currency' => ['required', 'string', 'max:10'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'receipt_footer' => ['nullable', 'string', 'max:500'],
        ];
    }
}
