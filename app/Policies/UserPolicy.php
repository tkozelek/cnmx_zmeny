<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\Response;

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

        return $this->inCurrentTeam($model) && $user->hasPermissionInTeam('user.view');
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
    public function update(User $user, User $model, ?Role $newRole = null): bool|Response
    {
        $team = app(Team::class);

        if (! $this->inCurrentTeam($model) || ! $user->hasPermissionInTeam('user.update', $team)) {
            return false;
        }

        if (! $this->canManageRole($user, $this->roleOf($model)) || ! $this->canManageRole($user, $newRole)) {
            return false;
        }

        // $newRole is null when the edit page is merely being opened - nothing is being changed
        // yet, so there is nothing to protect against.
        if ($newRole === null || $newRole === Role::HeadManager || ! $this->isLastHeadManager($model)) {
            return true;
        }

        return $this->denyLastHeadManager('Toto je posledný hlavný manažér kina - najprv vymenujte ďalšieho, potom mu môžete zmeniť rolu.');
    }

    public function delete(User $user, User $model): bool|Response
    {
        if ($user->id === $model->id) {
            return false;
        }

        if (! $this->inCurrentTeam($model) || ! $user->hasPermissionInTeam('user.delete')) {
            return false;
        }

        if (! $this->canManageRole($user, $this->roleOf($model))) {
            return false;
        }

        if ($this->isLastHeadManager($model)) {
            return $this->denyLastHeadManager('Toto je posledný hlavný manažér kina - jeho deaktiváciou by kino zostalo bez správcu.');
        }

        return true;
    }

    /**
     * Accept or refuse a membership.
     *
     * Tier-checked like update() and delete(), which it was not: `user.approve` is a permission a
     * plain Manager holds, so refusing a membership was a way to unapprove and deactivate a
     * HeadManager - routing straight around canManageRole(). The buttons only appear for pending
     * members, but a Livewire call is not limited to what the buttons offer.
     */
    public function approve(User $user, User $model): bool|Response
    {
        $team = app(Team::class);

        if (! $this->inCurrentTeam($model) || ! $user->hasPermissionInTeam('user.approve', $team)) {
            return false;
        }

        if (! $this->canManageRole($user, $this->roleOf($model))) {
            return false;
        }

        if ($this->isLastHeadManager($model)) {
            return $this->denyLastHeadManager('Toto je posledný hlavný manažér kina - nemožno mu odobrať prístup.');
        }

        return true;
    }

    /** A refusal that explains itself; the 403 page prints the message. */
    private function denyLastHeadManager(string $message): Response
    {
        return Response::deny($message);
    }

    /**
     * Whether this person is the only one left who can administer the cinema.
     *
     * Demoting, deactivating or unapproving them would leave the team with no hlavný manažér at
     * all - and since promoting *into* that tier needs `user.manage-managers`, which only that
     * tier holds, there would be no way back short of database access. A HeadManager demoting
     * themselves is the likeliest route in, so this is checked regardless of who is acting.
     */
    private function isLastHeadManager(User $model): bool
    {
        $team = app(Team::class);

        if ($this->roleOf($model) !== Role::HeadManager) {
            return false;
        }

        return $team->activeHoldersOf(Role::HeadManager)
            ->reject(fn (User $holder): bool => $holder->getKey() === $model->getKey())
            ->isEmpty();
    }

    /**
     * The tenant boundary for every check above.
     *
     * `User` is the one model here that is not team-owned - it is shared across cinemas through
     * `team_user` - so nothing scopes these lookups automatically. Without this, holding
     * `user.update` in one cinema authorised editing, role-changing or deactivating *any*
     * account in the database by id.
     */
    private function inCurrentTeam(User $model): bool
    {
        return $model->isMemberOf(app(Team::class));
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
