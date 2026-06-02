---
name: project-decisions
description: Key architectural decisions locked in during the planning session
metadata: 
  node_type: memory
  type: project
  originSessionId: acc11c85-ddb3-4b6c-b692-d7b47f339774
---

Decisions made during the 2026-06-02 planning session — these should be treated as settled unless Tomáš explicitly revisits them.

**Tenancy:** Keep Stancl Tenancy v3, database-per-tenant. Subdomain routing (`cinemaname.app.com`). Do not switch to path-based or shared-DB tenancy.

**RBAC:** Spatie Permission v6 teams mode. Three roles: `admin`, `manager`, `employee`. Blocked users → `is_active = false` flag, not a role. Unverified self-registered users → no role assigned until admin approves.

**Admin panel:** Filament v3. Two panels: tenant admin at `/admin` (cinema staff), central platform panel at `app.com/platform` (super-admins only).

**Settings:** `spatie/laravel-settings` + `filament/spatie-laravel-settings-plugin` for typed tenant settings (replaces plain Eloquent `tenant_settings` row from early planning).

**Work plan:** Position+time slots (`plan_slots`) assigned per day. Users assigned to slots via `plan_assignments`. Manager slot (`positions.is_manager = true`) required per day — hard block on week lock if missing.

**Absence system:** Unified `absences` table with type enum (single/multi/recurring). No approval flow — user self-service. Deadline enforced via `tenant_settings.absence_hours`. Replaces old `user_holidays`.

**iCal:** `spatie/icalendar-generator` — do not build raw iCal strings.

**Position hierarchy:** `tier` + `tier_group` columns on `positions`. Higher tier covers lower tiers in the same group. Zaucovaný(1) → Uvádzač(2) → Bufet(3) = group 1. Pokladňa = group 2. Vedúci = group 3.

**DnD for plan grid:** `saade/filament-fullcalendar` as primary plan builder. `guava/calendar` v1.x for employee-overview tab. SortableJS already bundled in Filament for reorderable lists — no extra install.

**Employee portal UI:** MaryUI (`robsontenorio/mary`) for Livewire employee-facing pages.

**PDF exports:** `spatie/laravel-pdf` if Chromium available on server; fall back to `barryvdh/laravel-dompdf` for simple layouts.

**Email:** `resend/resend-laravel` as the mailer driver.

**Queue monitoring:** `laravel/horizon` (Redis queues required).

**Permissions auto-gen:** `bezhansalleh/filament-shield` — generates Spatie permissions from Resource class names.

**Tenancy↔Filament bridge:** `tomatophp/filament-tenancy`.

**Bug reporting feature from old app:** Dropped — out of scope.
