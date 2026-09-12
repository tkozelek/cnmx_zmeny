# 17 — Logging System

## Three Layers

The app needs three distinct logging concerns, each with a different purpose and audience:

| Layer | What | Who reads it | Storage |
|---|---|---|---|
| **Audit log** | Business events ("who changed what in the app") | Admin in Filament | Tenant DB (`activity_log` table) |
| **Application log** | Errors, exceptions, slow queries, job failures | Developer / DevOps | Log files / external service |
| **Security log** | Auth events, suspicious behaviour, rate-limit hits | DevOps / security | Separate log channel |

---

## Layer 1: Audit Log (Spatie Activity Log)

Package: `spatie/laravel-activitylog` — already planned in doc 02.

### What Gets Logged

Every business-significant action is logged with actor, subject, and a human-readable description.

**Plan Management**
| Event | Description | Causer | Subject |
|---|---|---|---|
| Week created | "Týždeň [date_from–date_to] bol vygenerovaný" | User | Week |
| Week locked | "Týždeň [dates] bol uzamknutý" | User | Week |
| Week unlocked | "Týždeň [dates] bol odomknutý" | User | Week |
| Plan slot created | "Pozícia [name] [time] pridaná do [day]" | User | PlanSlot |
| Plan slot deleted | "Pozícia [name] [time] odstránená z [day]" | User | PlanSlot |
| User assigned to slot | "[Employee] priradený na [position] [time] [day]" | User | PlanAssignment |
| User removed from slot | "[Employee] odstránený z [position] [time] [day]" | User | PlanAssignment |
| Uncertified user assigned | "[Employee] priradený na [position] bez certifikácie — admin override" | User | PlanAssignment |

**Absence**
| Event | Description |
|---|---|
| Absence created (employee) | "[User] nahlásil neprítomnosť: [date/range/recurring]" |
| Absence created (admin) | "[Admin] pridal neprítomnosť pre [user]: [date] (admin override)" |
| Absence deleted | "[User/Admin] zrušil neprítomnosť: [date]" |

**Marketplace**
| Event | Description |
|---|---|
| Listing posted | "[User] hľadá náhradu: [position] [time] [day]" |
| Offer made | "[User] ponúkol záskoku pre [position] [day]" |
| Offer approved | "Zmena schválená: [old user] → [new user] na [position] [time] [day]" |
| Listing cancelled | "[User/Admin] zrušil hľadanie náhrady #[id]" |
| Listing expired | "Hľadanie náhrady #[id] vypršalo bez záskoku" |

**User Management**
| Event | Description |
|---|---|
| User invited | "[Admin] pozval nového zamestnanca: [email]" |
| User role changed | "[Admin] zmenil rolu [user]: [old_role] → [new_role]" |
| User deactivated | "[Admin] deaktivoval účet: [user]" |
| User reactivated | "[Admin] aktivoval účet: [user]" |
| Position certified | "[Admin] certifikoval [user] pre pozíciu [position]" |
| iCal token regenerated | "[User] obnovil iCal token" |

**Settings**
| Event | Description |
|---|---|
| Any setting changed | "[Admin] zmenil nastavenie [key]: [old] → [new]" |

### Configuration

```php
// config/activitylog.php
'default_log_name'              => 'default',
'activity_model'                => \Spatie\Activitylog\Models\Activity::class,
'table_name'                    => 'activity_log',
'database_connection'           => null,   // uses tenant DB connection
'clean_records_older_than_days' => 365,    // keep 1 year of history
```

### Model Setup

Use `LogsActivity` trait on every model that should be audited:

```php
// Shared via a trait or base model
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

trait AuditableTrait
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName(static::class);
    }
}
```

For models where the default property diff is not enough (e.g. `PlanAssignment` where the important event is the user change), override `tapActivity()`:

```php
// PlanAssignment
public function tapActivity(Activity $activity, string $eventName): void
{
    $slot = $this->planSlot->load('position', 'day');
    $activity->description = match($eventName) {
        'created' => "{$this->user?->name} priradený na {$slot->position->name} {$slot->start_time} {$slot->day->date}",
        'updated' => "Priradenie aktualizované: {$slot->position->name} {$slot->start_time} {$slot->day->date}",
        'deleted' => "{$this->user?->name} odstránený z {$slot->position->name} {$slot->start_time} {$slot->day->date}",
    };
}
```

