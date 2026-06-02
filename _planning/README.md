# CNMX Zmeny — Multi-Tenant Rewrite Planning

> Branch: `planning/multi-tenant-rewrite`  
> Date: 2026-06-02  
> Status: Planning phase — do NOT implement from this branch, open a fresh feature branch per section.

---

## What This App Does

**CNMX Zmeny** is a shift scheduling system for cinema staff. Workers sign up for available work days. The work week runs Thursday → Wednesday (non-standard offset). Admins manage users, lock weeks once finalized, upload files, and export schedules. The codebase already has a partially-wired Stancl Tenancy v3 skeleton and Spatie Laravel Permission installed but unused.

---

## Rewrite Goals

| Goal | Notes |
|---|---|
| True multi-tenancy | Each cinema is a fully isolated tenant |
| Modern admin | Filament v3 panel for all management tasks |
| Proper RBAC | Spatie Permission with teams (one team = one tenant) |
| Configurable weeks | Each tenant sets their own week offset, advance-notice rules |
| Work plan system | Admin builds a shift template per day with named positions and times |
| Unavailability | Users mark days they cannot work; configurable submission deadline |
| Week locking | Admin locks a week; locked weeks become read-only |
| Reports & exports | PDF/Excel for work plans, hours summaries, position coverage |
| Full DB redesign | Start clean — no legacy mediumInt PKs, no magic role integers |
| Security-first | Rate limiting, policy gates, audit trail, CSRF, XSS hardening |

---

## Planning Documents Index

| File | Topic |
|---|---|
| [01-current-state.md](01-current-state.md) | Audit of the existing app: what to keep, what to scrap |
| [02-database.md](02-database.md) | Complete new DB schema with rationale |
| [03-multitenancy.md](03-multitenancy.md) | Tenancy strategy, domain routing, data isolation |
| [04-spatie-permissions.md](04-spatie-permissions.md) | Roles, permissions, team setup with Spatie |
| [05-filament.md](05-filament.md) | Filament admin panel — panels, resources, widgets |
| [06-livewire.md](06-livewire.md) | Livewire frontend — components, real-time behaviour |
| [07-work-planning.md](07-work-planning.md) | Position templates, plan generation, week locking |
| [08-unavailability.md](08-unavailability.md) | Unavailability system, deadlines, backwards-write prevention |
| [09-security.md](09-security.md) | Security, data integrity, rate limiting, audit log |
| [10-reports.md](10-reports.md) | Reports, exports (PDF / Excel), scheduled summaries |
| [11-implementation-order.md](11-implementation-order.md) | Phased implementation roadmap |
| [12-shift-marketplace.md](12-shift-marketplace.md) | Shift marketplace — replacement requests, offers, email blasts, admin approval |

---

## Technology Stack

- **Laravel 12** — framework
- **Livewire 3** — reactive user-facing UI
- **Filament 3** — admin panel
- **Spatie Laravel Permission 6** (teams mode) — RBAC
- **Stancl Tenancy 3** — database-per-tenant isolation (keep existing package)
- **Maatwebsite Excel 3** — exports
- **Laravel Pulse** — observability
- **PostgreSQL or MySQL 8** — database (new schema)
- **Redis** — cache, queues, rate limiting

---

## Key Decisions Summary

1. **Keep Stancl Tenancy v3** — it is already installed and partially configured; implement it properly instead of switching.
2. **Replace custom role integers with Spatie teams** — roles become `admin`, `manager`, `employee`; one Spatie Team per tenant.
3. **Filament for all admin work** — user management, position setup, plan builder, reports, week locking.
4. **Livewire for employee-facing UI** — calendar view, unavailability marking, hours view.
5. **Positions are tenant-scoped** — each cinema defines its own position names and typical slots.
6. **Work plan = generated, not free-form** — admin builds a template of position+time slots, assigns users to slots.
7. **Unavailability is forward-only** — users cannot mark a past day; a configurable `advance_hours` setting controls how early they must submit.
