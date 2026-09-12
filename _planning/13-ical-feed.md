# 13 — iCal Subscription Feed

## Purpose

Every employee gets a private, permanent URL that they paste into Google Calendar, Apple Calendar, Outlook, or any other calendar app. Their assigned shifts automatically appear and stay in sync. No app install, no manual entry. When the admin updates the plan or locks a week, the next calendar sync picks up the changes.

---

## How It Works

1. Employee logs in → visits settings page → copies their personal feed URL
2. They add it as a "subscribed calendar" in their phone/desktop calendar
3. Calendar app polls the URL every few hours (behaviour varies by client)
4. Each plan assignment appears as an event: `"Uvádzač — Kino Lumière"`, `13:00–21:00`

---

## Token Design

A dedicated `ical_token` column on the `users` table:

```sql
-- Add to users table migration
ical_token  string(64)  UNIQUE  nullable  -- hex token, generated on first request
```

Generate with `Str::random(64)` on first access (lazy generation — no token until the employee opens settings). This avoids generating tokens for users who never use the feature.

Token is **not** the user's session or API token — it is a separate, low-privilege, read-only credential specifically for this feed. Rotating it invalidates all existing calendar subscriptions (so make rotation explicit — "Obnov odkaz" button with a confirmation).

---

## Route

```php
// routes/tenant.php (no auth middleware — token IS the auth)
Route::get('/feed/{token}.ics', ICalFeedController::class)
    ->name('ical.feed')
    ->middleware('throttle:60,1');  // 60 requests per minute max
```

No session, no CSRF. The `.ics` extension is required by some calendar clients.

---

## Controller

```php
// app/Http/Controllers/ICalFeedController.php

class ICalFeedController
{
    public function __invoke(string $token): Response
    {
        $user = User::where('ical_token', $token)
                    ->where('is_active', true)
                    ->firstOrFail();  // 404 on invalid/revoked token

        $assignments = PlanAssignment::with(['planSlot.position', 'planSlot.day.week'])
            ->where('user_id', $user->id)
            ->whereHas('planSlot.day.week', fn ($q) => $q->where('locked', true))
            // Only serve locked weeks — prevents calendar noise from unfinished draft plans
            ->whereHas('planSlot.day', fn ($q) =>
                $q->where('date', '>=', now()->subMonths(2)->toDateString())  // 2 months history
                  ->where('date', '<=', now()->addMonths(3)->toDateString())  // 3 months ahead
            )
            ->get();

        $calendar = app(ICalBuilder::class)->build($user, $assignments);

        return response($calendar, 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="zmeny.ics"',
            'Cache-Control'       => 'no-cache, no-store',  // always fresh
            'X-WR-CALNAME'        => 'Moje zmeny',
        ]);
    }
}
```

---

## iCal Builder

```php
// app/Services/ICalBuilder.php

class ICalBuilder
{
    public function build(User $user, Collection $assignments): string
    {
        $settings = app(TenantSettings::class);
        $tz       = $settings->timezone;  // "Europe/Bratislava"

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//CNMX Zmeny//SK',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:Moje zmeny',
            'X-WR-TIMEZONE:' . $tz,
            'REFRESH-INTERVAL;VALUE=DURATION:PT1H',  // hint: re-check every hour
            'X-PUBLISHED-TTL:PT1H',
        ];

        foreach ($assignments as $assignment) {
            $slot     = $assignment->planSlot;
            $day      = $slot->day;
            $position = $slot->position;

            $dtStart = Carbon::parse("{$day->date} {$slot->start_time}", $tz);
            $dtEnd   = $slot->end_time
                ? Carbon::parse("{$day->date} {$slot->end_time}", $tz)
                : $dtStart->copy()->addHours(8);  // fallback if no end time

            $uid     = "assignment-{$assignment->id}@cnmx";
            $summary = $position->name . ($slot->notes ? " — {$slot->notes}" : '');
            $dtstamp = now()->format('Ymd\THis\Z');

            $lines = array_merge($lines, [
                'BEGIN:VEVENT',
                'UID:'       . $uid,
                'DTSTAMP:'   . $dtstamp,
                'DTSTART;TZID=' . $tz . ':' . $dtStart->format('Ymd\THis'),
                'DTEND;TZID='   . $tz . ':' . $dtEnd->format('Ymd\THis'),
                'SUMMARY:'   . $this->escape($summary),
                'DESCRIPTION:' . $this->escape($position->name . "\n" . tenant('name')),
                'LOCATION:'  . $this->escape(tenant('name')),
                'STATUS:CONFIRMED',
                'END:VEVENT',
            ]);
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    private function escape(string $value): string
    {
        return str_replace(["\r\n", "\n", ",", ";", "\\"], ["\\n", "\\n", "\\,", "\\;", "\\\\"], $value);
    }
}
```

