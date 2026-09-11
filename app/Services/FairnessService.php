<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Who has earned the next Friday/weekend slot.
 *
 * Advisory only - it sorts and badges the pool in the rozpis builder, it never places
 * anybody. The manager decides.
 */
class FairnessService
{
    /**
     * The "ordinary weekday" point on the day-weight scale.
     *
     * Fixed at 1.0 by definition (see TeamSetting::DEFAULT_FAIRNESS_DAY_WEIGHTS): above it is
     * hard to fill, below it is sought after. Deliberately *not* min($weights) - the lowest
     * configured weight is the most sought-after day, and treating that as "ordinary" is what
     * used to mislabel the weekend as a routine shift in the AI prompt.
     */
    public const float ORDINARY_DAY_WEIGHT = 1.0;

    /** What somebody has put in: who has earned the next good shift. */
    public const string RANK_EARNED_CREDIT = 'earnedCredit';

    /** What somebody has dodged: who should be asked to take the next unpopular day. */
    public const string RANK_HARD_DAY_DEBT = 'hardDayDebt';

    /**
     * Per-person fairness stats over the team's configured lookback window, keyed by user_id.
     *
     * A day's configured weight is how hard it is to fill. With the default weights
     * [1, 1, 1, 1, 1.6, 0.8, 0.8] that makes Friday the shift nobody volunteers for and the
     * weekend the sought-after one, below an ordinary weekday rather than above it.
     *
     * Two rankings come out of that, because the builder asks two opposite questions and one
     * number cannot answer both:
     *
     * `earnedCredit` = Σ(weight of every day worked). **Who has the strongest claim on the good
     * stuff** - the next weekend, and a bigger share of next week's shifts:
     *
     * - Works a lot → high, because every day adds to the sum.
     * - Takes the Fridays → higher still, because a Friday is worth 1.6 where a weekday is 1.
     * - Only picks the popular weekends → earns 0.8 a shift against a Friday's 1.6, so it takes
     *   two of them to draw level with one Friday. Cherry-picking never gets *ahead* of carrying
     *   the unpopular days, it only catches up by working twice as often.
     * - Barely works → low. Volume is a real gate, not just a ratio.
     *
     * `hardDayDebt` = totalDays × (team's average *worked* day weight − their own). **Who should
     * be asked to take the next Friday** - positive means they have been carrying fewer hard days
     * than this cinema's people actually do, scaled by how much they work, so somebody who has
     * worked one day in three months is not "owed" a Friday by virtue of being absent.
     *
     * Note this average is over the assignments people really worked, which is a different
     * quantity from the mean of the configured weight array that isHardToStaffDay() thresholds
     * on. One says "what this team's weeks look like in practice", the other "which days the
     * cinema priced as unusual".
     *
     * `avgWeight` is how hard-to-fill someone's typical shift has been; shown to the manager as
     * context and used to derive the debt.
     *
     * Source is `Assignment` (the plan), not `Shift` (worked hours/payroll): the rozpis works
     * entirely at the assignment level, and historical Shift rows are not guaranteed to exist
     * for every past assignment. Revisit if assignment history proves an incomplete proxy.
     *
     * @return Collection<int, array{totalDays: int, avgWeight: float, earnedCredit: float, hardDayDebt: float}>
     */
    public function scores(Team $team, CarbonImmutable $asOf): Collection
    {
        $weights = $team->fairnessDayWeights();
        $since = $asOf->subWeeks($team->fairnessWindowWeeks());

        // Team passed explicitly, so the tenant scope is dropped - same reasoning as
        // WeekLock::locked(): asking about another team from console or tests must not
        // silently answer "nobody worked here".
        $worked = Assignment::withoutGlobalScope('team')
            ->where('team_id', $team->getKey())
            ->whereNotNull('position_id')
            ->whereBetween('date', [$since->toDateString(), $asOf->toDateString()])
            ->get(['user_id', 'date']);

        $weightOf = fn (Assignment $assignment): float => $weights[$assignment->date->dayOfWeekIso - 1];

        // The team's own typical day mix as actually worked, not a hand-tuned constant and not
        // the mean of the configured weights: "under-carrying" has to mean under-carrying
        // *relative to what this cinema's people really do*, which moves with how its slots fall.
        $teamAvgWeight = $worked->isEmpty() ? 0.0 : $worked->sum($weightOf) / $worked->count();

        return $worked
            ->groupBy('user_id')
            ->map(function (Collection $assignments) use ($weightOf, $teamAvgWeight): array {
                $total = $assignments->count();
                $earned = $assignments->sum($weightOf);
                $avgWeight = $earned / $total;

                return [
                    'totalDays' => $total,
                    'avgWeight' => round($avgWeight, 2),
                    'earnedCredit' => round($earned, 2),
                    'hardDayDebt' => round($total * ($teamAvgWeight - $avgWeight), 2),
                ];
            });
    }

    /**
     * Which stat decides the order of the pool on this day, or null when the day is unremarkable.
     *
     * Both keys sort **descending**, which is the point of having two of them: the old single
     * score had to be read backwards on desirable days, and reading a volume-weighted score
     * backwards puts whoever works least at the top of the queue for the best shift.
     *
     * - Hard to staff (Friday, by default) → RANK_HARD_DAY_DEBT. Somebody has to be asked.
     * - Desirable (the weekend, by default) → RANK_EARNED_CREDIT. The thanks for the Fridays.
     * - Anything else → null.
     *
     * Null means "this day settles nothing", not "order does not matter" - and the two callers
     * want opposite things from it. The builder's drag pool falls back to alphabetical, because a
     * manager looking for a name has to be able to find it. The advisory lists (the published
     * "who could be drawn" block, the AI payload) fall back to RANK_EARNED_CREDIT, because a list
     * whose whole purpose is to rank people is useless in name order - see rankingKeyOrMerit().
     */
    public function rankingKeyFor(Team $team, CarbonInterface $date): ?string
    {
        return match (true) {
            $this->isHardToStaffDay($team, $date) => self::RANK_HARD_DAY_DEBT,
            $this->isDesirableDay($team, $date) => self::RANK_EARNED_CREDIT,
            default => null,
        };
    }

