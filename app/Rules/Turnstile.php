<?php

namespace App\Rules;

use Closure;
use Http;
use Illuminate\Contracts\Validation\ValidationRule;

class Turnstile implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secretKey = config('services.turnstile.secret');

        if (!$secretKey) {
            $fail('Cloudflare Turnstile nie je správne nakonfigurovaný.');

            return;
        }

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => $secretKey,
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);

        $outcome = $response->json();

        if (! isset($outcome['success']) || ! $outcome['success']) {
            $fail('Overenie Cloudflare Turnstile zlyhalo.');
        }
    }
}
