# 08 — Unavailability System

## Purpose

Employees declare days they cannot work. Admins see this when building the plan. The system prevents:
- Declarations about past days
- Declarations submitted too close to the target date (configurable deadline)

Admins can override both restrictions.

---

## Data Model

```sql
-- unavailabilities table (one row per user per date)
id               bigint      PK
user_id          bigint      FK users.id CASCADE DELETE
date             date
reason           string(255) nullable
submitted_at     timestamp               -- set to NOW() on creation
admin_override   boolean     DEFAULT false
overridden_by    bigint      nullable FK users.id SET NULL
created_at       timestamp
updated_at       timestamp

UNIQUE (user_id, date)
INDEX  (user_id, date)
```

---

## Deadline Logic

### Setting

`tenant_settings.unavailability_hours` — integer, default `48`.

Interpretation: an employee must submit their unavailability for day D at least `unavailability_hours` hours before the **start of day D** (midnight).

### Example

- `unavailability_hours = 48`
- Target date: Friday 2025-06-13 (starts at midnight Thursday/Friday)
- Deadline: Wednesday 2025-06-11 00:00
- An employee can submit unavailability for Friday until Wednesday midnight (i.e. Tuesday evening at latest for a 48h window)

### Server-Side Validation

```php
// app/Services/UnavailabilityService.php

public function canSubmit(Carbon $date, ?User $submitter = null): bool
{
    // Admins bypass all deadline checks
    if ($submitter && $submitter->hasRole(['admin', 'manager'])) {
        return true;
    }

    $settings = app(TenantSettings::class);
    $deadline = $date->copy()
                     ->startOfDay()
                     ->subHours($settings->unavailability_hours);

    return now($settings->timezone)->lt($deadline);
}

public function create(User $user, string $date, ?string $reason, User $submitter): Unavailability
{
    $targetDate = Carbon::parse($date, app(TenantSettings::class)->timezone);

    // Past dates: never allowed for non-admins
    if ($targetDate->isPast() && ! $submitter->hasRole('admin')) {
        throw new UnavailabilityDeadlineException('Nemožno nahlásiť nedostupnosť pre minulý dátum.');
    }

    if (! $this->canSubmit($targetDate, $submitter)) {
        throw new UnavailabilityDeadlineException(
            "Termín na nahlásenie nedostupnosti pre {$date} uplynul."
        );
    }

    return Unavailability::create([
        'user_id'        => $user->id,
        'date'           => $targetDate->toDateString(),
        'reason'         => $reason,
        'submitted_at'   => now(),
        'admin_override' => $submitter->hasRole('admin'),
        'overridden_by'  => $submitter->hasRole('admin') ? $submitter->id : null,
    ]);
}
```

### What "Backwards" Means

- **Past date** → always rejected for employees; only admins can add.
- **Future date past deadline** → rejected for employees; only admins can add.
- **Future date before deadline** → allowed for everyone.

This ensures that when an admin builds the plan, the employee data they see is stable.

---

## Edit & Delete Rules

### Employee can:
- Delete their own entry if `canSubmit($record->date)` returns true (i.e. still before the deadline).
- Edit reason (not date) if still before deadline. Date change = delete + recreate.

### Employee cannot:
- Delete or edit entries for dates past the deadline.
- Add entries for past dates or dates past deadline.

### Admin can:
- Create, edit, and delete any entry at any time (`admin_override = true` set automatically).
- All admin overrides are recorded in `overridden_by`.
- All changes logged in activity log.

---

## Locked Week Interaction

When a week is locked:
- No new unavailability can be submitted for dates in that week (not even by admins — the plan is finalized).
- Existing unavailability records are preserved for historical reference.
- In `UnavailabilityService::create()`, check `Week::forDate($date)->locked` before creating.

---

## UI: Employee Side (Livewire)

### `UnavailabilityManager` component

**Date picker rules:**
- Min date: `tomorrow` (past dates not selectable)
- Disabled dates: days where `canSubmit(date)` returns false (calculated client-side from JS, confirmed server-side)
- Disabled dates: days where the user already has an entry

**Visual deadline indicator:**
Under the form, show: "Najbližší termín na nahlásenie: [date and time of next approaching deadline]"

For example, if today is Tuesday and the deadline is 48h, the deadline for Thursday is Wednesday midnight — show "Streda 11.6. 00:00".

**Entry list:**
```
| Dátum        | Dôvod               | Stav     | Akcie     |
|--------------|---------------------|----------|-----------|
| Pi 13.6.2025 | Lekár               | Aktívne  | [Zmazať]  |
| So 14.6.2025 | —                   | Aktívne  | [Zmazať]  |
| Ut 3.6.2025  | Dovolenka           | Minulé   | —         |
```

"Zmazať" button disabled (greyed out) if past deadline or if week is locked.

---

## UI: Admin Side (Filament)

### `UnavailabilityResource` table

Shows all entries across all users. Columns:
- User name + lastname
- Date
- Day of week
- Reason
- Submitted at
- Admin override badge (orange if `admin_override = true`)
- Conflict badge (red if user is assigned to a plan slot on this date)

**Conflict detection:** Join with `plan_assignments` to find cases where a user is both unavailable and assigned. Surface as a warning — admin may have intentionally overridden.

**Filters:**
- By user (multi-select)
- By week (select from weeks list, maps date range)
- By date range
- Show conflicts only (toggle)
- Show admin overrides only (toggle)

**Actions:**
- Delete (with confirmation)
- Create (opens modal, bypasses deadline — admin context)
- Bulk delete selected

### Dashboard widget

`UnavailabilityWidget` shows this week's and next week's unavailability at a glance:
- List grouped by day
- Each day shows count: "3 nedostupní"
- Click → filtered `UnavailabilityResource` view

---

## Notification: Deadline Approaching

Optional: 24h before the final submission deadline for a week, send a push/email notification to all employees reminding them to submit unavailability.

```php
// Scheduled command: daily
// For each future week whose deadline is within 24h:
//   → Queue SendUnavailabilityReminderNotification for all employees without existing entries
```

---

## Edge Cases

| Case | Handling |
|---|---|
| Employee submits unavailability, then is assigned to a plan slot on that day | System shows ⚠️ in plan, does NOT auto-remove assignment |
| Admin removes unavailability entry after plan is locked | Allow (admin override), no effect on locked plan |
| Tenant changes `unavailability_hours` setting | Affects future submissions only; existing records are not re-validated |
| Employee has unavailability for entire week | Normal — they simply won't be available for any slot |
| Same date submitted twice | DB UNIQUE constraint prevents it; UI shows "Už ste nahlásili nedostupnosť pre tento dátum" |
