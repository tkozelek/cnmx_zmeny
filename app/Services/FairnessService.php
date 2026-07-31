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
 * Advisory only — it sorts and badges the pool in the rozpis builder, it never places
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
     * manager as context. `priorityScore` is the actual ranking — read it as "how much does this
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

        // Team passed explicitly, so the tenant scope is dropped — same reasoning as
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

    /** What one worked day on this date is worth to the team. */
    public function dayWeight(Team $team, CarbonInterface $date): float
    {
        return $team->fairnessDayWeights()[$date->dayOfWeekIso - 1];
    }

    /**
     * A day weighted above the cheapest day of the week — Friday and the weekend by default.
     *
     * These are the days almost nobody volunteers for, which is exactly why they are worth
     * ranking the pool for: somebody has to take them. On an ordinary weekday the builder keeps
     * DayCard's alphabetical order instead.
     */
    public function isHardToStaffDay(Team $team, CarbonInterface $date): bool
    {
        return $this->dayWeight($team, $date) > min($team->fairnessDayWeights());
    }
}
