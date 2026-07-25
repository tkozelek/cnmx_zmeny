<?php

namespace App\Http\Requests\Hours;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreRateRequest extends FormRequest
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
            'weekday' => ['required', 'numeric', 'between:0,999.99'],
            'saturday' => ['required', 'numeric', 'between:0,999.99'],
            'sunday' => ['required', 'numeric', 'between:0,999.99'],
            'break_deduction' => ['nullable', 'numeric', 'between:0,999.99'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
