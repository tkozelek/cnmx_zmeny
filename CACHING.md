# Caching

Application-level cache keys used outside the framework's own caching (Spatie Permission's
`permission.cache`, Eloquent's per-instance relation cache). Keep this file in sync whenever a
cache key is added, renamed, or its invalidation point changes — this is the map an AI agent (or
a human) needs to trust cached data without re-deriving it from the code each time.

| Key | Set in | Value | TTL | Invalidated in |
|---|---|---|---|---|
| `user:{id}:approved-teams` | `App\Models\User::approvedTeams()` | `Collection<Team>` of every team the user is an approved (non-pending) member of | 10 min, plus a re-query-if-empty self-heal on read | `App\Models\User::forgetApprovedTeamsCache()`, called from `App\Livewire\UsersDataTable::accept()`/`::deny()`. Also self-corrects on direct DB edits (e.g. an admin manually detaching a team via tinker) once the TTL expires, or immediately if the cached value happens to be empty. |
| `team:{id}:settings` | `App\Models\Team::cachedSettings()` | The team's single `TeamSetting` row (or null) | 10 min | `App\Models\TeamSetting::booted()`'s `saved` event hook — fires on any save through any path (settings page, seeder, tinker, tests), not just one call site. |

## Why this exists

`approvedTeams()` backs `User::isApprovedIn()`, which is on the hot path for every request
(`EnsureUserIsActive`, `SetCurrentTeam` middleware) and re-queried again by the team switcher
(rendered twice — desktop + mobile nav) and `SettingsController`. Before caching, one page load
fired 4+ near-identical `team_user` queries. It's also memoized on the `User` instance itself so
one request never hits the cache store more than once.

## Adding a new cache key

Only cache data that:
- is read far more often than it changes (team membership approval is the model case — set once
  by an admin, read on every request afterward), and
- has a small, enumerable set of write sites you can point invalidation at.

Don't cache page-specific/frequently-changing data (absences, assignments, anything rendered by
`rappasoft/laravel-livewire-tables`) — the risk of stale reads outweighs the query savings there.

Add the new key to the table above in the same commit that introduces it.
