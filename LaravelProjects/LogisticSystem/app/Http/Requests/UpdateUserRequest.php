<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('user');

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'branches' => 'nullable|array',
            'branches.*' => 'exists:branches,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The user name is required.',
            'email.required' => 'The email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already in use.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'avatar.image' => 'The avatar must be an image file.',
            'avatar.max' => 'The avatar must not exceed 2MB.',
            'roles.*.exists' => 'One or more selected roles are invalid.',
            'branches.*.exists' => 'One or more selected branches are invalid.',
        ];
    }
}
