<?php

namespace App\Services;

use App\Models\Team;
use App\Models\WeekLock;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class WeekLockPolicy
{
    public function __construct(private readonly WeekService $weeks) {}

    /**
     * The nearest week people can still sign up for: steps past every locked week from the
     * current one on. If the whole week_lookahead is locked, lands back on the current week.
     */
    public function defaultUnlockedWeek(Team $team): CarbonImmutable
    {
        $currentWeek = $this->weeks->start($team, CarbonImmutable::now());
        $lastWeek = $this->weeks->lastOpenWeek($team);

        // Team passed explicitly, as in WeekLock::locked() - the scope would answer for the wrong team in console/tests.
        $lockedWeekStarts = WeekLock::query()
            ->withoutGlobalScope('team')
            ->where('team_id', $team->id)
            ->where('is_locked', true)
            ->whereBetween('week_start', [$currentWeek->toDateString(), $lastWeek->toDateString()])
            ->pluck('week_start')
            ->map(fn (CarbonInterface $weekStart): string => $weekStart->toDateString())
            ->all();

        for ($week = $currentWeek; $week->lte($lastWeek); $week = $week->addWeek()) {
            if (! in_array($week->toDateString(), $lockedWeekStarts, true)) {
                return $week;
            }
        }

        return $currentWeek;
    }
}
