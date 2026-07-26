<?php

namespace App\Services;

use App\Models\Absence;
use App\Models\Assignment;
use App\Models\Media;
use App\Models\Team;
use App\Models\User;
use App\Models\WeekLock;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
        $isAdmin = $viewer->hasRole('admin');

        // Preload all assignments for the week in 1 query to prevent N+1 queries across the 7 DayCards.
        $weekAssignments = Assignment::with(['user', 'position'])
            ->betweenDates($from, $to)
            ->get()
            ->groupBy(fn (Assignment $a): string => $a->date->toDateString());

        return [
            'weekStart' => $weekStart,
            'weekEnd' => $to,
            'days' => $this->weeks->days($weekStart),
            'weekAssignments' => $weekAssignments,
            'locked' => WeekLock::locked($team->getKey(), $weekStart),
            'media' => $this->media($weekStart, $isAdmin),
            'absences' => $isAdmin ? $this->absences($from, $to) : collect(),
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
     * @return Collection<int, object>
     */
    private function signupCounts(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return Assignment::betweenDates($from, $to)
            ->join('users', 'users.id', '=', 'assignments.user_id')
            ->groupBy('users.id', 'users.name', 'users.lastname')
            ->orderByDesc('count')
            ->get([
                'users.id as user_id',
                'users.name',
                'users.lastname',
                DB::raw('COUNT(DISTINCT assignments.date) as count'),
            ]);
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
