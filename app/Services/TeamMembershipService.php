<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Team;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Membership and per-cinema role changes made from outside any cinema (the platform panel), where
 * the registrar holds no team and every role write has to be bracketed to the right one.
 */
class TeamMembershipService
{
    public function __construct(
        private readonly PermissionRegistrar $registrar,
        private readonly PlatformAudit $audit,
    ) {}

    public function roleOf(User $user, Team $team): ?Role
    {
        $roleName = DB::table(config('permission.table_names.model_has_roles'))
            ->join(config('permission.table_names.roles').' as roles', 'roles.id', '=', 'role_id')
            ->where('model_type', $user->getMorphClass())
            ->where('model_id', $user->getKey())
            ->where(config('permission.table_names.model_has_roles').'.team_id', $team->getKey())
            ->value('roles.name');

        return Role::tryFrom($roleName ?? '');
    }

    public function add(User $user, Team $team, Role $role): void
    {
        $user->teams()->syncWithoutDetaching([$team->getKey() => ['approved_at' => now()]]);
        $this->inTeam($team, $user, fn () => $user->syncRoles([$role->value]));
        $user->forgetApprovedTeamsCache();

        $this->audit->record('membership.added', $team, ['user_id' => $user->getKey(), 'role' => $role->value]);
    }

    public function approve(User $user, Team $team): void
    {
        $user->teams()->updateExistingPivot($team->getKey(), ['approved_at' => now()]);

        if ($this->roleOf($user, $team) === null) {
            $this->inTeam($team, $user, fn () => $user->syncRoles([Role::Employee->value]));
        }

        $user->forgetApprovedTeamsCache();

        $this->audit->record('membership.approved', $team, ['user_id' => $user->getKey()]);
    }

    public function changeRole(User $user, Team $team, Role $role): void
    {
        if ($role !== Role::HeadManager) {
            $this->ensureNotLastHeadManager($user, $team);
        }

        $this->inTeam($team, $user, fn () => $user->syncRoles([$role->value]));

        $this->audit->record('membership.role-changed', $team, ['user_id' => $user->getKey(), 'role' => $role->value]);
    }

    public function remove(User $user, Team $team): void
    {
        $this->ensureNotLastHeadManager($user, $team);

        $this->inTeam($team, $user, fn () => $user->syncRoles([]));
        $user->teams()->detach($team->getKey());

        if ($user->current_team_id === $team->getKey()) {
            $user->forceFill(['current_team_id' => null])->save();
        }

        $user->forgetApprovedTeamsCache();

        $this->audit->record('membership.removed', $team, ['user_id' => $user->getKey()]);
    }

    private function ensureNotLastHeadManager(User $user, Team $team): void
    {
        if ($this->roleOf($user, $team) !== Role::HeadManager) {
            return;
        }

        $others = $team->activeHoldersOf(Role::HeadManager)->reject(fn (User $holder): bool => $holder->is($user));

        if ($others->isEmpty()) {
            throw new DomainException('Toto je posledný hlavný manažér kina – najprv vymenuj ďalšieho.');
        }
    }

    /** Spatie caches `roles` on the model per team, so the relation is dropped on both sides of the switch. */
    private function inTeam(Team $team, User $user, callable $callback): void
    {
        $originalTeamId = $this->registrar->getPermissionsTeamId();
        $this->registrar->setPermissionsTeamId($team->getKey());
        $user->unsetRelation('roles')->unsetRelation('permissions');

        try {
            $callback();
        } finally {
            $this->registrar->setPermissionsTeamId($originalTeamId);
            $user->unsetRelation('roles')->unsetRelation('permissions');
        }
    }
}
