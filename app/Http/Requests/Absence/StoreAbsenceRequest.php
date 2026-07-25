<?php

namespace App\Http\Requests\Absence;

use App\Models\Absence;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreAbsenceRequest extends FormRequest
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
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'day_of_week' => ['nullable', 'integer', 'between:0,6'],
            'open_ended' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * The submission deadline: an absence must be reported at least
     * `team_settings.absence_deadline_hours` before the first day it covers, so the plan
     * can still be changed. Admins are exempt — they fix things after the fact.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || $this->user()->hasRole('admin')) {
                    return;
                }

                $hours = app(Team::class)->absenceDeadlineHours();
                $from = CarbonImmutable::parse($this->date('date_from'))->startOfDay();

                if ($from->lt(now()->addHours($hours))) {
                    $validator->errors()->add(
                        'date_from',
                        "Absenciu treba nahlásiť aspoň {$hours} hodín dopredu. Kontaktuj vedúceho."
                    );
                }
            },
        ];
    }

    /**
     * An open-ended recurring absence ("every Tuesday, indefinitely") is stored with the
     * FOREVER sentinel rather than a null date_to — see the Absence model for why.
     */
    protected function passedValidation(): void
    {
        if ($this->boolean('open_ended') && $this->filled('day_of_week')) {
            $this->merge(['date_to' => Absence::FOREVER]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_from.required' => 'Zadaj odkedy budeš chýbať.',
            'date_to.required' => 'Zadaj dokedy budeš chýbať.',
            'date_to.after_or_equal' => 'Koniec absencie nemôže byť pred jej začiatkom.',
        ];
    }
}
