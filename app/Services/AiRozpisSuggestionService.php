<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\PositionSlot;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Gemini\Data\Content;
use Gemini\Data\GenerationConfig;
use Gemini\Data\Schema;
use Gemini\Enums\DataType;
use Gemini\Enums\ResponseMimeType;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Optional draft overlay: asks Gemini to propose a full day's placements, which the manager then
 * accepts one at a time (or ignores).
 *
 * Two things this deliberately is not. It is not auto-fill - nothing here writes to
 * `assignments`; accepting a suggestion goes through RozpisDay::place() and the same
 * AssignmentPolicy check a manual drag does. And it is not trusted: every field of the reply is
 * re-resolved against a fresh database read before the manager can even see it, because the
 * checking is the load-bearing part of this feature.
 *
 * No names, e-mails or phone numbers leave the app. Each pool member is identified only by a
 * salted hash of their user id, and the hash -> assignment map stays local to the call.
 */
class AiRozpisSuggestionService
{
    /** Enough for a day's worth of placements as JSON; the reply is a short list, not prose. */
    private const int MAX_OUTPUT_TOKENS = 2048;

    /** Seven days of the same list. Generous, because a truncated reply is a dropped day. */
    private const int WEEK_MAX_OUTPUT_TOKENS = 8192;

    /** Long enough that a collision is not a practical concern, short enough to read in a log. */
    private const int TOKEN_LENGTH = 16;

    /** Nobody worked inside the lookback window - no claim in either direction. */
    private const array NO_HISTORY = [
        'totalDays' => 0,
        'avgWeight' => 0.0,
        'earnedCredit' => 0.0,
        'hardDayDebt' => 0.0,
    ];

    /**
     * Memoised per instance: scores() is one query over the whole lookback window, and a single
     * suggest() call needs the same answer three times (pool, payload, re-verify).
     *
     * @var Collection<int, array{totalDays: int, avgWeight: float, earnedCredit: float, hardDayDebt: float}>|null
     */
    private ?Collection $cachedScores = null;

    public function __construct(
        private readonly FairnessService $fairness,
        private readonly WeekService $weeks,
    ) {}

    /** No key configured, no feature: the button never renders and nothing calls out. */
    public function enabled(): bool
    {
        return filled(config('gemini.api_key'));
    }

    /**
     * Propose placements for one day.
     *
     * Returns validated, ready-to-apply pairs - or an empty list for every failure mode there
     * is (disabled, nothing to place, network error, malformed reply, safety block, raced data).
     * A shrunken suggestion is always preferable to a partially trusted one.
     *
     * @return list<array{assignment_id: int, slot_id: int}>
     */
    public function suggest(Team $team, CarbonImmutable $date): array
    {
        if (! $this->enabled()) {
            return [];
        }

        $pool = $this->pool($team, $date);
        $slots = $this->emptySlots($date);

        if ($pool->isEmpty() || $slots->isEmpty()) {
            return [];
        }

        // The hash -> assignment map never leaves this method.
        $tokens = $this->tokenise($pool);

        try {
            $placements = $this->ask(
                $this->payload($team, $date, $tokens, $slots),
                $this->instructions(),
                self::MAX_OUTPUT_TOKENS,
            );
        } catch (Throwable $e) {
            // A billed third-party call that failed is not an application error - the manager
            // simply gets no suggestion and carries on placing people by hand.
            Log::warning('AI rozpis suggestion failed.', ['exception' => $e->getMessage()]);

            return [];
        }

        return $this->verify($placements, $tokens, $team, $date);
    }

