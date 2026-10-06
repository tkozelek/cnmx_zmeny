<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Absence;
use App\Models\Assignment;
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
        $weekAssignments = Assignment::with('user')
            ->betweenDates($from, $to)
            ->get()
            ->groupBy(fn (Assignment $a): string => $a->date->toDateString());

        $lockedWeekStarts = WeekLock::where('team_id', $team->id)
            ->pluck('week_start')
            ->map(fn ($ws) => $ws instanceof CarbonInterface ? $ws->toDateString() : (string) $ws)
            ->toArray();

        return [
            'weekStart' => $weekStart,
            'weekEnd' => $to,
            'days' => $this->weeks->days($weekStart),
            'weekAssignments' => $weekAssignments,
            'locked' => WeekLock::locked($team->getKey(), $weekStart),
            'lockedWeekStarts' => $lockedWeekStarts,
            'absences' => $canViewAbsences ? $this->absences($from, $to) : collect(),
            'available' => $viewer->hasPermissionInTeam('assignment.lock', $team) ? $this->available($team, $weekStart) : [],
        ];

    }

    /**
     * Per day, every active brigádnik with no absence covering it - the pool a manager draws
     * from (losovanie). Who already signed up is subtracted by DayCard, so it stays current
     * as people sign up and withdraw.
     *
     * @return array<string, array<int, string>> Y-m-d => [user id => "Priezvisko M."]
     */
    private function available(Team $team, CarbonImmutable $weekStart): array
    {
        [$from, $to] = $this->weeks->range($weekStart);

        $employees = $team->activeHoldersOf(Role::Employee);

        $absencesByUser = Absence::whereIn('user_id', $employees->modelKeys())
            ->overlapping($from, $to)
            ->get()
            ->groupBy('user_id');

        return $this->weeks->days($weekStart)
            ->mapWithKeys(fn (CarbonImmutable $day): array => [
                $day->toDateString() => $employees
                    ->reject(fn (User $user): bool => ($absencesByUser[$user->id] ?? collect())
                        ->contains(fn (Absence $absence): bool => $absence->covers($day)))
                    ->mapWithKeys(fn (User $user): array => [$user->id => (string) $user])
                    ->all(),
            ])
            ->all();
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
}
