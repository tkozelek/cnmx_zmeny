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
        return $user->hasRole('admin') || $media->is_visible_to_employees;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Media $media): bool
    {
        return $user->hasRole('admin');
    }

    public function toggleVisibility(User $user, Media $media): bool
    {
        return $user->hasRole('admin');
    }
}
