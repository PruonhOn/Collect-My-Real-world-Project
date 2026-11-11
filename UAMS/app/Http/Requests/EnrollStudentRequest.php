<?php

namespace App\Http\Requests;

use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnrollStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:students,id',
            'class_section_id' => 'required|exists:class_sections,id',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->student_id && $this->class_section_id) {
                $exists = Enrollment::where('student_id', $this->student_id)
                    ->where('class_section_id', $this->class_section_id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'student_id',
                        'This student is already enrolled in the selected class section.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Student is required.',
            'student_id.exists' => 'Selected student does not exist.',
            'class_section_id.required' => 'Class section is required.',
            'class_section_id.exists' => 'Selected class section does not exist.',
        ];
    }
}
