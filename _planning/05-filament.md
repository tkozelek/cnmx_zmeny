# 05 — Filament Admin Panel

## Two Panels

| Panel | Path | Audience | Context |
|---|---|---|---|
| `TenantAdminPanel` | `cinemaname.app.com/admin` | Cinema admins & managers | Tenant context (tenant DB active) |
| `PlatformPanel` | `app.com/platform` | Platform super-admins | Central DB, no tenant |

---

## Panel Registration

### Tenant Admin Panel

```php
// app/Providers/Filament/TenantAdminPanelProvider.php

use Filament\Panel;
use Filament\PanelProvider;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

class TenantAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tenant-admin')
            ->path('admin')
            ->login()
            ->authGuard('web')
            ->middleware([
                InitializeTenancyByDomain::class,
                SetSpatiePermissionTeamId::class,  // custom middleware
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureUserIsActive::class,
            ])
            ->resources([
                UserResource::class,
                PositionResource::class,
                WeekResource::class,
                PlanResource::class,
                UnavailabilityResource::class,
                ShiftResource::class,
                MediaResource::class,
                TenantSettingsResource::class,
            ])
            ->pages([
                Dashboard::class,
                WeekPlanPage::class,
                ReportsPage::class,
            ])
            ->widgets([
                WeekOverviewWidget::class,
                UnavailabilityWidget::class,
                PositionCoverageWidget::class,
                RecentActivityWidget::class,
            ])
            ->navigationGroups([
                'Planovanie',
                'Zamestnanci',
                'Nastavenia',
                'Prehľady',
            ])
            ->colors(['primary' => Color::Indigo]);
    }
}
```

### Platform Panel

```php
// app/Providers/Filament/PlatformPanelProvider.php

return $panel
    ->id('platform')
    ->path('platform')
    ->domain(config('app.central_domain'))  // only accessible on central domain
    ->login()
    ->resources([
        TenantResource::class,
        CentralUserResource::class,
    ])
    ->pages([
        PlatformDashboard::class,
    ]);
```

---

## Resources

### UserResource
**Navigation group:** Zamestnanci  
**Permissions:** `users.view` to list, `users.create`, `users.edit`, `users.delete`, `users.assign-roles`

Table columns:
- Name + lastname
- Email
- Role badge (Spatie role)
- Active toggle
- Last login
- Actions: Edit, Deactivate, Reset password, Delete

Form fields:
- Name, Lastname, Email
- Role select (from Spatie roles for current tenant)
- Is active toggle
- Rates subform (weekday/saturday/sunday/break rates) — collapsible

Create action: triggers invite email (new user gets a set-password link, not a registration form).

---

### PositionResource
**Navigation group:** Nastavenia  
**Permissions:** `positions.manage`

Table columns:
- Colour swatch + Name
- Is manager badge
- Active toggle
- Sort order (drag-to-reorder with `HasReorderableTable`)

Form fields:
- Name (string)
- Colour (ColorPicker)
- Is manager (Toggle) — only one position can have this; enforce in validation
- Is active (Toggle)
- Sort order (hidden, managed by reorder)

---

### WeekResource
**Navigation group:** Planovanie  
**Permissions:** `weeks.view` (list), `weeks.lock` (lock action), `weeks.create` (generate action)

Table columns:
- Date from → Date to
- Status badge: Open / Locked
- Day count
- Actions: View Plan, Lock/Unlock, Generate Plan, Export

Custom action `GenerateWeekAction`:
- Opens a modal asking for week date range (pre-filled based on `week_offset` setting)
- On submit, creates `Week` + 7 `Day` records
- If a `PlanTemplate` exists (see PlanResource), pre-populates `plan_slots`

Custom action `LockWeekAction`:
- Requires confirmation modal
- Sets `locked = true`, `locked_at`, `locked_by`
- After locking, no further changes to plan or unavailability are allowed

Custom action `ExportWeekAction`:
- Generates Excel + PDF (see reports section)

---

### PlanResource / WeekPlanPage
**Navigation group:** Planovanie  
**Permissions:** `plan.create`, `plan.assign`

This is the most complex part of the admin panel. Recommend a **custom Filament page** (`WeekPlanPage`) rather than a standard Resource, because the UI needs a grid layout (days as columns, positions as rows).

Layout concept:
```
Week: 12.6 – 18.6.2025          [Generate from template] [Lock week] [Export]

         | Thu 12.6 | Fri 13.6 | Sat 14.6 | Sun 15.6 | Mon 16.6 | Tue 17.6 | Wed 18.6 |
---------|----------|----------|----------|----------|----------|----------|----------|
Uvádzač  | [+ slot] | Martin K | —        | [+ slot] | ...      | ...      | ...      |
13:00    |          |          |          |          |          |          |          |
Uvádzač  | Jana N   | [+ slot] | Eva P    | [+ slot] | ...      | ...      | ...      |
15:00    |          |          |          |          |          |          |          |
Bufet    | Peter S  | ...      | ...      | ...      | ...      | ...      | ...      |
13:00    |          |          |          |          |          |          |          |
Vedúci   | Mgr.Novák| ...      | ...      | ...      | ...      | ...      | ...      |
```