---

## What Events Are Included

| Scenario | Included? | Reason |
|---|---|---|
| Locked week assignment | ✓ Yes | Finalized — safe to show |
| Unlocked week assignment | ✗ No | Draft plan, still changing |
| Past assignments (up to 2 months) | ✓ Yes | Calendar history |
| Marketplace swap accepted | ✓ Yes | Assignment updated, same UID |
| Assignment removed from plan | Appears as deleted on next sync | iCal clients detect removed VEVENTs by UID |

**On swap accepted:** the `plan_assignment` row is updated in place (same `id`, new `user_id`). Because the event UID is `assignment-{id}`, the original employee's feed will no longer contain that UID → their calendar removes it. The new employee's feed will contain it → their calendar adds it. No extra work needed.

---

## Token Management (Livewire: ProfileSettings)

Add to `ProfileSettings` Livewire component:

```
── Kalendárny feed ─────────────────────────────────────────
  Skopírujte odkaz a pridajte ho do svojho kalendára:

  [ https://kino.app.com/feed/abc123...xyz.ics ] [Kopírovať]

  [Obnov odkaz]  ← destroys old token, generates new one
  ⚠ Po obnovení budete musieť odkaz znova pridať do kalendára.
```

`generateToken()` method in `ProfileSettings`:
```php
public function generateToken(): void
{
    $this->authorize('update', auth()->user());

    auth()->user()->update(['ical_token' => Str::random(64)]);

    $this->dispatch('token-refreshed');
    session()->flash('status', 'Odkaz bol obnovený.');
}
```

---

## Filament Admin View

In `UserResource` table: add a "Feed" icon action that copies/shows the feed URL for that user. Useful for helping an employee who can't find it themselves.

In `UserResource` edit form: show the feed URL (read-only) with a "Regenerate token" action.

---

## Security Considerations

- Token is 64 random hex chars — 256 bits of entropy, brute-force infeasible
- Feed is read-only — no write operations possible via the token
- Feed does NOT expose: email, rates, pay information, other employees' data
- If an employee's account is deactivated (`is_active = false`), feed returns `404` immediately
- Token is separate from auth session — revoking token doesn't log user out
- Rate-limited to 60 req/min (generous for calendar apps, blocks scrapers)
- HTTPS only (enforced by `ForceHttps` middleware on all routes)
- Log token generation/rotation in activity log: `"iCal token regenerated"`

---

## Calendar Client Compatibility Notes

| Client | Subscription interval | Notes |
|---|---|---|
| Google Calendar | Every 24h (fixed, not configurable by server) | `REFRESH-INTERVAL` header ignored |
| Apple Calendar (iOS/macOS) | Every 1h (default, configurable in settings) | Respects `X-PUBLISHED-TTL` |
| Outlook (desktop) | Every 3h by default | Configurable per subscription |
| Thunderbird | Manual or 1h | Fine |

**Google Calendar caveat:** Google caches for 24h regardless of headers. This means an employee's Google Calendar may be up to 24h behind after a plan update. Mention this in the UI: "Google Kalendár sa aktualizuje raz za 24 hodín. Pre okamžité zmeny skontrolujte app."

---

## Implementation Checklist

- [ ] Add `ical_token` column to `users` migration
- [ ] `ICalBuilder` service
- [ ] `ICalFeedController` + route
- [ ] Token generation/rotation in `ProfileSettings` Livewire component
- [ ] Feed URL display in `ProfileSettings` UI
- [ ] "Feed" action in Filament `UserResource`
- [ ] Regenerate token action in Filament `UserResource` edit
- [ ] Activity log on token rotation
- [ ] Feature test: valid token returns 200 text/calendar, invalid returns 404, inactive user returns 404, only locked weeks appear
