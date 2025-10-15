<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRatesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'weekday' => ['nullable', 'decimal:0,2'],
            'saturday' => ['nullable', 'decimal:0,2'],
            'sunday' => ['nullable', 'decimal:0,2'],
            'break' => ['nullable', 'decimal:0,2'],
        ];
    }
}
