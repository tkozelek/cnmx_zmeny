<?php

namespace App\Livewire;

use App\Models\Assignment;
use App\Models\Position;
use App\Models\PositionSlot;
use App\Models\Team;
use App\Services\AiRozpisSuggestionService;
use App\Services\FairnessService;
use App\Services\RozpisService;
use App\Services\WeekService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * One day of the rozpis: the day's position slots on one side, the people who signed up for
 * that day and are not placed yet on the other.
 *
 * Mirrors DayCard's structure (locked date prop, computed derived state, preloaded collections
 * released after first use) but writes `position_id`/`start_time` instead of creating rows.
 * Nobody is ever placed who did not sign up for this exact date themselves — the pool is built
 * from real Assignment rows, never invented.
 */
class RozpisDay extends Component
{
    /** Y-m-d. The identity of the day; locked against tampering. */
    #[Locked]
    public string $date;

    /** Preloaded by RozpisService so seven of these do not each query for themselves. */
    public ?Collection $initialSlots = null;

    public ?Collection $initialAssignments = null;

    /** @var array<int, array{totalDays: int, avgWeight: float, priorityScore: float}>|null */
    public ?array $initialFairness = null;

    /** "Add position" form. */
    public ?int $newPositionId = null;

    public ?string $newStartTime = null;

    /**
     * The AI draft, if one was asked for. Locked because it survives between requests, and a
     * client-side edit must not be able to smuggle in a placement — though accepting one still
     * runs the full policy and slot check, so tampering buys nothing a manual drag would not.
     *
     * @var list<array{assignment_id: int, slot_id: int}>
     */
    #[Locked]
    public array $suggestions = [];

    /**
     * Another day card copied a slot onto this one, so this day now has a row it is not showing.
     * Cheaper than polling and narrower than refreshing the page.
     */
    #[On('rozpis-slots-changed')]
    public function slotsChanged(): void
    {
        $this->refresh();
    }

    /**
     * Place someone in a slot, or drop them back in the pool when $slotId is null.
     *
     * Keyed on the slot, not the position: a day can offer three bufet rows, and "put them on
     * bufet" would not say which one. One method for both directions because that is what a drag
     * is — SortableJS reports the list the card landed in, and the pool is a list without a slot.
     */
    public function place(int $assignmentId, ?int $slotId = null): void
    {
        $assignment = $this->assignmentForThisDay($assignmentId);

        $this->authorize('assignPosition', $assignment);

        if ($slotId === null) {
            $this->clear($assignment);

            return;
        }

        // Loaded by date as well as id, so a replayed request cannot reach another day's slot.
        $slot = PositionSlot::where('date', $this->date)->findOrFail($slotId);

        // Both writes or neither: a slot holds one person by unique index now, so releasing the
        // previous occupant is not a courtesy, it is what makes the second write legal.
        DB::transaction(function () use ($assignment, $slot): void {
            // Whoever was there is bumped back to the pool rather than silently sharing it —
            // the drop is the manager saying "this one instead".
            Assignment::where('position_slot_id', $slot->getKey())
                ->whereKeyNot($assignment->getKey())
                ->update(['position_id' => null, 'position_slot_id' => null, 'start_time' => null, 'end_time' => null]);

            $assignment->update([
                'position_id' => $slot->position_id,
                'position_slot_id' => $slot->getKey(),
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
            ]);
        });

        $this->refresh();

        $this->dispatch('toast', message: "{$assignment->user} → {$this->slotLabels[$slot->getKey()]}");
    }

    public function unplace(int $assignmentId): void
    {
        $assignment = $this->assignmentForThisDay($assignmentId);

        $this->authorize('assignPosition', $assignment);

        $this->clear($assignment);

        $this->dispatch('toast', message: "{$assignment->user} vrátený do zoznamu.", type: 'error');
    }