### Filament: Activity Log Viewer

A dedicated `ActivityLogResource` (or use the community package `pxlrbt/filament-activity-log`):

**Navigation group:** Nastavenia  
**Permission:** admin only

Table columns:
- Icon (created=green, updated=orange, deleted=red)
- Causer (who)
- Description
- Subject type + ID (clickable link to the resource if it still exists)
- Created at

Filters:
- By causer (user select)
- By log name (model type)
- By event (created / updated / deleted)
- Date range

---

## Layer 2: Application Log

Use Laravel's standard Monolog stack. Extend with structured logging.

### Log Channels (config/logging.php)

```php
'channels' => [
    'stack' => [
        'driver'   => 'stack',
        'channels' => ['daily', 'stderr'],
    ],

    'daily' => [
        'driver' => 'daily',
        'path'   => storage_path('logs/laravel.log'),
        'level'  => env('LOG_LEVEL', 'warning'),
        'days'   => 14,
    ],

    'security' => [
        'driver' => 'daily',
        'path'   => storage_path('logs/security.log'),
        'level'  => 'info',
        'days'   => 90,
    ],

    // In production: send to external service
    'papertrail' => [  // or Logtail, Sentry, etc.
        'driver'       => 'monolog',
        'level'        => env('LOG_LEVEL', 'error'),
        'handler'      => SyslogUdpHandler::class,
        'handler_with' => ['host' => env('PAPERTRAIL_URL'), 'port' => env('PAPERTRAIL_PORT')],
    ],
],
```

### Structured Log Context

Every log message should include tenant context so production logs are filterable by cinema:

```php
// app/Http/Middleware/InjectTenantLogContext.php
// Runs after InitializeTenancyByDomain

public function handle(Request $request, Closure $next): Response
{
    if (tenancy()->initialized()) {
        Log::withContext([
            'tenant_id'   => tenant('id'),
            'tenant_name' => tenant('name'),
        ]);
    }
    return $next($request);
}
```

### What Gets Logged at Each Level

| Level | Examples |
|---|---|
| `ERROR` | Unhandled exceptions, failed queued jobs, DB connection failure |
| `WARNING` | Uncertified user assigned (business rule violated), plan generation with missing slots, export failed |
| `INFO` | Week generated, week locked, export downloaded, iCal feed accessed |
| `DEBUG` | (dev only) SQL queries, Livewire component lifecycle |

### Slow Query Logging

```php
// AppServiceProvider::boot()
if (config('app.log_slow_queries')) {
    DB::listen(function (QueryExecuted $query) {
        if ($query->time > 500) {  // ms
            Log::warning('Slow query detected', [
                'sql'      => $query->sql,
                'bindings' => $query->bindings,
                'time_ms'  => $query->time,
            ]);
        }
    });
}
```

Enable with `LOG_SLOW_QUERIES=true` in `.env`. Never enable in production under heavy load — use only for debugging.

### Failed Job Logging

```php
// app/Console/Kernel.php or via Queue::failing()
Queue::failing(function (JobFailed $event) {
    Log::error('Queue job failed', [
        'job'       => $event->job->getName(),
        'exception' => $event->exception->getMessage(),
        'payload'   => $event->job->payload(),
    ]);
});
```

Also: configure `failed_jobs` table (already in schema) and set `QUEUE_FAILED_DRIVER=database`.

---

## Layer 3: Security Log

All security-relevant events go to the `security` log channel AND optionally to the audit log.

### Events Logged

```php
// Auth events
Log::channel('security')->info('Login success', [
    'user_id' => $user->id,
    'ip'      => request()->ip(),
    'ua'      => request()->userAgent(),
]);

Log::channel('security')->warning('Login failed', [
    'email' => $credentials['email'],
    'ip'    => request()->ip(),
]);

Log::channel('security')->warning('Too many login attempts', [
    'email' => $email,
    'ip'    => request()->ip(),
]);

Log::channel('security')->info('Password reset requested', ['email' => $email]);
Log::channel('security')->info('Password changed',         ['user_id' => $user->id]);
Log::channel('security')->info('iCal token regenerated',   ['user_id' => $user->id]);
Log::channel('security')->info('Logout',                   ['user_id' => $user->id]);

// Authorization failures
Log::channel('security')->warning('Authorization denied', [
    'user_id'    => auth()->id(),
    'action'     => $ability,
    'model'      => $model,
    'ip'         => request()->ip(),
]);
```

