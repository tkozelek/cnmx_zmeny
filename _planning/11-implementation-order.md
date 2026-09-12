# 11 — Implementation Roadmap

Each phase is a separate git branch merged to `development` when complete. Never work directly on `master`.

---

## Phase 0 — Project Bootstrap
**Branch:** `feature/bootstrap`  
**Estimated effort:** 1–2 days

- [ ] Fresh Laravel 12 install (or clean branch from master)
- [ ] Install and configure all packages:
  - `stancl/tenancy` v3
  - `filament/filament` v3
  - `spatie/laravel-permission` v6 (teams mode)
  - `spatie/laravel-activitylog`
  - `maatwebsite/excel` v3
  - `barryvdh/laravel-dompdf`
  - `laravel/pulse`
- [ ] Configure Redis for cache, session, queues (`.env` template)
- [ ] Configure Stancl Tenancy: bootstrappers, central domains, job pipeline
- [ ] Configure Spatie Permission: `teams = true`, `team_model = Tenant::class`
- [ ] Set up two Filament panels: `TenantAdminPanelProvider` + `PlatformPanelProvider`
- [ ] Basic CI pipeline: `composer audit`, PHPStan level 6, Pint, `php artisan test`
- [ ] `SecurityHeaders` middleware wired in
- [ ] Verify: tenant creation creates isolated DB + seeds it

---

## Phase 1 — Core Data Model
**Branch:** `feature/core-schema`  
**Depends on:** Phase 0  
**Estimated effort:** 2–3 days

- [ ] Central migrations: `tenants`, `domains`
- [ ] Tenant migrations (in `database/migrations/tenant/`):
  - `tenant_settings`
  - `users` (without `id_role`)
  - `positions`
  - `weeks`
  - `days`
  - `plan_slots`
  - `plan_assignments`
  - `unavailabilities`
  - `shifts`
  - `rates`
  - `media`
  - Spatie permission tables (via `artisan vendor:publish`)
  - Activity log table
- [ ] All Eloquent models with relationships, fillable lists, casts
- [ ] `TenantSeeder`: positions, roles, permissions, role→permission matrix, `tenant_settings`
- [ ] `CreateTenantAdmin` job: creates first admin user + invite email
- [ ] Basic model factories for all tables (for tests)
- [ ] PHPUnit feature tests: tenant creation, seeding, DB isolation check

---

## Phase 2 — Authentication & Permissions
**Branch:** `feature/auth`  
**Depends on:** Phase 1  
**Estimated effort:** 2 days

- [ ] Login / logout controllers (tenant routes)
- [ ] Password reset flow (tenant-aware brokers)
- [ ] `SetSpatiePermissionTeamId` middleware
- [ ] `EnsureUserIsActive` middleware
- [ ] All Policy classes (Week, PlanSlot, PlanAssignment, Unavailability, User, Shift, Media)
- [ ] Register all policies in `AuthServiceProvider`
- [ ] Self-registration flow (behind `allow_self_registration` setting)
- [ ] Filament login pages wired (tenant panel + platform panel)
- [ ] PHPUnit tests: role access, policy denials, cross-tenant isolation attempt

---

## Phase 3 — Filament: Users & Settings
**Branch:** `feature/filament-users`  
**Depends on:** Phase 2  
**Estimated effort:** 2–3 days

- [ ] `UserResource` (list, create with invite, edit, deactivate, delete)
- [ ] `PositionResource` (CRUD, drag reorder)
- [ ] `TenantSettingsResource` (single edit page)
- [ ] `PlatformPanel`: `TenantResource` (create/suspend/delete tenants)
- [ ] Invite email notification wired
- [ ] `UserAllowedToLogin` notification wired to role assignment
- [ ] Basic dashboard page (placeholder widgets)

---

## Phase 4 — Week & Plan System
**Branch:** `feature/plan`  
**Depends on:** Phase 3  
**Estimated effort:** 4–5 days

- [ ] `WeekService::generateWeekForDate()` (week_offset aware)
- [ ] `GenerateFutureWeeks` scheduled command
- [ ] `WeekResource` with `GenerateWeekAction`, `LockWeekAction`
- [ ] Plan template tables + `PositionTemplateResource`
- [ ] `GeneratePlanFromTemplateAction`
- [ ] `WeekPlanPage` (custom Filament page — the week grid)
- [ ] `AssignUserToSlotAction` (slot assignment modal with unavailability warnings)
- [ ] Manager position validation on lock
- [ ] `PlanConflictService::detectForWeek()`
- [ ] `WeekLocked` notification queued to all assigned employees
- [ ] `UnlockWeekAction`
- [ ] Activity logging on all plan changes
- [ ] Feature tests: plan generation, locking, conflict detection

---

## Phase 5 — Unavailability System
**Branch:** `feature/unavailability`  
**Depends on:** Phase 2 (auth), Phase 4 (week locking check)  
**Estimated effort:** 2–3 days

- [ ] `UnavailabilityService` with `canSubmit()`, `create()`, deadline logic
- [ ] `UnavailabilityPolicy`
- [ ] `UnavailabilityResource` in Filament (table, filters, admin create/delete)
- [ ] `UnavailabilityWidget` on Filament dashboard
- [ ] `UnavailabilityManager` Livewire component (employee UI)
- [ ] Deadline-based date picker restrictions (client + server)
- [ ] Edge case handling: locked week, past date, duplicate date
- [ ] Conflict badge wiring in `WeekPlanPage`
- [ ] Optional: `SendUnavailabilityReminderNotification` scheduled command
- [ ] Feature tests: deadline enforcement, admin override, locked week rejection

