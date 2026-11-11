<?php

namespace App\Http\Requests\V1\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string', 'max:120'],
            'settings.*.value' => ['nullable'],
            'settings.*.group' => ['nullable', 'string', 'max:60'],
            'settings.*.is_public' => ['nullable', 'boolean'],
        ];
    }
}
