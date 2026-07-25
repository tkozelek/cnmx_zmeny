<?php

namespace App\Policies;

use App\Models\Absence;
use App\Models\Team;
use App\Models\User;

/**
 * Team-managed Absence Policy:
 * - Everyone can create a new absence for themselves in their active cinema team ('absence.create').
 * - Everyone can manage/end their own active absences ('absence.manage-own').
 * - Inactive (past) absences can be deleted by the owner within X days (30 days) ('absence.delete-own'),
 *   or by managers/admins with 'absence.delete-inactive' or 'absence.manage'.
 */
class AbsencePolicy
{
    /** Number of days an inactive absence remains deletable by its owner. */
    public const INACTIVE_DELETION_WINDOW_DAYS = 30;

    /**
     * Determine if the user can view all staff absences for the team.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInTeam('absence.view');
    }

    /**
     * Determine if the user can create an absence.
     */
    public function create(User $user): bool
    {
        $team = app(Team::class);

        return $user->isApprovedIn($team);
    }

    /**
     * Determine if the user can end an active absence early.
     */
    public function end(User $user, Absence $absence): bool
    {
        $team = app(Team::class);

        if ($absence->team_id !== $team->id) {
            return false;
        }

        // Owner can end their own active absence
        if ($absence->user_id === $user->id) {
            return true;
        }

        // Managers/Admins with permission can end any absence
        return $user->hasPermissionInTeam('absence.manage', $team);
    }

    /**
     * Determine if the user can delete an absence.
     */
    public function delete(User $user, Absence $absence): bool
    {
        $team = app(Team::class);

        if ($absence->team_id !== $team->id) {
            return false;
        }

        // Managers / admins with permission can delete any absence (active or inactive)
        if ($user->hasPermissionInTeam('absence.delete-inactive', $team) || $user->hasPermissionInTeam('absence.manage', $team)) {
            return true;
        }

        // Owner can delete their own absence
        if ($absence->user_id === $user->id) {
            $isActive = $absence->date_to->gte(now()->startOfDay());

            // Active absence can be deleted by owner
            if ($isActive) {
                return true;
            }

            // Inactive absence can be deleted by owner only within X days (30 days)
            $daysSinceEnd = (int) $absence->date_to->diffInDays(now()->startOfDay());

            return $daysSinceEnd <= self::INACTIVE_DELETION_WINDOW_DAYS;
        }

        return false;
    }
}
