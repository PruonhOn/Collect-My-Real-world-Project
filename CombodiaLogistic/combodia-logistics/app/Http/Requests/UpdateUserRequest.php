<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-users');
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $userId,
            'phone' => 'nullable|string|max:20',
            'username' => 'nullable|string|max:255|unique:users,username,' . $userId,
            'password' => 'nullable|string|min:8|confirmed',
            'gender' => 'nullable|string|in:male,female,other',
            'date_of_birth' => 'nullable|date|before:today',
            'position' => 'nullable|string|max:255',
            'employee_id' => 'nullable|string|max:255|unique:users,employee_id,' . $userId,
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'សូមបញ្ចូលឈ្មោះ',
            'name.string' => 'ឈ្មោះត្រូវតែជាអត្ថបទ',
            'email.required' => 'សូមបញ្ចូលអ៊ីមែល',
            'email.email' => 'សូមបញ្ចូលអ៊ីមែលឱ្យបានត្រឹមត្រូវ',
            'email.unique' => 'អ៊ីមែលនេះមានរួចហើយ',
            'password.min' => 'ពាក្យសម្ងាត់ត្រូវតែមានយ៉ាងហោចណាស់ 8 អក្សរ',
            'password.confirmed' => 'ពាក្យសម្ងាត់មិនត្រូវគ្នា',
        ];
    }
}
