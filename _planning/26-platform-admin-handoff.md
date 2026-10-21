# 26 — Platform super admin, per-cinema modules, auth forms — handoff

> Date: 2026-10-21
> Branch: `feature/bootstrap` — commits `f194213`, `ca8bbe4` (pushed)
> Status: implemented, 229 tests green; panel not yet clicked through in a browser

---

## 1. What exists now

| Area | Summary |
|---|---|
| Platform panel | Filament v5 panel at `/platform`, super admins only, mandatory TOTP |
| Super admin | `users.is_super_admin` flag; acts in any cinema with head-manager powers, invisible to tenants |
| Modules | Per-cinema Pennant features: `Rozpis`, `RozpisAi` (off by default) |
| Rozpis | Restored from `654e068` (positions, slots, fairness, publish, notifications, export, Gemini AI) behind `Rozpis` |
| Audit | Panel writes → `activity_log` (`log_name = platform`), redacted |
| Auth UI | Login/register redesigned (spacing, ≥44px targets, hints, busy button, subtle glow background) |
| Absences | Both tables default to relevance order (running/upcoming → ended → cancelled) |

Dependency changes: Livewire 3 → **4.4**, rappasoft tables 3.9 → **3.10**, added `filament/filament ^5.10`,
`laravel/pennant`, `google-gemini-php/laravel`, `spatie/laravel-activitylog`, npm `sortablejs`.

---

## 2. Super admin — how it works

- **Flag, not a role.** Spatie teams mode keys `model_has_roles` by `team_id`, so a cross-cinema role is
  impossible. `is_super_admin` is not fillable.
- **Flag alone grants nothing.** `App\Services\SuperAdminSession::isActive()` requires: flag + active +
  TOTP secret set + signed in on the separate `platform` guard (`config/auth.php`). A login at
  `/prihlasenie` never unlocks cross-cinema access.
- **Entering a cinema:** panel → Kiná → "Otvoriť kino" (`SuperAdminSession::enter()` logs the same account
  into `web` and sets `current_team_id`), or the normal team switcher, which lists every cinema for them.
- **Bypass point:** `User::hasPermissionInTeam()` returns true for an active super admin. Deliberately
  not `Gate::before` — policy business rules (last head manager, absence ownership, week locks) still apply.
- **Membership API split:** `approvedTeams()` / `isApprovedIn()` stay pure membership (used for people
  lists); `accessibleTeams()` / `canEnter()` add "all cinemas" for an active super admin.
- **Kept off tenant data:** excluded in `UsersDataTable`, `Team::pendingUsers()`, `Team::activeHoldersOf()`;
  `AssignmentPolicy::create` denies non-member targets; `StoreAbsenceRequest::authorize` now checks
  `AbsencePolicy::create` (was `return true`); `PrunePersonalData` skips super admins.
- **`User::$guard_name = 'web'`** is required — inside the panel the default guard is `platform` and
  Spatie would look roles up under it.
- Nav: "Platforma" link via `Gate::define('accessPlatform')`; Pulse gate also allows active super admins.

Managing super admins:
```
php artisan app:super-admin someone@example.com            # grant (account must be active + verified)
php artisan app:super-admin someone@example.com --revoke   # refuses the last super admin
```
In the panel (Používatelia → edit) grant / revoke / reset 2FA ask for the acting admin's password;
self-revoke and revoking the last super admin are refused (`SuperAdminRoster`).

---

## 3. The `/platform` panel

Provider: `app/Providers/Filament/PlatformPanelProvider.php` (registered manually in `config/app.php`).
Code: `app/Filament/Platform/**`. No Filament tenancy — the registrar has no team, so `BelongsToTeam`
is unscoped and the panel sees everything. Resources set `$shouldSkipAuthorization = true`
(tenant policies assume a current team; the panel itself is the gate via `User::canAccessPanel`).

| Resource | Contents |
|---|---|
| Kiná (`TeamResource`) | name/slug/registration flag; all `team_settings` (rozpis fairness + quick times only when module on); module toggles; relation managers: Členovia, Pozície (only with rozpis), Zamknuté týždne; "Otvoriť kino"; delete requires typing the name |
| Používatelia (`UserResource`) | global list + filters; edit account; memberships relation manager; super admin / 2FA actions; delete requires typing the e-mail (hidden for super admins); no create (accounts come from registration / manager invite) |
| Absencie, Zápisy | read + delete across all cinemas, team/date filters |
| Audit (`ActivityResource`) | read-only `activity_log` |
| Roly a oprávnenia | read-only matrix from `RolePermissionSeeder` |
| Dashboard | `PlatformStats` widget |

