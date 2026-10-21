<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNameRequest extends FormRequest
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
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', self::NO_FORMULA_PREFIX],
            'lastname' => ['required', 'string', 'max:100', self::NO_FORMULA_PREFIX],
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
