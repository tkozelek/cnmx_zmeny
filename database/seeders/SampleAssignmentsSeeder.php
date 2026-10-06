<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\Assignment;
use App\Models\Team;
use App\Models\User;
use App\Models\WeekLock;
use App\Services\WeekService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * A cinema with a history: a roster big enough to choose from, and every week from
 * WEEKS_BACK ago to WEEKS_AHEAD from now already signed up for.
 *
 * Past weeks are locked, the current week and the ones ahead are still open for signup.
 *
 * Re-runnable: users are matched on email, signups on (user, date).
 */
class SampleAssignmentsSeeder extends Seeder
{
    private const int WEEKS_BACK = 8;

    /** Within the default 5-week lookahead, or the calendar could not navigate to them. */
    private const int WEEKS_AHEAD = 3;

    public function run(): void
    {
        $team = Team::first();

        if (! $team) {
            return;
        }

        // Seeders run outside a request, so nothing has set the team the tenant scope reads -
        // without this, team_id is not filled on create and every lookup is unscoped.
        setPermissionsTeamId($team->id);

        $employees = $this->roster($team, $this->employeeNames(), RoleEnum::Employee);
        $leaders = $this->roster($team, $this->managerNames(), RoleEnum::Manager);
        $admin = $team->users()->get()
            ->first(fn (User $user): bool => $user->hasPermissionInTeam('assignment.lock', $team));

        $weeks = app(WeekService::class);
        $currentWeek = $weeks->start($team, CarbonImmutable::now());

        foreach (range(-self::WEEKS_BACK, self::WEEKS_AHEAD) as $offset) {
            $weekStart = $currentWeek->addWeeks($offset);

            foreach ($weeks->days($weekStart) as $day) {
                $this->signUp($team, $employees, $day);
            }

            if ($offset < 0 && $admin) {
                WeekLock::firstOrCreate(
                    ['team_id' => $team->id, 'week_start' => $weekStart->toDateString()],
                    ['locked_by' => $admin->id],
                );
            }
        }

        $this->command?->info(sprintf(
            '%d týždňov histórie a %d týždňov dopredu pre %s (%d ľudí v tíme).',
            self::WEEKS_BACK,
            self::WEEKS_AHEAD + 1,
            $team->name,
            $employees->count() + $leaders->count(),
        ));
    }

    /**
     * Approved members of the cinema, created if they are not there yet.
     *
     * `syncWithoutDetaching` rather than `attach`, so a member seeded earlier without an
     * `approved_at` is approved now instead of staying stuck in the pending queue.
     *
     * @param  list<array{0: string, 1: string}>  $names
     * @return Collection<int, User>
     */
    private function roster(Team $team, array $names, RoleEnum $role): Collection
    {
        return collect($names)->map(function (array $person) use ($team, $role): User {
            [$name, $lastname] = $person;

            $user = User::firstOrCreate(
                ['email' => Str::slug($name.'.'.$lastname, '.').'@cinemax.sk'],
                [
                    'name' => $name,
                    'lastname' => $lastname,
                    'password' => 'password',
                    'current_team_id' => $team->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $user->teams()->syncWithoutDetaching([$team->id => ['approved_at' => now()]]);
            $user->assignRole($role->value);

            return $user;
        });
    }

    /**
     * Who wrote themselves down for this day - more on the busy Friday to Sunday.
     *
     * @param  Collection<int, User>  $employees
     */
    private function signUp(Team $team, Collection $employees, CarbonImmutable $day): void
    {
        $wanted = in_array($day->dayOfWeekIso, [5, 6, 7], true) ? rand(9, 13) : rand(6, 10);

        $employees->random(min($wanted, $employees->count()))->each(fn (User $user): Assignment => Assignment::firstOrCreate(
            [
                'team_id' => $team->id,
                'user_id' => $user->id,
                'date' => $day->toDateString(),
            ],
            [
                // Null created_by: they signed themselves up.
                'note' => rand(0, 4) === 0 ? 'môže až od '.rand(17, 19).':00' : null,
            ]
        ));
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function employeeNames(): array
    {
        return [
            ['Martin', 'Kováč'],
            ['Anna', 'Horváthová'],
            ['Zoltán', 'Varga'],
            ['Eva', 'Nagyová'],
            ['Tomáš', 'Molnár'],
            ['Peter', 'Szabó'],
            ['Lucia', 'Balážová'],
            ['Michal', 'Kráľ'],
            ['Simona', 'Tóthová'],
            ['Jakub', 'Šimko'],
            ['Veronika', 'Danišová'],
            ['Filip', 'Bartoš'],
            ['Natália', 'Gažová'],
            ['Adam', 'Sedlák'],
            ['Kristína', 'Poláková'],
            ['Dominik', 'Hruška'],
            ['Barbora', 'Mrázová'],
            ['Patrik', 'Lukáč'],
            ['Denisa', 'Sabová'],
            ['Marek', 'Turčan'],
            ['Ivana', 'Krajčíková'],
            ['Samuel', 'Beňo'],
            ['Klaudia', 'Vlčková'],
            ['Richard', 'Zeman'],
        ];
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function managerNames(): array
    {
        return [
            ['Andrea', 'Dubovská'],
            ['Róbert', 'Hlaváč'],
        ];
    }
}
