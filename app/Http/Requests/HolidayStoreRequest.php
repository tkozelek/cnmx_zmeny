<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HolidayStoreRequest extends FormRequest
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
            'date_from' => 'required',
            'date_to' => 'required',
            'popis' => 'required',
            'form_token' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'popis.required' => 'Popis je potrebný.',
            'date_from.required' => 'Začiatok je potrebný.',
            'date_to.required' => 'Koniec je potrebný.',
        ];
    }
}
