<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleConsolidationMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_consolidates_roles_and_cleans_up_duplicates(): void
    {
        $team = $this->tenant();
        $registrar = app(PermissionRegistrar::class);

        // Setup old roles
        $adminRole = Role::findOrCreate('admin', 'web');
        $supervisorRole = Role::findOrCreate('supervisor', 'web');
        $headManagerRole = Role::findOrCreate('head-manager', 'web');
        $managerRole = Role::findOrCreate('manager', 'web');

        $userWithBothAdminAndHeadManager = User::factory()->create(['current_team_id' => $team->id]);
        $userWithOnlyAdmin = User::factory()->create(['current_team_id' => $team->id]);
        $userWithBothSupervisorAndManager = User::factory()->create(['current_team_id' => $team->id]);
        $userWithOnlySupervisor = User::factory()->create(['current_team_id' => $team->id]);

        $registrar->setPermissionsTeamId($team->id);

        // Assign old & new roles
        $userWithBothAdminAndHeadManager->assignRole($adminRole, $headManagerRole);
        $userWithOnlyAdmin->assignRole($adminRole);
        $userWithBothSupervisorAndManager->assignRole($supervisorRole, $managerRole);
        $userWithOnlySupervisor->assignRole($supervisorRole);

        // Run the migration
        $migration = require database_path('migrations/2026_08_09_215536_consolidate_admin_and_supervisor_roles_into_head_manager_and_manager.php');
        $migration->up();

        // Check old roles deleted
        $this->assertNull(Role::where('name', 'admin')->where('guard_name', 'web')->first());
        $this->assertNull(Role::where('name', 'supervisor')->where('guard_name', 'web')->first());

        $registrar->forgetCachedPermissions();

        // Check user roles
        $this->assertTrue($userWithBothAdminAndHeadManager->fresh()->hasRole('head-manager'));
        $this->assertTrue($userWithOnlyAdmin->fresh()->hasRole('head-manager'));
        $this->assertTrue($userWithBothSupervisorAndManager->fresh()->hasRole('manager'));
        $this->assertTrue($userWithOnlySupervisor->fresh()->hasRole('manager'));

        // Check model_has_roles row counts
        $adminHeadRows = DB::table('model_has_roles')
            ->where('model_id', $userWithBothAdminAndHeadManager->id)
            ->where('team_id', $team->id)
            ->count();
        $this->assertSame(1, $adminHeadRows);

        $supManRows = DB::table('model_has_roles')
            ->where('model_id', $userWithBothSupervisorAndManager->id)
            ->where('team_id', $team->id)
            ->count();
        $this->assertSame(1, $supManRows);
    }
}
