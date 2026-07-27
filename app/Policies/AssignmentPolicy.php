<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Team;
use App\Models\User;
use App\Models\WeekLock;
use App\Services\WeekService;
use Carbon\CarbonInterface;

/**
 * Who may put someone on the plan, and take them off again.
 *
 * The week-lock rule lives here rather than in validation: a locked week is a question of
 * permission, not of whether the submitted data is well-formed.
 */
class AssignmentPolicy
{
    public function __construct(private readonly WeekService $weeks) {}

    /** Sign up for a date. Admins are not stopped by a lock — they are the ones locking. */
    public function create(User $user, Team $team, CarbonInterface $date, ?User $targetUser = null): bool
    {
        $forUser = $targetUser ?? $user;

        // Users cannot sign up for work on dates covered by their reported absences.
        $hasAbsence = $forUser->absences()
            ->where('team_id', $team->id)
            ->overlapping($date, $date)
            ->get()
            ->contains(fn ($absence) => $absence->covers($date));

        if ($hasAbsence) {
            return false;
        }

        if ($user->hasPermissionInTeam('assignment.create', $team)) {
            return true;
        }

        return $user->isApprovedIn($team) && ! $this->weekLocked($team, $date);
    }

    /** Remove a row: your own, from an unlocked week. Admins may remove anyone's. */
    public function delete(User $user, Assignment $assignment): bool
    {
        $team = app(Team::class);
        if ($assignment->team_id !== $team->id) {
            return false;
        }

        if ($user->hasPermissionInTeam('assignment.delete', $team)) {
            return true;
        }

        return $assignment->user_id === $user->id
            && ! $this->weekLocked($assignment->team, $assignment->date);
    }

    /** Only authorized roles freeze and unfreeze a week. */
    public function lock(User $user): bool
    {
        $team = app(Team::class);

        return $user->hasPermissionInTeam('assignment.lock', $team);
    }

    private function weekLocked(Team $team, CarbonInterface $date): bool
    {
        return WeekLock::locked($team->getKey(), $this->weeks->start($team, $date));
    }
}
