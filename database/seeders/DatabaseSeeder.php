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
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds all 14 official Cinemax cinema locations in Slovakia.
 * Makes 'Žilina Max' the default active cinema.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

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

        $tommy = $this->member($defaultTeam, 'Tomáš', 'Kozelek', 'tommyside@centrum.sk', RoleEnum::HeadManager, 'asdasd');
        $admin = $this->member($defaultTeam, 'Admin', 'Kina', 'admin@kino.test', RoleEnum::HeadManager);
        $jana = $this->member($defaultTeam, 'Jana', 'Nováková', 'jana@kino.test', RoleEnum::Employee);
        $peter = $this->member($defaultTeam, 'Peter', 'Horváth', 'peter@kino.test', RoleEnum::Employee);

        // Attach users to multiple teams for testing team switcher dropdown
        $extraTeams = $teams->where('id', '!=', $defaultTeam->id)->take(4);
        foreach ($extraTeams as $t) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($t->id);

            $tommy->teams()->attach($t->id, ['approved_at' => now()]);
            $tommy->assignRole(RoleEnum::HeadManager->value);

            $admin->teams()->attach($t->id, ['approved_at' => now()]);
            $admin->assignRole(RoleEnum::HeadManager->value);

            $jana->teams()->attach($t->id, ['approved_at' => now()]);
            $jana->assignRole(RoleEnum::Employee->value);
        }

        $this->call(SampleAssignmentsSeeder::class);

        $this->command?->info('Žilina Max nastavené ako hlavné kino.');
        $this->command?->info("Prihlás sa ako {$tommy->email} s heslom 'asdasd'.");
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
