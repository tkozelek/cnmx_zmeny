<?php

namespace App\Rules;

use App\Models\Team;
use App\Models\WeekLock;
use App\Services\WeekService;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A lock belongs to a week_start, so moving the start day would silently unlock every current and
 * future locked week.
 */
class WeekStartDayChangeable implements ValidationRule
{
    public function __construct(private readonly Team $team) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ((int) $value === $this->team->weekStartDay()) {
            return;
        }

        $hasOpenLocks = WeekLock::withoutGlobalScope('team')
            ->where('team_id', $this->team->getKey())
            ->where('week_start', '>=', app(WeekService::class)->start($this->team, CarbonImmutable::now())->toDateString())
            ->exists();

        if ($hasOpenLocks) {
            $fail('Začiatok týždňa sa nedá zmeniť, kým je aktuálny alebo budúci týždeň zamknutý alebo má zverejnený rozpis. Najprv ho odomkni a rozpis stiahni.');
        }
    }
}