---

## Phase 6 — Employee Frontend
**Branch:** `feature/employee-ui`  
**Depends on:** Phase 4, Phase 5  
**Estimated effort:** 3–4 days

- [ ] Blade layout + navigation
- [ ] `CalendarWeek` Livewire component (read-only week grid)
- [ ] Week navigation (prev/next)
- [ ] `HoursView` Livewire component
- [ ] `ProfileSettings` Livewire component (password change)
- [ ] Responsive design (mobile-first, stacked day view on small screens)
- [ ] Accessibility audit (labels, contrast, keyboard navigation)
- [ ] Unavailability page wired to `UnavailabilityManager`

---

## Phase 7 — Shifts & Hours
**Branch:** `feature/shifts`  
**Depends on:** Phase 3  
**Estimated effort:** 2 days

- [ ] `ShiftResource` in Filament (CRUD, inline edit)
- [ ] `RateResource` or embed rates in `UserResource` (collapsible subform)
- [ ] Hours calculation service (`ShiftCalculator`: hours = end - start - break_minutes/60)
- [ ] Weekend rate detection (check shift date for Saturday/Sunday)
- [ ] `HoursView` data calculations wired

---

## Phase 8 — Reports & Exports
**Branch:** `feature/reports`  
**Depends on:** Phase 4, Phase 7  
**Estimated effort:** 3 days

- [ ] `WeeklyPlanExport` (Excel: plan sheet + unavailability sheet)
- [ ] PDF export for weekly plan (`barryvdh/laravel-dompdf`)
- [ ] `HoursSummaryExport` (Excel)
- [ ] `PayrollCalculationExport` (Excel, admin only)
- [ ] Position coverage view + PDF
- [ ] `UnavailabilitySummaryExport` (Excel)
- [ ] `ActivityLogExport` (Excel, admin only)
- [ ] `ReportsPage` in Filament wiring all exports
- [ ] `ExportPlanAction` on `WeekResource`

---

## Phase 9 — Media Management
**Branch:** `feature/media`  
**Depends on:** Phase 3  
**Estimated effort:** 1 day

- [ ] `MediaResource` in Filament
- [ ] File upload with MIME validation
- [ ] Secure download controller (auth check)
- [ ] `is_public` toggle — public files visible to employees on CalendarPage

---

## Phase 10 — Polish, Testing & Hardening
**Branch:** `feature/hardening`  
**Depends on:** all prior phases  
**Estimated effort:** 3–4 days

- [ ] Full PHPStan pass at level 6 — fix all errors
- [ ] Full `pint` code style pass
- [ ] `composer audit` — resolve any advisories
- [ ] Complete Policy test coverage
- [ ] Cross-tenant isolation test (verify tenant A cannot access tenant B data)
- [ ] Load test on `WeekPlanPage` with large employee count
- [ ] Security headers verified in browser DevTools
- [ ] HTTPS redirect tested
- [ ] Session security settings verified
- [ ] Review all `$fillable` lists
- [ ] Audit all routes for missing `authorize()` calls
- [ ] Pulse dashboard configured
- [ ] Error pages (403, 404, 500) localized to Slovak

---

## Phase 11 — Data Migration from Old App
**Branch:** `feature/data-migration`  
**Estimated effort:** 2 days

- [ ] One-off `MigrateOldDataCommand` artisan command
- [ ] Maps old `users` → new `users` + assigns `employee` role
- [ ] Maps old role integer 3 → `admin` role
- [ ] Maps old `user_holidays` → `unavailabilities`
- [ ] Maps old `shifts` → new `shifts` (column rename)
- [ ] Maps old `rates` → new `rates` (column rename)
- [ ] Old `user_days` → `plan_assignments` with `position_id = null` (flagged for manual review)
- [ ] Validate counts before and after
- [ ] Dry-run mode (`--dry-run` flag that rolls back transaction)

---

## Package Reference

```json
"require": {
    "laravel/framework": "^12.0",
    "laravel/sanctum": "^4.0",
    "laravel/pulse": "^1.4",
    "livewire/livewire": "^3.4",
    "filament/filament": "^3.2",
    "stancl/tenancy": "^3.8",
    "spatie/laravel-permission": "^6.0",
    "spatie/laravel-activitylog": "^4.0",
    "maatwebsite/excel": "^3.1",
    "barryvdh/laravel-dompdf": "^2.0",
    "guzzlehttp/guzzle": "^7.2"
},
"require-dev": {
    "barryvdh/laravel-ide-helper": "^3.0",
    "fakerphp/faker": "^1.9",
    "laravel/pint": "^1.0",
    "laravel/sail": "^1.18",
    "mockery/mockery": "^1.4",
    "nunomaduro/collision": "^8.0",
    "nunomaduro/larastan": "^2.9",
    "pestphp/pest": "^3.0",
    "pestphp/pest-plugin-laravel": "^3.0"
}
```

---

## Definition of Done (per phase)

A phase is complete when:
1. All checklist items are ticked
2. `php artisan test` passes with no failures
3. PHPStan level 6 reports no errors
4. `pint --test` reports no style issues
5. Reviewed and merged to `development` via PR
6. `development` branch is deployable to staging at any time
