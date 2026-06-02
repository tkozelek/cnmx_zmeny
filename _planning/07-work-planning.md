# 07 — Work Planning System

## Overview

The work planning system is the core feature of the rewrite. It replaces the old flat `user_days` pivot (which only stored "who worked that day") with a structured plan of **positions → time slots → assigned users** for each day.

---

## Key Concepts

| Concept | Description |
|---|---|
| **Position** | A named work role: Uvádzač, Bufet, Pokladňa, Vedúci. Defined per tenant. |
| **Plan Slot** (`plan_slots`) | One position + start time + required headcount on a specific day |
| **Plan Assignment** (`plan_assignments`) | One user assigned to one plan slot |
| **Work Plan** | All plan slots + assignments for a given week |
| **Plan Template** | A reusable set of position+time slot definitions (without users) |
| **Week Lock** | Admin marks a week as finalized — no further edits to plan or unavailability |

---

## Work Week Definition

The work week is configurable per tenant via `tenant_settings.week_offset`.

`week_offset` is an integer 0–6 (0 = Monday, 6 = Sunday). Default: **3 = Thursday**.

Week generation:
```php
// WeekService::generateWeekForDate(Carbon $date): Week
public function generateWeekForDate(Carbon $date): Week
{
    $settings = app(TenantSettings::class);
    $offset   = $settings->week_offset;  // e.g. 3 for Thursday

    // Find the most recent Thursday on or before $date
    $dateFrom = $date->copy()->startOfWeek(Carbon::MONDAY)->addDays($offset);
    if ($dateFrom->gt($date)) {
        $dateFrom->subWeek();
    }
    $dateTo = $dateFrom->copy()->addDays(6);

    return Week::firstOrCreate(
        ['date_from' => $dateFrom->toDateString()],
        ['date_to'   => $dateTo->toDateString()]
    );
}
```

> For `week_offset = 3` (Thursday): a week for 2025-06-12 runs Thu 12.6 → Wed 18.6.

---

## Plan Templates

Admin defines a template once and reuses it to pre-fill position slots for any new week. Templates do not hold user assignments — only the structure.

### DB Tables (not in core schema, add these)

#### `plan_templates`
```sql
id           bigint    PK
name         string    -- "Štandardný týždeň"
is_default   boolean   DEFAULT false  -- used if no template selected on generation
created_at   timestamp
updated_at   timestamp
```

#### `plan_template_slots`
```sql
id                  bigint    PK
plan_template_id    bigint    FK plan_templates.id CASCADE DELETE
position_id         bigint    FK positions.id CASCADE DELETE
day_of_week         tinyint   -- 0=Mon…6=Sun (relative to week_offset)
start_time          time
end_time            time      nullable
required_count      tinyint   DEFAULT 1
sort_order          smallint  DEFAULT 0
```

### Usage Flow

1. Admin opens "Pozičné šablóny" in Filament settings.
2. Creates a template, adds slots per day-of-week.
3. When generating a new week plan, selects the template.
4. `GeneratePlanFromTemplateAction` creates `plan_slots` from template slots, translating `day_of_week` to actual `day_id`.

---

## Plan Generation Flow

```
Admin clicks "Generovať plán" on a Week record
    ↓
Modal: Select template (default pre-selected) + optional notes
    ↓
GeneratePlanAction::handle(Week $week, PlanTemplate $template)
    ↓
For each day in the week (7 days):
    For each template slot (matching that day_of_week):
        Create plan_slot (position_id, start_time, end_time, required_count)
        Create N empty plan_assignments (user_id = null) where N = required_count
    ↓
Redirect to WeekPlanPage for the generated week
    ↓
Admin manually assigns users to empty slots
```

If a plan already exists for the week when "Generate" is clicked:
- Warn admin: "Plán pre tento týždeň už existuje. Pokračovaním prepíšete existujúce pridelenia."
- Offer: "Ponechaj existujúce priradenia" (regenerate slots but keep existing user assignments where position+time match).

---

## Assigning Users to Slots

In the `WeekPlanPage` Filament grid, each empty cell shows an `[+ Priradiť]` button.

