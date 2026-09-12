<?php

namespace App\Http\Requests\Team;

use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('updateSettings', app(Team::class));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'week_start_day' => ['required', 'integer', 'between:0,6'],
            'week_lookahead' => ['required', 'integer', 'between:1,52'],
            'absence_deadline_days' => ['required', 'integer', 'between:0,30'],
            'stale_absence_deletion_days' => ['required', 'integer', 'between:0,365'],

            // Exactly seven, Monday-indexed. A short array would misprice part of the week, and
            // Team::fairnessDayWeights() would silently fall back to the defaults instead.
            'fairness_day_weights' => ['required', 'array', 'size:7'],
            'fairness_day_weights.*' => ['required', 'numeric', 'between:0.1,10'],
            'fairness_window_weeks' => ['required', 'integer', 'between:1,52'],
        ];
    }

    /**
     * Number inputs arrive as strings, and the `array` cast would store them as strings -
     * leaving the JSON column's contents dependent on how the row happened to be written.
     */
    protected function passedValidation(): void
    {
        $this->merge([
            'fairness_day_weights' => array_map('floatval', array_values($this->validated('fairness_day_weights'))),
        ]);
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Názov kina je povinný.',
            'week_start_day.required' => 'Začiatok týždňa je povinný.',
            'week_start_day.between' => 'Začiatok týždňa musí byť deň v týždni.',
            'week_lookahead.between' => 'Počet týždňov dopredu musí byť medzi 1 a 52.',
            'absence_deadline_days.between' => 'Uzávierka absencií musí byť medzi 0 a 30 dňami.',
            'stale_absence_deletion_days.required' => 'Lehota na vymazanie neaktívnej absencie je povinná.',
            'stale_absence_deletion_days.between' => 'Lehota na vymazanie neaktívnej absencie musí byť medzi 0 a 365 dňami.',
            'fairness_day_weights.size' => 'Váhy dní musia byť zadané pre všetkých 7 dní.',
            'fairness_day_weights.*.required' => 'Váha dňa je povinná.',
            'fairness_day_weights.*.numeric' => 'Váha dňa musí byť číslo.',
            'fairness_day_weights.*.between' => 'Váha dňa musí byť medzi 0,1 a 10.',
            'fairness_window_weeks.required' => 'Obdobie hodnotenia je povinné.',
            'fairness_window_weeks.between' => 'Obdobie hodnotenia musí byť medzi 1 a 52 týždňami.',
        ];
    }
}
