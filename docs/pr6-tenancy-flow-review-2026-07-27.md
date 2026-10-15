# Flow review — PR #6 "multitenant rewrite"

Scope: same as the companion readability review — `app/Http`, `app/Models`,
`app/Services`, `app/Livewire`, `app/Policies`, `app/Traits` in
`git diff origin/master...feature/bootstrap`.

## Flow map — main path: signing up for a calendar day

1. Request hits `web`/`tenant` middleware group (`app/Http/Kernel.php`): `auth` →
   `EnsureUserIsActive` → `SetCurrentTeam`.
2. `SetCurrentTeam::handle()` resolves the acting team, calls
   `PermissionRegistrar::setPermissionsTeamId()`, and binds `Team::class` into the
   container — **this is the point where "current team" is decided for the whole
   request**, but nothing downstream receives it as a parameter; every consumer re-fetches
   it via `app(Team::class)` or the registrar.
3. `CalendarController::index()/show()` → private `week()` → `WeekService::alignFromRequest()`
   → renders `calendar.index` with `DayCard` Livewire components.
4. `DayCard::signUp()` (a later, separate Livewire request — not part of the original HTTP
   trace) re-resolves `app(Team::class)`, checks absences inline, calls
   `$this->authorize('create', [Assignment::class, $team, $day])`, which enters
   `AssignmentPolicy::create()`.
5. `AssignmentPolicy::create()` re-checks the same absence-overlap logic (duplicated from
   `DayCard::signUp()`), then `WeekLock::locked()`.
6. `Assignment::firstOrCreate()` fires `BelongsToTeam::bootBelongsToTeam()`'s `creating`
   hook, which fills `team_id` from `PermissionRegistrar::getPermissionsTeamId()` — the
   *third* independent read of the same value set in step 2.

## Where the trace breaks

| Location | Hazard | Impact | What breaks the trace | Suggested |
|---|---|---|---|---|
| `SetCurrentTeam.php:2234` → `BelongsToTeam.php:6470` → `User.php:5164` | hidden-state | High | "Current team" is never passed as data — it's stashed in Spatie's `PermissionRegistrar` (a library-owned global meant for role resolution) and re-read independently by the trait's global scope, by `hasPermissionInTeam()`, and by every controller/service via `app(Team::class)`. A reader tracing `DayCard::signUp()` cannot see where its team came from without already knowing this convention exists. | Keep the convention (rewriting it is out of scope for a rewrite this size) but add one central doc comment (e.g. on `Team.php` or a short ARCHITECTURE note) that names all three read sites, so a trace has one place to look up "where does team scoping come from" instead of discovering it three times |
| `User.php:5156-5174` (`hasPermissionInTeam()`) | hidden-state | High | A method that reads like a pure predicate (`hasPermissionInTeam(): bool`) calls `setPermissionsTeamId()` as a side effect when a `$team` override is passed — so calling this check can silently change what team-scoped queries return for the rest of the request if the caller passes a non-current team | Split into a pure check (assume registrar is already correctly set, per `SetCurrentTeam`) and, if cross-team checks are genuinely needed, a separately named method that makes the mutation explicit (`checkPermissionAs(Team $team, string $permission)`) |
| `User.php:5096-5118` (`approvedTeams()`) | control-shape | Med | Two nested cache reads with a conditional re-query ("if empty, refetch and overwrite") plus an instance-level memo — three exit points returning three different provenances of the same collection. Tracing why a caller sees stale vs fresh data requires simulating all three branches | Name the self-heal branch as a separate step, e.g. `refreshIfEmpty(Collection $cached): Collection`, so the "happy path" (memo hit → cache hit) reads straight down and the workaround is visibly a workaround |
| `DayCard::signUp()` (Livewire) vs `AssignmentPolicy::create()` | data-flow / duplication | Med | The identical "does this date fall inside a reported absence" query+filter is written twice — once inline in the component, once inside the policy that the component calls right after. A reader following the component assumes the absence check is *the* gate, then hits the same check again one level down inside `authorize()`, unsure which one is authoritative | Delete the inline check in `DayCard::signUp()` and let `$this->authorize()` be the single gate — the toast message can be produced from a caught `AuthorizationException` or a pre-check call to `Gate::allows()` instead of duplicating the query |
| `WeekLockController` / `AssignmentPolicy::weekLocked()` / `CalendarController` | data-flow | Low | "Is this week locked" is computed three separate ways across the diff: `WeekLock::locked()` (policy), a `pluck('week_start')->map()->toArray()` inline in `CalendarService::forWeek()` and again in `WeekService::defaultUnlockedWeek()` | Route all three through `WeekLock::locked()` / a shared `WeekLock::lockedStartsBetween()` scope so there's one place that defines "locked" |
| `app/Http/Controllers/Calendar/CalendarController.php:682-698` (`week()`) | step-naming | Low (positive) | Good example: reads top-down as `align week → compute next → render`, with the "why null" nextWeek logic commented in place rather than hidden in a helper | none — cite as the reference model |

## Resequencing sketch — `DayCard::signUp()`

Proposed reading order (structure only):

1. `assertNoConflictingAbsence()` — currently a 6-line inline block; name it so the
   happy path in `signUp()` is one line.
2. `$this->authorize('create', …)` — single source of truth for both absence and
   lock/role checks (folds the policy's own absence check in, once the duplication above
   is removed).
3. `createAssignment()` — the `firstOrCreate` call, named.
4. `notifyPeers()` — the two `dispatch()` calls, named as one step.

## Verify list (possible correctness issues — not fixed here)

- **verify**: `UserTable::deny()` (`app/Livewire/UserTable.php:3831`) sets only
  `is_active = false`, while `UsersDataTable::deny()` (`app/Livewire/UsersDataTable.php:4038`)
  also stamps `approved_at = now()` before deactivating. If both components are reachable
  in the shipped UI, denying the same pending user through one screen vs the other leaves
  the `team_user` row in different states. Worth confirming which component is actually
  wired into a route/view before this ships.
- **verify**: `User::approvedTeams()` (`app/Models/User.php:5106-5115`) — the "cache is
  empty → refetch and overwrite" branch assumes an empty result is always stale rather
  than genuinely correct (a user approved in zero teams). If that's a real state (e.g. a
  freshly registered, not-yet-approved user), this method will re-query the DB on every
  single call for that user, defeating the cache silently.

## Prioritized punch list

1. Remove the duplicated absence-overlap check between `DayCard::signUp()` and
   `AssignmentPolicy::create()`; let authorization be the single gate.
2. Confirm and fix the `UserTable`/`UsersDataTable` `deny()` divergence (also flagged in
   the readability review as duplication).
3. Split `User::hasPermissionInTeam()`'s side-effecting branch out of the predicate name.
4. Name the self-heal branch inside `User::approvedTeams()`, and confirm the "empty means
   stale" assumption is intentional.
5. Consolidate the three independent "is this week locked" computations behind one method.
