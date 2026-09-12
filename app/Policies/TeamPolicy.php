<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    /**
     * Authorize viewing team settings.
     */
    public function viewSettings(User $user, Team $team): bool
    {
        return $user->hasPermissionInTeam('team.view', $team);
    }

    /**
     * Authorize managing/updating team settings.
     */
    public function updateSettings(User $user, Team $team): bool
    {
        return $user->hasPermissionInTeam('team.update', $team);
    }
}
