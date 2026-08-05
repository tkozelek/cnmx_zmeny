# 23 — Rozpis Builder: Position Assignment & Fairness Scoring

> Date: 2026-07-28
> Status: Planned — not yet implemented

---

## Context

Employees already self-sign-up for days via the existing `calendar.index` / `DayCard` flow (they just say "I'm available Thursday", no position). What's missing is the **manager's step**: once a week is locked, take that day's signed-up people and drag each one onto an actual position (bufet 1, uvádzač VIP, ...) with a start time — exactly what the reference file (`Rozpis zmien brigádnikov_CNMX ZA_08.08.-14.08..xlsx`) shows: per day, a manažér name, then rows of `meno / pozícia / čas nástupu`. The position list and count differ day to day (5 positions on Štvrtok, 7 on Piatok) because cinema staffing depends on the movie schedule.

Three things this plan covers, in order of how much they change the architecture:

1. **The rozpis builder itself** — no schema blocker: `assignments.position_id`/`start_time`/`end_time` already exist on the `Assignment` model, just unused (the position badge in `day-card.blade.php` is literally already there, `class="hidden"`). This is additive UI + policy work on top of what's built.
2. **"Copy positions from another day / last week" as empty slots** — confirmed with the user this means the position+time *layout* copies over as empty targets, independent of who ends up dragged onto them. The 2026-07-24 schema rewrite (`_planning/20-schema-rework-handoff.md`) deliberately removed any slot/template concept in favor of pure self-signup. This request reintroduces a minimal version of it — **one small table**, not the old `plan_slots`/`plan_templates`/`PlanAssignment` system doc 07 describes (no admin headcount requirement, no template library, no separate assignment join table). Flagging this plainly since it does reverse part of a recorded decision, just in the lightest form that still does the job.
3. **Weekend/Friday fairness scoring** — no prior design exists anywhere in `_planning/`. Built from scratch below. Per the user's note, the score must be normalized by how much someone works overall, not just their raw weekend count — otherwise a person who only ever works 2 weekend days a month reads as "just as loaded" as a full-timer who works weekends *and* five weekdays. The score is a ratio (weighted-weekend-share ÷ total days worked), not a raw weekend tally.

**Stack decision (confirmed with the user):** build in Livewire, not Filament. The planning docs (`18-packages.md`, `19-design-system.md`) recommend a Filament panel with `saade/filament-fullcalendar` for this, but Filament was never installed — every real feature since the rewrite (calendar, absences, users, week lock) is Livewire, following the `DayCard` pattern. Introducing Filament now just for this screen would mean standing up a whole panel before writing a line of rozpis code. `sortablejs` is already in `package.json`, just unwired — that's the drag-and-drop layer.

**Scope confirmed with the user:**
- Manual fairness scoring is **advisory only** — a score shown next to each name, pool sorted by it, manager places everyone by hand. On top of that, an **optional AI-generated draft** (below) can propose a full set of placements for the manager to accept or ignore — this is a later addition to the plan, added after the base design was confirmed, so it's called out as its own section rather than folded silently into "advisory only."
- **Position CRUD is in scope** — there's currently no way to create/edit/reorder positions except editing the seeder by hand, and this feature is unusable without it.
- **Every fairness parameter is a per-team setting, not a hardcoded constant** — the day weights *and* the lookback window are both editable on the settings page. Nothing about "how fair is fair" is baked into code.

---

## Data model additions

### 1. `position_slots` table (new)

```
id, team_id, date, position_id, start_time, end_time, timestamps
unique(team_id, date, position_id)
```

One row = "this position is offered on this day at this time" — independent of who (if anyone) is placed in it. This is what makes "copy from another day" possible: copying duplicates these rows (skip if a slot for that position already exists on the target date — copy is additive, never clobbers a day the manager already built).

`PositionSlot` model: `BelongsToTeam`, `belongsTo(Position)`, cast `date` as `date:Y-m-d` (same reasoning as `Assignment`/`WeekLock` — a plain `date` cast breaks the unique key and range lookups, see `app/Models/Assignment.php:44-47`).

