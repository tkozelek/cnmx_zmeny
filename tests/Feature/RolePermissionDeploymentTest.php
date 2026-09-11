<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * What `php artisan migrate` alone leaves behind.
 *
 * The permission set only ever lived in RolePermissionSeeder, and seeders do not run on deploy,
 * so the live database had a `manager` role with no permissions and a `head-manager` with one.
 * Every test in this suite calls tenant(), which seeds explicitly - so nothing here ever noticed.
 * These cases deliberately do not, which is the only way to catch it.
 */
class RolePermissionDeploymentTest extends TestCase
{
    use RefreshDatabase;

    /** The permissions a manager needs to do the job the role exists for. */
    private const MANAGER_NEEDS = [
        'user.view-any',
        'user.create',
        'user.update',
        'user.approve',
        'assignment.assign-position',
        'assignment.lock',
        'position.view-any',
        'team.view',
        'team.update',
        'media.create',
        'absence.view',
    ];

    public function test_migrations_alone_give_a_manager_a_working_role(): void
    {
        $manager = $this->migratedOnlyMember(Role::Manager);

        foreach (self::MANAGER_NEEDS as $permission) {
            $this->assertTrue(
                $manager->hasPermissionInTeam($permission, $manager->currentTeam),
                "A manager on a migrated-but-unseeded database is missing {$permission}.",
            );
        }
    }

    public function test_migrations_alone_give_a_head_manager_the_manager_tier_too(): void
    {
        $headManager = $this->migratedOnlyMember(Role::HeadManager);

        $this->assertTrue($headManager->hasPermissionInTeam('user.view-any', $headManager->currentTeam));
        $this->assertTrue($headManager->hasPermissionInTeam('user.manage-managers', $headManager->currentTeam));
    }

    /** And the tiers stay apart: the one thing a plain manager must not have. */
    public function test_migrations_alone_still_withhold_manager_administration_from_a_manager(): void
    {
        $manager = $this->migratedOnlyMember(Role::Manager);

        $this->assertFalse($manager->hasPermissionInTeam('user.manage-managers', $manager->currentTeam));
    }

    public function test_an_employee_gets_nothing_beyond_their_own_affairs(): void
    {
        $employee = $this->migratedOnlyMember(Role::Employee);
        $team = $employee->currentTeam;

        $this->assertTrue($employee->hasPermissionInTeam('absence.create', $team));
        $this->assertFalse($employee->hasPermissionInTeam('user.view-any', $team));
        $this->assertFalse($employee->hasPermissionInTeam('assignment.assign-position', $team));
    }

    /**
     * A member built without calling tenant() - so the roles and permissions are only whatever
     * the migrations put there.
     */
    private function migratedOnlyMember(Role $role): User
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        $team = Team::factory()->create();
        $registrar->setPermissionsTeamId($team->getKey());
        app()->instance(Team::class, $team);

        $user = User::factory()->create(['current_team_id' => $team->getKey()]);
        $user->teams()->attach($team, ['approved_at' => now()]);
        $user->assignRole($role->value);

        return $user;
    }
}
