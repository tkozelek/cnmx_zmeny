# 02 — Database Schema

All tables live in a **single shared database**. Each cinema is a row in the `teams` table. Tenant-scoped models carry a `team_id` foreign key and use a `BelongsToTeam` global scope for automatic data isolation. No Stancl Tenancy — Filament v3's native `->tenant()` handles panel-level scoping.

---

## `teams`

```sql
id            bigint      PK
name          string(120)             -- "Kino Lumière"
slug          string(60)  UNIQUE      -- subdomain identifier
is_active     boolean     DEFAULT true
created_at    timestamp
updated_at    timestamp
```

---

## `team_user` (pivot)

```sql
team_id   bigint  FK teams.id CASCADE DELETE
user_id   bigint  FK users.id CASCADE DELETE
PRIMARY KEY (team_id, user_id)
```

---

## `team_settings`
One row per team. Stores all per-cinema configurable behaviour.

```sql
id                       bigint      PK
team_id                  bigint      UNIQUE FK teams.id CASCADE DELETE
week_offset              tinyint     -- 0=Mon, 3=Thu (default), day the work week starts
week_lookahead           tinyint     -- how many weeks ahead to show (default 5)
absence_hours            smallint    -- hours before a day that submission is still allowed (default 48)
absence_max_multi_days   tinyint     -- max length of a multi-day absence block (default 30, 0=unlimited)
absence_reminder_enabled boolean     DEFAULT true
allow_self_registration  boolean     DEFAULT false
marketplace_enabled      boolean     DEFAULT true
marketplace_auto_approve boolean     DEFAULT false
timezone                 string(50)  -- "Europe/Bratislava"
locale                   string(10)  -- "sk"
created_at               timestamp
updated_at               timestamp
```

> Backed by `spatie/laravel-settings` so the class is typed PHP — see doc 04/18 for details.

---

### `users`
```sql
id                bigint      PK
team_id           bigint      FK teams.id CASCADE DELETE
current_team_id   bigint      nullable FK teams.id SET NULL
name              string(100)
lastname          string(100)
email             string(180)
email_verified_at timestamp   nullable
password          string
remember_token    string(100) nullable
ical_token        string(64)  nullable UNIQUE
last_login_at     timestamp   nullable
is_active         boolean     DEFAULT true
created_at        timestamp
updated_at        timestamp

UNIQUE (team_id, email)   -- email unique per team, not globally
```

> `team_id` = which team this user account belongs to. `current_team_id` = the active team for users who belong to multiple teams. Roles handled entirely by Spatie Permission — no `id_role` column.

---

### `positions`
Named work roles that the cinema defines (e.g. Uvádzač, Bufet, Pokladňa, Vedúci).

```sql
id               bigint      PK
team_id          bigint      FK teams.id CASCADE DELETE
name             string(80)              -- "Uvádzač"
color            string(7)  nullable     -- "#3b82f6" — for UI colour coding
is_manager       boolean    DEFAULT false -- marks the position as the mandatory manager slot
tier             tinyint    DEFAULT 1    -- skill level within the group
tier_group       tinyint    DEFAULT 1    -- 1=floor, 2=box office, 3=management, 0=standalone
sort_order       smallint   DEFAULT 0
is_active        boolean    DEFAULT true
created_at       timestamp
updated_at       timestamp

INDEX (team_id)
```

---

### `weeks`
```sql
id               bigint      PK
team_id          bigint      FK teams.id CASCADE DELETE
date_from        date        -- Thursday (or whatever week_offset is)
date_to          date        -- following Wednesday
locked           boolean     DEFAULT false
locked_at        timestamp   nullable
locked_by        bigint      nullable FK users.id SET NULL
created_at       timestamp
updated_at       timestamp

INDEX (team_id, date_from)
```

---

### `days`
```sql
id               bigint      PK
week_id          bigint      FK weeks.id CASCADE DELETE
date             date
INDEX (date)
UNIQUE (week_id, date)
```

---

### `plan_slots`
The **template** of what positions are needed on a given day. Admin builds this when generating the plan for a week.

```sql
id               bigint      PK
day_id           bigint      FK days.id CASCADE DELETE
position_id      bigint      FK positions.id CASCADE DELETE
start_time       time                    -- "13:00"
end_time         time        nullable    -- "21:00" (optional, for display)
required_count   tinyint     DEFAULT 1  -- how many people are needed for this slot
notes            string(255) nullable
sort_order       smallint    DEFAULT 0
created_at       timestamp
updated_at       timestamp

INDEX (day_id, position_id)
```

---

### `plan_assignments`
Connects a user to a specific `plan_slot`. One row per assigned person.

```sql
id               bigint      PK
plan_slot_id     bigint      FK plan_slots.id CASCADE DELETE
user_id          bigint      nullable FK users.id SET NULL  -- nullable: slot defined but not yet filled
assigned_by      bigint      nullable FK users.id SET NULL
assigned_at      timestamp   nullable
notes            string(255) nullable
created_at       timestamp
updated_at       timestamp

INDEX (plan_slot_id)
INDEX (user_id)
```

> One `plan_slot` with `required_count = 2` will have up to 2 `plan_assignment` rows.

