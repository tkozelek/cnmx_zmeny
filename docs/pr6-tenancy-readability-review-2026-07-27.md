# Readability review — PR #6 "multitenant rewrite"

Scope: `git diff origin/master...feature/bootstrap` restricted to `app/Http`, `app/Models`,
`app/Services`, `app/Livewire`, `app/Policies`, `app/Traits` (103 files, ~3,800/1,900 lines).
Views and migrations excluded per request.

## Executive summary

Overall quality is high for a from-scratch rewrite — most new classes carry an intent
comment, use constructor injection, `declare` typed returns, and give named rationale
for non-obvious decisions ("ponytail:" comments). The worst offenders are concentrated
in three places: (1) duplicated user-approval logic across `UserTable` and
`UsersDataTable` that has silently diverged in behavior, (2) a self-healing/double-cache
method on `User` (`approvedTeams()`) whose logic is hard to justify by reading it, and
(3) a large branchy `AbsencePolicy::delete()` that would benefit from named predicate
extraction despite already being heavily commented.

Suggested reading order for a newcomer: `app/Http/Middleware/SetCurrentTeam.php` →
`app/Traits/BelongsToTeam.php` → `app/Models/Team.php` → `app/Models/User.php` →
`app/Policies/AssignmentPolicy.php` → `app/Http/Controllers/Calendar/*`. This traces the
tenancy mechanism end to end before hitting any feature code.

## Findings by file

| Location | Category | Impact | Current | Suggested | Why |
|---|---|---|---|---|---|
| `app/Models/User.php:5096-5118` (`approvedTeams()`) | structure | High | Instance memo + `Cache::remember`, then re-queries and overwrites the cache if the cached result is empty | Extract the "empty → refetch" branch into a named private method (`refreshIfStale()`), or drop the empty-check workaround and document why the cache can go stale instead | A reader has to reconstruct that this exists to paper over a stale-cache race after approval; the comment explains intent but the shape (nested remember + manual re-check) still requires re-deriving what problem it solves |
| `app/Livewire/UserTable.php:3831-3839` vs `app/Livewire/UsersDataTable.php:4038-4046` | duplication | High | Both have `deny()` for the same "block a pending user" action, but `UserTable::deny()` only sets `is_active=false` while `UsersDataTable::deny()` also stamps `approved_at`. `accept()` is duplicated too. | Move to `AbsenceService`-style shared service (`UserMembershipService::approve()/deny()`) used by both components | Two components doing the same domain action with subtly different persisted state is exactly the kind of drift a future dev won't notice until a bug report |
| `app/Policies/AbsencePolicy.php:5362-5418` (`delete()`) | structure | Med | One ~55-line method with 5 sequential conditions mixing ownership, permission, grace period, and retention window | Extract `isWithinGracePeriod()`, `isRetentionElapsed()`, `canManageTeamAbsences()` as named private methods | Excellent comments already, but the reader still has to hold 4 concepts in their head at once inside one method body |
| `app/Models/User.php:5156-5174` (`hasPermissionInTeam()`) | separation | Med | A boolean-check method calls the global `setPermissionsTeamId()`, mutating shared registrar state as a side effect | Note in the docblock that this mutates global team context, or push the team-id set to the caller/middleware only | A method named like a pure predicate (`hasX`) silently changing global state surprises a reader who calls it just to check something |
| `app/Http/Controllers/Auth/RegisterController.php:440-449` (`resolveTeam()`) | naming | Low | Fine as-is; flagged only because behavior (auto-pick when exactly one team) is non-obvious without the docblock | none — already has a one-line doc | — |
| `app/Livewire/UsersDataTable.php:3948-4026` (`columns()`) | separation | Low | Column formatting mixes HTML string building with role/pending lookups inline in closures | Acceptable given the vendored table package's API constraints; only worth revisiting if more columns are added | Long lines of raw HTML inside `format()` closures are harder to scan, but this matches the package's idiom and is bounded |
| `app/Services/WeekService.php` (whole file) | (positive) | — | Old procedural `Week`/`Day` model juggling replaced with small pure date-math methods (`start`, `end`, `next`, `clampForward`) | none | Good reference model for the rest of the module — cite as the bar other services should match |

## Rename map

No renames proposed — naming in the new code is already intention-revealing
(`approvedTeams`, `weekStartDay`, `isSelfSignup`, etc.).

## Prioritized punch list

1. Deduplicate `UserTable`/`UsersDataTable` accept/deny into one service; fix the
   behavioral drift in `deny()` while doing so.
2. Simplify or clearly comment the empty-cache-refetch branch in
   `User::approvedTeams()`.
3. Extract named predicates from `AbsencePolicy::delete()`.
4. Document the side effect in `User::hasPermissionInTeam()`.
