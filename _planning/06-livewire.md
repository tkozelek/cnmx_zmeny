# 06 — Livewire Frontend (Employee-Facing UI)

The employee-facing app lives under the tenant domain at the root path. It is intentionally lightweight — employees see their schedule, mark unavailability, and view their own hours. Everything else happens in the Filament admin panel.

---

## Page Structure

```
/              → CalendarPage (current week plan)
/week/{week}   → CalendarPage (specific week)
/nedostupnost  → UnavailabilityPage
/hodiny        → HoursPage
/nastavenia    → SettingsPage (password change, profile)
/prihlasenie   → Login (standard Blade, no Livewire needed)
```

---

## Layout

Use a simple Blade layout with a top navigation bar. No Livewire needed for the shell itself — Livewire components are mounted inside Blade pages.

```
layouts/app.blade.php
  ├── _nav.blade.php  (navigation: home, unavailability, hours, logout)
  └── @yield('content') → Livewire component
```

---

## Components

### `CalendarWeek` (main view)

**File:** `app/Livewire/CalendarWeek.php`

Displays the work plan for a week as a read-only grid. Employees see who is working which position on which day. Their own assignments are highlighted.

Properties:
- `$week` — bound from route model binding
- `$currentUserId` — logged-in user

Data loaded:
- All `plan_slots` for the week, eager-loaded with `position`, `day`, `plan_assignments.user`
- All `unavailabilities` for the week (to show "off" badges on non-assigned days)

Computed:
- `slotsByDay()` — returns slots grouped by `day.date` → `position.sort_order` → slot list

Rendering:
```
[Thu 12.6]  [Fri 13.6]  [Sat 14.6]  ...
Uvádzač 13:00  Martin K     Jana N      ...
Bufet 13:00    Peter S      Tomáš D     ...
Vedúci         Mgr. Novák   Mgr. Novák  ...
```

If the week is not yet planned (no slots): show "Plán pre tento týždeň ešte nie je k dispozícii."

If a user has unavailability on a day: show a small "Nedostupný" badge on their row for that day.

Navigation: previous/next week buttons update the route via `$this->redirect(route('week', $week->id))`.

**No editing on this page** — it is read-only for all employees. Admin edits happen in Filament.

---

### `UnavailabilityManager`

**File:** `app/Livewire/UnavailabilityManager.php`

Employees view and manage their own unavailability declarations.

Properties:
- `$entries` — collection of own `Unavailability` records, ordered by date desc
- `$selectedDate` — currently picked date (for create form)
- `$reason` — optional text

Methods:
- `mount()` — load entries
- `submit()` — validate, check deadline, create record
- `delete(int $id)` — delete if before deadline and owned by user

Deadline check (in component or service):
```php
$deadline = now()->addHours(
    app(TenantSettings::class)->unavailability_hours
);
if (Carbon::parse($this->selectedDate)->startOfDay() <= $deadline) {
    $this->addError('selectedDate', 'Termín na nahlásenie nedostupnosti pre tento deň už uplynul.');
    return;
}
```

UI:
- Date picker (only future dates or dates still before deadline, past dates disabled in the picker)
- Reason text (optional)
- List of existing entries with a delete button (hidden if past deadline)
- Warning if today is within 48h of a future day (i.e. deadline approaching)

No backwards-write: the date picker `min` attribute is always `today + 1 day`. Server also validates this.

---

### `HoursView`

**File:** `app/Livewire/HoursView.php`

Read-only view of own shifts and calculated pay.

Properties:
- `$month` — current month (YY-MM), default current month
- `$shifts` — own shifts for the month
- `$rate` — own Rate record

Computed:
- `totalHours()` — sum of (end - start - break) in hours
- `totalPay()` — multiply by applicable rate (weekday/saturday/sunday)

UI:
- Month selector (prev/next month links)
- Table: Date, Position, Start, End, Break, Hours, Pay
- Summary row: total hours, total pay

Employees cannot edit shifts — only admins can via Filament.

---

### `ProfileSettings`

**File:** `app/Livewire/ProfileSettings.php`

Password change form.

Properties:
- `$currentPassword`
- `$newPassword`
- `$newPasswordConfirmation`

Method `save()`:
- Validate `currentPassword` matches `Hash::check()`
- Validate `newPassword` against Laravel's `Password::defaults()` rules
- Hash and save

No email or name editing — admin manages this.

---

## Auth Flow (Employee-Facing)

Standard Laravel session auth. No Livewire-specific auth — use plain Blade forms for login/logout to keep it simple and robust.

Login:
- POST `/login` → `LoginController@authenticate`
- Throttled: 6 attempts per minute (existing pattern, keep it)
- On success: redirect to `route('home')` (CalendarPage)
- On fail: redirect back with error

Logout:
- POST `/logout` (CSRF-protected)
- Redirect to `route('login')`

Password reset:
- Standard Laravel `Password::sendResetLink()` / `Password::reset()` flow
- Custom notification `ResetPasswordNotification` (already exists, keep)

Self-registration (if `allow_self_registration = true`):
- `/registracia` page — name, lastname, email, password
- Creates User with no roles, `email_verified_at = null`
- Sends verification email
- After email verification: user can log in but has no role → sees "waiting for approval" page
- Admin approves in Filament → assigns `employee` role → `UserAllowedToLogin` notification sent

Self-registration disabled (default):
- Admin invites users from Filament → user receives set-password link
- After password set: user has `employee` role and can log in immediately

---

## Livewire Best Practices for This App

1. **No N+1 queries** — always eager-load in `mount()` or computed properties. Use `with()` in queries.
2. **Wire:model.lazy** for text inputs — avoid round-trips on every keystroke.
3. **Optimistic UI** — for unavailability deletion, remove from `$entries` immediately before the server confirms.
4. **Poll sparingly** — the calendar view does not need live updates. No `wire:poll`.
5. **Flash messages** — use `session()->flash()` with a `x-flash` Blade component for success/error feedback.
6. **Form validation** — use `#[Rule]` attributes on properties (Livewire 3 syntax) for inline validation.
7. **Pagination** — `WithPagination` trait on `UnavailabilityManager` if entries grow long.

---

## Blade Components (shared)

```
resources/views/components/
  ├── layout/
  │   ├── app.blade.php
  │   └── nav.blade.php
  ├── ui/
  │   ├── alert.blade.php          -- flash messages
  │   ├── badge.blade.php          -- status badges
  │   ├── button.blade.php
  │   ├── card.blade.php
  │   ├── modal.blade.php          -- simple Alpine.js modal wrapper
  │   └── date-picker.blade.php
  └── calendar/
      ├── week-grid.blade.php      -- outer grid layout
      ├── day-header.blade.php     -- column headers
      └── slot-cell.blade.php      -- individual plan slot cell
```

Keep Alpine.js (already bundled with Livewire 3) for small client-side interactivity (modal open/close, confirm dialogs).

---

## Accessibility & Mobile

- Use `<table>` for the week grid — semantic and screen-reader friendly.
- Mobile: stack days vertically (CSS `@media` breakpoint), show one day at a time with swipe navigation (Alpine.js + CSS transition).
- Minimum touch target: 44px (WCAG 2.1 AA).
- All forms have `<label>` elements associated with inputs.
