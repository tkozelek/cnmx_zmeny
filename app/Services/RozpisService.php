<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Position;
use App\Models\PositionSlot;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Assembles one week of the rozpis builder.
 *
 * Same shape and reasoning as CalendarService::forWeek(): everything the seven day components
 * need is fetched in bulk here, because seven components each querying for themselves is seven
 * times the queries.
 */
class RozpisService
{
    public function __construct(
        private readonly WeekService $weeks,
        private readonly FairnessService $fairness,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forWeek(Team $team, CarbonImmutable $weekStart): array
    {
        [$from, $to] = $this->weeks->range($weekStart);

        return [
            'weekStart' => $weekStart,
            'weekEnd' => $to,
            'days' => $this->weeks->days($weekStart),
            'weekSlots' => PositionSlot::with('position')
                ->betweenDates($from, $to)
                ->get()
                ->groupBy(fn (PositionSlot $slot): string => $slot->date->toDateString()),
            'weekAssignments' => Assignment::with(['user', 'position'])
                ->betweenDates($from, $to)
                ->get()
                ->groupBy(fn (Assignment $assignment): string => $assignment->date->toDateString()),
            'fairness' => $this->fairness->scores($team, CarbonImmutable::now())->all(),
            'positions' => Position::selectable()->get(),
        ];
    }

    /**
     * Copy a day's whole layout onto another day, and report how many slots were added.
     *
     * Tops up rather than duplicating: if the source offers three bufet rows and the target
     * already has one, two are added. A position the target already matches in count is left
     * alone, which makes a second click a no-op instead of doubling the day.
     *
     * Additive by design either way — copying must never quietly overwrite a day the manager has
     * already built.
     */
    public function copySlots(CarbonImmutable $from, CarbonImmutable $to): int
    {
        $source = PositionSlot::where('date', $from->toDateString())->get();

        if ($source->isEmpty()) {
            return 0;
        }

        $existing = PositionSlot::where('date', $to->toDateString())->get()->countBy('position_id');
        $added = 0;

        foreach ($source->groupBy('position_id') as $positionId => $slots) {
            // Skip the rows the target already has, copy the remainder.
            foreach ($slots->slice($existing[$positionId] ?? 0) as $slot) {
                PositionSlot::create([
                    'position_id' => $positionId,
                    'date' => $to->toDateString(),
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'sort_order' => $slot->sort_order,
                ]);

                $added++;
            }
        }

        return $added;
    }

    /**
     * The days a layout may be copied from: this week's other six dates, plus the same weekday
     * of the previous week.
     *
     * @return Collection<int, CarbonImmutable>
     */
    public function copySources(CarbonImmutable $weekStart, CarbonImmutable $date): Collection
    {
        return $this->weeks->days($weekStart)
            ->reject(fn (CarbonImmutable $day): bool => $day->isSameDay($date))
            ->push($date->subWeek())
            ->values();
    }
}
