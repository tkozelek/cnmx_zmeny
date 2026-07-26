<?php

namespace App\Policies;

use App\Enums\AbsenceStatus;
use App\Models\Absence;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Team-managed Absence Policy:
 * - Everyone can create a new absence for themselves in their active cinema team ('absence.create').
 * - Only the owner can end their own active absence ('absence.manage-own') — managers/admins
 *   cannot end someone else's; they can only delete one once it's inactive (see delete()).
 * - The owner can delete their own absence outright within CREATION_DELETE_GRACE_MINUTES of
 *   creating it (fixing an immediate mistake).
 * - Past that, an active absence can't be deleted by anyone — it must be ended (cancelled)
 *   first. Once inactive (cancelled or past), it has to sit for the team's configurable
 *   retention period (`Team::staleAbsenceDeletionDays()`, default 30 days; 0 = no waiting)
 *   before it can be deleted at all — this applies uniformly, no admin bypass.
 * - Managers/admins with 'absence.delete-inactive' or 'absence.manage' additionally get to
 *   delete anyone's absence (not just their own), subject to the same active/retention rules.
 */
class AbsencePolicy
{
    /** How long after creating an absence its owner may delete it outright, no questions asked. */
    private const CREATION_DELETE_GRACE_MINUTES = 15;

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
     *
     * Owner-only — managers/admins never cancel someone else's absence for them. Their reach
     * over other people's absences is limited to deleting one once it's already inactive.
     */
    public function end(User $user, Absence $absence): bool
    {
        return $absence->user_id === $user->id;
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

        $isOwner = $absence->user_id === $user->id;
        $canManageTeamAbsences = $user->hasPermissionInTeam('absence.delete-inactive', $team) || $user->hasPermissionInTeam('absence.manage', $team);

        // Only the owner, or a manager/admin acting on anyone's absence, gets this far.
        if (! $isOwner && ! $canManageTeamAbsences) {
            return false;
        }

        // A freshly created absence can be deleted outright by its owner for a short grace
        // period — correcting an immediate mistake, no need to go through "end" first. Doesn't
        // apply to a manager deleting someone else's — they didn't just make the mistake.
        if ($isOwner && $absence->created_at->diffInMinutes(now()) <= self::CREATION_DELETE_GRACE_MINUTES) {
            return true;
        }

        // Past the grace period, an active absence can't be deleted directly by anyone —
        // it must be cancelled first.
        if ($absence->isActive()) {
            return false;
        }

        // Inactive (cancelled or past) absence must sit for the team's configurable retention
        // period, counted FROM when it ended, before ANYONE may delete it — owner or manager,
        // no bypass. 0 means no waiting — deletable as soon as it's inactive.
        $window = $team->staleAbsenceDeletionDays();

        if ($window === 0) {
            return true;
        }

        // Cancelled: count from when it was cancelled (updated_at) — date_to may still be in
        // the future for a plan cancelled before it even started.
        // Naturally expired: count from date_to.
        $inactiveSince = $absence->status === AbsenceStatus::Cancelled
            ? CarbonImmutable::parse($absence->updated_at)->startOfDay()
            : CarbonImmutable::parse($absence->date_to)->startOfDay();

        $daysInactive = (int) $inactiveSince->diffInDays(now()->startOfDay());

        // Deletable only once the retention period has actually elapsed.
        return $daysInactive >= $window;
    }
}