    /**
     * Propose placements for all seven days at once, keyed by date.
     *
     * Worth its own path rather than seven suggest() calls: those cannot see each other, so each
     * one independently hands its day to whoever ranks highest, and the person at the top of the
     * queue collects a shift on every day of the week. One request sees the whole week, so it can
     * spread the work - and it is given each person's recommended day count
     * (FairnessService::weeklyTargets) to spread it against.
     *
     * Tokens are per person here rather than per signup, because a person spans days. Everything
     * else holds: no names leave the app, and every pair comes back through the same fresh-read
     * verification a single day's suggestion does.
     *
     * @return array<string, list<array{assignment_id: int, slot_id: int}>>
     */
    public function suggestWeek(Team $team, CarbonImmutable $weekStart): array
    {
        if (! $this->enabled()) {
            return [];
        }

        [$from, $to] = $this->weeks->range($weekStart);

        $signups = Assignment::betweenDates($from, $to)->get();
        $slots = PositionSlot::with('position')->betweenDates($from, $to)->get();
        $openSlots = $this->openSlotsIn($signups, $slots);

        if ($signups->whereNull('position_id')->isEmpty() || $openSlots->isEmpty()) {
            return [];
        }

        // One token per person for the whole week - the reply says "this person, this slot", and
        // the slot already carries the date.
        $salt = Str::random(32);
        $tokens = $signups->pluck('user_id')->unique()->mapWithKeys(fn (int $userId): array => [
            substr(hash('sha256', $salt.'|'.$userId), 0, self::TOKEN_LENGTH) => $userId,
        ])->all();

        try {
            $placements = $this->ask(
                $this->weekPayload($team, $weekStart, $tokens, $signups, $openSlots),
                $this->weekInstructions(),
                self::WEEK_MAX_OUTPUT_TOKENS,
            );
        } catch (Throwable $e) {
            Log::warning('AI rozpis week suggestion failed.', ['exception' => $e->getMessage()]);

            return [];
        }

        return $this->verifyWeek($placements, $tokens, $from, $to);
    }

    /**
     * The slots this reply may fill, in the order it should fill them: no shift-leader rows, none
     * already occupied, earliest day and earliest start first.
     *
     * One definition for both paths - a single day's suggestion and the whole week's - because
     * two copies of "what the model is allowed to touch" is two chances to disagree about it. The
     * date is part of the sort so a week's collection comes out day by day; for a single day it
     * is a constant and the start time decides, exactly as before.
     *
     * @param  Collection<int, PositionSlot>  $slots
     * @param  Collection<int, int>  $takenSlotIds
     * @return Collection<int, PositionSlot>
     */
    private function assignableSlots(Collection $slots, Collection $takenSlotIds): Collection
    {
        return $slots
            ->reject(fn (PositionSlot $slot): bool => $slot->isManagerSlot() || $takenSlotIds->contains($slot->getKey()))
            // One closure returning an array - see RozpisService::sortSlots() for why an array of
            // closures silently sorts these backwards.
            ->sortBy(fn (PositionSlot $slot): array => [
                $slot->date->toDateString(),
                $slot->start_time ?? '99:99:99',
                $slot->position->sort_order,
            ])
            ->values();
    }

    /**
     * The week's slots nobody occupies.
     *
     * @param  Collection<int, Assignment>  $signups
     * @param  Collection<int, PositionSlot>  $slots
     * @return Collection<int, PositionSlot>
     */
    private function openSlotsIn(Collection $signups, Collection $slots): Collection
    {
        return $this->assignableSlots($slots, $signups->pluck('position_slot_id')->filter());
    }