Supporting services: `TeamMembershipService` (add/approve/change role/remove, brackets the Spatie
registrar, refuses orphaning the last head manager), `PlatformAudit`, `SuperAdminRoster`.
Shared redaction: `App\Support\AuditRedactor` (used by `Loggable` too; MFA columns never logged).
`InitialsAvatarProvider` replaces ui-avatars.com (CSP + privacy).

---

## 4. Per-cinema modules

- Enum `App\Enums\TeamFeature` (`Rozpis`, `RozpisAi` requires `Rozpis`). Check with `Team::hasFeature()`,
  which honours `requires()`; Pennant alone only knows the stored flag.
- Pennant scope = `app(Team::class)` (see `AppServiceProvider::scopeFeaturesToTheCurrentTeam`);
  with no team bound everything reads as off. In the panel always use `Feature::for($team)`.
- Rozpis routes sit in `EnsureFeaturesAreActive::using(TeamFeature::Rozpis)` → 404 when off; the
  middleware is also persistent Livewire middleware.
- AI additionally needs `GEMINI_API_KEY` (`AiRozpisSuggestionService::enabled(Team)`).
- **Adding a module:** new `TeamFeature` case + route group middleware + `hasFeature` checks in views/policies.
  It appears in the panel automatically.

---

## 5. Rozpis restore notes

- Migration `2026_10_21_100000_restore_rozpis_tables.php` recreates the schema dropped by
  `2026_10_06_202403`; verified up/down on SQLite and local MySQL.
- Deviations from `654e068`: `RozpisDay::slots()` → `daySlots()` (Livewire 4 reserves `$slots`);
  `AiRozpisSuggestionService::enabled()` takes a `Team`; no `@php` left in restored views;
  copy converted to "ty"; flatpickr via `await loadFlatpickr()`; `note` not written to activity log.
- Skipped (optional in original): position on DayCard/WeeklyScheduleExport, `position_id` on
  `StoreAssignmentRequest`, SampleAssignmentsSeeder layout.
- Privacy page updated (rozpis history, platform audit trail — 12 months; Gemini as processor);
  `PrunePersonalData::pruneOldActivity()` enforces it.

---

## 6. Auth forms

- Components: `x-auth-card` (title, subtitle, `wide`, `footer` slot), `x-submit-button` (busy label,
  keeps width, re-enables on bfcache), `x-form-input` (logic moved to the class; new `hint` prop).
- `.field` / `.field-row` in `resources/css/app.css`: subgrid rows keep side-by-side labels/controls/errors
  aligned. Any new custom control wrapper must use `.field__control` to stay in row 3.
- `.auth-backdrop`: two radial-gradient glows with eased stops (a linear fade banded), slow drift,
  disabled under reduced motion.
- Register hides the Kino picker when only one cinema is active; the missing-cinema error is now a
  `team_id` field error (was an undisplayed `session('error')`).

---

## 7. Deploy checklist

1. `composer install` (post-autoload-dump runs `filament:upgrade` → publishes panel assets)
2. `php artisan migrate` (features table, user MFA/super-admin columns, rozpis restore + permission sync)
3. `npm ci && npm run build`
4. `php artisan app:super-admin <you>` → log in at `/platform`, set up TOTP
5. Optional `.env`: `GEMINI_API_KEY`, `GEMINI_MODEL` (default `gemini-2.5-flash`)
6. Scheduler cron must run (`app:prune-personal-data` now also prunes `activity_log`)
7. Livewire 4 endpoints are `/livewire-{hash}/…` — check any proxy/CDN rule that matched `/livewire/`

---

## 8. Open items

- **Browser walkthrough of `/platform`** not done — needs a local super admin with TOTP (creates dev data).
- rappasoft 3.10 was one day old at install; watch the users/absences tables.
- PHPStan without Larastan is noise on Eloquent magic; install Larastan before relying on it.
- `config/database.php:62` uses `PDO::MYSQL_ATTR_SSL_CA` (deprecated in PHP 8.5) — every test reports it.
- `CLAUDE.md` still describes the old integer-role setup and no Filament; worth a refresh.
- Panel cannot create users (by design so far).
- Week-start-lock rule now lives in `App\Rules\WeekStartDayChangeable`, shared by the settings
  request and the panel.

## 9. Tests added

`SuperAdminAccessTest`, `PlatformPanelTest`, `RozpisFeatureFlagTest`, restored Rozpis/Position/Publish/AI/
Fairness tests, week-start-lock case in `TeamSettingTest`, relevance order in `AbsenceTest`.
Run all: `php artisan test --compact`.