Filling a slot does **not** add a new relation — it's read by matching `Assignment::where('date', $date)->where('position_id', $slot->position_id)->first()`. Dragging a person onto a slot just does `$assignment->update(['position_id' => $slot->position_id, 'start_time' => $slot->start_time, 'end_time' => $slot->end_time])`. No FK from `Assignment` to `PositionSlot` needed — the slot is a template/target, the assignment is still the one source of truth for who's actually working.

### 2. `team_settings.fairness_day_weights` (new column)

JSON array of 7 floats, Monday-indexed (matches the `weekDays` array already in `TeamSettingController@edit`, `app/Http/Controllers/Team/TeamSettingController.php:26-34`). Default `[1, 1, 1, 1, 1.3, 1.6, 1.6]` (Fri/Sat/Sun weighted up). `TeamSetting` gets a `DEFAULT_FAIRNESS_DAY_WEIGHTS` const alongside its existing `DEFAULT_*` consts, cast as `array`.

### 3. `team_settings.fairness_window_weeks` (new column)

`unsignedTinyInteger`, default `12`. How far back `FairnessService` looks when computing the ratio (see below) — a per-team dial, not a code constant, same pattern as the existing `week_lookahead`/`absence_deadline_days` columns on this same table. `TeamSetting` gets `DEFAULT_FAIRNESS_WINDOW_WEEKS = 12`.

Both new settings are editable on the existing `/sprava-kina` settings page — extend `UpdateTeamSettingRequest` and `resources/views/team/settings.blade.php` with 7 numeric weight inputs (reusing the `weekDays` label array already passed to the view) plus one number input for the window. Nothing about the fairness calculation is hardcoded — every team can tune both how much weekends count and how much history counts.

No other schema changes. `Assignment`, `Position`, `WeekLock` are untouched.

---

## Fairness scoring (`app/Services/FairnessService.php`)

```php
class FairnessService
{
    /** @return Collection<int, array{totalDays: int, avgWeight: float, priorityScore: float}> keyed by user_id */
    public function scores(Team $team, CarbonImmutable $asOf): Collection
    {
        $settings = $team->cachedSettings();
        $weights = $settings->fairness_day_weights;
        $ceiling = max($weights) + 1; // always above the highest configured day-weight

        $since = $asOf->subWeeks($settings->fairness_window_weeks);

        return Assignment::where('team_id', $team->id)
            ->whereNotNull('position_id')
            ->whereBetween('date', [$since->toDateString(), $asOf->toDateString()])
            ->get()
            ->groupBy('user_id')
            ->map(function (Collection $assignments) use ($weights, $ceiling): array {
                $total = $assignments->count();
                $weighted = $assignments->sum(fn (Assignment $a) => $weights[$a->date->dayOfWeekIso - 1]);
                $avgWeight = $total ? $weighted / $total : 0.0;

                return [
                    'totalDays' => $total,
                    'avgWeight' => $avgWeight,
                    // Rewards volume, penalizes a weekend-heavy ratio — see explanation below.
                    'priorityScore' => $total * ($ceiling - $avgWeight),
                ];
            });
    }
}
```

`avgWeight` is "how weekend-heavy is this person's typical shift, on average" — shown to the manager as context, no longer used directly as a sort key (see `priorityScore` below).

`priorityScore` is the actual ranking used to decide **who gets offered a weekend/Friday slot first** — confirmed with the user, this is a genuine reward/penalty system, not pure rotation:
- A person who takes a lot of shifts overall, mostly weekdays with some weekends mixed in, ranks **highest** — `totalDays` is large and `avgWeight` stays close to baseline, so `(ceiling - avgWeight)` stays large too. Reward for volume.
- A person who signs up **only for weekends** ranks low even if their raw weekend count looks high — their `avgWeight` sits near the ceiling, which shrinks their score down regardless of volume. Penalized for cherry-picking, per the user's explicit ask.
- A person who barely works at all (low `totalDays`) never jumps ahead of the regulars just because their few shifts happen to be weekdays — the `totalDays` multiplier keeps rare workers low too. Volume is a real gate, not just a ratio.