    /**
     * Everything the model may know about the week: the people once, then the days.
     *
     * @param  array<string, int>  $tokens  token -> user_id
     * @param  Collection<int, Assignment>  $signups
     * @param  Collection<int, PositionSlot>  $openSlots
     * @return array<string, mixed>
     */
    private function weekPayload(
        Team $team,
        CarbonImmutable $weekStart,
        array $tokens,
        Collection $signups,
        Collection $openSlots,
    ): array {
        $scores = $this->scores($team);
        // Only the slots this reply may fill. Counting manager rows (which are off limits) or
        // rows already taken by hand would hand everybody a budget bigger than the work, and the
        // budget is the only thing stopping one person collecting a shift on all seven days.
        $targets = $this->fairness->weeklyTargets($signups, $openSlots->count(), $scores->all());

        // Days somebody has already been placed on by hand count against their target - the model
        // is filling the remainder of the week, not planning it from scratch.
        $alreadyPlaced = $signups->whereNotNull('position_slot_id')->countBy('user_id');
        $available = $signups->whereNull('position_id')->groupBy(
            fn (Assignment $assignment): string => $assignment->date->toDateString()
        );

        $people = [];

        foreach ($tokens as $token => $userId) {
            $stats = $scores[$userId] ?? self::NO_HISTORY;

            $people[] = [
                'token' => $token,
                'earnedCredit' => $stats['earnedCredit'],
                'hardDayDebt' => $stats['hardDayDebt'],
                'avgWeight' => $stats['avgWeight'],
                'totalDays' => $stats['totalDays'],
                'signedUpDays' => $targets[$userId]['signups'] ?? 0,
                'recommendedDays' => $targets[$userId]['target'] ?? 0,
                'alreadyPlacedDays' => $alreadyPlaced[$userId] ?? 0,
            ];
        }

        // Strongest claim on the week's work first - the same key the weekly budget is derived
        // from, so the order the model reads and the budget it is held to cannot disagree.
        usort($people, fn (array $a, array $b): int => $b['earnedCredit'] <=> $a['earnedCredit']);

        $byDate = $openSlots->groupBy(fn (PositionSlot $slot): string => $slot->date->toDateString());
        $tokenOf = array_flip($tokens);

        $days = $this->weeks->days($weekStart)
            ->map(function (CarbonImmutable $day) use ($byDate, $available, $tokenOf, $team): ?array {
                $key = $day->toDateString();
                $daySlots = $byDate->get($key);

                if (! $daySlots) {
                    return null;
                }

                return [
                    'date' => $key,
                    'weekday' => $day->format('l'),
                    'dayWeight' => $this->fairness->dayWeight($team, $day),
                    'isHardToStaffDay' => $this->fairness->isHardToStaffDay($team, $day),
                    'isDesirableDay' => $this->fairness->isDesirableDay($team, $day),
                    // Only these people signed up for this day. Nobody else may be placed on it.
                    'availableTokens' => $available->get($key, collect())
                        ->map(fn (Assignment $assignment): string => $tokenOf[$assignment->user_id])
                        ->unique()
                        ->values()
                        ->all(),
                    'slots' => $this->describe($daySlots->values()),
                ];
            })
            ->filter()
            ->values()
            ->all();

        $weights = $team->fairnessDayWeights();

        return [
            'ordinaryDayWeight' => FairnessService::ORDINARY_DAY_WEIGHT,
            'lowestDayWeight' => min($weights),
            'highestDayWeight' => max($weights),
            'people' => $people,
            'days' => $days,
        ];
    }

