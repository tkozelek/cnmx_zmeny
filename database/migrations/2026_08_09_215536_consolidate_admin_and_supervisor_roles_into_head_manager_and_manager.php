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
        // Selected via join first to avoid MySQL error 1093 (cannot delete from table referenced in subquery FROM clause).
        $collidingRows = DB::table("{$modelHasRolesTable} as old")
            ->join("{$modelHasRolesTable} as existing", function ($join) use ($newRole, $teamKey) {
                $join->on('existing.model_id', '=', 'old.model_id')
                    ->on('existing.model_type', '=', 'old.model_type')
                    ->on("existing.{$teamKey}", '=', "old.{$teamKey}")
                    ->where('existing.role_id', '=', $newRole->id);
            })
            ->where('old.role_id', $oldRole->id)
            ->select('old.model_id', 'old.model_type', "old.{$teamKey}")
            ->get();

        foreach ($collidingRows as $row) {
            $query = DB::table($modelHasRolesTable)
                ->where('role_id', $oldRole->id)
                ->where('model_id', $row->model_id)
                ->where('model_type', $row->model_type);

            if ($row->{$teamKey} === null) {
                $query->whereNull($teamKey);
            } else {
                $query->where($teamKey, $row->{$teamKey});
            }

            $query->delete();
        }

        DB::table($modelHasRolesTable)
            ->where('role_id', $oldRole->id)
            ->update(['role_id' => $newRole->id]);

        $oldRole->delete();
    }
};
