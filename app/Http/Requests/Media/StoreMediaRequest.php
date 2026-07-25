<?php

namespace App\Http\Requests\Media;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Extension allow-list, not a MIME allow-list: `mimes` checks the real content
            // type, so a renamed executable is rejected rather than trusted.
            'file' => ['required', 'file', 'mimes:pdf,xlsx,xls,jpg,jpeg,png,gif,webp', 'max:2048'],
            'week_start' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Vyber súbor.',
            'file.mimes' => 'Nepovolený typ súboru.',
            'file.max' => 'Súbor je príliš veľký (max 2 MB).',
        ];
    }
}
