<?php

namespace App\Http\Requests\Settings;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['bail', 'required', 'email', 'max:255', $this->notTheCurrentAddress(...), Rule::unique('users', 'email')],
            'password' => ['required', 'current_password'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...config('constants.messages'),
            'password.current_password' => 'Heslo je nesprávne.',
        ];
    }

    private function notTheCurrentAddress(string $attribute, mixed $value, Closure $fail): void
    {
        if (mb_strtolower((string) $value) === mb_strtolower($this->user()->email)) {
            $fail('Toto je tvoja súčasná adresa.');
        }
    }

    protected function getRedirectUrl(): string
    {
        return route('settings.index').'#email';
    }
}