Implementation:
- Each cell is a Livewire component inside the Filament page
- Clicking a cell opens an action modal with a user-select (filtered to employees with no unavailability on that day)
- Users with unavailability marked for that day are shown but greyed out (admin can still override)
- Locked weeks: cells are read-only

**Plan Template**: Admin can save the current week's slot structure as a template (position + times) to reuse for future weeks. Stored as a separate `plan_templates` + `plan_template_slots` table (or just JSON in `tenant_settings`).

---

### UnavailabilityResource
**Navigation group:** Zamestnanci  
**Permissions:** `unavailability.view-all`

Table columns:
- User name
- Date
- Reason (truncated)
- Submitted at
- Admin override badge (if admin manually added/edited)
- Actions: Delete (with `unavailability.delete-any` permission)

Filters:
- By user
- By week (select from weeks list)
- By date range
- Show only conflicts (unavailability overlaps a plan assignment)

Admin can also create unavailability entries for any user from this page, bypassing the deadline.

---

### ShiftResource
**Navigation group:** Zamestnanci  
**Permissions:** `reports.hours`

Standard CRUD for shift records. Inline editing of start/end/break.

Table:
- User, Date, Position, Start, End, Break, Calculated hours
- Bulk actions: recalculate hours for a date range

---

### TenantSettingsResource (single page)
**Navigation group:** Nastavenia  
**Permissions:** `settings.edit`

A single Filament `EditRecord` page (no list — one row in `tenant_settings`).

Fields:
- Week offset (Select: Monday–Sunday)
- Week lookahead (Number: 1–12)
- Unavailability deadline (Number: hours before day, e.g. 48)
- Allow self-registration (Toggle)
- Timezone (Select from PHP timezone list)
- Locale (Select: sk, cs, en)

---

### MediaResource
**Navigation group:** Planovanie  
**Permissions:** `media.upload`, `media.manage`

Table:
- Filename, Week association, Uploaded by, Size, Is public toggle
- Download action, Delete action

Upload action on Week form / separate upload page.

---

## Dashboard Page

**Widgets on dashboard:**

### `WeekOverviewWidget`
Shows current open week:
- Date range
- Plan completion % (slots filled vs total slots)
- Days with ≥1 unfilled slot highlighted in red

### `UnavailabilityWidget`
Table of this week's unavailability submissions. Click to jump to `UnavailabilityResource`.

### `PositionCoverageWidget`
Per-day coverage bars: each position shown as a progress bar (assigned/required).

### `RecentActivityWidget`
Last 10 activity log entries (from `spatie/laravel-activitylog`). Shows who changed what.

---

## Filament Actions — Key Custom Actions

### `GeneratePlanFromTemplateAction`
On `WeekResource` table row. Opens modal:
1. Select template (or "blank")
2. Confirm → creates `plan_slots` for all 7 days based on template
3. Notifies admin with a summary: "Plan created: 14 slots across 7 days"

### `AssignUserToSlotAction`
On `WeekPlanPage` grid cells. Opens modal:
- User select (Livewire-powered search, shows unavailability warning)
- Optional notes
- Saves `plan_assignment`

### `LockWeekAction`
On `WeekResource` table row:
- Confirmation modal with "Type the week dates to confirm"
- Sets locked status
- Fires `WeekLocked` event → notifies all assigned employees by email

### `ExportPlanAction`
On `WeekResource`:
- Choose format: Excel / PDF
- Choose content: plan only / hours summary / both
- Downloads file

---

## Authorization in Filament

```php
// In each Resource class:
public static function canViewAny(): bool
{
    return auth()->user()->can('weeks.view');
}

public static function canCreate(): bool
{
    return auth()->user()->can('weeks.create');
}

// etc.
```

Or use Filament's policy integration:
```php
public static string $model = Week::class;
// Filament automatically calls Week policy methods
```

Filament respects the `WeekPolicy`, `PlanSlotPolicy`, etc. registered in `AuthServiceProvider`.

---

## Navigation Structure

```
Dashboard

Planovanie
  ├── Týždne (WeekResource)
  ├── Plán práce (WeekPlanPage)
  └── Súbory (MediaResource)

Zamestnanci
  ├── Zamestnanci (UserResource)
  ├── Nedostupnosť (UnavailabilityResource)
  └── Odpracované hodiny (ShiftResource)

Prehľady
  └── Reporty (ReportsPage)

Nastavenia
  ├── Pozície (PositionResource)
  └── Nastavenia kina (TenantSettingsResource)
```