Worked example (weights `[1,1,1,1,1.3,1.6,1.6]`, ceiling `2.6`):

| Person | totalDays | avgWeight | priorityScore | Reads as |
|---|---|---|---|---|
| Mostly-weekday regular | 20 | 1.10 | 20 × 1.50 = 30.0 | Highest — reward |
| Weekend-only signer-upper | 4 | 1.60 | 4 × 1.00 = 4.0 | Penalized despite "all weekends" |
| Rare weekday-only worker | 2 | 1.00 | 2 × 1.60 = 3.2 | Low — barely works at all |
| Heavy all-rounder (works everything, incl. many weekends) | 20 | 1.45 | 20 × 1.15 = 23.0 | Still rewarded for volume, just less than the balanced regular |

Source is `Assignment` (the plan), not `Shift` (actual worked hours/payroll) — the rozpis feature operates entirely at the Assignment level, and historical `Shift` rows aren't guaranteed to exist for every past assignment. Noted as a place to revisit if Assignment history proves an incomplete proxy later.

Used in `RozpisDay` to sort/badge the unassigned pool by `priorityScore` (descending) when the day's own weight is above the team's baseline (i.e. a "premium" day) — plain weekday slots keep the existing alphabetical order (`DayCard`'s current behavior), so nothing changes for the common case. The **same** `priorityScore` also drives the AI suggestion's placement order below — one metric, not two competing ones.

**Hard invariant, unconditional:** both the manual advisory sort and the AI suggestion only ever rank people who already have a real, unassigned `Assignment` row for that exact date — i.e. people who signed up for that specific day themselves. Nobody is ever suggested or assigned to a day they didn't sign up for; `priorityScore` decides *which slot* a signed-up person is offered, never *whether* to invent a signup for someone who didn't make one.

---

## Rozpis builder (Livewire)

Mirrors the existing `CalendarService` → `DayCard` split, gated the opposite way: **editable only once the week is locked** (the trigger the user described), read-only/blocked before that.

- **`app/Services/RozpisService.php`** — assembles one week's data in bulk to avoid N+1 across 7 day components, same shape as `CalendarService::forWeek()` (`app/Services/CalendarService.php:25-52`): preload all `PositionSlot`s for the week (`betweenDates`), all `Assignment`s for the week (reuse `Assignment::betweenDates`), and `FairnessService::scores()` once, grouped by date for the parent Blade to hand to each `RozpisDay`.
- **`app/Http/Controllers/Rozpis/RozpisController.php`** — `show(Team, string $date)`: resolves week via `WeekService` (existing), 403s via `$this->authorize('viewRozpis', $team)` if the week isn't locked yet or the user lacks the permission. Route (no `role:` middleware — see note below): `GET /tyzden/{date}/rozpis` → `rozpis.show`.
- **`app/Livewire/RozpisDay.php`** (mirrors `DayCard.php` structure — `#[Locked]` date prop, `#[Computed]` derived state, `initialSlots`/`initialAssignments` preloaded once then unset):
  - `slots` — this date's `PositionSlot`s ordered by `position.sort_order`.
  - `unassignedPool` — this date's `Assignment`s with `position_id` null, sorted by `fairnessScore` when today's weight is elevated, else alphabetically.
  - `filledFor(int $positionId)` — the `Assignment` (if any) occupying a slot.
  - `place(int $assignmentId, int $positionId)` — `$this->authorize('assignPosition', $assignment)`, sets position/time from the matching slot.
  - `unplace(int $assignmentId)` — clears position/time, assignment falls back into the pool.
  - `addSlot(int $positionId, ?string $startTime)` / `removeSlot(int $slotId)` — manual slot management for days that don't match any previous layout.
- **`resources/views/livewire/rozpis-day.blade.php`** — position columns as SortableJS lists, unassigned pool as its own list, same `group` name scoped per day (`'rozpis-'.$date`) so items move between the pool and any column but never cross days. Reuses the card/badge visual language already established in `day-card.blade.php` (locked state styling, `wire:loading`, semantic colors from doc 19).
- **Alpine/SortableJS wiring** — `resources/js/app.js` gets the directive doc 18 sketched, wired to actually call the Livewire method:
  ```js
  Alpine.directive('sortable', (el, { expression }, { evaluate }) => {
      Sortable.create(el, {
          group: el.dataset.sortableGroup,
          animation: 150,
          onAdd: (evt) => evaluate(`${expression}(${evt.item.dataset.assignmentId}, ${el.dataset.positionId ?? 'null'})`),
      });
  });
  ```
- **Copy actions** (buttons in `resources/views/rozpis/index.blade.php`, the parent page): "Kopírovať z iného dňa" (dropdown of the week's other 6 dates) and "Kopírovať z minulého týždňa" (same weekday, week - 7 days) both call a controller action or a parent Livewire method that bulk-inserts `PositionSlot` rows for the target date from the source date, skipping any `(position_id)` that already has a slot that day. Toast reports how many slots were added.

### Authorization

New permission `assignment.assign-position` (placing/unplacing on a slot, plus slot add/remove — same workflow, one permission, not four).

- **`AssignmentPolicy::assignPosition(User $user, Assignment $assignment): bool`** — team match, `WeekLock::locked()` must be **true** (inverse of `create()`/`delete()`, which require the week to be *unlocked*), then `hasPermissionInTeam('assignment.assign-position', $team)`.
- **`app/Policies/PositionSlotPolicy.php`** (new, scaffolded via `artisan make:policy`) — `create`/`delete` with the same team+lock+permission shape, for manual slot add/remove and the copy actions.
- **`TeamPolicy`-style `viewRozpis`** on `Team` (or fold into the controller directly) — gates the route itself: locked week + `assignment.assign-position` permission, else redirect to `calendar.show` for that week with a flash message ("Najprv zamknite týždeň.").

No route-level `role:admin` middleware on the new routes — deliberately following `TeamSettingController`'s pattern (`app/Http/Controllers/Team/TeamSettingController.php:16-19`, authorize-in-controller, no middleware) rather than the existing `Route::middleware('role:admin')` group wrapping week-lock/users/media. **Aside, not fixed here:** that existing group blocks the `manager` role from ever reaching those routes even though `AssignmentPolicy::lock()` already permits managers via `hasPermissionInTeam` — a pre-existing inconsistency, flagging it rather than expanding this change to fix it.

**Bonus fix bundled into the seeder edit:** `DatabaseSeeder` currently seeds `absence.*`/`user.*`/`team.*` permissions but never seeds `assignment.create`/`assignment.delete`/`assignment.lock`/`media.*` at all — they only work today because `hasPermissionInTeam()` gives `admin`/`manager` an unconditional bypass. Since this work is already adding `assignment.assign-position` and `position.*` permissions to the same seeder loop, the existing gap gets closed in the same edit (cheap, same file, same loop — not a separate initiative).

---

## AI-assisted schedule suggestion (optional layer, on top of manual drag-and-drop)

Confirmed with the user: this is a **draft overlay, not an auto-fill**. A manager clicks "AI navrhni rozpis" for a day (or the whole week); the AI proposes placements shown as dashed/muted "AI navrhuje: {name}" cards in the still-empty slots; nothing is written to `assignments` until the manager clicks "Prijať" (accept) on a placement — individually or all at once. Discarding the suggestion (navigating away, dragging manually instead) throws it away — it's never persisted separately from a real `Assignment` row, so no new table for this.

**Privacy design (confirmed with the user):** no real names, phone numbers, or other PII are sent to the AI. Before each request, the app assigns each unassigned pool member an opaque per-request label (`Person 1`, `Person 2`, ...) and keeps the label→`assignment_id` mapping locally only for that request. The AI only ever sees: labels, their fairness stats (`avgWeight`, `totalDays` from `FairnessService`), and the day's position slots (name/code/time — not sensitive). Its JSON response is translated back through the same local map before anything touches the database.

- **`app/Services/AiRozpisSuggestionService.php`** — builds the anonymized payload (pool labels + `priorityScore`/`avgWeight`/`totalDays` from `FairnessService` + today's empty `PositionSlot`s + today's day-weight), calls the Anthropic Messages API via Laravel's `Http` facade (no new Composer dependency — this is a plain REST POST with a JSON body, nothing an SDK is doing for us here), and asks for a strict JSON object mapping `label → position_id`. The prompt states the placement rule directly so the AI's ordering matches the deterministic one below rather than inventing its own notion of "fair": fill the highest day-weight slots first, offering each to the highest-`priorityScore` remaining pool member; once every premium slot is filled, place everyone else left in the pool into whatever slots remain, in any order — nobody sits out just because their score is low, full coverage wins once the reward pass is done.
- **The service itself also computes a deterministic candidate ordering independently of the AI** (slots sorted by day-weight desc then `start_time` asc, pool sorted by `priorityScore` desc) — used two ways: as the actual prompt content (so the AI is filling in a pre-sorted list, not guessing at ranking from raw numbers) and as the reference the validation step checks the AI's answer against.
- **Validation before anything is shown to the manager — "double-check the AI's correctness" is the load-bearing part of this feature, not a formality:**
  1. Response must parse as the expected JSON shape; anything else → no suggestion, not a crash.
  2. Every `label` in the response is re-resolved against **this request's actual pool**, not trusted as typed back — an unknown label is dropped.
  3. Every `position_id` is re-checked against **today's actual empty `PositionSlot`s**, freshly queried (not the payload the AI was sent, in case something changed mid-request) — an unknown or now-already-filled position is dropped.
  4. **The signed-up invariant is re-verified here too, not assumed**: the resolved person must still have a real `Assignment` row for this exact date with `position_id` still null. If anything raced (someone was placed manually while the AI call was in flight, or withdrew their signup), that placement is dropped rather than silently applied.
  5. No label or position used twice in the same response.
  
  Every dropped item just shrinks the suggestion — never a partial-trust "apply what looks right." Malformed pieces disappear; nothing malformed ever reaches the manager's screen as a clickable "Prijať."
- **Accepting a suggestion reuses the exact same write path as a manual drag** — `RozpisDay::place()` and `AssignmentPolicy::assignPosition`. The AI has no elevated write access; "accept" is just a manager action that happens to be pre-filled.
- **Configurable, not hardcoded** (per the "everything customizable" requirement): `ANTHROPIC_API_KEY` and the model id both come from `config/services.php`/`.env` (e.g. `services.anthropic.key`, `services.anthropic.model`), not a literal string in the service — swapping models or disabling the feature entirely (no key set → button hidden) needs no code change.
- **Cost/latency awareness** — this is a real, billed API call per click, not a free local computation like `FairnessService`. Button is manager-initiated only (no auto-trigger, no polling), disabled via `wire:loading` while a request is in flight, and nothing about the rest of the rozpis builder depends on it being available.

### Testing (added to the existing test list)

6. Outbound request payload contains no real name/phone/email — only opaque labels and numeric stats (a real privacy assertion, not a formatting one).
7. A fake AI response referencing a label or `position_id` outside today's actual pool/slots is dropped entirely, not applied (`Http::fake()` — deterministic, no real API call in tests).
8. Accepting a suggested placement is denied under the same conditions a manual `place()` call would be denied (unlocked week, wrong permission) — proves there's no separate, weaker authorization path for AI-originated placements.
9. A fake AI response naming a real user who does **not** have a signup for that date is dropped — proves the "only days they actually signed up for" rule is enforced server-side, not merely requested in the prompt.
10. `FairnessServiceTest`: given a high-volume mostly-weekday person and a low-volume weekend-only person, `priorityScore` ranks the former higher — proves the reward/penalty behavior described above, not just the raw ratio.
11. Given a pool larger than the number of premium (high-weight) slots, the deterministic ordering places the remaining pool members into the leftover slots rather than leaving them unfilled or excluded for having a low `priorityScore`.

---

## Position management (new — hard prerequisite)

Nothing manages `positions` today except hand-editing the seeder. Small CRUD, same shape as the existing admin user table:

- **`app/Http/Controllers/Position/PositionController.php`** — `index`/`store`/`update`/`destroy`, route group `/admin/pozicie` (matches `/admin/pouzivatelia` convention), each action behind `PositionPolicy` (`view-any`/`create`/`update`/`delete`, new permissions `position.view-any` etc., same bonus-fix seeder edit as above).
- **`app/Livewire/PositionList.php`** — table + inline create/edit form (name, code, color, `is_manager`, `is_active`) plus SortableJS drag-reorder writing `sort_order` (same directive as the rozpis grid — one wiring effort covers both features).
- Deactivating (not deleting) is the default destructive action, matching `is_active` already existing on the model and `scopeSelectable()` already filtering by it — deleting a position that has historical assignments would orphan `assignments.position_id`.

---

## Files touched (new unless noted)

- Migrations: `create_position_slots_table`, `add_fairness_settings_to_team_settings_table` (both `fairness_day_weights` and `fairness_window_weeks` in one migration — they're added, edited, and reasoned about together)
- Models: `PositionSlot` (new); `TeamSetting` (edit — const + fillable + cast)
- Services: `FairnessService`, `RozpisService`
- Policies: `PositionSlotPolicy`, `PositionPolicy` (new); `AssignmentPolicy` (edit — add `assignPosition`)
- Controllers: `Rozpis/RozpisController`, `Position/PositionController`
- Livewire: `RozpisDay`, `PositionList`
- Views: `rozpis/index.blade.php`, `livewire/rozpis-day.blade.php`, `admin/positions/index.blade.php` (or similar), edits to `team/settings.blade.php`
- `routes/web.php` — `rozpis.show`, `rozpis.copy`, `admin/pozicie` group
- `database/seeders/DatabaseSeeder.php` — add `position.*`, `assignment.assign-position` permissions; close the existing `assignment.*`/`media.*` seeding gap
- `resources/js/app.js` — SortableJS Alpine directive
- Factories: `PositionSlotFactory`

All new classes scaffolded via `php artisan make:model|policy|controller|livewire|test --no-interaction` first, then filled in — per the project's standing convention, not hand-written at a fresh path.

---

## Testing (per this repo's stated philosophy — one happy path + one meaningful failure per flow, not exhaustive coverage)

1. Manager places a signed-up employee into a slot on a **locked** week → `assignment.position_id`/`start_time` set.
2. The same action on an **unlocked** week, or by a non-manager → denied (this is the actual trigger condition the user described: locking is what unlocks the builder).
3. Copy-from-day is additive: a slot that already exists on the target date is left alone, not overwritten.
4. Position reorder + create happy path; non-admin/non-manager blocked.
5. `FairnessServiceTest` (unit): a low-volume all-weekend user and a high-volume mixed-schedule user must **not** produce the same "overloaded" signal — assert `avgWeight` reflects the ratio, not the raw weekend count. A second case asserts an assignment older than `fairness_window_weeks` is excluded, and changing that setting changes what's counted.

## Verification

- `php artisan test --compact --filter=Rozpis` and `--filter=Position` and `--filter=Fairness` for the new suites once written.
- `php artisan migrate` on the local Herd/Docker MySQL, then exercise the flow at `http://cnmx_zmeny.test` end-to-end: lock a week → build its rozpis by dragging real seeded signups onto positions → copy to the next day → confirm fairness badges change after placing someone on a weekend slot.
- `vendor/bin/pint --dirty --format agent` before finalizing, per project convention.
