<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('staff')?->id ?? $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)],
            'role' => ['required', Rule::enum(Role::class)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