    /**
     * The week prompt. Shares the domain briefing with the single-day one and replaces the
     * choosing rules, because spreading work across seven days is a different problem from
     * filling one.
     */
    private function weekInstructions(): string
    {
        return <<<'PROMPT'
        # Role

        You build the weekly shift plan ("rozpis") for a multiplex cinema. You are given the people
        who volunteered this week and, for each day, the slots that still need staffing. You pair
        them up for the whole week at once. A human shift manager reviews every placement and
        accepts or rejects each one, so your job is a solid first draft, not a final decision.

        A slot is one row of the plan: one job, one person, on one specific day. The same job often
        appears several times in a day - a busy Friday may need three people on the bufet, listed
        as "Bufet 1", "Bufet 2", "Bufet 3". Those are separate slots needing separate people.

        # Input

        A single JSON object:

        - `ordinaryDayWeight`, `lowestDayWeight`, `highestDayWeight` - the scale every `dayWeight`
          and `avgWeight` below is measured on. `ordinaryDayWeight` is a routine weekday;
          `highestDayWeight` is the day nobody volunteers for; `lowestDayWeight` is the one
          everybody wants, typically because it pays better.
        - `people` - the volunteers for the week, **already sorted strongest claim first**. Never
          any names; each has:
          - `token` - an opaque identifier, the same person across every day of the week.
          - `earnedCredit` - **what they have already done for the cinema**: the sum of the day
            weights of every shift they worked in the recent history window. It rises with how
            much somebody works *and* with how unpopular the days they worked were, so a Friday
            counts for more than a Saturday. High = they have earned the good shifts.
          - `hardDayDebt` - **how much they owe the cinema an unpopular day.** Positive means they
            have been taking fewer hard days than this cinema's people typically do, scaled by how
            much they work. High = they are the one to ask next. Negative means they have been
            carrying more than their share and should be spared for now.
          - `totalDays` - shifts worked in the recent history window; a rough proxy for experience.
          - `avgWeight` - the average day weight they have worked. Near `highestDayWeight` means
            they routinely take the unpopular days; near `lowestDayWeight` means they mostly pick
            the sought-after ones.
          - `signedUpDays` - how many days this week they made themselves available for.
          - `recommendedDays` - **how many shifts this person should get this week.** Already
            balances their availability against their `earnedCredit`. This is your budget per person.
          - `alreadyPlacedDays` - shifts the manager has already given them by hand this week.
            These count against `recommendedDays`.
        - `days` - one entry per day that still has open slots, in week order. Each has `date`,
          `weekday`, `dayWeight` (what one shift that day is worth - high means few volunteer),
          `isHardToStaffDay`, `isDesirableDay`, `availableTokens` and `slots` (sorted earliest
          start first, each with `slot_id`, `name`, `code`, `startTime`).

        Every slot you are given is an ordinary slot. Shift-leader rows ("vedúci zmeny") are
        assigned by hand and have already been removed from this input - you will never see one.

        # Hard rules - a reply that breaks any of these is discarded

        1. Use only `token` values from `people` and only `slot_id` values from `days[].slots`,
           copied exactly.
        2. A person may be placed on a slot **only if their token appears in that day's
           `availableTokens`**. They volunteered for specific days; nobody may be scheduled on a
           day they did not sign up for.
        3. Each `slot_id` at most once across the whole reply.
        4. At most one slot per person per day. Across the week a person may and should work
           several days - but never two slots on the same date.
        5. Never invent a person, a slot or a day.
        6. More slots than available people on a day: leave the surplus out. Do not pad.

        # The budget: how much each person should work this week

        This is the part that matters most, and the part a day-at-a-time planner gets wrong.

        For every person, keep a running count of the slots you have given them **so far in this
        reply**, and add `alreadyPlacedDays` to it. Call that their *load*. Their budget is
        `recommendedDays`.

        - `load < recommendedDays` - **under-loaded. Favour this person.** They are the ones the
          week still owes work to. Reach for them first.
        - `load == recommendedDays` - **done.** They have had their share. Do not give them more
          while anybody under-loaded is available for that slot.
        - `load > recommendedDays` - **over-loaded. Penalise this person.** Only place them when a
          slot would otherwise stay empty, and prefer whoever is least far over.

        Somebody who already has a lot of shifts this week is *not* a good candidate for the next
        one, however high their `earnedCredit`. The scores decide who is favoured **between people
        with the same load**, never a reason to pile a fourth day onto someone whose budget is two.
        Spreading the work across the team is the whole point of planning the week in one pass.

        Work the week in passes rather than filling day one to exhaustion: give everybody their
        first shift before you give anybody their second, everybody their second before anybody's
        third, and so on. A plan where three people work five days each and eight people work none
        is a failure even if every slot is filled.

        # How to choose

        1. Order the days by need: `isHardToStaffDay` first, then descending `dayWeight`. These are
           the shifts nobody volunteers for, so they get the pick of the available people. An easy
           Tuesday can be filled from whoever is left.
        2. Inside a day, work down `slots` in the given order - earliest start first.
        3. For each slot, out of the people whose token is in that day's `availableTokens` and who
           have no slot yet on that date, choose:
           a. the lowest load relative to their budget (most under-loaded first);
           b. break ties on **the ranking that matches the day**:
              - `isHardToStaffDay` → the higher `hardDayDebt`. Somebody has to take the Friday, and
                it should be whoever has been dodging them, not whoever always covers them.
              - `isDesirableDay` → the higher `earnedCredit`. This day is the thanks for the hard
                ones, so it goes to whoever has done most for the cinema.
              - neither → the higher `earnedCredit`.
           c. break remaining ties on the higher `totalDays`, preferring the more experienced hand.
        4. Full coverage beats the budget. If a slot has no under-loaded candidate left, fill it
           with the least over-loaded available person rather than leaving the cinema unstaffed.
        5. `hardDayDebt` near zero means two opposite things and only one of them is a reason to
           spare somebody: a regular who already carries their share, and a newcomer who has barely
           worked at all. Read it with `totalDays`. The newcomer has not earned an exemption from
           the unpopular days - they simply have no history yet.
        6. Stop when every slot is filled or no eligible person remains. People left unplaced is a
           correct outcome - they are the week's náhradníci.

        # Sanity check before you answer

        Re-read your own list and confirm: no slot twice; nobody twice on one date; nobody on a day
        their token was not listed as available for; and no one materially over `recommendedDays`
        while somebody else who was available for that day sits under theirs. Fix any of these
        before replying.

        # Output

        The JSON object described by the response schema, and nothing else. No commentary, no
        explanation, no markdown fence.
        PROMPT;
    }

