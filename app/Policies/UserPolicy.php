<?php

namespace App\Policies;

use App\Enums\Role;
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
 * - user.manage-managers: Touch a Manager/HeadManager account, or promote anyone into one -
 *   the one thing a plain Manager may not do (see canManageRole()).
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

    /** $targetRole is the role the new account is about to be assigned. */
    public function create(User $user, ?Role $targetRole = null): bool
    {
        if (! $user->hasPermissionInTeam('user.create')) {
            return false;
        }

        return $this->canManageRole($user, $targetRole);
    }

    /**
     * $newRole is the role the form is about to set - omitted when just opening the edit page,
     * in which case only $model's current role gates access.
     */
    public function update(User $user, User $model, ?Role $newRole = null): bool
    {
        $team = app(Team::class);

        if (! $user->hasPermissionInTeam('user.update', $team)) {
            return false;
        }

        return $this->canManageRole($user, $this->roleOf($model))
            && $this->canManageRole($user, $newRole);
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        if (! $user->hasPermissionInTeam('user.delete')) {
            return false;
        }

        return $this->canManageRole($user, $this->roleOf($model));
    }

    public function approve(User $user, User $model): bool
    {
        $team = app(Team::class);

        return $user->hasPermissionInTeam('user.approve', $team);
    }

    /** Manager/HeadManager-tier accounts are the one thing a plain Manager may not touch. */
    private function canManageRole(User $user, ?Role $role): bool
    {
        if (! in_array($role, [Role::Manager, Role::HeadManager], true)) {
            return true;
        }

        return $user->hasPermissionInTeam('user.manage-managers');
    }

    private function roleOf(User $model): ?Role
    {
        return Role::tryFrom($model->roles->first()?->name ?? '');
    }
}
