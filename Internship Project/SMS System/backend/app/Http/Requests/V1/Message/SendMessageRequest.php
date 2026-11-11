<?php

namespace App\Http\Requests\V1\Message;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['messages.send', 'messages.respond']) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'parent_id' => ['nullable', 'integer', Rule::exists('messages', 'id')],
            'subject' => ['nullable', 'string', 'max:191'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
