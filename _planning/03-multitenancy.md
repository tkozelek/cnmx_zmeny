# 03 — Multi-Tenancy

## Strategy: Single Database + Spatie Teams

All cinemas share one database. Each cinema is a **Spatie Team** (`teams` table). Every tenant-scoped model carries a `team_id` foreign key. Filament v3's built-in tenancy system handles panel-level scoping automatically.

This replaces the original Stancl Tenancy database-per-tenant plan. For this app's scale it is the right call — no DB provisioning jobs, no bootstrapper middleware stack, no per-tenant migrations, vastly simpler infrastructure.

**Drop:** `stancl/tenancy` entirely.  
**Use:** Spatie Permission teams mode + Filament's native `->tenant()` + a `BelongsToTeam` trait for model scoping.

---

## Teams Table

```sql
teams
  id            bigint      PK
  name          string(120)             -- "Kino Lumière"
  slug          string(60)  UNIQUE      -- subdomain / URL identifier
  is_active     boolean     DEFAULT true
  created_at    timestamp
  updated_at    timestamp
```

The `Team` model is the tenant. It is also the Spatie Permission team.

---

## What Gets a `team_id`

Every table that holds cinema-specific data gets `team_id bigint FK teams.id CASCADE DELETE`:

| Table | team_id? |
|---|---|
| `users` | ✓ |
| `tenant_settings` | ✓ (one row per team) |
| `positions` | ✓ |
| `weeks` | ✓ |
| `days` | via `week_id` → no direct column needed |
| `plan_slots` | via `day_id` → no direct column needed |
| `plan_assignments` | via `plan_slot_id` → no direct column needed |
| `absences` | ✓ |
| `shifts` | ✓ |
| `rates` | ✓ |
| `media` | ✓ |
| `marketplace_listings` | via `plan_assignment_id` → no direct column needed |
| `activity_log` | ✓ (add `team_id` via custom tap) |
| Spatie `model_has_roles` | ✓ (teams mode adds this automatically) |

Days, plan_slots, plan_assignments and marketplace_listings are reachable through their parent FK chain, so they don't need a direct `team_id` — querying always goes through a `whereHas` or eager load that is already scoped.

---

## BelongsToTeam Trait

A shared trait that auto-scopes all queries and auto-fills `team_id` on creation:

```php
// app/Traits/BelongsToTeam.php

trait BelongsToTeam
{
    public static function bootBelongsToTeam(): void
    {
        // Auto-fill team_id on new records
        static::creating(function (Model $model) {
            if (! $model->team_id && auth()->check()) {
                $model->team_id = auth()->user()->current_team_id;
            }
        });
    }

    public static function bootedBelongsToTeam(): void
    {
        // Global scope: always filter to current team
        static::addGlobalScope('team', function (Builder $query) {
            if (auth()->check() && auth()->user()->current_team_id) {
                $query->where(
                    (new static)->getTable() . '.team_id',
                    auth()->user()->current_team_id
                );
            }
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
```

Apply to: `User`, `TenantSettings`, `Position`, `Week`, `Absence`, `Shift`, `Rate`, `Media`.

> The global scope means you never write `->where('team_id', ...)` manually anywhere — it is always injected. Artisan commands and queue jobs that need to bypass this (e.g. a cross-tenant report) call `Model::withoutGlobalScope('team')` explicitly.

---

## User ↔ Team Relationship

A user can belong to multiple teams (a person could manage two cinemas). The active team is tracked on the user:

```sql
-- Add to users table:
current_team_id   bigint  nullable FK teams.id SET NULL
```

```sql
-- Pivot: team_user
team_id   bigint  FK teams.id CASCADE DELETE
user_id   bigint  FK users.id CASCADE DELETE
PRIMARY KEY (team_id, user_id)
```

```php
// User model
public function teams(): BelongsToMany
{
    return $this->belongsToMany(Team::class)->withTimestamps();
}

public function currentTeam(): BelongsTo
{
    return $this->belongsTo(Team::class, 'current_team_id');
}

public function switchTeam(Team $team): void
{
    abort_unless($this->teams->contains($team), 403);
    $this->update(['current_team_id' => $team->id]);
    app(\Spatie\Permission\PermissionRegistrar::class)
        ->setPermissionsTeamId($team->id);
}
```

---

## Spatie Permission Team Scoping

After every login and every team switch, set the active team ID for permission lookups:

```php
// app/Http/Middleware/SetActiveTeam.php

public function handle(Request $request, Closure $next): Response
{
    if ($user = $request->user()) {
        $teamId = $user->current_team_id;
        app(\Spatie\Permission\PermissionRegistrar::class)
            ->setPermissionsTeamId($teamId);
    }
    return $next($request);
}
```

Register in the `web` middleware group (after `Authenticate`).

Also call in:
- `LoginController::authenticate()` after successful login
- `User::switchTeam()` 
- Queued jobs: restore in the job constructor from a stored `$teamId` property

---

## Filament v3 Native Tenancy

Filament v3 ships first-class multi-tenancy that maps perfectly onto this model.

