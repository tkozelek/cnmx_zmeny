# 18 — Recommended Packages

All packages below are verified as actively maintained in 2025/2026. Grouped by concern, with a priority rating and specific notes on how each fits this app.

---

## Drag & Drop — Work Plan (WeekPlanPage)

This is the most interaction-heavy part of the whole app — assigning employees to position slots across a 7-day grid. Getting the DnD right here has a big UX payoff.

### Option A: `saade/filament-fullcalendar` (recommended for the plan grid)

~400 stars · Filament v3 · [github](https://github.com/saade/filament-fullcalendar)

Wraps FullCalendar.io as a Filament widget. The **timeGrid** view maps directly onto the shift plan: columns = days, rows = time slots, events = position assignments. Drag an employee card from an "unassigned" sidebar onto a time slot to assign them.

```
composer require saade/filament-fullcalendar
```

What you get for free from FullCalendar:
- Drag-and-drop events between day columns and time rows
- Resize events to change end time
- Click empty slot → opens create modal
- Click existing event → opens edit/reassign modal
- Constraint rules: prevent dropping onto locked days
- Event colour by position (maps to `positions.color`)
- Locale support (`sk` locale available)

Integration approach for the WeekPlanPage:
```php
// Filament widget backed by plan_slots + plan_assignments
// fetchEvents() returns all slots for the week as FullCalendar event objects
// eventDrop() fires a Livewire action → updates plan_assignment.user_id
// eventResize() fires a Livewire action → updates plan_slot end_time
```

### Option B: `guava/calendar` v1.x (resource grid view)

~300 stars · Filament v3 · [github](https://github.com/GuavaCZ/calendar)

Uses `@event-calendar/core` (lightweight open-source FullCalendar alternative). The **ResourceTimeGridWeek** view is unique here: resources (employees) are columns, time is the Y-axis. This flips the mental model — you see each employee's week at a glance rather than each day's positions. Useful as a secondary "employee overview" tab on the plan page.

```
composer require guava/calendar:"^1.0"
```

**Recommendation:** Use `saade/filament-fullcalendar` as the primary plan builder (position-centric view) and `guava/calendar` as an optional "Employee view" tab (shows each person's assigned slots side-by-side).

---

### Drag & Drop for non-calendar lists: `@shopify/draggable` via Alpine.js

For things like reordering the position list (`PositionResource`), reordering template slots in `PlanTemplateResource`, or a Kanban-style "unassigned employees" sidebar next to the calendar.

```js
// package.json
"@shopify/draggable": "^1.0.0-beta.14"
```

Or use **SortableJS** (lighter, more popular):
```js
"sortablejs": "^1.15.0"
```

Wire SortableJS into Alpine.js with a tiny directive:
```js
// resources/js/sort-directive.js
Alpine.directive('sortable', (el, { expression }, { evaluate, cleanup }) => {
    const sortable = Sortable.create(el, {
        animation: 150,
        onEnd: ({ oldIndex, newIndex }) => {
            evaluate(expression)  // dispatch to Livewire
        }
    });
    cleanup(() => sortable.destroy());
});
```

Filament already uses SortableJS internally for its reorderable tables — so it is already bundled in your Filament install. You can reference it directly without adding a new npm dependency.

### Drag & Drop for Filament Tables: built-in `HasReorderableTable`

Filament v3 ships `ReorderAction` and `HasReorderableTable` natively. No extra package needed for ordered lists (position sort order, template slot order).

```php
// PositionResource table
->reorderable('sort_order')
->defaultSort('sort_order')
```

---

## Filament Plugins

### `bezhansalleh/filament-shield`
~2800 stars · [github](https://github.com/bezhanSalleh/filament-shield)

Auto-generates Spatie permission policies for every Filament Resource/Page/Widget. Provides a built-in "Roles & Permissions" resource so admin can manage role→permission assignments from the panel UI instead of via seeders. Supports super-admin, multi-panel, and teams mode.

```
composer require bezhansalleh/filament-shield
php artisan shield:generate --all
```

**Why this matters here:** Without it you'd manually seed and maintain the 20+ permissions from doc 04. Shield generates them from your Resource class names and lets you edit them at runtime.

---

### `tomatophp/filament-tenancy`
~57 stars · [github](https://github.com/tomatophp/filament-tenancy)

Specific bridge between `stancl/tenancy ^3.8` and Filament. Provides tenant creation/management from the central panel, tenant impersonation (log into a cinema's panel as their admin), and middleware helpers that correctly initialize tenancy inside Filament's request lifecycle.

```
composer require tomatophp/filament-tenancy
```

---

### `pxlrbt/filament-excel`
Filament Core Team member · [github](https://github.com/pxlrbt/filament-excel)

Adds a bulk action and header action to any Filament table to export the current (filtered) rows as Excel. Uses `maatwebsite/excel` under the hood — which you already have. Handles column selection modal, custom formatters.

```
composer require pxlrbt/filament-excel
```

Replaces the need to manually wire `Excel::download()` on every resource — the bulk action handles it.

---

### `pxlrbt/filament-activity-log`
Same author · [github](https://github.com/pxlrbt/filament-activity-log)

Adds a `RelationManager` to any Filament resource that shows the `spatie/laravel-activitylog` history for that record inline. In `WeekResource`: see the full lock/unlock/plan-change history. In `UserResource`: see role changes, assignments. Zero extra queries — piggybacks on the existing activity log table.

```
composer require pxlrbt/filament-activity-log
```

---

### `filament/spatie-laravel-settings-plugin` (official)

Official Filament plugin. Turns a `spatie/laravel-settings` class into a Filament `SettingsPage` with form components. Use for the `TenantSettingsPage` — much cleaner than a custom single-row EditRecord page.

```
composer require filament/spatie-laravel-settings-plugin
composer require spatie/laravel-settings
```

The settings class becomes typed PHP:
```php
class TenantSettings extends Settings
{
    public int    $week_offset           = 3;
    public int    $week_lookahead        = 5;
    public int    $absence_hours         = 48;
    public bool   $allow_self_registration = false;
    public string $timezone             = 'Europe/Bratislava';

    public static function group(): string { return 'tenant'; }
}
```

---

### `jeffgreco13/filament-breezy` (use v2 for Filament 3)
[github](https://github.com/jeffgreco13/filament-breezy)

Adds a full profile page (name, email, password change) and **two-factor authentication (TOTP)** with recovery codes to any Filament panel. For a cinema app handling payroll data, 2FA for the admin role is a meaningful security addition.

```
composer require jeffgreco13/filament-breezy:"^2.0"
```

---

### `opcodesio/log-viewer`
~3000 stars · [github](https://github.com/opcodesio/log-viewer)

The best standalone log viewer for Laravel. Accessible at `/log-viewer` (behind auth middleware). Supports multi-file, search, level filters, JSON structured logs. Can be embedded inside Filament as an iframe page or accessed directly.

```
composer require opcodesio/log-viewer
```

---

### `awcodes/filament-curator`
~300 stars · [github](https://github.com/awcodes/filament-curator)

Full media library manager for Filament — global media picker modal, gallery view, alt text, per-collection filtering. Better than the Spatie Media Library plugin for managing cinema assets (logos, banners, schedule PDFs). The picker modal lets admin attach an existing file to a week without re-uploading.

```
composer require awcodes/filament-curator
```

---

## Spatie Packages

### `spatie/laravel-settings`
[docs](https://github.com/spatie/laravel-settings)

Typed settings stored in DB. Replaces the `tenant_settings` table + plain Eloquent model with a proper typed PHP class. Settings are auto-cached, cast to native types, and editable via Filament's settings plugin (above).

```
composer require spatie/laravel-settings
```

---

### `spatie/laravel-data`
~1800 stars · [docs](https://spatie.be/docs/laravel-data)

Strongly-typed Data Transfer Objects that also work as Form Request validators, Eloquent casters, and API resources. Eliminates repetitive `$request->validated()` arrays everywhere.

Example for shift creation:
```php
class CreateShiftData extends Data
{
    public function __construct(
        public readonly int      $user_id,
        public readonly Carbon   $date,
        public readonly string   $start,
        public readonly string   $end,
        public readonly int      $break_minutes = 0,
    ) {}
}
// Usage: CreateShiftData::from($request) — validates + casts in one step
```

```
composer require spatie/laravel-data
```

---

### `spatie/icalendar-generator`
[docs](https://github.com/spatie/icalendar-generator)

Fluent iCal builder. Handles RFC 5545 line-folding, timezone blocks, RRULE for recurring events, and attendees. **Replaces the hand-rolled `ICalBuilder` service from doc 13.**

```php
$calendar = Calendar::create('Moje zmeny')
    ->event(
        Event::create()
            ->name('Uvádzač — ' . tenant('name'))
            ->startsAt($dtStart)
            ->endsAt($dtEnd)
            ->uniqueIdentifier("assignment-{$assignment->id}@cnmx")
    )
    ->get();
```

```
composer require spatie/icalendar-generator
```

---

### `spatie/laravel-backup`
~6000 stars

Automated DB + file backups to any filesystem disk (local, S3, Dropbox). Sends notifications on failure. Run per-tenant by iterating tenants in a custom command. Requires PHP 8.4+ for the latest version.

```
composer require spatie/laravel-backup
```

---

### `spatie/laravel-schedule-monitor`
~990 stars

Monitors scheduled commands — stores execution history in DB, sends alerts when a command doesn't run on time (e.g. the weekly reminder email job). Integrates with Oh Dear or custom webhooks.

```
composer require spatie/laravel-schedule-monitor
```

---

### `spatie/laravel-pdf`
[docs](https://spatie.be/docs/laravel-pdf)

Generates PDFs from Blade views using headless Chromium (via Browsershot). Better quality than DomPDF for complex layouts like the position coverage heatmap and styled shift rosters. Requires Chrome/Puppeteer on the server.

```
composer require spatie/laravel-pdf
```

If server Chrome is not available: keep `barryvdh/laravel-dompdf` for simple PDFs and use `spatie/laravel-pdf` only for the styled reports.

---

## Laravel Core / Infrastructure

### `laravel/horizon`
First-party · [docs](https://laravel.com/docs/12.x/horizon)

Redis queue dashboard. Job throughput, runtime metrics, failure tracking, retry from UI. **Required for production if using Redis queues.** Tag jobs with the tenant ID for per-cinema queue visibility:

```php
// In your notification jobs:
public function tags(): array
{
    return ['tenant:' . tenant('id'), 'notification'];
}
```

```
composer require laravel/horizon
```

---

### `laravel/reverb`
First-party WebSocket server · [docs](https://laravel.com/docs/12.x/reverb)

Self-hosted WebSockets — no Pusher account needed. One Reverb instance can serve **multiple app IDs**, making it naturally multi-tenant. Use for:
- Real-time "new listing posted" toast on the marketplace page
- Live plan update badge ("Plan was just updated") when an admin modifies the week
- Admin dashboard live counters

```
composer require laravel/reverb
php artisan reverb:install
```

---

### `laravel/telescope`
First-party · dev only

Inspects requests, queries, jobs, mail, notifications, exceptions, gate checks, cache operations. Essential during development — especially useful for catching N+1 queries in the plan grid and verifying Livewire component behaviour.

```
composer require laravel/telescope --dev
```

Restrict to local env in `TelescopeServiceProvider`:
```php
public function register(): void
{
    Telescope::night();
    $this->hideSensitiveRequestDetails();

    Telescope::filter(fn (IncomingEntry $entry) => $this->app->isLocal());
}
```

---

### `sentry/sentry-laravel`
[docs](https://docs.sentry.io/platforms/php/guides/laravel/)

Production exception tracking with stack traces, user context, breadcrumbs, and performance tracing. Free tier covers small apps. Integrates with Horizon for job failure context.

```
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=https://...
```

---

## Livewire / Frontend

### `robsontenorio/mary` (MaryUI)
~1500 stars · [docs](https://mary-ui.com)

50+ Blade/Livewire components built on daisyUI + Tailwind. The employee-facing portal (`AbsencePage`, `CalendarWeek`, `HoursView`) is the right place for MaryUI — it gives you polished stats cards, tables, modals, date pickers, and toasts without writing any CSS.

```
composer require robsontenorio/mary
php artisan mary:install
```

Key components for this app:
- `<x-mary-table>` — absence list, hours view
- `<x-mary-stat>` — summary cards on dashboard
- `<x-mary-datetime>` — date/time picker for absence form
- `<x-mary-modal>` — confirmation dialogs
- `<x-mary-toast>` — success/error feedback
- `<x-mary-calendar>` — lightweight month picker

---

### `wire-elements/modal`
~1500 stars · [github](https://github.com/wire-elements/modal)

The standard modal manager for Livewire v3. Stacked modals, slideovers, data passing between components. Use for the marketplace "claim shift" modal, absence submission confirmation, and any multi-step forms in the employee portal.

```
composer require wire-elements/modal
```

---

### `masmerise/livewire-toaster`
[github](https://github.com/masmerise/livewire-toaster)

Push toast notifications from any Livewire component or from server-side events. Zero Alpine.js config — registers via Livewire's own mechanism.

```php
Toaster::success('Neprítomnosť bola nahlásená.');
Toaster::error('Termín na nahlásenie uplynul.');
```

```
composer require masmerise/livewire-toaster
```

---

## Email / Notifications

### `resend/resend-laravel`
[github](https://github.com/resend/resend-laravel)

First-class Resend integration as a Laravel mailer driver. Better deliverability than raw SMTP, free tier (3000 emails/month), built-in bounce and complaint handling via webhooks. One-line config swap:
```dotenv
MAIL_MAILER=resend
RESEND_KEY=re_...
```

```
composer require resend/resend-laravel
```

---

### `laravel-notification-channels/webpush`
[github](https://github.com/laravel-notification-channels/webpush)

Browser push notifications without a native app. Works on Android Chrome, iOS Safari 16.4+, desktop browsers. For time-sensitive marketplace alerts ("Niekto hľadá záskok na sobotu!"). Note: requires custom middleware to switch tenant DB connection during subscription registration with stancl/tenancy — document this as a known integration step.

```
composer require laravel-notification-channels/webpush
```

---

## Developer Experience

### `enlightn/enlightn`
[docs](https://www.laravel-enlightn.com)

Automated security and performance audit — 130+ checks for exposed .env, missing HTTPS, weak session config, open redirects, unprotected routes, slow queries, missing indexes. Run before each major release.

```
composer require enlightn/enlightn --dev
php artisan enlightn
```

---

### `rector/rector` + `driftingly/rector-laravel`
Automated code modernization. Run during Laravel version upgrades to auto-fix deprecated APIs (e.g. when upgrading from 12→13).

```
composer require rector/rector --dev
composer require driftingly/rector-laravel --dev
```

---

## Priority Summary

### Install before writing a single line of app code
```
filament/spatie-laravel-settings-plugin
bezhansalleh/filament-shield
tomatophp/filament-tenancy
spatie/laravel-settings
spatie/laravel-data
laravel/horizon
laravel/reverb
laravel/telescope (--dev)
```

### Install when building the plan UI
```
saade/filament-fullcalendar     ← primary plan grid
guava/calendar:"^1.x"           ← employee overview tab
# SortableJS already bundled in Filament — no install needed for reorderable tables
```

### Install when building the employee portal
```
robsontenorio/mary
wire-elements/modal
masmerise/livewire-toaster
spatie/icalendar-generator
```

### Install when building reports
```
pxlrbt/filament-excel
pxlrbt/filament-activity-log
spatie/laravel-pdf              ← or keep barryvdh/laravel-dompdf if no Chrome on server
```

### Install when hardening for production
```
opcodesio/log-viewer
sentry/sentry-laravel
spatie/laravel-backup
spatie/laravel-schedule-monitor
enlightn/enlightn (--dev)
jeffgreco13/filament-breezy     ← 2FA for admins
resend/resend-laravel
```

---

## What to Skip (and Why)

| Package | Reason to skip |
|---|---|
| `consoletvs/charts` (currently installed) | Abandoned — replace with Filament's built-in Chart widgets |
| `barryvdh/laravel-ide-helper` | Keep only in `--dev`, never in production autoload |
| Custom `ICalBuilder` service (doc 13) | Replace entirely with `spatie/icalendar-generator` |
| `Illuminate\Support\Facades\DB` raw queries | Use Eloquent or `spatie/laravel-query-builder` |
| Any `jQuery` dependency | Alpine.js covers everything jQuery was used for |
