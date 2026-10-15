---
name: project-overview
description: "What cnmx_zmeny is, current state, and rewrite goals"
metadata: 
  node_type: memory
  type: project
  originSessionId: acc11c85-ddb3-4b6c-b692-d7b47f339774
---

**cnmx_zmeny** is a cinema shift scheduling app. Staff sign up for work days. The work week runs Thursday → Wednesday (non-standard offset). Admins manage users, lock weeks, upload files, export schedules.

**Current state (master branch):**
- Laravel 12, Livewire 3, custom integer-based roles (1=Neoverený, 2=Brigádnik, 3=Admin, 4=Zablokovaný)
- Stancl Tenancy v3 installed but barely wired (one test route `dd("ahoj")`)
- Spatie Permission installed but unused
- 21 migrations, mediumInt PKs, flat `user_days` pivot (no position/time tracking)
- 94 known vulnerabilities on master (old dependencies)

**Rewrite goals (planning branch: `planning/multi-tenant-rewrite`):**
- True multi-tenancy: database-per-tenant via Stancl Tenancy v3
- Filament v3 admin panel (tenant panel at `/admin`, central platform panel)
- Spatie Permission v6 with teams mode (one team = one tenant)
- Full DB redesign: positions, plan_slots, plan_assignments, absences, marketplace listings
- Configurable week offset per tenant
- Work plan builder with position+time slots, manager enforcement, week locking
- Unified absence system (single-day, multi-day, recurring) — no approval needed
- Shift marketplace (replacement requests, email blast to eligible employees)
- iCal subscription feed per employee (spatie/icalendar-generator)
- Position skill hierarchy: Zaucovaný → Uvádzač → Bufet (tier_group model)
- Analytics dashboard (hours, fill rates, absence patterns, marketplace stats)
- Three-layer logging (Spatie audit log, structured app log, security log)

**Planning docs:** `_planning/` folder, 18 files, on branch `planning/multi-tenant-rewrite`.

**Why:** 2026-06-02
