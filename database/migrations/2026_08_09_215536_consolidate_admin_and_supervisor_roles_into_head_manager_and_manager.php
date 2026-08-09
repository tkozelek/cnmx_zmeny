<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The role system moved from 5 roles (admin, head-manager, manager, supervisor, employee) to
 * 3 (head-manager, manager, employee) - see App\Enums\Role. Existing `admin` holders become
 * `head-manager`, existing `supervisor` holders become `manager`, since both are strict subsets
 * of what those roles could already do.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $teamKey = config('permission.column_names.team_foreign_key');

        Permission::findOrCreate('user.manage-managers', 'web');

        $headManager = Role::findOrCreate('head-manager', 'web');
        $manager = Role::findOrCreate('manager', 'web');
        $headManager->givePermissionTo('user.manage-managers');

        $this->mergeRole('admin', $headManager, $tableNames['model_has_roles'], $teamKey);
        $this->mergeRole('supervisor', $manager, $tableNames['model_has_roles'], $teamKey);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Not reversible: admin and supervisor assignments are merged into head-manager/manager
        // above, and which merged row used to be which role is no longer recorded anywhere.
    }

    private function mergeRole(string $oldRoleName, Role $newRole, string $modelHasRolesTable, string $teamKey): void
    {
        $oldRole = Role::where('name', $oldRoleName)->where('guard_name', 'web')->first();

        if (! $oldRole) {
            return;
        }

        // A user already holding $newRole in the same team would collide on the composite
        // primary key [team_id, role_id, model_id, model_type] if the old row were updated
        // in place, so those duplicates are dropped instead of merged.
        DB::table($modelHasRolesTable)
            ->where('role_id', $oldRole->id)
            ->whereExists(function ($query) use ($modelHasRolesTable, $newRole, $teamKey) {
                $query->selectRaw('1')
                    ->from("{$modelHasRolesTable} as existing")
                    ->whereColumn('existing.model_id', "{$modelHasRolesTable}.model_id")
                    ->whereColumn('existing.model_type', "{$modelHasRolesTable}.model_type")
                    ->whereColumn("existing.{$teamKey}", "{$modelHasRolesTable}.{$teamKey}")
                    ->where('existing.role_id', $newRole->id);
            })
            ->delete();

        DB::table($modelHasRolesTable)
            ->where('role_id', $oldRole->id)
            ->update(['role_id' => $newRole->id]);

        $oldRole->delete();
    }
};
