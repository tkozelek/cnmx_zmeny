<?php

namespace Tests\Unit;

use App\Models\Assignment;
use App\Models\Position;
use App\Models\Team;
use App\Models\User;
use App\Services\FairnessService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The scoring formula is the one piece of this feature a reader cannot verify by eye, so it gets
 * pinned down here.
 *
 * What the numbers mean, with the default weights [1, 1, 1, 1, 1.6, 0.8, 0.8]: a day's weight is
 * how hard it is to fill. Friday (1.6) is the shift nobody volunteers for; the weekend (0.8) is
 * sought after, which is why it sits *below* an ordinary weekday rather than above it.
 *
 * So `priorityScore = totalDays × (ceiling − avgWeight)` answers "how much does this person owe
 * the team a hard day": high for a regular whose shifts have been the easy ones, low for someone
 * who already takes the Fridays, and low for anyone who barely works at all.
 */
class FairnessServiceTest extends TestCase
{
    use RefreshDatabase;

    /** A Thursday, so every "previous Friday/Saturday/Monday" sits behind it. */
    private const string AS_OF = '2026-07-30';

    /**
     * The reward/penalty behaviour: at equal volume, the person who has been taking the popular
     * shifts is ahead in the queue for the unpopular one.
     */
    public function test_someone_who_avoids_fridays_is_offered_the_next_one_first(): void
    {
        $team = $this->tenant();
        $position = Position::factory()->create(['team_id' => $team->id]);

        $takesFridays = $this->member($team);
        $avoidsFridays = $this->member($team);

        // 6 Fridays (1.6) + 6 Saturdays (0.8) => avgWeight 1.2
        $this->worked($takesFridays, $position, $this->weekdays('friday', 6));
        $this->worked($takesFridays, $position, $this->weekdays('saturday', 6));

        // 12 Saturdays and not one Friday => avgWeight 0.8, the cheapest day there is
        $this->worked($avoidsFridays, $position, $this->weekdays('saturday', 12));

        $scores = app(FairnessService::class)->scores($team, CarbonImmutable::parse(self::AS_OF));

        $this->assertSame(12, $scores[$takesFridays->id]['totalDays']);
        $this->assertSame(12, $scores[$avoidsFridays->id]['totalDays']);

        $this->assertSame(1.2, $scores[$takesFridays->id]['avgWeight']);
        $this->assertSame(
            0.8,
            $scores[$avoidsFridays->id]['avgWeight'],
            'A weekend shift must weigh less than an ordinary weekday.',
        );

        // ceiling = max(weights) + 1 = 2.6 → 12 × 1.4 = 16.8 vs 12 × 1.8 = 21.6
        $this->assertSame(16.8, $scores[$takesFridays->id]['priorityScore']);
        $this->assertSame(21.6, $scores[$avoidsFridays->id]['priorityScore']);

        $this->assertGreaterThan(
            $scores[$takesFridays->id]['priorityScore'],
            $scores[$avoidsFridays->id]['priorityScore'],
            'Whoever has taken fewer of the hard days is next in line for one.',
        );
    }

    /**
     * Volume is a real gate, not just a ratio: two people with an identical avgWeight are not
     * equally in line, because one of them barely works here.
     */
    public function test_volume_separates_two_people_with_the_same_average_day(): void
    {
        $team = $this->tenant();
        $position = Position::factory()->create(['team_id' => $team->id]);

        $regular = $this->member($team);
        $occasional = $this->member($team);

        $this->worked($regular, $position, $this->weekdays('monday', 12));
        $this->worked($occasional, $position, $this->weekdays('monday', 2));

        $scores = app(FairnessService::class)->scores($team, CarbonImmutable::parse(self::AS_OF));

        // The same kind of day, so the same avgWeight - only the volume differs.
        $this->assertSame(1.0, $scores[$regular->id]['avgWeight']);
        $this->assertSame(1.0, $scores[$occasional->id]['avgWeight']);

        $this->assertSame(19.2, $scores[$regular->id]['priorityScore']);
        $this->assertSame(3.2, $scores[$occasional->id]['priorityScore']);
    }

    /** The window is a per-team dial, so widening it must change what is counted. */
    public function test_assignments_older_than_the_window_are_excluded(): void
    {
        $team = $this->tenant();
        $position = Position::factory()->create(['team_id' => $team->id]);
        $user = $this->member($team);

        $asOf = CarbonImmutable::parse(self::AS_OF);

        $this->worked($user, $position, [
            $asOf->subWeek()->toDateString(),
            $asOf->subWeeks(20)->toDateString(),
        ]);

        $scores = app(FairnessService::class)->scores($team, $asOf);
        $this->assertSame(1, $scores[$user->id]['totalDays'], 'The 20-week-old day is outside the default 12-week window.');

        $this->widenWindow($team, 30);

        $scores = app(FairnessService::class)->scores($team, $asOf);
        $this->assertSame(2, $scores[$user->id]['totalDays'], 'Widening fairness_window_weeks must pull the older day back in.');
    }

    /** Signed up but never placed on a position is not work done, so it must not score. */
    public function test_unplaced_signups_do_not_count(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);

