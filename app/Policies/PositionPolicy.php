<?php

namespace App\Policies;

use App\Models\Position;
use App\Models\Team;
use App\Models\User;

/**
 * Who may define the cinema's positions.
 *
 * No week-lock rule here, unlike AssignmentPolicy: the position catalogue is configuration,
 * not part of any one week's plan.
 */
class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInTeam('position.view-any', app(Team::class));
    }

    public function view(User $user, Position $position): bool
    {
        return $this->sameTeam($position) && $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionInTeam('position.create', app(Team::class));
    }

    public function update(User $user, Position $position): bool
    {
        return $this->sameTeam($position)
            && $user->hasPermissionInTeam('position.update', app(Team::class));
    }

    /**
     * Deleting a position cascades to its historical assignments (see the composite FK on
     * `assignments`), which is why the UI deactivates instead. Kept as its own permission so
     * the destructive path stays explicit.
     */
    public function delete(User $user, Position $position): bool
    {
        return $this->sameTeam($position)
            && $user->hasPermissionInTeam('position.delete', app(Team::class));
    }

    private function sameTeam(Position $position): bool
    {
        return $position->team_id === app(Team::class)->getKey();
    }
}
