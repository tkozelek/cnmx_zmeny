# 20 — Schema Rework: Handoff

> **Status:** migrations written and verified against a throwaway MySQL 8 schema. Application code is **not** updated — that is the next agent's job. The app is broken until it is.
>
> This document is authoritative where it conflicts with `02-database.md`, `03-multitenancy.md`, `07-work-planning.md`, `08-unavailability.md` and `15-absence-system.md`. Those docs describe an *earlier* target. Divergences are listed below with reasons — do not "fix" them back.

---

## 0. Run this first

The 23 legacy migration files still need deleting — a permission gate blocked the batch delete, so it has to be run by hand:

```bash
git rm database/migrations/2024_05_24_110000_create_user_roles.php \
       database/migrations/2024_05_24_110010_create_users.php \
       database/migrations/2024_05_24_110011_create_weeks.php \
       database/migrations/2024_05_24_110012_create_days.php \
       database/migrations/2024_05_24_110523_create_file_storage.php \
       database/migrations/2024_05_24_111453_create_user_days.php \
       database/migrations/2024_05_24_111824_create_user_holidays.php \
       database/migrations/2024_05_29_234251_create_bugs.php \
       database/migrations/2024_06_24_085422_add_last_login_at_to_users_table.php \
       database/migrations/2025_10_07_234100_create_shifts_table.php \
       database/migrations/2025_10_14_210555_create_rates_table.php \
       database/migrations/2025_10_15_181850_add_cascade_to_days.php \
       database/migrations/2025_10_15_181948_add_nullable_to_weeks.php \
       database/migrations/2025_10_15_184022_add_cascade_to_user_days.php \
       database/migrations/2025_10_15_190921_add_cascade_to_file_storage.php \
       database/migrations/2026_07_18_100000_create_teams_table.php \
       database/migrations/2026_07_18_100010_create_team_user_table.php \
       database/migrations/2026_07_18_100020_add_current_team_id_to_users_table.php \
       database/migrations/2026_07_18_100030_add_team_id_to_weeks_table.php \
       database/migrations/2026_07_18_100040_add_team_id_to_shifts_table.php \
       database/migrations/2026_07_18_100050_add_team_id_to_rates_table.php \
       database/migrations/2026_07_18_100060_add_team_id_to_file_storage_table.php \
       database/migrations/2026_07_18_100070_add_team_id_to_user_holidays_table.php

php artisan config:clear && php artisan migrate:fresh
```

`migrate:fresh` drops the one remaining legacy user row (`tommyside@centrum.sk`). There is no seeder yet — see §6.

Files kept: `create_pulse_tables`, `create_cache_table`, `create_jobs_table`, `create_failed_jobs_table`, `create_permission_tables` (config-driven, needs no edit), plus the 11 new `2026_07_24_*` migrations.

---

## 1. The one structural idea

**Signup keys off a `date` column. Weeks are computed, not stored.**

| Before | After |
|---|---|
| `weeks` → `days` → `user_days` (pivot, free-text `popis` only) | `assignments(team_id, user_id, position_id, date, …)` |
| A `Day` row had to exist before anyone could sign up | Nothing to pre-generate — any date works |
| `WeekService::generateWeek()` created `Week` + 7 `Day` rows | Weeks derived in PHP from `team_settings.week_start_day` |
| `weeks.locked` boolean on a stored week | A `week_locks` row exists ⇒ that week is frozen |
| `user_days.popis` free text ("RN") | `positions` — a first-class per-team row |

`week_start_day` (0=Mon … 6=Sun, default **3 = Thursday**) is what makes the Thu–Wed week general: each cinema picks its own.

### Deriving a week

```php
// The week containing $date for a team whose week starts on $startDay (0=Mon).
$weekStart = $date->copy()->startOfWeek(Carbon::MONDAY)->addDays($startDay);
if ($weekStart->gt($date)) {
    $weekStart->subWeek();
}
$weekEnd = $weekStart->copy()->addDays(6);
```

`$weekStart` is the identity of a week everywhere: the `week_locks.week_start` key, the `media.week_start` scope, and the URL parameter for week navigation. Always aligned via the snippet above — never trust a raw date from a query string.

---

## 2. Tables

11 domain tables. `bugs` is gone (out of scope per `02-database.md`).

| Table | Notes |
|---|---|
| `teams` | + `slug` (unique), `is_active`. bigint PK now. |
| `users` | **`email` globally unique. No `team_id`. No `id_role`.** `current_team_id` + `is_active`. |
| `team_user` | PK(team_id, user_id) + **`approved_at`** — null = pending approval. |
| `team_settings` | `week_start_day`, `week_lookahead`, `absence_deadline_hours`, `timezone`, `locale`. |
| `positions` | Per-team. `name`, `code` ("RN"), `color`, `is_manager`, `sort_order`, `is_active`. |
| `assignments` | The plan. `date` + optional `start_time`/`end_time`. `created_by` null = self-signup. |
| `absences` | `date_from`, `date_to`, `day_of_week` nullable. Replaces `user_holidays`. |
| `week_locks` | `(team_id, week_start)` unique. Row present = locked. |
| `shifts` | Worked hours. **`starts_at`/`ends_at` DATETIME.** |
| `rates` | `break` → `break_deduction` (was a reserved word). |
| `media` | Replaces `file_storage`. `week_start` DATE instead of `id_week`. `is_shown` → `is_visible`. |

