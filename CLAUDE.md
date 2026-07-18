# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository state: two things live here at once

1. **`app/`, `routes/`, `resources/`, `database/`** — the existing, live Laravel app ("CNMX Zmeny"), a shift-scheduling system for cinema staff. This is legacy code slated for a full rewrite, not yet replaced.
2. **`_planning/`** — a complete design spec for a ground-up multi-tenant rewrite of the same app, written on this branch (`planning/multi-tenant-rewrite`).

**Do not implement the rewrite design on this branch.** `_planning/README.md` says so explicitly: this branch is planning-only; each rewrite phase gets its own fresh feature branch off `master`, per `_planning/11-implementation-order.md`. If asked to build something from `_planning/`, confirm which phase/branch first rather than committing rewrite code here.

When a task touches the current app, read `_planning/01-current-state.md` first — it lists what's intentionally being kept vs. scrapped, which explains why parts of the current code look primitive (e.g., integer role IDs, no policies) even though better packages are already installed.

## Commands

No test suite, linter config, or static-analysis config exists yet in this repo (no `tests/`, `phpunit.xml`, `pint.json`, or `phpstan.neon`) — don't assume `php artisan test` or `vendor/bin/pint` will find anything to run.

```
composer install              # PHP deps
npm install                   # JS deps
npm run dev                   # Vite dev server
npm run build                 # Vite production build
php artisan serve             # local server
php artisan migrate           # run migrations
```

Ad-hoc app commands (`app/Console/Commands/`): `ClearAllCaches`, `ClearWeeksAfterYear`.

## Testing philosophy

There is no test suite yet — when adding tests, write only feature tests for **core user-facing flows**, not exhaustive coverage. One happy-path (+ one meaningful failure case where it matters, e.g. a locked week) per flow is enough. Concretely, "core" means things like:

- A user signing up for / being removed from a day (`DayUserController::toggleUser`/`destroy`)
- Marking, ending, and deleting an absence/holiday (`HolidayController`)
- Admin accepting (role change from unverified), editing, and deleting a user (`AdminUserController`, `AdminUserEditController`)
- Locking a week and confirming it becomes read-only (`CalendarController::lock`)

Do **not** write tests for things a reader can already verify from the code: getters/casts, route registration, validation-rule presence, framework/package behavior, or trivial CRUD with no business rule attached. If a test doesn't assert a behavior someone could get wrong, skip it.

## Current app architecture (`app/`)

Laravel 12, but wired with the pre-Laravel-11 structure: `app/Http/Kernel.php` (not `bootstrap/app.php`) defines middleware groups/aliases, and `app/Exceptions/Handler.php` handles exceptions.

**Auth & roles** — no Spatie Permission wiring despite it being in `composer.json` (installed but unused — planned for the rewrite). Roles are a plain integer FK (`users.id_role` → `roles` table), defined in `config/constants.php` (`neovereny`=1 unverified, `brigadnik`=2 worker, `admin`=3, `zablokovany`=4 blocked). Two custom middleware aliases gate routes:
- `allowed` (`EnsureUserIsAllowed`) — lets `admin`/`brigadnik` through, logs out and redirects blocked/unverified users with a Slovak flash message.
- `role:{n}` (`EnsureUserHasRole`) — hard role-ID gate, e.g. `role:3` for admin-only routes.

**Tenancy** — `config/tenancy.php` and `app/Models/Tenant.php` reference `stancl/tenancy` classes, but the package is **not** in `composer.json`/`composer.lock` — this is dead config from an earlier, abandoned attempt, not a working feature. The rewrite plan (`_planning/03-multitenancy.md`, `_planning/README.md` "Key Decisions") replaces this entirely with single-database Spatie Permission teams (one team = one tenant), not Stancl Tenancy.

**Week/day scheduling model** — the core domain concept. `WeekService` generates `Week` + `Day` rows (a `Week` has 7 `Day`s); the work week starts next Thursday (see `WeekService::generateWeek()`), not Monday. `Week.locked` freezes a week from further edits. `Day` ↔ `User` is a many-to-many pivot (`user_days`, only stores a free-text `popis`/description — no position or time slot, called out in `_planning/01-current-state.md` as too flat and slated for replacement by a proper `work_plan_assignments` model). `CalendarController` + `DayUserController` drive the employee-facing week view; `AdminController`/`AdminUserController`/`AdminUserEditController` drive admin management, all gated behind `role:3`.

**Other domain pieces**: `Holiday`/`HolidayService` (unavailability marking — planned rename to `UnavailabilityService` with deadline enforcement), `Shift`/`Rate` (hours + pay-rate tracking), `File`/`FileService`/`FileUploadController` (per-week file uploads, reused via Excel export), `Bug`/`BugReportController` (in-app bug reporting — planned for removal, out of scope for a scheduling app).

**Frontend**: server-rendered Blade views under `resources/views/` (one directory per feature area — `admin`, `calendar`, `holiday`, `hours`, `roles`, `users`, etc.) plus one Livewire component (`app/Livewire/UserTable.php`) for the admin user table. Tailwind + Flowbite for styling, Vite for the asset pipeline (there's also a lingering `webpack.mix.js`/`laravel-mix` from an older toolchain — Vite is the active one per `package.json`'s `dev`/`build` scripts).

**Locale**: app is Slovak-first (`config/app.php` locale `sk`, fallback `en`). Validation messages, flash messages, and route URIs (e.g. `/prihlasenie`, `/registracia`, `/dovolenka`) are in Slovak — keep new user-facing strings consistent with that.

## The planned rewrite (`_planning/`)

Read `_planning/README.md` first for the index and key decisions, then the numbered doc for the specific area you're touching. High-level shape of the target architecture (not yet implemented):

- **Single database**, multi-tenant via **Spatie Permission teams** (one team = one cinema), scoped through Filament's native `->tenant()` — no separate tenant databases.
- **Filament v3** for all admin/management work; **Livewire v3** stays for the employee-facing calendar/unavailability/hours UI.
- Full DB redesign: no `mediumInt` PKs, no integer role magic numbers, positions become a first-class team-scoped model, and the flat `user_days` pivot is replaced by a template-driven work-plan/assignment system.
- Phased implementation order is fully specified in `_planning/11-implementation-order.md` (Phase 0 bootstrap → Phase 11 data migration from the old app) — each phase is its own branch off `master`, merged to `development`, with its own definition of done (tests, PHPStan level 6, Pint, PR review).
