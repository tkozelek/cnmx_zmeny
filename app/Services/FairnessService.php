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
     * Per-person fairness stats over the team's configured lookback window, keyed by user_id.
     *
     * A day's configured weight is how hard it is to fill. With the default weights
     * [1, 1, 1, 1, 1.6, 0.8, 0.8] that makes Friday the shift nobody volunteers for and the
     * weekend the sought-after one, below an ordinary weekday rather than above it.
     *
     * `avgWeight` is therefore how hard-to-fill someone's typical shift has been; shown to the
     * manager as context. `priorityScore` is the actual ranking - read it as "how much does this
     * person owe the team a hard day":
     *
     *   priorityScore = totalDays × (ceiling − avgWeight)
     *
     * - Works a lot, and mostly the popular shifts → highest. They are next to be asked.
     * - Already takes the Fridays → avgWeight climbs toward the ceiling and the score shrinks, so
     *   they are not asked again while somebody else is due.
     * - Barely works at all → the totalDays multiplier keeps them low regardless. Volume is a
     *   real gate, not just a ratio.
     *
     * Source is `Assignment` (the plan), not `Shift` (worked hours/payroll): the rozpis works
     * entirely at the assignment level, and historical Shift rows are not guaranteed to exist
     * for every past assignment. Revisit if assignment history proves an incomplete proxy.
     *
     * @return Collection<int, array{totalDays: int, avgWeight: float, priorityScore: float}>
     */
    public function scores(Team $team, CarbonImmutable $asOf): Collection
    {
        $weights = $team->fairnessDayWeights();
        $ceiling = max($weights) + 1; // always above the highest configured day weight
        $since = $asOf->subWeeks($team->fairnessWindowWeeks());

        // Team passed explicitly, so the tenant scope is dropped - same reasoning as
        // WeekLock::locked(): asking about another team from console or tests must not
        // silently answer "nobody worked here".
        return Assignment::withoutGlobalScope('team')
            ->where('team_id', $team->getKey())
            ->whereNotNull('position_id')
            ->whereBetween('date', [$since->toDateString(), $asOf->toDateString()])
            ->get(['user_id', 'date'])
            ->groupBy('user_id')
            ->map(function (Collection $assignments) use ($weights, $ceiling): array {
                $total = $assignments->count();
                $weighted = $assignments->sum(
                    fn (Assignment $assignment): float => $weights[$assignment->date->dayOfWeekIso - 1]
                );
                $avgWeight = $total > 0 ? $weighted / $total : 0.0;

                return [
                    'totalDays' => $total,
                    'avgWeight' => round($avgWeight, 2),
                    'priorityScore' => round($total * ($ceiling - $avgWeight), 2),
                ];
            });
    }

    /**
     * How many of the week's shifts each person should be given, keyed by user_id.
     *
     * Two inputs, in that order of importance:
     *
     * 1. How many days they wrote themselves down for. Somebody available five days is offered
     *    more work than somebody available one - availability is the thing the manager cannot
     *    argue with.
     * 2. Their fairness score, which tilts the split between two equally available people toward
     *    whoever is more owed a shift.
     *
     * The tilt is deliberately bounded to ±25%: the score is a nudge between comparable people,
     * never a reason to hand somebody four days when they offered two. The target is capped at
     * their own signups for the same reason, and a target of 0 is a real answer - it means the
     * week has more volunteers than shifts and this one is not needed.
     *
     * Pure arithmetic over collections the caller already loaded, so it costs no queries.
     *
     * @param  Collection<int, Assignment>  $weekAssignments  every signup in the week, placed or not
     * @param  int  $capacity  position slots the week offers
     * @param  array<int, array{totalDays: int, avgWeight: float, priorityScore: float}>  $scores
     * @return array<int, array{signups: int, target: int}>
     */
    public function weeklyTargets(Collection $weekAssignments, int $capacity, array $scores): array
    {
        $signups = $weekAssignments->countBy('user_id');

        if ($signups->isEmpty() || $capacity < 1) {
            return [];
        }

        $priorities = $signups->keys()
            ->mapWithKeys(fn (int $userId): array => [$userId => (float) ($scores[$userId]['priorityScore'] ?? 0.0)]);

        $lowest = $priorities->min();
        $spread = $priorities->max() - $lowest;

        // 0.75 … 1.25 of a plain proportional share. A week where everybody scores the same has
        // no spread to read, so everyone keeps their plain share.
        $shares = $signups->map(fn (int $days, int $userId): float => $days * (
            $spread > 0 ? 0.75 + 0.5 * (($priorities[$userId] - $lowest) / $spread) : 1.0
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
}
