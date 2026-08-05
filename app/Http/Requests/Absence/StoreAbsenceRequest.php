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
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * The submission deadline: an absence must be reported at least
     * `team_settings.absence_deadline_days` before the first day it covers.
     * Admins and managers are exempt - they fix things after the fact.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || $this->user()->hasPermissionInTeam('absence.manage')) {
                    return;
                }

                $team = app(Team::class);
                $days = $team->absenceDeadlineDays();
                $from = CarbonImmutable::parse($this->date('date_from'))->startOfDay();
                $deadline = now()->startOfDay()->addDays($days);

                if ($from->lt($deadline)) {
                    $unit = match (true) {
                        $days === 1 => 'deň',
                        $days >= 2 && $days <= 4 => 'dni',
                        default => 'dní',
                    };
                    $validator->errors()->add(
                        'date_from',
                        "Absenciu v kine {$team->name} je potrebné nahlásiť minimálne {$days} {$unit} vopred."
                    );
                }
            },
        ];
    }

    /**
     * An open-ended recurring absence ("every Tuesday, indefinitely") is stored with the
     * FOREVER sentinel rather than a null date_to - see the Absence model for why.
     */
    protected function prepareForValidation(): void
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
            'reason.required' => 'Uveď dôvod absencie.',
        ];
    }
}
