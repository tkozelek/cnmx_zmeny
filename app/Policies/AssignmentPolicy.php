<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Team;
use App\Models\User;
use App\Models\WeekLock;
use App\Services\WeekService;
use App\Traits\GuardsCurrentTeam;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\Response;

/**
 * Who may put someone on the plan, and take them off again.
 *
 * The week-lock rule lives here rather than in validation: a locked week is a question of
 * permission, not of whether the submitted data is well-formed.
 */
class AssignmentPolicy
{
    use GuardsCurrentTeam;

    public const string LOCKED_MESSAGE = 'Týždeň je zamknutý – zápisy sa už nedajú meniť.';

    public const string PAST_MESSAGE = 'Minulé dni sa už nedajú meniť.';

    public function __construct(private readonly WeekService $weeks) {}

    /**
     * Sign up for a date. Admins are not stopped by a lock - they are the ones locking.
     *
     * `$targetUser` is who is being written into the day, which is not always `$user`: a manager
     * may sign somebody else up. The absence check has to be about *them* - refusing the manager
     * their own day off while happily booking an absent brigádnik is exactly backwards.
     */
    public function create(User $user, Team $team, CarbonInterface $date, ?User $targetUser = null): Response
    {
        $forUser = $targetUser ?? $user;

        // Keeps a visiting super admin off the plan, lists and exports.
        if (! $forUser->isApprovedIn($team)) {
            return Response::deny('Na zmeny sa môžu zapisovať len členovia kina.');
        }

        // Users cannot sign up for work on dates covered by their reported absences.
        $hasAbsence = $forUser->absences()
            ->where('team_id', $team->id)
            ->overlapping($date, $date)
            ->get()
            ->contains(fn ($absence) => $absence->covers($date));

        if ($hasAbsence) {
            return Response::deny($forUser->is($user)
                ? 'Na tento deň máš nahlásenú absenciu.'
                : 'Na tento deň má nahlásenú absenciu.');
        }

        if ($user->hasPermissionInTeam('assignment.create', $team)) {
            return Response::allow();
        }

        if (! $user->isApprovedIn($team)) {
            return Response::deny();
        }

        if ($date->lt(CarbonImmutable::today())) {
            return Response::deny(self::PAST_MESSAGE);
        }

        // The calendar only shows weeks up to the team's lookahead; a crafted request must not
        // reach further (it would also skew everyone's statistics).
        if ($date->gt($this->weeks->end($this->weeks->lastOpenWeek($team)))) {
            return Response::deny('Tento týždeň ešte nie je otvorený na zápis.');
        }

        return $this->weekLocked($team, $date) ? Response::deny(self::LOCKED_MESSAGE) : Response::allow();
    }

    /** Remove a row: your own, from an unlocked week. Admins may remove anyone's. */
    public function delete(User $user, Assignment $assignment): Response|bool
    {
        $team = app(Team::class);

        if (! $this->belongsToCurrentTeam($assignment)) {
            return false;
        }

        if ($user->hasPermissionInTeam('assignment.delete', $team)) {
            return true;
        }

        if ($assignment->user_id !== $user->id) {
            return false;
        }

        if ($assignment->date->lt(CarbonImmutable::today())) {
            return Response::deny(self::PAST_MESSAGE);
        }

        return $this->weekLocked($assignment->team, $assignment->date)
            ? Response::deny(self::LOCKED_MESSAGE)
            : Response::allow();
    }

    /**
     * Place someone on a position (or take them off it) in the rozpis builder.
     *
     * Requires the week to be *locked* - the inverse of create()/delete(). Self-signup and
     * position assignment are two consecutive phases of the same week, and locking is the
     * switch between them: employees stop editing, the manager starts.
     */
    public function assignPosition(User $user, Assignment $assignment): bool
    {
        $team = app(Team::class);

        if (! $this->belongsToCurrentTeam($assignment)) {
            return false;
        }

        if (! $this->weekLocked($team, $assignment->date)) {
            return false;
        }

        return $user->canBuildRozpis();
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
