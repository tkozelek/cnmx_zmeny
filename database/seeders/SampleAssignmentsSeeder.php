<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\Assignment;
use App\Models\Position;
use App\Models\PositionSlot;
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
 * Past weeks are built and published, which is what gives FairnessService something to rank on -
 * an empty history scores everybody 0 and the builder's ordering says nothing. The current week
 * and the ones ahead get the day layouts and the signups but nobody placed: that is the week the
 * manager is meant to sit down and build.
 *
 * Re-runnable: users are matched on email, signups on (user, date), and a day that already has
 * slots is left exactly as it is.
 */
class SampleAssignmentsSeeder extends Seeder
{
    /** Under the default 12-week fairness window, so the whole history counts. */
    private const int WEEKS_BACK = 8;

    /** Within the default 5-week lookahead, or the builder could not navigate to them. */
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
        $positions = Position::where('team_id', $team->id)->get()->keyBy('code');
        $admin = $team->users()->get()
            ->first(fn (User $user): bool => $user->hasPermissionInTeam('assignment.lock', $team));

        $weeks = app(WeekService::class);
        $currentWeek = $weeks->start($team, CarbonImmutable::now());

        // Seeded writes stay out of the activity log: a thousand causer-less rows would bury the
        // real edits the rozpis history panel exists to show.
        activity()->withoutLogging(function () use ($team, $weeks, $currentWeek, $positions, $employees, $leaders, $admin): void {
            foreach (range(-self::WEEKS_BACK, self::WEEKS_AHEAD) as $offset) {
                $weekStart = $currentWeek->addWeeks($offset);
                $isHistory = $offset < 0;

                foreach ($weeks->days($weekStart) as $day) {
                    $slots = $this->layout($day, $positions);
                    $signups = $this->signUp($team, $employees, $leaders, $day, $slots->count());

                    if ($isHistory) {
                        $this->build($signups, $slots);
                    }
                }

                if ($isHistory && $admin) {
                    $this->publish($team, $weekStart, $admin);
                }
            }
        });

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
     * One day's rows, created only when the day has none - a day someone has already arranged by
     * hand is never topped up, or re-seeding would double every busy Friday.
     *
     * Friday to Sunday get a second pokladňa and a third bufet/uvádzač, which is the point of the
     * fairness weighting: the hard days are also the big ones.
     *
     * @param  Collection<string, Position>  $positions
     * @return Collection<int, PositionSlot>
     */
    private function layout(CarbonImmutable $day, Collection $positions): Collection
    {
        $existing = PositionSlot::with('position')->where('date', $day->toDateString())->get();

        if ($existing->isNotEmpty()) {
            return $existing;
        }

        $busy = in_array($day->dayOfWeekIso, [5, 6, 7], true);

        $wanted = [
            ['UV', $busy ? 3 : 2, '16:30'],
            ['BUF', $busy ? 3 : 2, '16:00'],
            ['POK', $busy ? 2 : 1, '15:30'],
            ['VED', 1, '15:00'],
        ];

        $slots = collect();
        $order = 0;

        foreach ($wanted as [$code, $count, $startTime]) {
            $position = $positions->get($code);

            if (! $position) {
                continue;
            }

            for ($i = 0; $i < $count; $i++) {
                $slots->push(PositionSlot::create([
                    'position_id' => $position->id,
                    'date' => $day->toDateString(),
                    'start_time' => $startTime,
                    'sort_order' => $order += 10,
                ]));
            }
        }

        return $slots;
    }

    /**
     * Who wrote themselves down for this day: a few more volunteers than there are rows, so the
     * builder always has a real choice to make and the leftovers show up as náhradníci.
     *
     * One shift leader is always among them, otherwise the vedúci row would have nobody legal to
     * put on it.
     *
     * @param  Collection<int, User>  $employees
     * @param  Collection<int, User>  $leaders
     * @return Collection<int, Assignment>
     */
    private function signUp(Team $team, Collection $employees, Collection $leaders, CarbonImmutable $day, int $capacity): Collection
    {
        $volunteers = $employees
            ->random(min($capacity + rand(1, 4), $employees->count()))
            ->concat($leaders->isEmpty() ? [] : [$leaders->random()]);

        return $volunteers->map(fn (User $user): Assignment => Assignment::firstOrCreate(
            [
                'team_id' => $team->id,
                'user_id' => $user->id,
                'date' => $day->toDateString(),
            ],
            [
                // Null created_by: they signed themselves up, which is how the pool fills in
                // reality. Placement happens afterwards, in build().
                'note' => rand(0, 4) === 0 ? 'môže až od '.rand(17, 19).':00' : null,
            ]
        ));
    }

    /**
     * Fill a past week's rows the way a manager would have: the vedúci row from whoever holds the
     * permission, the rest from the volunteers, and one row left open on some days because that is
     * what a real week looks like.
     *
     * @param  Collection<int, Assignment>  $signups
     * @param  Collection<int, PositionSlot>  $slots
     */
    private function build(Collection $signups, Collection $slots): void
    {
        $pool = $signups->filter(fn (Assignment $a): bool => $a->position_slot_id === null)->shuffle();

        foreach ($slots as $slot) {
            if ($slot->occupant()->exists()) {
                continue;
            }

            $candidate = $slot->position->is_manager
                ? $pool->first(fn (Assignment $a): bool => $a->user->hasRole(RoleEnum::Manager->value))
                // Every fifth ordinary row stays empty - an unfilled day is a state worth seeing.
                : (rand(1, 5) === 1 ? null : $pool->first());

            if (! $candidate) {
                continue;
            }

            $candidate->update([
                'position_id' => $slot->position_id,
                'position_slot_id' => $slot->getKey(),
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
            ]);

            $pool = $pool->reject(fn (Assignment $a): bool => $a->is($candidate));
        }
    }

    /** A finished week is a locked, published one - that is the state the export reads. */
    private function publish(Team $team, CarbonImmutable $weekStart, User $admin): void
    {
        WeekLock::firstOrCreate(
            ['team_id' => $team->id, 'week_start' => $weekStart->toDateString()],
            [
                'locked_by' => $admin->id,
                'rozpis_published_at' => $weekStart->addDays(6),
                'published_by' => $admin->id,
            ]
        );
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
