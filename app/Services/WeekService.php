<?php

namespace App\Services;

use App\Models\Team;
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

    public function alignFromRequest(Team $team, ?string $date, ?WeekLockPolicy $lockPolicy = null): CarbonImmutable
    {
        if (! $date) {
            return ($lockPolicy ?? app(WeekLockPolicy::class))->defaultUnlockedWeek($team);
        }

        try {
            $parsed = CarbonImmutable::parse($date);
        } catch (InvalidFormatException) {
            return ($lockPolicy ?? app(WeekLockPolicy::class))->defaultUnlockedWeek($team);
        }

        return $this->clampForward($team, $this->start($team, $parsed));
    }

    public function defaultUnlockedWeek(Team $team): CarbonImmutable
    {
        return app(WeekLockPolicy::class)->defaultUnlockedWeek($team);
    }

    public function clampForward(Team $team, CarbonImmutable $weekStart): CarbonImmutable
    {
        $last = $this->start($team, CarbonImmutable::now())->addWeeks($team->weekLookahead());

        return $weekStart->gt($last) ? $last : $weekStart;
    }
}
