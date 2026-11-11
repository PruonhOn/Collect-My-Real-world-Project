<?php

namespace App\Http\Requests\V1\Score;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScoreRequest extends FormRequest
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
            'score' => ['required', 'numeric', 'min:0'],
            'remark' => ['nullable', 'string', 'max:191'],
            'student_id' => ['sometimes', 'integer', Rule::exists('students', 'id')],
        ];
    }
}
