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
 * Two things this deliberately is not. It is not auto-fill — nothing here writes to
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

    /** Long enough that a collision is not a practical concern, short enough to read in a log. */
    private const int TOKEN_LENGTH = 16;

    /**
     * Memoised per instance: scores() is one query over the whole lookback window, and a single
     * suggest() call needs the same answer three times (pool, payload, re-verify).
     *
     * @var Collection<int, array{totalDays: int, avgWeight: float, priorityScore: float}>|null
     */
    private ?Collection $cachedScores = null;

    public function __construct(private readonly FairnessService $fairness) {}

    /** No key configured, no feature: the button never renders and nothing calls out. */
    public function enabled(): bool
    {
        return filled(config('gemini.api_key'));
    }

    /**
     * Propose placements for one day.
     *
     * Returns validated, ready-to-apply pairs — or an empty list for every failure mode there
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
            $placements = $this->ask($this->payload($team, $date, $tokens, $slots));
        } catch (Throwable $e) {
            // A billed third-party call that failed is not an application error — the manager
            // simply gets no suggestion and carries on placing people by hand.
            Log::warning('AI rozpis suggestion failed.', ['exception' => $e->getMessage()]);

            return [];
        }

        return $this->verify($placements, $tokens, $team, $date);
    }

    /**
     * Everyone who signed up for this date and is not placed yet, best claim first.
     *
     * @return Collection<int, Assignment>
     */
    private function pool(Team $team, CarbonImmutable $date): Collection
    {
        $scores = $this->scores($team);

        return Assignment::where('date', $date->toDateString())
            ->whereNull('position_id')
            ->get()
            ->sortByDesc(fn (Assignment $a): float => (float) ($scores[$a->user_id]['priorityScore'] ?? 0.0))
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

        return PositionSlot::with('position')
            ->where('date', $date->toDateString())
            ->get()
            ->reject(fn (PositionSlot $slot): bool => $taken->contains($slot->getKey()))
            ->sortBy([
                fn (PositionSlot $slot): string => $slot->start_time ?? '99:99:99',
                fn (PositionSlot $slot): int => $slot->position->sort_order,
            ])
            ->values();
    }

    /**
     * @return Collection<int, array{totalDays: int, avgWeight: float, priorityScore: float}>
     */
    private function scores(Team $team): Collection
    {
        return $this->cachedScores ??= $this->fairness->scores($team, CarbonImmutable::now());
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
        $weights = $team->fairnessDayWeights();

        $people = [];

        foreach ($tokens as $token => $assignment) {
            $stats = $scores[$assignment->user_id] ?? ['totalDays' => 0, 'avgWeight' => 0.0, 'priorityScore' => 0.0];

            $people[] = [
                'token' => $token,
                'priorityScore' => $stats['priorityScore'],
                'avgWeight' => $stats['avgWeight'],
                'totalDays' => $stats['totalDays'],
            ];
        }

        return [
            'weekday' => $date->format('l'),
            'dayWeight' => $this->fairness->dayWeight($team, $date),
            'ordinaryDayWeight' => min($weights),
            'highestDayWeight' => max($weights),
            'isHardToStaffDay' => $this->fairness->isHardToStaffDay($team, $date),
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
                'isManagerRole' => (bool) $slot->position->is_manager,
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
    private function ask(array $payload): array
    {
        $response = Gemini::generativeModel(model: config('gemini.model'))
            ->withSystemInstruction(Content::parse($this->instructions()))
            ->withGenerationConfig(new GenerationConfig(
                maxOutputTokens: self::MAX_OUTPUT_TOKENS,
                // The task is constraint satisfaction over a pre-ranked list. Creativity here
                // would only mean drifting off the given order.
                temperature: 0.0,
                responseMimeType: ResponseMimeType::APPLICATION_JSON,
                responseSchema: $this->schema(),
            ))
            ->generateContent(json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        // Throws when the reply was blocked or came back without a text part — caught by
        // suggest(), which turns it into "no suggestion".
        $decoded = json_decode($response->text(), true);

        return is_array($decoded['placements'] ?? null) ? $decoded['placements'] : [];
    }

    private function schema(): Schema
    {
        return new Schema(
            type: DataType::OBJECT,
            description: 'The placements chosen for this day.',
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
     * where in a real workplace: it needs the domain, what each number means, and — most of all —
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
        times in a day — a busy Friday may need three people on the bufet, listed as "Bufet 1",
        "Bufet 2" and "Bufet 3". Those are three separate slots with three separate `slot_id`
        values and they each need their own, different person.

        # Input

        A single JSON object:

        - `weekday`, `dayWeight`, `ordinaryDayWeight`, `highestDayWeight`, `isHardToStaffDay` —
          `dayWeight` is what one shift on this day is worth to the cinema. A weight at or near
          `highestDayWeight` marks a day almost nobody volunteers for; `ordinaryDayWeight` is a
          routine day. When `isHardToStaffDay` is true, who gets which position matters more,
          because somebody has to be asked to take the unpopular work.
        - `people` — the volunteers, **already sorted so the person with the strongest claim on
          this day comes first**. Each has:
          - `token` — an opaque identifier. You will never see names; this is deliberate.
          - `priorityScore` — how strong their claim is. It already combines how much they work
            overall with how many unpopular days they have taken, so a high score means "this
            person has earned the next good slot, or is next in line to be asked". Higher is
            earlier in the queue.
          - `totalDays` — shifts worked in the recent history window. A rough proxy for
            experience.
          - `avgWeight` — the average `dayWeight` of the shifts they have worked. Near
            `highestDayWeight` means they routinely take the unpopular days.
        - `slots` — the still-unfilled slots, **already sorted earliest start time first**. Each
          has `slot_id`, `name` (numbered when the day repeats a job), `code`, `startTime` and
          `isManagerRole`.

        # Hard rules — a reply that breaks any of these is discarded

        1. Use only `token` values that appear in `people`, copied exactly.
        2. Use only `slot_id` values that appear in `slots`, copied exactly.
        3. Each token at most once. Nobody works two slots on the same day.
        4. Each `slot_id` at most once. A slot holds one person.
        5. Never invent a person, a slot, or a placement for anyone not listed. The people in
           `people` volunteered for this specific day; nobody else may be scheduled.
        6. If there are more slots than people, leave the surplus slots out of your reply
           entirely. Do not pad the list.

        # How to choose

        1. Work down `slots` in the order given. For each, take the highest-`priorityScore`
           person still unplaced. The given order is authoritative — do not re-derive fairness
           from the raw numbers or second-guess the ranking.
        2. Prefer a person with a high `totalDays` for a slot where `isManagerRole` is true: that
           row runs the shift and wants someone experienced. This is a preference, not a rule —
           never break a hard rule above to satisfy it, and if it conflicts with the queue order,
           prefer the queue.
        3. Once every slot has someone, stop. People left unplaced is a correct outcome.
        4. If there are more people than slots, every slot must still be filled — do not leave a
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
     * resolve is dropped, which shrinks the suggestion — there is no partial trust, and nothing
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