    /**
     * Re-resolve a week's reply against a fresh read, same contract as verify().
     *
     * The slot carries the date, so the person is matched to *their* signup on *that* day. One
     * slot once, one person once per day - a person may legitimately appear on several days.
     *
     * @param  list<array<string, mixed>>  $placements
     * @param  array<string, int>  $tokens  token -> user_id
     * @return array<string, list<array{assignment_id: int, slot_id: int}>>
     */
    private function verifyWeek(array $placements, array $tokens, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $signups = Assignment::betweenDates($from, $to)->get();
        $slots = PositionSlot::with('position')->betweenDates($from, $to)->get();

        $openSlots = $this->openSlotsIn($signups, $slots)->keyBy('id');

        // The one assignment each person still holds unplaced on each date.
        $unplaced = $signups->whereNull('position_id')->keyBy(
            fn (Assignment $assignment): string => $assignment->user_id.'|'.$assignment->date->toDateString()
        );

        $verified = [];
        $usedSlots = [];
        $usedPersonDays = [];

        foreach ($placements as $placement) {
            $token = $placement['token'] ?? null;
            $slotId = $placement['slot_id'] ?? null;

            if (! is_string($token) || ! is_int($slotId) || in_array($slotId, $usedSlots, true)) {
                continue;
            }

            $userId = $tokens[$token] ?? null;
            $slot = $openSlots->get($slotId);

            if ($userId === null || ! $slot) {
                continue;
            }

            $date = $slot->date->toDateString();
            $personDay = $userId.'|'.$date;

            // Signed up for this exact date, still unplaced, and not already given a slot earlier
            // in this same reply.
            $assignment = $unplaced->get($personDay);

            if (! $assignment || in_array($personDay, $usedPersonDays, true)) {
                continue;
            }

            $usedSlots[] = $slotId;
            $usedPersonDays[] = $personDay;

            $verified[$date][] = [
                'assignment_id' => (int) $assignment->getKey(),
                'slot_id' => $slotId,
            ];
        }

        return $verified;
    }

    /**
     * Everyone who signed up for this date and is not placed yet, best claim first.
     *
     * @return Collection<int, Assignment>
     */
    private function pool(Team $team, CarbonImmutable $date): Collection
    {
        $scores = $this->scores($team);
        $claims = $this->claimsFor($team, $date, $scores);

        return Assignment::where('date', $date->toDateString())
            ->whereNull('position_id')
            ->get()
            ->sortByDesc(fn (Assignment $a): float => $claims[$a->user_id] ?? 0.0)
            ->values();
    }

    /**
     * This date's slots that nobody occupies, earliest start first.
     *
     * @return Collection<int, PositionSlot>
     */
    private function emptySlots(CarbonImmutable $date): Collection
    {
        $taken = Assignment::where('date', $date->toDateString())
            ->whereNotNull('position_slot_id')
            ->pluck('position_slot_id');

        return $this->assignableSlots(
            PositionSlot::with('position.group')->where('date', $date->toDateString())->get(),
            $taken,
        );
    }

    /**
     * @return Collection<int, array{totalDays: int, avgWeight: float, earnedCredit: float, hardDayDebt: float}>
     */
    private function scores(Team $team): Collection
    {
        return $this->cachedScores ??= $this->fairness->scores($team, CarbonImmutable::now());
    }

