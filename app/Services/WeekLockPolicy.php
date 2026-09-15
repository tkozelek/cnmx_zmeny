<?php

namespace App\Services;

use App\Models\Team;
use App\Models\WeekLock;
use Carbon\CarbonImmutable;

class WeekLockPolicy
{
    public function __construct(private readonly WeekService $weeks) {}

    /**
     * Steps at most one week past the current one - if that's locked too, lands back on the
     * (locked) current week rather than hunting further into the team's full week_lookahead.
     */
    public function defaultUnlockedWeek(Team $team): CarbonImmutable
    {
        $currentWeek = $this->weeks->start($team, CarbonImmutable::now());
        $nextWeek = $currentWeek->addWeek();

        if (WeekLock::locked($team->id, $currentWeek) && ! WeekLock::locked($team->id, $nextWeek)) {
            return $nextWeek;
        }

        return $currentWeek;
    }
}
