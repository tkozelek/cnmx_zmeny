# 21 — Backend rewrite to the new schema: progress & handoff

> **Backend pass and Blade/JS view pass complete (2026-07-25).**
>
> All admin, profile, help, holiday/absence, and hours views have been converted to the new
> schema, Spatie roles, and dark slate design system.
>
> `migrate:fresh --seed`, `route:list`, `test` (41 tests / 110 assertions), and Pint all pass.
>
> **Dev login:** `tommyside@centrum.sk` / `asdasd` (admin), or `admin@kino.test` / `password`.
> Herd serves this at `http://cnmx_zmeny.test` — **underscore, not kebab-case**.
>
> `_planning/20-schema-rework-handoff.md` remains the authority on the schema.

---

## 1. What was built

### Models

| Model | Notes |
|---|---|
| `Team` | `weekStartDay()`, `weekLookahead()`, `absenceDeadlineHours()` fall back to `TeamSetting` constants. |
| `TeamSetting` | `DEFAULT_WEEK_START_DAY = 3`, `DEFAULT_WEEK_LOOKAHEAD = 5`, `DEFAULT_ABSENCE_DEADLINE_HOURS = 48`. |
| `Position` | `scopeSelectable()`, `label()` (code ?: name). |
| `Assignment` | `scopeBetweenDates()`, `isSelfSignup()`. `start_time`/`end_time` left uncast (TIME columns). |
| `Absence` | `FOREVER` sentinel, `scopeOverlapping()` in doc 20's index order, `scopeActive()`, `scopePast()`, `covers()`, `isRecurring()`, `isOpenEnded()`. |
| `WeekLock` | `UPDATED_AT = null`, static `locked(int $teamId, $weekStart)` which drops the tenant scope because the team is passed explicitly. |
| `Media` | `scopeForWeek()`, `scopeVisible()`. |
| `Shift` | `workedMinutes()`, `payrollDate()`, `scopeInMonth()`. The overnight fix. |
| `Rate` | `break_deduction`; the old `'break' => 0` fillable bug is gone. |
| `User` | `id_role`, `role()`, `hasRole()`, `isAdmin()`, `days()` and the date accessors deleted; Spatie `HasRoles` added; `teams()`, `currentTeam()`, `switchTeam()`, `isApprovedIn()`, `assignments()`, `absences()`, `shifts()`, `rate()`, `media()`. |

**One trap found and fixed:** every DATE column is cast `date:Y-m-d`, **not** `date`. A plain
`date` cast writes `"2026-07-23 00:00:00"` into the column. MySQL hides this by truncating a
DATE column, but the value never matches a `Y-m-d` string in a `where`, so
`WeekLock::firstOrCreate(['week_start' => …])` silently missed and hit the unique index. On
SQLite the time is kept and it fails outright. Applies to `Assignment::date`,
`Absence::date_from`/`date_to`, `WeekLock::week_start`, `Media::week_start`.

### Services

- `WeekService` — pure arithmetic: `start()`, `end()`, `days()`, `range()`, `next()`,
  `previous()`, `alignFromRequest()` (never trusts a URL date), `clampForward()` (bounded by
  `week_lookahead`; the past is deliberately left open so admins can look back).
- `CalendarService::forWeek()` — the week's dates, lock flag and media; absences and signup
  counts only for admins, because absence reasons are management information. It deliberately
  does **not** load assignments: each `DayCard` fetches its own (§4).
- `AbsenceService` — `activeFor()`, `pastFor()`, `end()` (shorten to today, or delete if it
  has not started).
- `MediaService` — `store()`, `delete()`. Deletes the row even when the file is already gone.
- `ShiftService::syncMonth()` — rolls `ends_at` to the next day when it precedes `starts_at`.

### Controllers, requests, policies, middleware

All written. Notable decisions beyond the previous plan:

- `SetCurrentTeam` binds the resolved `Team` into the container
  (`app()->instance(Team::class, $team)`), so controllers, form requests and Livewire just
  type-hint or `app(Team::class)` it. Less plumbing than a request attribute at every site.
- `Kernel` gained the `tenant` middleware group; the dead `$routeMiddleware` property is gone
  and Spatie's `role`/`permission`/`role_or_permission` aliases are registered.
