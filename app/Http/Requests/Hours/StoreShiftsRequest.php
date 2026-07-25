<?php

namespace App\Http\Requests\Hours;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * A whole month of worked hours, posted by the hours screen as JSON.
 */
class StoreShiftsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2024'],
            'shifts' => ['nullable', 'array'],
            'shifts.*.date' => ['required', 'date_format:Y-m-d'],
            'shifts.*.start' => ['required', 'date_format:H:i'],
            'shifts.*.end' => ['required', 'date_format:H:i'],
            'shifts.*.break_minutes' => ['nullable', 'integer', 'between:0,600'],
        ];
    }

    /** The caller is fetch(), so failures must come back as JSON, not a redirect. */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
