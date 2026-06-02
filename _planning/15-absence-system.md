# 15 — Absence System (Single-day, Recurring, Multi-day)

> This document supersedes and extends `08-unavailability.md`. All three absence types live in one unified system with shared deadline logic and a single admin view.

---

## Overview

Employees declare when they cannot work. Three types exist, all self-service (no admin approval required — the user is trusted):

| Type | Example | Deadline applies? |
|---|---|---|
| **Single-day** | "Nedôjdem v piatok 13.6." | Yes |
| **Multi-day block** | "Dovolenka 14.6.–21.6." | Yes, based on first day |
| **Recurring** | "Každý utorok som na škole" | No deadline — set once, valid until cancelled |

All three are visible to admins in Filament and feed into the plan builder's conflict detection.

---

## DB Schema

### `absences`

One unified table for all three types.

```sql
id              bigint      PK
user_id         bigint      FK users.id CASCADE DELETE
type            enum        'single', 'multi', 'recurring'
date            date        nullable  -- for type='single'
date_from       date        nullable  -- for type='multi'
date_to         date        nullable  -- for type='multi'
day_of_week     tinyint     nullable  -- 0=Mon…6=Sun, for type='recurring'
recurring_from  date        nullable  -- recurring valid from (null = immediately)
recurring_until date        nullable  -- recurring valid until (null = indefinite)
reason          string(500) nullable
submitted_at    timestamp
admin_override  boolean     DEFAULT false
overridden_by   bigint      nullable FK users.id SET NULL
created_at      timestamp
updated_at      timestamp

-- Prevent duplicates
UNIQUE (user_id, date)              -- single-day only
UNIQUE (user_id, day_of_week, recurring_from)  -- recurring only
INDEX  (user_id)
INDEX  (type, date)
INDEX  (type, day_of_week)
```

> A check constraint (or application-level validation) ensures that `date` is set for `type='single'`, `date_from`+`date_to` for `type='multi'`, and `day_of_week` for `type='recurring'`.

---

## Deadline Rules

Deadline applies to single-day and multi-day absences. It is based on `tenant_settings.absence_hours` (integer hours, default 48). Applies to the **first affected day**:

- Single-day: deadline = `date - absence_hours`
- Multi-day: deadline = `date_from - absence_hours`
- Recurring: **no deadline** — recurring patterns are structural commitments (school, second job) that are set once and don't require advance notice per occurrence.

```php
// app/Services/AbsenceService.php

public function canSubmit(Carbon $firstDate, string $type, ?User $submitter = null): bool
{
    if ($type === 'recurring') return true;  // no deadline for recurring

    if ($submitter?->hasRole(['admin', 'manager'])) return true;

    $settings = app(TenantSettings::class);
    $deadline = $firstDate->copy()
                          ->startOfDay()
                          ->subHours($settings->absence_hours);

    return now($settings->timezone)->lt($deadline);
}
```

### Past-date Rule

- **Single-day & multi-day**: employees cannot submit for past dates. Admins can.
- **Recurring**: allowed to start in the past (e.g. "My Tuesday school started 3 weeks ago") — admin flag set automatically when `recurring_from` is in the past.

---

## Effective Date Computation

For conflict detection in the plan builder, every absence needs to be expanded into concrete dates:

```php
// app/Services/AbsenceService.php

public function getAbsentDates(User $user, Carbon $from, Carbon $to): Collection
{
    $absences = Absence::where('user_id', $user->id)->get();
    $dates    = collect();

    foreach ($absences as $absence) {
        match($absence->type) {
            'single'    => $dates->push(Carbon::parse($absence->date)),
            'multi'     => $dates->push(
                ...CarbonPeriod::create($absence->date_from, $absence->date_to)->toArray()
            ),
            'recurring' => $dates->push(
                ...CarbonPeriod::create(
                    max($absence->recurring_from ?? $from, $from),
                    $absence->recurring_until ?? $to
                )
                ->filter(fn ($d) => $d->dayOfWeek === $absence->day_of_week)
                ->toArray()
            ),
        };
    }

    return $dates
        ->filter(fn ($d) => $d->between($from, $to))
        ->unique(fn ($d) => $d->toDateString())
        ->sort();
}
```

This is called by `PlanConflictService` and the Filament assignment modal.

---

## Tenant Settings Additions

```sql
-- Replace unavailability_hours with absence_hours (more accurate name):
absence_hours               smallint   DEFAULT 48
-- How many hours before the first absent day the submission must be made

absence_max_multi_days      tinyint    DEFAULT 30
-- Maximum length of a single multi-day absence block (0 = unlimited)

absence_recurring_requires_reason  boolean  DEFAULT false
-- If true, recurring absences require a reason text
```

---

## Employee UI (Livewire: `AbsencePage`)

Route: `/neprítomnosť`

Three tabbed sections:

### Tab 1: Jednorazová (single-day)

Date picker (min = tomorrow or first date before deadline).
Reason text (optional).

### Tab 2: Viacero dní (multi-day)

```
Od: [date picker]   Do: [date picker]   (max range: absence_max_multi_days)
Dôvod: [text]       (optional, shown as "Dovolenka" placeholder)
```

Validation:
- `date_from` must be ≤ `date_to`
- `date_from` must pass `canSubmit()` deadline check
- Range must not exceed `absence_max_multi_days` (if configured)
- Overlapping multi-day blocks are merged or rejected with a clear message

