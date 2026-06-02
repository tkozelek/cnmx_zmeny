---
name: project_cinema_decisions
description: Locked architectural decisions for the cinema scheduler rewrite
metadata: 
  node_type: memory
  type: project
  originSessionId: acc11c85-ddb3-4b6c-b692-d7b47f339774
---

Settled as of 2026-06-02. Treat as final unless Tomáš explicitly revisits.

- **Multi-tenancy:** Single DB + Spatie teams. Stancl Tenancy rejected — overkill for this scale. All tenant models carry `team_id` + `BelongsToTeam` global scope.
- **Filament scoping:** Native `->tenant(Team::class)` — no third-party tenancy bridge needed.
- **Roles:** Three — `admin`, `manager`, `employee`. Blocked users = `is_active = false`, not a role.
- **Settings:** `spatie/laravel-settings` + `filament/spatie-laravel-settings-plugin` for typed per-team config.
- **Absences:** Single `absences` table, type enum: `single` / `multi` / `recurring`. No approval flow.
- **Plan grid DnD:** `saade/filament-fullcalendar` as primary builder. `guava/calendar` v1.x for employee overview.
- **Employee portal:** MaryUI (`robsontenorio/mary`) on Livewire.
- **Email:** `resend/resend-laravel`.
- **Queues:** `laravel/horizon` (Redis required).
- **Permission generation:** `bezhansalleh/filament-shield`.
- **iCal:** `spatie/icalendar-generator` — no hand-rolled iCal strings.
- **Bug reporting (old app):** Dropped — out of scope.