    public function addSlot(): void
    {
        $this->authorize('create', [PositionSlot::class, $this->dayCarbon]);

        $validated = $this->validate([
            'newPositionId' => [
                'required', 'integer',
                Rule::exists('positions', 'id')
                    ->where('team_id', app(Team::class)->getKey())
                    ->where('is_active', true),
            ],
            'newStartTime' => ['nullable', 'date_format:H:i'],
        ], [
            'newPositionId.required' => 'Vyberte pozíciu.',
            'newPositionId.exists' => 'Táto pozícia nie je dostupná.',
            'newStartTime.date_format' => 'Čas nástupu musí byť v tvare HH:MM.',
        ]);

        // Plain create: adding Bufet to a day that already has one is the point, not a mistake.
        // Two bufet rows are two slots of one position — that is how a cinema staffs a busy day.
        PositionSlot::create([
            'date' => $this->date,
            'position_id' => $validated['newPositionId'],
            'start_time' => $validated['newStartTime'],
            // Onto the end of the day, in tens like Position::sort_order.
            'sort_order' => ($this->slots->max('sort_order') ?? 0) + 10,
        ]);

        $this->reset(['newPositionId', 'newStartTime']);
        $this->refresh();
    }

    /**
     * Change a row's start time after the fact — the schedule moves, the bufet now opens at 17:00.
     *
     * Whoever is standing in the slot moves with it: they took these times *from* the slot when
     * they were placed, so leaving them behind would leave the plan disagreeing with itself.
     */
    public function updateSlotTime(int $slotId, ?string $startTime = null): void
    {
        $this->authorize('create', [PositionSlot::class, $this->dayCarbon]);

        $slot = PositionSlot::where('date', $this->date)->findOrFail($slotId);

        $startTime = $startTime === '' ? null : $startTime;

        // The picker only ever emits H:i, so anything else is a crafted request rather than a
        // user mistake — no field to show an error against, so refuse it outright.
        abort_unless($startTime === null || preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $startTime) === 1, 422);

        DB::transaction(function () use ($slot, $startTime): void {
            $slot->update(['start_time' => $startTime]);

            // The slot's own value, not the submitted one: PositionSlot normalises H:i to H:i:s
            // on write, and the occupant must end up holding the same shape.
            $slot->occupant()->update(['start_time' => $slot->start_time]);
        });

        $this->refresh();

