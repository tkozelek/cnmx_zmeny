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
 * Two scores come out of that, because the builder asks two opposite questions:
 *
 * - `earnedCredit` = Σ(weight of every day worked) - what somebody has put in. It rises with
 *   volume *and* with taking the unpopular days, so it answers "who has earned the next weekend,
 *   and a bigger share of next week".
 * - `hardDayDebt` = totalDays × (team average weight − their own) - who has been dodging the hard
 *   days relative to what this cinema's people typically carry, scaled by how much they work. It
 *   answers "who is next to be asked to take the Friday".
 *
 * Both are read highest-first. That is the point of having two: the single score they replaced had
 * to be read backwards on desirable days, and reading a volume-weighted score backwards handed the
 * best shift to whoever worked least.
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

        // Team average across all 24 worked days is (14.4 + 9.6) / 24 = 1.0, so the debts are
        // 12 × (1.0 − 1.2) = −2.4 and 12 × (1.0 − 0.8) = +2.4.
        $this->assertSame(-2.4, $scores[$takesFridays->id]['hardDayDebt']);
        $this->assertSame(2.4, $scores[$avoidsFridays->id]['hardDayDebt']);

        $this->assertGreaterThan(
            $scores[$takesFridays->id]['hardDayDebt'],
            $scores[$avoidsFridays->id]['hardDayDebt'],
            'Whoever has taken fewer of the hard days is next in line for one.',
        );

        // And the mirror image: 6 Fridays + 6 Saturdays is worth more than 12 Saturdays, so the
        // one who carried the Fridays has the stronger claim when the weekend comes round.
        $this->assertSame(14.4, $scores[$takesFridays->id]['earnedCredit']);
        $this->assertSame(9.6, $scores[$avoidsFridays->id]['earnedCredit']);

        $this->assertGreaterThan(
            $scores[$avoidsFridays->id]['earnedCredit'],
            $scores[$takesFridays->id]['earnedCredit'],
            'Carrying the unpopular days must earn more than cherry-picking the popular ones.',
        );
    }

    /**
     * The regression this whole split exists for.
     *
     * The weekend used to be handed out by reading one volume-weighted score *backwards*, so the
     * lowest score won it - and the lowest score belongs to whoever barely turns up, not to
     * whoever has been carrying the Fridays. Both rankings are read highest-first now.
     */
    public function test_the_weekend_goes_to_whoever_earned_it_not_whoever_works_least(): void
    {
        $team = $this->tenant();
        $position = Position::factory()->create(['team_id' => $team->id]);
        $fairness = app(FairnessService::class);

        $veteran = $this->member($team);
        $barelyWorks = $this->member($team);

        $this->worked($veteran, $position, $this->weekdays('friday', 10));
        $this->worked($barelyWorks, $position, $this->weekdays('saturday', 1));

        $scores = $fairness->scores($team, CarbonImmutable::parse(self::AS_OF));

        $saturday = CarbonImmutable::parse('2026-08-01');
        $friday = CarbonImmutable::parse('2026-07-31');

        $this->assertSame('earnedCredit', $fairness->rankingKeyFor($team, $saturday));
        $this->assertSame('hardDayDebt', $fairness->rankingKeyFor($team, $friday));

        // 10 Fridays = 16.0 against one Saturday = 0.8.
        $this->assertGreaterThan(
            $scores[$barelyWorks->id]['earnedCredit'],
            $scores[$veteran->id]['earnedCredit'],
            'Ten Fridays must outrank one Saturday for the next Saturday.',
        );

        // ... and the Friday is still offered to the one who has not been taking them.
        $this->assertGreaterThan(
            $scores[$veteran->id]['hardDayDebt'],
            $scores[$barelyWorks->id]['hardDayDebt'],
            'The person who has carried every Friday is not the one to ask for the next one.',
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

        $this->assertSame(12.0, $scores[$regular->id]['earnedCredit']);
        $this->assertSame(2.0, $scores[$occasional->id]['earnedCredit']);

        // Everybody here worked the same kind of day, so nobody is dodging anything: the team
        // average *is* 1.0 and both debts are zero. Volume alone must not manufacture a debt.
        $this->assertSame(0.0, $scores[$regular->id]['hardDayDebt']);
        $this->assertSame(0.0, $scores[$occasional->id]['hardDayDebt']);
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
     * The recommended split: availability decides the shape, earned credit only tilts it.
     *
     * Both people below are equally available, so a plain proportional split would give them the
     * same number. The tilt is what separates them - and it is bounded, which the cap test below
     * pins down: no score is worth more days than somebody actually offered.
     */
    public function test_the_recommended_day_count_follows_signups_and_is_tilted_by_the_score(): void
    {
        $team = $this->tenant();

        $veteran = $this->member($team);
        $newer = $this->member($team);
        $occasional = $this->member($team);

        $signups = collect([
            [$veteran, '2026-08-03'], [$veteran, '2026-08-04'],
            [$newer, '2026-08-03'], [$newer, '2026-08-04'],
            [$occasional, '2026-08-05'],
        ])->map(fn (array $signup): Assignment => Assignment::factory()->create([
            'team_id' => $team->id,
            'user_id' => $signup[0]->id,
            'position_id' => null,
            'date' => $signup[1],
        ]));

        // 4 slots for 5 signups, and $veteran has put far more in than $newer.
        $targets = app(FairnessService::class)->weeklyTargets($signups, 4, [
            $veteran->id => ['totalDays' => 10, 'avgWeight' => 1.6, 'earnedCredit' => 20.0, 'hardDayDebt' => -1.0],
            $newer->id => ['totalDays' => 4, 'avgWeight' => 1.0, 'earnedCredit' => 4.0, 'hardDayDebt' => 0.5],
        ]);

        $this->assertSame(2, $targets[$veteran->id]['signups']);
        $this->assertSame(2, $targets[$newer->id]['signups']);

        $this->assertGreaterThan(
            $targets[$newer->id]['target'],
            $targets[$veteran->id]['target'],
            'Equal availability, more earned - this one is recommended more of the week.',
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
     * The other half of the fairness story: the weekend is "desirable" because it sits below the
     * fixed 1.0 baseline, not because it sits below the week's average - the average is pulled up
     * by Friday, so an average-relative test would wrongly call every ordinary weekday desirable
     * too.
     */
    public function test_only_days_below_the_ordinary_baseline_count_as_desirable(): void
    {
        $team = $this->tenant();
        $fairness = app(FairnessService::class);

        // Defaults [1, 1, 1, 1, 1.6, 0.8, 0.8] - only the weekend sits below 1.0.
        $this->assertTrue($fairness->isDesirableDay($team, CarbonImmutable::parse('2026-08-01')), 'Saturday');
        $this->assertTrue($fairness->isDesirableDay($team, CarbonImmutable::parse('2026-08-02')), 'Sunday');
        $this->assertFalse($fairness->isDesirableDay($team, CarbonImmutable::parse('2026-08-03')), 'Monday');
        $this->assertFalse($fairness->isDesirableDay($team, CarbonImmutable::parse('2026-07-31')), 'Friday');

        // All-equal weights: nothing is below baseline, so nothing is desirable either.
        $this->setWeights($team, [1, 1, 1, 1, 1, 1, 1]);

        $this->assertFalse($fairness->isDesirableDay($team, CarbonImmutable::parse('2026-08-01')), 'Saturday');
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
