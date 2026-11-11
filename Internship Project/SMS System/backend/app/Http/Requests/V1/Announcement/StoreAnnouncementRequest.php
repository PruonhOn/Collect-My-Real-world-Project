<?php

namespace App\Http\Requests\V1\Announcement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('announcements.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:191'],
            'body' => ['required', 'string', 'max:20000'],
            'audience' => ['required', 'array', 'min:1'],
            'audience.*' => ['string', Rule::in(['all', 'admin', 'teacher', 'staff', 'student', 'parent'])],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
        ];
    }
}
