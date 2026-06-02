# 04 — Spatie Laravel Permission (Teams Mode)

## Why Teams Mode

Each tenant is one Spatie **Team**. A user can belong to multiple teams (a person could theoretically work at two cinemas) while holding different roles in each. Teams mode adds a `team_id` column to `model_has_roles` and `model_has_permissions`, so role lookups are automatically scoped.

---

## Installation & Setup

```php
// config/permission.php
'teams' => true,
'team_model' => App\Models\Tenant::class,  // Stancl Tenant model doubles as the Spatie Team
```

The `Tenant` model must implement the `HasTeams` interface or at minimum be what `setPermissionsTeamId()` uses:

```php
// In any service provider or middleware, after tenancy is initialized:
app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(
    tenant()->id  // sets the active team for all permission checks
);
```

This must be called:
- In a middleware that runs after `InitializeTenancyByDomain`
- In queue jobs after tenancy is re-initialized
- In Artisan commands that run in tenant context

---

## Roles

Three roles per tenant (seeded in `TenantSeeder`):

| Role | Slovak label | Description |
|---|---|---|
| `admin` | Administrátor | Full control over the tenant |
| `manager` | Vedúci | Can view plans, approve unavailability, view reports |
| `employee` | Brigádnik | Can view their own schedule and submit unavailability |

A fourth "state" that is not really a role:
- `is_active = false` on the User model replaces the old "Zablokovaný" role — blocking is a user attribute, not a permission state.
- Unverified users (self-registered, not yet approved by admin) simply have `email_verified_at = null` and no roles assigned — middleware denies access.

### Role Seeding

```php
// database/seeders/TenantSeeder.php
$adminRole    = Role::create(['name' => 'admin',    'guard_name' => 'web']);
$managerRole  = Role::create(['name' => 'manager',  'guard_name' => 'web']);
$employeeRole = Role::create(['name' => 'employee', 'guard_name' => 'web']);

// Assign permissions to roles (see below)
$adminRole->givePermissionTo(Permission::all());
$managerRole->givePermissionTo([...]);
$employeeRole->givePermissionTo([...]);
```

---

## Permissions

All permissions use kebab-case namespaced strings.

### User Management
- `users.view` — list all users
- `users.create` — invite new users
- `users.edit` — edit user profiles
- `users.delete` — delete/deactivate users
- `users.assign-roles` — change user roles

### Week & Plan
- `weeks.view` — view any week's plan
- `weeks.create` — generate new weeks
- `weeks.lock` — lock/unlock a week
- `plan.create` — build/edit the position plan for a week
- `plan.assign` — assign users to plan slots

### Unavailability
- `unavailability.view-all` — see all employees' unavailability
- `unavailability.edit-any` — admin override on any unavailability entry
- `unavailability.delete-any` — remove any unavailability entry

### Reports
- `reports.hours` — view hours/payroll reports
- `reports.plan` — export work plans
- `reports.export` — trigger any export

### Settings
- `settings.view` — view tenant settings
- `settings.edit` — modify tenant settings (week offset, etc.)
- `positions.manage` — create/edit/delete positions

### Media
- `media.upload` — upload files
- `media.manage` — see all files, toggle visibility, delete

---

## Role → Permission Matrix

| Permission | admin | manager | employee |
|---|:---:|:---:|:---:|
| users.view | ✓ | ✓ | — |
| users.create | ✓ | — | — |
| users.edit | ✓ | — | — |
| users.delete | ✓ | — | — |
| users.assign-roles | ✓ | — | — |
| weeks.view | ✓ | ✓ | ✓ |
| weeks.create | ✓ | — | — |
| weeks.lock | ✓ | — | — |
| plan.create | ✓ | — | — |
| plan.assign | ✓ | ✓ | — |
| unavailability.view-all | ✓ | ✓ | — |
| unavailability.edit-any | ✓ | — | — |
| unavailability.delete-any | ✓ | — | — |
| reports.hours | ✓ | ✓ | — |
| reports.plan | ✓ | ✓ | — |
| reports.export | ✓ | ✓ | — |
| settings.view | ✓ | ✓ | — |
| settings.edit | ✓ | — | — |
| positions.manage | ✓ | — | — |
| media.upload | ✓ | ✓ | — |
| media.manage | ✓ | — | — |

Employee additionally has an implicit permission to:
- View their own schedule
- Submit/edit/delete their own unavailability (subject to deadline)
- View their own hours

These are handled by **Policies** rather than Spatie permissions (they are ownership checks, not role-level gates).

---

## Policy Classes

Use Policy classes for ownership-based decisions. Register in `AuthServiceProvider`.

```php
// app/Policies/UnavailabilityPolicy.php
public function create(User $user): bool
{
    // Employees can create their own; deadline is enforced in the service layer
    return $user->can('employee') || $user->hasRole(['admin', 'manager']);
}

public function update(User $user, Unavailability $record): bool
{
    if ($user->hasRole('admin')) return true;
    // Owner can edit only if before the deadline
    return $record->user_id === $user->id
        && $this->isBeforeDeadline($record->date);
}

public function delete(User $user, Unavailability $record): bool
{
    if ($user->hasPermissionTo('unavailability.delete-any')) return true;
    return $record->user_id === $user->id
        && $this->isBeforeDeadline($record->date);
}
```

```php
// app/Policies/PlanSlotPolicy.php
public function assign(User $user, PlanSlot $slot): bool
{
    // Can't assign to a locked week
    if ($slot->day->week->locked) return false;
    return $user->hasPermissionTo('plan.assign');
}
```

```php
// app/Policies/WeekPolicy.php
public function lock(User $user, Week $week): bool
{
    return $user->hasPermissionTo('weeks.lock');
}
```

---

## Middleware Usage

Replace custom `EnsureUserHasRole` with Spatie's built-in middleware:

```php
// routes/tenant.php
Route::middleware(['role:admin'])->group(function () {
    // admin only
});

Route::middleware(['role:admin|manager'])->group(function () {
    // admin or manager
});

Route::middleware(['permission:plan.create'])->group(function () {
    Route::post('/plan', [PlanController::class, 'store']);
});
```

Add Spatie middleware aliases in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);
})
```

---

## Active User Guard

The `is_active` flag on `User` needs a middleware check on every authenticated request:

```php
// app/Http/Middleware/EnsureUserIsActive.php
public function handle(Request $request, Closure $next): Response
{
    if ($request->user() && ! $request->user()->is_active) {
        Auth::logout();
        return redirect()->route('login')->withErrors(['email' => 'Váš účet bol deaktivovaný.']);
    }
    return $next($request);
}
```

Apply globally in the `web` middleware group.

---

## Seeding Summary

`TenantSeeder` runs on every new tenant creation and seeds:

1. `tenant_settings` row with defaults
2. Default positions (Uvádzač, Bufet, Pokladňa, Vedúci)
3. Spatie roles: admin, manager, employee
4. All permissions
5. Role → permission assignments per matrix above

The first admin user is created by `CreateTenantAdmin` job (separate from seeder), receives an invite email.
