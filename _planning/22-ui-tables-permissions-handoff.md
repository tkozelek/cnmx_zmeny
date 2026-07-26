# 22 — UI, Tables, Permissions, and Absence System Handoff

> Date: 2026-07-25  
> Author: Antigravity AI Assistant  
> Status: Fully Implemented & Verified  

---

## 1. Single-Database Multi-Tenancy & Authorization

### Cinema Team Context
- Architecture uses single-database multi-tenancy backed by **Spatie Laravel Permission (Teams mode)**.
- Every cinema location is a row in `teams`. Membership and approval live in `team_user` (`approved_at`).
- Active cinema is stored on `users.current_team_id`.
- Switching teams (`TeamSwitchController`) uses `redirect()->back()` to preserve the user's current URL location across team switches.

### Team Permission Helper (`User::hasPermissionInTeam`)
- Method `$user->hasPermissionInTeam(string $permission, ?Team $team = null): bool` added to `App\Models\User`.
- Sets Spatie permission team context to `$team->id` and checks permission or manager/admin role safely with exception handling.

---

## 2. Standardized Dot-Notation Spatie Permissions

All permissions follow the `resource.action` dot-notation format:

| Permission | Description | Assigned Roles |
|---|---|---|
| `absence.view` | View all staff absences for active cinema team | Manager, Admin |
| `absence.create` | Submit new absence requests | Employee, Manager, Admin |
| `absence.manage-own` | End or manage own active absence | Employee, Manager, Admin |
| `absence.delete-own` | Delete own absence within 30-day window | Employee, Manager, Admin |
| `absence.delete-inactive` | Delete past/inactive staff absences | Manager, Admin |
| `absence.manage` | Full management of all staff absences | Manager, Admin |
| `user.view-any` | View cinema staff list | Manager, Admin |
| `user.view` | View user profile details | Employee, Manager, Admin |
| `user.create` | Invite / create cinema staff user | Manager, Admin |
| `user.update` | Edit staff user roles and details | Manager, Admin |
| `user.delete` | Block / delete staff user from cinema | Manager, Admin |
| `user.approve` | Approve pending user registration | Manager, Admin |

---

## 3. Absence System & Policies (`App\Policies\AbsencePolicy`)

- **Creation (`create`)**: Everyone approved in the cinema team can submit an absence.
- **Advance Notice Rule (`StoreAbsenceRequest`)**:
  - Non-management staff must submit absences at least `$team->absenceDeadlineHours()` in advance (configured in `team_settings.absence_deadline_hours`, default `48` hours).
  - Admins and Managers are exempt from advance notice deadlines for emergency overrides.
  - Required fields: `date_from`, `date_to`, and `reason` (mandatory).
- **Active Absence Management (`end` / `delete`)**:
  - Absence owners can manage/end their own active absences.
  - Managers/Admins can manage or end any staff absence in their team.
- **Inactive Absence Deletion (`delete`)**:
  - Owners can delete their past inactive absences within **30 days** (`INACTIVE_DELETION_WINDOW_DAYS = 30`).
  - Managers/Admins holding `absence.delete-inactive` or `absence.manage` can delete inactive absences at any time.

---

## 4. Livewire Rappasoft Tables & Design System

### Design System & Color Tokens
- Dark neutral background theme (`neutral-900` / `neutral-950`).
- Header headers: Pitch-black (`bg-neutral-950`).
- Hover state: `hover:bg-neutral-800/90`.
- Action buttons: Solid high-contrast **Emerald-600** (`bg-emerald-600 hover:bg-emerald-500`) for confirm/edit/end, and **Rose-600** (`bg-rose-600 hover:bg-rose-500`) for deny/delete.

### Uniform Control Sizing (`h-10`)
- Search input, Filter dropdowns, Column select button, and Per-Page dropdowns all share exact matching dimensions:
  `h-10 px-3 text-sm rounded-lg border-neutral-700 bg-neutral-800 text-neutral-100 focus:border-neutral-500 focus:outline-none`.

### Split Absences Tables (`/dovolenka`)
1. **Moje absencie (`MyAbsencesDataTable`)**:
   - Displayed at the top for **all users**.
   - Filtered strictly to `user_id = auth()->id()`.
2. **Všetky absencie zamestnancov (`AbsencesDataTable`)**:
   - Displayed below for **Managers and Admins** (`@can('viewAny', Absence::class)`).
   - Scoped to `team_id = $currentTeam->id`.

### Overlapping Date Filter Queries
Date range filters (`Od dátumu` & `Do dátumu`) in both absence tables execute overlapping date logic:
- `Od dátumu` (`$filterFrom`): `where('date_to', '>=', $filterFrom)`
- `Do dátumu` (`$filterTo`): `where('date_from', '<=', $filterTo)`
Any absence block that collides with or covers the selected filter window is included in the table.

---

## 5. Modal & Date Picker Integration

- **Alpine.js Modal Component**: Replaced legacy inline markup with responsive Alpine modal overlay (`z-[100]`), guaranteeing coverage over sticky `z-50` navigation.
- **Validation Auto-Open**: Automatically re-opens upon form validation errors (`openModal: @js($errors->any())`) and displays a styled error banner.
- **Flatpickr Integration**: Dual date range selection (`mode: 'range'`, Slovak localization) bound via Alpine `initFlatpickr()`.

---

## 6. Essential Commands & Verification

```bash
php artisan migrate:fresh --seed      # Reset DB, seed all 14 cinemas, roles, permissions
npm run build                         # Rebuild Vite assets
php artisan test --compact            # Run test suite
vendor/bin/pint --dirty --format agent # Format PHP code
```
