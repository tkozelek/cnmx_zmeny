<?php

namespace App\Services;

use App\Models\Absence;
use App\Models\Assignment;
use App\Models\Media;
use App\Models\Team;
use App\Models\User;
use App\Models\WeekLock;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Assembles everything one week of the calendar needs.
 */
class CalendarService
{
    public function __construct(private readonly WeekService $weeks) {}

    /**
     * @return array<string, mixed>
     */
    public function forWeek(Team $team, CarbonImmutable $weekStart, User $viewer): array
    {
        [$from, $to] = $this->weeks->range($weekStart);
        $canViewAbsences = $viewer->hasPermissionInTeam('absence.view', $team);

        // Preload all assignments for the week in 1 query to prevent N+1 queries across the 7 DayCards.
        $weekAssignments = Assignment::with(['user', 'position'])
            ->betweenDates($from, $to)
            ->get()
            ->groupBy(fn (Assignment $a): string => $a->date->toDateString());

        $lockedWeekStarts = WeekLock::where('team_id', $team->id)
            ->pluck('week_start')
            ->map(fn ($ws) => $ws instanceof CarbonInterface ? $ws->toDateString() : (string) $ws)
            ->toArray();

        // One row answers both questions: a week is locked when it exists, and published when it
        // also carries a timestamp. Publication only ever happens to a locked week.
        $lock = WeekLock::forWeek($team->getKey(), $weekStart);

        return [
            'weekStart' => $weekStart,
            'weekEnd' => $to,
            'days' => $this->weeks->days($weekStart),
            'weekAssignments' => $weekAssignments,
            'locked' => $lock !== null,
            'rozpisPublished' => (bool) $lock?->isRozpisPublished(),
            'lockedWeekStarts' => $lockedWeekStarts,
            'media' => $this->media($weekStart, $canViewAbsences),
            'absences' => $canViewAbsences ? $this->absences($from, $to) : collect(),
        ];

    }

    /**
     * @return Collection<int, Absence>
     */
    private function absences(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return Absence::with('user')
            ->overlapping($from, $to)
            ->get()
            ->filter(fn (Absence $absence): bool => $this->weeks->days($from)->contains(
                fn (CarbonImmutable $day): bool => $absence->covers($day)
            ))
            ->values();
    }

    /**
     * @return Collection<int, Media>
     */
    private function media(CarbonImmutable $weekStart, bool $isAdmin): Collection
    {
        return Media::forWeek($weekStart)
            ->unless($isAdmin, fn ($query) => $query->visible())
            ->latest()
            ->get();
    }
}
