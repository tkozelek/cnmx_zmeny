<?php

namespace App\Services;

use App\Models\Team;
use App\Models\WeekLock;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Collection;

class WeekService
{
    public function start(Team $team, CarbonInterface $date): CarbonImmutable
    {
        $day = CarbonImmutable::parse($date)->startOfDay();
        $weekStart = $day->startOfWeek(CarbonInterface::MONDAY)->addDays($team->weekStartDay());

        return $weekStart->gt($day) ? $weekStart->subWeek() : $weekStart;
    }

    public function end(CarbonImmutable $weekStart): CarbonImmutable
    {
        return $weekStart->addDays(6);
    }

    /**
     * @return Collection<int, CarbonImmutable>
     */
    public function days(CarbonImmutable $weekStart): Collection
    {
        return collect(range(0, 6))->map(fn (int $offset): CarbonImmutable => $weekStart->addDays($offset));
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(CarbonImmutable $weekStart): array
    {
        return [$weekStart, $this->end($weekStart)];
    }

    public function next(CarbonImmutable $weekStart): CarbonImmutable
    {
        return $weekStart->addWeek();
    }

    public function previous(CarbonImmutable $weekStart): CarbonImmutable
    {
        return $weekStart->subWeek();
    }

    public function alignFromRequest(Team $team, ?string $date): CarbonImmutable
    {
        if (! $date) {
            return $this->defaultUnlockedWeek($team);
        }

        try {
            $parsed = CarbonImmutable::parse($date);
        } catch (InvalidFormatException) {
            return $this->defaultUnlockedWeek($team);
        }

        return $this->clampForward($team, $this->start($team, $parsed));
    }

    public function defaultUnlockedWeek(Team $team): CarbonImmutable
    {
        $currentWeek = $this->start($team, CarbonImmutable::now());
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

    public function clampForward(Team $team, CarbonImmutable $weekStart): CarbonImmutable
    {
        $last = $this->start($team, CarbonImmutable::now())->addWeeks($team->weekLookahead());

        return $weekStart->gt($last) ? $last : $weekStart;
    }
}
