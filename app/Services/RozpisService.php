<?php

namespace App\Services;

use App\Models\Absence;
use App\Models\Assignment;
use App\Models\Position;
use App\Models\PositionSlot;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

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

        $slots = PositionSlot::with('position.group')->betweenDates($from, $to)->get();
        $assignments = Assignment::with(['user', 'position'])->betweenDates($from, $to)->get();
        $fairness = $this->fairness->scores($team, CarbonImmutable::now())->all();

        return [
            'weekStart' => $weekStart,
            'weekEnd' => $to,
            'days' => $this->weeks->days($weekStart),
            'weekSlots' => $slots->groupBy(fn (PositionSlot $slot): string => $slot->date->toDateString()),
            'weekAssignments' => $assignments->groupBy(fn (Assignment $assignment): string => $assignment->date->toDateString()),
            'fairness' => $fairness,
            'workload' => $this->workload($assignments, $slots->count(), $fairness),
            'history' => $this->history($team, $from, $to),
            'positions' => Position::selectable()->get(),
        ];
    }

    /**
     * Who changed this week's plan, most recent first, batched into sittings.
     *
     * One row per database write is unreadable: filling a Friday is twenty writes in ninety
     * seconds, and a list of twenty identical-looking lines hides the one that matters. So the
     * activities are grouped by who made them and the minute they were made in - one line per
     * person per minute, with the individual changes spelled out underneath.
     *
     * Filtered on the stamped properties rather than by joining the subject rows, because the
     * edits most worth auditing are the ones that deleted their own subject - see
     * LogsRozpisActivity for why team and date are carried in the activity itself.
     *
     * @return list<array{causer: string, at: CarbonImmutable, day: ?string, changes: list<string>}>
     */
    private function history(Team $team, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $activities = Activity::with('causer')
            ->where('properties->team_id', $team->getKey())
            ->whereBetween('properties->date', [$from->toDateString(), $to->toDateString()])
            ->latest()
            // A week of dragging runs to hundreds of writes; the panel answers "what changed
            // recently", not "everything that ever happened".
            ->limit(200)
            ->get();

        if ($activities->isEmpty()) {
            return collect();
        }

        // Names resolved in one query rather than per row - the activities store ids. Includes
        // people who have since left the cinema: their history still has to name them.
        $names = User::whereIn('id', $activities->pluck('properties.user_id')->filter()->unique())
            ->pluck('name', 'id');

        return $activities
            ->groupBy(fn (Activity $activity): string => $activity->causer_id
                .'|'.$activity->created_at->format('Y-m-d H:i')
                .'|'.data_get($activity->properties, 'date'))
            ->map(fn (Collection $batch): array => [
                'causer' => (string) ($batch->first()->causer ?? 'Systém'),
                'at' => CarbonImmutable::parse($batch->first()->created_at),
                'day' => data_get($batch->first()->properties, 'date'),
                'changes' => $batch->map(fn (Activity $activity): string => $this->describeChange($activity, $names))
                    ->filter()
                    ->values()
                    ->all(),
            ])
            ->values();
    }

    /**
     * One activity as a sentence a manager can read.
     *
     * Built from the event and which columns moved rather than dumping the raw diff: `user_id`
     * and `position_slot_id` are meaningless as numbers, and "position_slot_id: null → 42" is not
     * an answer to "what changed".
     *
     * @param  Collection<int, string>  $names
     */
    private function describeChange(Activity $activity, Collection $names): string
    {
        $new = (array) data_get($activity->attribute_changes, 'attributes', []);
        $old = (array) data_get($activity->attribute_changes, 'old', []);
        $who = $names[data_get($activity->properties, 'user_id')] ?? null;
        $time = fn (?string $value): string => $value ? substr($value, 0, 5) : 'bez času';

        if (class_basename((string) $activity->subject_type) === 'PositionSlot') {
            return match ($activity->event) {
                'created' => 'pridal pozíciu do dňa ('.$time($new['start_time'] ?? null).')',
                'deleted' => 'zmazal pozíciu z dňa',
                default => array_key_exists('start_time', $new)
                    ? 'zmenil čas pozície: '.$time($old['start_time'] ?? null).' -> '.$time($new['start_time'] ?? null)
                    : 'upravil pozíciu v dni',
            };
        }

        // An Assignment. Placement is the change worth naming; the rest is bookkeeping.
        $person = $who ? '"'.$who.'"' : 'zamestnanca';

        return match (true) {
            $activity->event === 'created' && ($new['position_slot_id'] ?? null) === null => 'zapísal '.$person.' na deň',
            $activity->event === 'created' => 'zaradil '.$person.' na pozíciu',
            $activity->event === 'deleted' => 'odstránil '.$person.' zo dňa',
            array_key_exists('position_slot_id', $new) && ($new['position_slot_id'] ?? null) === null => 'vyradil '.$person.' späť medzi nezaradených',
            array_key_exists('position_slot_id', $new) => 'zaradil '.$person.' na pozíciu',
            array_key_exists('start_time', $new) => 'zmenil čas '.$person.': '.$time($old['start_time'] ?? null).' -> '.$time($new['start_time'] ?? null),
            default => 'upravil zaradenie '.$person,
        };
    }

    /**
     * How many of the week's shifts each person should get, ready to print: one row per volunteer,
     * the people to lean on first at the top.
     *
     * Read once before assigning rather than watched while dragging - nothing in it moves when a
     * slot is filled, because it is derived from the signups and the history, both of which a
     * locked week has already fixed.
     *
     * @param  Collection<int, Assignment>  $assignments
     * @param  array<int, array{totalDays: int, avgWeight: float, priorityScore: float}>  $fairness
     * @return list<array{name: string, signups: int, target: int, priorityScore: float, placed: int}>
     */
    private function workload(Collection $assignments, int $capacity, array $fairness): array
    {
        $targets = $this->fairness->weeklyTargets($assignments, $capacity, $fairness);
        $placed = $assignments->whereNotNull('position_slot_id')->countBy('user_id');

        return $assignments
            ->unique('user_id')
            ->map(fn (Assignment $assignment): array => [
                'name' => (string) $assignment->user,
                'signups' => $targets[$assignment->user_id]['signups'] ?? 0,
                'target' => $targets[$assignment->user_id]['target'] ?? 0,
                'placed' => $placed[$assignment->user_id] ?? 0,
                'priorityScore' => (float) ($fairness[$assignment->user_id]['priorityScore'] ?? 0.0),
            ])
            // Most owed first: that is the order a manager works down the list in.
            ->sortByDesc(fn (array $row): array => [$row['target'], $row['priorityScore']])
            ->values()
            ->all();
    }

    /**
     * The finished week, resolved down to strings - one entry per day, in week order.
     *
     * Shared by the read-only view and the Excel export so the two cannot drift: whatever an
     * employee reads on screen is what comes out of the spreadsheet. Nothing here is editable,
     * so unlike forWeek() it hands back printable values rather than models.
     *
     * The vedúci is deliberately not one of the `rows`: the printed sheet names them once, in the
     * day's "manažér:" heading, and listing them again among bufet 1..3 reads as a second person
     * on shift. They stay a real slot in the builder - somebody has to be assignable to it - and
     * `managerRows` keeps them for the places that want every shift, like the flat Zoznam sheet.
     *
     * `eligible` is the manager-only counterpart of `unfilled`: everybody who signed up for the
     * day and is not yet on a position, minus anyone with an absence covering it (a defensive
     * filter - a signed-up absentee should not happen, but an absence can be filed after the
     * signup), ordered by fairness score descending, same as the builder's hard-day pool. It is
     * not gated here - the published view decides who gets to see it, this just supplies the data.
     *
     * @return Collection<int, array{date: CarbonImmutable, dayName: string, manager: ?string, managerRows: list<array{label: string, time: ?string, name: ?string, user_id: ?int}>, rows: list<array{label: string, time: ?string, name: ?string, user_id: ?int}>, substitutes: list<string>, eligible: list<array{name: string, priorityScore: float}>, unfilled: int}>
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

        $fairness = $this->fairness->scores($team, CarbonImmutable::now())->all();

        $absencesByUser = Absence::where('team_id', $team->getKey())
            ->overlapping($from, $to)
            ->get()
            ->groupBy('user_id');

        return $this->weeks->days($weekStart)
            ->map(function (CarbonImmutable $day) use ($slotsByDate, $assignmentsByDate, $fairness, $absencesByUser): array {
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
                    'user_id' => $occupant->user_id ?? null,
                ];

                // Labelled off the whole day before the split, so pulling the vedúci out cannot
                // renumber the bufet rows.
                $isManager = fn (PositionSlot $slot): bool => (bool) $slot->position->is_manager;

                $managerRows = $slots->filter($isManager)->map($toRow)->values()->all();
                $rows = $this->markGroupStarts($slots->reject($isManager)->map($toRow)->values()->all());

                $pool = $assignments->whereNull('position_slot_id')
                    ->sortBy(fn (Assignment $assignment): string => (string) $assignment->user)
                    ->values();

                $eligible = $pool
                    ->reject(fn (Assignment $assignment): bool => ($absencesByUser[$assignment->user_id] ?? collect())
                        ->contains(fn (Absence $absence): bool => $absence->covers($day)))
                    ->sortByDesc(fn (Assignment $assignment): float => (float) ($fairness[$assignment->user_id]['priorityScore'] ?? 0.0))
                    ->map(fn (Assignment $assignment): array => [
                        'name' => (string) $assignment->user,
                        'priorityScore' => round((float) ($fairness[$assignment->user_id]['priorityScore'] ?? 0.0), 1),
                    ])
                    ->values()
                    ->all();

                return [
                    'date' => $day,
                    'dayName' => Str::title($day->locale('sk')->dayName),
                    'rows' => $rows,
                    'managerRows' => $managerRows,

                    // Who was in charge, for the day's heading. The first manager slot with somebody
                    // on it - a day normally offers exactly one.
                    'manager' => collect($managerRows)->pluck('name')->filter()->first(),

                    // Signed up for the day, never placed. The reference calls these "náhradníci".
                    'substitutes' => $pool->map(fn (Assignment $assignment): string => (string) $assignment->user)->all(),

                    'eligible' => $eligible,

                    // How many printed rows nobody stands in. Manager slots are deliberately out of
                    // it: the vedúci is arranged separately and is routinely still blank while the
                    // rest of the day is settled, so counting them would leave every day flagged.
                    'unfilled' => collect($rows)->whereNull('name')->count(),
                ];
            });
    }

    /**
     * One day's rows in the order they are shown: the manager's dragged order first, then the
     * cinema-wide position order for rows nobody has dragged yet, then name as a tie-break.
     *
     * A single closure returning an array, NOT `sortBy([$a, $b, $c])`. Laravel reads a closure
     * inside that array as a two-argument *comparator*, so a one-argument key extractor gets
     * called as `$fn($a, $b)` and its return value - a plain sort_order - is taken as the
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
     * The very first row starts a group only when it actually has one - an ungrouped list gets no
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
     * Additive by design either way - copying must never quietly overwrite a day the manager has
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
     * Copy the entire position slot structure from a previous week to a target week.
     * Maps each day of the source week to the corresponding day of the target week by day index.
     */
    public function copyWeekSlots(Team $team, CarbonImmutable $sourceWeekStart, CarbonImmutable $targetWeekStart): int
    {
        $sourceDays = $this->weeks->days($sourceWeekStart)->values();
        $targetDays = $this->weeks->days($targetWeekStart)->values();
        $totalAdded = 0;

        foreach ($sourceDays as $index => $sourceDay) {
            $targetDay = $targetDays->get($index);

            if ($targetDay) {
                $totalAdded += $this->copySlots($sourceDay, $targetDay);
            }
        }

        return $totalAdded;
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