- `LoginController` checks **email and password only**. Folding `is_active` into the credentials
  was tried and reverted: it makes a blocked account indistinguishable from a wrong password and
  leaves `EnsureUserIsActive`'s accurate messages unreachable, since such a user can never
  authenticate to reach the middleware. The middleware ejects them on the next request instead —
  one decision point, one accurate message. Credential failures attach to the `email` field so
  the form renders them inline; `session('error')` renders in an `x-alert` on the login page.
- `RegisterController` attaches a **pending** membership (`approved_at` null) and assigns
  `employee`; it resolves the team automatically while only one active cinema exists.
- `Admin\UserController::destroy` **deactivates** (`is_active = false`). `shifts.user_id` and
  `rates.user_id` are RESTRICT, so a real delete would fail for anyone who has ever worked.
- `WeeklyScheduleExport` rebuilt off `assignments`; second sheet counts distinct dates per
  person, so two positions on one Friday is still one Friday.
- `UnverifiedUserComposerViewComposer` → `PendingMemberCountComposer`. The 15-minute cache is
  gone: it needed invalidating from three places and still went stale.

### Security fixes made along the way

- **`routes/api.php` `/api/shifts` + `/api/shifts/bulk` deleted.** They took `user_id` from
  the query string with **no authentication and no team scoping** — anyone could read or
  overwrite anyone's worked hours. Replaced by `shifts.index`/`shifts.store` inside `tenant`,
  which only ever act on the logged-in user unless an admin asks for someone else.
- `Media::download` streams through the app and checks `is_visible`, so an invisible file
  cannot be fetched by guessing its public URL.
- `User::switchTeam()` refuses teams the user is not approved in.
- `StoreAssignmentRequest` scopes `position_id` to the current team (it is optional now, see
  §4), so a borrowed id is rejected in validation rather than by the composite FK.

### Tests

`phpunit.xml` (in-memory SQLite), `tests/TestCase.php` (with `tenant()` / `member()` helpers
that prime Spatie's registrar — without it every team-owned query runs unscoped and passes
tests it should fail) and `tests/CreatesApplication.php`.

41 tests / 110 assertions, all passing:

- `Unit\ShiftTest` — 21:00 → 01:30 overnight shift: 240 paid minutes, booked to the start day.
- `Feature\DayCardTest` — the Livewire signup path: sign up (no position), the shared session
  note gets attached, double-click is idempotent, withdraw, locked week ⇒ 403, someone else's
  row ⇒ 403, admin may remove anyone's.
- `Feature\CalendarPageTest` — the page actually renders (a stale `route()` in any partial is a
  500, not a missing button), locked week shows no signup, a mid-week URL resolves to its week.
- `Feature\LoginTest` — success, wrong password, unknown email, the error being *visible* on the
  page, blocked ⇒ "Účet je zablokovaný.", pending ⇒ "Ešte si nebol/a overený…".
- `Feature\AssignmentSignupTest` — the plain POST/DELETE path: sign up, remove own, locked
  week ⇒ 403, someone else's row ⇒ 403, admin may still place someone in a locked week.
- `Feature\AbsenceTest` — create, past-deadline ⇒ error, delete own, someone else's ⇒ 403,
  ending a running absence shortens it, ending a future one deletes it.
- `Feature\WeekLockTest` — admin locks/unlocks, employee ⇒ 403, any date in the week hits the
  same lock row.
- `Feature\MembershipApprovalTest` — Livewire accept/deny, pending and blocked users bounced
  to login and logged out.

`ShiftTest` extends `Tests\TestCase`, not PHPUnit's: Eloquent resolves a connection while
booting model traits, so even an unsaved model needs the framework up. No DB is touched.

### Factory gotcha worth remembering

`TeamFactory::configure()` is **Laravel's own hook**, called from the factory constructor.
The first attempt defined it as a state method *and* called `->configure()` explicitly, which
registered the `afterCreating` callback twice and violated `team_settings.team_id`'s unique
index. It now lives in the real hook, so every `Team::factory()` gets its settings row with
nothing to remember at the call site.

