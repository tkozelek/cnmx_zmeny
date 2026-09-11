<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * A name may not begin with a character Excel reads as the start of a formula.
     *
     * Names land in the .xlsx exports a manager opens; the exports escape these too, but keeping
     * them out of the column in the first place means anything else that ever renders a name -
     * a PDF, a CSV, a print sheet - inherits the fix for free. No real name starts with these.
     */
    private const string NO_FORMULA_PREFIX = 'not_regex:/^[=+\\-@\\t\\r]/';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * `email` is unique across the whole table, not per team: one person is one account
     * who can hold a different role in each cinema they work at.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', self::NO_FORMULA_PREFIX],
            'lastname' => ['required', 'string', 'max:100', self::NO_FORMULA_PREFIX],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)],

            // Which cinema to join. Optional while there is only one - the controller
            // resolves it rather than making the form carry a pointless select.
            'team_id' => ['nullable', Rule::exists('teams', 'id')->where('is_active', true)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return config('constants.messages');
    }
}
