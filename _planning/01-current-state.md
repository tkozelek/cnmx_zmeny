# 01 — Current State Audit

## What Exists Today

### Strengths to Carry Forward
- Week-based scheduling model is the right abstraction — keep `weeks` + `days` concept
- Holiday/unavailability model (`user_holidays`) is directionally correct
- Shift + Rate model for hours tracking is good; refine rather than scrap
- File-per-week uploads are a useful admin tool
- Excel export (`WeeklyScheduleExport`) is already wired — reuse the pattern
- Livewire already integrated (v3) — keep for employee UI
- Maatwebsite Excel already installed

### Things to Scrap / Fully Replace

| Current Thing | Problem | Replacement |
|---|---|---|
| Integer role IDs in `roles` table | Magic numbers, not extensible | Spatie Permission with teams |
| Custom `EnsureUserHasRole` middleware | Fragile, bypasses Spatie | Spatie `role` / `permission` middleware |
| `AuthServiceProvider` — empty policies | Gates not properly registered | Proper Policy classes + `Gate::policy()` |
| `mediumInt` PKs everywhere | Artificial limit, non-standard | `unsignedBigInteger` / `id()` helper |
| `user_days` pivot only stores `popis` | No position, no time slot — too flat | `work_plan_assignments` (see DB plan) |
| Tenant routes with `dd("ahoj")` | Placeholder only | Proper tenant-aware routing |
| Hard-coded 5-week lookahead in config | Cannot differ per tenant | Tenant settings JSON column |
| `file_storage` mixed use | Week files + bug attachments in one table | Split: `media` (Spatie Media Library or simple) and `bug_attachments` |
| Bug reporting feature | Out of scope for scheduling app | Remove or move to a separate admin-only tool |
| `consoletvs/charts` | Outdated, limited | Replace with Filament widgets or simple Chart.js |
| Laravel Pulse | Keep but move to central (non-tenant) panel | Central Filament panel only |

---

## Existing Code That Can Be Salvaged

### Services
- `WeekService` — logic for generating week date ranges from an offset. **Adapt** to read `week_offset` from tenant settings.
- `WeekDataService` — aggregates calendar data. **Rewrite** to include positions and plan assignments.
- `HolidayService` — unavailability logic. **Rename** to `UnavailabilityService`, add deadline enforcement.
- `FileService` — basic upload/delete. **Keep** but refactor to use a proper media library or at minimum clean storage paths.

### Exports
- `WeeklyScheduleExport` — **rewrite** to output position-aware plan (who works which position at which time).

### Notifications
- `UserAllowedToLogin` — **keep**, adapt to Filament-triggered approval flow.
- `ResetPasswordNotification` — standard Laravel, **keep as-is**.

### Livewire Components
- `UserTable` Livewire component — **rewrite** in Filament (Filament has its own table builder).
- `RoleAssignment` — **replace** with Filament user resource + Spatie role picker.

---

## Migration Strategy

Because the DB schema changes completely, this is a **greenfield install with data migration scripts**, not an in-place upgrade:

1. Create new app in a fresh Laravel 12 install (or a new git branch).
2. Write one-off migration scripts that read old DB and seed new tenant-aware tables.
3. Map old role integers → new Spatie roles.
4. Map old `user_days` records → `work_plan_assignments` where possible (positions will be unknown — flag as "unassigned position").
5. Carry over `user_holidays` → `unavailabilities`.
6. Carry over `shifts` and `rates` directly (column rename only).

---

## Open Questions to Resolve Before Implementation

- [ ] Will each cinema get its own subdomain (`cinemaname.app.com`) or path-based routing?
- [ ] Should tenants share the same database with `tenant_id` scoping, or have separate databases? (Current config = separate DB — recommend keeping for true isolation)
- [ ] What is the maximum number of positions a single day plan can have? (Affects UI layout)
- [ ] Should the manager slot be a special position type, or just a position named "Vedúci"?
- [ ] Is the existing `hours` / `rates` system in scope for the rewrite? (hours tracking vs shift scheduling are two features)
- [ ] Should users be able to register themselves, or admin-invite only in the new version?