### Roles

`users.id_role` and the legacy integer `roles` table are **deleted**. Roles are Spatie Permission in teams mode — `model_has_roles.team_id` gives a user different roles in different teams, which is the whole point.

`config/permission.php` has been reverted: `table_names.roles` is back to `'roles'` (the `permission_roles` rename only existed to dodge the legacy table, which is gone).

Two legacy role values are no longer roles at all:

| Legacy role | Now |
|---|---|
| `zablokovany` (4, blocked) | `users.is_active = false` |
| `neovereny` (1, unverified) | `team_user.approved_at IS NULL` |

Role names per `04-spatie-permissions.md`: `admin`, `manager`, `employee`.

---

## 3. Deliberate divergences from the planning docs

| Doc | Said | Built | Why |
|---|---|---|---|
| 02 | `UNIQUE(team_id, email)` + `users.team_id` | `email` globally unique, no `team_id` | Per-team email means one person = two accounts, so they could never hold different roles in different teams. Self-contradictory with the stated requirement. |
| 02, 07 | `days`, `plan_slots`, `plan_assignments`, `plan_templates`, `plan_template_slots` | one `assignments` table | Signup is per (user, date, position). The slot/assignment split only pays for itself with admin-declared headcount, which is not in scope (see §5). |
| 02, 07 | `weeks` table + nightly `GenerateFutureWeeks` | `week_locks` only | The only thing `weeks` stored was `locked`. |
| 02 | `shifts.start`/`end` as `TIME` | `starts_at`/`ends_at` `DATETIME` | A 21:00→01:30 cinema shift gives `end < start`: negative durations, hours booked to the wrong payroll day. This was a live payroll bug in the design. |
| 15 | `type` enum + 7 date columns | `date_from`, `date_to`, `day_of_week` | Same expressiveness, no enum, no cross-column check constraint. Recurring preserved. |
| 15 | `admin_override`, `overridden_by` | dropped | That is what the activity log is for. |
| 02 | `media.is_public` | `is_visible` | Legacy `is_shown` meant "employees can see it", not public web access. |
| 03 | composite FKs not discussed | `(team_id, position_id)` composite FK on `assignments`, `shifts` | Cheap DB-level guarantee that a row can't pair team A with team B's position. Rejected the reviewer's wider proposal to composite-FK `(team_id, user_id)` → `team_user` with RESTRICT: it would make removing a member impossible once they have history. |

**Absence sentinel:** `absences.date_to` is `NOT NULL`. An open-ended recurring absence writes `9999-12-31` (add `Absence::FOREVER`). This keeps the overlap query a sargable range scan; `OR date_to IS NULL` cannot use the index.

**Overlap query** — must be in this column order to hit `absences_team_id_date_from_date_to_index`:

```php
Absence::where('team_id', $teamId)
    ->where('date_from', '<=', $rangeEnd)
    ->where('date_to', '>=', $rangeStart)
```

Do not add `user_id` to that composite index. It is unbound in this query, and sitting between `team_id` and the date columns it breaks `date_from` as a range bound — the original `(team_id, user_id, date_from)` index served this query not at all. `(user_id, date_from)` exists separately for "my own absences".

---

## 4. Application code to update

Direct grep hits: **20 files under `app/`, 17 Blade views**. Not exhaustive — models and controllers below need rewriting whether or not they matched.

### Delete outright
- `app/Models/Day.php`, `app/Models/Week.php`, `app/Models/Role.php`, `app/Models/Bug.php`
- `app/Http/Controllers/BugReportController.php`, `resources/views/bugreport/`
- `app/Http/Middleware/EnsureUserHasRole.php`, `app/Http/Middleware/EnsureUserIsAllowed.php` — replaced by Spatie's `role:` / `permission:` middleware plus an `EnsureUserIsActive` check (`04-spatie-permissions.md` §Active User Guard)
- `app/Policies/DayPolicy.php` → becomes `AssignmentPolicy`
- `config/constants.php` role IDs — now dead
- `app/Http/Kernel.php`: drop the `allowed` and `role` aliases, register Spatie's

