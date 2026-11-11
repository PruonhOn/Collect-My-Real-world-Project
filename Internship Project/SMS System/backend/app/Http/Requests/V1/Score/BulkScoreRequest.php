<?php

namespace App\Http\Requests\V1\Score;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['scores.enter', 'scores.enter.own']) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'rows.*.score' => ['required', 'numeric', 'min:0'],
            'rows.*.remark' => ['nullable', 'string', 'max:191'],
        ];
    }
}
