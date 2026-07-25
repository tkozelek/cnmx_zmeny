<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Position;
use App\Models\Team;
use App\Models\User;
use App\Services\WeekService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SampleAssignmentsSeeder extends Seeder
{
    public function run(): void
    {
        $team = Team::first();
        if (! $team) {
            return;
        }

        $weekService = app(WeekService::class);
        $currentWeek = $weekService->start($team, CarbonImmutable::now());
        $days = $weekService->days($currentWeek);

        $sampleNames = [
            ['Martin', 'Kováč'],
            ['Anna', 'Horváthová'],
            ['Zoltán', 'Varga'],
            ['Eva', 'Nagyová'],
            ['Tomáš', 'Molnár'],
            ['Peter', 'Szabó'],
            ['Lucia', 'Balážová'],
            ['Michal', 'Kráľ'],
        ];

        $createdUsers = collect();

        foreach ($sampleNames as [$name, $lastname]) {
            $email = Str::slug($name.'.'.$lastname).'@cinemax.sk';
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'lastname' => $lastname,
                    'password' => bcrypt('password'),
                    'current_team_id' => $team->id,
                    'is_active' => true,
                ]
            );

            if (! $user->teams()->where('team_id', $team->id)->exists()) {
                $user->teams()->attach($team->id);
            }

            $createdUsers->push($user);
        }

        $positions = Position::where('team_id', $team->id)->get();

        foreach ($days as $day) {
            $dateStr = $day->toDateString();
            $assignedUsers = $createdUsers->random(min(rand(3, 6), $createdUsers->count()));

            foreach ($assignedUsers as $u) {
                Assignment::updateOrCreate(
                    [
                        'team_id' => $team->id,
                        'user_id' => $u->id,
                        'date' => $dateStr,
                    ],
                    [
                        'position_id' => $positions->isNotEmpty() && rand(0, 1) ? $positions->random()->id : null,
                        'note' => rand(0, 1) ? 'od '.rand(14, 18).':00' : null,
                    ]
                );
            }
        }
    }
}