```php
// app/Providers/Filament/AdminPanelProvider.php

return $panel
    ->tenant(Team::class, slugAttribute: 'slug')
    ->tenantRoutePrefix('') // cinemaname.app.com/admin — no extra prefix
    ->tenantMiddleware([SetActiveTeam::class], isPersistent: true)
    ->tenantRegistration(RegisterTeamPage::class)  // optional: self-service cinema signup
```

What Filament's tenant() does automatically:
- Adds the team slug to every admin URL: `/admin/{team:slug}/weeks`
- Injects a team switcher in the sidebar (if user belongs to multiple teams)
- Runs `SetActiveTeam` middleware on every panel request
- Scopes all Resource queries through the team (via `getTenantOwnershipRelationshipName()` on models)

Each Resource declares its team relationship:
```php
// app/Filament/Resources/WeekResource.php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery(); // BelongsToTeam global scope handles it
}
```

Or use Filament's built-in `HasTenant` on the model:
```php
// Week model
use Filament\Models\Contracts\HasTenant;

class Week extends Model implements HasTenant
{
    public function getTenants(Panel $panel): Collection
    {
        return $this->team ? collect([$this->team]) : collect();
    }
}
```

---

## URL Structure

With subdomain routing (one subdomain per cinema):

```
app.com/platform          → Central Filament panel (super-admins)
kino-lumiere.app.com/admin → Cinema admin panel (auto-scoped to that team)
kino-lumiere.app.com/      → Employee-facing Livewire app
```

Subdomain → team resolution: a small middleware reads the subdomain, finds the matching `teams.slug`, sets `current_team_id` on the authenticated user for that request:

```php
// app/Http/Middleware/ResolveTeamFromSubdomain.php

public function handle(Request $request, Closure $next): Response
{
    $host = $request->getHost();
    $central = config('app.central_domain'); // "app.com"

    if ($host !== $central && str_ends_with($host, '.' . $central)) {
        $slug = str_replace('.' . $central, '', $host);
        $team = Team::where('slug', $slug)->where('is_active', true)->firstOrFail();

        if ($user = $request->user()) {
            abort_unless($user->teams->contains($team), 403);
            if ($user->current_team_id !== $team->id) {
                $user->switchTeam($team);
            }
        }

        // Store on request so guests (iCal feed, public pages) can also use it
        $request->attributes->set('current_team', $team);
    }

    return $next($request);
}
```

---

## Data Isolation Checklist

- [ ] `BelongsToTeam` trait applied to all top-level tenant models
- [ ] Global scope tested: authenticated user can never read another team's rows — write a test that creates two teams, authenticates as team A user, asserts team B rows are invisible
- [ ] Filament panel URLs include team slug — wrong-team URL returns 403
- [ ] `SetActiveTeam` middleware fires on every web request after auth
- [ ] Queue jobs store `team_id` and restore it in `handle()` — never rely on auth() inside a queued job
- [ ] Artisan commands that iterate teams call `withoutGlobalScope('team')` and then manually scope per team
- [ ] Soft deletes do not leak: `Model::onlyTrashed()` in admin also respects global scope
- [ ] Activity log entries tagged with `team_id` (custom `tap` on `LogsActivity`)
- [ ] iCal feed: token lookup scoped — `User::withoutGlobalScope('team')->where('ical_token', $token)` (needed because the user has no session/team set yet at feed time)

---

## Central / Platform Admin

Runs outside any team context. A separate Filament panel at `app.com/platform`:

```php
// PlatformPanelProvider
return $panel
    ->id('platform')
    ->path('platform')
    // No ->tenant() — this panel sees all teams
    ->authGuard('web')
    ->resources([TeamResource::class, CentralUserResource::class])
```

`TeamResource` provides full CRUD for teams — create, suspend (`is_active = false`), delete.

Central super-admins are users in the shared `users` table with `current_team_id = null` and a `super_admin` role (global, not team-scoped).

---

## Local Development

No `/etc/hosts` tricks needed. Just run with a single domain and switch teams via the Filament team switcher in the sidebar:

```
localhost/admin/{team-slug}/weeks
```

Or configure two subdomains via Valet/Herd for closer-to-production testing.

---

## What Was Removed vs the Original Plan

| Removed | Replaced with |
|---|---|
| `stancl/tenancy` package | Filament native `->tenant()` + `BelongsToTeam` trait |
| Separate tenant databases | Single DB, `team_id` columns |
| Stancl bootstrappers (DB, Cache, Queue, Filesystem) | Standard Laravel, no bootstrapping needed |
| `TenancyServiceProvider` | Removed |
| `tenant.php` route file | All routes in `web.php`, scoped by subdomain middleware |
| Per-tenant DB migrations | Single migration set for everyone |
| `CreateDatabase` / `MigrateDatabase` jobs | Team creation is just `Team::create([...])` |
| `tomatophp/filament-tenancy` package | Not needed — Filament handles it natively |
| `tenant_{id}` database naming | Not applicable |
