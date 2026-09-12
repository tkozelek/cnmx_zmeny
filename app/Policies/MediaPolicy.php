<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\Team;
use App\Models\User;
use App\Traits\GuardsCurrentTeam;

class MediaPolicy
{
    use GuardsCurrentTeam;

    public function viewAny(User $user): bool
    {
        $team = app(Team::class);

        return $user->hasPermissionInTeam('media.view', $team);
    }

    public function download(User $user, Media $media): bool
    {
        $team = app(Team::class);
        if (! $this->belongsToCurrentTeam($media)) {
            return false;
        }

        return $user->hasPermissionInTeam('media.view', $team) || $media->is_visible;
    }

    public function create(User $user): bool
    {
        $team = app(Team::class);

        return $user->hasPermissionInTeam('media.create', $team);
    }

    public function delete(User $user, Media $media): bool
    {
        $team = app(Team::class);
        if (! $this->belongsToCurrentTeam($media)) {
            return false;
        }

        return $user->hasPermissionInTeam('media.delete', $team);
    }

    public function toggleVisibility(User $user, Media $media): bool
    {
        $team = app(Team::class);
        if (! $this->belongsToCurrentTeam($media)) {
            return false;
        }

        return $user->hasPermissionInTeam('media.update', $team);
    }
}
