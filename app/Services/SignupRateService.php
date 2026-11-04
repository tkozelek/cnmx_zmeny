<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Share of a cinema's members signed up on an average Monday, Tuesday, ... - normalised by the
 * cinema's size so cinemas compare on one scale. Shared by the /platform dashboard and Správa kina.
 *
 * Divides by the current member count: membership history isn't kept, so in a fast-growing
 * cinema the older weeks of the window read slightly low.
 */
class SignupRateService
{
    public const int WEEKS = 8;

    /**
     * Active, approved, non-super-admin members per cinema, largest first.
     *
     * @param  list<int>|null  $teamIds  null = every cinema
     * @return Collection<int, positive-int> team id => members
     */
    public function memberCounts(?array $teamIds = null, ?int $limit = null): Collection
    {
        return DB::table('team_user')
            ->join('users', 'users.id', '=', 'team_user.user_id')
            ->whereNotNull('team_user.approved_at')
            ->where('users.is_active', true)
            ->where('users.is_super_admin', false)
            ->when($teamIds !== null, fn ($query) => $query->whereIn('team_user.team_id', $teamIds))
            ->selectRaw('team_user.team_id, count(*) as total')
            ->groupBy('team_user.team_id')
            ->orderByDesc('total')
            ->when($limit !== null, fn ($query) => $query->limit($limit))
            ->pluck('total', 'team_id')
            ->mapWithKeys(fn (mixed $total, mixed $teamId): array => [(int) $teamId => (int) $total])
            ->filter(fn (int $total): bool => $total > 0);
    }

    /**
     * Percent of members signed up per ISO weekday (index 0 = Monday) over the last WEEKS
     * finished weeks' worth of days, ending yesterday.
     *
     * @param  Collection<int, positive-int>  $memberCounts  team id => members, from memberCounts()
     * @return array<int, list<float>> team id => 7 percentages
     */
    public function byWeekday(Collection $memberCounts): array
    {
        $to = CarbonImmutable::yesterday();
        $from = $to->subWeeks(self::WEEKS)->addDay();

        $perDay = DB::table('assignments')
            ->whereIn('team_id', $memberCounts->keys())
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('team_id, date, count(*) as total')
            ->groupBy('team_id', 'date')
            ->get();

        $daysPerWeekday = array_fill(1, 7, 0);
        for ($day = $from; $day->lte($to); $day = $day->addDay()) {
            $daysPerWeekday[$day->dayOfWeekIso]++;
        }

        $sums = [];
        foreach ($perDay as $row) {
            $weekday = CarbonImmutable::parse($row->date)->dayOfWeekIso;
            $sums[$row->team_id][$weekday] = ($sums[$row->team_id][$weekday] ?? 0) + (int) $row->total;
        }

        return $memberCounts->map(fn (int $members, int $teamId): array => array_map(
            fn (int $weekday): float => round(($sums[$teamId][$weekday] ?? 0) / $daysPerWeekday[$weekday] / $members * 100, 1),
            range(1, 7),
        ))->all();
    }
}
