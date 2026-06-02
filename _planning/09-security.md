# 09 — Security & Data Stability

## Threat Model

This is a multi-tenant SaaS with financial data (pay rates, hours). The primary threats are:

1. **Cross-tenant data leakage** — one cinema sees another's employee data
2. **Privilege escalation** — employee accessing admin functions
3. **Unauthorized plan modifications** — editing a locked week, submitting past-deadline unavailability
4. **Account takeover** — brute-force login, password reset abuse
5. **Mass assignment** — filling model attributes that should not be user-controlled
6. **XSS** — user-supplied content rendered unsanitized
7. **CSRF** — state-changing requests without token verification
8. **File upload abuse** — malicious files stored or served
9. **Injection** — SQL injection via Eloquent or raw queries

---

## Authentication

### Password Policy
Use Laravel's `Password::defaults()` with a strong rule set:
```php
// AppServiceProvider::boot()
Password::defaults(fn () =>
    Password::min(10)
        ->letters()
        ->mixedCase()
        ->numbers()
        ->uncompromised()  // checks HaveIBeenPwned API
);
```

### Rate Limiting
```php
// routes/tenant.php
Route::post('/login', ...)->middleware('throttle:6,1');
Route::post('/zabudnute-heslo', ...)->middleware('throttle:3,5');  // 3 per 5 minutes
Route::post('/registracia', ...)->middleware('throttle:3,10');
```

Use Redis for rate limiting storage (`CACHE_DRIVER=redis` in `.env`).

### Session
```php
// config/session.php
'secure'    => env('SESSION_SECURE_COOKIE', true),   // HTTPS-only
'same_site' => 'lax',
'encrypt'   => true,
'lifetime'  => 120,  // 2 hours idle timeout
```

### Remember Me
Limit "remember me" to 7 days (not the default 5 years):
```php
Auth::login($user, remember: true);
// Override in AuthController with explicit cookie expiry
```

---

## Authorization

### Every HTTP endpoint must have explicit authorization

Use the authorization checklist:
- `$this->authorize('action', $model)` in every controller method (or in Form Request `authorize()`)
- Filament resources implement `canViewAny()`, `canCreate()`, `canEdit()`, `canDelete()` — all check Spatie permissions
- **No route is publicly accessible that should require auth** — middleware applied to route groups, not individual routes

### Defense-in-Depth for Tenant Isolation

Do not rely solely on Stancl's DB switching. Add explicit tenant-scoped queries where natural:

```php
// Even though the DB is tenant-scoped, be explicit in critical places
// to prevent bugs from slipping through during development

// Example: WeekController
public function show(Week $week): Response
{
    // Route model binding resolves from the tenant DB automatically.
    // But if ever raw IDs are passed (e.g. API), validate:
    abort_unless($week->exists, 404);  // implicit — week from tenant DB or 404
    $this->authorize('view', $week);
    ...
}
```

### Policy Coverage

Every model that is accessed by users must have a Policy:

| Model | Policy | Key checks |
|---|---|---|
| Week | WeekPolicy | lock requires permission; view always allowed |
| PlanSlot | PlanSlotPolicy | edit/delete requires permission; locked week blocks all |
| PlanAssignment | PlanAssignmentPolicy | assign requires permission; locked week blocks |
| Unavailability | UnavailabilityPolicy | create/edit/delete: deadline + ownership |
| User | UserPolicy | update: admin only; view self: always |
| Shift | ShiftPolicy | create/edit: admin only |
| Media | MediaPolicy | upload/delete: admin/manager |

Register all in `AuthServiceProvider`:
```php
protected $policies = [
    Week::class           => WeekPolicy::class,
    PlanSlot::class       => PlanSlotPolicy::class,
    PlanAssignment::class => PlanAssignmentPolicy::class,
    Unavailability::class => UnavailabilityPolicy::class,
    User::class           => UserPolicy::class,
    Shift::class          => ShiftPolicy::class,
    Media::class          => MediaPolicy::class,
];
```

---

## Mass Assignment Protection

All models must use `$fillable` (not `$guarded = []`). Fields that must never be user-controlled:
- `user_id` on `plan_assignments` — set from `auth()->id()` or admin selection, never from request
- `locked` / `locked_at` / `locked_by` on `Week` — set only by service, never direct fill
- `admin_override` / `overridden_by` on `Unavailability` — set by service
- `is_active` on `User` — set by admin only, never from profile form

Example:
```php
class Unavailability extends Model
{
    protected $fillable = [
        'user_id', 'date', 'reason', 'submitted_at',
        // admin_override and overridden_by are NOT in $fillable
    ];
}
```

---

## XSS Prevention