### Hooking into Laravel Auth Events

```php
// app/Listeners/LogAuthEvents.php

class LogAuthEvents
{
    public function handleLogin(Login $event): void
    {
        Log::channel('security')->info('login_success', [
            'user_id' => $event->user->id,
            'ip'      => request()->ip(),
        ]);
        $event->user->update(['last_login_at' => now()]);
    }

    public function handleFailed(Failed $event): void
    {
        Log::channel('security')->warning('login_failed', [
            'email' => $event->credentials['email'] ?? null,
            'ip'    => request()->ip(),
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        Log::channel('security')->info('logout', [
            'user_id' => $event->user?->id,
        ]);
    }
}
```

Register in `EventServiceProvider`:
```php
protected $listen = [
    Login::class   => [LogAuthEvents::class . '@handleLogin'],
    Failed::class  => [LogAuthEvents::class . '@handleFailed'],
    Logout::class  => [LogAuthEvents::class . '@handleLogout'],
];
```

---

## Log Rotation & Retention

| Channel | Retention | Notes |
|---|---|---|
| `daily` (app log) | 14 days | Rotated by Monolog `daily` driver |
| `security` | 90 days | Longer retention for incident response |
| `activity_log` (DB) | 365 days | Cleaned by scheduled `CleanOldActivityLog` command |
| `failed_jobs` (DB) | Manual | Review and purge periodically (`queue:flush`) |

Scheduled cleanup:
```php
// routes/console.php
Schedule::command('activitylog:clean')->weekly();
Schedule::command('queue:prune-failed', ['--hours=720'])->weekly();  // prune >30d old
```

---

## Production Log Destination

For production, ship logs to an external log aggregation service. Options:

| Service | Laravel driver | Notes |
|---|---|---|
| **Logtail / Better Stack** | HTTP handler | Good free tier, structured log search |
| **Sentry** | `sentry/sentry-laravel` | Best for exception tracking with stack traces |
| **Papertrail** | Syslog UDP | Simple, inexpensive |
| **AWS CloudWatch** | `maxbanton/cwh` | If already on AWS |

Recommended: **Sentry for exceptions** (errors + warnings) + **Logtail for full log stream** (info + above). Configure in `.env`:

```dotenv
LOG_CHANNEL=stack
LOG_STACK=daily,sentry
SENTRY_LARAVEL_DSN=https://...
```

---

## Filament: Security Log Viewer (Admin Only)

A read-only `SecurityLogPage` Filament page (admin only) that tails/reads the `security.log` file.

Shows last 500 lines in a formatted table (parsed from structured JSON log lines). Columns: timestamp, level, event type, user ID, IP.

Simple implementation: read file with `File::get(storage_path('logs/security.log'))`, parse JSON lines, paginate.

> In production: replace with a link to the external log service (Logtail/Sentry) rather than reading local files.

---

## Implementation Checklist

- [ ] `spatie/laravel-activitylog` installed and configured (`config/activitylog.php`)
- [ ] `AuditableTrait` applied to: `Week`, `PlanSlot`, `PlanAssignment`, `Absence`, `User`, `MarketplaceListing`, `MarketplaceOffer`, `TenantSettings`, `UserPosition`
- [ ] Custom `tapActivity()` on `PlanAssignment` and `Absence`
- [ ] `ActivityLogResource` in Filament (or community package integration)
- [ ] `InjectTenantLogContext` middleware in tenant middleware stack
- [ ] `security` log channel in `config/logging.php`
- [ ] `LogAuthEvents` listener registered for Login, Failed, Logout events
- [ ] Authorization denial logging in policy base class or via `Gate::after()`
- [ ] Slow query logging (behind env flag)
- [ ] Failed job logging via `Queue::failing()`
- [ ] `CleanOldActivityLog` + `queue:prune-failed` in scheduler
- [ ] Production log destination configured (Sentry + Logtail recommended)
- [ ] `SecurityLogPage` Filament page (or link to external service)
- [ ] `reports.analytics` and `security.view-logs` permissions added to permission list
- [ ] Feature tests: audit log entries created for each major event, security log entries for auth events
