<?php

namespace App\Http\Requests\Vip;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscribeVipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', Rule::exists('vip_plans', 'id')->where('is_active', true)],
        ];
    }
}