    /**
     * Everyone's claim on one day, keyed by user_id - the `claim` field the day prompt ranks on.
     *
     * Whichever of the two fairness rankings that day is decided by: `hardDayDebt` when somebody
     * has to be asked to take it, `earnedCredit` when it is the day people want, and plain
     * `earnedCredit` on an ordinary weekday that settles neither. Always "higher is a stronger
     * claim", so the pool sorts descending in every case and the prompt needs one rule rather
     * than two opposite ones.
     *
     * The day's ranking key is resolved once here rather than per candidate: it is a property of
     * the date, and the date does not change while we sort.
     *
     * @param  Collection<int, array{totalDays: int, avgWeight: float, earnedCredit: float, hardDayDebt: float}>  $scores
     * @return array<int, float>
     */
    private function claimsFor(Team $team, CarbonImmutable $date, Collection $scores): array
    {
        $key = $this->fairness->rankingKeyOrMerit($team, $date);

        return $scores->keys()
            ->mapWithKeys(fn (int $userId): array => [
                $userId => $this->fairness->rank($scores->all(), $userId, $key),
            ])
            ->all();
    }

    /**
     * Opaque token -> assignment, in pool order.
     *
     * The token is a salted SHA-256 of the user id, truncated. The salt is regenerated per call,
     * so the same person hashes differently on every request: the value identifies nobody, and
     * two requests cannot be correlated into a history of who works where. It exists only so the
     * reply can point back at a person without a name ever being sent.
     *
     * @param  Collection<int, Assignment>  $pool
     * @return array<string, Assignment>
     */
    private function tokenise(Collection $pool): array
    {
        $salt = Str::random(32);
        $tokens = [];

        foreach ($pool as $assignment) {
            $token = substr(hash('sha256', $salt.'|'.$assignment->user_id), 0, self::TOKEN_LENGTH);

            $tokens[$token] = $assignment;
        }

        return $tokens;
    }

    /**
     * The whole request, as data. Everything the model is allowed to know about this day.
     *
     * @param  array<string, Assignment>  $tokens
     * @param  Collection<int, PositionSlot>  $slots
     * @return array<string, mixed>
     */
    private function payload(Team $team, CarbonImmutable $date, array $tokens, Collection $slots): array
    {
        $scores = $this->scores($team);
        $claims = $this->claimsFor($team, $date, $scores);
        $weights = $team->fairnessDayWeights();

        $people = [];

        foreach ($tokens as $token => $assignment) {
            $stats = $scores[$assignment->user_id] ?? self::NO_HISTORY;

            $people[] = [
                'token' => $token,
                'claim' => round($claims[$assignment->user_id] ?? 0.0, 2),
                'avgWeight' => $stats['avgWeight'],
                'totalDays' => $stats['totalDays'],
            ];
        }

        return [
            'weekday' => $date->format('l'),
            'dayWeight' => $this->fairness->dayWeight($team, $date),
            'ordinaryDayWeight' => FairnessService::ORDINARY_DAY_WEIGHT,
            'lowestDayWeight' => min($weights),
            'highestDayWeight' => max($weights),
            'isHardToStaffDay' => $this->fairness->isHardToStaffDay($team, $date),
            'isDesirableDay' => $this->fairness->isDesirableDay($team, $date),
            // Already sorted best-claim-first and earliest-slot-first, so the model pairs two
            // ranked lists rather than inventing its own notion of fairness.
            'people' => $people,
            // One entry per *row* of the day, so a day with three bufet rows lists three
            // independent slots that each need their own person.
            'slots' => $this->describe($slots),
        ];
    }

    /**
     * Slots as the model sees them: an id to answer with, and a name numbered when the day
     * repeats a position ("Bufet 1", "Bufet 2") so the reply is legible in a log.
     *
     * @param  Collection<int, PositionSlot>  $slots
     * @return list<array<string, mixed>>
     */
    private function describe(Collection $slots): array
    {
        $totals = $slots->countBy('position_id');
        $seen = [];

        return $slots->map(function (PositionSlot $slot) use ($totals, &$seen): array {
            $ordinal = $seen[$slot->position_id] = ($seen[$slot->position_id] ?? 0) + 1;

            return [
                'slot_id' => $slot->getKey(),
                'name' => $slot->label($ordinal, $totals[$slot->position_id] > 1),
                'code' => $slot->position->code,
                'startTime' => $slot->start_time ? substr((string) $slot->start_time, 0, 5) : null,
            ];
        })->all();
    }

