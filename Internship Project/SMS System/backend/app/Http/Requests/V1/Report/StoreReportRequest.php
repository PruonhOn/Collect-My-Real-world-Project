<?php

namespace App\Http\Requests\V1\Report;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reports.export') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:100'],
            'params' => ['nullable', 'array'],
            'params.search' => ['nullable', 'string'],
            'params.status' => ['nullable', 'string'],
            'params.course_id' => ['nullable', 'integer'],
            'params.student_id' => ['nullable', 'integer'],
            'params.from' => ['nullable', 'date'],
            'params.to' => ['nullable', 'date'],
        ];
    }
}