### Rewrite
| File | Change |
|---|---|
| `app/Models/User.php` | Drop `$attributes['id_role' => 1]`, `id_role` from `$fillable`/`$hidden`, `role()`, `hasRole(int)`, `isAdmin()`, `days()`. Add `HasRoles` (Spatie), `teams()`, `currentTeam()`, `switchTeam()`, `assignments()`, `absences()`. **`hasRole()` must go before adding `HasRoles`** — the signatures collide. |
| `app/Services/WeekService.php` | No longer generates rows. Becomes pure week arithmetic from `week_start_day` (§1) + `isLocked(Team, $weekStart)`. |
| `app/Services/WeekDataService.php` | Query `assignments` by `whereBetween('date', [$weekStart, $weekEnd])` instead of walking `Week`→`Day`→`user_days`. |
| `app/Http/Controllers/DayUserController.php` | `toggleUser`/`destroy` now create/delete an `Assignment` by `(user_id, date, position_id)`. Position becomes a required input where `popis` used to be free text. |
| `app/Models/Holiday.php` → `Absence` | `date_from`/`date_to` are `date` not `datetime`; `popis` → `reason`; `date_canceled` is gone (delete the row, or shorten `date_to`). |
| `app/Http/Controllers/HolidayController.php`, `HolidayStoreRequest`, `HolidayPolicy` | Rename to Absence*; deadline from `team_settings.absence_deadline_hours`. |
| `app/Models/File.php` → `Media` | `id_week` → `week_start`, `id_user` → `user_id`, `is_shown` → `is_visible`, add `team_id`. |
| `app/Models/Shift.php` | `date`+`start`+`end` → `starts_at`/`ends_at` casts. **Every hours calculation must be re-derived from the DATETIME pair** — this is where the overnight bug was. |
| `app/Models/Rate.php` | `break` → `break_deduction`. |
| `app/Http/Controllers/HoursController.php`, `app/Exports/WeeklyScheduleExport.php` | Follow the shifts/assignments changes. |
| `app/Livewire/UserTable.php`, `UnverifiedUserComposerViewComposer.php` | "Unverified" = `team_user.approved_at IS NULL`, not `id_role = 1`. |
| `AdminCreateUserRequest`, `AdminUserEditRequest` | Drop `id_role` validation; validate role name + team instead. |
| Blade views (17) | `$user->isAdmin()` → `@can` / `$user->hasRole('admin')`; `popis` → position name; day-keyed loops → date-keyed. |

### Add
- `app/Models/Team.php` (slug, settings, users, positions), `TeamSettings`, `Position`, `Assignment`, `WeekLock`
- `app/Traits/BelongsToTeam.php` — global scope + auto-fill `team_id` (`03-multitenancy.md` has the implementation)
- `app/Http/Middleware/SetActiveTeam.php` — `setPermissionsTeamId()` after auth, and on team switch
- `app/Http/Middleware/EnsureUserIsActive.php`

`BelongsToTeam` goes on: `TeamSettings`, `Position`, `Assignment`, `Absence`, `WeekLock`, `Shift`, `Rate`, `Media`. **Not** on `User` — users are cross-team by design now.

### Locking

One check, used by the assignment policy, the absence policy, and the admin UI:

```php
WeekLock::where('team_id', $teamId)->where('week_start', $weekStart)->exists()
```

Anything that writes an `assignment` or an `absence` for a date inside a locked week must be rejected. Locking is a delete-the-row-to-unlock operation.

---

## 5. Explicitly out of scope

- **Admin-declared headcount** ("Friday needs 2× RN"). A `demands` table was designed and cut — no consumer. Signup is open; admin curates after the fact. If the "RN: 1/2 filled" indicator or the mandatory-manager-before-lock rule from `07-work-planning.md` is wanted, that table is the way, and `positions.is_manager` is already there for it.
- Plan templates, shift marketplace (doc 12), iCal feed (doc 13), activity log, `spatie/laravel-settings` for `team_settings`.
- Filament panels. `team_settings.week_start_day` and `teams.slug` are in place for Filament's `->tenant()` when that phase starts.

---

## 6. No seeder yet

Deliberate. A seeder is useless until the models are rewritten — it would seed a schema the current app cannot read. Write it as part of the model work: one team, its `team_settings` row, `admin`/`manager`/`employee` roles + permissions, default positions (Uvádzač, Bufet, Pokladňa, Vedúci), and one admin user with `team_user.approved_at` set.

---

## 7. Verification already done

Migrations were applied to a throwaway `cnmx_migcheck` schema on the project's Docker MySQL 8 (created, tested, dropped — `cnmx_zmeny` untouched). All 11 applied clean. Asserted:

| Check | Result |
|---|---|
| Assignment pairing team A with team B's position | rejected — `ERROR 1452` on the composite FK |
| Correct-tenant assignment | accepted |
| Duplicate `(user, date, position)` | rejected — `ERROR 1062` |
| Same user, same day, two *different* positions | accepted (2 rows) |
| Overnight shift 21:00 → 01:30 | `worked_minutes = 270`, `payroll_date = 2026-08-14 (Friday)` — correct, not negative |
| `shifts.user_id` / `rates.user_id` delete rule | `RESTRICT` — payroll survives a user delete. All other FKs CASCADE / SET NULL as intended |
| Absence overlap query plan | uses `absences_team_id_date_from_date_to_index`, `Using index` (covering) |

Not yet verified: anything requiring application models.
