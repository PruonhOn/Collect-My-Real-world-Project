<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-users');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'username' => 'nullable|string|max:255|unique:users,username',
            'password' => 'required|string|min:8|confirmed',
            'gender' => 'nullable|string|in:male,female,other',
            'date_of_birth' => 'nullable|date|before:today',
            'position' => 'nullable|string|max:255',
            'employee_id' => 'nullable|string|max:255|unique:users,employee_id',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'សូមបញ្ចូលឈ្មោះ',
            'name.string' => 'ឈ្មោះត្រូវតែជាអត្ថបទ',
            'name.max' => 'ឈ្មោះមិនអាចលើសពី 255 អក្សរទេ',
            'email.required' => 'សូមបញ្ចូលអ៊ីមែល',
            'email.email' => 'សូមបញ្ចូលអ៊ីមែលឱ្យបានត្រឹមត្រូវ',
            'email.unique' => 'អ៊ីមែលនេះមានរួចហើយ',
            'phone.max' => 'លេខទូរស័ព្ទមិនអាចលើសពី 20 ខ្ទង់ទេ',
            'username.unique' => 'ឈ្មោះអ្នកប្រើប្រាស់នេះមានរួចហើយ',
            'password.required' => 'សូមបញ្ចូលពាក្យសម្ងាត់',
            'password.min' => 'ពាក្យសម្ងាត់ត្រូវតែមានយ៉ាងហោចណាស់ 8 អក្សរ',
            'password.confirmed' => 'ពាក្យសម្ងាត់មិនត្រូវគ្នា',
            'gender.in' => 'ភេទត្រូវតែជា ប្រុស ស្រី ឬ ផ្សេងទៀត',
            'date_of_birth.before' => 'ថ្ងៃកំណើតត្រូវតែមុនថ្ងៃនេះ',
            'is_active.boolean' => 'ស្ថានភាពត្រូវតែជា 1 ឬ 0',
        ];
    }
}
