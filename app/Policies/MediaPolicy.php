<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;

class MediaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function download(User $user, Media $media): bool
    {
        $team = app(Team::class);
        if ($media->team_id !== $team->id) {
            return false;
        }

        return $user->hasRole('admin') || $media->is_visible;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Media $media): bool
    {
        $team = app(Team::class);
        if ($media->team_id !== $team->id) {
            return false;
        }

        return $user->hasRole('admin');
    }

    public function toggleVisibility(User $user, Media $media): bool
    {
        $team = app(Team::class);
        if ($media->team_id !== $team->id) {
            return false;
        }

        return $user->hasRole('admin');
    }
}
