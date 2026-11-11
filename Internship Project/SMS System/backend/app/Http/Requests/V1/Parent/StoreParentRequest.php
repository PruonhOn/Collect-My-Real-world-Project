<?php

namespace App\Http\Requests\V1\Parent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('parents.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
            'occupation' => ['nullable', 'string', 'max:191'],
            'students' => ['nullable', 'array'],
            'students.*' => ['integer', Rule::exists('students', 'id')],
        ];
    }
}
