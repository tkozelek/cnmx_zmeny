<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Position;
use App\Models\PositionSlot;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
            'weekSlots' => PositionSlot::with('position.group')
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
     * The finished week, resolved down to strings — one entry per day, in week order.
     *
     * Shared by the read-only view and the Excel export so the two cannot drift: whatever an
     * employee reads on screen is what comes out of the spreadsheet. Nothing here is editable,
     * so unlike forWeek() it hands back printable values rather than models.
     *
     * The vedúci is deliberately not one of the `rows`: the printed sheet names them once, in the
     * day's "manažér:" heading, and listing them again among bufet 1..3 reads as a second person
     * on shift. They stay a real slot in the builder — somebody has to be assignable to it — and
     * `managerRows` keeps them for the places that want every shift, like the flat Zoznam sheet.
     *
     * @return Collection<int, array{date: CarbonImmutable, dayName: string, manager: ?string, managerRows: list<array{label: string, time: ?string, name: ?string}>, rows: list<array{label: string, time: ?string, name: ?string}>, substitutes: list<string>, complete: bool}>
     */
    public function plan(Team $team, CarbonImmutable $weekStart): Collection
    {
        [$from, $to] = $this->weeks->range($weekStart);

        $slotsByDate = PositionSlot::with('position.group')
            ->betweenDates($from, $to)
            ->get()
            ->groupBy(fn (PositionSlot $slot): string => $slot->date->toDateString());

        $assignmentsByDate = Assignment::with(['user', 'position'])
            ->betweenDates($from, $to)
            ->get()
            ->groupBy(fn (Assignment $assignment): string => $assignment->date->toDateString());

        return $this->weeks->days($weekStart)->map(function (CarbonImmutable $day) use ($slotsByDate, $assignmentsByDate): array {
            $key = $day->toDateString();
            $assignments = $assignmentsByDate->get($key, collect());

            $slots = $this->sortSlots($slotsByDate->get($key, collect()));

            $labels = $this->labelSlots($slots);

            $toRow = fn (PositionSlot $slot): array => [
                'label' => $labels[$slot->getKey()],
                'group' => $slot->position->groupName(),
                // The column is a full TIME; everything on screen and on paper wants H:i.
                'time' => $slot->start_time ? substr((string) $slot->start_time, 0, 5) : null,
                'name' => ($occupant = $assignments->firstWhere('position_slot_id', $slot->getKey()))
                    ? (string) $occupant->user
                    : null,
            ];

            // Labelled off the whole day before the split, so pulling the vedúci out cannot
            // renumber the bufet rows.
            $isManager = fn (PositionSlot $slot): bool => (bool) $slot->position->is_manager;

            $managerRows = $slots->filter($isManager)->map($toRow)->values()->all();
            $rows = $this->markGroupStarts($slots->reject($isManager)->map($toRow)->values()->all());

            return [
                'date' => $day,
                'dayName' => Str::title($day->locale('sk')->dayName),
                'rows' => $rows,
                'managerRows' => $managerRows,

                // Who was in charge, for the day's heading. The first manager slot with somebody
                // on it — a day normally offers exactly one.
                'manager' => collect($managerRows)->pluck('name')->filter()->first(),

                // Signed up for the day, never placed. The reference calls these "náhradníci".
                'substitutes' => $assignments->whereNull('position_slot_id')
                    ->sortBy(fn (Assignment $assignment): string => (string) $assignment->user)
                    ->map(fn (Assignment $assignment): string => (string) $assignment->user)
                    ->values()
                    ->all(),

                // Every slot the day offers, vedúci included — an unstaffed manager slot is just
                // as much a hole in the plan as an unstaffed bufet.
                'complete' => ($everySlot = [...$rows, ...$managerRows]) !== []
                    && collect($everySlot)->every(fn (array $row): bool => $row['name'] !== null),
            ];
        });
    }

    /**
     * One day's rows in the order they are shown: the manager's dragged order first, then the
     * cinema-wide position order for rows nobody has dragged yet, then name as a tie-break.
     *
     * A single closure returning an array, NOT `sortBy([$a, $b, $c])`. Laravel reads a closure
     * inside that array as a two-argument *comparator*, so a one-argument key extractor gets
     * called as `$fn($a, $b)` and its return value — a plain sort_order — is taken as the
     * comparison result. That is always positive, so the rows come out reversed. Returning an
     * array from one closure sorts element by element, which is what was meant.
     *
     * @param  Collection<int, PositionSlot>  $slots
     * @return Collection<int, PositionSlot>
     */
    public function sortSlots(Collection $slots): Collection
    {
        return $slots
            ->sortBy(fn (PositionSlot $slot): array => [
                // Group wins over the manager's dragged order, which is what makes dragging
                // reorder rows *within* a group: a row pulled into another group's run sorts
                // straight back where it belongs.
                $slot->position->groupOrder(),
                $slot->position->groupName() ?? '',
                $slot->sort_order,
                $slot->position->sort_order,
                $slot->position->name,
            ])
            ->values();
    }

    /**
     * Flag the first row of each group, so a renderer can draw a heading or a divider without
     * having to remember the previous row itself.
     *
     * Rows arrive already grouped by sortSlots(), so this is a single pass comparing neighbours.
     * The very first row starts a group only when it actually has one — an ungrouped list gets no
     * stray heading.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public function markGroupStarts(array $rows): array
    {
        $previous = null;

        foreach ($rows as $index => $row) {
            $rows[$index]['startsGroup'] = $row['group'] !== null && $row['group'] !== $previous;
            $previous = $row['group'];
        }

        return $rows;
    }

    /**
     * Display names for one day's slots, keyed by slot id: "Bufet" when the day offers one,
     * "Bufet 1"/"Bufet 2" when it offers several.
     *
     * Numbered by order within the day rather than stored, so deleting bufet 2 renumbers the
     * rest instead of leaving a gap. Lives here rather than in the Livewire component because
     * the export and the read-only view have to number them identically.
     *
     * @param  Collection<int, PositionSlot>  $slots
     * @return array<int, string>
     */
    public function labelSlots(Collection $slots): array
    {
        $totals = $slots->countBy('position_id');
        $seen = [];
        $labels = [];

        foreach ($slots as $slot) {
            $ordinal = $seen[$slot->position_id] = ($seen[$slot->position_id] ?? 0) + 1;

            $labels[$slot->getKey()] = $slot->label($ordinal, $totals[$slot->position_id] > 1);
        }

        return $labels;
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