    /**
     * The same answer for callers that must rank people somehow: an unremarkable day falls back
     * to plain merit rather than to no order at all.
     */
    public function rankingKeyOrMerit(Team $team, CarbonInterface $date): string
    {
        return $this->rankingKeyFor($team, $date) ?? self::RANK_EARNED_CREDIT;
    }

    /**
     * One person's standing on whichever ranking a day is decided by, 0.0 when no ranking applies.
     *
     * The single home for the "null key means no ranking, a missing person means no history" rule
     * - the rozpis builder, the published plan and the AI payload all read scores this way, and
     * three copies of a fallback is three chances to disagree about what "no history" ranks as.
     *
     * @param  array<int, array{totalDays: int, avgWeight: float, earnedCredit: float, hardDayDebt: float}>  $scores
     */
    public function rank(array $scores, int $userId, ?string $rankingKey): float
    {
        return $rankingKey === null ? 0.0 : (float) ($scores[$userId][$rankingKey] ?? 0.0);
    }

    /**
     * How many of the week's shifts each person should be given, keyed by user_id.
     *
     * Two inputs, in that order of importance:
     *
     * 1. How many days they wrote themselves down for. Somebody available five days is offered
     *    more work than somebody available one - availability is the thing the manager cannot
     *    argue with.
     * 2. Their `earnedCredit`, which tilts the split between two equally available people toward
     *    whoever has done more for the team - more days, and more of the unpopular ones.
     *
     * The tilt is deliberately bounded to ±25%: the credit is a nudge between comparable people,
     * never a reason to hand somebody four days when they offered two. The target is capped at
     * their own signups for the same reason, and a target of 0 is a real answer - it means the
     * week has more volunteers than shifts and this one is not needed.
     *
     * Pure arithmetic over collections the caller already loaded, so it costs no queries.
     *
     * @param  Collection<int, Assignment>  $weekAssignments  every signup in the week, placed or not
     * @param  int  $capacity  position slots the week offers
     * @param  array<int, array{totalDays: int, avgWeight: float, earnedCredit: float, hardDayDebt: float}>  $scores
     * @return array<int, array{signups: int, target: int}>
     */
    public function weeklyTargets(Collection $weekAssignments, int $capacity, array $scores): array
    {
        $signups = $weekAssignments->countBy('user_id');

        if ($signups->isEmpty() || $capacity < 1) {
            return [];
        }

        $credits = $signups->keys()
            ->mapWithKeys(fn (int $userId): array => [$userId => $this->rank($scores, $userId, self::RANK_EARNED_CREDIT)]);

        $lowest = $credits->min();
        $spread = $credits->max() - $lowest;

        // 0.75 … 1.25 of a plain proportional share. A week where everybody scores the same has
        // no spread to read, so everyone keeps their plain share.
        $shares = $signups->map(fn (int $days, int $userId): float => $days * (
            $spread > 0 ? 0.75 + 0.5 * (($credits[$userId] - $lowest) / $spread) : 1.0
        ));

        $total = $shares->sum();

        return $signups->map(fn (int $days, int $userId): array => [
            'signups' => $days,
            'target' => min($days, (int) round($capacity * $shares[$userId] / $total)),
        ])->all();
    }

    /** What one worked day on this date is worth to the team. */
    public function dayWeight(Team $team, CarbonInterface $date): float
    {
        return $team->fairnessDayWeights()[$date->dayOfWeekIso - 1];
    }

    /**
     * A day weighted above the week's own average - Friday only, with the default weights.
     *
     * Measured against the average rather than the cheapest day, because "above the cheapest"
     * flags almost the whole week the moment one day is priced low: with the defaults
     * [1, 1, 1, 1, 1.6, 0.8, 0.8] the sought-after weekend sets the floor at 0.8 and Monday
     * through Friday all come out hard to staff, which tells the manager nothing.
     *
     * The average moves with whatever the cinema configures, so the flag keeps meaning "this day
     * stands out from the rest of your week" instead of needing a hand-tuned threshold. A week of
     * identical weights flags nothing, which is correct - no day stands out. On an ordinary day
     * the builder keeps the pool in alphabetical order instead.
     */
    public function isHardToStaffDay(Team $team, CarbonInterface $date): bool
    {
        $weights = $team->fairnessDayWeights();

        return $this->dayWeight($team, $date) > array_sum($weights) / count($weights);
    }

    /**
     * A day weighted below ORDINARY_DAY_WEIGHT - the weekend, with the default weights.
     *
     * Tested against the fixed 1.0 baseline rather than the week's average, unlike
     * isHardToStaffDay(): the average sits above 1.0 precisely because a hard day is pulling it
     * up, so an average-relative test would also brand every ordinary weekday "desirable" for the
     * crime of being cheaper than Friday. 1.0 already means "ordinary" on this scale by
     * definition (see TeamSetting::DEFAULT_FAIRNESS_DAY_WEIGHTS) - genuinely sought-after is
     * below *that*, not below whatever the week's outlier drags the average to.
     *
     * This is the other half of the fairness story: isHardToStaffDay() decides who is asked to
     * take the next Friday, this decides who is offered the next weekend as thanks for it.
     */
    public function isDesirableDay(Team $team, CarbonInterface $date): bool
    {
        return $this->dayWeight($team, $date) < self::ORDINARY_DAY_WEIGHT;
    }
}