        $this->dispatch('toast', message: $startTime
            ? "{$this->slotLabels[$slot->getKey()]} — nástup {$startTime}."
            : "{$this->slotLabels[$slot->getKey()]} — čas nástupu zmazaný.");
    }

    /**
     * Copy one slot onto other days — "I built Thursday's bufet, put it on Friday and Saturday
     * too".
     *
     * Each target is authorized on its own, because the lock is per week and a target can sit in
     * a different one. Targets outside the offered list are skipped rather than trusted.
     *
     * @param  array<int, string>  $dates
     */
    public function copySlot(int $slotId, array $dates): void
    {
        $slot = $this->slots->firstWhere('id', $slotId);

        abort_unless((bool) $slot, 404);

        $offered = array_column($this->copyTargets, 'date');
        $copied = 0;

        foreach (array_unique($dates) as $date) {
            if (! in_array($date, $offered, true)) {
                continue;
            }

            $target = CarbonImmutable::parse($date)->startOfDay();

            $this->authorize('create', [PositionSlot::class, $target]);

            PositionSlot::create([
                'date' => $target->toDateString(),
                'position_id' => $slot->position_id,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'sort_order' => $slot->sort_order,
            ]);

            $copied++;
        }

        $this->dispatch('toast', ...match ($copied) {
            0 => ['message' => 'Nevybrali ste žiadny deň.', 'type' => 'error'],
            default => ['message' => "Pozícia skopírovaná do {$copied} dní."],
        });

        // Every other day card on the page now has one more row than it is showing.
        $this->dispatch('rozpis-slots-changed');
    }

    /**
     * Persist a dragged row order for this day.
     *
     * Ids that are not this day's slots are ignored rather than trusted, so a replayed request
     * cannot reorder another day. Anything the browser left out keeps its existing order value.
     *
     * @param  array<int, int|string>  $slotIds
     */
    public function reorderSlots(array $slotIds): void
    {
        $this->authorize('create', [PositionSlot::class, $this->dayCarbon]);

        $mine = $this->slots->keyBy('id');
        $order = 0;

        foreach ($slotIds as $slotId) {
            $slot = $mine->get((int) $slotId);

            if ($slot) {
                $slot->update(['sort_order' => $order += 10]);
            }
        }

        $this->refresh();
    }

    public function removeSlot(int $slotId): void
    {
        $slot = PositionSlot::findOrFail($slotId);

        $this->authorize('delete', $slot);

        // Clear the occupant first: the FK nulls position_slot_id on delete, but position_id and
        // the times would survive and render as placed on a row the day no longer offers.
        $occupant = $this->filledFor($slot->getKey());

        if ($occupant) {
            $this->clear($occupant);
        }

        $slot->delete();

        $this->refresh();
    }

    /**
     * Ask for a draft. Manager-initiated only — this is a billed request per click, never
     * triggered by rendering or polling.
     */
    public function suggest(): void
    {
        $this->authorize('create', [PositionSlot::class, $this->dayCarbon]);

        $this->suggestions = app(AiRozpisSuggestionService::class)
            ->suggest(app(Team::class), $this->dayCarbon);

        $this->dispatch('toast', ...match (count($this->suggestions)) {
            0 => ['message' => 'AI nenavrhla žiadne zaradenie.', 'type' => 'error'],
            default => ['message' => 'Návrh pripravený — potvrďte jednotlivé zaradenia.'],
        });
    }

    /**
     * Accept one suggested placement.
     *
     * Deliberately routed through place(): the AI has no write path of its own, so this is a
     * manager action that happens to be pre-filled, and it is refused under exactly the
     * conditions a manual drag would be refused under.
     */
    public function acceptSuggestion(int $assignmentId): void
    {
        $accepted = collect($this->suggestions)
            ->firstWhere('assignment_id', $assignmentId);

        if (! $accepted) {
            return;
        }

        $this->place($accepted['assignment_id'], $accepted['slot_id']);

        $this->suggestions = collect($this->suggestions)
            ->reject(fn (array $s): bool => $s['assignment_id'] === $assignmentId)
            ->values()
            ->all();
    }

    public function acceptAllSuggestions(): void
    {
        foreach ($this->suggestions as $suggestion) {
            $this->place($suggestion['assignment_id'], $suggestion['slot_id']);
        }

        $this->suggestions = [];
    }

    public function dismissSuggestions(): void
    {
        $this->suggestions = [];
    }

    /**
     * RozpisWeekAi drafted the whole week; take this day's share of it.
     *
     * A day the model left alone gets an empty list rather than being skipped, so a stale draft
     * from an earlier single-day run cannot sit beside a fresh week-wide one.
     *
     * @param  array<string, list<array{assignment_id: int, slot_id: int}>>  $byDate
     */
    #[On('rozpis-week-suggested')]
    public function weekSuggested(array $byDate): void
    {
        $this->suggestions = $byDate[$this->date] ?? [];
    }

    /** The pending suggestion for a slot, resolved to a name for display. */
    public function suggestionFor(int $slotId): ?Assignment
    {
        $suggestion = collect($this->suggestions)->firstWhere('slot_id', $slotId);

        return $suggestion
            ? $this->unassignedPool->firstWhere('id', $suggestion['assignment_id'])
            : null;
    }

    #[Computed]
    public function aiEnabled(): bool
    {
        return app(AiRozpisSuggestionService::class)->enabled();
    }

    /**
     * This day's slots: the manager's dragged order first, then the cinema-wide position order
     * for rows nobody has dragged yet.
     *
     * @return Collection<int, PositionSlot>
     */
    #[Computed]
    public function slots(): Collection
    {
        $slots = $this->initialSlots ?? PositionSlot::with('position.group')
            ->where('date', $this->date)
            ->get();

        $this->initialSlots = null;

        return app(RozpisService::class)->sortSlots($slots);
    }

    /**
     * @return Collection<int, Assignment>
     */
    #[Computed]
    public function assignments(): Collection
    {
        $assignments = $this->initialAssignments ?? Assignment::with(['user', 'position'])
            ->where('date', $this->date)
            ->get();

        $this->initialAssignments = null;

        return $assignments;
    }

    /**
     * Everyone who signed up for this day and has no position yet.
     *
     * Sorted by priorityScore only on a hard-to-staff (Friday/weekend) day — the days somebody
     * has to be asked to take, so the order matters. A plain weekday keeps DayCard's alphabetical
     * listing, and nothing changes for the common case.
     *
     * @return Collection<int, Assignment>
     */
    #[Computed]
    public function unassignedPool(): Collection
    {
        $pool = $this->assignments->whereNull('position_id');

        if (! $this->isHardToStaff) {
            return $pool->sortBy(fn (Assignment $assignment): string => (string) $assignment->user)->values();
        }

        return $pool
            ->sortByDesc(fn (Assignment $assignment): float => $this->scoreFor($assignment->user_id))
            ->values();
    }

    public function filledFor(int $slotId): ?Assignment
    {
        return $this->assignments->firstWhere('position_slot_id', $slotId);
    }

    /**
     * One entry per row of the day's plan, with everything the view needs already resolved.
     *
     * The view prints these and decides nothing: no lookups, no counting, no formatting in the
     * template. Which also means the rendered state can be asserted straight off the component.
     *
     * @return list<array{slot: PositionSlot, label: string, time: ?string, occupant: ?Assignment, suggestion: ?Assignment}>
     */
    #[Computed]
    public function rows(): array
    {
        return app(RozpisService::class)->markGroupStarts(
            $this->slots->map(fn (PositionSlot $slot): array => [
                'slot' => $slot,
                'label' => $this->slotLabels[$slot->getKey()],
                'group' => $slot->position->groupName(),
                // H:i for display and for the picker; the column is a full TIME.
                'time' => $slot->start_time ? substr((string) $slot->start_time, 0, 5) : null,
                'occupant' => $this->filledFor($slot->getKey()),
                'suggestion' => $this->suggestionFor($slot->getKey()),
            ])->all()
        );
    }

    /** How many of the day's rows have somebody in them. */
    #[Computed]
    public function filledCount(): int
    {
        return $this->slots
            ->filter(fn (PositionSlot $slot): bool => (bool) $this->filledFor($slot->getKey()))
            ->count();
    }

    /**
     * The SortableJS group name. Scoped per day, so a card can move between this day's lists but
     * never into another day.
     */
    #[Computed]
    public function dragGroup(): string
    {
        return 'rozpis-'.$this->date;
    }

    #[Computed]
    public function dayName(): string
    {
        return Str::title($this->dayCarbon->locale('sk')->dayName);
    }

    /**
     * Display names for this day's slots, keyed by slot id: "Bufet" when there is one, "Bufet 1",
     * "Bufet 2", "Bufet 3" when the day offers several.
     *
     * Numbered by order within the day rather than stored, so deleting bufet 2 renumbers the rest
     * instead of leaving a gap. Delegated so the builder, the read-only view and the export all
     * number a day's rows the same way.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function slotLabels(): array
    {
        return app(RozpisService::class)->labelSlots($this->slots);
    }

    /**
     * Every active position, for the "add position" dropdown.
     *
     * Nothing is filtered out by already being on the day: adding a second Bufet is the whole
     * point.
     *
     * @return Collection<int, Position>
     */
    #[Computed]
    public function availablePositions(): Collection
    {
        return Position::selectable()->get();
    }

    /**
     * The days a single slot may be copied onto: this week's other six, ready to render.
     *
     * Narrower than copySources(), which also offers last week as a *source* — copying a row
     * forward into a week that is already worked would be nonsense.
     *
     * @return list<array{date: string, label: string}>
     */
    #[Computed]
    public function copyTargets(): array
    {
        $team = app(Team::class);
        $weeks = app(WeekService::class);

        return $weeks->days($weeks->start($team, $this->dayCarbon))
            ->reject(fn (CarbonImmutable $day): bool => $day->isSameDay($this->dayCarbon))
            ->map(fn (CarbonImmutable $day): array => $this->dayOption($day))
            ->values()
            ->all();
    }

    /**
     * One day, as a value the view can print without formatting anything itself.
     *
     * @return array{date: string, label: string}
     */
    private function dayOption(CarbonImmutable $day): array
    {
        return [
            'date' => $day->toDateString(),
            'label' => Str::title($day->locale('sk')->dayName).' '.$day->format('d.m.'),
        ];
    }

    /**
     * The days this one's layout may be copied from — the same list the controller validates
     * the submitted source against, so the dropdown and the guard cannot drift apart.
     *
     * @return list<array{date: string, label: string}>
     */
    #[Computed]
    public function copySources(): array
    {
        $team = app(Team::class);
        $weekStart = app(WeekService::class)->start($team, $this->dayCarbon);

        return app(RozpisService::class)->copySources($weekStart, $this->dayCarbon)
            ->map(fn (CarbonImmutable $day): array => $this->dayOption($day))
            ->values()
            ->all();
    }

    #[Computed]
    public function isHardToStaff(): bool
    {
        return app(FairnessService::class)->isHardToStaffDay(app(Team::class), $this->dayCarbon);
    }

    #[Computed]
    public function canBuild(): bool
    {
        return auth()->user()?->hasPermissionInTeam('assignment.assign-position', app(Team::class)) ?? false;
    }

    #[Computed]
    public function dayCarbon(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->date)->startOfDay();
    }

    /**
     * @return array<int, array{totalDays: int, avgWeight: float, priorityScore: float}>
     */
    #[Computed]
    public function fairness(): array
    {
        $scores = $this->initialFairness
            ?? app(FairnessService::class)->scores(app(Team::class), CarbonImmutable::now())->all();

        $this->initialFairness = null;

        return $scores;
    }

    /** Never worked inside the window: score 0, which sorts last. */
    public function scoreFor(int $userId): float
    {
        return (float) ($this->fairness[$userId]['priorityScore'] ?? 0.0);
    }

    /**
     * @return array{totalDays: int, avgWeight: float, priorityScore: float}
     */
    public function statsFor(int $userId): array
    {
        return $this->fairness[$userId] ?? ['totalDays' => 0, 'avgWeight' => 0.0, 'priorityScore' => 0.0];
    }

    public function render()
    {
        return view('livewire.rozpis-day');
    }

    /**
     * Loaded by date as well as id: the policy answers about the team and the lock, not about
     * *this* component's day, so without the date a replayed request could move an assignment
     * belonging to a different date.
     */
    private function assignmentForThisDay(int $assignmentId): Assignment
    {
        return Assignment::with('user')
            ->where('date', $this->date)
            ->findOrFail($assignmentId);
    }

    private function clear(Assignment $assignment): void
    {
        $assignment->update([
            'position_id' => null,
            'position_slot_id' => null,
            'start_time' => null,
            'end_time' => null,
        ]);

        $this->refresh();
    }

    private function refresh(): void
    {
        unset(
            $this->slots,
            $this->assignments,
            $this->unassignedPool,
            $this->slotLabels,
            $this->rows,
            $this->filledCount,
        );
    }
}
