<?php

namespace App\Policies;

use App\Models\PositionSlot;
use App\Models\Team;
use App\Models\User;
use App\Models\WeekLock;
use App\Services\WeekService;
use Carbon\CarbonInterface;

/**
 * Slots are the rozpis builder's layout, so they follow the builder's rule: editable only
 * *after* the week is locked.
 *
 * That is the inverse of AssignmentPolicy::create()/delete(), which require an unlocked week.
 * Locking is what closes self-signup and opens the manager's step. One permission covers the
 * whole workflow (add, remove, copy) rather than one per button.
 */
class PositionSlotPolicy
{
    public function __construct(private readonly WeekService $weeks) {}

    public function create(User $user, CarbonInterface $date): bool
    {
        return $this->mayBuild($user, $date);
    }

    public function delete(User $user, PositionSlot $slot): bool
    {
        if ($slot->team_id !== app(Team::class)->getKey()) {
            return false;
        }

        return $this->mayBuild($user, $slot->date);
    }

    private function mayBuild(User $user, CarbonInterface $date): bool
    {
        $team = app(Team::class);

        if (! WeekLock::locked($team->getKey(), $this->weeks->start($team, $date))) {
            return false;
        }

        return $user->hasPermissionInTeam('assignment.assign-position', $team);
    }
}
