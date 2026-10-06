<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The three roles every cinema team has, and what each one may do.
 *
 * Single source of truth for both production (called from DatabaseSeeder) and tests
 * (called from Tests\TestCase::tenant()), so a test actor's permissions never drift from
 * what a real HeadManager/Manager/Employee actually gets - hasPermissionInTeam() has no
 * role-name bypass, so a role with nothing synced to it can do nothing.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        $absencePermissions = [
            'absence.view',
            'absence.create',
            'absence.manage-own',
            'absence.delete-own',
            'absence.delete-inactive',
            'absence.manage',
        ];

        $userPermissions = [
            'user.view-any',
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
            'user.approve',

            // Editing or role-changing a Manager/HeadManager account, or promoting anyone into
            // one - the one thing a plain Manager may not do. See UserPolicy.
            'user.manage-managers',
        ];

        $teamPermissions = [
            'team.view',
            'team.update',
        ];

        $assignmentPermissions = [
            'assignment.create',
            'assignment.delete',
            'assignment.lock',
        ];

        $allPermissions = array_merge(
            $absencePermissions,
            $userPermissions,
            $teamPermissions,
            $assignmentPermissions,
        );

        foreach ($allPermissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        foreach (RoleEnum::cases() as $role) {
            $spatieRole = Role::findOrCreate($role->value, 'web');

            $spatieRole->syncPermissions(match ($role) {
                // Runs the cinema, including the managers who run it.
                RoleEnum::HeadManager => $allPermissions,

                // Runs the cinema day to day - schedule, locking weeks, hiring/accepting
                // brigádnici - but may not touch another manager's account or promote anyone
                // into that tier. That is the one thing HeadManager keeps for itself.
                RoleEnum::Manager => array_values(array_diff($allPermissions, ['user.manage-managers'])),

                RoleEnum::Employee => [
                    'absence.create',
                    'absence.manage-own',
                    'absence.delete-own',
                    'user.view',
                ],
            });
        }
    }
}
