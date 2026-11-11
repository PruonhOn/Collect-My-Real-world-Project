<?php

namespace App\Http\Requests\Admin\VipPlan;

use App\Models\VipPlan;
use Illuminate\Foundation\Http\FormRequest;

class StoreVipPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', VipPlan::class);
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:100', 'unique:vip_plans,slug'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.name' => ['required', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
        ];
    }
}
