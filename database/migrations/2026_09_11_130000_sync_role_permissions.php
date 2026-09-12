<?php

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/**
 * Apply the role permission set to databases that were migrated but never seeded.
 *
 * The set only ever lived in RolePermissionSeeder, and seeders do not run on deploy, so a
 * production database ended up with `manager` holding no permissions and `head-manager` holding
 * only the one the role-consolidation migration granted it. hasPermissionInTeam() has no
 * role-name fallback, so in practice neither role could do anything: not the user list, the
 * rozpis builder, the week lock, positions, or the cinema settings.
 *
 * Deliberately delegates to the seeder rather than restating the set, which does mean this
 * migration's behaviour follows the seeder as it changes. That is the intent - "make permissions
 * match the current definition" is the only useful thing it could do, and the alternative (a
 * frozen copy) is what created the drift in the first place. `permissions:sync` is the same
 * operation for the times a change lands without a migration.
 *
 * Idempotent: findOrCreate plus syncPermissions. Touches no role *assignment*, so nobody's role
 * changes - only what each role may do.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new RolePermissionSeeder)->run();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Not reversible, and should not be: stripping a role's permissions to "fix" a rollback
        // would lock every manager out of the application.
    }
};
