<?php

namespace App\Http\Requests\V1\Assessment;

use App\Enums\AssessmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['assessments.manage', 'assessments.manage.own']) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:191'],
            'type' => ['required', Rule::enum(AssessmentType::class)],
            'max_score' => ['required', 'numeric', 'gt:0'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'date' => ['nullable', 'date'],
        ];
    }
}
