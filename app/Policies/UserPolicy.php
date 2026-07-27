<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

/**
 * Team-managed User Policy using dot-notation permissions:
 * - user.view-any: View user list for current cinema team
 * - user.view: View user details or own profile
 * - user.create: Add/invite new user to cinema team
 * - user.update: Edit user details and roles
 * - user.delete: Block/remove user from cinema team
 * - user.approve: Approve pending user registration
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInTeam('user.view-any');
    }

    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermissionInTeam('user.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionInTeam('user.create');
    }

    public function update(User $user, User $model): bool
    {
        $team = app(Team::class);

        return $user->hasPermissionInTeam('user.update', $team);
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasPermissionInTeam('user.delete');
    }

    public function approve(User $user, User $model): bool
    {
        $team = app(Team::class);

        return $user->hasPermissionInTeam('user.approve', $team);
    }
}
