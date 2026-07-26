# CNMX Zmeny — Multi-Tenant Rewrite Planning

> Branch: `planning/multi-tenant-rewrite`  
> Date: 2026-06-02  
> Status: Core rewrite implemented & verified.

---

## What This App Does

**CNMX Zmeny** is a shift scheduling system for cinema staff. Workers sign up for available work days. The work week runs Thursday → Wednesday (non-standard offset). Admins manage users, lock weeks once finalized, upload files, and export schedules.

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
| [13-ical-feed.md](13-ical-feed.md) | iCal subscription feed — private per-user URL for Google/Apple/Outlook Calendar sync |
| [14-position-skills.md](14-position-skills.md) | Position skill tiers & training hierarchy (Zaucovaný → Uvádzač → Bufet) |
| [15-absence-system.md](15-absence-system.md) | Unified absence system — single-day, multi-day, recurring; replaces doc 08 |
| [16-analytics.md](16-analytics.md) | Analytics dashboard — hours, position coverage, absence patterns, marketplace stats |
| [17-logging.md](17-logging.md) | Logging system — audit log, application log, security log, production destinations |
| [18-packages.md](18-packages.md) | Recommended packages — Filament plugins, DnD, Spatie, Livewire UI, DX tooling |
| [19-design-system.md](19-design-system.md) | Design system — color, type, spacing, and UI patterns shared by Filament + Livewire |
| [20-schema-rework-handoff.md](20-schema-rework-handoff.md) | Authoritative database schema definition |
| [21-backend-rewrite-progress.md](21-backend-rewrite-progress.md) | As-built backend, Livewire components, routes, and test status |
| [22-ui-tables-permissions-handoff.md](22-ui-tables-permissions-handoff.md) | Detailed handoff document for UI DataTables, Spatie dot-notation permissions, team policies, and absence modal |

---

## Technology Stack

- **Laravel 13** — framework (PHP 8.4)
- **Livewire 3** — reactive user-facing UI
- **Spatie Laravel Permission 6** (teams mode) — RBAC
- **Tailwind CSS v3** — styling
- **Maatwebsite Excel 3** — exports
- **Laravel Pulse** — observability