- All user input rendered in Blade uses `{{ }}` (auto-escaped), never `{!! !!}`.
- The only `{!! !!}` usage should be for trusted HTML from the application itself (e.g. locale strings that contain `<br>`).
- Input sanitization: trim strings, no raw HTML stored in DB from user input.
- Content-Security-Policy header (add via middleware):

```php
// app/Http/Middleware/SecurityHeaders.php
$response->headers->set('Content-Security-Policy',
    "default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;"
);
$response->headers->set('X-Frame-Options', 'DENY');
$response->headers->set('X-Content-Type-Options', 'nosniff');
$response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
```

---

## CSRF

- All state-changing forms include `@csrf` (Blade default enforces this via `VerifyCsrfToken` middleware).
- API routes (if any) use Sanctum token authentication — CSRF not applicable.
- Filament uses its own CSRF protection.
- Livewire 3 sends CSRF token automatically on every request.

---

## File Upload Security

```php
// FileUploadController or Filament file field
$request->validate([
    'file' => [
        'required',
        'file',
        'max:10240',  // 10 MB
        'mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
    ]
]);

// Store with random name, never use original filename as the path
$path = $request->file('file')->store(
    'uploads/' . tenant()->id,
    'local'  // not public by default
);
```

- Never serve files directly from storage — serve via a controller that checks authorization.
- Never execute uploaded files (no `.php` allowed in MIME list).
- Validate MIME type server-side, not just extension.

---

## SQL Injection

- Use Eloquent exclusively. No `DB::select("SELECT * FROM users WHERE id = {$id}")`.
- When raw queries are unavoidable (e.g. complex reports), use parameterized bindings:
  ```php
  DB::select('SELECT * FROM shifts WHERE user_id = ?', [$userId]);
  ```
- PHPStan + Larastan at level 6+ will catch many of these at CI time.

---

## Audit Trail

Add `spatie/laravel-activitylog` (it is compatible with multi-tenancy — logs go into the tenant DB).

Log these events automatically:

```php
// In each model that matters:
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PlanAssignment extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'plan_slot_id', 'assigned_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

Models to log: `Week` (lock/unlock), `PlanAssignment` (assign/remove), `Unavailability` (create/delete), `User` (role changes, deactivation), `TenantSettings` (any change).

---

## Data Integrity

### Database Constraints
- All FKs defined with explicit `onDelete()` behaviour (CASCADE or SET NULL — never silent orphans).
- UNIQUE constraints on `unavailabilities(user_id, date)`, `plan_assignments(plan_slot_id, user_id)` (a user can only be assigned once per slot).
- NOT NULL on all required columns — validate at DB level, not just application level.

### Transactions
Operations that touch multiple tables must be wrapped in transactions:

```php
// GeneratePlanFromTemplateAction
DB::transaction(function () use ($week, $template) {
    foreach ($week->days as $day) {
        foreach ($templateSlots->where('day_of_week', $day->date->dayOfWeek) as $slot) {
            $planSlot = PlanSlot::create([...]);
            for ($i = 0; $i < $slot->required_count; $i++) {
                PlanAssignment::create(['plan_slot_id' => $planSlot->id, 'user_id' => null]);
            }
        }
    }
});
```

### Soft Deletes
Use `SoftDeletes` on `User` and `Unavailability`:
- Deleted users' historical shifts and plan assignments are preserved for payroll reports.
- Soft-deleted unavailability entries are kept for audit.

Do NOT use soft deletes on `PlanSlot` or `PlanAssignment` — when admin removes a slot, it should be genuinely removed (the week plan is an active document).

---

## Environment & Secrets

- `.env` never committed (`.gitignore` already covers it).
- Use `php artisan key:generate` — never share `APP_KEY`.
- Database credentials: one DB user per tenant DB with permissions limited to that database (avoid root/superuser for app connections).
- All sensitive config via environment variables — no hard-coded credentials anywhere.
- `APP_DEBUG=false` in production — never expose stack traces to users.

---

## Dependency Security

- Run `composer audit` in CI pipeline to detect known vulnerabilities.
- Run `npm audit` for frontend dependencies.
- Keep Laravel and packages up to date — subscribe to Laravel security announcements.
- Use `barryvdh/laravel-ide-helper` in dev only (`require-dev`), never in production.

---

## CI Pipeline Security Checks

Mandatory CI steps before merge:

```yaml
- run: composer audit
- run: php artisan test --stop-on-failure
- run: ./vendor/bin/phpstan analyse --level=6
- run: ./vendor/bin/pint --test  # code style check
```

---

## HTTPS & Infrastructure

- Force HTTPS via `FORCE_HTTPS=true` + `$app->forceHttps()` in `AppServiceProvider`.
- Use `Illuminate\Http\Middleware\TrustProxies` configured for the load balancer.
- HSTS header: `Strict-Transport-Security: max-age=31536000; includeSubDomains`
- Wildcard SSL cert for `*.app.com` tenant subdomains.