`AssignmentFactory` defaults `position_id` to null (matching self-signup). Its `onPosition()`
state takes `team_id` **from the position** rather than generating its own team — two
independent factories produce two different teams, and the `(team_id, position_id)` composite FK
correctly rejects that pairing, which makes for a confusing test failure.

---

## 2. Decisions carried over (still not up for re-litigation)

The numbered decisions from the previous revision all held. Tenant context is Spatie's
registrar; no Day/Week view models; week arithmetic in `WeekService` and lock state on
`WeekLock`; the absence deadline is validation and
the week lock is authorization; role rows are global with per-team assignments; `Position`
CRUD is deliberately unbuilt (Filament phase, doc 05); `Loggable` kept minus its `creating`
hook; `config/constants.php` keeps only `messages`.

---

## 3. Route names (new)

`routes/web.php` was rewritten. Slovak URIs kept; week navigation is `/week/{date}` with a
`Y-m-d` date. Names changed, which is what the view pass has to follow:

| Old | New |
|---|---|
| `calendar.toggleUser` (AJAX) | `assignments.store` / `assignments.destroy` |
| `admin.calendar.userdestroy` | `assignments.destroy` |
| `admin.calendar.lock` | `weeks.lock` / `weeks.unlock` |
| `admin.calendar.export` | `schedule.export` |
| `holiday.*` | `absences.*` |
| `files.*` | `media.*` (`media.visibility` replaces `files.show`) |
| `settings.password` | `settings.password.edit` / `.update` |
| `admin.pouzivatelia.add` | `admin.users.store` |
| `bugreport.*` | gone — the four error views and both nav partials now link to `help` |
| `hours.store` | `shifts.store` (and `shifts.index` for reads) |

New: `teams.switch`, `logout` is now **POST** `/odhlasenie`.

---

## 4. The calendar page — done, as Livewire day cards

The post-login screen works end to end (verified in a browser against Herd at
`http://cnmx_zmeny.test`, **underscore not kebab**, and by `CalendarPageTest`).

**Each day is one `App\Livewire\DayCard`.** Seven per page, each owning its own signup state,
so clicking one re-renders only that card. This replaced the legacy `calendar.toggleUser`
endpoint, which returned a blob of rendered HTML for jQuery to splice into
`.users-container` — the error the user originally hit was the leftover
`route('calendar.toggleUser')` in `layouts/layout.blade.php`.

Design choices in the card:

- **Signup picks no position.** It is one button per day; an admin fills the position in later
  when building the plan. This reversed an earlier attempt at a per-card position dropdown —
  see the schema note below, because it changed two things in the migration.
- The button toggles: `Zapísať` → `Odpísať` once you are on the day.
- **The note is one shared field above the grid**, `App\Livewire\ExtraNote`, kept in the
  **session** rather than passed down as a prop. That is what makes it survive week navigation
  and a reload, and it means typing does not re-render the seven cards. It persists until
  explicitly cleared (X inside the input), so signing up for six days with "od 15:00" is one
  piece of typing. `DayCard::signUp()` reads it from the session. Replaces the legacy
  `partials/_extratext`.
- `Position::label()` (which prefers the short code) is still used for the badge on a row that
  *has* a position, so an admin-assigned position shows up.
- The card renders as locked **and** `signUp()` re-checks the policy, so replaying the Livewire
  request against a locked week is refused rather than merely hidden.

### Schema change this forced

Two edits to `2026_07_24_100050_create_assignments_table.php`:

| Was | Now | Why |
|---|---|---|
| `position_id` NOT NULL | **nullable** | Nothing picks a position at signup. NULL honestly means "not decided yet". The composite `(team_id, position_id)` FK still guarantees a set position belongs to the same team; it simply is not enforced while NULL. |
| `unique(user_id, date, position_id)` | **`unique(user_id, date)`** | One signup per person per day, which is what a single button means. The old index gave *no* protection once `position_id` was nullable — MySQL treats NULLs as distinct, so a double-clicked button would have inserted two rows. |

This reverses doc 20 §7's "same user, same day, two different positions ⇒ accepted (2 rows)".
That capability only made sense while signup chose a position. **If per-position assignment
comes back with the Filament plan builder, `unique(user_id, date)` is the index to widen.**

