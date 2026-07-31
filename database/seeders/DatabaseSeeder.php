<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\Position;
use App\Models\Team;
use App\Models\TeamSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds all 14 official Cinemax cinema locations in Slovakia.
 * Makes 'Žilina Max' the default active cinema.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRoles();

        $cinemaLocations = [
            'Žilina Max',
            'Banská Bystrica Europa SC',
            'Bratislava Bory Mall',
            'Dunajská Streda Max',
            'Košice Optima',
            'Martin Tulip',
            'Nitra Max',
            'Poprad Max',
            'Prešov Max',
            'Prešov Novum',
            'Skalica Max',
            'Trenčín Max',
            'Trnava Aréna',
            'Trnava Max',
        ];

        $teams = collect();
        $defaultTeam = null;

        foreach ($cinemaLocations as $name) {
            $team = Team::create([
                'name' => $name,
                'slug' => Str::slug($name),
            ]);

            TeamSetting::create(['team_id' => $team->id]);

            app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
            $this->seedPositions();

            $teams->put($name, $team);

            if ($name === 'Žilina Max') {
                $defaultTeam = $team;
            }
        }

        $defaultTeam ??= $teams->first();

        app(PermissionRegistrar::class)->setPermissionsTeamId($defaultTeam->id);

        $tommy = $this->member($defaultTeam, 'Tomáš', 'Kozelek', 'tommyside@centrum.sk', RoleEnum::Admin, 'asdasd');
        $admin = $this->member($defaultTeam, 'Admin', 'Kina', 'admin@kino.test', RoleEnum::Admin);
        $jana = $this->member($defaultTeam, 'Jana', 'Nováková', 'jana@kino.test', RoleEnum::Employee);
        $peter = $this->member($defaultTeam, 'Peter', 'Horváth', 'peter@kino.test', RoleEnum::Employee);

        // Attach users to multiple teams for testing team switcher dropdown
        $extraTeams = $teams->where('id', '!=', $defaultTeam->id)->take(4);
        foreach ($extraTeams as $t) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($t->id);

            $tommy->teams()->attach($t->id, ['approved_at' => now()]);
            $tommy->assignRole(RoleEnum::Admin->value);

            $admin->teams()->attach($t->id, ['approved_at' => now()]);
            $admin->assignRole(RoleEnum::Admin->value);

            $jana->teams()->attach($t->id, ['approved_at' => now()]);
            $jana->assignRole(RoleEnum::Employee->value);
        }

        $this->call(SampleAssignmentsSeeder::class);

        $this->command?->info('Žilina Max nastavené ako hlavné kino.');
        $this->command?->info("Prihlás sa ako {$tommy->email} s heslom 'asdasd'.");
    }

    private function seedRoles(): void
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
        ];

        $teamPermissions = [
            'team.view',
            'team.update',
        ];

        /**
         * These were checked by AssignmentPolicy/MediaPolicy but never seeded — they only
         * worked because hasPermissionInTeam() bypasses the check entirely for admin and
         * manager. Seeding them for real means that bypass stops being load-bearing.
         *
         * `assignment.create` and `assignment.delete` deliberately stay off the employee role:
         * both act as lock overrides (see AssignmentPolicy::create), so granting them would let
         * an employee sign up for a frozen week.
         */
        $assignmentPermissions = [
            'assignment.create',
            'assignment.delete',
            'assignment.lock',
            'assignment.assign-position',
        ];

        $mediaPermissions = [
            'media.view',
            'media.create',
            'media.update',
            'media.delete',
        ];

        $positionPermissions = [
            'position.view-any',
            'position.create',
            'position.update',
            'position.delete',
        ];

        $allPermissions = array_merge(
            $absencePermissions,
            $userPermissions,
            $teamPermissions,
            $assignmentPermissions,
            $mediaPermissions,
            $positionPermissions,
        );

        foreach ($allPermissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        foreach (RoleEnum::cases() as $role) {
            $spatieRole = Role::findOrCreate($role->value, 'web');

            if ($role === RoleEnum::Admin || $role === RoleEnum::Manager) {
                $spatieRole->syncPermissions($allPermissions);
            } elseif ($role === RoleEnum::Employee) {
                $spatieRole->syncPermissions([
                    'absence.create',
                    'absence.manage-own',
                    'absence.delete-own',
                    'user.view',
                ]);
            }
        }
    }

    private function seedPositions(): void
    {
        $positions = [
            ['name' => 'Uvádzač', 'code' => 'UV', 'sort_order' => 10],
            ['name' => 'Bufet', 'code' => 'BUF', 'sort_order' => 20],
            ['name' => 'Pokladňa', 'code' => 'POK', 'sort_order' => 30],
            ['name' => 'Vedúci', 'code' => 'VED', 'sort_order' => 40, 'is_manager' => true],
        ];

        foreach ($positions as $position) {
            Position::create($position);
        }
    }

    private function member(
        Team $team,
        string $name,
        string $lastname,
        string $email,
        RoleEnum $role,
        string $password = 'password',
    ): User {
        $user = User::create([
            'name' => $name,
            'lastname' => $lastname,
            'email' => $email,
            'password' => Hash::make($password),
            'current_team_id' => $team->id,
        ]);

        $user->teams()->attach($team, ['approved_at' => now()]);
        $user->assignRole($role->value);

        return $user;
    }
}
