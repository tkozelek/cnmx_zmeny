# 03 — Multi-Tenancy

## Strategy: Database-per-Tenant (Stancl Tenancy v3)

Keep the existing `stancl/tenancy` package (v3). Each cinema gets its own MySQL/PostgreSQL database. This provides the strongest data isolation guarantee — a bug in application code cannot accidentally leak one cinema's data to another, even if a query is constructed incorrectly.

---

## Tenancy Package Configuration

### Identification

Use **subdomain-based** identification. Each cinema gets `cinemaname.app.com`.

```php
// config/tenancy.php
'tenant_finder' => Stancl\Tenancy\TenantFinders\DomainTenantFinder::class,

'identification_middleware' => [
    Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
    Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
],
```

Central domains (admin panel, marketing site) are defined in `config/tenancy.php`:
```php
'central_domains' => [
    'app.com',        // central: platform admin
    'www.app.com',
    'localhost',
    '127.0.0.1',
],
```

Alternative: path-based (`app.com/kino-lumiere/`) — simpler SSL setup (single cert), but Stancl's subdomain approach is cleaner. **Recommend subdomain.**

---

## Tenant Lifecycle

### Creation Flow

1. Platform admin creates a new tenant via the **central Filament panel**.
2. A `TenantCreated` event fires.
3. Stancl `JobPipeline` runs in order:
   - `CreateDatabase` — provisions `tenant_{slug}` database
   - `MigrateDatabase` — runs all migrations in `database/migrations/tenant/`
   - `SeedDatabase` — runs `TenantSeeder` (seeds `tenant_settings`, default `positions`, default Spatie roles/permissions)
   - `CreateTenantAdmin` — creates the first admin user and sends invite email
4. DNS entry (manual step) points `slug.app.com` to the server.

```php
// app/Providers/TenancyServiceProvider.php
Events::listen(TenantCreated::class, JobPipeline::make([
    Jobs\CreateDatabase::class,
    Jobs\MigrateDatabase::class,
    Jobs\SeedDatabase::class,
    Jobs\CreateTenantAdmin::class,
])->send(fn (TenantCreated $event) => $event->tenant)->toListener());
```

### Deletion / Suspension
- Soft-delete tenant (set `is_active = false` on central tenant record).
- Full delete: run `DeleteDatabase` job + remove domain record.
- **Never auto-delete** — require explicit confirmation via a Filament action with typed confirmation.

---

## Route Structure

### Central Routes (`routes/web.php`)
- `/` — marketing / login to central admin
- `/platform/*` — central Filament panel (super-admin only)
- Password reset with central broker

### Tenant Routes (`routes/tenant.php`)
All cinema-facing routes. Wrapped in `InitializeTenancyByDomain` + `PreventAccessFromCentralDomains`:

```php
// routes/tenant.php
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    Route::get('/', [CalendarController::class, 'index'])->name('home');

    // Auth
    Route::get('/login',    [LoginController::class, 'index'])->name('login');
    Route::post('/login',   [LoginController::class, 'authenticate'])->middleware('throttle:6,1');
    Route::post('/logout',  [LoginController::class, 'logout'])->name('logout');

    // ... all scheduling routes

    // Filament tenant admin panel is also served under the tenant domain
    // Filament panel registered with tenant middleware (see filament.md)
});
```

### Filament Panel Routing
The **tenant admin Filament panel** lives at `/admin` under the tenant domain: `cinemaname.app.com/admin`.
The **central Filament panel** lives at `app.com/platform`.

---

## Bootstrappers

The bootstrappers define what gets "switched" when a tenant request comes in.

```php
'bootstrappers' => [
    Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,  // switch DB connection
    Stancl\Tenancy\Bootstrappers\CacheTagsBootstrapper::class,         // prefix cache keys
    Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class, // suffix storage paths
    Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,      // re-init tenancy in queued jobs
],
```

---

## Tenant Settings

Tenant-specific configuration lives in `tenant_settings` (one row, seeded on creation). The `TenantSettings` model is loaded into a singleton on bootstrap:

```php
// App\Http\Middleware or AppServiceProvider
app()->singleton(TenantSettings::class, fn () => TenantSettings::first());
```

This avoids repeated DB hits for settings on every request. Cache it with `remember()` for the duration of the request.

Settings exposed:
- `week_offset` — day number (0=Mon…6=Sun) the work week starts on
- `week_lookahead` — how many future weeks to generate/show
- `unavailability_hours` — hours before a day that submission is still allowed
- `allow_self_registration` — boolean
- `timezone`, `locale`

---

## Data Isolation Checklist

- [ ] All application models that belong to a tenant are in the tenant DB — no `tenant_id` column needed on each row.
- [ ] The `User` model has no `tenant_id` — it lives in the tenant DB which is the isolation boundary.
- [ ] Filament tenant panel uses `InitializeTenancyByDomain` middleware — can never see another tenant's data.
- [ ] Queue jobs that run in tenant context use `QueueTenancyBootstrapper` — they re-initialize tenancy when dequeued.
- [ ] Scheduled commands that touch tenant data iterate over all tenants explicitly using `Tenant::all()->each(fn ($t) => tenancy()->initialize($t))`.
- [ ] File uploads go to `storage/app/tenant_{id}/` (FilesystemTenancyBootstrapper suffixes the disk).
- [ ] Cache keys are automatically prefixed by `CacheTagsBootstrapper`.
- [ ] No raw SQL queries that could cross-contaminate — use Eloquent exclusively.

---

## Central Super-Admin Panel

Runs outside any tenant context on `app.com/platform`.

Capabilities:
- Create / suspend / delete tenants
- View all tenants and their basic stats (user count, last activity)
- Impersonate a tenant (use `stancl/tenancy`'s `Tenancy::initialize()` + session-based impersonation flag)
- Global health dashboard (Laravel Pulse)

Access: Only users in the central `users` table with a `super_admin` role.

---

## Local Development

For local dev, use `localhost` subdomains in `/etc/hosts`:

```
127.0.0.1  app.test
127.0.0.1  kino-lumiere.app.test
127.0.0.1  kino-palace.app.test
```

Or use Laravel Sail with a wildcard proxy. Add `.env` variable:
```
TENANCY_TEST_DOMAIN=app.test
```

The `TenancySeeder` creates one test tenant automatically when running `php artisan db:seed`.