On click → modal:
- Dropdown of active employees
- Filter: employees with unavailability on this date are shown with ⚠️ icon but are selectable (admin override)
- Filter: employees already assigned to another slot on the same day at overlapping times are shown with 🔴 icon
- Optional notes field

On confirm → `PlanAssignment::create(['plan_slot_id' => ..., 'user_id' => ..., 'assigned_by' => auth()->id()])`.

Activity log entry created: `"Tomáš K. priradený na Uvádzač 13:00 (Piatok 13.6.)"`.

---

## Manager Position Rule

Every day's plan must have exactly 1 user assigned to a position where `is_manager = true` (e.g. "Vedúci").

Enforcement:
1. **Soft warning** in `WeekPlanPage` — days without a manager assignment show a red warning badge in the day header.
2. **Hard block on lock** — `LockWeekAction` validates that every day has ≥1 manager assignment before locking. If not: `$action->halt()` with error message listing the incomplete days.

```php
// In LockWeekAction::before()
$daysWithoutManager = $week->days->filter(function (Day $day) {
    return ! $day->planSlots()
        ->whereHas('position', fn ($q) => $q->where('is_manager', true))
        ->whereHas('assignments', fn ($q) => $q->whereNotNull('user_id'))
        ->exists();
});

if ($daysWithoutManager->isNotEmpty()) {
    Notification::make()
        ->danger()
        ->title('Nemožno uzamknúť týždeň')
        ->body('Chýba vedúci pre: ' . $daysWithoutManager->pluck('date')->join(', '))
        ->send();
    $action->halt();
}
```

---

## Week Locking

### What Locking Does
- Sets `weeks.locked = true`, `locked_at = now()`, `locked_by = auth()->id()`
- All `plan_slots` and `plan_assignments` for the week become read-only
- `UnavailabilityPolicy::create()` rejects new entries for dates in a locked week
- Filament hides edit/delete actions on locked week's resources
- Livewire `CalendarWeek` shows a "🔒 Uzamknutý" badge

### Validation Before Lock
1. All manager positions are filled (hard block — see above)
2. No plan slot has `required_count` > actual assigned users (soft warning, not a block — admin may intentionally leave a slot open)
3. Week has at least 1 plan slot total (can't lock an empty plan)

### Unlock
Admin can unlock a week (e.g. to fix a mistake) with a separate `UnlockWeekAction`. Logged in activity log.

### Post-Lock Notification
After a week is locked, send notifications to all assigned employees:
- Email listing their assigned days + positions + times
- `WeekLockedNotification` queued job (one per user per week)

---

## Plan Conflict Detection

The system should detect and surface (but not prevent) conflicts:

| Conflict | Detection | Display |
|---|---|---|
| User unavailable on assigned day | `unavailabilities` table check | ⚠️ badge on assignment |
| User assigned to 2 overlapping slots same day | Compare start_time/end_time | 🔴 badge on both cells |
| Same slot needs N people but has < N assigned | `required_count` vs assignment count | Grey "0/2" indicator |

Conflict detection runs:
- When viewing `WeekPlanPage` (computed property)
- When locking (validates, soft-warns about unfilled slots)
- In `PlanConflictService::detectForWeek(Week $week): Collection`

---

## Week Auto-Generation

A scheduled command generates upcoming weeks automatically so admins always have future weeks to plan into:

```php
// app/Console/Commands/GenerateFutureWeeks.php
// Runs daily via Laravel Scheduler

Tenant::all()->each(function (Tenant $tenant) {
    tenancy()->initialize($tenant);
    $settings = app(TenantSettings::class);

    $latestWeek = Week::orderByDesc('date_from')->first();
    $lookahead  = $settings->week_lookahead;

    $weeksToGenerate = max(0, $lookahead - Week::where('locked', false)->count());

    for ($i = 0; $i < $weeksToGenerate; $i++) {
        $nextDate = $latestWeek
            ? Carbon::parse($latestWeek->date_to)->addDay()
            : now();
        $latestWeek = app(WeekService::class)->generateWeekForDate($nextDate);
    }

    tenancy()->end();
});
```

This only creates `Week` + `Day` records — it does NOT auto-generate plan slots (that requires admin action).
