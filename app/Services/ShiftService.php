<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Writes worked hours. The hours screen posts a whole month at once, so this syncs a
 * month rather than saving one shift at a time.
 */
class ShiftService
{
    /**
     * Replace one user's month with the submitted rows.
     *
     * @param  array<int, array{date: string, start: string, end: string, break_minutes?: int|null}>  $rows
     * @return int Number of shifts the month now holds.
     */
    public function syncMonth(User $user, int $year, int $month, array $rows): int
    {
        $shifts = collect($rows)->keyBy(
            fn (array $row): string => $this->startsAt($row)->toDateTimeString()
        );

        return DB::transaction(function () use ($user, $year, $month, $shifts): int {
            $user->shifts()
                ->inMonth($year, $month)
                ->whereNotIn('starts_at', $shifts->keys()->all())
                ->delete();

            foreach ($shifts as $startsAt => $row) {
                // ponytail: keyed on (user_id, starts_at) rather than the table's
                // (user_id, starts_at, position_id) unique index. position_id is nullable
                // and MySQL treats NULLs as distinct, so an upsert on that index would
                // insert a duplicate every save for hours entered without a position.
                $user->shifts()->updateOrCreate(
                    ['starts_at' => $startsAt],
                    [
                        'ends_at' => $this->endsAt($row),
                        'break_minutes' => (int) ($row['break_minutes'] ?? 0),
                    ]
                );
            }

            return $user->shifts()->inMonth($year, $month)->count();
        });
    }

    private function startsAt(array $row): CarbonImmutable
    {
        return CarbonImmutable::parse($row['date'].' '.$row['start']);
    }

    /**
     * A shift that ends earlier in the day than it started ran past midnight, so it ends
     * the next day. This is the bug the DATETIME columns exist to fix - a TIME pair made
     * a 21:00 -> 01:30 shift come out as minus nineteen and a half hours.
     */
    private function endsAt(array $row): CarbonImmutable
    {
        $start = $this->startsAt($row);
        $end = CarbonImmutable::parse($row['date'].' '.$row['end']);

        return $end->lessThanOrEqualTo($start) ? $end->addDay() : $end;
    }
}
