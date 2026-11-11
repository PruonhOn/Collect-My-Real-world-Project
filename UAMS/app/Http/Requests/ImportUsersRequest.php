<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'A file is required for import.',
            'file.file' => 'The uploaded file is invalid.',
            'file.mimes' => 'The file must be a CSV, XLSX, or XLS file.',
            'file.max' => 'The file must not exceed 10MB.',
        ];
    }
}
