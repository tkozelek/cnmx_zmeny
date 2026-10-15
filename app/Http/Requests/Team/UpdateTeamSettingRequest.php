<?php

namespace App\Http\Requests\Team;

use App\Models\Team;
use App\Models\WeekLock;
use App\Services\WeekService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
        ];
    }

    /**
     * A lock belongs to a week_start, so moving the start day would silently unlock every current and
     * future locked week. Past ones no longer matter - employees can't change past days.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $team = app(Team::class);

                if ($validator->errors()->isNotEmpty() || (int) $this->input('week_start_day') === $team->weekStartDay()) {
                    return;
                }

                $hasOpenLocks = WeekLock::query()
                    ->where('week_start', '>=', app(WeekService::class)->start($team, CarbonImmutable::now())->toDateString())
                    ->exists();

                if ($hasOpenLocks) {
                    $validator->errors()->add('week_start_day', 'Začiatok týždňa sa nedá zmeniť, kým je aktuálny alebo budúci týždeň zamknutý. Najprv ho odomkni.');
                }
            },
        ];
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
        ];
    }
}
