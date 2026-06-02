# 02 — Database Schema

All tables live inside the **tenant database** unless marked `[CENTRAL]`. Each tenant gets an isolated database (`tenant_{uuid}`) via Stancl Tenancy. Central tables live in the main application database.

---

## Central Database (shared infrastructure)

### `[CENTRAL] tenants`
```sql
id               uuid        PK (Stancl UUID generator)
name             string(120)  -- "Kino Lumière"
slug             string(60)   UNIQUE  -- used as DB prefix / subdomain
data             json         -- Stancl reserved payload
created_at       timestamp
updated_at       timestamp
```

### `[CENTRAL] domains`
```sql
id               bigint      PK
domain           string(255) UNIQUE
tenant_id        uuid        FK tenants.id CASCADE DELETE
```

### `[CENTRAL] users` (central super-admins only, optional)
Keep a minimal central user table for platform-level administration if needed. Ordinary cinema users live in the tenant DB.

---

## Tenant Database (per cinema)

### `tenant_settings`
One row per tenant (seeded on tenant creation). Stores all configurable behaviour.

```sql
id                       bigint      PK
week_offset              tinyint     -- 0=Mon, 3=Thu (default), day the work week starts
week_lookahead           tinyint     -- how many weeks ahead to show (default 5)
unavailability_hours     smallint    -- hours before a day that a user must submit unavailability (default 48)
allow_self_registration  boolean     -- can users register themselves (default false)
timezone                 string(50)  -- "Europe/Bratislava"
locale                   string(10)  -- "sk"
created_at               timestamp
updated_at               timestamp
```

> **Why a separate table instead of JSON on tenant?** Typed columns, easy Filament form binding, easy migrations when new settings are added.

---

### `users`
```sql
id               bigint      PK
name             string(100)
lastname         string(100)
email            string(180) UNIQUE
email_verified_at timestamp  nullable
password         string
remember_token   string(100) nullable
last_login_at    timestamp   nullable
is_active        boolean     DEFAULT true
created_at       timestamp
updated_at       timestamp
```

> Roles are handled entirely by Spatie Permission — no `id_role` column.

---

### `positions`
Named work roles that the cinema defines (e.g. Uvádzač, Bufet, Pokladňa, Vedúci).

```sql
id               bigint      PK
name             string(80)              -- "Uvádzač"
color            string(7)  nullable     -- "#3b82f6" — for UI colour coding
is_manager       boolean    DEFAULT false -- marks the position as the mandatory manager slot
sort_order       smallint   DEFAULT 0
is_active        boolean    DEFAULT true
created_at       timestamp
updated_at       timestamp
```

---

### `weeks`
```sql
id               bigint      PK
date_from        date        -- Thursday (or whatever week_offset is)
date_to          date        -- following Wednesday
locked           boolean     DEFAULT false
locked_at        timestamp   nullable
locked_by        bigint      nullable FK users.id SET NULL
created_at       timestamp
updated_at       timestamp

INDEX (date_from)
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

### `unavailabilities`
User declares they cannot work a specific day.

```sql
id               bigint      PK
user_id          bigint      FK users.id CASCADE DELETE
date             date
reason           string(255) nullable
submitted_at     timestamp               -- set to now() on creation; used for deadline auditing
admin_override   boolean     DEFAULT false -- admin can add/edit past the deadline
overridden_by    bigint      nullable FK users.id SET NULL
created_at       timestamp
updated_at       timestamp

UNIQUE (user_id, date)
INDEX (user_id, date)
```

---

### `shifts`
Actual recorded hours after the shift is worked (post-facto).

```sql
id               bigint      PK
user_id          bigint      FK users.id CASCADE DELETE
plan_assignment_id bigint    nullable FK plan_assignments.id SET NULL
date             date
position_id      bigint      nullable FK positions.id SET NULL
start            time
end              time
break_minutes    smallint    DEFAULT 0
notes            string(255) nullable
created_at       timestamp
updated_at       timestamp

UNIQUE (user_id, date, start)
INDEX (user_id, date)
```

---

### `rates`
Hourly pay rates per user — used for payroll report calculations.

```sql
id               bigint      PK
user_id          bigint      UNIQUE FK users.id CASCADE DELETE
weekday          decimal(8,2)
saturday         decimal(8,2)
sunday           decimal(8,2)
break_deduction  decimal(8,2) DEFAULT 0
created_at       timestamp
updated_at       timestamp
```

---

### `media`
Replaces the old `file_storage` table. Week-scoped files uploaded by admin (rosters, notes, etc.).

```sql
id               bigint      PK
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
tenants (central)
  └── tenant_settings
  └── users ──────────────────────────────────┐
        └── rates                             │
        └── unavailabilities                  │
        └── shifts ── plan_assignments ◄──── plan_slots ── days ── weeks
                        └── positions ◄────────────────────────────────┘
```

---

## Migration Notes

- Use `Schema::create()` migrations, never raw SQL.
- All FKs use `constrained()` helper with explicit `onDelete()`.
- Add database-level `UNIQUE` constraints in addition to application-level validation.
- Tenant migrations live in `database/migrations/tenant/` — Stancl runs them on tenant creation.
- Central migrations live in `database/migrations/` as usual.
- Seed positions with sensible defaults (Uvádzač, Bufet, Pokladňa, Vedúci) in `TenantSeeder`.
- `tenant_settings` is seeded with 1 row immediately on tenant creation via `JobPipeline`.

---

## What Was Dropped vs Old Schema

| Old | New | Reason |
|---|---|---|
| `roles` table | Spatie `roles` table | Proper RBAC |
| `id_role` on users | Spatie model_has_roles | Decoupled roles |
| `user_days` pivot | `plan_slots` + `plan_assignments` | Support positions, times, required counts |
| `bugs` table | Removed | Out of scope |
| `file_storage` | `media` | Cleaner, optional Spatie Media Library |
| `mediumInt` PKs | `bigint` PKs | Standard Laravel `id()` |
| `weeks.next_week_id / prev_week_id` | Removed — derive from `date_from` ordering | Redundant self-references |
