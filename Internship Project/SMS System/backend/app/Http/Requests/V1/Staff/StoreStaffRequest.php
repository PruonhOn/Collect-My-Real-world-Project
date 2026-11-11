<?php

namespace App\Http\Requests\V1\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('staff.manage') ?? false;
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
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('staff', 'employee_code')],
            'position' => ['nullable', 'string', 'max:191'],
            'department' => ['nullable', 'string', 'max:191'],
            'hire_date' => ['nullable', 'date'],
        ];
    }
}
