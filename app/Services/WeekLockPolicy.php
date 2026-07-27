<?php

namespace App\Services;

use App\Models\Team;
use App\Models\WeekLock;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class WeekLockPolicy
{
    public function __construct(private readonly WeekService $weeks) {}

    public function defaultUnlockedWeek(Team $team): CarbonImmutable
    {
        $currentWeek = $this->weeks->start($team, CarbonImmutable::now());
        $maxWeek = $currentWeek->addWeeks($team->weekLookahead());

        $lockedWeekStarts = WeekLock::withoutGlobalScope('team')
            ->where('team_id', $team->id)
            ->whereBetween('week_start', [$currentWeek->toDateString(), $maxWeek->toDateString()])
            ->pluck('week_start')
            ->map(fn ($ws) => $ws instanceof CarbonInterface ? $ws->toDateString() : (string) $ws)
            ->toArray();

        $cursor = $currentWeek;

        while ($cursor->lte($maxWeek)) {
            if (! in_array($cursor->toDateString(), $lockedWeekStarts, true)) {
                return $cursor;
            }
            $cursor = $cursor->addWeek();
        }

        return $currentWeek;
    }
}