`assignments.store` / `assignments.destroy` (plain POST/DELETE, `AssignmentController`) still
exist and were updated to match — nothing links to them now that the card handles signup, but
they are the only path by which an admin can sign *somebody else* up, which the card does not
offer. Covered by `AssignmentSignupTest`.

**A tenancy hole was found and fixed doing this.** Livewire's `/livewire/update` is its own
route and only re-runs middleware registered as *persistent*; auth is on that list by default,
`SetCurrentTeam` and `EnsureUserIsActive` were not. Every Livewire action would therefore have
run with no team in the registrar — `BelongsToTeam` stops filtering and `app(Team::class)`
builds an empty model, so a component would read and write across every cinema. Registered in
`AppServiceProvider::persistTenantMiddlewareThroughLivewire()`. This affected the pre-existing
`UserTable` too; the tests missed it because they set the container binding directly.

Deleted as superseded: `components/one-day`, `day-user-list`, `day-user-row`, `day-button`,
`partials/_extratext`, and `livewire/user-button.blade.php` (an orphan view with no component
class). `app.js` lost the signup AJAX and the hardcoded `/upload/{id}/destroy` handler.

The "Zobraziť mená zamestnancov" toggle now flips a `hide-names` class on `<html>` with a CSS
rule in `app.css`, instead of setting `display:none` on each row — a Livewire re-render
replaces the rows and would have discarded inline styles.

### Visual pass

Applied `_planning/19-design-system.md` to the calendar path: page background `slate-950` with
`slate-900` cards (was `bg-gray-700` — wrong ramp, and too light for cards to read as raised),
**indigo** for the primary action, semantic emerald/amber/rose/slate for state only, `text-sm`
body, `font-medium`/`font-semibold` rather than `font-bold`, `max-w-7xl` with
`px-4 sm:px-6 lg:px-8` gutters, `gap-*` instead of margins, and ≥44px touch targets
(`min-h-11`) on every control. Mobile-first: one column, 7-up only from `xl`.

Restyled: `livewire/day-card`, `livewire/extra-note`, `calendar/index`, `components/date`
(week nav, now with a "Dnes" button), `components/table` + `table-row`/`table-cell`/
`table-cell-header`, `partials/_toggle`, `partials/_fileuploadmodal`, `layouts/layout` body.

`partials/_adminbutton` and `partials/_nonadminbutton` are **deleted** — collapsed into one
toolbar in `calendar/index`. The second was a hand-rolled duplicate of the same files modal
that `_fileuploadmodal` already provides, and `_fileupload` already gates the upload form on
admin, so one include serves both roles.

The app remains **single-theme dark** — no `dark:` variants. Doc 19 wants dark as the default
with a light variant available; adding the dual theme is a separate job and is not done.

## 5. Remaining work: the rest of the Blade pass

Still referencing removed shapes (the calendar path and the nav/error views are done):

```
admin/edit, admin/partials/_createuser, components/tablerows,
help/index, holiday/index, profile/index
```

What they need:

- `$user->isAdmin()` / `config('constants.roles.*')` → `@can` or `$user->hasRole('admin')`.
- `$absence->popis` → `$absence->reason`; `date_canceled` no longer exists.
- The route renames in §3.
- Registration and admin create-user forms post `role` (a name from `App\Enums\Role`), not
  `id_role`.
- Reference implementations already converted: `livewire/user-table.blade.php` (role badge,
  pending/blocked), `calendar/index.blade.php`, `partials/_fileupload` (media routes),
  `partials/_adminbutton` (POST/DELETE lock), `components/date` (date-based week nav),
  `components/logout-button` (logout is POST now).
- `resources/js/app.js` still calls `/api/shifts` and `/api/shifts/bulk`, which are deleted —
  point the hours screen at `shifts.index` / `shifts.store`. That screen is broken until then.

Also outstanding, deliberately:

- `ProfileController` uses MySQL's `WEEKDAY()`, so the profile screen is not SQLite-portable.
  Fine for the app; if a profile test is ever wanted, that query needs abstracting.
- `Position` CRUD, admin-declared headcount, plan templates — Filament phase, doc 20 §5.