### Tab 3: Opakujúce sa (recurring)

```
Deň: [Mon][Tue][Wed][Thu][Fri][Sat][Sun]  ← multi-select toggle buttons
Platí od: [date picker]   Platí do: [date picker / "indefinite" toggle]
Dôvod: [text]   (required if tenant setting enabled)
```

Note under the form: "Opakujúce sa neprítomnosti platia automaticky každý týždeň a nemajú lehotu na nahlásenie."

---

### Unified Absence List

All three types shown in one timeline:

```
Nadchádzajúce / aktívne
────────────────────────────────────────────────────────
🔁  Každý utorok (od 1.9.2025)           Škola     [Zrušiť]
📅  14.6. – 21.6.2025 (8 dní)           Dovolenka  [Zrušiť]
📌  13.6.2025 (piatok)                   Lekár      [Zrušiť]

Minulé
────────────────────────────────────────────────────────
📌  3.6.2025                              —          —
```

**"Zrušiť" rules:**
- Single-day: can cancel if still before deadline
- Multi-day: can cancel if `date_from` is still before deadline; if the block has partially started, can only shorten it (edit `date_to` to yesterday)
- Recurring: can cancel at any time (end it by setting `recurring_until = today - 1`)

---

## Admin UI (Filament: `AbsenceResource`)

**Navigation group:** Zamestnanci  
**Permission:** `absences.view-all`

### Table Columns
- Type badge (Single / Multi-day / Recurring)
- User name
- Date(s) / Day of week
- Reason
- Submitted at
- Deadline status (On time / Late / Admin override)
- Conflict indicator (if overlaps a plan assignment)

### Filters
- By type
- By user (multi-select)
- By week (expands to date range)
- Conflicts only
- Admin overrides only

### Actions
- **Create** — admin can create any type for any user, any date (deadline bypassed, `admin_override = true`)
- **Edit** — admin can edit reason, dates on any record
- **Delete** — admin can delete any record
- **Bulk delete** selected

### Admin Override Rules
When admin creates/edits a past-deadline or past-date absence, `admin_override = true` and `overridden_by = auth()->id()` are set automatically. Shown as an orange "Admin" badge in the table. All changes logged in activity log.

---

## Conflict Detection Integration

Absence conflicts surface in two places:

### 1. `WeekPlanPage` (Filament)

When an employee is already assigned to a plan slot, and they have an absence for that day, the cell shows a warning badge:
- `⚠ Jednorazová neprítomnosť` — they marked single/multi-day unavailability
- `🔁 Opakujúca sa neprítomnosť` — the day matches their recurring pattern

Clicking the badge opens an info popover with the reason and submission date.

### 2. Assignment Modal

When assigning an employee to a slot in `AssignUserToSlotAction`, users with any absence type for that date are shown with a warning chip:
```
[Jana N.  ⚠ Neprítomná]   ← selectable but warned
```

---

## Locking Interaction

When a week is locked:
- **Single-day**: no new submissions for dates in that week (even by admins — plan is final)
- **Multi-day**: if `date_from` is before the lock and `date_to` is in a locked week, the user can still shorten the block (reduce `date_to`) but cannot extend it into the locked period
- **Recurring**: unaffected by week lock — recurring patterns are not week-specific

---

## Notifications

### `AbsenceDeadlineReminderNotification`
Scheduled daily: find employees who have upcoming plan assignments and have NOT yet submitted absence for the coming days approaching the deadline.

"Pripomíname vám, že termín na nahlásenie neprítomnosti pre **[piatok 13.6.]** uplynie **zajtra o polnoci**."

Configurable in `tenant_settings`: `absence_reminder_enabled` (boolean, default true).

### `AbsenceConflictNotification`
Sent to admin/manager when an employee submits an absence and they are **already assigned** to a plan slot on that date.

"Jana N. nahlásila neprítomnosť na piatok 13.6. – je priradená na Uvádzač 13:00. Skontrolujte plán."

---

## Implementation Checklist

- [ ] `absences` table migration (replace old `user_holidays`)
- [ ] `Absence` model with type enum, relationships, scopes
- [ ] `AbsenceService::canSubmit()`, `getAbsentDates()`, create/cancel methods
- [ ] `AbsencePolicy`: create (deadline), cancel (deadline + ownership), admin override
- [ ] `AbsencePage` Livewire component with 3 tabs
- [ ] Absence list with unified timeline view
- [ ] Cancel logic per type (single deadline, multi shorten, recurring end date)
- [ ] `AbsenceResource` in Filament (table, filters, admin create/edit/delete)
- [ ] Conflict badges in `WeekPlanPage`
- [ ] Conflict warnings in `AssignUserToSlotAction` modal
- [ ] Locking interaction: block single/multi for locked weeks, allow recurring
- [ ] `AbsenceDeadlineReminderNotification` + scheduled command
- [ ] `AbsenceConflictNotification`
- [ ] Tenant settings: `absence_hours`, `absence_max_multi_days`, `absence_recurring_requires_reason`, `absence_reminder_enabled`
- [ ] Data migration: old `user_holidays` → `absences` (type='multi')
- [ ] Feature tests: all three types, deadline logic, overlap detection, lock interaction