---

### `absences`
Replaces `unavailabilities`. Covers single-day, multi-day, and recurring — see doc 15.

```sql
id              bigint      PK
team_id         bigint      FK teams.id CASCADE DELETE
user_id         bigint      FK users.id CASCADE DELETE
type            enum        'single', 'multi', 'recurring'
date            date        nullable
date_from       date        nullable
date_to         date        nullable
day_of_week     tinyint     nullable
recurring_from  date        nullable
recurring_until date        nullable
reason          string(500) nullable
submitted_at    timestamp
admin_override  boolean     DEFAULT false
overridden_by   bigint      nullable FK users.id SET NULL
created_at      timestamp
updated_at      timestamp

UNIQUE (user_id, date)
INDEX  (team_id, user_id)
```

---

### `shifts`
Actual recorded hours after the shift is worked (post-facto).

```sql
id                 bigint      PK
team_id            bigint      FK teams.id CASCADE DELETE
user_id            bigint      FK users.id CASCADE DELETE
plan_assignment_id bigint      nullable FK plan_assignments.id SET NULL
date               date
position_id        bigint      nullable FK positions.id SET NULL
start              time
end                time
break_minutes      smallint    DEFAULT 0
notes              string(255) nullable
created_at         timestamp
updated_at         timestamp

UNIQUE (user_id, date, start)
INDEX  (team_id, user_id, date)
```

---

### `rates`
Hourly pay rates per user — used for payroll report calculations.

```sql
id               bigint      PK
team_id          bigint      FK teams.id CASCADE DELETE
user_id          bigint      FK users.id CASCADE DELETE
weekday          decimal(8,2)
saturday         decimal(8,2)
sunday           decimal(8,2)
break_deduction  decimal(8,2) DEFAULT 0
created_at       timestamp
updated_at       timestamp

UNIQUE (team_id, user_id)
```

---

### `media`
Replaces the old `file_storage` table. Week-scoped files uploaded by admin (rosters, notes, etc.).

```sql
id               bigint      PK
team_id          bigint      FK teams.id CASCADE DELETE
week_id          bigint      nullable FK weeks.id SET NULL
user_id          bigint      nullable FK users.id SET NULL  -- uploader
disk             string(50)  DEFAULT 'local'
path             string(500)
filename         string(255)
original_name    string(255)
mime_type        string(100)
size             bigint
is_public        boolean     DEFAULT false
created_at       timestamp
updated_at       timestamp

INDEX (team_id)
```

> Alternative: use **Spatie Media Library** and attach media to Week models directly. Recommended if media management gets complex.

---

### `activity_log` (Spatie Activity Log)
```sql
id               bigint      PK
log_name         string      nullable
description      text
subject_type     string      nullable
subject_id       bigint      nullable
causer_type      string      nullable
causer_id        bigint      nullable
properties       json        nullable
event            string      nullable
batch_uuid       uuid        nullable
created_at       timestamp
updated_at       timestamp
```

> Add `spatie/laravel-activitylog` — critical for audit trail in a multi-user scheduling context.

---

### Spatie Permission Tables (tenant-scoped)
Auto-created by `spatie/laravel-permission` with teams mode enabled:

- `roles` — id, name, guard_name, team_id (nullable)
- `permissions` — id, name, guard_name
- `model_has_roles` — role_id, model_type, model_id, team_id
- `model_has_permissions` — permission_id, model_type, model_id, team_id
- `role_has_permissions` — permission_id, role_id

---

## Schema Diagram (simplified)

```
teams
  └── team_settings
  └── users (team_id) ─────────────────────────────┐
        └── rates (team_id)                        │
        └── absences (team_id)                     │
        └── shifts (team_id) ── plan_assignments ◄── plan_slots ── days ── weeks (team_id)
                                                          └── positions (team_id)
```

---

## Migration Notes

- All migrations in `database/migrations/` — single migration set, one database.
- All FKs use `constrained()` helper with explicit `onDelete()`.
- `team_id` indexed on every top-level tenant table.
- Seed positions with sensible defaults (Uvádzač, Bufet, Pokladňa, Vedúci) in `TeamSeeder`.
- `team_settings` row created automatically when a `Team` is created via model observer.

---

## What Was Dropped vs Old Schema

| Old | New | Reason |
|---|---|---|
| `tenants` / `domains` (Stancl) | `teams` + `team_user` pivot | Single DB, no separate databases |
| `roles` table (integer IDs) | Spatie `roles` table | Proper RBAC |
| `id_role` on users | Spatie `model_has_roles` | Decoupled roles |
| `user_days` pivot | `plan_slots` + `plan_assignments` | Support positions, times, required counts |
| `user_holidays` | `absences` | Unified single/multi/recurring model |
| `bugs` table | Removed | Out of scope |
| `file_storage` | `media` | Cleaner, with team_id scoping |
| `mediumInt` PKs | `bigint` PKs | Standard Laravel `id()` |
| `weeks.next_week_id / prev_week_id` | Removed — derive from `date_from` ordering | Redundant self-references |
| `tenant_settings` (Stancl) | `team_settings` backed by `spatie/laravel-settings` | Typed, cached, single-DB |
