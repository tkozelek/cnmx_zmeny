<?php

namespace App\Console\Commands;

use App\Enums\Role;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * Bring every role's permissions in line with RolePermissionSeeder.
 *
 * The permission set is application configuration, not sample data - it has to be deployed the
 * way code is. It only ever lived in a seeder, and seeders do not run on deploy, so a migrated
 * production database had a `manager` role with no permissions at all and a `head-manager` with
 * one. Since hasPermissionInTeam() has no role-name fallback, that meant nobody could reach the
 * user list, the rozpis builder, the week lock, positions or the cinema settings.
 *
 * Run this after any change to the permission set. It is idempotent - findOrCreate plus
 * syncPermissions - so running it twice does nothing the first run did not already do, and it
 * touches no user, team or role *assignment*, only what each role is allowed to do.
 */
class SyncRolePermissions extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Sync role permissions with the definition in RolePermissionSeeder';

    public function handle(): int
    {
        $this->components->info('Syncing role permissions…');

        (new RolePermissionSeeder)->setCommand($this)->run();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Role::cases() as $role) {
            $count = SpatieRole::where('name', $role->value)->where('guard_name', 'web')
                ->first()?->permissions()->count() ?? 0;

            $this->components->twoColumnDetail($role->value, "{$count} permissions");
        }

        return self::SUCCESS;
    }
}
