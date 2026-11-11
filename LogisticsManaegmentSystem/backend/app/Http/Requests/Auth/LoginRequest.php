<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Sign-in credentials.
 *
 * Validation only. Whether the credentials are correct, and whether the account
 * is locked out, are answered by App\Http\Controllers\Api\V1\Auth\LoginController
 * so that both can return a status code the SPA can act on rather than a
 * ValidationException with a generic "Validation failed." message.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Case and surrounding whitespace are not meaningful in an email address,
     * but they do change which row is looked up. Normalising here means the
     * lookup, the rate-limit key and the stored address all agree.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge([
                'email' => Str::lower(trim((string) $this->input('email'))),
            ]);
        }
    }

    /**
     * Rate-limit bucket for this attempt.
     *
     * Scoped to the address *and* the client IP together. Keying on the email
     * alone would let anyone lock a colleague out by guessing at their account;
     * keying on the IP alone would give a botnet unlimited attempts. Note this
     * is the address being attempted, not a verified one - that is deliberate,
     * so that attempts against a non-existent address still consume the budget.
     */
    public function throttleKey(): string
    {
        return 'login:'.Str::transliterate(Str::lower((string) $this->input('email'))).'|'.$this->ip();
    }

    public function maxAttempts(): int
    {
        return (int) config('logistics.auth.login_max_attempts');
    }

    public function decaySeconds(): int
    {
        return (int) config('logistics.auth.login_decay_seconds');
    }
}
