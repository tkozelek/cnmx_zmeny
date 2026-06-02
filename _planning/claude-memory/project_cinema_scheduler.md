---
name: project_cinema_scheduler
description: cnmx_zmeny cinema shift scheduler — current state and rewrite goals
metadata: 
  node_type: memory
  type: project
  originSessionId: acc11c85-ddb3-4b6c-b692-d7b47f339774
---

Cinema shift scheduler app. Work week is Thursday→Wednesday (configurable offset per tenant). Staff sign up for work days, admins lock weeks, export schedules.

**Current master:** Laravel 12, Livewire 3, custom integer roles, Stancl Tenancy v3 half-wired, Spatie Permission installed but unused, flat `user_days` pivot (no positions/times), 94 known security vulnerabilities.

**Rewrite planned on branch:** `planning/multi-tenant-rewrite` — 18 planning docs under `_planning/`.

**Rewrite goals:**
- Single-DB multi-tenancy via Spatie teams
- Filament v3 admin panel + Livewire employee portal (MaryUI)
- Full DB redesign: positions, plan_slots, plan_assignments, absences, marketplace_listings
- Work plan builder with position+time slots, drag-and-drop assignment, week locking
- Unified absence system (single-day / multi-day / recurring), no approval flow
- Shift marketplace: replacement requests, email blast to eligible employees
- iCal subscription feed per employee
- Position skill hierarchy (Zaucovaný → Uvádzač → Bufet, tier_group model)
- Analytics dashboard, three-layer logging, reports/exports