    /**
     * One billed request. Structured output, so a shape mismatch is the API's problem rather
     * than ours to parse around: the reply is guaranteed-shaped JSON or an exception.
     *
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function ask(array $payload, string $instructions, int $maxOutputTokens): array
    {
        $response = Gemini::generativeModel(model: config('gemini.model'))
            ->withSystemInstruction(Content::parse($instructions))
            ->withGenerationConfig(new GenerationConfig(
                maxOutputTokens: $maxOutputTokens,
                // The task is constraint satisfaction over a pre-ranked list. Creativity here
                // would only mean drifting off the given order.
                temperature: 0.0,
                responseMimeType: ResponseMimeType::APPLICATION_JSON,
                responseSchema: $this->schema(),
            ))
            ->generateContent(json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        // Throws when the reply was blocked or came back without a text part - caught by
        // suggest(), which turns it into "no suggestion".
        $decoded = json_decode($response->text(), true);

        return is_array($decoded['placements'] ?? null) ? $decoded['placements'] : [];
    }

    /** Shared by both prompts: a slot_id already says which day it belongs to. */
    private function schema(): Schema
    {
        return new Schema(
            type: DataType::OBJECT,
            description: 'The chosen placements.',
            properties: [
                'placements' => new Schema(
                    type: DataType::ARRAY,
                    description: 'One entry per filled slot. May be shorter than the slot list.',
                    items: new Schema(
                        type: DataType::OBJECT,
                        properties: [
                            'token' => new Schema(
                                type: DataType::STRING,
                                description: 'The token of the person, copied exactly from people[].token.',
                            ),
                            'slot_id' => new Schema(
                                type: DataType::INTEGER,
                                description: 'The slot_id, copied exactly from slots[].slot_id.',
                            ),
                        ],
                        required: ['token', 'slot_id'],
                    ),
                ),
            ],
            required: ['placements'],
        );
    }