        Assignment::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'position_id' => null,
            'date' => CarbonImmutable::parse(self::AS_OF)->subWeek()->toDateString(),
        ]);

        $scores = app(FairnessService::class)->scores($team, CarbonImmutable::parse(self::AS_OF));

        $this->assertFalse($scores->has($user->id));
    }

    /**
     * Which days get the "neobľúbený" treatment - the ranked pool and the score badge.
     *
     * The threshold is the week's own average, not its cheapest day: pricing the weekend *below*
     * an ordinary weekday (which the defaults do) would otherwise drag the floor down and leave
     * Monday through Friday all flagged, which is no signal at all.
     */
    public function test_only_days_above_the_week_average_count_as_hard_to_staff(): void
    {
        $team = $this->tenant();
        $fairness = app(FairnessService::class);

        // Defaults [1, 1, 1, 1, 1.6, 0.8, 0.8] average 1.03 - Friday alone clears it.
        $this->assertTrue($fairness->isHardToStaffDay($team, CarbonImmutable::parse('2026-07-31')), 'Friday');
        $this->assertFalse($fairness->isHardToStaffDay($team, CarbonImmutable::parse('2026-08-03')), 'Monday');
        $this->assertFalse($fairness->isHardToStaffDay($team, CarbonImmutable::parse('2026-08-01')), 'Saturday');

        // A cinema that cannot fill its weekend: average 1.36, so Fri/Sat/Sun clear it and the
        // ordinary weekdays - the whole point of the change - do not.
        $this->setWeights($team, [1, 1, 1, 1, 1.5, 2, 2]);

        $this->assertTrue($fairness->isHardToStaffDay($team, CarbonImmutable::parse('2026-08-01')), 'Saturday');
        $this->assertTrue($fairness->isHardToStaffDay($team, CarbonImmutable::parse('2026-07-31')), 'Friday');
        $this->assertFalse($fairness->isHardToStaffDay($team, CarbonImmutable::parse('2026-08-03')), 'Monday');

        // Nothing stands out, so nothing is flagged.
        $this->setWeights($team, [1, 1, 1, 1, 1, 1, 1]);

        $this->assertFalse($fairness->isHardToStaffDay($team, CarbonImmutable::parse('2026-08-01')), 'Saturday');
    }

    /**
     * The recommended split: availability decides the shape, the score only tilts it.
     *
     * Both people below are equally available, so a plain proportional split would give them the
     * same number. The tilt is what separates them - and it is bounded, which the cap test below
     * pins down: no score is worth more days than somebody actually offered.
     */
    public function test_the_recommended_day_count_follows_signups_and_is_tilted_by_the_score(): void
    {
        $team = $this->tenant();

        $owed = $this->member($team);
        $settled = $this->member($team);
        $occasional = $this->member($team);

        $signups = collect([
            [$owed, '2026-08-03'], [$owed, '2026-08-04'],
            [$settled, '2026-08-03'], [$settled, '2026-08-04'],
            [$occasional, '2026-08-05'],
        ])->map(fn (array $signup): Assignment => Assignment::factory()->create([
            'team_id' => $team->id,
            'user_id' => $signup[0]->id,
            'position_id' => null,
            'date' => $signup[1],
        ]));

        // 4 slots for 5 signups, and $owed outranks $settled on the fairness score.
        $targets = app(FairnessService::class)->weeklyTargets($signups, 4, [
            $owed->id => ['totalDays' => 10, 'avgWeight' => 1.0, 'priorityScore' => 20.0],
            $settled->id => ['totalDays' => 10, 'avgWeight' => 1.6, 'priorityScore' => 4.0],
        ]);

        $this->assertSame(2, $targets[$owed->id]['signups']);
        $this->assertSame(2, $targets[$settled->id]['signups']);

        $this->assertGreaterThan(
            $targets[$settled->id]['target'],
            $targets[$owed->id]['target'],
            'Equal availability, higher score - this one is recommended more of the week.',
        );

        // Nobody is ever recommended past their own availability, whatever the score says.
        foreach ($targets as $row) {
            $this->assertLessThanOrEqual($row['signups'], $row['target']);
        }

        $this->assertSame(1, $targets[$occasional->id]['signups']);

        // No slots to hand out, so no recommendation to make.
        $this->assertSame([], app(FairnessService::class)->weeklyTargets($signups, 0, []));
    }

    /**
     * @param  list<float|int>  $weights
     */
    private function setWeights(Team $team, array $weights): void
    {
        // Through the model for the same reason widenWindow() explains.
        $team->settings->update(['fairness_day_weights' => $weights]);
        $team->unsetRelation('settings');
    }

    /**
     * @param  list<string>  $dates
     */
    private function worked(User $user, Position $position, array $dates): void
    {
        foreach ($dates as $date) {
            Assignment::factory()->create([
                'team_id' => $position->team_id,
                'user_id' => $user->id,
                'position_id' => $position->id,
                'date' => $date,
            ]);
        }
    }

    /**
     * The $count most recent occurrences of a weekday before AS_OF. `$skip` moves the run further
     * back so two people can hold non-overlapping runs of the same weekday.
     *
     * @return list<string>
     */
    private function weekdays(string $day, int $count, int $skip = 0): array
    {
        $cursor = CarbonImmutable::parse(self::AS_OF)->previous($day)->subWeeks($skip);

        return collect(range(0, $count - 1))
            ->map(fn (int $offset): string => $cursor->subWeeks($offset)->toDateString())
            ->all();
    }

    private function widenWindow(Team $team, int $weeks): void
    {
        // Through the model, not the relation's query builder: only a model save fires the
        // `saved` hook that busts Team::cachedSettings().
        $team->settings->update(['fairness_window_weeks' => $weeks]);
        $team->unsetRelation('settings');
    }
}