    /**
     * The system prompt.
     *
     * Written as a full brief rather than a one-liner because the model is deciding who works
     * where in a real workplace: it needs the domain, what each number means, and - most of all -
     * which rules are hard. Every hard rule below is also enforced in verify() against a fresh
     * database read; the prompt asks, the code guarantees. The soft preferences are safe to leave
     * unenforced because no answer they influence can be invalid.
     */
    private function instructions(): string
    {
        return <<<'PROMPT'
        # Role

        You build the daily shift plan ("rozpis") for a multiplex cinema. For one calendar day you
        are given the people who have already volunteered to work that day and the slots the
        cinema needs staffed, and you pair them up. A human shift manager reviews every placement
        you propose and accepts or rejects each one, so your job is a solid first draft, not a
        final decision.

        A slot is one row of the plan: one job, one person. The same job often appears several
        times in a day - a busy Friday may need three people on the bufet, listed as "Bufet 1",
        "Bufet 2" and "Bufet 3". Those are three separate slots with three separate `slot_id`
        values and they each need their own, different person.

        # Input

        A single JSON object:

        - `weekday`, `dayWeight`, `ordinaryDayWeight`, `lowestDayWeight`, `highestDayWeight`,
          `isHardToStaffDay`, `isDesirableDay` - `dayWeight` is what one shift on this day is worth
          to the cinema. `ordinaryDayWeight` is a routine weekday. At or near `highestDayWeight`
          (`isHardToStaffDay`) is a day almost nobody volunteers for; at or near `lowestDayWeight`
          (`isDesirableDay`) is one everybody wants, typically because it pays better.
        - `people` - the volunteers, **already sorted so the person with the strongest claim on
          this day comes first**. Each has:
          - `token` - an opaque identifier. You will never see names; this is deliberate.
          - `claim` - how strong their claim on **this** day is. Already computed for the kind of
            day this is: on a hard-to-staff day it measures how much they owe the cinema an
            unpopular shift, on a sought-after day how much they have earned one by working a lot
            and taking the hard days; on an ordinary weekday it is simply how much they have
            earned. Always: higher is earlier in the queue.
          - `totalDays` - shifts worked in the recent history window. A rough proxy for
            experience.
          - `avgWeight` - the average `dayWeight` of the shifts they have worked. Near
            `highestDayWeight` means they routinely take the unpopular days; near
            `lowestDayWeight` means they mostly pick the sought-after ones.
        - `slots` - the still-unfilled slots, **already sorted earliest start time first**. Each
          has `slot_id`, `name` (numbered when the day repeats a job), `code` and `startTime`.

        Every slot you are given is an ordinary slot. Shift-leader rows ("vedúci zmeny") are
        assigned by hand and have already been removed from this input - you will never see one.

        # Hard rules - a reply that breaks any of these is discarded

        1. Use only `token` values that appear in `people`, copied exactly.
        2. Use only `slot_id` values that appear in `slots`, copied exactly.
        3. Each token at most once. Nobody works two slots on the same day.
        4. Each `slot_id` at most once. A slot holds one person.
        5. Never invent a person, a slot, or a placement for anyone not listed. The people in
           `people` volunteered for this specific day; nobody else may be scheduled.
        6. If there are more slots than people, leave the surplus slots out of your reply
           entirely. Do not pad the list.

        # How to choose

        1. Work down `slots` in the order given. For each, take the highest-`claim` person still
           unplaced. The given order is authoritative - do not re-derive fairness from `totalDays`
           and `avgWeight` or second-guess the ranking; `claim` already accounts for both, and for
           what kind of day this is.
        2. Break a tie in `claim` on the higher `totalDays` - the more experienced hand.
        3. Once every slot has someone, stop. People left unplaced is a correct outcome.
        4. If there are more people than slots, every slot must still be filled - do not leave a
           slot empty because the remaining candidates have low scores. Full coverage wins once
           the people with the strongest claims have been placed.

        # Output

        The JSON object described by the response schema, and nothing else. No commentary, no
        explanation, no markdown fence.
        PROMPT;
    }

    /**
     * Re-resolve every field against a fresh read of the database.
     *
     * The reply is treated as a suggestion about state, never as state. Anything that does not
     * resolve is dropped, which shrinks the suggestion - there is no partial trust, and nothing
     * unverified ever reaches the manager as a clickable "Prijať".
     *
     * @param  list<array<string, mixed>>  $placements
     * @param  array<string, Assignment>  $tokens
     * @return list<array{assignment_id: int, slot_id: int}>
     */
    private function verify(array $placements, array $tokens, Team $team, CarbonImmutable $date): array
    {
        // Re-queried rather than reused: someone may have been placed by hand, or withdrawn
        // their signup, while the request was in flight.
        $stillOpen = $this->emptySlots($date)->map(fn (PositionSlot $slot): int => $slot->getKey());
        $stillUnplaced = $this->pool($team, $date)->keyBy('id');

        $verified = [];
        $usedTokens = [];
        $usedSlots = [];

        foreach ($placements as $placement) {
            $token = $placement['token'] ?? null;
            $slotId = $placement['slot_id'] ?? null;

            if (! is_string($token) || ! is_int($slotId)) {
                continue;
            }

            if (in_array($token, $usedTokens, true) || in_array($slotId, $usedSlots, true)) {
                continue;
            }

            $assignment = $tokens[$token] ?? null;

            if (! $assignment || ! $stillOpen->contains($slotId)) {
                continue;
            }

            // The signed-up invariant, enforced rather than assumed: this person must still
            // hold a real, unplaced assignment for this exact date.
            if (! $stillUnplaced->has($assignment->getKey())) {
                continue;
            }

            $usedTokens[] = $token;
            $usedSlots[] = $slotId;

            $verified[] = [
                'assignment_id' => (int) $assignment->getKey(),
                'slot_id' => $slotId,
            ];
        }

        return $verified;
    }
}
